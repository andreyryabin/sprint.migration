import {filterOptions, getMessage, groupOptions} from "../helpers";
import {CheckboxField} from "./CheckboxField";

export const CheckboxListField = {
    components: {
        CheckboxField
    },
    props: {
        value: {
            type: Array,
            default: () => [],
        },
        options: {
            type: Array,
            default: () => [],
        },
        searchThreshold: {
            type: Number,
            default: 8,
        },
    },
    data() {
        return {
            search: '',
            selected: [],
        };
    },
    watch: {
        value: {
            immediate: true,
            handler(v) {
                this.selected = Array.isArray(v) ? v.slice() : [];
            },
        },
    },
    computed: {
        checklist() {
            return new Set(this.selected);
        },
        filtered() {
            return filterOptions(this.search, this.options)
        },
        grouped() {
            return groupOptions(this.filtered);
        },
    },
    methods: {
        getMessage,
        toggle(id, checked) {
            const idSet = new Set(this.selected);
            if (checked) {
                idSet.add(id);
            } else {
                idSet.delete(id);
            }
            const ids = [...idSet];
            this.selected = ids;
            this.$emit('update:value', ids);
        },
        selectAll() {
            const ids = this.filtered.map(item => item.id);
            this.selected = ids;
            this.$emit('update:value', ids);
        },
    },
    template: `
      <div class="sp-selector">
        <div
            v-if="options.length > searchThreshold"
            class="sp-selector_row"
        >
          <input
              v-model="search"
              size="20"
              type="text"
              placeholder="Search"
          />
        </div>
        <div
            v-for="(groupItems, groupName) in grouped"
            class="sp-selector_row"
        >
          <div
              v-if="groupName !== '-'"
              v-text="groupName"
          />
          <CheckboxField
              v-for="item in groupItems"
              :key="item.id"
              :label="item.name"
              :value="checklist.has(item.id)"
              @update:value="toggle(item.id, $event)"
          />
        </div>
        <div 
            v-if="filtered.length > 0"
            class="sp-selector_row"
        >
          <input
              class="adm-btn"
              v-text="getMessage('SELECT_ALL')"
              @click="selectAll"
          />
        </div>

      </div>
    `
};
