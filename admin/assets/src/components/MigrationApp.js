import {ajax} from "main.core";
import {markRaw} from "ui.vue3";
import {colorbind, colorize, getMessage, progressMessage, showContextMenu,} from "../helpers";

import {MigrationFilter} from "./MigrationFilter"

const STATUS_INSTALLED = 'installed';
const STATUS_NEW = 'new';
const STATUS_UNKNOWN = 'unknown';

const ACTION_UP = 'up';
const ACTION_DOWN = 'down';

export const MigrationApp = {
    components: {
        MigrationFilter,
    },

    props: {
        config: {
            type: String,
            default: ''
        },
        otherConfigs: {
            type: Array,
            default: () => ([])
        },
        webdir: {
            type: String,
            default: ''
        },
        lang: {
            type: String,
            default: ''
        },
        site: {
            type: String,
            default: ''
        },
    },

    data() {
        return {
            versions: [],
            messages: [],
            progress: [],
            builders: [],
            filter: {},
            selectedBuilder: '',
            loading: 0,
            running: false,
            replay: false,
        };
    },
    created() {
        this.filter = JSON.parse(
            localStorage.getItem('sprint_migration_filter_' + this.config) || '{"view":"actual"}'
        );
    },

    computed: {
        summaryCount() {
            let summary = {'new': 0, 'installed': 0, 'unknown': 0};
            this.versions.forEach(({status}) => {
                if (status === STATUS_NEW) summary.new++;
                if (status === STATUS_INSTALLED) summary.installed++;
                if (status === STATUS_UNKNOWN) summary.unknown++;
            });
            return summary;
        },

        summaryLabels() {
            return colorize([
                `[label:red]${this.summaryCount.new}[/]`,
                `[label:green]${this.summaryCount.installed}[/]`,
            ].join(''));
        },

        versionsCount() {
            return this.versions.length;
        },
        messagesCount() {
            return this.messages.length;
        },

    },

    watch: {
        filter(newValue, oldValue) {
            if (newValue !== oldValue) {
                localStorage.setItem('sprint_migration_filter_' + this.config, JSON.stringify(newValue));
                this.refresh();
            }
        },
        versionsCount() {
            this.$nextTick(() => {
                const div = this.$refs.migration_list;
                if (div) div.scrollTop = div.scrollHeight;
            });
        },
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

        refresh() {
            this.execAction('refresh', {filter: this.filter})
                .then((data) => {
                    this.versions = data.versions || [];
                });
        },

        async execVersion(version, action, params, settag) {
            this.running = true;
            this.replay = false;
            try {
                this.execLog(version, action, 'start');

                let currentParams = params;
                while (true) {
                    if (!this.running) {
                        this.execLog(version, action, 'canceled');
                        this.replay = {
                            version,
                            action,
                            params: currentParams,
                            settag
                        }
                        return false;
                    }

                    const data = await this.execAction('execVersion', {
                        version,
                        action,
                        params: currentParams,
                        settag
                    });

                    if (data.restart) {
                        currentParams = data.params;
                        continue;
                    }

                    if (!data.success) {
                        this.execLog(version, action, 'error');
                        this.replay = {
                            version,
                            action,
                            params: currentParams,
                            settag
                        }
                        return false;
                    }

                    this.execLog(version, action, 'finish');
                    this.pickVersion(data.meta);
                    return true;
                }

            } finally {
                this.running = false;
            }
        },

        async execAll(action, settag) {
            for (const version of this.getVersionsByAction(action)) {
                const success = await this.execVersion(version, action, {}, settag);
                if (!success) {
                    return false;
                }
            }

            this.refresh();
            return true;
        },

        execLog(version, action, msg) {
            this.messages.push(markRaw({type: 'info', message: version + '::' + action + '() ' + msg}));
        },

        getVersionsByAction(action) {
            if (action === ACTION_DOWN) {
                return this.versions
                    .filter((item) => item.status === STATUS_INSTALLED)
                    .map((item) => item.version)
                    .reverse();
            }
            return this.versions
                .filter(item => item.status === STATUS_NEW)
                .map((item) => item.version);
        },


        pickVersion(meta) {
            if (meta && meta.version) {
                let index = this.versions.findIndex(item => item.version === meta.version);
                if (index >= 0) {
                    this.versions.splice(index, 1, meta);
                }
            }
        },

        upVersion(version) {
            this.execVersion(version, ACTION_UP, {}, '');
        },

        downVersion(version, params) {
            this.execVersion(version, ACTION_DOWN, {}, '');
        },

        replayVersion() {
            if (this.replay && this.replay.version) {
                this.execVersion(
                    this.replay.version,
                    this.replay.action,
                    this.replay.params,
                    this.replay.settag
                );
            }
        },

        upAll() {
            if (confirm('Confirm install migrations')) {
                this.execAll(ACTION_UP, '')
            }
        },

        downAll() {
            if (confirm('Confirm rollback migrations')) {
                this.execAll(ACTION_DOWN, '')
            }
        },

        upAllWithTag() {
            let settag = prompt('Set migrations tag');
            if (settag) {
                this.execAll(ACTION_UP, settag)
            }
        },


        markVersion(version, status) {
            this.execAction('markVersion', {
                version: version,
                status: status,
            }).then((data) => {
                this.pickVersion(data.meta);
            });
        },

        setVersionTag(version, defaultTag) {
            let settag = prompt('Set migration tag', defaultTag);
            if (settag) {
                this.execAction('setVersionTag', {
                    version: version,
                    settag: settag
                }).then((data) => {
                    this.pickVersion(data.meta);
                });
            }
        },

        moveVersion(version, toConfig) {
            this.execAction('moveVersion', {
                version: version,
                toConfig: toConfig,
            }).then(() => {
                this.refresh();
            });

        },
        deleteVersion(version) {
            if (confirm('Confirm delete migration file')) {
                this.execAction('deleteVersion', {
                    version: version,
                }).then(() => {
                    this.refresh();
                });
            }
        },

        buildContextMenu(e) {
            let menu = [];

            menu.push({
                TEXT: getMessage('UP_START_WITH_TAG'),
                ONCLICK: () => this.upAllWithTag(),
            });
            menu.push({
                TEXT: getMessage('DOWN_START'),
                ONCLICK: () => this.downAll(),
            });

            showContextMenu(e.target, menu);
        },

        buildVersionMenu(e, item) {
            let menu = [];

            if (item.status === STATUS_NEW) {
                menu.push({
                    TEXT: getMessage('VERSION_UP'),
                    ONCLICK: () => this.upVersion(item.version),
                });
                menu.push({
                    TEXT: getMessage('MARK_AS_INSTALLED'),
                    ONCLICK: () => this.markVersion(item.version, STATUS_INSTALLED),
                });
            }

            if (item.status === STATUS_INSTALLED) {
                menu.push({
                    TEXT: getMessage('VERSION_DOWN'),
                    ONCLICK: () => this.downVersion(item.version),
                });
                menu.push({
                    TEXT: getMessage('SETTAG'),
                    ONCLICK: () => this.setVersionTag(item.version, item.tag),
                });
                menu.push({
                    TEXT: getMessage('MARK_AS_NEW'),
                    ONCLICK: () => this.markVersion(item.version, STATUS_NEW),
                });
            }

            if (item.status === STATUS_UNKNOWN) {
                menu.push({
                    TEXT: getMessage('SETTAG'),
                    ONCLICK: () => this.setVersionTag(item.version, item.tag),
                });
            }

            if (item.status !== STATUS_UNKNOWN && this.webdir) {
                menu.push({
                    TEXT: getMessage('VIEW_FILE'),
                    LINK: '/bitrix/admin/fileman_file_view.php?' + (new URLSearchParams({
                        lang: this.lang,
                        site: this.site,
                        path: this.webdir + '/' + item.version + '.php',
                    })).toString(),
                });
            }

            if (this.otherConfigs.length > 0) {
                menu.push({
                    TEXT: getMessage('TRANSFER_TO'),
                    MENU: this.otherConfigs.map((otherConfig) => ({
                        TEXT: otherConfig.title,
                        ONCLICK: () => this.moveVersion(item.version, otherConfig.name)
                    })),
                });
            }

            menu.push({SEPARATOR: 'Y'});
            menu.push({
                TEXT: getMessage('DELETE'),
                ONCLICK: () => this.deleteVersion(item.version),
            });

            showContextMenu(e.target, menu);
        },

        selectBuilder(name) {

        },
    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row2">
            <div class="sp-col sp-col-scroll sp-white">
              <div class="sp-search_summary">
                <MigrationFilter
                    v-model:value="filter"
                    :class="{'sp-disabled':running }"
                />
                <div
                    class="sp-summary"
                    v-html="summaryLabels"
                />
              </div>
              <div class="sp-migration_list" ref="migration_list">
                <table v-if="versions.length > 0" class="sp-list">
                  <tr v-for="item in versions" :key="item.version">
                    <td class="sp-list_buttons">
                    <span
                        :class="{'adm-btn': true, 'sp-disabled':running}"
                        v-text="'&equiv;'"
                        @click="buildVersionMenu($event, item)"
                    />
                    </td>
                    <td class="sp-list_content">
                      <div v-bind="colorbind(item.status)" v-text="item.version"/>
                      <div v-if="item.file_status" v-text="item.file_status"/>
                      <div v-if="item.record_status" v-text="item.record_status"/>
                      <div v-if="item.labels" v-html="colorize(item.labels)"/>
                      <div v-if="item.description" v-html="colorize(item.description)"/>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="sp-col sp-col-scroll">
              <div class="sp-migration_log" ref="migration_log">
                <div
                    v-for="(item, index) in messages"
                    v-bind="colorbind(item.type)"
                    :key="index"
                    v-text="item.message"
                />
              </div>
            </div>
          </div>
        </div>
        <div class="sp-table">
          <div class="sp-row2">
            <div class="sp-col">
              <input
                  :value="getMessage('UP_START')"
                  type="button"
                  :class="{'adm-btn-green': true,'sp-disabled':running }"
                  @click="upAll"
              />
              <span
                  :class="{'adm-btn': true,'sp-disabled':running }"
                  v-text="'&equiv;'"
                  @click="buildContextMenu($event)"
              />
              <span
                  v-if="loading"
                  class="migration_loading"
                  v-text="getMessage('LOADING_TEXT')"
              />
            </div>
            <div class="sp-col sp-migration_progress">
              <input
                  v-if="running"
                  value="&#10073;&#10073;"
                  type="button"
                  :class="{'adm-btn': true}"
                  @click="running = false"
              />
              <input
                  v-if="replay"
                  value="&#9658;"
                  type="button"
                  :class="{'adm-btn': true}"
                  @click="replayVersion"
              />

              <div
                  v-for="(item, index) in progress"
                  class="migration_loading"
                  :key="index"
                  v-text="progressMessage(item)"
              />
            </div>
          </div>
        </div>
        <div class="sp-table" v-if="builders.length > 0">
          <div class="sp-row2">
            <div class="sp-col sp-col_builders">
              <div v-for="(groupItems, groupName) in builders">
                <div
                    class="sp-builder_group"
                    v-text="groupName"
                />
                <div
                    v-for="item in groupItems"
                    class="sp-builder_title"
                    @click="selectBuilder(item['NAME'])"
                    v-text="item['TITLE']"
                />
              </div>
            </div>
            <div class="sp-col" style="position: relative">
              <div v-if="selectedBuilder" style="position: sticky;top: 10px"/>
            </div>
          </div>
        </div>
      </div>
    `
};
