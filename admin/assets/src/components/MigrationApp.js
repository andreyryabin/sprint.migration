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
        getMessage
    },
    template: `
      <MigrationTabs :items="{versions:getMessage('VERSIONS'), builders:getMessage('BUILDERS')}">
        <template v-slot:versions>
          <MigrationVersions
              :config="config"
              :otherConfigs="otherConfigs"
              :webdir="webdir"
              :lang="lang"
              :site="site"
          />
        </template>
        <template v-slot:builders>
          <MigrationBuilders
              :config="config"
          />
        </template>
      </MigrationTabs>
    `
}