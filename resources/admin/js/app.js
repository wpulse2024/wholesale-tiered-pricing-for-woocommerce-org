import { createApp } from 'vue';
import App from './App.vue';
import ElementPlus from 'element-plus'
import 'element-plus/dist/index.css'
import router from './routes'

window.$ = window.jQuery = jQuery;


// Create and mount Vue app
const app = createApp(App);
app.use(ElementPlus);
app.use(router);
app.mount('#wtpfw_admin_settings_wrapper');
