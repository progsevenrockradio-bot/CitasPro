<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <!-- País -->
    <div>
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.pais') : 'País' }}</label>
      <select 
        :value="modelCountryId"
        @input="$emit('update:modelCountryId', $event.target.value ? Number($event.target.value) : null)"
        class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
      >
        <option value="">{{ $t ? $t('ubicacion.selecciona_pais') : 'Selecciona un país' }}</option>
        <option v-for="p in paises" :key="p.id" :value="p.id">
          {{ p.bandera || '🏳️' }} {{ p.nombre }}
        </option>
      </select>
    </div>

    <!-- Estado (sólo si el país tiene estados) -->
    <div v-if="estados.length > 0">
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.estado') : 'Estado / Región' }}</label>
      <select 
        :value="modelStateId"
        @input="$emit('update:modelStateId', $event.target.value ? Number($event.target.value) : null)"
        class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
      >
        <option value="">{{ $t ? $t('ubicacion.selecciona_estado') : 'Selecciona un estado' }}</option>
        <option v-for="e in estados" :key="e.id" :value="e.id">
          {{ e.nombre }}
        </option>
      </select>
    </div>

    <!-- Ciudad -->
    <div v-if="estados.length > 0 && ciudades.length > 0">
      <label class="block text-sm font-medium text-text-muted mb-2">{{ $t ? $t('ubicacion.ciudad') : 'Ciudad' }}</label>
      <select 
        :value="modelCityId"
        @input="$emit('update:modelCityId', $event.target.value ? Number($event.target.value) : null); updateCityText($event.target.value)"
        class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
      >
        <option value="">{{ $t ? $t('ubicacion.selecciona_ciudad') : 'Selecciona una ciudad' }}</option>
        <option v-for="c in ciudades" :key="c.id" :value="c.id">
          {{ c.nombre }}
        </option>
      </select>
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
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

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
