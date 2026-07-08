<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center bg-bg-card border border-border p-6 rounded-2xl">
      <div>
        <h2 class="text-2xl font-bold mb-1 text-primary">Testimonios y Reseñas</h2>
        <p class="text-text-secondary text-sm">Modera las opiniones y calificaciones recibidas por tus clientes.</p>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loading" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-secondary text-sm font-medium mb-1">Calificación Promedio</p>
        <div class="flex items-center gap-2">
          <p class="text-3xl font-bold">{{ stats.promedio || 0 }}</p>
          <div class="flex items-center text-yellow-400">
            <Star v-for="i in 5" :key="i" 
                  :class="['w-5 h-5', i <= Math.round(stats.promedio) ? 'fill-yellow-400 text-yellow-400' : 'text-border-sutil']" />
          </div>
        </div>
      </div>
      
      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loading" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-secondary text-sm font-medium mb-1">Total Opiniones</p>
        <p class="text-3xl font-bold">{{ stats.total || 0 }}</p>
      </div>

      <div class="bg-bg-card border border-border p-6 rounded-2xl relative overflow-hidden">
        <div v-if="loading" class="absolute inset-0 bg-bg-card/80 flex items-center justify-center">
          <Loader2 class="w-6 h-6 animate-spin text-primary" />
        </div>
        <p class="text-text-secondary text-sm font-medium mb-1">Testimonios Ocultos</p>
        <p class="text-3xl font-bold text-accent">{{ stats.ocultas || 0 }}</p>
      </div>
    </div>

    <!-- Testimonios List -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[400px]">
      <div v-if="loading" class="flex flex-col items-center justify-center h-64">
        <Loader2 class="w-12 h-12 animate-spin text-primary mb-4" />
        <p class="text-text-secondary">Cargando testimonios...</p>
      </div>

      <div v-else-if="resenas.length === 0" class="flex flex-col items-center justify-center h-64 text-text-secondary">
        <Star class="w-12 h-12 mx-auto mb-3 opacity-30 text-primary" />
        <p>Aún no has recibido ningún testimonio de tus clientes.</p>
      </div>

      <div v-else class="space-y-6">
        <div class="grid grid-cols-1 gap-4">
          <div v-for="resena in resenas" :key="resena.id" 
               class="bg-black/20 border border-border rounded-2xl p-6 flex flex-col md:flex-row gap-6 relative group transition-colors hover:border-primary/30">
            
            <!-- Cliente Info -->
            <div class="flex items-center md:items-start gap-4 shrink-0 md:w-48">
              <div class="w-12 h-12 rounded-xl overflow-hidden bg-bg border border-border flex items-center justify-center shadow shrink-0">
                <img v-if="resena.cliente?.foto" :src="resena.cliente.foto" class="w-full h-full object-cover" />
                <span v-else class="text-lg font-bold text-primary">{{ resena.cliente?.nombre[0] }}</span>
              </div>
              <div class="min-w-0">
                <p class="font-bold text-white truncate">{{ resena.cliente?.nombre }} {{ resena.cliente?.apellido }}</p>
                <p class="text-xs text-text-secondary mt-0.5">{{ formatFecha(resena.created_at) }}</p>
              </div>
            </div>

            <!-- Contenido -->
            <div class="flex-grow space-y-2">
              <div class="flex items-center gap-1 text-yellow-400">
                <Star v-for="i in 5" :key="i" 
                      :class="['w-4 h-4', i <= resena.calificacion ? 'fill-yellow-400 text-yellow-400' : 'text-border-sutil']" />
              </div>
              <p class="text-white/90 text-sm leading-relaxed whitespace-pre-wrap italic">
                "{{ resena.comentario || 'Sin comentario escrito.' }}"
              </p>
              <div class="flex flex-wrap gap-2 pt-1 text-xs text-text-secondary">
                <span class="bg-white/5 border border-border-sutil px-2 py-0.5 rounded-lg">
                  Atendido por: <strong class="text-white">{{ resena.profesional?.nombre }} {{ resena.profesional?.apellido }}</strong>
                </span>
                <span v-if="resena.cita?.servicio" class="bg-white/5 border border-border-sutil px-2 py-0.5 rounded-lg">
                  Servicio: <strong class="text-white">{{ resena.cita.servicio.nombre }}</strong>
                </span>
              </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-3 self-end md:self-center shrink-0">
              <span :class="[
                'px-2.5 py-1 rounded-xl text-xs font-bold uppercase tracking-wider',
                resena.activo ? 'bg-green-500/10 text-green-400' : 'bg-red-500/10 text-red-400'
              ]">
                {{ resena.activo ? 'Visible' : 'Oculto' }}
              </span>

              <button 
                @click="confirmarToggle(resena)"
                :class="[
                  'px-4 py-2 rounded-xl text-xs font-bold transition-all active:scale-95 cursor-pointer shadow-md',
                  resena.activo 
                    ? 'bg-white/5 border border-border text-text-secondary hover:bg-white/10 hover:text-white' 
                    : 'bg-accent hover:bg-accent-hover text-white shadow-cta-glow'
                ]"
              >
                {{ resena.activo ? 'Ocultar' : 'Aprobar' }}
              </button>
            </div>

          </div>
        </div>

        <!-- Paginación -->
        <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-8 border-t border-border-sutil pt-6">
          <button
            v-for="page in pagination.last_page"
            :key="page"
            @click="fetchResenas(page)"
            :class="[
              'w-10 h-10 rounded-xl text-xs font-black transition-all shadow-md cursor-pointer active:scale-95',
              page === pagination.current_page
                ? 'bg-primary text-white shadow-primary/30'
                : 'bg-white/5 text-text-secondary hover:bg-white/10 hover:text-white border border-border'
            ]"
          >
            {{ page }}
          </button>
        </div>

      </div>
    </div>

    <!-- Modal de Confirmación -->
    <ConfirmModal 
      v-model:show="showConfirmModal"
      :title="confirmTitle"
      :message="confirmMessage"
      :type="confirmType"
      :confirm-text="confirmText"
      :cancel-text="cancelText"
      @confirm="executeToggle"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Star, Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import ConfirmModal from '../../Components/ConfirmModal.vue';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

