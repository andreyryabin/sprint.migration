import {ajax} from "main.core";
import {getMessage, getResponseErrors} from "../helpers";
import {MigrationSearch} from "./MigrationSearch"
import {MigrationList} from "./MigrationList"
import {MigrationLog} from "./MigrationLog"

export const MigrationApp = {
    components: {
        MigrationSearch,
        MigrationList,
        MigrationLog,
    },

    props: {
        config: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            versions: [],
        };
    },
    created() {

        ajax.runAction(
            'sprint:migration.controller.main.refresh',
            {data: {config, filter: []}}
        ).then((response) => {
            this.responseError = getResponseErrors(response);
            return response.data;
        }).then((data) => {
            this.versions = data.versions || [];
        }).catch((response) => {
            this.responseError = getResponseErrors(response);
        });

    },
    mounted() {
    },
    watch: {},
    methods: {
        getMessage,
    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row2">
            <div class="sp-col sp-col-scroll sp-white">
              <MigrationSearch
                  @change=""
              />
              <MigrationList
                  :config="config"
                  :items="versions"
              />
            </div>
            <div class="sp-col sp-col-scroll">
              <MigrationLog/>
            </div>
          </div>
        </div>
        <div class="sp-table">
          <div class="sp-row2">
            <div class="sp-col">
              <input type="button"
                     :value="getMessage('UP_START')"
                     class="adm-btn-green"/>
              <span
                  class="migration_loading"
                  v-text="getMessage('LOADING_TEXT')"
              />
            </div>
            <div class="sp-col">
              <div id="migration_progress"></div>
              <div id="migration_actions"></div>
            </div>
          </div>
        </div>
        <div class="sp-separator"></div>
      </div>
    `
};
