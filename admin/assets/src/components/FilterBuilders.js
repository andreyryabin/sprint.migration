import {getMessage} from "../helpers";
import {StringField} from '../fields/StringField';
import {SelectField} from '../fields/SelectField';

export const FilterBuilders = {
    components: {
        StringField,
        SelectField,
    },
    props: {},
    data() {
        return {
            search: '',
        };
    },

    methods: {
        getMessage,

        changeFilter() {
            this.$emit('changeFilter', {
                search: this.search,
            })
        }
    },
    template: `
      <div>
        <StringField
            v-model:value="search"
            :placeholder="getMessage('SEARCH')"
            @press-enter="changeFilter"
            @update:value="changeFilter"
        />
        <input :value="getMessage('SEARCH')" type="button" @click="changeFilter"/>
      </div>
    `
};
