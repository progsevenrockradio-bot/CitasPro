<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">Mi Agenda</h2>
      <button 
        @click="showNuevaCita = true"
        class="bg-primary hover:bg-primary-hover px-4 py-2 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)]">
        + Nueva Cita
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">Citas Hoy</p>
        <p class="text-3xl font-bold">{{ metrics?.citas_hoy || 0 }}</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">Citas del Mes</p>
        <p class="text-3xl font-bold">{{ metrics?.citas?.total || 0 }}</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loadingMetrics" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-muted text-sm font-medium mb-1">Canceladas</p>
        <p class="text-3xl font-bold text-red-400">{{ metrics?.citas?.total_cancelaciones || 0 }}</p>
      </div>
    </div>

    <!-- Calendar Placeholders -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[500px]">
      <h3 class="text-xl font-bold mb-6">Próximos 7 días</h3>
      
      <div v-if="loadingAgenda" class="flex flex-col items-center justify-center h-64 text-primary">
        <Loader2 class="w-12 h-12 animate-spin mb-4" />
        <p>Cargando agenda...</p>
      </div>
      
      <div v-else-if="agenda.length === 0" class="flex flex-col items-center justify-center h-64 text-text-muted">
        <Calendar class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p class="mb-4">No tienes citas programadas.</p>
        <button 
          @click="showNuevaCita = true"
          class="bg-primary hover:bg-primary-hover text-white px-6 py-2 rounded-xl font-medium transition-all shadow-lg">
          Agendar mi primera cita
        </button>
      </div>

      <div v-else class="space-y-8">
        <div v-for="dia in agenda" :key="dia.fecha">
          <h4 class="font-bold text-lg text-primary border-b border-border/50 pb-2 mb-4">{{ dia.dia_label }}</h4>
          <div v-if="dia.citas.length === 0" class="text-text-muted text-sm italic">
            Sin citas
          </div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="cita in dia.citas" :key="cita.id" 
                 class="bg-black/20 border border-border rounded-xl p-4 flex gap-4 hover:border-primary/50 transition-colors relative group">
              <div class="flex flex-col items-center justify-center bg-bg-card rounded-lg px-3 py-2 min-w-[80px]">
                <span class="text-lg font-bold text-white">{{ cita.hora_inicio }}</span>
                <span class="text-xs text-text-muted">{{ cita.duracion_min }} min</span>
              </div>
              <div class="flex-1">
                <p class="font-bold text-white">{{ cita.cliente?.nombre }} {{ cita.cliente?.apellido || '' }}</p>
                <p class="text-sm text-text-muted">{{ cita.servicio?.nombre }}</p>
                <div class="mt-2 flex items-center gap-2">
                  <span :class="{
                    'px-2 py-0.5 rounded text-xs font-medium': true,
                    'bg-green-500/10 text-green-400': cita.estado === 'completada',
                    'bg-blue-500/10 text-blue-400': cita.estado === 'confirmada',
                    'bg-yellow-500/10 text-yellow-400': cita.estado === 'pendiente',
                    'bg-red-500/10 text-red-400': cita.estado === 'cancelada',
                  }">{{ cita.estado.toUpperCase() }}</span>
                  <span class="text-sm text-text-muted ml-auto font-medium">{{ cita.precio_total }} {{ cita.moneda || '€' }}</span>
                </div>
              </div>
              <!-- Hover Actions -->
              <div class="absolute top-4 right-4 opacity-0 group-hover:opacity-100 transition-opacity">
                <button 
                  v-if="cita.estado !== 'cancelada'"
                  @click="cancelarCita(cita.id)"
                  class="bg-red-500 hover:bg-red-600 text-white p-1.5 rounded-lg shadow"
                  title="Cancelar Cita">
                  <X class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <!-- Modal -->
    <NuevaCitaModal 
      :show="showNuevaCita" 
      @close="showNuevaCita = false" 
      @saved="onCitaSaved"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Calendar, Loader2, X } from 'lucide-vue-next';
import axios from 'axios';
import NuevaCitaModal from './Modals/NuevaCitaModal.vue';

const metrics = ref(null);
const agenda = ref([]);
const loadingMetrics = ref(true);
const loadingAgenda = ref(true);
const showNuevaCita = ref(false);

const fetchMetrics = async () => {
  try {
    const response = await axios.get('/api/dashboard/metricas');
    metrics.value = response.data;
  } catch (error) {
    console.error("Error cargando métricas", error);
  } finally {
    loadingMetrics.value = false;
  }
};

const fetchAgenda = async () => {
  try {
    const response = await axios.get('/api/dashboard/agenda');
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

const cancelarCita = async (id) => {
  if (!confirm('¿Estás seguro de cancelar esta cita?')) return;
  
  try {
    // Intentamos parchear el estado de la cita
    await axios.patch(`/api/dashboard/citas/${id}/estado`, { estado: 'cancelada' })
      .catch(async () => {
         // Si falla (porque a veces se usa la API principal), probamos con la API de citas
         await axios.delete(`/api/citas/${id}`);
      });
      
    // Recargar datos
    fetchAgenda();
    fetchMetrics();
  } catch (error) {
    console.error("Error al cancelar cita", error);
    alert('No se pudo cancelar la cita.');
  }
};

const onCitaSaved = () => {
  fetchAgenda();
  fetchMetrics();
};

onMounted(() => {
  fetchMetrics();
  fetchAgenda();
});
</script>
