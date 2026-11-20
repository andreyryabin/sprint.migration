import {optionsNames} from "../helpers";

export const SelectField = {
    props: {
        value: {
            type: [String, Number],
            default: '',
        },
        options: {
            type: Object,
            default: () => ({}),
        },
        size: {
            type: Number,
            default: 1,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },

    computed: {
        items() {
            return Object.entries(this.options).map(([id, name]) => ({id, name}));
        },

        ids() {
            return this.items.map(item => item.id);
        },

        normalizedValue() {
            if (this.ids.length === 0) {
                return '';
            }
            return this.ids.includes(this.value) ? this.value : this.ids[0];
        },
    },

    watch: {
        value: {
            immediate: true,
            handler() {
                this.syncValue();
            },
        },
        normalizedValue: {
            handler() {
                this.syncValue();
            },
        },
    },

    methods: {
        syncValue() {
            const next = this.normalizedValue;
            if (next !== this.value) {
                this.$emit('update:value', next);
                this.$emit('update:description', optionsNames(this.options, [next]));
            }
        },

        onChange(event) {
            let val = event.target.value;
            this.$emit('update:value', val);
            this.$emit('update:description', optionsNames(this.options, [val]));
        },
    },

    template: `
      <div class="SelectField">
        <select
            :value="normalizedValue"
            :size="size"
            :disabled="disabled"
            @change="onChange"
        >
          <option
              v-for="item in items"
              :key="item.id"
              :value="item.id"
              v-text="item.name"
          />
        </select>
      </div>
    `,
};