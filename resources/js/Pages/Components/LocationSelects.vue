<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- País -->
    <div>
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.pais') : 'País' }}</label>
      <CustomSelect 
        :modelValue="modelCountryId"
        @update:modelValue="$emit('update:modelCountryId', $event)"
        :options="paisesOptions"
        :placeholder="$t ? $t('ubicacion.selecciona_pais') : 'Selecciona un país'"
      />
    </div>

    <!-- Estado (sólo si el país tiene estados) -->
    <div v-if="estados.length > 0">
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.estado') : 'Estado / Región' }}</label>
      <CustomSelect 
        :modelValue="modelStateId"
        @update:modelValue="$emit('update:modelStateId', $event)"
        :options="estadosOptions"
        :placeholder="$t ? $t('ubicacion.selecciona_estado') : 'Selecciona un estado'"
      />
    </div>

    <!-- Ciudad -->
    <div v-if="estados.length > 0 && ciudades.length > 0">
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.ciudad') : 'Ciudad' }}</label>
      <CustomSelect 
        :modelValue="modelCityId"
        @update:modelValue="(val) => { $emit('update:modelCityId', val); updateCityText(val); }"
        :options="ciudadesOptions"
        :placeholder="$t ? $t('ubicacion.selecciona_ciudad') : 'Selecciona una ciudad'"
      />
    </div>
    
    <!-- Input texto libre de ciudad si el país no tiene estados registrados -->
    <div v-else-if="modelCountryId && estados.length === 0 && !loadingEstados">
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.ciudad') : 'Ciudad' }}</label>
      <input 
        :value="cityText"
        @input="$emit('update:cityText', $event.target.value)"
        type="text" 
        :placeholder="$t ? $t('ubicacion.ciudad_libre') : 'Escribe tu ciudad'"
        class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
      />
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import CustomSelect from './CustomSelect.vue';

const props = defineProps({
  modelCountryId: { type: Number, default: null },
  modelStateId: { type: Number, default: null },
  modelCityId: { type: Number, default: null },
  cityText: { type: String, default: '' },
});

const emit = defineEmits([
  'update:modelCountryId', 
  'update:modelStateId', 
  'update:modelCityId', 
  'update:cityText'
]);

const paises = ref([]);
const estados = ref([]);
const ciudades = ref([]);
const loadingEstados = ref(false);

const paisesOptions = computed(() => paises.value.map(p => ({ value: p.id, label: p.nombre, icon: p.bandera || '🏳️' })));
const estadosOptions = computed(() => estados.value.map(e => ({ value: e.id, label: e.nombre })));
const ciudadesOptions = computed(() => ciudades.value.map(c => ({ value: c.id, label: c.nombre })));

onMounted(async () => {
  await cargarPaises();
  // Si tenemos país inicial, cargamos estados
  if (props.modelCountryId) {
    await cargarEstados(props.modelCountryId);
    // Si tenemos estado inicial, cargamos ciudades
    if (props.modelStateId) {
      await cargarCiudades(props.modelStateId);
    }
  }
});

const cargarPaises = async () => {
  try {
    const res = await axios.get('/api/paises');
    paises.value = res.data;
  } catch (e) {
    console.error('Error al cargar países', e);
  }
};

const cargarEstados = async (paisId) => {
  if (!paisId) {
    estados.value = [];
    return;
  }
  loadingEstados.value = true;
  try {
    const res = await axios.get(`/api/locations/states/${paisId}`);
    estados.value = res.data.success ? res.data.data : [];
  } catch (e) {
    console.error('Error al cargar estados', e);
    estados.value = [];
  } finally {
    loadingEstados.value = false;
  }
};

const cargarCiudades = async (estadoId) => {
  if (!estadoId) {
    ciudades.value = [];
    return;
  }
  try {
    const res = await axios.get(`/api/locations/cities/${estadoId}`);
    ciudades.value = res.data.success ? res.data.data : [];
  } catch (e) {
    console.error('Error al cargar ciudades', e);
    ciudades.value = [];
  }
};

// Cuando el prop del país cambia
watch(() => props.modelCountryId, async (newVal, oldVal) => {
  // Solo reaccionar si el cambio viene del usuario en el componente (evitar bucles de inicialización)
  if (oldVal !== undefined && newVal !== oldVal) {
    emit('update:modelStateId', null);
    emit('update:modelCityId', null);
    emit('update:cityText', '');
    ciudades.value = [];
    await cargarEstados(newVal);
  }
});

// Cuando el prop del estado cambia
watch(() => props.modelStateId, async (newVal, oldVal) => {
  if (oldVal !== undefined && newVal !== oldVal) {
    emit('update:modelCityId', null);
    emit('update:cityText', '');
    await cargarCiudades(newVal);
  }
});

// Actualizar el texto de la ciudad cuando seleccionan en el select
const updateCityText = (cityIdStr) => {
  if (!cityIdStr) {
    emit('update:cityText', '');
    return;
  }
  const cityId = Number(cityIdStr);
  const city = ciudades.value.find(c => c.id === cityId);
  if (city) {
    emit('update:cityText', city.nombre);
  }
};
</script>
