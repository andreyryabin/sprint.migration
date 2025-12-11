import {ajax} from "main.core";
import {getMessage} from "../helpers";
import {MigrationSearch} from "./MigrationSearch"

export const MigrationApp = {
    components: {
        MigrationSearch
    },

    props: {
        config: {
            type: String,
            default: ''
        },
    },
    data() {
        return {
            xxx: 123

        };
    },
    created() {

        ajax.runAction(
            '/123',
            {
                data: {}
            }
        ).then((response) => {

        }).catch((response) => {

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
              <MigrationList/>
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
