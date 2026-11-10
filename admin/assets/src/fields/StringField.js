import {makeString} from "../helpers";
export const StringField = {
    components: {
    },
    emits: ['blur','focus'],
    props: {
        value: {
            type: String,
            default: '',
        },
        css: {
            type: String,
            default: 'StringField',
        },
        placeholder: {
            type: String,
            default: '',
        },
    },
    computed: {
        collected: {
            get() {
                return makeString(this.value);
            },
            set(val) {
                this.$emit('update:value', val);
            }
        }
    },
    methods: {
        handleFocus() {
            /* используется извне через $refs */
            this.$refs.input.focus();
        },
    },

    template: `
      <div :class="css">
        <input
            v-model="collected"
            ref="input"
            type="text"
            :placeholder="placeholder"
            :title="placeholder"
            @blur="$emit('blur')"
            @focus="$emit('focus')"
            @keydown.enter.prevent="$emit('press-enter')"
            @keydown.esc.prevent="$emit('press-cancel')"
        >
      </div>
    `
};
