<template>
  <div class="space-y-6">
    <!-- Header Area -->
    <div class="flex justify-between items-center bg-bg-card border border-border p-6 rounded-2xl">
      <div>
        <h2 :class="['text-2xl font-bold mb-1', theme.text]">{{ $t('agenda.themes.' + type + '.title') }}</h2>
        <p class="text-text-muted text-sm">{{ $t('agenda.themes.' + type + '.subtitle') }}</p>
      </div>
      <button 
        @click="showNuevaCita = true"
        :class="['text-white px-5 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2', theme.bg, theme.shadow]">
        <Plus class="w-5 h-5" /> {{ $t('agenda.nueva_cita') }}
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 :class="['w-6 h-6 animate-spin', theme.text]" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">{{ $t('agenda.citas_hoy') }}</p>
        <p class="text-3xl font-bold">{{ metrics?.citas_hoy || 0 }}</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 :class="['w-6 h-6 animate-spin', theme.text]" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">{{ $t('agenda.citas_mes') }}</p>
        <p class="text-3xl font-bold">{{ metrics?.citas?.total || 0 }}</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 :class="['w-6 h-6 animate-spin', theme.text]" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">{{ $t('agenda.canceladas') }}</p>
        <p class="text-3xl font-bold text-red-400">{{ metrics?.citas?.total_cancelaciones || 0 }}</p>
      </div>
    </div>

    <!-- Calendar Area -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[500px]">
      <h3 class="text-xl font-bold mb-6">{{ $t('agenda.proximos_7_dias') }}</h3>
      
      <div v-if="loadingAgenda" class="flex flex-col items-center justify-center h-64">
        <Loader2 :class="['w-12 h-12 animate-spin mb-4', theme.text]" />
        <p class="text-text-muted">{{ $t('agenda.cargando') }}</p>
      </div>
      
      <div v-else-if="agenda.length === 0" class="flex flex-col items-center justify-center h-64 text-text-muted">
        <CalendarIcon class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p class="mb-4">{{ $t('agenda.sin_citas') }}</p>
        <button 
          @click="showNuevaCita = true"
          :class="['text-white px-6 py-2 rounded-xl font-medium transition-all shadow-lg', theme.bg]">
          {{ $t('agenda.agendar_primera') }}
        </button>
      </div>

      <div v-else class="space-y-8">
        <div v-for="dia in agenda" :key="dia.fecha">
          <h4 :class="['font-bold text-lg border-b border-border/50 pb-2 mb-4', theme.text]">{{ dia.dia_label }}</h4>
          <div v-if="dia.citas.length === 0" class="text-text-muted text-sm italic">
            {{ $t('agenda.dia_sin_citas') }}
          </div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="cita in dia.citas" :key="cita.id" 
                 :class="['bg-black/20 border border-border rounded-xl p-4 flex gap-4 transition-colors relative group', theme.hoverBorder]">
              <div class="flex flex-col items-center justify-center bg-bg-card rounded-lg px-3 py-2 min-w-[80px]">
                <span class="text-lg font-bold text-white">{{ cita.hora_inicio }}</span>
                <span class="text-xs text-text-muted">{{ cita.duracion_min }} min</span>
              </div>
              <div class="flex-1">
                <p class="font-bold text-white">{{ cita.cliente?.nombre }}</p>
                <p class="text-sm text-text-muted">{{ cita.servicio?.nombre }}</p>
                
                <!-- Campos extra parseados de las notas -->
                <div v-if="getExtraInfo(cita.notas)" class="mt-2 text-xs p-2 rounded bg-black/30 border border-border/30 text-text-muted whitespace-pre-line">
                  {{ getExtraInfo(cita.notas) }}
                </div>

                <div class="mt-2 flex items-center gap-2">
                  <span :class="{
                    'px-2 py-0.5 rounded text-xs font-medium': true,
                    'bg-green-500/10 text-green-400': cita.estado === 'completada',
                    'bg-blue-500/10 text-blue-400': cita.estado === 'confirmada',
                    'bg-yellow-500/10 text-yellow-400': cita.estado === 'pendiente',
                    'bg-red-500/10 text-red-400': cita.estado === 'cancelada',
                  }">{{ cita.estado.toUpperCase() }}</span>
                  <span class="text-sm text-text-muted ml-auto font-medium">{{ cita.precio }} {{ cita.moneda || '€' }}</span>
                </div>
              </div>
              <!-- Hover Actions -->
              <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity flex gap-2">
                <button 
                  v-if="cita.estado === 'pendiente'"
                  @click="cambiarEstado(cita.id, 'confirmada')"
                  class="bg-blue-500 hover:bg-blue-600 text-white p-1.5 rounded-lg shadow"
                  :title="$t('agenda.confirmar_cita')">
                  <Check class="w-4 h-4" />
                </button>
                <button 
                  v-if="cita.estado === 'confirmada'"
                  @click="cambiarEstado(cita.id, 'completada')"
                  class="bg-green-500 hover:bg-green-600 text-white p-1.5 rounded-lg shadow"
                  :title="$t('agenda.marcar_completada')">
                  <CheckCircle class="w-4 h-4" />
                </button>
                <button 
                  v-if="cita.estado !== 'cancelada' && cita.estado !== 'completada'"
                  @click="cancelarCita(cita.id)"
                  class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg shadow"
                  :title="$t('agenda.cancelar_cita')">
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal Confirmación Cancelación -->
    <ConfirmModal 
      v-model:show="showCancelModal"
      :title="$t('agenda.cancelar_cita')"
      :message="$t('agenda.confirmar_cancelar')"
      type="danger"
      :confirm-text="$t('acciones.si_eliminar')"
      :cancel-text="$t('acciones.cancelar')"
      @confirm="executeCancelarCita"
    />

    <!-- Modal Nueva Cita -->
    <NuevaCitaModal 
      :show="showNuevaCita" 
      :type="type"
      @close="showNuevaCita = false" 
      @saved="onCitaSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue';
