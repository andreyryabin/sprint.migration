export const SelectField = {
    props: {
        value: {
            type: String,
            default: '',
        },
        options: {
            type: Object,
            default: () => ({})
        },
        size: {
            type: Number,
            default: 1,
        },
        css: {
            type: String,
            default: 'SelectField',
        },
    },
    data() {
        return {
            selected: '',
            items: [],
            isMounted: false
        };
    },

    created() {
        let ids = [];
        for (const [id, name] of Object.entries(this.options)) {
            this.items.push({id, name});
            ids.push(id);
        }

        if (ids.length === 0) {
            return;
        }

        if (ids.includes(this.value)) {
            this.selected = this.value;
        } else {
            this.selected = ids[0];
            this.emitValue();
        }

        this.isMounted = true;
    },
    methods: {
        emitValue() {
            this.$emit('update:value', this.selected);
        },
    },
    template: `
      <div v-if="isMounted" :class="css">
        <select
            v-model="selected"
            :size="size"
            @change="emitValue"
        >
          <option
              v-for="(item, index) in items"
              :key="'key'+index"
              :value="item.id"
              v-text="item.name"
          />
        </select>
      </div>
    `
};

