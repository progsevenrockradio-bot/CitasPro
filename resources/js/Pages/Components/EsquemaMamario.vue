<template>
  <div class="esquema-mamario-container bg-black/20 p-4 rounded-xl border border-white/10 flex flex-col items-center">
    
    <!-- Popover de Diagnóstico -->
    <div v-if="selectedZone" class="fixed z-50 bg-gray-800 border border-white/20 p-4 rounded-lg shadow-xl min-w-[250px]" :style="popoverStyle">
      <p class="text-sm font-bold text-white mb-2">
        {{ getZoneName(selectedBreast, selectedZone) }}
      </p>
      
      <div class="space-y-3">
        <div>
          <label class="text-xs text-gray-400 block mb-1">Hallazgo:</label>
          <select v-model="currentObservation.tipo" class="w-full bg-black/40 border border-white/10 rounded px-2 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            <option value="">Normal / Sin hallazgos</option>
            <option value="Nódulo">Nódulo</option>
            <option value="Quiste">Quiste</option>
            <option value="Asimetría">Asimetría</option>
            <option value="Retracción">Retracción</option>
            <option value="Secreción">Secreción</option>
            <option value="Dolor">Dolor</option>
            <option value="Cicatriz">Cicatriz</option>
            <option value="Otro">Otro...</option>
          </select>
        </div>
        
        <div v-if="currentObservation.tipo && currentObservation.tipo !== ''">
          <label class="text-xs text-gray-400 block mb-1">Observación adicional (opcional):</label>
          <textarea v-model="currentObservation.nota" rows="2" class="w-full bg-black/40 border border-white/10 rounded px-2 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500 resize-none"></textarea>
        </div>

        <div class="flex gap-2 pt-2 border-t border-white/10">
          <button @click="saveObservation" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs py-1.5 rounded transition-colors">Guardar</button>
          <button @click="cancelObservation" class="flex-1 bg-white/5 hover:bg-white/10 text-gray-300 text-xs py-1.5 rounded transition-colors">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- Leyenda Superior -->
    <div class="flex w-full justify-between max-w-[400px] mb-4 text-gray-400 text-sm font-semibold tracking-wider">
      <span>MAMA DERECHA</span>
      <span>MAMA IZQUIERDA</span>
    </div>

    <!-- SVG Esquema -->
    <svg viewBox="0 0 400 200" width="100%" class="max-w-[500px]">
      
      <!-- ================= MAMA DERECHA ================= -->
      <!-- Centro en (100, 100), Radio 80 -->
      <g transform="translate(100, 100)">
        <!-- Q. Supero-Externo (Arriba Izquierda visualmente, porque es derecha del paciente) -->
        <!-- M 0 0 L -80 0 A 80 80 0 0 1 0 -80 Z -->
        <path d="M 0 0 L -80 0 A 80 80 0 0 1 0 -80 Z" 
              :fill="getColor('derecha', 'supero_externo')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('derecha', 'supero_externo', $event)" />
              
        <!-- Q. Supero-Interno (Arriba Derecha visualmente) -->
        <path d="M 0 0 L 0 -80 A 80 80 0 0 1 80 0 Z" 
              :fill="getColor('derecha', 'supero_interno')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('derecha', 'supero_interno', $event)" />
              
        <!-- Q. Infero-Interno (Abajo Derecha visualmente) -->
        <path d="M 0 0 L 80 0 A 80 80 0 0 1 0 80 Z" 
              :fill="getColor('derecha', 'infero_interno')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('derecha', 'infero_interno', $event)" />
              
        <!-- Q. Infero-Externo (Abajo Izquierda visualmente) -->
        <path d="M 0 0 L 0 80 A 80 80 0 0 1 -80 0 Z" 
              :fill="getColor('derecha', 'infero_externo')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('derecha', 'infero_externo', $event)" />

        <!-- Areola/Pezón Central -->
        <circle cx="0" cy="0" r="20" 
                :fill="getColor('derecha', 'areola')" stroke="#374151" stroke-width="2" 
                class="cursor-pointer hover:opacity-70 transition-opacity"
                @click="onZoneClick('derecha', 'areola', $event)" />
                
        <!-- Cola de Spence (Derecha) - Extension supero-externa -->
        <path d="M -56 -56 Q -90 -90 -100 -50 Q -80 -30 -80 0 A 80 80 0 0 1 0 -80 Z" 
              fill="none" stroke="#374151" stroke-width="1" stroke-dasharray="4" />
      </g>


      <!-- ================= MAMA IZQUIERDA ================= -->
      <!-- Centro en (300, 100), Radio 80 -->
      <g transform="translate(300, 100)">
        <!-- Q. Supero-Interno (Arriba Izquierda visualmente) -->
        <path d="M 0 0 L -80 0 A 80 80 0 0 1 0 -80 Z" 
              :fill="getColor('izquierda', 'supero_interno')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('izquierda', 'supero_interno', $event)" />
              
        <!-- Q. Supero-Externo (Arriba Derecha visualmente) -->
        <path d="M 0 0 L 0 -80 A 80 80 0 0 1 80 0 Z" 
              :fill="getColor('izquierda', 'supero_externo')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('izquierda', 'supero_externo', $event)" />
              
        <!-- Q. Infero-Externo (Abajo Derecha visualmente) -->
        <path d="M 0 0 L 80 0 A 80 80 0 0 1 0 80 Z" 
              :fill="getColor('izquierda', 'infero_externo')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('izquierda', 'infero_externo', $event)" />
              
        <!-- Q. Infero-Interno (Abajo Izquierda visualmente) -->
        <path d="M 0 0 L 0 80 A 80 80 0 0 1 -80 0 Z" 
              :fill="getColor('izquierda', 'infero_interno')" stroke="#374151" stroke-width="2" 
              class="cursor-pointer hover:opacity-70 transition-opacity"
              @click="onZoneClick('izquierda', 'infero_interno', $event)" />

        <!-- Areola/Pezón Central -->
        <circle cx="0" cy="0" r="20" 
                :fill="getColor('izquierda', 'areola')" stroke="#374151" stroke-width="2" 
                class="cursor-pointer hover:opacity-70 transition-opacity"
                @click="onZoneClick('izquierda', 'areola', $event)" />
                
        <!-- Cola de Spence (Izquierda) - Extension supero-externa -->
        <path d="M 56 -56 Q 90 -90 100 -50 Q 80 -30 80 0 A 80 80 0 0 0 0 -80 Z" 
              fill="none" stroke="#374151" stroke-width="1" stroke-dasharray="4" />
      </g>
    </svg>

    <!-- Resumen de Hallazgos -->
    <div v-if="hasFindings" class="w-full mt-6 bg-black/40 rounded-lg p-4 border border-white/5">
      <h4 class="text-sm font-bold text-white mb-3">Resumen de Hallazgos</h4>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="getFindings('derecha').length > 0">
          <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Mama Derecha</p>
          <ul class="space-y-2">
            <li v-for="f in getFindings('derecha')" :key="f.zona" class="text-sm text-gray-300">
              <span class="font-semibold text-indigo-400">{{ getZoneNameOnly(f.zona) }}:</span> 
              {{ f.tipo }} <span v-if="f.nota" class="text-xs text-gray-500">({{ f.nota }})</span>
            </li>
          </ul>
        </div>
        <div v-if="getFindings('izquierda').length > 0">
          <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Mama Izquierda</p>
          <ul class="space-y-2">
            <li v-for="f in getFindings('izquierda')" :key="f.zona" class="text-sm text-gray-300">
              <span class="font-semibold text-indigo-400">{{ getZoneNameOnly(f.zona) }}:</span> 
              {{ f.tipo }} <span v-if="f.nota" class="text-xs text-gray-500">({{ f.nota }})</span>
            </li>
          </ul>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, watch, onMounted, computed } from 'vue';