import { Calendar as CalendarIcon, Loader2, X, Check, CheckCircle, Plus } from 'lucide-vue-next';
import axios from 'axios';
import NuevaCitaModal from '../Dashboard/Modals/NuevaCitaModal.vue';
import ConfirmModal from '../Components/ConfirmModal.vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  type: {
    type: String,
    default: 'general'
  }
});

const theme = computed(() => {
  const themes = {
    general: {
      title: 'Mi Agenda - Citas Pro',
      subtitle: 'Gestiona tus citas generales y reservas abiertas.',
      text: 'text-primary',
      bg: 'bg-primary hover:bg-primary-hover',
      hoverBorder: 'hover:border-primary/50',
      shadow: 'shadow-[0_0_15px_rgba(99,102,241,0.25)]'
    },
    medical: {
      title: 'Clínica Médica',
      subtitle: 'Agenda clínica con especialidad e historia médica.',
      text: 'text-teal-400',
      bg: 'bg-teal-600 hover:bg-teal-700',
      hoverBorder: 'hover:border-teal-500/50',
      shadow: 'shadow-[0_0_15px_rgba(20,184,166,0.25)]'
    },
    dental: {
      title: 'Clínica Dental',
      subtitle: 'Agenda odontológica con tratamientos específicos.',
      text: 'text-sky-400',
      bg: 'bg-sky-600 hover:bg-sky-700',
      hoverBorder: 'hover:border-sky-500/50',
      shadow: 'shadow-[0_0_15px_rgba(14,165,233,0.25)]'
    }
  };
  return themes[props.type] || themes.general;
});

const metrics = ref(null);
const agenda = ref([]);
const loadingMetrics = ref(true);
const loadingAgenda = ref(true);
const showNuevaCita = ref(false);
const showCancelModal = ref(false);
const citaToCancel = ref(null);

const cambiarEstado = async (id, estado) => {
  try {
    await axios.patch(`/api/dashboard/citas/${id}/estado`, { estado });
    fetchAgenda();
  } catch (error) {
    console.error(`Error cambiando estado a ${estado}:`, error);
    alert(t('agenda.error_estado'));
  }
};

const fetchMetrics = async () => {
  loadingMetrics.value = true;
  try {
    const response = await axios.get('/api/dashboard/metricas', {
      params: { type: props.type }
    });
    metrics.value = response.data;
  } catch (error) {
    console.error("Error cargando métricas", error);
  } finally {
    loadingMetrics.value = false;
  }
};

const fetchAgenda = async () => {
  loadingAgenda.value = true;
  try {
    const response = await axios.get('/api/dashboard/agenda', {
      params: { type: props.type }
    });
    agenda.value = response.data.agenda || [];
    if (metrics.value) {
      metrics.value.citas_hoy = response.data.resumen_hoy?.total || 0;
    }
  } catch (error) {
    console.error("Error cargando agenda", error);
  } finally {
    loadingAgenda.value = false;
  }
};

const cancelarCita = (id) => {
  citaToCancel.value = id;
  showCancelModal.value = true;
};

const executeCancelarCita = async () => {
  if (!citaToCancel.value) return;
  const id = citaToCancel.value;
  
  try {
    await axios.delete(`/api/citas/${props.type}/${id}`);
    fetchAgenda();
    fetchMetrics();
    citaToCancel.value = null;
  } catch (error) {
    console.error("Error al cancelar cita", error);
    // TODO: Considerar usar un componente de Toast o Notificación en lugar de alert
    alert(t('agenda.error_cancelar'));
  }
};

const getExtraInfo = (notas) => {
  if (!notas) return null;
  const matchMedical = notas.match(/=== INFO CLÍNICA MÉDICA ===\n([\s\S]*)/);
  const matchDental = notas.match(/=== INFO CLÍNICA DENTAL ===\n([\s\S]*)/);
  if (props.type === 'medical' && matchMedical) {
    return matchMedical[1].trim();
  }
  if (props.type === 'dental' && matchDental) {
    return matchDental[1].trim();
  }
  return null;
};

const onCitaSaved = () => {
  fetchAgenda();
  fetchMetrics();
};

onMounted(() => {
  fetchMetrics();
  fetchAgenda();
});

watch(() => props.type, () => {
  fetchMetrics();
  fetchAgenda();
});
</script>
