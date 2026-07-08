<template>
  <div class="p-6 max-w-4xl mx-auto pb-24">
    <div class="mb-8">
      <h1 class="text-3xl font-black text-white">Configuración Web</h1>
      <p class="text-gray-400 mt-2">Gestiona los textos, tipografías e imágenes de la página principal pública.</p>
    </div>

    <div v-if="loading" class="animate-pulse space-y-4">
      <div class="h-12 bg-white/5 rounded-xl w-full"></div>
      <div class="h-40 bg-white/5 rounded-xl w-full"></div>
    </div>

    <div v-else class="space-y-6">
      <!-- Pestañas -->
      <div class="flex gap-2 border-b border-white/10 pb-4 overflow-x-auto">
        <button 
          v-for="tab in tabs" 
          :key="tab.id"
          @click="activeTab = tab.id"
          :class="['px-4 py-2 rounded-lg font-bold text-sm transition-all whitespace-nowrap', activeTab === tab.id ? 'bg-primary text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10']"
        >
          {{ tab.label }}
        </button>
      </div>

      <!-- Formulario -->
      <form @submit.prevent="guardar" class="bg-bg-card border border-border-sutil rounded-2xl p-6">
        
        <!-- Pestaña 1: Tipografía -->
        <div v-if="activeTab === 'tipografia'" class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Fuente Principal (Cuerpo)</label>
            <input v-model="configs.font_primary" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" placeholder="Ej. Inter, Roboto, Outfit..." />
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Fuente para Títulos H1</label>
            <input v-model="configs.font_h1" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" placeholder="Ej. Outfit" />
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Fuente para Títulos H2</label>
            <input v-model="configs.font_h2" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" placeholder="Ej. Outfit" />
          </div>
        </div>

        <!-- Pestaña 2: Textos Hero -->
        <div v-if="activeTab === 'textos'" class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Etiqueta Superior (Badge)</label>
            <input v-model="configs.hero_badge" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" />
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Título Principal (H1)</label>
            <input v-model="configs.hero_title" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" />
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Subtítulo</label>
            <textarea v-model="configs.hero_subtitle" rows="3" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none"></textarea>
          </div>
        </div>

        <!-- Pestaña 3: Imágenes -->
        <div v-if="activeTab === 'imagenes'" class="space-y-6">
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Logo Principal</label>
            <div class="flex items-center gap-4">
              <div v-if="logoPreview" class="w-20 h-20 bg-gray-900 rounded-xl p-2 flex items-center justify-center border border-white/10">
                <img :src="logoPreview" class="max-w-full max-h-full object-contain" />
              </div>
              <input type="file" @change="e => files.logo_url = e.target.files[0]" accept="image/*" class="text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-hover" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Fondo del Hero (Directorio)</label>
            <div class="flex items-center gap-4">
              <div v-if="heroBgPreview" class="w-32 h-20 bg-gray-900 rounded-xl p-0 overflow-hidden flex items-center justify-center border border-white/10">
                <img :src="heroBgPreview" class="w-full h-full object-cover" />
              </div>
              <input type="file" @change="e => files.hero_bg_url = e.target.files[0]" accept="image/*" class="text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-primary-hover" />
            </div>
          </div>
        </div>

        <!-- Pestaña 4: Pricing -->
        <div v-if="activeTab === 'pricing'" class="space-y-6">
          <div class="flex items-center justify-between bg-white/5 p-4 rounded-xl">
            <div>
              <p class="font-bold text-white">Mostrar Plan Gratuito</p>
              <p class="text-sm text-gray-400">Si se desactiva, el plan gratuito se oculta de la vista pública.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="configs.pricing_show_free" true-value="true" false-value="false" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>
          <div class="flex items-center justify-between bg-white/5 p-4 rounded-xl">
            <div>
              <p class="font-bold text-white">Destacar Plan PRO</p>
              <p class="text-sm text-gray-400">Muestra la tarjeta PRO más grande y con colores resaltados.</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="configs.pricing_pro_highlight" true-value="true" false-value="false" class="sr-only peer">
              <div class="w-11 h-6 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>
          <div>
            <label class="block text-sm font-bold text-gray-400 mb-2">Enlace botón Enterprise</label>
            <input v-model="configs.pricing_enterprise_contact" type="text" class="w-full bg-white/5 border border-white/10 rounded-xl px-4 py-3 text-white focus:border-primary outline-none" placeholder="Ej. /contacto o https://wa.me/..." />
          </div>
        </div>

        <!-- Submit -->
        <div class="mt-8 pt-6 border-t border-white/10 flex justify-end">
          <button type="submit" :disabled="saving" class="px-6 py-3 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl transition-all disabled:opacity-50 flex items-center gap-2">
            <span v-if="saving">Guardando...</span>
            <span v-else>Guardar Cambios</span>
          </button>
        </div>

      </form>
    </div>

    <!-- Modal de confirmación/aviso personalizado -->
    <ConfirmModal 
      v-model:show="showConfirmModal"
      :title="confirmTitle"
      :message="confirmMessage"
      :type="confirmType"
      :confirm-text="confirmText"
      :cancel-text="cancelText"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import ConfirmModal from '../../Components/ConfirmModal.vue';

