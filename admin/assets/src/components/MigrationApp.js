import {getMessage} from "../helpers";

import {MigrationTabs} from './MigrationTabs';
import {MigrationVersions} from './MigrationVersions';
import {MigrationBuilders} from './MigrationBuilders';

export const MigrationApp = {
    components: {
        MigrationTabs,
        MigrationVersions,
        MigrationBuilders,
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
            activeTab: 'versions'
        }
    },
    methods: {
        getMessage,

        selectTab(tabName) {
            if (tabName === 'versions') {
                this.$nextTick(() => {
                    this.$refs.versions.refreshVersions();
                });
            }
        }
    },
    template: `
      <MigrationTabs
          :items="{versions:getMessage('TAB_VERSIONS'), builders:getMessage('TAB_BUILDERS')}"
          v-model:selected="activeTab"
          @update:selected="selectTab"
      >
        <template v-slot:versions>
          <MigrationVersions
              ref="versions"
              :config="config"
              :otherConfigs="otherConfigs"
              :webdir="webdir"
              :lang="lang"
              :site="site"
          />
        </template>
        <template v-slot:builders>
          <MigrationBuilders
              ref="builders"
              :lang="lang"
              :config="config"
          />
        </template>
      </MigrationTabs>
    `
}