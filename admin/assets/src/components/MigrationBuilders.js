import {ajax} from "main.core";
import {markRaw} from "ui.vue3";
import {colorbind, colorize, getMessage, makeString, progressMessage} from "../helpers";
import {FilterBuilders} from "./FilterBuilders";

export const MigrationBuilders = {
    components: {
        FilterBuilders
    },

    props: {
        config: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            messages: [],
            progress: [],
            builders: [],
            selected: '',
            filter: {},
            loading: 0,
            running: false,
            replay: false,
        };
    },
    created() {

        this.refreshBuilders();
    },

    computed: {
        messagesCount() {
            return this.messages.length;
        },

        filtered() {
            if (this.filter.search) {
                let s1 = makeString(this.filter.search).toLowerCase();
                return this.builders.filter(item => {
                    let s2 = [item.name, item.title].join(' ').toLowerCase();
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
                'sprint:migration.controller.main.' + action,
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

        selectBuilder(name) {
            if (this.selected !== name) {
                this.selected = name;
                this.execBuilder(name, {})
            }
        },
    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row">
            <div class="sp-col">
              <div class="sp-search">
                <FilterBuilders
                    :class="{'sp-search_filter':true}"
                    @changeFilter="filter=$event"
                />
                <div class="sp-search_summary">
                  {{ getMessage('BUILDERS_CNT') }}:
                  <span class="sp-label" v-text="summaryLabel"/>
                </div>
              </div>
            </div>
          </div>
          <div class="sp-row2">
            <div class="sp-col sp-col_builders">
              <div v-for="(groupItems, groupName) in grouped">
                <div
                    class="sp-builder_group"
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
            <div class="sp-col" style="position: relative">
              <div v-if="selected" style="position: sticky;top: 10px"/>
            </div>
          </div>
        </div>
      </div>
    `
};
