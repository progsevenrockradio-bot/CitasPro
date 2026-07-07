<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-black/20">
        <h3 class="text-xl font-bold text-white">{{ $t('agenda.modal_editar.titulo') || 'Editar Cita' }} - {{ theme.title }}</h3>
        <button @click="close" class="text-text-muted hover:text-white transition-colors">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div v-if="loadingData" class="p-12 flex justify-center items-center">
        <Loader2 :class="['w-8 h-8 animate-spin', theme.text]" />
      </div>
      <div v-else class="p-6 overflow-y-auto flex-1 space-y-5">
        
        <!-- Cliente (Sólo lectura en edición) -->
        <div class="bg-black/20 p-4 rounded-xl border border-border">
          <p class="text-sm font-medium text-text-muted mb-1">{{ $t('agenda.modal.cliente') || 'Cliente' }}</p>
          <p class="text-white font-bold">{{ form.cliente_nombre }}</p>
          <p class="text-xs text-text-muted">{{ form.cliente_telefono }}</p>
        </div>

        <!-- Servicio -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.servicio') || 'Servicio' }}</label>
          <CustomSelect 
            v-model="form.servicio_id" 
            :options="servicioOptions" 
            :placeholder="$t('agenda.modal.selecciona_servicio') || 'Selecciona un servicio'"
            disabled
          />
          <p class="text-xs text-text-muted mt-1">El servicio no se puede cambiar. Cancela y crea una cita nueva si es necesario.</p>
        </div>

        <!-- Fecha y Hora -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.fecha') || 'Fecha' }}</label>
            <input 
              v-model="form.fecha" 
              type="date" 
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent [color-scheme:dark]', theme.ring]"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.hora') || 'Hora' }}</label>
            <input 
              v-model="form.hora" 
              type="time" 
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent [color-scheme:dark]', theme.ring]"
            />
          </div>
        </div>

        <!-- Estado -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.estado') || 'Estado' }}</label>
          <select 
            v-model="form.estado" 
            :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent', theme.ring]"
          >
            <option value="pendiente">Pendiente</option>
            <option value="confirmada">Confirmada</option>
            <option value="completada">Completada</option>
            <option value="cancelada">Cancelada</option>
            <option value="no_asistio">No Asistió</option>
          </select>
        </div>

        <!-- Notas -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.notas') || 'Notas Privadas' }}</label>
          <textarea 
            v-model="form.notas_profesional" 
            rows="3"
            :placeholder="$t('agenda.modal.notas_placeholder') || 'Añade notas visibles solo para ti...'"
            :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent', theme.ring]"
          ></textarea>
        </div>

        <!-- Error Message -->
        <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm">
          {{ errorMsg }}
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-border bg-black/20 flex justify-end gap-3">
        <button 
          @click="close" 
          class="px-5 py-2.5 rounded-xl font-medium text-text-muted hover:text-white hover:bg-white/5 transition-colors">
          {{ $t('acciones.cancelar') || 'Cancelar' }}
        </button>
        <button 
          @click="guardarCita" 
          :disabled="loading || loadingData"
          :class="['text-white px-6 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2', theme.bg, theme.shadow]">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          {{ $t('acciones.guardar') || 'Guardar Cambios' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { X, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import CustomSelect from '../../Components/CustomSelect.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  show: Boolean,
  cita: {
    type: Object,
    default: null
  },
  type: {
    type: String,
    default: 'general'
  }
});

const emit = defineEmits(['close', 'saved']);

const servicios = ref([]);
const loading = ref(false);
const loadingData = ref(false);
const errorMsg = ref('');

const theme = computed(() => {
  const themes = {
    general: {
      title: 'Citas Pro',
      bg: 'bg-primary hover:bg-primary-hover',
      text: 'text-primary',
      ring: 'focus:ring-primary',
      shadow: 'shadow-[0_0_15px_rgba(99,102,241,0.25)]',
      icon: '✂️'
    },
    medical: {
      title: 'Clínica Médica',
      bg: 'bg-teal-600 hover:bg-teal-700',
      text: 'text-teal-500',
      ring: 'focus:ring-teal-500',
      shadow: 'shadow-[0_0_15px_rgba(20,184,166,0.25)]',
      icon: '🩺'
    },
    dental: {
      title: 'Clínica Dental',
      bg: 'bg-sky-600 hover:bg-sky-700',
      text: 'text-sky-500',
      ring: 'focus:ring-sky-500',
      shadow: 'shadow-[0_0_15px_rgba(14,165,233,0.25)]',
      icon: '🦷'
    }
  };
  return themes[props.type] || themes.general;
});

const servicioOptions = computed(() => {
  return servicios.value.map(s => ({
    value: s.id,
    label: `${s.nombre} (${s.precio}€ - ${s.duracion_min} min)`,
    icon: theme.value.icon
  }));
});

const form = ref({
  id: null,
  cliente_nombre: '',
  cliente_telefono: '',
  servicio_id: '',
  fecha: '',
  hora: '',
  estado: '',
  notas_profesional: ''
});

// Load data when modal opens or cita changes
watch(() => props.show, (newVal) => {
  if (newVal && props.cita) {
    errorMsg.value = '';
    cargarServicios().then(() => {
      cargarCita();
    });
  }
});

const cargarServicios = async () => {
  try {
    const res = await axios.get('/api/servicios', {
      params: { type: props.type }
    });
    servicios.value = res.data.servicios || [];
  } catch (error) {
    console.error("Error al cargar servicios", error);
  }
};

const cargarCita = () => {
  const c = props.cita;
  form.value = {
    id: c.id,
    cliente_nombre: c.cliente?.nombre || c.cliente?.nombre_completo || 'N/A',
    cliente_telefono: c.cliente?.telefono || c.cliente?.tel || '',
    servicio_id: c.servicio?.id || (servicios.value.find(s => s.nombre === c.servicio?.nombre)?.id) || '',
    fecha: c.fecha,
    hora: c.hora_inicio || c.hora,
    estado: c.estado,
    notas_profesional: c.notas_profesional || ''
  };
  
  // Si en el frontend usamos una estructura diferente a veces
  if(c.notas) form.value.notas_profesional = c.notas;
};

const guardarCita = async () => {
  errorMsg.value = '';
  loading.value = true;

  const payload = {
    fecha: form.value.fecha,
    hora: form.value.hora,
    estado: form.value.estado,
    notas_profesional: form.value.notas_profesional
  };

  try {
    const endpoint = `/api/citas/${props.type}/${form.value.id}`;
    await axios.patch(endpoint, payload);
    emit('saved');
    close();
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Error al guardar los cambios de la cita.';
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
