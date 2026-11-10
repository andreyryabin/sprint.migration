import {getMessage} from "../helpers";
import {StringField} from '../fields/StringField';
import {SelectField} from '../fields/SelectField';

export const FilterVersions = {
    components: {
        StringField,
        SelectField,
    },
    props: {
        config: {
            type: String,
            default: ''
        }
    },
    data() {
        return {
            search: '',
            view: 'actual',
        };
    },

    created() {
        this.initFilter();
        this.emitFilter();
    },

    methods: {
        getMessage,

        initFilter() {
            try {
                let obj = JSON.parse(localStorage.getItem('sprint_migration_filter_' + this.config)) ?? {};
                this.search = obj.search ?? ''
                this.view = obj.view ?? 'actual'
            } catch {
            }
        },

        saveFilter() {
            localStorage.setItem('sprint_migration_filter_' + this.config, JSON.stringify({
                search: this.search,
                view: this.view,
            }));
        },

        emitFilter() {
            this.$emit('changeFilter', {
                search: this.search,
                view: this.view,
            })
        },

        changeFilter() {
            this.saveFilter();
            this.emitFilter();
        }
    },
    template: `
      <div>
        <StringField
            v-model:value="search"
            :placeholder="getMessage('SEARCH')"
            @press-enter="changeFilter"
        />
        <SelectField
            v-model:value="view"
            @update:value="changeFilter"
            :options="{
                actual: getMessage('TOGGLE_ACTUAL'),
                all: getMessage('TOGGLE_LIST'),
                new: getMessage('TOGGLE_NEW'),
                installed: getMessage('TOGGLE_INSTALLED'),
                unknown: getMessage('TOGGLE_UNKNOWN'),
                tag: getMessage('TOGGLE_TAG'),
                modified: getMessage('TOGGLE_MODIFIED'),
                older: getMessage('TOGGLE_OLDER'),
            }"
        />
        <input :value="getMessage('SEARCH')" type="button" @click="changeFilter"/>
      </div>
    `
};
