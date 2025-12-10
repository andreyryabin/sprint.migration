import {MigrationApp} from './components/MigrationApp';
import {BitrixVue} from "ui.vue3";
import "./index.css";

BX.ready(() => {
    let $app = BX('sprint_migration_app');

    BitrixVue.createApp(MigrationApp, $app.dataset).mount($app);
});
