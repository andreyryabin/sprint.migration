import {getMessage} from "../helpers";

export const MigrationFilter = {
    props: {
        value: {
            type: Object,
            default: () => ({})
        },

    },
    data() {
        return {
            search: this.value.search || '',
            view: this.value.view || 'actual',
        };
    },

    methods: {
        getMessage,

        emitValue() {
            this.$emit('update:value', {
                search: this.search,
                view: this.view,
            })
        }
    },
    template: `
      <div class="sp-search">
        <input
            v-model="search"
            :placeholder="getMessage('SEARCH')"
            type="text"
            class="adm-input"
            @keydown.enter.prevent="emitValue"
        />
        <select v-model="view" @change="emitValue">
          <option value="actual" v-text="getMessage('TOGGLE_ACTUAL')"/>
          <option value="all" v-text="getMessage('TOGGLE_LIST')"/>
          <option value="new" v-text="getMessage('TOGGLE_NEW')"/>
          <option value="installed" v-text="getMessage('TOGGLE_INSTALLED')"/>
          <option value="unknown" v-text="getMessage('TOGGLE_UNKNOWN')"/>
          <option value="tag" v-text="getMessage('TOGGLE_TAG')"/>
          <option value="modified" v-text="getMessage('TOGGLE_MODIFIED')"/>
          <option value="older" v-text="getMessage('TOGGLE_OLDER')"/>
        </select>
        <input :value="getMessage('SEARCH')" type="button" @click="emitValue"/>
      </div>
    `
};
