<template>
  <div class="min-h-screen bg-bg flex items-center justify-center p-4 relative">
    <!-- Selector de Idioma Flotante -->
    <div class="absolute top-4 right-4 z-50">
      <LanguageSwitcher />
    </div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary/20 rounded-full blur-[100px] pointer-events-none"></div>

    <div class="w-full max-w-lg bg-bg-card backdrop-blur-xl border border-border rounded-2xl p-8 shadow-2xl relative z-10">
      <div class="text-center mb-8">
        <div class="w-12 h-12 rounded-xl bg-primary flex items-center justify-center shadow-[0_0_20px_rgba(99,102,241,0.3)] mx-auto mb-4">
          <span class="text-white font-bold text-xl">C</span>
        </div>
        <h1 class="text-2xl font-bold text-white mb-2">{{ $t('auth.registro_titulo') }}</h1>
        <p class="text-text-muted">{{ $t('auth.registro_subtitulo') }}</p>
      </div>

      <!-- PASO 1: Datos -->
      <form v-if="step === 1" @submit.prevent="enviarOtp" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.nombre') }}</label>
            <input v-model="form.nombre" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.apellido') }}</label>
            <input v-model="form.apellido" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.correo') }}</label>
          <input v-model="form.email" type="email" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.contrasena') }}</label>
          <input v-model="form.password" type="password" required :placeholder="$t('auth.min_caracteres')" class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
        </div>


        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.telefono') }}</label>
          <div class="flex gap-2">
            <!-- Selector de Código de País -->
            <div class="w-36 flex-shrink-0">
              <CustomSelect 
                v-model="paisSeleccionado" 
                :options="paisOptions" 
                :placeholder="$t('auth.prefijo')"
              />
            </div>
            <!-- Número de Teléfono -->
            <div class="flex-1">
              <input 
                v-model="telefonoIngresado" 
                type="tel" 
                required 
                placeholder="600111222" 
                class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
              >
            </div>
          </div>
          <p class="text-xs text-text-muted mt-1">{{ $t('auth.info_telefono') }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.nombre_negocio') }}</label>
          <input v-model="form.nombre_negocio" type="text" required class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all">
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.tipo_negocio') }}</label>
          <CustomSelect 
            v-model="form.categoria_id" 
            :options="categoriaOptions" 
            :placeholder="$t('auth.selecciona_categoria')"
          />
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-3 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2 mt-4">
          <span v-if="!loading">{{ $t('auth.continuar') }}</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>

        <p class="text-center text-sm text-text-muted mt-4">
          {{ $t('auth.ya_tienes_cuenta') }} <router-link to="/login" class="text-primary hover:underline">{{ $t('auth.inicia_sesion') }}</router-link>
        </p>
      </form>

      <!-- PASO 2: OTP -->
      <form v-if="step === 2" @submit.prevent="verificarOtp" class="space-y-4">
        <p class="text-center text-sm mb-4">
          <span v-if="canalEnvio === 'email'">{{ $t('auth.enviado_correo') }} <strong>{{ form.email }}</strong></span>
          <span v-else-if="canalEnvio === 'telegram'">{{ $t('auth.enviado_telegram') }} <strong>Telegram</strong></span>
          <span v-else>{{ $t('auth.enviado_telefono') }} <strong>{{ form.telefono }}</strong></span>
        </p>
        
        <div>
          <label class="block text-sm font-medium text-text-muted mb-1">{{ $t('auth.codigo_pin') }}</label>
          <input v-model="form.codigo" type="text" required maxlength="6" class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white text-center text-2xl tracking-[0.5em] focus:outline-none focus:border-primary transition-all" placeholder="123456">
        </div>

        <div v-if="errorMsg" class="p-3 rounded-lg bg-red-500/10 border border-red-500/20 text-red-400 text-sm">
          {{ errorMsg }}
        </div>

        <button type="submit" :disabled="loading" class="w-full bg-primary hover:bg-primary-hover text-white font-medium py-3 rounded-xl transition-all shadow-lg flex justify-center items-center gap-2">
          <span v-if="!loading">{{ $t('auth.verificar_crear') }}</span>
          <Loader2 v-else class="w-5 h-5 animate-spin" />
        </button>

        <button type="button" @click="step = 1" class="w-full bg-transparent hover:bg-white/5 text-text-muted font-medium py-2 rounded-xl transition-all">
          {{ $t('auth.volver') }}
        </button>

        <button type="button" @click="enviarOtp" :disabled="loading" class="w-full text-xs text-primary hover:underline mt-2">
          {{ $t('auth.reenviar') }}
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
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';

const router = useRouter();
const step = ref(1);
const loading = ref(false);
const errorMsg = ref('');
const categorias = ref([]);
const paises = ref([]);

const canalEnvio = ref('email'); // Canal por defecto: email
const paisSeleccionado = ref('34'); // Por defecto España (+34)
const telefonoIngresado = ref('');

const form = ref({
  nombre: '',
  apellido: '',
  email: '',
  password: '', // Añadido
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

const paisOptions = computed(() => {
  return paises.value.map(p => ({
    value: p.prefijo,
    label: `+${p.prefijo}`,
    icon: p.bandera
  }));
});

onMounted(async () => {
  try {
    const [resCats, resPaises] = await Promise.all([
      axios.get('/api/categorias'),
      axios.get('/api/paises')
    ]);
    categorias.value = resCats.data;
    paises.value = resPaises.data;
  } catch (e) {
    console.error("No se pudieron cargar los datos iniciales");
  }
});

const enviarOtp = async () => {
  loading.value = true;
  errorMsg.value = '';
  
  // Limpiamos espacios y caracteres no numéricos del teléfono ingresado
  const numLimpio = telefonoIngresado.value.replace(/\D/g, '');
  
  // Concatenamos el prefijo telefónico seleccionado
  form.value.telefono = `+${paisSeleccionado.value}${numLimpio}`;

  try {
    // Enviamos teléfono, email, contraseña y el canal seleccionado por el usuario
    await axios.post('/api/auth/otp/enviar', { 
      telefono: form.value.telefono,
      email: form.value.email,
      password: form.value.password,
      canal: canalEnvio.value
    });
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
    
    // Aseguramos que el teléfono esté concatenado con el prefijo correcto
    const numLimpio = telefonoIngresado.value.replace(/\D/g, '');
    form.value.telefono = `+${paisSeleccionado.value}${numLimpio}`;
    
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
