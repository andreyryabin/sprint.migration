import {ajax} from "main.core";

import {
    colorbind,
    colorize,
    extractResponse,
    getMessage,
    optionsFilter,
    optionsGroup,
    progressMessage,
} from "../helpers";

import {StringField} from '../fields/StringField';
import {TextareaField} from '../fields/TextareaField';
import {SelectField} from '../fields/SelectField';
import {CheckboxListField} from '../fields/CheckboxListField';
import {RadioListField} from '../fields/RadioListField';

export const MigrationBuilders = {
    components: {
        StringField,
        TextareaField,
        SelectField,
        CheckboxListField,
        RadioListField,
    },

    props: {
        config: {
            type: String,
            default: ''
        },
        lang: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            loading1: 0,
            loading2: 0,
            running: false,
            messages: [],
            progress: [],
            builders: [],
            selected: {},
            autoDescription: {},
            filter: {search: ''},
        };
    },
    created() {
        this.createFilter();

        //this.refreshBuilders();
    },

    computed: {
        filtered() {
            return optionsFilter(this.filter.search, this.builders, ['name', 'title', 'group', 'note']);
        },

        grouped() {
            return optionsGroup(this.filtered, 'group');
        },

        summaryLabel() {
            if (this.filtered.length !== this.builders.length) {
                return this.filtered.length + ' / ' + this.builders.length
            }
            return this.builders.length;
        },

        autoDescriptionList() {
            return Object.values(this.autoDescription || {}).filter(Boolean);
        },
    },

    methods: {
        getMessage,
        progressMessage,
        colorize,
        colorbind,

        createFilter() {
            this.filter.search = '';
        },

        saveFilter() {
            this.refreshBuilders();
        },

        execAction(action, payload) {
            return ajax.runAction(
                'sprint:migration.controller.BuilderController.' + action,
                {data: payload, getParameters: {config: this.config, lang: this.lang}}
            ).then((response) => {
                extractResponse(response, this.messages, this.progress);
                return response.data;
            }).catch((response) => {
                extractResponse(response, this.messages, this.progress);
            });
        },

        refreshBuilders() {
            this.loading1 = 1;
            this.execAction('refreshBuilders', {})
                .then((data) => {
                    this.builders = data.builders || [];
                    this.loading1 = 0;
                });
        },

        async execBuilder() {
            if (!this.selected || !this.selected.name) {
                return;
            }

            this.running = true;

            let data;
            try {
                do {
                    if (!this.running) {
                        return false;
                    }

                    data = await this.execAction('execBuilder', {
                        name: this.selected.name,
                        params: this.selected.params,
                    });

                    this.selected.params = data.params;
                    this.selected.fields = data.fields;

                } while (data.restart)

                return data.success;
            } finally {
                this.running = false;
            }
        },

        replayBuilder() {
            if (this.running) {
                this.running = false;
                this.loading2 = 0;
                return;
            }

            this.loading2 = 1;
            this.execBuilder()
                .then(() => {
                    this.loading2 = 0;
                });
        },

        selectBuilder(name) {
            this.messages = [];
            this.progress = [];
            this.selected = {};
            this.autoDescription = {};

            const item = this.builders.find(item => item.name === name);

            if (item) {
                /*Object.assign нельзя item.params будут переданы по ссылке*/
                this.selected = JSON.parse(JSON.stringify(item));
            }
        },

        collectAutoDescription(field, message) {
            if (!field.auto) {
                return;
            }
            this.autoDescription[field.code] = message ? field.title + ': ' + message : '';
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
                      :placeholder="getMessage('SEARCH_BUILDER')"
                      @press-enter="saveFilter"
                  />
                  <div>
                    <input
                        :value="getMessage('SEARCH_BUTTON')"
                        class="adm-btn"
                        type="button"
                        @click="saveFilter"
                    />
                  </div>
                </div>
                <div class="sp-search_summary">
                  <span
                      v-if="loading1"
                      class="sp-loading"
                      v-text="getMessage('LOADING_TEXT')"
                  />
                  {{ getMessage('SUMMARY_BUILDERS') }}:
                  <span
                      class="sp-label"
                      v-text="summaryLabel"
                  />
                </div>
              </div>
            </div>
          </div>
          <div class="sp-row2">
            <div class="sp-col sp-col-p0">
              <div class="sp-builder_list" ref="builder_list">
                <div
                    v-for="(groupItems, groupName) in grouped"
                    :key="groupName"
                    class="sp-builder_group"
                >
                  <div
                      class="sp-builder_caption"
                      v-text="groupName"
                  />
                  <div
                      v-for="item in groupItems"
                      :key="item.name"
                      :class="{'sp-builder_title': true, 'sp-active': selected && selected.name===item.name}"
                      @click="selectBuilder(item.name)"
                      v-html="colorize(item.title)"
                  />
                </div>
              </div>
            </div>
            <div class="sp-col sp-col-p0 sp-relative">
              <div
                  v-if="selected && selected.name"
                  class="sp-builder_form sp-sticky"
              >
                <div
                    v-html="colorize(selected.title)"
                    class="sp-builder-title"
                />
                <div
                    v-if="selected.note"
                    class="sp-info-message"
                    v-html="colorize(selected.note)"
                />
                <div
                    v-for="field in selected.fields"
                    :key="selected.name +':'+ field.code"
                >
                  <div
                      v-if="field.title"
                      class="sp-field-title"
                      v-html="colorize(field.title)"
                  />
                  <div
                      v-if="field.note"
                      class="sp-field-note"
                      v-html="colorize(field.note)"
                  />
                  <component
                      v-if="field.component"
                      :is="field.component"
                      v-model:value="selected.params[field.code]"
                      v-bind="field.props"
                      @update:description="collectAutoDescription(field, $event)"
                  />
                </div>
                <div class="sp-migration_buttons">
                  <input
                      :class="['adm-btn','sp-play']"
                      type="button"
                      :value="getMessage(running ? 'PAUSE' : 'BUILDER_NEXT')"
                      @click="replayBuilder"
                  />
                  <input
                      :class="{'adm-btn': true,'sp-disabled':running}"
                      type="button"
                      :value="getMessage('BUILDER_RESET')"
                      @click="selectBuilder(selected.name)"
                  />
                  <span
                      v-if="loading2"
                      class="sp-loading"
                      v-text="getMessage('LOADING_TEXT')"
                  />
                  <span
                      v-for="(item, index) in progress"
                      :key="index"
                      class="sp-progress"
                      v-text="progressMessage(item)"
                  />
                </div>
                <div class="sp-builder_log">
                  <div
                      v-for="(item, index) in messages"
                      :key="index"
                      v-bind="colorbind(item.type)"
                      v-text="item.message"
                  />
                </div>
                <div
                    v-if="autoDescriptionList.length"
                    class="sp-info-message"
                >
                  <div
                      class="sp-field-title"
                      v-text="'План миграции'"
                  />
                  <div
                      v-if="selected.title"
                      v-text="selected.title"
                  />
                  <div
                      v-for="(text,code) in autoDescriptionList"
                      :key="code"
                      v-text="text"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `
};