const props = defineProps({
  modelValue: {
    type: Object,
    default: () => ({ derecha: {}, izquierda: {} })
  }
});

const emit = defineEmits(['update:modelValue']);

// Estado
const mamas = ref({ derecha: {}, izquierda: {} });

onMounted(() => {
  if (props.modelValue && Object.keys(props.modelValue).length > 0) {
    mamas.value = JSON.parse(JSON.stringify(props.modelValue));
    if(!mamas.value.derecha) mamas.value.derecha = {};
    if(!mamas.value.izquierda) mamas.value.izquierda = {};
  }
});

watch(mamas, (newVal) => {
  emit('update:modelValue', newVal);
}, { deep: true });

// Lógica de UI
const selectedBreast = ref(null);
const selectedZone = ref(null);
const popoverStyle = ref({ top: '0px', left: '0px' });

const currentObservation = ref({ tipo: '', nota: '' });

const getColor = (breast, zone) => {
  const data = mamas.value[breast]?.[zone];
  if (data && data.tipo && data.tipo !== '') {
    // Si hay un hallazgo
    if (data.tipo === 'Nódulo' || data.tipo === 'Tumor') return '#ef4444'; // rojo
    if (data.tipo === 'Quiste') return '#3b82f6'; // azul
    if (data.tipo === 'Dolor') return '#f59e0b'; // naranja
    return '#8b5cf6'; // morado default para otros
  }
  return '#1f2937'; // bg-gray-800 default
};

const getZoneNameOnly = (zone) => {
  const names = {
    supero_externo: 'C. Supero-Externo',
    supero_interno: 'C. Supero-Interno',
    infero_externo: 'C. Infero-Externo',
    infero_interno: 'C. Infero-Interno',
    areola: 'Región Areolar/Central'
  };
  return names[zone] || zone;
};

const getZoneName = (breast, zone) => {
  const b = breast === 'derecha' ? 'Mama Derecha' : 'Mama Izquierda';
  return `${b} - ${getZoneNameOnly(zone)}`;
};

const onZoneClick = (breast, zone, event) => {
  selectedBreast.value = breast;
  selectedZone.value = zone;
  
  // Cargar datos existentes si los hay
  const existing = mamas.value[breast]?.[zone];
  if (existing) {
    currentObservation.value = { ...existing };
  } else {
    currentObservation.value = { tipo: '', nota: '' };
  }

  // Posicionar
  popoverStyle.value = {
    top: `${event.clientY}px`,
    left: `${event.clientX}px`
  };
};

const saveObservation = () => {
  if (!mamas.value[selectedBreast.value]) {
    mamas.value[selectedBreast.value] = {};
  }
  
  if (currentObservation.value.tipo === '') {
    // Si se selecciona "Normal", borramos el registro
    delete mamas.value[selectedBreast.value][selectedZone.value];
  } else {
    mamas.value[selectedBreast.value][selectedZone.value] = { ...currentObservation.value };
  }
  
  cancelObservation();
};

const cancelObservation = () => {
  selectedBreast.value = null;
  selectedZone.value = null;
};

const hasFindings = computed(() => {
  return getFindings('derecha').length > 0 || getFindings('izquierda').length > 0;
});

const getFindings = (breast) => {
  if (!mamas.value[breast]) return [];
  const findings = [];
  for (const [zona, data] of Object.entries(mamas.value[breast])) {
    if (data && data.tipo) {
      findings.push({ zona, ...data });
    }
  }
  return findings;
};
</script>
