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

// Interceptor para redireccionar al login si expira la sesión (401 o 419)
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response && (error.response.status === 401 || error.response.status === 419)) {
      localStorage.removeItem('token');
      router.push('/login');
    }
    return Promise.reject(error);
  }
);

app.mount('#app');
