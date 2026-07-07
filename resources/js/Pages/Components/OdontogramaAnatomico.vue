<template>
  <div class="odontograma-anatomico-container bg-black/20 p-4 rounded-xl border border-white/10 flex flex-col items-center">
    
    <!-- Popover de Diagnóstico -->
    <div v-if="selectedTooth" class="fixed z-50 bg-gray-800 border border-white/20 p-4 rounded-lg shadow-xl min-w-[250px]" :style="popoverStyle">
      <p class="text-sm font-bold text-white mb-2">
        Pieza {{ selectedTooth }}
      </p>
      
      <div class="space-y-3">
        <div>
          <label class="text-xs text-gray-400 block mb-1">Hallazgo Clínico:</label>
          <select v-model="currentObservation.tipo" class="w-full bg-black/40 border border-white/10 rounded px-2 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500">
            <option value="">Normal / Sin hallazgos</option>
            <option value="Endodoncia">Endodoncia</option>
            <option value="Absceso">Absceso / Infección</option>
            <option value="Implante">Implante</option>
            <option value="Corona">Corona</option>
            <option value="Pérdida Ósea">Pérdida Ósea</option>
            <option value="Extracción">Extracción</option>
            <option value="Otro">Otro...</option>
          </select>
        </div>
        
        <div v-if="currentObservation.tipo && currentObservation.tipo !== ''">
          <label class="text-xs text-gray-400 block mb-1">Nota adicional (opcional):</label>
          <textarea v-model="currentObservation.nota" rows="2" class="w-full bg-black/40 border border-white/10 rounded px-2 py-1.5 text-sm text-white focus:outline-none focus:border-indigo-500 resize-none"></textarea>
        </div>

        <div class="flex gap-2 pt-2 border-t border-white/10">
          <button @click="saveObservation" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs py-1.5 rounded transition-colors">Guardar</button>
          <button @click="cancelObservation" class="flex-1 bg-white/5 hover:bg-white/10 text-gray-300 text-xs py-1.5 rounded transition-colors">Cancelar</button>
        </div>
      </div>
    </div>

    <!-- Leyenda Superior -->
    <div class="flex w-full justify-center mb-4">
      <h3 class="text-white text-sm font-bold">Odontograma Anatómico</h3>
    </div>

    <!-- SVG Anatómico (Paths orgánicos) -->
    <svg width="100%" height="auto" viewBox="0 0 1000 600" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid meet" class="max-w-[800px] w-full mx-auto">
      <g id="anatomical-odontogram" transform="translate(0, -100)">
        
        <!-- ARCO SUPERIOR -->
        <g id="superior-arch">
          <!-- 18 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('18', $event)">
            <path d="M150,220 C160,210 180,210 190,220 C200,240 200,270 190,290 C180,310 160,310 150,290 Z M170,290 C175,300 175,320 170,330 M150,290 C145,300 145,320 150,330 M190,290 C195,300 195,320 190,330" stroke="#9CA3AF" :fill="getFillColor('18')" stroke-width="1.5" />
            <text x="160" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">18</text>
          </g>
          <!-- 17 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('17', $event)">
            <path d="M210,220 C220,210 240,210 250,220 C260,240 260,270 250,290 C240,310 220,310 210,290 Z M230,290 C235,300 235,320 230,330 M210,290 C205,300 205,320 210,330 M250,290 C255,300 255,320 250,330" stroke="#9CA3AF" :fill="getFillColor('17')" stroke-width="1.5" />
            <text x="220" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">17</text>
          </g>
          <!-- 16 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('16', $event)">
            <path d="M270,220 C280,210 300,210 310,220 C320,240 320,270 310,290 C300,310 280,310 270,290 Z M290,290 C295,300 295,320 290,330 M270,290 C265,300 265,320 270,330 M310,290 C315,300 315,320 310,330" stroke="#9CA3AF" :fill="getFillColor('16')" stroke-width="1.5" />
            <text x="280" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">16</text>
          </g>
          <!-- 15 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('15', $event)">
            <path d="M330,225 C335,220 345,220 350,225 C355,235 355,265 350,285 C345,305 335,305 330,285 Z M340,285 C345,295 345,315 340,325 M330,285 C325,295 325,315 330,325" stroke="#9CA3AF" :fill="getFillColor('15')" stroke-width="1.5" />
            <text x="330" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">15</text>
          </g>
          <!-- 14 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('14', $event)">
            <path d="M370,225 C375,220 385,220 390,225 C395,235 395,265 390,285 C385,305 375,305 370,285 Z M380,285 C385,295 385,315 380,325 M370,285 C365,295 365,315 370,325" stroke="#9CA3AF" :fill="getFillColor('14')" stroke-width="1.5" />
            <text x="370" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">14</text>
          </g>
          <!-- 13 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('13', $event)">
            <path d="M410,220 C420,210 430,210 440,220 C450,240 440,290 425,320 C410,290 400,240 410,220 Z" stroke="#9CA3AF" :fill="getFillColor('13')" stroke-width="1.5" />
            <text x="415" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">13</text>
          </g>
          <!-- 12 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('12', $event)">
            <path d="M460,225 C465,220 475,220 480,225 C485,235 485,270 480,295 C475,320 465,320 460,295 Z" stroke="#9CA3AF" :fill="getFillColor('12')" stroke-width="1.5" />
            <text x="460" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">12</text>
          </g>
          <!-- 11 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('11', $event)">
            <path d="M500,220 C510,210 520,210 530,220 C540,230 540,270 530,290 C520,310 510,310 500,290 Z" stroke="#9CA3AF" :fill="getFillColor('11')" stroke-width="1.5" />
            <text x="505" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">11</text>
          </g>

          <!-- 21 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('21', $event)">
            <path d="M560,220 C570,210 580,210 590,220 C600,230 600,270 590,290 C580,310 570,310 560,290 Z" stroke="#9CA3AF" :fill="getFillColor('21')" stroke-width="1.5" />
            <text x="565" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">21</text>
          </g>
          <!-- 22 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('22', $event)">
            <path d="M610,225 C615,220 625,220 630,225 C635,235 635,270 630,295 C625,320 615,320 610,295 Z" stroke="#9CA3AF" :fill="getFillColor('22')" stroke-width="1.5" />
            <text x="610" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">22</text>
          </g>
          <!-- 23 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('23', $event)">
            <path d="M660,220 C670,210 680,210 690,220 C700,240 690,290 675,320 C660,290 650,240 660,220 Z" stroke="#9CA3AF" :fill="getFillColor('23')" stroke-width="1.5" />
            <text x="665" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">23</text>
          </g>
          <!-- 24 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('24', $event)">
            <path d="M710,225 C715,220 725,220 730,225 C735,235 735,265 730,285 C725,305 715,305 710,285 Z M720,285 C725,295 725,315 720,325 M710,285 C705,295 705,315 710,325" stroke="#9CA3AF" :fill="getFillColor('24')" stroke-width="1.5" />
            <text x="710" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">24</text>
          </g>
          <!-- 25 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('25', $event)">
            <path d="M750,225 C755,220 765,220 770,225 C775,235 775,265 770,285 C765,305 755,305 750,285 Z M760,285 C765,295 765,315 760,325 M750,285 C745,295 745,315 750,325" stroke="#9CA3AF" :fill="getFillColor('25')" stroke-width="1.5" />
            <text x="750" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">25</text>
          </g>
          <!-- 26 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('26', $event)">
            <path d="M790,220 C800,210 820,210 830,220 C840,240 840,270 830,290 C820,310 800,310 790,290 Z M810,290 C815,300 815,320 810,330 M790,290 C785,300 785,320 790,330 M830,290 C835,300 835,320 830,330" stroke="#9CA3AF" :fill="getFillColor('26')" stroke-width="1.5" />
            <text x="800" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">26</text>
          </g>
          <!-- 27 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('27', $event)">
            <path d="M850,220 C860,210 880,210 890,220 C900,240 900,270 890,290 C880,310 860,310 850,290 Z M870,290 C875,300 875,320 870,330 M850,290 C845,300 845,320 850,330 M890,290 C895,300 895,320 890,330" stroke="#9CA3AF" :fill="getFillColor('27')" stroke-width="1.5" />
            <text x="860" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">27</text>
          </g>
          <!-- 28 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('28', $event)">
            <path d="M910,220 C920,210 940,210 950,220 C960,240 960,270 950,290 C940,310 920,310 910,290 Z M930,290 C935,300 935,320 930,330 M910,290 C905,300 905,320 910,330 M950,290 C955,300 955,320 950,330" stroke="#9CA3AF" :fill="getFillColor('28')" stroke-width="1.5" />
            <text x="920" y="190" font-family="Arial" font-size="16" fill="#9CA3AF">28</text>
          </g>
        </g>

        <!-- ARCO INFERIOR -->
        <g id="inferior-arch">
          <!-- 48 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('48', $event)">
            <path d="M150,480 C160,490 180,490 190,480 C200,460 200,430 190,410 C180,390 160,390 150,410 Z M170,410 C175,400 175,380 170,370 M150,410 C145,400 145,380 150,370" stroke="#9CA3AF" :fill="getFillColor('48')" stroke-width="1.5" />
            <text x="160" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">48</text>
          </g>
          <!-- 47 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('47', $event)">
            <path d="M210,480 C220,490 240,490 250,480 C260,460 260,430 250,410 C240,390 220,390 210,410 Z M230,410 C235,400 235,380 230,370 M210,410 C205,400 205,380 210,370" stroke="#9CA3AF" :fill="getFillColor('47')" stroke-width="1.5" />
            <text x="220" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">47</text>
          </g>
          <!-- 46 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('46', $event)">
            <path d="M270,480 C280,490 300,490 310,480 C320,460 320,430 310,410 C300,390 280,390 270,410 Z M290,410 C295,400 295,380 290,370 M270,410 C265,400 265,380 270,370" stroke="#9CA3AF" :fill="getFillColor('46')" stroke-width="1.5" />
            <text x="280" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">46</text>
          </g>
          <!-- 45 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('45', $event)">
            <path d="M330,475 C335,480 345,480 350,475 C355,465 355,435 350,415 C345,395 335,395 330,415 Z M340,415 C345,405 345,385 340,375" stroke="#9CA3AF" :fill="getFillColor('45')" stroke-width="1.5" />
            <text x="330" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">45</text>
          </g>
          <!-- 44 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('44', $event)">
            <path d="M370,475 C375,480 385,480 390,475 C395,465 395,435 390,415 C385,395 375,395 370,415 Z M380,415 C385,405 385,385 380,375" stroke="#9CA3AF" :fill="getFillColor('44')" stroke-width="1.5" />
            <text x="370" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">44</text>
          </g>
          <!-- 43 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('43', $event)">
            <path d="M410,480 C420,490 430,490 440,480 C450,460 440,410 425,380 C410,410 400,460 410,480 Z" stroke="#9CA3AF" :fill="getFillColor('43')" stroke-width="1.5" />
            <text x="415" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">43</text>
          </g>
          <!-- 42 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('42', $event)">
            <path d="M460,475 C465,480 475,480 480,475 C485,465 485,430 480,405 C475,380 465,380 460,405 Z" stroke="#9CA3AF" :fill="getFillColor('42')" stroke-width="1.5" />
            <text x="460" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">42</text>
          </g>
          <!-- 41 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('41', $event)">
            <path d="M500,480 C510,490 520,490 530,480 C540,470 540,430 530,410 C520,390 510,390 500,410 Z" stroke="#9CA3AF" :fill="getFillColor('41')" stroke-width="1.5" />
            <text x="505" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">41</text>
          </g>
          <!-- 31 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('31', $event)">
            <path d="M560,480 C570,490 580,490 590,480 C600,470 600,430 590,410 C580,390 570,390 560,410 Z" stroke="#9CA3AF" :fill="getFillColor('31')" stroke-width="1.5" />
            <text x="565" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">31</text>
          </g>
          <!-- 32 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('32', $event)">
            <path d="M610,475 C615,480 625,480 630,475 C635,465 635,430 630,405 C625,380 615,380 610,405 Z" stroke="#9CA3AF" :fill="getFillColor('32')" stroke-width="1.5" />
            <text x="610" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">32</text>
          </g>
          <!-- 33 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('33', $event)">
            <path d="M660,480 C670,490 680,490 690,480 C700,460 690,410 675,380 C660,410 650,460 660,480 Z" stroke="#9CA3AF" :fill="getFillColor('33')" stroke-width="1.5" />
            <text x="665" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">33</text>
          </g>
          <!-- 34 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('34', $event)">
            <path d="M710,475 C715,480 725,480 730,475 C735,465 735,435 730,415 C725,395 715,395 710,415 Z M720,415 C725,405 725,385 720,375" stroke="#9CA3AF" :fill="getFillColor('34')" stroke-width="1.5" />
            <text x="710" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">34</text>
          </g>
          <!-- 35 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('35', $event)">
            <path d="M750,475 C755,480 765,480 770,475 C775,465 775,435 770,415 C765,395 755,395 750,415 Z M760,415 C765,405 765,385 760,375" stroke="#9CA3AF" :fill="getFillColor('35')" stroke-width="1.5" />
            <text x="750" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">35</text>
          </g>
          <!-- 36 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('36', $event)">
            <path d="M790,480 C800,490 820,490 830,480 C840,460 840,430 830,410 C820,390 800,390 790,410 Z M810,410 C815,400 815,380 810,370 M790,410 C785,400 785,380 790,370" stroke="#9CA3AF" :fill="getFillColor('36')" stroke-width="1.5" />
            <text x="800" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">36</text>
          </g>
          <!-- 37 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('37', $event)">
            <path d="M850,480 C860,490 880,490 890,480 C900,460 900,430 890,410 C880,390 860,390 850,410 Z M870,410 C875,400 875,380 870,370 M850,410 C845,400 845,380 850,370" stroke="#9CA3AF" :fill="getFillColor('37')" stroke-width="1.5" />
            <text x="860" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">37</text>
          </g>
          <!-- 38 -->
          <g class="cursor-pointer hover:opacity-70 transition-opacity" @click="onToothClick('38', $event)">
            <path d="M910,480 C920,490 940,490 950,480 C960,460 960,430 950,410 C940,390 920,390 910,410 Z M930,410 C935,400 935,380 930,370 M910,410 C905,400 905,380 910,370" stroke="#9CA3AF" :fill="getFillColor('38')" stroke-width="1.5" />
            <text x="920" y="520" font-family="Arial" font-size="16" fill="#9CA3AF">38</text>
          </g>
        </g>

        <text x="20" y="350" font-family="Arial, sans-serif" font-size="24" fill="#9CA3AF">DERECHA</text>
        <text x="820" y="350" font-family="Arial, sans-serif" font-size="24" fill="#9CA3AF">IZQUIERDA</text>
      </g>
    </svg>

    <!-- Resumen de Hallazgos -->
    <div v-if="hasFindings" class="w-full mt-6 bg-black/40 rounded-lg p-4 border border-white/5">
      <h4 class="text-sm font-bold text-white mb-3">Hallazgos Anatómicos</h4>
      <div class="grid grid-cols-1 gap-2">
        <div v-for="finding in getFindings()" :key="finding.tooth" class="text-sm text-gray-300">
          <span class="font-semibold text-indigo-400">Pieza {{ finding.tooth }}:</span> 
          {{ finding.tipo }} <span v-if="finding.nota" class="text-xs text-gray-500">({{ finding.nota }})</span>
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
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue']);

