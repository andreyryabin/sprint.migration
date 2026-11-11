import {ajax} from "main.core";
import {markRaw} from "ui.vue3";
import {colorbind, colorize, getMessage, makeString, progressMessage} from "../helpers";

import {StringField} from '../fields/StringField';

export const MigrationBuilders = {
    components: {
        StringField
    },

    props: {
        config: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            loading: 0,
            running: false,
            replay: false,
            messages: [],
            progress: [],
            builders: [],
            selected: '',
            filter: {
                search: ''
            },
        };
    },
    created() {
        this.refreshBuilders();

        this.filter = this.getFilter(this.filter);
    },

    computed: {
        messagesCount() {
            return this.messages.length;
        },

        filtered() {
            if (this.filter.search) {
                let s1 = makeString(this.filter.search).toLowerCase();
                return this.builders.filter(item => {
                    let s2 = [item.name, item.title, item.group].join(' ').toLowerCase();
                    return s2.includes(s1);

                })
            }

            return this.builders;
        },

        grouped() {
            let tree = {};

            this.filtered.forEach((builder) => {
                if (!tree[builder.group]) {
                    tree[builder.group] = [];
                }
                tree[builder.group].push(builder);
            });

            return tree;
        },

        summaryLabel() {
            if (this.filtered.length !== this.builders.length) {
                return this.filtered.length + ' / ' + this.builders.length
            }
            return this.builders.length;
        }
    },

    watch: {
        messagesCount() {
            this.$nextTick(() => {
                const div = this.$refs.migration_log;
                if (div) div.scrollTop = div.scrollHeight;
            });
        },
    },
    methods: {
        getMessage,
        progressMessage,
        colorize,
        colorbind,

        getFilter(def) {
            return {...def};
        },

        saveFilter() {
            this.refreshBuilders();
        },

        processResponse(resp) {
            if (resp.data && resp.data.messages) {
                this.messages.push(...resp.data.messages.map(markRaw));
            }

            if (resp.data && resp.data.progress) {
                this.progress = resp.data.progress.map(markRaw);
            }

            if (resp && resp.errors) {
                resp.errors.forEach(item => this.messages.push(markRaw({
                    type: 'error',
                    message: item.message
                })));
            }

            if (resp?.status === 'error' && resp.data?.ajaxRejectData?.xhr?.responseText) {
                this.messages.push(markRaw({
                    type: 'error',
                    message: resp.data?.ajaxRejectData?.xhr?.responseText
                }));
            }
        },

        execAction(action, payload) {
            this.loading = 1;
            return ajax.runAction(
                'sprint:migration.controller.BuilderController.' + action,
                {data: payload, getParameters: {config: this.config}}
            ).then((response) => {
                this.processResponse(response);
                this.loading = 0;
                return response.data;
            }).catch((response) => {
                this.processResponse(response);
                this.loading = 0;
            });
        },

        refreshBuilders() {
            this.execAction('refreshBuilders', {})
                .then((data) => {
                    this.builders = data.builders || [];
                });
        },

        async execBuilder(builder, params) {
            this.running = true;
            this.replay = false;
            try {

                let currentParams = params;
                while (true) {
                    if (!this.running) {
                        this.replay = {
                            builder,
                            params: currentParams,
                            button: 'CONTINUE'
                        }
                        return false;
                    }

                    const data = await this.execAction('execBuilder', {
                        builder,
                        params: currentParams,
                    });

                    if (data.restart) {
                        currentParams = data.params;
                        continue;
                    }

                    if (!data.success) {
                        this.replay = {
                            builder,
                            params: currentParams,
                            button: 'RESTART'
                        }
                        return false;
                    }

                    return true;
                }

            } finally {
                this.running = false;
            }
        },

        replayBuilder() {
            if (this.running) {
                this.running = false;
                return;
            }

            if (this.replay && this.replay.version) {
                this.execBuilder(
                    this.replay.builder,
                    this.replay.params,
                );
            }
        },

        selectBuilder(builder) {
            if (this.selected !== builder) {
                this.selected = builder;
                this.execBuilder(builder, {})
            }
        },
    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row">
            <div class="sp-col">
              <div class="sp-search">
                <div class="sp-search_filter">
                  <StringField
                      v-model:value="filter.search"
                      :placeholder="getMessage('SEARCH')"
                      @press-enter="saveFilter"
                  />
                  <input :value="getMessage('SEARCH')" type="button" @click="saveFilter"/>
                </div>
                <div class="sp-search_summary">
                  {{ getMessage('BUILDERS_CNT') }}:
                  <span class="sp-label" v-text="summaryLabel"/>
                </div>
              </div>
            </div>
          </div>
          <div class="sp-row2">
            <div class="sp-col sp-col-p0">
              <div class="sp-builder_list" ref="builder_list">
                <div
                    v-for="(groupItems, groupName) in grouped"
                    class="sp-builder_group"
                >
                  <div
                      class="sp-builder_caption"
                      v-text="groupName"
                  />
                  <div
                      v-for="item in groupItems"
                      :class="['sp-builder_title', (selected===item.name ? 'sp-active':'')]"
                      @click="selectBuilder(item.name)"
                      v-html="colorize(item.title)"
                  />
                </div>
              </div>
            </div>
            <div class="sp-col sp-col-p0">
              <div class="sp-migration_log" ref="migration_log">
                <div
                    v-for="(item, index) in messages"
                    v-bind="colorbind(item.type)"
                    :key="index"
                    v-text="item.message"
                />
              </div>

              <div v-if="selected" style="position: sticky;top: 10px"/>
            </div>
          </div>
        </div>
      </div>
    `
};
