import {optionsFilter, optionsGroup, optionsNames} from "../helpers";

let uid = 0;
export const RadioListField = {
    props: {
        value: {
            type: [String, Number],
            default: '',
        },
        options: {
            type: Array,
            default: () => ([]),
        },
        searchThreshold: {
            type: Number,
            default: 8,
        },
    },
    data() {
        return {
            search: '',
            name: `RadioListField${++uid}`,
        };
    },
    watch: {
        value: {
            immediate: true,
            handler(id) {
                this.$emit('update:description', optionsNames(this.options, [id]));
            },
        },
    },
    computed: {
        selected: {
            get() {
                return this.value;
            },
            set(id) {
                this.$emit('update:value', id);
            },
        },
        filtered() {
            return optionsFilter(this.search, this.options);
        },
        grouped() {
            return optionsGroup(this.filtered);
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
                v-model="selected"
                :name="name"
                :value="item.id"
                type="radio"
            />
            {{ item.name }}
          </label>
        </div>
      </div>
    `
};
