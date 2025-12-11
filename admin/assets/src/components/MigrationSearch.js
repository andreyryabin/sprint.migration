import {getMessage} from "../helpers";

export const MigrationSearch = {
    props: {},
    data() {
        return {
            search: '',
            view: 'actual'

        };
    },
    created() {
    },
    mounted() {
    },
    watch: {},
    methods: {
        getMessage,
    },
    template: `
      <div class="sp-search">
        <input
            v-model="search"
            :placeholder="getMessage('SEARCH')"
            type="text"
            class="adm-input"
        />
        <select
            v-model="view"
        >
          <option value="actual" v-text="getMessage('TOGGLE_ACTUAL')"/>
          <option value="all" v-text="getMessage('TOGGLE_LIST')"/>
          <option value="new" v-text="getMessage('TOGGLE_NEW')"/>
          <option value="installed" v-text="getMessage('TOGGLE_INSTALLED')"/>
          <option value="unknown" v-text="getMessage('TOGGLE_UNKNOWN')"/>
          <option value="tag" v-text="getMessage('TOGGLE_TAG')"/>
          <option value="modified" v-text="getMessage('TOGGLE_MODIFIED')"/>
          <option value="older" v-text="getMessage('TOGGLE_OLDER')"/>
        </select>
        <input
            :value="getMessage('SEARCH')"
            type="button"
            @click="$emit('change', {search, view})"
        />
      </div>
    `
};
