<template>
  <div v-if="fields && fields.length" class="space-y-6 bg-white/5 border border-white/10 rounded-2xl p-6">
    <div>
      <h3 class="text-lg font-bold text-white mb-1 flex items-center gap-2">
        📋 Historia Clínica y Antecedentes
      </h3>
      <p class="text-xs text-gray-400">Por favor, rellene esta información la primera vez para abrir su expediente clínico.</p>
    </div>

    <!-- Agrupamiento por secciones -->
    <div v-for="(sectionFields, sectionName) in groupedFields" :key="sectionName" class="space-y-4 pt-4 border-t border-white/5 first:border-t-0 first:pt-0">
      <h4 v-if="sectionName !== 'default'" class="text-xs font-black uppercase tracking-wider text-indigo-400 mb-3">
        {{ sectionName }}
      </h4>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div 
          v-for="field in sectionFields" 
          :key="field.key" 
          v-show="shouldShowField(field)"
          class="space-y-1.5"
          :class="{ 'md:col-span-2': field.type === 'textarea' || field.type === 'checkbox' || field.type === 'odontograma' || field.type === 'esquema_mamario' || field.key === 'motivo_consulta' }"
        >
          <label class="block text-xs font-semibold text-gray-300">
            {{ field.label }}
            <span v-if="field.required" class="text-red-400 font-bold">*</span>
          </label>

          <!-- Input tipo Text, Date o Number -->
          <input
            v-if="field.type === 'text' || field.type === 'date' || field.type === 'number'"
            :type="field.type"
            v-model="responses[field.key]"
            :placeholder="field.placeholder || ''"
            :required="field.required && shouldShowField(field)"
            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-sans"
          />

          <!-- Textarea -->
          <textarea
            v-else-if="field.type === 'textarea'"
            v-model="responses[field.key]"
            :placeholder="field.placeholder || 'Detalles...'"
            :required="field.required && shouldShowField(field)"
            rows="3"
            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-sans resize-none"
          ></textarea>

          <!-- Radio -->
          <div v-else-if="field.type === 'radio'" class="flex flex-wrap gap-4 pt-1">
            <label 
              v-for="opt in field.options" 
              :key="opt" 
              class="inline-flex items-center gap-2 cursor-pointer text-sm text-gray-300 select-none"
            >
              <input
                type="radio"
                :value="opt"
                v-model="responses[field.key]"
                :name="field.key"
                :required="field.required && shouldShowField(field)"
                class="w-4.5 h-4.5 rounded-full border-white/20 bg-black/40 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
              />
              <span>{{ opt }}</span>
            </label>
          </div>

          <!-- Select -->
          <select
            v-else-if="field.type === 'select'"
            v-model="responses[field.key]"
            :required="field.required && shouldShowField(field)"
            class="w-full bg-black/40 border border-white/10 rounded-xl px-4 py-2.5 text-white text-sm focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all font-sans appearance-none"
          >
            <option value="" disabled selected class="bg-gray-900 text-gray-400">Seleccionar...</option>
            <option v-for="opt in field.options" :key="opt" :value="opt" class="bg-gray-900 text-white">
              {{ opt }}
            </option>
          </select>

          <!-- Checkbox -->
          <div v-else-if="field.type === 'checkbox'" class="space-y-2 pt-1">
            <div class="grid grid-cols-2 gap-2">
              <label 
                v-for="opt in field.options" 
                :key="opt" 
                class="flex items-center gap-2 cursor-pointer text-xs text-gray-300 select-none"
              >
                <input
                  type="checkbox"
                  :value="opt"
                  v-model="responses[field.key]"
                  class="w-4 h-4 rounded border-white/20 bg-black/40 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
                />
                <span>{{ opt }}</span>
              </label>
            </div>
          </div>

          <!-- Odontograma -->
          <div v-else-if="field.type === 'odontograma'" class="w-full">
            <Odontograma v-model="responses[field.key]" />
          </div>

          <!-- Esquema Mamario -->
          <div v-else-if="field.type === 'esquema_mamario'" class="w-full">
            <EsquemaMamario v-model="responses[field.key]" />
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import Odontograma from './Odontograma.vue';
import EsquemaMamario from './EsquemaMamario.vue';

const props = defineProps({
  fields: {
    type: Array,
    required: true,
    default: () => []
  },
  modelValue: {
    type: Object,
    default: () => ({})
  }
});

const emit = defineEmits(['update:modelValue']);

const responses = ref({ ...props.modelValue });

// Agrupar campos por secciones
const groupedFields = computed(() => {
  const groups = {};
  props.fields.forEach(field => {
    const sec = field.section || 'default';
    if (!groups[sec]) groups[sec] = [];
    groups[sec].push(field);
  });
  return groups;
});

// Inicializar el objeto respuestas para evitar reactividad rota
const initializeResponses = () => {
  props.fields.forEach(field => {
    if (responses.value[field.key] === undefined) {
      if (field.type === 'checkbox') {
        responses.value[field.key] = [];
      } else {
        responses.value[field.key] = '';
      }
    }
  });
};

onMounted(() => {
  initializeResponses();
});

// Evaluar lógica condicional del campo (depends_on)
const shouldShowField = (field) => {
  if (!field.depends_on) return true;
  const dep = field.depends_on;
  const parentValue = responses.value[dep.field];
  return parentValue === dep.value;
};

// Emitir cambios
watch(responses, (val) => {
  emit('update:modelValue', val);
}, { deep: true });

// Observar cambio en prop iniciales
watch(() => props.modelValue, (newVal) => {
  if (JSON.stringify(newVal) !== JSON.stringify(responses.value)) {
    responses.value = { ...newVal };
  }
}, { deep: true });
</script>
