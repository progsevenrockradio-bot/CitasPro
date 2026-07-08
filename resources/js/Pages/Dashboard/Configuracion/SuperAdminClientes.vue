<template>
  <div class="space-y-6 max-w-6xl">
    <div>
      <h2 class="text-2xl font-bold">Mis Clientes (Profesionales)</h2>
      <p class="text-text-muted text-sm mt-1">Gestiona los profesionales registrados y el estado de sus cuentas en la plataforma.</p>
    </div>

    <!-- Search / List -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[500px]">
      <div class="flex gap-4 mb-6">
        <input 
          v-model="searchQuery"
          @keyup.enter="loadProfesionales"
          type="text" 
          placeholder="Buscar profesional por nombre o email..." 
          class="flex-1 bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
        />
        <button 
          @click="loadProfesionales(1)"
          :disabled="loading"
          class="bg-indigo-600 hover:bg-indigo-500 text-white px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2"
        >
          <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
          <Search v-else class="w-5 h-5" /> 
          Buscar
        </button>
      </div>

      <div v-if="loading && profesionales.length === 0" class="flex justify-center py-12 text-primary">
        <Loader2 class="w-10 h-10 animate-spin" />
      </div>

      <div v-else-if="profesionales.length === 0" class="text-center text-text-muted py-12 border-t border-border/50">
        <Users class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p>No se encontraron profesionales registrados.</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-border text-text-muted text-xs font-bold uppercase tracking-wider">
              <th class="pb-3 font-semibold p-4">Profesional</th>
              <th class="pb-3 font-semibold p-4">Negocio</th>
              <th class="pb-3 font-semibold p-4">Teléfono</th>
              <th class="pb-3 font-semibold p-4">Rol</th>
              <th class="pb-3 font-semibold p-4 text-center">Estado</th>
              <th class="pb-3 font-semibold p-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/50 text-sm">
            <tr v-for="profesional in profesionales" :key="profesional.id" class="hover:bg-white/5 transition-colors">
              <td class="py-4 p-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-indigo-600/20 flex items-center justify-center text-indigo-400 font-bold">
                    {{ profesional.nombre.charAt(0) }}{{ profesional.apellido?.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-bold text-white">{{ profesional.nombre }} {{ profesional.apellido }}</p>
                    <p class="text-xs text-text-muted">{{ profesional.email }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 p-4 text-white font-medium">
                {{ profesional.negocio?.nombre || 'Sin Negocio' }}
              </td>
              <td class="py-4 p-4 text-white font-mono text-xs">{{ profesional.telefono || 'N/A' }}</td>
              <td class="py-4 p-4 capitalize text-text-muted">
                {{ profesional.rol }}
              </td>
              <td class="py-4 p-4 text-center">
                <span 
                  :class="[
                    'px-2 py-0.5 rounded text-xs font-semibold uppercase tracking-wider',
                    profesional.activo ? 'bg-green-500/10 text-green-400 border border-green-500/20' : 'bg-red-500/10 text-red-400 border border-red-500/20'
                  ]"
                >
                  {{ profesional.activo ? 'Activo' : 'Suspendido' }}
                </span>
              </td>
              <td class="py-4 p-4 text-right">
                <button 
                  @click="confirmToggleEstado(profesional)" 
                  class="text-indigo-400 hover:text-indigo-300 font-medium underline text-sm transition-all"
                >
                  {{ profesional.activo ? 'Suspender' : 'Activar' }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Paginación -->
      <div v-if="totalPages > 1" class="flex justify-between items-center bg-bg-card border border-border p-4 rounded-2xl mt-6">
        <span class="text-xs text-text-muted">
          Pág. {{ currentPage }} de {{ totalPages }} (Total: {{ totalItems }} profesionales)
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

    <!-- ConfirmModal personalizado para suspensión/activación -->
    <ConfirmModal 
      v-model:show="showConfirmModal"
      title="Confirmar cambio de estado"
      :message="confirmMessage"
      :type="confirmType"
      confirm-text="Confirmar"
      cancel-text="Cancelar"
      @confirm="executeToggleEstado"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Search, Loader2, Users } from 'lucide-vue-next';
import axios from 'axios';
import ConfirmModal from '../../Components/ConfirmModal.vue';

const loading = ref(true);
const searchQuery = ref('');
const profesionales = ref([]);

// Paginación
const currentPage = ref(1);
const totalPages = ref(1);
const totalItems = ref(0);

// Modal
const showConfirmModal = ref(false);
const confirmMessage = ref('');
const confirmType = ref('warning');
const selectedProfesional = ref(null);

const loadProfesionales = async (page = 1) => {
  loading.value = true;
  try {
    const token = localStorage.getItem('token');
    const res = await axios.get('/api/admin/profesionales', { 
      params: { page, buscar: searchQuery.value },
      headers: { 'Authorization': `Bearer ${token}` }
    });
    if (res.data) {
      profesionales.value = res.data.data || [];
      currentPage.value = res.data.current_page || 1;
      totalPages.value = res.data.last_page || 1;
      totalItems.value = res.data.total || 0;
    }
  } catch (error) {
    console.error("Error al cargar profesionales:", error);
  } finally {
    loading.value = false;
  }
};

const cambiarPagina = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    loadProfesionales(page);
  }
};

const confirmToggleEstado = (profesional) => {
  selectedProfesional.value = profesional;
  confirmType.value = profesional.activo ? 'danger' : 'warning';
  confirmMessage.value = profesional.activo 
    ? `¿Estás seguro de que deseas suspender la cuenta de ${profesional.nombre} ${profesional.apellido}? Se bloqueará su acceso.`
    : `¿Deseas activar la cuenta de ${profesional.nombre} ${profesional.apellido}?`;
  showConfirmModal.value = true;
};

const executeToggleEstado = async () => {
  if (!selectedProfesional.value) return;
  const p = selectedProfesional.value;
  try {
    const token = localStorage.getItem('token');
    const nuevoEstado = !p.activo;
    const res = await axios.patch(`/api/admin/profesionales/${p.id}`, {
      activo: nuevoEstado
    }, {
      headers: { 'Authorization': `Bearer ${token}` }
    });
    
    if (res.data.success) {
      p.activo = nuevoEstado;
    }
  } catch (error) {
    console.error("Error al actualizar estado del profesional:", error);
  } finally {
    selectedProfesional.value = null;
  }
};

onMounted(() => {
  loadProfesionales();
});
</script>
