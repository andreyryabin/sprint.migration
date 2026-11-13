import {cleanAttrs, makeString} from "../helpers";

export const StringField = {
    props: {
        value: {
            type: String,
            default: '',
        },
        placeholder: {
            type: String,
            default: '',
        },
        size: {
            type: Number,
            default: 0,
        },
    },
    computed: {
        collected: {
            get() {
                return this.value;
            },
            set(val) {
                this.$emit('update:value', val);
            }
        },
        cleanedAttrs() {
            return cleanAttrs({
                placeholder: this.placeholder,
                size: this.size,
            });
        }
    },
    template: `
      <div class="StringField">
        <input
            v-model="collected"
            v-bind="cleanedAttrs"
            ref="input"
            type="text"
            @blur="$emit('blur')"
            @focus="$emit('focus')"
            @keydown.enter.prevent="$emit('press-enter')"
            @keydown.esc.prevent="$emit('press-cancel')"
        >
      </div>
    `
};