// Estado: { '18': { tipo: 'Endodoncia', nota: '' }, ... }
const odontogramaState = ref({});

onMounted(() => {
  if (props.modelValue && Object.keys(props.modelValue).length > 0) {
    odontogramaState.value = JSON.parse(JSON.stringify(props.modelValue));
  }
});

watch(odontogramaState, (newVal) => {
  emit('update:modelValue', newVal);
}, { deep: true });

// Lógica de Popover
const selectedTooth = ref(null);
const popoverStyle = ref({ top: '0px', left: '0px' });
const currentObservation = ref({ tipo: '', nota: '' });

const onToothClick = (tooth, event) => {
  selectedTooth.value = tooth;
  
  // Cargar datos existentes si los hay
  const existing = odontogramaState.value[tooth];
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

const getFillColor = (tooth) => {
  const data = odontogramaState.value[tooth];
  if (!data || !data.tipo) return 'transparent'; // O '#1f2937' si prefieren fondo
  
  if (data.tipo === 'Endodoncia') return '#3b82f6'; // azul
  if (data.tipo === 'Absceso') return '#ef4444'; // rojo
  if (data.tipo === 'Implante') return '#10b981'; // verde
  if (data.tipo === 'Corona') return '#f59e0b'; // naranja
  if (data.tipo === 'Extracción') return '#6b7280'; // gris
  if (data.tipo === 'Pérdida Ósea') return '#8b5cf6'; // morado
  
  return '#1f2937'; 
};

const saveObservation = () => {
  if (currentObservation.value.tipo === '') {
    // Si se selecciona "Normal", borramos el registro
    delete odontogramaState.value[selectedTooth.value];
  } else {
    odontogramaState.value[selectedTooth.value] = { ...currentObservation.value };
  }
  cancelObservation();
};

const cancelObservation = () => {
  selectedTooth.value = null;
};

const hasFindings = computed(() => {
  return Object.keys(odontogramaState.value).length > 0;
});

const getFindings = () => {
  const findings = [];
  for (const [tooth, data] of Object.entries(odontogramaState.value)) {
    if (data && data.tipo) {
      findings.push({ tooth, ...data });
    }
  }
  return findings.sort((a,b) => parseInt(a.tooth) - parseInt(b.tooth));
};
</script>
