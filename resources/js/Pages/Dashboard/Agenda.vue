<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">Mi Agenda</h2>
      <button class="bg-primary hover:bg-primary-hover px-4 py-2 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)]">
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
        <p>No tienes citas programadas.</p>
      </div>

      <div v-else class="space-y-8">
        <div v-for="dia in agenda" :key="dia.fecha">
          <h4 class="font-bold text-lg text-primary border-b border-border/50 pb-2 mb-4">{{ dia.dia_label }}</h4>
          <div v-if="dia.citas.length === 0" class="text-text-muted text-sm italic">
            Sin citas
          </div>
          <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div v-for="cita in dia.citas" :key="cita.id" 
                 class="bg-black/20 border border-border rounded-xl p-4 flex gap-4 hover:border-primary/50 transition-colors">
              <div class="flex flex-col items-center justify-center bg-bg-card rounded-lg px-3 py-2 min-w-[80px]">
                <span class="text-lg font-bold text-white">{{ cita.hora_inicio }}</span>
                <span class="text-xs text-text-muted">{{ cita.duracion_min }} min</span>
              </div>
              <div class="flex-1">
                <p class="font-bold text-white">{{ cita.cliente?.nombre }}</p>
                <p class="text-sm text-text-muted">{{ cita.servicio?.nombre }}</p>
                <div class="mt-2 flex items-center gap-2">
                  <span :class="{
                    'px-2 py-0.5 rounded text-xs font-medium': true,
                    'bg-green-500/10 text-green-400': cita.estado === 'completada',
                    'bg-blue-500/10 text-blue-400': cita.estado === 'confirmada',
                    'bg-yellow-500/10 text-yellow-400': cita.estado === 'pendiente',
                  }">{{ cita.estado.toUpperCase() }}</span>
                  <span class="text-sm text-text-muted ml-auto font-medium">{{ cita.precio }} {{ cita.moneda }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Calendar, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const metrics = ref(null);
const agenda = ref([]);
const loadingMetrics = ref(true);
const loadingAgenda = ref(true);

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

onMounted(() => {
  fetchMetrics();
  fetchAgenda();
});
</script>
