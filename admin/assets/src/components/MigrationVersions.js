import {ajax} from "main.core";
import {colorclass, colorize, getMessage, extractResponse, progressMessage, showContextMenu} from "../helpers";

import {StringField} from '../fields/StringField';
import {SelectField} from '../fields/SelectField';

const STATUS_INSTALLED = 'installed';
const STATUS_NEW = 'new';
const STATUS_UNKNOWN = 'unknown';

const ACTION_UP = 'up';
const ACTION_DOWN = 'down';

export const MigrationVersions = {
    components: {
        StringField,
        SelectField
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
            loading: 0,
            running: false,
            replay: false,
            versions: [],
            messages: [],
            progress: [],
            filter: {search: '', view: 'actual'},
            variants: {
                actual: getMessage('TOGGLE_ACTUAL'),
                all: getMessage('TOGGLE_LIST'),
                new: getMessage('TOGGLE_NEW'),
                installed: getMessage('TOGGLE_INSTALLED'),
                unknown: getMessage('TOGGLE_UNKNOWN'),
                tag: getMessage('TOGGLE_TAG'),
                modified: getMessage('TOGGLE_MODIFIED'),
                older: getMessage('TOGGLE_OLDER'),
            }
        };
    },

    created() {
        this.createFilter();

        // this.refreshVersions();
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
        messagesCount() {
            return this.messages.length;
        },
    },

    watch: {
        summaryCount: {
            handler() {
                this.$nextTick(() => {
                    const div = this.$refs.migration_list;
                    if (div) div.scrollTop = div.scrollHeight;
                });
            }
        },
        messagesCount: {
            handler() {
                this.$nextTick(() => {
                    const div = this.$refs.migration_log;
                    if (div) div.scrollTop = div.scrollHeight;
                });
            }
        },

    },
    methods: {
        getMessage,
        progressMessage,
        colorize,
        colorclass,

        createFilter() {
            try {
                let obj = JSON.parse(
                    localStorage.getItem('sprint_migration_filter_' + this.config)
                ) ?? {};
                this.filter.search = obj.search ?? '';
                this.filter.view = obj.view ?? 'actual';
            } catch {

            }
        },

        saveFilter() {
            localStorage.setItem('sprint_migration_filter_' + this.config, JSON.stringify(this.filter));

            this.refreshVersions();
        },

        execAction(action, payload) {
            this.loading = 1;
            return ajax.runAction(
                'sprint:migration.controller.versioncontroller.' + action,
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

        refreshVersions() {
            this.execAction('refreshVersions', {filter: this.filter})
                .then((data) => {
                    this.versions = data.versions || [];
                });
        },

        async execVersion(version, action, params, settag) {
            this.running = true;
            this.replay = false;

            try {
                let restartParams = params;
                while (true) {
                    if (!this.running) {
                        this.replay = {
                            version,
                            action,
                            params: restartParams,
                            settag,
                            button: 'CONTINUE'
                        }
                        return false;
                    }

                    const data = await this.execAction('execVersion', {
                        version,
                        action,
                        params: restartParams,
                        settag
                    });

                    if (data.restart) {
                        restartParams = data.params;
                        continue;
                    }

                    if (!data.success) {
                        this.replay = {
                            version,
                            action,
                            params: data.params,
                            settag,
                            button: 'RESTART'
                        }
                        return false;
                    }

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
            return true;
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

        getVersionsByStatus(status) {
            return this.versions
                .filter((item) => item.status === status)
                .map((item) => item.version)
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
            this.execVersion(version, ACTION_UP, {}, '')
                .then(() => {
                });
        },

        downVersion(version) {
            this.execVersion(version, ACTION_DOWN, {}, '')
                .then(() => {
                });
        },

        replayVersion() {
            if (this.running) {
                this.running = false;
                return;
            }

            if (this.replay && this.replay.version) {
                this.execVersion(
                    this.replay.version,
                    this.replay.action,
                    this.replay.params,
                    this.replay.settag
                ).then(() => {
                });
            }
        },

        upAll() {
            if (confirm('Confirm install migrations')) {
                this.execAll(ACTION_UP, '')
                    .then(() => {
                        this.refreshVersions();
                    });
            }
        },

        downAll() {
            if (confirm('Confirm rollback migrations')) {
                this.execAll(ACTION_DOWN, '')
                    .then(() => {
                        this.refreshVersions();
                    });
            }
        },

        upAllWithTag() {
            let settag = prompt('Set migrations tag');
            if (settag) {
                this.execAll(ACTION_UP, settag)
                    .then(() => {
                        this.refreshVersions();
                    });
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

        setAllTag() {
            let installed = this.getVersionsByStatus(STATUS_INSTALLED);
            if (installed.length <= 0) {
                return;
            }

            let settag = prompt('Set migrations tag', '');
            if (settag) {
                this.execAction('setAllTag', {
                    versions: installed,
                    settag: settag
                }).then(() => {
                    this.refreshVersions();
                });
            }
        },

        moveVersion(version, toConfig) {
            this.execAction('moveVersion', {
                version: version,
                toConfig: toConfig,
            }).then(() => {
                this.refreshVersions();
            });

        },
        deleteVersion(version) {
            if (confirm('Confirm delete migration file')) {
                this.execAction('deleteVersion', {
                    version: version,
                }).then(() => {
                    this.refreshVersions();
                });
            }
        },

        makeContextMenu(e) {
            let menu = [];

            menu.push({
                TEXT: getMessage('UP_START_WITH_TAG'),
                ONCLICK: () => this.upAllWithTag(),
            });
            menu.push({
                TEXT: getMessage('DOWN_START'),
                ONCLICK: () => this.downAll(),
            });

            if (this.summaryCount.installed > 0) {
                menu.push({
                    TEXT: getMessage('SET_TAG_ALL'),
                    ONCLICK: () => this.setAllTag(),
                });
            }

            showContextMenu(e.target, menu);
        },

        makeVersionMenu(e, item) {
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
                    TEXT: getMessage('SET_TAG'),
                    ONCLICK: () => this.setVersionTag(item.version, item.tag),
                });
                menu.push({
                    TEXT: getMessage('MARK_AS_NEW'),
                    ONCLICK: () => this.markVersion(item.version, STATUS_NEW),
                });
            }

            if (item.status === STATUS_UNKNOWN) {
                menu.push({
                    TEXT: getMessage('SET_TAG'),
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
                    TEXT: getMessage('MOVE_TO'),
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

    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row">
            <div class="sp-col">
              <div class="sp-search">
                <div :class="{'sp-search_filter':true,'sp-disabled':running }">
                  <StringField
                      v-model:value="filter.search"
                      :placeholder="getMessage('SEARCH')"
                      @press-enter="saveFilter"
                  />
                  <SelectField
                      v-model:value="filter.view"
                      :options="variants"
                      @update:value="saveFilter"
                  />
                  <input
                      :value="getMessage('SEARCH')"
                      class="adm-btn"
                      type="button"
                      @click="saveFilter"
                  />
                </div>
                <div class="sp-search_summary">
                  {{ getMessage('VERSIONS_NEW') }}:
                  <span class="sp-label sp-label-red" v-text="summaryCount.new"/>
                  {{ getMessage('VERSIONS_INSTALLED') }}:
                  <span class="sp-label sp-label-green" v-text="summaryCount.installed"/>
                </div>
              </div>
            </div>
          </div>
          <div class="sp-row2">
            <div class="sp-col sp-col-p0 sp-white">
              <div class="sp-migration_list" ref="migration_list">
                <table v-if="versions.length > 0" class="sp-list">
                  <tr v-for="item in versions" :key="item.version">
                    <td class="sp-list_buttons">
                    <span
                        :class="{'adm-btn': true, 'sp-disabled':running}"
                        v-text="'&equiv;'"
                        @click="makeVersionMenu($event, item)"
                    />
                    </td>
                    <td class="sp-list_content">
                      <div v-bind="colorclass(item.status)" v-text="item.version"/>
                      <div v-if="item.file_status" v-text="item.file_status"/>
                      <div v-if="item.record_status" v-text="item.record_status"/>
                      <div v-if="item.tag_status" v-html="colorize(item.tag_status)"/>
                      <div v-if="item.labels" v-html="colorize(item.labels)"/>
                      <div v-if="item.description" v-html="colorize(item.description)"/>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
            <div class="sp-col sp-col-p0">
              <div class="sp-migration_log" ref="migration_log">
                <div
                    v-for="(item, index) in messages"
                    v-bind="colorclass(item.type)"
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
                  @click="makeContextMenu($event)"
              />
              <span
                  v-if="loading"
                  class="sp-loading"
                  v-text="getMessage('LOADING_TEXT')"
              />
            </div>
            <div class="sp-col sp-migration_progress">
              <input
                  :value="getMessage(replay ? replay.button: 'PAUSE')"
                  type="button"
                  :class="['adm-btn', 'sp-play', (!replay && !running ? 'sp-disabled':'')]"
                  @click="replayVersion"
              />

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
    `
};
