<template>
  <div class="min-h-screen bg-bg flex items-center justify-center p-4">
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full max-w-lg bg-bg-card backdrop-blur-xl border border-border rounded-2xl p-8 shadow-2xl relative z-10">
      <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.3)] mx-auto mb-4">
          <span class="text-white font-bold text-xl">C</span>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">Registra tu Negocio</h1>
        <p class="text-text-muted">Crea tu cuenta en CitasPro gratis</p>
      </div>

      <!-- PASO 1: Datos -->
      <form v-if="step === 1" @submit.prevent="enviarOtp" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">Nombre</label>
            <input v-model="form.nombre" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">Apellido</label>
            <input v-model="form.apellido" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Correo Electrónico</label>
          <input v-model="form.email" type="email" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Teléfono (WhatsApp)</label>
          <input v-model="form.telefono" type="tel" required placeholder="+34600111222" class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
          <p class="text-xs text-text-muted mt-1">Incluye el código de país (ej. +34, +57, +58)</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Nombre del Negocio</label>
          <input v-model="form.nombre_negocio" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Tipo de Negocio</label>
          <CustomSelect 
            v-model="form.categoria_id" 
            :options="categoriaOptions" 
            placeholder="Selecciona una categoría"
          />
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-3 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2 mt-4">
          <span v-if="!loading">Continuar</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>

        <p class="text-center text-sm text-text-muted mt-4">
          ¿Ya tienes cuenta? <router-link to="/login" class="text-primary hover:underline">Inicia sesión</router-link>
        </p>
      </form>

      <!-- PASO 2: OTP -->
      <form v-if="step === 2" @submit.prevent="verificarOtp" class="space-y-4">
        <p class="text-center text-sm mb-4">Hemos enviado un código a <strong>{{ form.telefono }}</strong></p>
        
        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">Código PIN</label>
          <input v-model="form.codigo" type="text" required maxlength="6" class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white text-center text-2xl tracking-[0.5em] focus:outline-none focus:border-primary transition-all" placeholder="123456">
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-3 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2 mt-4">
          <span v-if="!loading">Verificar y Crear Cuenta</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>
        
        <button type="button" @click="step = 1" class="w-full bg-transparent hover:bg-white/5 text-text-muted font-medium py-2 rounded-xl transition-all mt-2">
          Volver atrás
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import CustomSelect from '../Components/CustomSelect.vue';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const errorMsg = ref('');
const categorias = ref([]);

const form = ref({
  nombre: '',
  apellido: '',
  email: '',
  telefono: '',
  nombre_negocio: '',
  categoria_id: '',
  codigo: '',
  es_registro: true
});

const categoriaOptions = computed(() => {
  return categorias.value.map(cat => ({
    value: cat.id,
    label: cat.nombre,
    icon: cat.icono
  }));
});

onMounted(async () => {
  try {
    const res = await axios.get('/api/categorias');
    categorias.value = res.data;
  } catch (e) {
    console.error("No se pudieron cargar las categorías");
  }
});

const enviarOtp = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    await axios.post('/api/auth/otp/enviar', { telefono: form.value.telefono });
    step.value = 2;
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Error al procesar el registro.';
  } finally {
    loading.value = false;
  }
};

const verificarOtp = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    await axios.get('/sanctum/csrf-cookie');
    const res = await axios.post('/api/auth/otp/verificar', form.value);
    
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
