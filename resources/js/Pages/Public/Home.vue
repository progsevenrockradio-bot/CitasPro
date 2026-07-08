<template>
  <div>
    <!-- Barra de pestañas superior si el directorio está habilitado -->
    <div v-if="enableDirectory" class="bg-gray-950 border-b border-white/10 sticky top-0 z-[60]">
      <div class="max-w-5xl mx-auto px-4">
        <div class="flex items-center gap-6 h-12">
          <button 
            @click="activeTab = 'landing'" 
            :class="[
              'text-sm font-semibold transition-colors border-b-2 py-3',
              activeTab === 'landing' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200'
            ]"
          >
            Sobre CitasPro
          </button>
          <button 
            @click="activeTab = 'directorio'" 
            :class="[
              'text-sm font-semibold transition-colors border-b-2 py-3 flex items-center gap-2',
              activeTab === 'directorio' ? 'border-indigo-500 text-white' : 'border-transparent text-gray-400 hover:text-gray-200'
            ]"
          >
            <span>Directorio de Negocios</span>
            <span class="px-2 py-0.5 rounded-full bg-indigo-600/20 text-indigo-400 text-[10px] uppercase font-bold border border-indigo-500/30">Beta</span>
          </button>
        </div>
      </div>
    </div>

    <!-- Contenido Dinámico -->
    <LandingInfo v-if="activeTab === 'landing' || !enableDirectory" />
    <Directorio v-if="activeTab === 'directorio' && enableDirectory" />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import LandingInfo from './LandingInfo.vue';
import Directorio from './Directorio.vue';

// Leer variable de entorno VITE_ENABLE_DIRECTORY
// Por defecto a false si no existe o si es "false"
const enableDirectory = import.meta.env.VITE_ENABLE_DIRECTORY === 'true';

// Pestaña activa por defecto: 'landing' (CitasPro)
const activeTab = ref('landing');

onMounted(() => {
  // Mejoras de SEO básicas
  document.title = "CitasPro | Tu negocio de citas en piloto automático";
  
  let metaDesc = document.querySelector('meta[name="description"]');
  if (!metaDesc) {
    metaDesc = document.createElement('meta');
    metaDesc.name = "description";
    document.head.appendChild(metaDesc);
  }
  metaDesc.content = "Gestiona tus profesionales, configura servicios y horarios, cobra de forma segura y automatiza recordatorios por WhatsApp y SMS para reducir inasistencias.";
});
</script>
