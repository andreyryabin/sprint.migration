export const MigrationTabs = {
    props: {
        selected: {
            type: String,
            default: null
        },
        items: {
            type: Object,
            default: () => ({})
        },
    },
    data() {
        return {
            innerSelected: null,
        };
    },
    computed: {
        firstTab() {
            return Object.keys(this.items)[0] || '';
        },
        currentSelected() {
            const candidates = [this.selected, this.innerSelected];
            for (const c of candidates) {
                if (c && this.items[c]) return c;
            }
            return this.firstTab;
        },
    },
    methods: {
        selectTab(name) {
            if (name === this.currentSelected) return;
            this.innerSelected = name;
            this.$emit('update:selected', name);
        },
        getTabCss(name) {
            return ['sp-tabs_tab', this.currentSelected === name && 'sp-tabs_tab_selected'];
        }
    },
    template: `
      <div class="sp-tabs">
        <div class="sp-tabs_tabs" role="tablist">
          <button
              v-for="(title, name) in items"
              :key="'tab-'+name"
              :class="getTabCss(name)"
              @click="selectTab(name)"
              v-text="title"
              type="button"
              role="tab"
              :tabindex="currentSelected === name ? 0 : -1"
          />
        </div>
        <div class="sp-tabs_slots">
          <div
              v-for="(title, name) in items"
              :key="'slot-'+name"
              class="sp-tabs_slot"
              role="tabpanel"
              v-show="selected === name"
          >
            <slot :name="name"/>
          </div>
        </div>
      </div>
    `
};

