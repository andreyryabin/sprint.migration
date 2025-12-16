import {ajax} from "main.core";
import {getMessage, getResponseErrors} from "../helpers";
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
            'sprint:migration.controller.main.refresh',
            {data: {payload: this.taskPayload}}
        ).then((response) => {
            this.responseError = getResponseErrors(response);
            return response.data;
        }).then((task) => {
            this.taskTitle = task.title;

            task.values.forEach((item) => {
                this.taskValues[item.id] = item.value;
            });

            task.fields.forEach((item) => {
                this.taskFields[item.id] = item;
                this.toolbar[item.id] = item.title;
            });

            this.value.forEach((item) => this.addValue(item));

            this.isMounted = true;
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
