import {ajax} from "main.core";

import {colorize, getMessage, showContextMenu, VersionAction, VersionStatus} from "../helpers";

import {MigrationFilter} from "./MigrationFilter"
import {MigrationLog} from "./MigrationLog"

export const MigrationApp = {
    components: {
        MigrationFilter,
        MigrationLog,
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
            builders: [],
            messages: [],
            filter: {},
            selectedBuilder: '',
            loading: 0,
            running: false,
            restart: false,
        };
    },
    created() {
        this.filter = JSON.parse(
            localStorage.getItem('sprint_migration_filter_' + this.config) || '{"view":"actual"}'
        );
    },
    watch: {
        filter(newValue, oldValue) {
            if (newValue !== oldValue) {
                localStorage.setItem('sprint_migration_filter_' + this.config, JSON.stringify(newValue));
                this.refresh();
            }
        }
    },
    methods: {
        getMessage,
        colorize,

        processResponse(resp) {
            if (resp.data && resp.data.messages) {
                resp.data.messages.forEach(message => this.messages.push(message));
            }

            if (resp && resp.errors) {
                resp.errors.forEach(item => this.messages.push('[error]' + item.message + '[/]'));
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

                    this.$nextTick(() => {
                        const log = this.$refs.migration_list;
                        if (log) log.scrollTop = log.scrollHeight;
                    });
                });
        },

        async execVersion(version, action, params, settag) {
            this.running = true;
            this.restart = false;
            try {
                this.execLog(version, action, 'start');

                let currentParams = params;
                while (true) {
                    if (!this.running) {
                        this.execLog(version, action, 'canceled');
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
                        this.restart = {
                            version,
                            action,
                            params: currentParams,
                            settag
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

            this.refresh();
            return true;
        },

        execLog(version, action, msg) {
            this.messages.push('[info]' + version + '::' + action + '() ' + msg + '[/]');
        },

        getVersionsByAction(action) {
            if (action === VersionAction.ACTION_DOWN) {
                return this.versions
                    .filter((item) => item.status === VersionStatus.STATUS_INSTALLED)
                    .map((item) => item.version)
                    .reverse();
            }
            return this.versions
                .filter(item => item.status === VersionStatus.STATUS_NEW)
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
            this.execVersion(version, VersionAction.ACTION_UP, {}, '');
        },

        downVersion(version, params) {
            this.execVersion(version, VersionAction.ACTION_DOWN, {}, '');
        },

        restartVersion() {
            if (this.restart && this.restart.version) {
                this.execVersion(
                    this.restart.version,
                    this.restart.action,
                    this.restart.params,
                    this.restart.settag
                );
            }
        },

        upAll() {
            if (confirm('Confirm install migrations')) {
                this.execAll(VersionAction.ACTION_UP, '')
            }
        },

        downAll() {
            if (confirm('Confirm rollback migrations')) {
                this.execAll(VersionAction.ACTION_DOWN, '')
            }
        },

        upAllWithTag() {
            let settag = prompt('Set migrations tag');
            if (settag) {
                this.execAll(VersionAction.ACTION_UP, settag)
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

        setTagVersion(version, defaultTag) {
            let settag = prompt('Set migration tag', defaultTag);
            if (settag) {
                this.execAction('setTagVersion', {
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

            if (item.status === VersionStatus.STATUS_NEW) {
                menu.push({
                    TEXT: getMessage('VERSION_UP'),
                    ONCLICK: () => this.upVersion(item.version),
                });
                menu.push({
                    TEXT: getMessage('MARK_AS_INSTALLED'),
                    ONCLICK: () => this.markVersion(item.version, VersionStatus.STATUS_INSTALLED),
                });
            }

            if (item.status === VersionStatus.STATUS_INSTALLED) {
                menu.push({
                    TEXT: getMessage('VERSION_DOWN'),
                    ONCLICK: () => this.downVersion(item.version),
                });
                menu.push({
                    TEXT: getMessage('SETTAG'),
                    ONCLICK: () => this.setTagVersion(item.version, item.tag),
                });
                menu.push({
                    TEXT: getMessage('MARK_AS_NEW'),
                    ONCLICK: () => this.markVersion(item.version, VersionStatus.STATUS_NEW),
                });
            }

            if (item.status === VersionStatus.STATUS_UNKNOWN) {
                menu.push({
                    TEXT: getMessage('SETTAG'),
                    ONCLICK: () => this.setTagVersion(item.version, item.tag),
                });
            }

            if (item.status !== VersionStatus.STATUS_UNKNOWN && this.webdir) {
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
              <MigrationFilter
                  v-model:value="filter"
                  :class="{'sp-disabled':running }"
              />
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
                      <div v-html="colorize('['+item.status+']'+ item.version +'[/]')"/>
                      <div v-if="item.file_status" v-text="item.file_status"/>
                      <div v-if="item.record_status" v-text="item.record_status"/>
                      <div v-if="item.labels" v-html="colorize(item.labels)"/>
                      <div v-if="item.description" v-html="colorize(item.description)"/>
                    </td>
                  </tr>
                </table>
                <div v-if="versions.length === 0" v-text="getMessage('LIST_EMPTY')"/>
              </div>
            </div>
            <div class="sp-col sp-col-scroll">
              <MigrationLog :messages="messages"/>
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
            <div class="sp-col">
              <div class="sp-migration_progress"></div>
              <div class="sp-migration_buttons">
                <input
                    v-if="running"
                    :value="getMessage('VERSION_STOP')"
                    type="button"
                    :class="{'adm-btn': true}"
                    @click="running = false"
                />
                <input
                    v-if="restart"
                    :value="getMessage('VERSION_RESTART')"
                    type="button"
                    :class="{'adm-btn': true}"
                    @click="restartVersion"
                />
              </div>
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
