import {optionsFilter, getMessage, optionsGroup, optionsNames} from "../helpers";

export const CheckboxListField = {
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
                this.$emit('update:description', optionsNames(this.options, this.selected));
            },
        },
    },
    computed: {
        checklist() {
            return new Set(this.selected);
        },
        filtered() {
            return optionsFilter(this.search, this.options)
        },
        grouped() {
            return optionsGroup(this.filtered);
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
            this.emitChecked(ids);
        },
        selectAll() {
            if (this.selected.length > 0) {
                this.selected = [];
            } else {
                this.selected = this.filtered.map(item => item.id);
            }
            this.emitChecked(this.selected.slice());
        },
        emitChecked(ids){
            this.$emit('update:value', ids);
            this.$emit('update:description', optionsNames(this.options, ids));
        }
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
            :key="groupName"
            class="sp-selector_row"
        >
          <div
              v-if="groupName !== '-'"
              v-text="groupName"
          />
          <label
              v-for="item in groupItems"
              :key="item.id"
          >
            <input
                type="checkbox"
                :checked="checklist.has(item.id)"
                @change="toggle(item.id, $event.target.checked)"
            />
            <span
                v-text="item.name"
            />
          </label>
        </div>
        <div
            v-if="filtered.length > 0"
            class="sp-selector_row"
        >
          <input
              class="adm-btn"
              type="button"
              :value="getMessage('SELECT_ALL')"
              @click="selectAll"
          />
        </div>

      </div>
    `
};
