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

      <!-- PASO 1: Email y Contraseña -->
      <form v-if="!requiere2fa" @submit.prevent="handleLogin" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Correo Electrónico</label>
          <input 
            v-model="form.email" 
            type="email" 
            required
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
            placeholder="dr@ejemplo.com"
          >
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Contraseña</label>
          <input 
            v-model="form.password" 
            type="password" 
            required
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
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

      <!-- PASO 2: OTP (Doble Factor) -->
      <form v-else @submit.prevent="verificarOtp2fa" class="space-y-4">
        <p class="text-center text-sm mb-4">
          <span v-if="canal2fa === 'email'">Hemos enviado un código a tu correo <strong>{{ destinatarioMascara }}</strong></span>
          <span v-else>Hemos enviado un código por <strong>Telegram</strong></span>
        </p>
        
        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Código PIN (2FA)</label>
          <input 
            v-model="codigoOtp" 
            type="text" 
            required 
            maxlength="6" 
            placeholder="123456"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white text-center text-2xl tracking-[0.5em] focus:outline-none focus:border-primary transition-all"
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
          <span v-if="!loading">Verificar y Entrar</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>

        <button 
          type="button" 
          @click="requiere2fa = false; codigoOtp = ''" 
          class="w-full bg-transparent hover:bg-white/5 text-text-muted font-medium py-2 rounded-xl transition-all mt-2"
        >
          Volver atrás
        </button>
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

// Control de 2FA
const requiere2fa = ref(false);
const canal2fa = ref('email');
const destinatarioMascara = ref('');
const codigoOtp = ref('');

const handleLogin = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    await axios.get('/sanctum/csrf-cookie');
    
    // Cambiamos el endpoint al de contraseñas de profesionales
    const res = await axios.post('/api/auth/login-contrasena', form.value);
    
    if (res.data.requiere_2fa) {
      // Activar paso de 2FA
      requiere2fa.value = true;
      canal2fa.value = res.data.canal;
      destinatarioMascara.value = res.data.destinatario;
    } else {
      // Login directo sin 2FA
      localStorage.setItem('token', res.data.token);
      axios.defaults.headers.common['Authorization'] = `Bearer ${res.data.token}`;
      router.push('/panel');
    }
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Credenciales incorrectas.';
  } finally {
    loading.value = false;
  }
};

const verificarOtp2fa = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const payload = {
      email: form.value.email,
      codigo: codigoOtp.value,
      es_registro: false
    };
    
    // Verificamos el PIN contra la base de datos
    const res = await axios.post('/api/auth/otp/verificar', payload);
    
    // Login exitoso
    localStorage.setItem('token', res.data.token);
    axios.defaults.headers.common['Authorization'] = `Bearer ${res.data.token}`;
    router.push('/panel');
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Código incorrecto o expirado.';
  } finally {
    loading.value = false;
  }
};
</script>
