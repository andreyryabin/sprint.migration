import {ajax, Loc} from "main.core";
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
        mess(text) {
            return Loc.hasMessage(text) ? Loc.getMessage(text) : text;
        },

        showForm() {
        },

        deleteRow(index) {
        }
    },
    template: `
      <div>
        <div class="sp-table">
          <div class="sp-row2">
            <div class="sp-col sp-col-scroll sp-white">
              <MigrationSearch/>
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
                     :value="mess('UP_START')"
                     class="adm-btn-green"/>
              <span id="migration_loading"
                    v-text="mess('LOADING_TEXT')"
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
