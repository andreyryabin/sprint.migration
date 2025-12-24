import {getMessage} from "../helpers";

export const MigrationList = {
    props: {
        config: {
            type: String,
            default: ''
        },
        items: {
            type: Array,
            default: () => ([]),
        },
    },
    data() {
        return {
            search: '',
            view: 'actual'

        };
    },
    created() {
    },
    mounted() {
    },
    watch: {},
    methods: {
        getMessage,
    },
    template: `
      <table class="sp-list">
        <tr
            v-for="item in items"
            :key="item.version"
        >
          <td class="sp-list-td__buttons">
            <a href="javascript:void(0)"
               class="adm-btn"
               hidefocus="true">&equiv;</a>
          </td>
          <td class="sp-list-td__content">
            <div
                :class="item.status"
                v-text="item.version"
            />
            <div
                v-if="item.fileStatus"
                v-text="item.fileStatus"
            />
            <div
                v-if="item.recordStatus"
                v-text="item.recordStatus"
            />
            <div
                v-if="item.releaseTag"
                v-text="item.releaseTag"
            />
            <div
                v-text="item.labels"
            />
            <div
                v-text="item.description"
            />
          </td>
        </tr>
      </table>
    `
};