const resenas = ref([]);
const stats = ref({ promedio: 0, total: 0, ocultas: 0 });
const loading = ref(true);
const pagination = ref(null);

// Modal state
const showConfirmModal = ref(false);
const selectedResena = ref(null);
const confirmTitle = ref('');
const confirmMessage = ref('');
const confirmType = ref('warning');
const confirmText = ref('Aceptar');
const cancelText = ref('Cancelar');

const fetchResenas = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/dashboard/resenas', { params: { page } });
    if (res.data.success) {
      resenas.value = res.data.data.data;
      pagination.value = res.data.data;
      stats.value = res.data.stats;
    }
  } catch (error) {
    console.error('Error cargando reseñas:', error);
  } finally {
    loading.value = false;
  }
};

const formatFecha = (dateStr) => {
  if (!dateStr) return '';
  try {
    return format(parseISO(dateStr), "d 'de' MMMM, yyyy", { locale: es });
  } catch {
    return dateStr;
  }
};

const confirmarToggle = (resena) => {
  selectedResena.value = resena;
  confirmTitle.value = resena.activo ? 'Ocultar Testimonio' : 'Aprobar Testimonio';
  confirmMessage.value = resena.activo 
    ? '¿Estás seguro de que deseas ocultar esta reseña? Ya no aparecerá públicamente en tu directorio de negocios.'
    : '¿Deseas aprobar este testimonio? Será visible en tu perfil público del directorio y se actualizará tu calificación promedio.';
  confirmType.value = resena.activo ? 'danger' : 'warning';
  confirmText.value = resena.activo ? 'Sí, ocultar' : 'Aprobar';
  cancelText.value = 'Cancelar';
  showConfirmModal.value = true;
};

const executeToggle = async () => {
  if (!selectedResena.value) return;
  try {
    const res = await axios.patch(`/api/dashboard/resenas/${selectedResena.value.id}/toggle-activo`);
    if (res.data.success) {
      fetchResenas(pagination.value?.current_page || 1);
    }
  } catch (error) {
    console.error('Error actualizando reseña:', error);
  }
};

onMounted(() => {
  fetchResenas();
});
</script>
