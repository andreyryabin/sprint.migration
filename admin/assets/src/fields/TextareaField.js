import {cleanAttrs, makeString} from "../helpers";

export const TextareaField = {
    inheritAttrs: false,
    components: {},
    props: {
        value: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        rows: {
            type: Number,
            default: 5,
        },
        cols: {
            type: Number,
            default: 40,
        },
    },

    computed: {
        collected: {
            get() {
                return this.value;
            },
            set(v) {
                this.$emit('update:value', v);
            },
        },
        cleanedAttrs() {
            return cleanAttrs({
                placeholder: this.placeholder,
                rows: this.rows,
                cols: this.cols,
            });
        }
    },
    template: `
      <div class="TextareaField">
        <textarea v-model="collected" v-bind="cleanedAttrs"/>
      </div>
    `
};
