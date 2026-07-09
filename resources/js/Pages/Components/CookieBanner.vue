<template>
  <div v-if="visible" class="fixed bottom-6 left-6 right-6 md:left-auto md:max-w-md z-50 animate-fade-in-up">
    <!-- Main Banner Panel -->
    <div class="bg-zinc-900/90 backdrop-blur-xl border border-zinc-800 rounded-2xl shadow-2xl p-6 text-white">
      <div v-if="!showConfig">
        <div class="flex items-center gap-3 mb-3">
          <div class="p-2 bg-blue-500/10 text-blue-400 rounded-lg">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h3 class="text-lg font-semibold font-sans">Control de Cookies</h3>
        </div>
        
        <p class="text-sm text-zinc-400 mb-5 leading-relaxed">
          Utilizamos cookies propias y de terceros para optimizar tu experiencia y analizar el uso de nuestra plataforma. Puedes aceptar todas, configurar tus preferencias o rechazar las no esenciales. 
          Lee nuestra 
          <a href="/politica-cookies" class="text-blue-400 hover:text-blue-300 underline" target="_blank">Política de Cookies</a>.
        </p>

        <div class="flex flex-col sm:flex-row gap-3">
          <button 
            @click="acceptAll" 
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-xl transition-all shadow-lg shadow-blue-600/20 text-sm"
          >
            Aceptar todas
          </button>
          
          <button 
            @click="acceptEssentialOnly" 
            class="flex-1 bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-medium py-2.5 px-4 rounded-xl transition-all text-sm border border-zinc-700/50"
          >
            Rechazar no esenciales
          </button>
        </div>
        
        <div class="text-center mt-4">
          <button 
            @click="showConfig = true" 
            class="text-xs text-zinc-500 hover:text-zinc-300 transition-colors"
          >
            ⚙️ Configurar preferencias
          </button>
        </div>
      </div>

      <!-- Config Panel -->
      <div v-else>
        <h3 class="text-lg font-semibold font-sans mb-3 text-white">Configuración de Cookies</h3>
        
        <div class="space-y-4 mb-6">
          <div class="flex items-start justify-between gap-4 p-3 bg-zinc-950/40 rounded-xl border border-zinc-800/50">
            <div>
              <h4 class="text-sm font-semibold text-white">Técnicas (Esenciales)</h4>
              <p class="text-xs text-zinc-500">Necesarias para el login, CSRF y funcionamiento básico.</p>
            </div>
            <span class="text-xs text-blue-400 font-medium py-1 px-2.5 bg-blue-500/10 rounded-lg">Siempre activo</span>
          </div>

          <div class="flex items-start justify-between gap-4 p-3 bg-zinc-950/40 rounded-xl border border-zinc-800/50">
            <div>
              <h4 class="text-sm font-semibold text-white">Analíticas</h4>
              <p class="text-xs text-zinc-500">Nos ayudan a saber cómo interactúan los usuarios con la web.</p>
            </div>
            <input type="checkbox" v-model="config.analytical" class="rounded border-zinc-700 bg-zinc-950 text-blue-500 focus:ring-blue-500 w-5 h-5 mt-1" />
          </div>

          <div class="flex items-start justify-between gap-4 p-3 bg-zinc-950/40 rounded-xl border border-zinc-800/50">
            <div>
              <h4 class="text-sm font-semibold text-white">Marketing</h4>
              <p class="text-xs text-zinc-500">Para métricas de campañas de publicidad personalizada.</p>
            </div>
            <input type="checkbox" v-model="config.marketing" class="rounded border-zinc-700 bg-zinc-950 text-blue-500 focus:ring-blue-500 w-5 h-5 mt-1" />
          </div>
        </div>

        <div class="flex gap-3">
          <button 
            @click="savePreferences" 
            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-medium py-2.5 px-4 rounded-xl transition-all text-sm"
          >
            Guardar preferencias
          </button>
          
          <button 
            @click="showConfig = false" 
            class="bg-zinc-800 hover:bg-zinc-700 text-zinc-300 font-medium py-2.5 px-4 rounded-xl transition-all text-sm border border-zinc-700"
          >
            Atrás
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const visible = ref(false);
const showConfig = ref(false);

const config = ref({
  essential: true,
  analytical: false,
  marketing: false
});

onMounted(() => {
  const consent = localStorage.getItem('cookie_consent');
  if (!consent) {
    visible.value = true;
  } else {
    try {
      const parsed = JSON.parse(consent);
      if (parsed.analytical) {
        enableAnalytics();
      }
    } catch (e) {
      visible.value = true;
    }
  }
});

const acceptAll = () => {
  config.value.analytical = true;
  config.value.marketing = true;
  save();
};

const acceptEssentialOnly = () => {
  config.value.analytical = false;
  config.value.marketing = false;
  save();
};

const savePreferences = () => {
  save();
};

const save = () => {
  localStorage.setItem('cookie_consent', JSON.stringify(config.value));
  visible.value = false;
  
  if (config.value.analytical) {
    enableAnalytics();
  }
};

const enableAnalytics = () => {
  // Aquí se inicializaría Clarity o Google Analytics si se requiere en el futuro
  console.log("Analytics cookies enabled by the user.");
};
</script>

<style scoped>
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fade-in-up {
  animation: fadeInUp 0.4s ease-out forwards;
}
</style>
