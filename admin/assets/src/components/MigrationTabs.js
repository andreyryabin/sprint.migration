export const MigrationTabs = {
    props: {
        items: {
            type: Object,
            default: () => ({})
        },
    },
    data() {
        return {
            selected: '',
        };
    },
    created() {
        this.selectTab(
            Object.keys(this.items)[0] || ''
        )
    },

    methods: {
        selectTab(name) {
            this.selected = name;
            this.$emit('select-tab', name)
        },
        getTabCss(name) {
            return ['sp-tabs_tab', (this.selected === name ? 'sp-tabs_tab_selected' : '')]
        }
    },
    template: `
      <div class="sp-tabs">
        <div class="sp-tabs_tabs">
          <div
              v-for="(title, name) in items"
              :key="'tab-'+name"
              :class="getTabCss(name)"
              @click="selectTab(name)"
              v-text="title"
          />
        </div>
        <div class="sp-tabs_slots">
          <div
              v-for="(title, name) in items"
              :key="'slot-'+name"
              class="sp-tabs_slot"
              v-show="selected === name"
          >
            <slot :name="name"/>
          </div>
        </div>
      </div>
    `
};

