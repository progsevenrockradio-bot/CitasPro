<template>
  <div class="odontograma-container bg-black/20 p-4 rounded-xl border border-white/10 overflow-x-auto">
    <div class="min-w-[700px]">
      
      <!-- Popover de Diagnóstico -->
      <div v-if="selectedSurface" class="fixed z-50 bg-gray-800 border border-white/20 p-3 rounded-lg shadow-xl" :style="popoverStyle">
        <p class="text-xs font-bold text-white mb-2">
          Pieza {{ selectedTooth.id }} - {{ selectedSurfaceName }}
        </p>
        <div class="flex flex-col gap-1">
          <button @click="setDiagnostico('Caries')" class="text-left text-xs px-2 py-1 hover:bg-white/10 rounded text-red-400">🔴 Caries</button>
          <button @click="setDiagnostico('Obturación')" class="text-left text-xs px-2 py-1 hover:bg-white/10 rounded text-blue-400">🔵 Obturación</button>
          <button @click="setDiagnostico('Extracción')" class="text-left text-xs px-2 py-1 hover:bg-white/10 rounded text-gray-400">❌ Extracción (Toda la pieza)</button>
          <button @click="setDiagnostico('Corona')" class="text-left text-xs px-2 py-1 hover:bg-white/10 rounded text-yellow-400">👑 Corona</button>
          <div class="border-t border-white/10 my-1"></div>
          <button @click="setDiagnostico(null)" class="text-left text-xs px-2 py-1 hover:bg-white/10 rounded text-white">Limpiar</button>
        </div>
      </div>

      <!-- Cuadrantes Superiores -->
      <div class="flex justify-center gap-8 mb-4">
        <!-- Permanente Superior Derecho (18 a 11) -->
        <div class="flex gap-1">
          <div v-for="id in [18,17,16,15,14,13,12,11]" :key="id" class="flex flex-col items-center gap-1">
            <span class="text-xs text-gray-400 font-mono">{{ id }}</span>
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" />
          </div>
        </div>
        <!-- Permanente Superior Izquierdo (21 a 28) -->
        <div class="flex gap-1">
          <div v-for="id in [21,22,23,24,25,26,27,28]" :key="id" class="flex flex-col items-center gap-1">
            <span class="text-xs text-gray-400 font-mono">{{ id }}</span>
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" />
          </div>
        </div>
      </div>

      <!-- Cuadrantes Temporales Superiores -->
      <div class="flex justify-center gap-8 mb-8">
        <div class="flex gap-1">
          <div v-for="id in [55,54,53,52,51]" :key="id" class="flex flex-col items-center gap-1">
            <span class="text-xs text-gray-500 font-mono">{{ id }}</span>
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" is-temporal />
          </div>
        </div>
        <div class="flex gap-1">
          <div v-for="id in [61,62,63,64,65]" :key="id" class="flex flex-col items-center gap-1">
            <span class="text-xs text-gray-500 font-mono">{{ id }}</span>
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" is-temporal />
          </div>
        </div>
      </div>

      <!-- Cuadrantes Temporales Inferiores -->
      <div class="flex justify-center gap-8 mb-4">
        <div class="flex gap-1">
          <div v-for="id in [85,84,83,82,81]" :key="id" class="flex flex-col items-center gap-1">
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" is-temporal />
            <span class="text-xs text-gray-500 font-mono">{{ id }}</span>
          </div>
        </div>
        <div class="flex gap-1">
          <div v-for="id in [71,72,73,74,75]" :key="id" class="flex flex-col items-center gap-1">
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" is-temporal />
            <span class="text-xs text-gray-500 font-mono">{{ id }}</span>
          </div>
        </div>
      </div>

      <!-- Cuadrantes Inferiores -->
      <div class="flex justify-center gap-8">
        <!-- Permanente Inferior Derecho (48 a 41) -->
        <div class="flex gap-1">
          <div v-for="id in [48,47,46,45,44,43,42,41]" :key="id" class="flex flex-col items-center gap-1">
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" />
            <span class="text-xs text-gray-400 font-mono">{{ id }}</span>
          </div>
        </div>
        <!-- Permanente Inferior Izquierdo (31 a 38) -->
        <div class="flex gap-1">
          <div v-for="id in [31,32,33,34,35,36,37,38]" :key="id" class="flex flex-col items-center gap-1">
            <Diente :id="id" :data="dientes[id]" @surface-click="onSurfaceClick" />
            <span class="text-xs text-gray-400 font-mono">{{ id }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import Diente from './OdontogramaDiente.vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue']);

