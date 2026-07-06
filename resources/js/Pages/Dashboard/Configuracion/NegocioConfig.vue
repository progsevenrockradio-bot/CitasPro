<template>
  <div class="space-y-6 max-w-4xl">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">{{ $t('config.titulo') }}</h2>
        <p class="text-text-muted text-sm mt-1">{{ $t('config.subtitulo') }}</p>
      </div>
      <button 
        @click="guardarCambios"
        :disabled="loading || saving"
        class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        {{ $t('acciones.guardar_cambios') }}
      </button>
    </div>

    <!-- Mensajes de feedback -->
    <div v-if="successMsg" class="bg-green-500/10 border border-green-500/50 text-green-400 p-4 rounded-xl flex items-center gap-3">
      <CheckCircle class="w-5 h-5" />
      <p>{{ successMsg }}</p>
    </div>
    
    <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl flex items-center gap-3">
      <AlertCircle class="w-5 h-5" />
      <p>{{ errorMsg }}</p>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-primary">
      <Loader2 class="w-10 h-10 animate-spin mb-4" />
      <p>{{ $t('acciones.cargando') }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Información Básica -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">{{ $t('config.info_basica') }}</h3>
        
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.nombre') }}</label>
          <input 
            v-model="form.nombre" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.descripcion') }}</label>
          <textarea 
            v-model="form.descripcion" 
            rows="3"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.sitio_web') }}</label>
          <input 
            v-model="form.sitio_web" 
            type="url" 
            placeholder="https://..."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div class="mt-6 pt-6 border-t border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-bold text-white">{{ $t('config.modulo_medico') }}</h4>
              <p class="text-sm text-text-muted mt-1">{{ $t('config.activa_fichas') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.es_medico" class="sr-only peer">
              <div class="w-11 h-6 bg-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Contacto y Ubicación -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">{{ $t('config.contacto_ubicacion') }}</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.telefono') }}</label>
            <input 
              v-model="form.telefono" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.whatsapp') }}</label>
            <input 
              v-model="form.whatsapp" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.email') }}</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.direccion') }}</label>
          <input 
            v-model="form.direccion" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        
        <LocationSelects
          v-model:model-country-id="form.pais_id"
          v-model:model-state-id="form.estado_id"
          v-model:model-city-id="form.ciudad_id"
          v-model:city-text="form.ciudad"
        />
      </div>
    </div>

    <!-- Zona Peligrosa -->
    <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6 mt-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-bold text-red-400">{{ $t('config.zona_peligro') }}</h3>
          <p class="text-sm text-text-muted mt-1">{{ $t('config.aviso_eliminar') }}</p>
        </div>
        <button 
          @click="mostrarModalEliminar = true"
          class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(239,68,68,0.2)] flex items-center justify-center gap-2 self-start md:self-center">
          <Trash2 class="w-4 h-4" />
          {{ $t('config.eliminar_btn') }}
        </button>
      </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div v-if="mostrarModalEliminar" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-bg-card border border-border rounded-2xl max-w-md w-full p-6 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <h3 class="text-xl font-bold text-white mb-2">{{ $t('config.modal_eliminar_titulo') }}</h3>
        <p class="text-text-muted text-sm mb-4">
          {{ $t('config.aviso_eliminar') }} {{ $t('config.para_confirmar') }} <strong>{{ form.nombre }}</strong>
        </p>

        <input 
          v-model="nombreConfirmacion" 
          type="text" 
          :placeholder="form.nombre" 
          class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500 transition-all mb-4 text-center"
        />

        <div class="flex gap-3">
          <button 
            @click="mostrarModalEliminar = false; nombreConfirmacion = ''" 
            class="flex-1 bg-white/5 hover:bg-white/10 text-white py-3 rounded-xl transition-all text-sm font-medium">
            {{ $t('acciones.cancelar') }}
          </button>
          <button 
            @click="eliminarNegocioDefinitivo" 
            :disabled="nombreConfirmacion !== form.nombre || deleting"
            class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white py-3 rounded-xl transition-all text-sm font-medium flex justify-center items-center gap-2">
            <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
            <Trash2 v-else class="w-4 h-4" />
            {{ deleting ? $t('acciones.eliminando') : $t('acciones.si_eliminar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { Save, AlertCircle, CheckCircle, Loader2, Trash2 } from 'lucide-vue-next';
import axios from 'axios';
import { useRouter } from 'vue-router';
import LocationSelects from '../../Components/LocationSelects.vue';

const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const mostrarModalEliminar = ref(false);
const nombreConfirmacion = ref('');

const form = ref({
  nombre: '',
  descripcion: '',
  sitio_web: '',
  telefono: '',
  whatsapp: '',
  email: '',
  direccion: '',
  ciudad: '',
  pais_id: null,
  estado_id: null,
  ciudad_id: null,
  horario_apertura: {},
  es_medico: false
});

const cargarNegocio = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/negocio');
    const d = res.data.negocio || {};
    form.value = {
      nombre: d.nombre || '',
      descripcion: d.descripcion || '',
      sitio_web: d.sitio_web || '',
      telefono: d.telefono || '',
      whatsapp: d.whatsapp || '',
      email: d.email || '',
      direccion: d.direccion || '',
      ciudad: d.ciudad || '',
      pais_id: d.pais_id || null,
      estado_id: d.estado_id || null,
      ciudad_id: d.ciudad_id || null,
      horario_apertura: d.horario_apertura || {},
      es_medico: Boolean(d.es_medico)
    };
  } catch (error) {
    console.error("Error al cargar negocio:", error);
    errorMsg.value = "No se pudo cargar la información del negocio.";
  } finally {
    loading.value = false;
  }
};

const guardarCambios = async () => {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    await axios.patch('/api/negocio', form.value);
    successMsg.value = 'Configuración guardada exitosamente.';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (error) {
    console.error("Error al guardar:", error);
    errorMsg.value = error.response?.data?.message || 'Hubo un error al guardar los cambios.';
  } finally {
    saving.value = false;
  }
};

const eliminarNegocioDefinitivo = async () => {
  if (nombreConfirmacion.value !== form.value.nombre) return;
  
  deleting.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.delete('/api/negocio');
    mostrarModalEliminar.value = false;
    
    // Limpiamos token local y mandamos a login
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
    
    alert(res.data.message);
    router.push('/login');
  } catch (error) {
    console.error("Error al borrar negocio:", error);
    errorMsg.value = error.response?.data?.message || 'Ocurrió un error al eliminar tu negocio.';
    mostrarModalEliminar.value = false;
  } finally {
    deleting.value = false;
    nombreConfirmacion.value = '';
  }
};

onMounted(() => {
  cargarNegocio();
});
</script>
