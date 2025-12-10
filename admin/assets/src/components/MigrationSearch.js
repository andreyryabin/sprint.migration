import {Loc} from "main.core";
export const MigrationSearch = {
    props: {
    },
    data() {
        return {
        };
    },
    created() {
    },
    mounted() {
    },
    watch: {},
    methods: {
        mess(text) {
            return Loc.hasMessage(text) ? Loc.getMessage(text) : text;
        },
    },
    template: `
      <div class="sp-search">
        <input id="migration_search" :placeholder="mess('SEARCH')" type="text" value="" class="adm-input"/>
        <select id="migration_view">
          <option value="migration_view_actual" v-text="mess('TOGGLE_ACTUAL')"/>
          <option value="migration_view_all" v-text="mess('TOGGLE_LIST')"/>
          <option value="migration_view_new" v-text="mess('TOGGLE_NEW')"/>
          <option value="migration_view_installed" v-text="mess('TOGGLE_INSTALLED')"/>
          <option value="migration_view_unknown" v-text="mess('TOGGLE_UNKNOWN')"/>
          <option value="migration_view_tag" v-text="mess('TOGGLE_TAG')"/>
          <option value="migration_view_modified" v-text="mess('TOGGLE_MODIFIED')"/>
          <option value="migration_view_older" v-text="mess('TOGGLE_OLDER')"/>
          <option value="migration_view_status" v-text=" mess('TOGGLE_STATUS')"/>
        </select>
        <input id="migration_refresh" type="button" :value="mess('SEARCH')"/>
      </div>
    `
};
