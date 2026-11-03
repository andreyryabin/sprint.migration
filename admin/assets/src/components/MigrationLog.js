import {colorize} from "../helpers";

export const MigrationLog = {
    props: {
        messages: {
            type: Array,
            default: () => ([])
        },
    },
    computed: {
        messagesCount() {
            return this.messages.length;
        }
    },

    watch: {
        messagesCount() {
            this.$nextTick(() => {
                const log = this.$refs.log;
                if (log) log.scrollTop = log.scrollHeight;
            });
        }
    },

    methods: {
        colorize,
    },

    template: `
      <div class="sp-migration_log" ref="log">
        <div
            v-for="(message, index) in messages"
            :key="index"
            v-html="colorize(message)"
        />
      </div>
    `
};
