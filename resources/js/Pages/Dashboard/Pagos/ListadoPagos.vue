<template>
  <div class="space-y-6 max-w-6xl">
    <div>
      <h2 class="text-2xl font-bold">Historial de Pagos</h2>
      <p class="text-text-muted text-sm mt-1">Consulta los cobros recibidos y transacciones realizadas en tu negocio.</p>
    </div>

    <!-- Carga -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-primary">
      <Loader2 class="w-10 h-10 animate-spin mb-4" />
      <p>Cargando historial de pagos...</p>
    </div>

    <!-- Sin Resultados -->
    <div v-else-if="pagos.length === 0" class="bg-bg-card border border-border rounded-2xl p-12 text-center text-text-muted">
      <div class="text-4xl mb-4">💳</div>
      <p class="font-bold text-white text-lg">No hay transacciones registradas</p>
      <p class="text-sm mt-1">Los pagos online o manuales que realicen tus clientes aparecerán en este listado.</p>
    </div>

    <!-- Tabla de Pagos -->
    <div v-else class="space-y-4">
      <div class="bg-bg-card border border-border rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-border bg-black/10 text-xs font-bold text-text-muted uppercase tracking-wider">
                <th class="p-4">Ref / ID</th>
                <th class="p-4">Cliente</th>
                <th class="p-4">Servicio</th>
                <th class="p-4">Fecha Cita</th>
                <th class="p-4">Monto</th>
                <th class="p-4">Método</th>
                <th class="p-4">Estado</th>
                <th class="p-4">Fecha Pago</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border/50 text-sm">
              <tr 
                v-for="pago in pagos" 
                :key="pago.id" 
                class="hover:bg-white/5 transition-colors"
              >
                <!-- Referencia -->
                <td class="p-4 font-mono text-xs text-white">
                  #{{ pago.id }}
                </td>
                
                <!-- Cliente -->
                <td class="p-4">
                  <div class="font-semibold text-white">
                    {{ pago.cliente ? `${pago.cliente.nombre} ${pago.cliente.apellido || ''}` : 'Cliente Desconocido' }}
                  </div>
                  <div class="text-xs text-text-muted mt-0.5">
                    {{ pago.cliente?.telefono }}
                  </div>
                </td>

                <!-- Servicio -->
                <td class="p-4 text-white font-medium">
                  {{ pago.cita?.servicio?.nombre || 'Consulta / Tratamiento' }}
                </td>

                <!-- Fecha Cita -->
                <td class="p-4 text-text-muted">
                  <div v-if="pago.cita">
                    {{ formatFecha(pago.cita.fecha) }}
                    <span class="text-xs ml-1 bg-black/30 px-1.5 py-0.5 rounded border border-border/20">
                      {{ formatHora(pago.cita.hora_inicio) }}
                    </span>
                  </div>
                  <span v-else>-</span>
                </td>

                <!-- Monto -->
                <td class="p-4 font-bold text-white">
                  {{ pago.monto }} {{ pago.moneda || 'EUR' }}
                  <span v-if="pago.es_sena" class="block text-[10px] text-primary font-normal">
                    Seña/Depósito
                  </span>
                </td>

                <!-- Método -->
                <td class="p-4 capitalize">
                  <span class="px-2 py-1 rounded-lg text-xs bg-black/30 border border-border/50 font-medium text-white/90">
                    {{ pago.metodo }}
                  </span>
                </td>

                <!-- Estado -->
                <td class="p-4">
                  <span 
                    :class="[
                      'px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider',
                      pago.estado === 'completado' ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 
                      pago.estado === 'pendiente' ? 'bg-yellow-500/10 text-yellow-400 border border-yellow-500/20' : 
                      'bg-red-500/10 text-red-400 border border-red-500/20'
                    ]"
                  >
                    {{ pago.estado }}
                  </span>
                </td>

                <!-- Fecha Pago -->
                <td class="p-4 text-text-muted text-xs">
                  {{ pago.pagado_en ? formatFechaCompleta(pago.pagado_en) : 'Pendiente de cobro' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="totalPages > 1" class="flex justify-between items-center bg-bg-card border border-border p-4 rounded-2xl">
        <span class="text-xs text-text-muted">
          Pág. {{ currentPage }} de {{ totalPages }} (Total: {{ totalItems }} transacciones)
        </span>
        <div class="flex gap-2">
          <button 
            @click="cambiarPagina(currentPage - 1)" 
            :disabled="currentPage === 1"
            class="px-3 py-1.5 rounded-lg border border-border text-xs font-medium bg-black/20 hover:bg-black/40 text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Anterior
          </button>
          <button 
            @click="cambiarPagina(currentPage + 1)" 
            :disabled="currentPage === totalPages"
            class="px-3 py-1.5 rounded-lg border border-border text-xs font-medium bg-black/20 hover:bg-black/40 text-white disabled:opacity-30 disabled:pointer-events-none transition-all"
          >
            Siguiente
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Loader2 } from 'lucide-vue-next';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

const loading = ref(true);
const pagos = ref([]);

// Paginación
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);

const loadPagos = async (page = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/pagos', { params: { page } });
    if (res.data.success && res.data.pagos) {
      pagos.value = res.data.pagos.data || [];
      currentPage.value = res.data.pagos.current_page || 1;
      totalPages.value = res.data.pagos.last_page || 1;
      totalItems.value = res.data.pagos.total || 0;
    }
  } catch (error) {
    console.error("Error al cargar listado de pagos:", error);
  } finally {
    loading.value = false;
  }
};

const cambiarPagina = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    loadPagos(page);
  }
};

// Formateadores de fecha
const formatFecha = (fechaStr) => {
  if (!fechaStr) return '';
  try {
    return format(parseISO(fechaStr), 'dd/MM/yyyy');
  } catch {
    return fechaStr;
  }
};

const formatHora = (horaStr) => {
  if (!horaStr) return '';
  return horaStr.substring(0, 5);
};

const formatFechaCompleta = (fechaCompletaStr) => {
  if (!fechaCompletaStr) return '';
  try {
    return format(parseISO(fechaCompletaStr), "d 'de' MMMM, yyyy HH:mm", { locale: es });
  } catch {
    return fechaCompletaStr;
  }
};

onMounted(() => {
  loadPagos();
});
</script>
