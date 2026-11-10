import {BitrixVue} from "ui.vue3";

import {MigrationApp} from './components/MigrationApp';
import "./index.css";

BX.ready(() => {
    let $app = BX('sprint_migration_app');

    let props = JSON.parse($app.dataset.props || '{}');

    BitrixVue.createApp(MigrationApp, props).mount($app);
});
