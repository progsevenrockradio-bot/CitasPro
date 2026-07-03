<template>
  <div class="space-y-6 max-w-4xl">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">Configuración del Negocio</h2>
        <p class="text-text-muted text-sm mt-1">Administra la información pública de tu negocio.</p>
      </div>
      <button 
        @click="guardarCambios"
        :disabled="loading || saving"
        class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        Guardar Cambios
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
      <p>Cargando información...</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Información Básica -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">Información Básica</h3>
        
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Nombre del Negocio</label>
          <input 
            v-model="form.nombre" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Descripción Corta</label>
          <textarea 
            v-model="form.descripcion" 
            rows="3"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Sitio Web (Opcional)</label>
          <input 
            v-model="form.sitio_web" 
            type="url" 
            placeholder="https://..."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
      </div>

      <!-- Contacto y Ubicación -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">Contacto y Ubicación</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Teléfono</label>
            <input 
              v-model="form.telefono" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">WhatsApp</label>
            <input 
              v-model="form.whatsapp" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Email de Contacto</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Dirección Física</label>
          <input 
            v-model="form.direccion" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Ciudad</label>
            <input 
              v-model="form.ciudad" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">País</label>
            <input 
              v-model="form.pais" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Save, Loader2, CheckCircle, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const loading = ref(true);
const saving = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const form = ref({
  nombre: '',
  descripcion: '',
  sitio_web: '',
  telefono: '',
  whatsapp: '',
  email: '',
  direccion: '',
  ciudad: '',
  pais: 'ES'
});

const cargarNegocio = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/negocio');
    const data = res.data;
    form.value = {
      nombre: data.nombre || '',
      descripcion: data.descripcion || '',
      sitio_web: data.sitio_web || '',
      telefono: data.telefono || '',
      whatsapp: data.whatsapp || '',
      email: data.email || '',
      direccion: data.direccion || '',
      ciudad: data.ciudad || '',
      pais: data.pais || 'ES'
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

onMounted(() => {
  cargarNegocio();
});
</script>
