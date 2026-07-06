<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-black/20">
        <h3 class="text-xl font-bold text-white">{{ $t('agenda.modal.titulo') }} - {{ theme.title }}</h3>
        <button @click="close" class="text-text-muted hover:text-white transition-colors">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto flex-1 space-y-5">
        
        <!-- Cliente -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.cliente_telefono') }}</label>
          <input 
            v-model="form.cliente.telefono" 
            type="text" 
            placeholder="+34 600 000 000"
            :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent', theme.ring]"
          />
          <p class="text-xs text-text-muted mt-1">{{ $t('agenda.modal.aviso_nuevo_cliente') }}</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.nombre') }}</label>
            <input 
              v-model="form.cliente.nombre" 
              type="text" 
              placeholder="Ej. Ana"
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent', theme.ring]"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.apellido') }}</label>
            <input 
              v-model="form.cliente.apellido" 
              type="text" 
              placeholder="Ej. Gómez"
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent', theme.ring]"
            />
          </div>
        </div>

        <!-- Servicio -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.servicio') }}</label>
          <CustomSelect 
            v-model="form.servicio_id" 
            :options="servicioOptions" 
            :placeholder="$t('agenda.modal.selecciona_servicio')"
          />
        </div>

        <!-- Campos Especializados por Tipo -->
        <div v-if="type === 'medical'" class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-teal-500/5 border border-teal-500/20">
          <div>
            <label class="block text-sm font-medium text-teal-400 mb-2">{{ $t('agenda.modal.especialidad') }}</label>
            <input 
              v-model="form.extra.especialidad" 
              type="text" 
              placeholder="Ej. Cardiología"
              class="w-full bg-black/20 border border-teal-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-teal-400 mb-2">{{ $t('agenda.modal.num_historia') }}</label>
            <input 
              v-model="form.extra.historia_clinica" 
              type="text" 
              placeholder="Ej. HC-94832"
              class="w-full bg-black/20 border border-teal-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-teal-500/50 focus:border-transparent"
            />
          </div>
        </div>

        <div v-if="type === 'dental'" class="grid grid-cols-2 gap-4 p-4 rounded-xl bg-sky-500/5 border border-sky-500/20">
          <div>
            <label class="block text-sm font-medium text-sky-400 mb-2">{{ $t('agenda.modal.tipo_tratamiento') }}</label>
            <input 
              v-model="form.extra.tipo_tratamiento" 
              type="text" 
              placeholder="Ej. Ortodoncia"
              class="w-full bg-black/20 border border-sky-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-sky-400 mb-2">{{ $t('agenda.modal.odontologo') }}</label>
            <input 
              v-model="form.extra.odontologo" 
              type="text" 
              placeholder="Ej. Dr. Martínez"
              class="w-full bg-black/20 border border-sky-500/30 rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-sky-500/50 focus:border-transparent"
            />
          </div>
        </div>

        <!-- Fecha y Hora -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.fecha') }}</label>
            <input 
              v-model="form.fecha" 
              type="date" 
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent [color-scheme:dark]', theme.ring]"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.hora') }}</label>
            <input 
              v-model="form.hora" 
              type="time" 
              :class="['w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:border-transparent [color-scheme:dark]', theme.ring]"
            />
          </div>
        </div>

        <!-- Notas -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal.notas') }}</label>
          <textarea 
            v-model="form.notas_profesional" 
            rows="2"
            :placeholder="$t('agenda.modal.notas_placeholder')"
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
          {{ $t('acciones.cancelar') }}
        </button>
        <button 
          @click="guardarCita" 
          :disabled="loading"
          :class="['text-white px-6 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2', theme.bg, theme.shadow]">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          {{ $t('agenda.modal.agendar') }}
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
  type: {
    type: String,
    default: 'general'
  }
});

const emit = defineEmits(['close', 'saved']);

const servicios = ref([]);
const loading = ref(false);
const errorMsg = ref('');

const theme = computed(() => {
  const themes = {
    general: {
      title: 'Citas Pro',
      bg: 'bg-primary hover:bg-primary-hover',
      ring: 'focus:ring-primary',
      shadow: 'shadow-[0_0_15px_rgba(99,102,241,0.25)]',
      icon: '✂️'
    },
    medical: {
      title: 'Clínica Médica',
      bg: 'bg-teal-600 hover:bg-teal-700',
      ring: 'focus:ring-teal-500',
      shadow: 'shadow-[0_0_15px_rgba(20,184,166,0.25)]',
      icon: '🩺'
    },
    dental: {
      title: 'Clínica Dental',
      bg: 'bg-sky-600 hover:bg-sky-700',
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
  cliente: {
    telefono: '',
    nombre: '',
    apellido: ''
  },
  servicio_id: '',
  fecha: new Date().toISOString().split('T')[0],
  hora: '10:00',
  notas_profesional: '',
  extra: {
    especialidad: '',
    historia_clinica: '',
    tipo_tratamiento: '',
    odontologo: ''
  }
});

// Reset form and reload services when modal opens
watch(() => props.show, (newVal) => {
  if (newVal) {
    errorMsg.value = '';
    form.value.cliente = { telefono: '', nombre: '', apellido: '' };
    form.value.servicio_id = '';
    form.value.fecha = new Date().toISOString().split('T')[0];
    form.value.hora = '10:00';
    form.value.notas_profesional = '';
    form.value.extra = {
      especialidad: '',
      historia_clinica: '',
      tipo_tratamiento: '',
      odontologo: ''
    };
    cargarServicios();
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

const guardarCita = async () => {
  errorMsg.value = '';
  
  if (!form.value.cliente.telefono || !form.value.cliente.nombre) {
    errorMsg.value = t('agenda.modal.error_campos');
    return;
  }
  if (!form.value.servicio_id) {
    errorMsg.value = t('agenda.modal.error_servicio');
    return;
  }

  loading.value = true;

  // Serializar campos extras en las notas del profesional
  let notasFinales = form.value.notas_profesional || '';
  if (props.type === 'medical' && (form.value.extra.especialidad || form.value.extra.historia_clinica)) {
    const header = '\n=== INFO CLÍNICA MÉDICA ===\n';
    const esp = form.value.extra.especialidad ? `Especialidad: ${form.value.extra.especialidad}\n` : '';
    const hc = form.value.extra.historia_clinica ? `Nº Historia Clínica: ${form.value.extra.historia_clinica}\n` : '';
    notasFinales += header + esp + hc;
  } else if (props.type === 'dental' && (form.value.extra.tipo_tratamiento || form.value.extra.odontologo)) {
    const header = '\n=== INFO CLÍNICA DENTAL ===\n';
    const trat = form.value.extra.tipo_tratamiento ? `Tratamiento: ${form.value.extra.tipo_tratamiento}\n` : '';
    const odt = form.value.extra.odontologo ? `Odontólogo: ${form.value.extra.odontologo}\n` : '';
    notasFinales += header + trat + odt;
  }

  const payload = {
    ...form.value,
    notas_profesional: notasFinales,
    type: props.type
  };

  try {
    const endpoint = `/api/citas/${props.type}`;
    await axios.post(endpoint, payload);
    emit('saved');
    close();
  } catch (error) {
    errorMsg.value = error.response?.data?.message || t('agenda.modal.error_guardar');
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