const loading = ref(true);
const saving = ref(false);
const activeTab = ref('tipografia');

// Refs para el modal de confirmación personalizado
const showConfirmModal = ref(false);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('success');
const confirmText = ref('Aceptar');
const cancelText = ref('');

const tabs = [
  { id: 'tipografia', label: 'Tipografía' },
  { id: 'textos', label: 'Textos y Banner' },
  { id: 'imagenes', label: 'Imágenes y Logos' },
  { id: 'pricing', label: 'Tarjetas Pricing' },
];

const configs = ref({});
const files = ref({
  logo_url: null,
  hero_bg_url: null
});

// Vistas previas en tiempo real
const logoPreview = computed(() => {
  if (files.value.logo_url) {
    return URL.createObjectURL(files.value.logo_url);
  }
  return configs.value.logo_url;
});

const heroBgPreview = computed(() => {
  if (files.value.hero_bg_url) {
    return URL.createObjectURL(files.value.hero_bg_url);
  }
  return configs.value.hero_bg_url;
});

onMounted(async () => {
  try {
    const res = await axios.get('/api/web-config');
    if (res.data.success) {
      configs.value = res.data.data;
    }
  } catch (error) {
    console.error("Error cargando configuración web:", error);
  } finally {
    loading.value = false;
  }
});

const guardar = async () => {
  saving.value = true;
  try {
    const formData = new FormData();
    
    // Adjuntar strings y booleanos
    Object.keys(configs.value).forEach(key => {
      // Si hay un archivo nuevo cargado para logo o fondo, no enviamos la URL string vieja
      if (key === 'logo_url' && files.value.logo_url) return;
      if (key === 'hero_bg_url' && files.value.hero_bg_url) return;
      
      formData.append(key, configs.value[key]);
    });
    
    // Adjuntar archivos si existen
    if (files.value.logo_url) formData.append('logo_url', files.value.logo_url);
    if (files.value.hero_bg_url) formData.append('hero_bg_url', files.value.hero_bg_url);

    const token = localStorage.getItem('token');
    const res = await axios.post('/api/superadmin/web-config', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
        'Authorization': `Bearer ${token}`
      }
    });

    if (res.data.success) {
      configs.value = res.data.data;
      files.value = { logo_url: null, hero_bg_url: null };
      
      // Mostrar modal de éxito
      confirmTitle.value = 'Éxito';
      confirmMessage.value = 'Configuración guardada correctamente.';
      confirmType.value = 'success';
      confirmText.value = 'Aceptar';
      cancelText.value = '';
      showConfirmModal.value = true;
    }
  } catch (error) {
    // Mostrar modal de error
    confirmTitle.value = 'Error';
    confirmMessage.value = 'Error al guardar la configuración.';
    confirmType.value = 'danger';
    confirmText.value = 'Aceptar';
    cancelText.value = '';
    showConfirmModal.value = true;
    console.error(error);
  } finally {
    saving.value = false;
  }
};
</script>
