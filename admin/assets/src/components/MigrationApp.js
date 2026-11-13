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
    methods: {
        getMessage,

        selectTab(tabName) {
            if (tabName === 'versions') {
                this.$nextTick(() => {
                    this.$refs.versions.refreshVersions();
                });

            }
            if (tabName === 'builders') {
                this.$nextTick(() => {
                    this.$refs.builders.refreshBuilders();
                });
            }
        }
    },
    template: `
      <MigrationTabs
          :items="{versions:getMessage('VERSIONS'), builders:getMessage('BUILDERS')}"
          @select-tab="selectTab"
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
              :config="config"
          />
        </template>
      </MigrationTabs>
    `
}