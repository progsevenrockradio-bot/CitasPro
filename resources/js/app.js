import './bootstrap';
import { createApp } from 'vue';
import router from './router';
import App from './App.vue';

// axios defaults for Sanctum CSRF
import axios from 'axios';
axios.defaults.withCredentials = true;
axios.defaults.withXSRFToken = true;

const app = createApp(App);

app.use(router);
app.mount('#app');
