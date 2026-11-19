<template>
  <div class="min-h-screen bg-bg flex items-start pt-16 pb-44 sm:items-center sm:py-8 justify-center p-4 relative overflow-x-hidden">
    <!-- Selector de Idioma Flotante -->
    <div class="absolute top-4 right-4 z-50">
      <LanguageSwitcher />
    </div>

    <!-- Decorative background -->
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full max-w-md bg-bg-card backdrop-blur-xl border border-border rounded-2xl p-8 shadow-2xl relative z-10">
      <div class="text-center mb-6">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.3)] mx-auto mb-4 overflow-hidden" :class="configs.logo_url ? 'bg-transparent' : 'bg-primary'">
          <img v-if="configs.logo_url" :src="configs.logo_url" alt="Logo" class="max-w-full max-h-full object-contain" />
          <span v-else class="text-white font-bold text-xl">C</span>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">{{ $t('auth.bienvenido') }}</h1>
        <p class="text-text-muted">{{ $t('auth.ingresa_panel') }}</p>
      </div>

      <!-- Selector de tipo de entrada -->
      <div class="grid grid-cols-2 gap-2 p-1 rounded-xl bg-black/30 border border-border mb-6">
        <button
          type="button"
          @click="selectAccountType('cliente')"
          :class="[
            'py-2.5 px-3 rounded-lg text-xs sm:text-sm font-medium transition-all',
            accountType === 'cliente'
              ? 'bg-primary text-white shadow-md'
              : 'text-text-muted hover:text-white hover:bg-white/5'
          ]"
        >
          {{ $t('auth.soy_cliente') }}
        </button>
        <button
          type="button"
          @click="selectAccountType('negocio')"
          :class="[
            'py-2.5 px-3 rounded-lg text-xs sm:text-sm font-medium transition-all',
            accountType === 'negocio'
              ? 'bg-primary text-white shadow-md'
              : 'text-text-muted hover:text-white hover:bg-white/5'
          ]"
        >
          {{ $t('auth.soy_negocio') }}
        </button>
      </div>

      <!-- FLUJO NEGOCIO / PROFESIONAL -->
      <template v-if="accountType === 'negocio'">
        <!-- PASO 1: Email y Contraseña -->
        <form v-if="!requiere2fa" @submit.prevent="handleLogin" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.correo') }}</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
              placeholder="dr@ejemplo.com"
            >
          </div>

          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.contrasena') }}</label>
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
            <span v-if="!loading">{{ $t('auth.ingresar') }}</span>
            <Loader2 v-else class="w-5 h-5 animate-spin" />
          </button>

          <p class="text-center text-sm text-text-muted mt-4">
            {{ $t('auth.no_tienes_cuenta') }} <router-link to="/registro" class="text-primary hover:underline">{{ $t('auth.registra_negocio') }}</router-link>
          </p>
        </form>

        <!-- PASO 2: OTP (Doble Factor) -->
        <form v-else @submit.prevent="verificarOtp2fa" class="space-y-4">
          <p class="text-center text-sm mb-4">
            <span v-if="canal2fa === 'email'">{{ $t('auth.enviado_correo') }} <strong>{{ destinatarioMascara }}</strong></span>
            <span v-else>{{ $t('auth.enviado_telegram') }} <strong>Telegram</strong></span>
          </p>

          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.codigo_pin') }}</label>
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
            <span v-if="!loading">{{ $t('auth.verificar_entrar') }}</span>
            <Loader2 v-else class="w-5 h-5 animate-spin" />
          </button>

          <button
            type="button"
            @click="requiere2fa = false; codigoOtp = ''; challengeToken = ''; errorMsg = ''"
            class="w-full bg-transparent hover:bg-white/5 text-text-muted font-medium py-2 rounded-xl transition-all mt-2"
          >
            {{ $t('auth.volver') }}
          </button>
        </form>
      </template>

      <!-- FLUJO CLIENTE -->
      <template v-else>
        <!-- PASO 1: Correo del cliente -->
        <form v-if="!clienteOtpEnviado" @submit.prevent="enviarOtpCliente" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.correo') }}</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
              placeholder="cliente@ejemplo.com"
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
            <span v-if="!loading">{{ $t('auth.enviar_codigo') }}</span>
            <Loader2 v-else class="w-5 h-5 animate-spin" />
          </button>
        </form>

        <!-- PASO 2: Código OTP del cliente -->
        <form v-else @submit.prevent="verificarOtpCliente" class="space-y-4">
          <p class="text-center text-sm mb-4">
            {{ $t('auth.enviado_correo') }} <strong>{{ destinatarioMascara || form.email }}</strong>
          </p>

          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.codigo_pin') }}</label>
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
            <span v-if="!loading">{{ $t('auth.verificar_entrar') }}</span>
            <Loader2 v-else class="w-5 h-5 animate-spin" />
          </button>

          <button
            type="button"
            @click="clienteOtpEnviado = false; codigoOtp = ''; errorMsg = ''"
            class="w-full bg-transparent hover:bg-white/5 text-text-muted font-medium py-2 rounded-xl transition-all mt-2"
          >
            {{ $t('auth.volver') }}
          </button>
        </form>
      </template>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { Loader2 } from 'lucide-vue-next';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';

