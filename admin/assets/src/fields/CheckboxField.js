export const CheckboxField = {
    props: {
        value: {
            type: Boolean,
            default: false,
        },
        label: {
            type: String,
            default: '',
        },
    },
    template: `
      <label>
        <input
            type="checkbox"
            :checked="value"
            @change="$emit('update:value', $event.target.checked)"
        />
        <span
            v-if="label"
            v-text="label"
        />
      </label>
    `,
};