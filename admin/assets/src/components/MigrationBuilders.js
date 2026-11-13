import {ajax} from "main.core";

import {
    colorclass,
    colorize,
    filterOptions,
    getMessage,
    groupOptions,
    extractResponse,
    progressMessage
} from "../helpers";

import {StringField} from '../fields/StringField';
import {TextareaField} from '../fields/TextareaField';
import {SelectField} from '../fields/SelectField';
import {CheckboxField} from '../fields/CheckboxField';
import {CheckboxListField} from '../fields/CheckboxListField';
import {RadioListField} from '../fields/RadioListField';

export const MigrationBuilders = {
    components: {
        StringField,
        TextareaField,
        SelectField,
        CheckboxField,
        CheckboxListField,
        RadioListField,
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
            selected: {},
            filter: {search: ''},

        };
    },
    created() {
        this.createFilter();

        //this.refreshBuilders();
    },

    computed: {
        filtered() {
            return filterOptions(this.filter.search, this.builders, ['name', 'title', 'group']);
        },

        grouped() {
            return groupOptions(this.filtered, 'group');
        },

        summaryLabel() {
            if (this.filtered.length !== this.builders.length) {
                return this.filtered.length + ' / ' + this.builders.length
            }
            return this.builders.length;
        }
    },

    methods: {
        getMessage,
        progressMessage,
        colorize,
        colorclass,

        createFilter() {
            this.filter.search = '';
        },

        saveFilter() {
            this.refreshBuilders();
        },

        execAction(action, payload) {
            this.loading = 1;
            return ajax.runAction(
                'sprint:migration.controller.BuilderController.' + action,
                {data: payload, getParameters: {config: this.config}}
            ).then((response) => {
                extractResponse(response, this.messages, this.progress);
                this.loading = 0;
                return response.data;
            }).catch((response) => {
                extractResponse(response, this.messages, this.progress);
                this.loading = 0;
            });
        },

        refreshBuilders() {
            this.execAction('refreshBuilders', {})
                .then((data) => {
                    this.builders = data.builders || [];
                });
        },

        async execBuilder(name, params) {
            this.running = true;
            this.selected = {};
            this.messages = [];
            this.progress = [];
            try {

                let restartParams = params;
                while (true) {
                    if (!this.running) {
                        return false;
                    }

                    const data = await this.execAction('execBuilder', {
                        name,
                        params: restartParams,
                    });

                    if (data.restart) {
                        restartParams = data.params;
                        continue;
                    }

                    this.selected = {
                        name,
                        params: data.params,
                        form: data.form
                    };

                    return data.success;
                }

            } finally {
                this.running = false;
            }
        },

        selectBuilder(name) {
            this.execBuilder(name, {})
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
                  <input
                      :value="getMessage('SEARCH')"
                      class="adm-btn"
                      type="button"
                      @click="saveFilter"
                  />
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
                      :class="{'sp-builder_title': true, 'sp-active': selected && selected.name===item.name}"
                      @click="selectBuilder(item.name)"
                      v-html="colorize(item.title)"
                  />
                </div>
              </div>
            </div>
            <div class="sp-col sp-col-p0 sp-relative">
              <div
                  v-if="selected && selected.form"
                  class="sp-builder_form sp-sticky"
              >
                <div
                    v-html="colorize(selected.form.title)"
                    class="sp-field"
                />
                <div
                    v-for="field in selected.form.fields"
                    class="sp-field"
                >
                  <div
                      class="sp-field-title"
                      v-html="colorize(field.title)"
                  />
                  <div
                      class="sp-field-note"
                      v-html="colorize(field.note)"
                  />
                  <component
                      :is="field.component"
                      v-model:value="selected.params[field.code]"
                      v-bind="field.props"
                  />
                </div>
                <div class="sp-field">
                  <input
                      class="adm-btn"
                      type="button"
                      :value="getMessage('BUILDER_NEXT')"
                  />
                  <input
                      class="adm-btn"
                      type="button"
                      :value="getMessage('BUILDER_RESET')"
                  />
                </div>
                <div
                    v-if="selected.form.note"
                    class="sp-field sp-info-message"
                    v-html="colorize(selected.form.note)"
                />
                <div class="sp-builder_log">
                  <div
                      v-for="(item, index) in messages"
                      v-bind="colorclass(item.type)"
                      :key="index"
                      v-text="item.message"
                  />
                </div>
                <div class="sp-builder_progress">
                  <div
                      v-for="(item, index) in progress"
                      class="sp-progress"
                      :key="index"
                      v-text="progressMessage(item)"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    `
};