const router = useRouter();
const accountType = ref('negocio');
const form = ref({ email: '', password: '' });
const loading = ref(false);
const errorMsg = ref('');
const configs = ref({});

// Control de 2FA (Negocio) y OTP (Cliente)
const requiere2fa = ref(false);
const clienteOtpEnviado = ref(false);
const canal2fa = ref('email');
const destinatarioMascara = ref('');
const codigoOtp = ref('');
const challengeToken = ref('');

const selectAccountType = (type) => {
  if (accountType.value === type) return;
  accountType.value = type;
  errorMsg.value = '';
  requiere2fa.value = false;
  clienteOtpEnviado.value = false;
  codigoOtp.value = '';
  challengeToken.value = '';
  destinatarioMascara.value = '';
};

onMounted(async () => {
  try {
    const res = await axios.get('/api/web-config');
    if (res.data.success) {
      configs.value = res.data.data;
    }
  } catch (error) {
    console.error("Error cargando configuración web", error);
  }
});

const completarAcceso = (data) => {
  localStorage.setItem('token', data.token);
  if (data.role) {
    localStorage.setItem('auth_role', data.role);
  } else {
    localStorage.removeItem('auth_role');
  }
  axios.defaults.headers.common['Authorization'] = `Bearer ${data.token}`;

  if (data.role === 'cliente') {
    router.push('/mi-cuenta');
  } else {
    router.push('/panel');
  }
};

const handleLogin = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    await axios.get('/sanctum/csrf-cookie');

    const isSuperAdmin = form.value.email.trim().toLowerCase() === 'admin@citaspro.com';
    const loginEndpoint = isSuperAdmin ? '/api/admin/login' : '/api/auth/login-contrasena';
    const payload = isSuperAdmin
      ? form.value
      : { ...form.value, account_type: 'negocio' };

    const res = await axios.post(loginEndpoint, payload);

    if (res.data.requiere_2fa) {
      requiere2fa.value = true;
      canal2fa.value = res.data.canal;
      destinatarioMascara.value = res.data.destinatario;
      challengeToken.value = res.data.challenge_token || '';
    } else {
      completarAcceso(res.data);
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
      challenge_token: challengeToken.value,
      account_type: 'negocio',
      es_registro: false
    };

    const res = await axios.post('/api/auth/otp/verificar', payload);
    completarAcceso(res.data);
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Código incorrecto o expirado.';
  } finally {
    loading.value = false;
  }
};

const enviarOtpCliente = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    await axios.get('/sanctum/csrf-cookie');
    const res = await axios.post('/api/auth/otp/enviar', {
      email: form.value.email,
      canal: 'email',
      account_type: 'cliente'
    });
    clienteOtpEnviado.value = true;
    destinatarioMascara.value = res.data.message || form.value.email;
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'No se pudo enviar el código de acceso.';
  } finally {
    loading.value = false;
  }
};

const verificarOtpCliente = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.post('/api/auth/otp/verificar', {
      email: form.value.email,
      codigo: codigoOtp.value,
      account_type: 'cliente',
      es_registro: false
    });
    completarAcceso(res.data);
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Código incorrecto o expirado.';
  } finally {
    loading.value = false;
  }
};
</script>