// Estructura de estado de los dientes
const dientes = ref({});

const initDientes = () => {
  const piezas = [
    18,17,16,15,14,13,12,11, 21,22,23,24,25,26,27,28,
    48,47,46,45,44,43,42,41, 31,32,33,34,35,36,37,38,
    55,54,53,52,51, 61,62,63,64,65,
    85,84,83,82,81, 71,72,73,74,75
  ];
  
  // Clonar el estado inicial o vaciarlo
  const initialState = props.modelValue || {};
  piezas.forEach(p => {
    if (!dientes.value[p]) {
      dientes.value[p] = initialState[p] ? JSON.parse(JSON.stringify(initialState[p])) : { top: null, right: null, bottom: null, left: null, center: null, whole: null };
    }
  });
};

onMounted(() => {
  initDientes();
  document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Sincronizar hacia arriba
watch(dientes, (newVal) => {
  // Limpiar vacíos antes de emitir para no hacer el JSON gigante
  const cleanState = {};
  for (const [key, tooth] of Object.entries(newVal)) {
    if (tooth.top || tooth.right || tooth.bottom || tooth.left || tooth.center || tooth.whole) {
      cleanState[key] = tooth;
    }
  }
  emit('update:modelValue', cleanState);
}, { deep: true });

// Sincronizar hacia abajo
watch(() => props.modelValue, (newVal) => {
  // Solo sincronizamos si hay diferencias reales para evitar ciclos
  if (JSON.stringify(newVal) !== JSON.stringify(emitState())) {
     const piezas = Object.keys(dientes.value);
     piezas.forEach(p => {
        dientes.value[p] = newVal && newVal[p] ? JSON.parse(JSON.stringify(newVal[p])) : { top: null, right: null, bottom: null, left: null, center: null, whole: null };
     });
  }
}, { deep: true });

const emitState = () => {
  const cleanState = {};
  for (const [key, tooth] of Object.entries(dientes.value)) {
    if (tooth.top || tooth.right || tooth.bottom || tooth.left || tooth.center || tooth.whole) {
      cleanState[key] = tooth;
    }
  }
  return cleanState;
};

// Lógica del Popover
const selectedTooth = ref(null);
const selectedSurface = ref(null);
const selectedSurfaceName = ref('');
const popoverStyle = ref({ top: '0px', left: '0px' });

const onSurfaceClick = ({ id, surface, event }) => {
  selectedTooth.value = dientes.value[id];
  selectedTooth.value.id = id; // guardamos ID para referencia
  selectedSurface.value = surface;
  
  const names = { top: 'Vestibular', bottom: 'Lingual/Palatino', left: 'Distal/Mesial', right: 'Mesial/Distal', center: 'Oclusal' };
  selectedSurfaceName.value = names[surface] || surface;

  // Posicionar popover
  popoverStyle.value = {
    top: `${event.clientY + 10}px`,
    left: `${event.clientX + 10}px`
  };
};

const setDiagnostico = (diag) => {
  if (!selectedTooth.value || !selectedSurface.value) return;
  
  if (diag === 'Extracción' || diag === 'Corona') {
    selectedTooth.value.whole = diag;
    // Limpiamos las superficies
    selectedTooth.value.top = null;
    selectedTooth.value.right = null;
    selectedTooth.value.bottom = null;
    selectedTooth.value.left = null;
    selectedTooth.value.center = null;
  } else {
    selectedTooth.value.whole = null;
    selectedTooth.value[selectedSurface.value] = diag;
  }
  
  // Cerrar popover
  selectedSurface.value = null;
  selectedTooth.value = null;
};

const handleClickOutside = (e) => {
  // Cerrar popover si se hace clic fuera del contenedor
  if (selectedSurface.value && !e.target.closest('.odontograma-container') && !e.target.closest('.fixed.z-50')) {
    selectedSurface.value = null;
    selectedTooth.value = null;
  }
};
</script>
