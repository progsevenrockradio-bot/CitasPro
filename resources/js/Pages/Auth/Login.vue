<template>
  <div class="min-h-screen bg-bg flex items-center justify-center p-4">
    <!-- Decorative background -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full max-w-md bg-bg-card backdrop-blur-xl border border-border rounded-2xl p-8 shadow-2xl relative z-10">
      <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.3)] mx-auto mb-4">
          <span class="text-white font-bold text-xl">C</span>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">Bienvenido de nuevo</h1>
        <p class="text-text-muted">Ingresa a tu panel de CitasPro</p>
      </div>

      <form @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Correo Electrónico</label>
          <input 
            v-model="form.email" 
            type="email" 
            required
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
            placeholder="dr@ejemplo.com"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Contraseña</label>
          <input 
            v-model="form.password" 
            type="password" 
            required
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
            placeholder="••••••••"
          >
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <button 
          type="submit" 
          :disabled="loading"
          class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-3 rounded-xl transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex justify-center items-center gap-2"
        >
          <span v-if="!loading">Ingresar al Panel</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>
        
        <p class="text-center text-sm text-text-muted mt-4">
          ¿No tienes cuenta? <router-link to="/registro" class="text-primary hover:underline">Registra tu negocio</router-link>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const router = useRouter();
const form = ref({ email: '', password: '' });
const loading = ref(false);
const errorMsg = ref('');

const handleLogin = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    // Inicializar CSRF de Sanctum
    await axios.get('/sanctum/csrf-cookie');
    
    // Attempt Login
    const res = await axios.post('/api/admin/login', form.value);
    
    // Guardamos el token real de sanctum y el indicador de sesión
    localStorage.setItem('token', res.data.token);
    
    // Configurar el token por defecto para futuras peticiones
    axios.defaults.headers.common['Authorization'] = `Bearer ${res.data.token}`;
    
    // Redirect
    router.push('/panel');
  } catch (error) {
    if (error.response?.status === 401 || error.response?.status === 422) {
      errorMsg.value = 'Credenciales incorrectas.';
    } else {
      errorMsg.value = 'Error al intentar iniciar sesión.';
    }
  } finally {
    loading.value = false;
  }
};
</script>
