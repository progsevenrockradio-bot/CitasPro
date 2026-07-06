<template>
  <div class="bg-bg-card border border-border rounded-2xl p-6 mt-6 space-y-5" v-if="paisId">
    <div class="flex justify-between items-center border-b border-border/50 pb-3">
      <h3 class="text-lg font-bold">Datos Fiscales y Facturación</h3>
      <button 
        @click="guardarDatosFiscales"
        :disabled="saving || !hasFields"
        class="bg-primary/20 hover:bg-primary/30 text-primary px-4 py-1.5 rounded-lg text-sm font-medium transition-colors flex items-center gap-2"
      >
        <Loader2 v-if="saving" class="w-3.5 h-3.5 animate-spin" />
        <Save v-else class="w-3.5 h-3.5" />
        Guardar Fiscales
      </button>
    </div>

    <div v-if="loadingFields" class="flex items-center justify-center py-6 text-text-muted">
      <Loader2 class="w-6 h-6 animate-spin mr-2" />
      <span>Cargando requerimientos fiscales del país...</span>
    </div>

    <div v-else-if="!hasFields" class="text-text-muted text-sm italic py-2">
      No hay requerimientos fiscales específicos configurados para este país.
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div v-for="field in fiscalFields" :key="field.key" class="space-y-1.5">
        <label class="block text-sm font-medium text-text-muted">
          {{ field.label }} <span v-if="field.required" class="text-red-400">*</span>
        </label>

        <!-- Tipo Text -->
        <input 
          v-if="field.type === 'text'"
          v-model="form[field.key]"
          type="text"
          :placeholder="field.placeholder"
          class="w-full bg-black/20 border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
          :class="errors[field.key] ? 'border-red-500' : 'border-border'"
        />

        <!-- Tipo Select -->
        <select
          v-else-if="field.type === 'select'"
          v-model="form[field.key]"
          class="w-full bg-black/20 border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
          :class="errors[field.key] ? 'border-red-500' : 'border-border'"
        >
          <option value="">Seleccione...</option>
          <option v-for="(label, val) in field.options" :key="val" :value="val">
            {{ label }}
          </option>
        </select>

        <!-- Mensaje de Error -->
        <p v-if="errors[field.key]" class="text-xs text-red-400 mt-1">
          {{ errors[field.key][0] }}
        </p>
      </div>
    </div>
    
    <div v-if="successMsg" class="text-sm text-green-400 flex items-center gap-2 mt-2">
      <CheckCircle class="w-4 h-4" /> {{ successMsg }}
    </div>
    <div v-if="generalError" class="text-sm text-red-400 flex items-center gap-2 mt-2">
      <AlertCircle class="w-4 h-4" /> {{ generalError }}
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { Save, Loader2, CheckCircle, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  paisId: {
    type: Number,
    default: null
  },
  initialFields: {
    type: Array,
    default: () => []
  },
  initialData: {
    type: Object,
    default: () => ({})
  }
});

const fiscalFields = ref(props.initialFields || []);
const loadingFields = ref(false);
const saving = ref(false);
const form = ref({ ...props.initialData });
const errors = ref({});
const successMsg = ref('');
const generalError = ref('');

const hasFields = computed(() => fiscalFields.value && fiscalFields.value.length > 0);

// Observar cambio de pais
watch(() => props.paisId, async (newVal, oldVal) => {
  if (newVal && newVal !== oldVal) {
    await fetchFiscalFields(newVal);
  }
});

// Sincronizar datos iniciales (cuando el componente padre los carga)
watch(() => props.initialData, (newVal) => {
  if (newVal) {
    form.value = { ...newVal };
  }
}, { deep: true });

watch(() => props.initialFields, (newVal) => {
  if (newVal) {
    fiscalFields.value = newVal;
  }
});

const fetchFiscalFields = async (paisId) => {
  loadingFields.value = true;
  errors.value = {};
  try {
    const res = await axios.get(`/api/paises/${paisId}/fiscal-fields`);
    fiscalFields.value = res.data.fiscal_fields || [];
    // Inicializar el formulario si cambió de país
    fiscalFields.value.forEach(field => {
      if (form.value[field.key] === undefined) {
        form.value[field.key] = '';
      }
    });
  } catch (err) {
    console.error('Error cargando campos fiscales:', err);
  } finally {
    loadingFields.value = false;
  }
};

const guardarDatosFiscales = async () => {
  saving.value = true;
  errors.value = {};
  successMsg.value = '';
  generalError.value = '';

  try {
    await axios.post('/api/negocio/datos-fiscales', {
      pais_id: props.paisId,
      datos_fiscales: form.value
    });
    successMsg.value = 'Datos fiscales guardados con éxito.';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (err) {
    if (err.response?.status === 422) {
      // Mapear los errores que vienen como "datos_fiscales.clave" a "clave"
      const apiErrors = err.response.data.errors;
      const mappedErrors = {};
      Object.keys(apiErrors).forEach(key => {
        if (key.startsWith('datos_fiscales.')) {
          const fieldKey = key.replace('datos_fiscales.', '');
          mappedErrors[fieldKey] = apiErrors[key];
        } else {
          generalError.value = apiErrors[key][0];
        }
      });
      errors.value = mappedErrors;
    } else {
      generalError.value = err.response?.data?.message || 'Error al guardar los datos fiscales.';
    }
  } finally {
    saving.value = false;
  }
};
</script>
