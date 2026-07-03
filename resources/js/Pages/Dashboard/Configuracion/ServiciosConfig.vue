<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">Servicios</h2>
        <p class="text-text-muted text-sm mt-1">Gestiona los servicios que ofreces a tus clientes.</p>
      </div>
      <button @click="abrirModal()" class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl font-medium flex items-center gap-2 transition-all">
        <Plus class="w-5 h-5" />
        Nuevo Servicio
      </button>
    </div>

    <!-- Lista de Servicios -->
    <div v-if="loading" class="flex justify-center py-12">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
    </div>

    <div v-else-if="servicios.length === 0" class="bg-bg-card border border-border rounded-2xl p-12 text-center">
      <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
        <Scissors class="w-8 h-8 text-primary" />
      </div>
      <h3 class="text-xl font-bold mb-2">No tienes servicios registrados</h3>
      <p class="text-text-muted mb-6">Agrega tu primer servicio para que tus clientes puedan reservar citas.</p>
      <button @click="abrirModal()" class="bg-primary/20 text-primary hover:bg-primary/30 px-6 py-2 rounded-lg font-medium transition-colors">
        Crear mi primer servicio
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="servicio in servicios" :key="servicio.id" class="bg-bg-card border border-border rounded-2xl p-5 hover:border-primary/50 transition-colors group">
        <div class="flex justify-between items-start mb-3">
          <h3 class="font-bold text-lg leading-tight">{{ servicio.nombre }}</h3>
          <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="abrirModal(servicio)" class="text-text-muted hover:text-white transition-colors">
              <Edit2 class="w-4 h-4" />
            </button>
            <button @click="eliminarServicio(servicio.id)" class="text-text-muted hover:text-red-400 transition-colors">
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </div>
        
        <p v-if="servicio.descripcion" class="text-sm text-text-muted mb-4 line-clamp-2">{{ servicio.descripcion }}</p>
        
        <div class="flex items-center gap-4 text-sm text-white font-medium mt-auto">
          <div class="flex items-center gap-1.5">
            <Clock class="w-4 h-4 text-text-muted" />
            {{ servicio.duracion_min }} min
          </div>
          <div class="flex items-center gap-1.5">
            <Banknote class="w-4 h-4 text-green-400" />
            {{ parseFloat(servicio.precio).toFixed(2) }} {{ servicio.moneda || '€' }}
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-bg-card border border-border rounded-2xl w-full max-w-md overflow-hidden animate-fade-in-up">
        <div class="p-6 border-b border-border/50 flex justify-between items-center">
          <h3 class="font-bold text-lg">{{ modoEdit ? 'Editar Servicio' : 'Nuevo Servicio' }}</h3>
          <button @click="cerrarModal" class="text-text-muted hover:text-white">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-xl flex items-center gap-2 mb-2 text-sm">
            <X class="w-4 h-4 flex-shrink-0" />
            <p>{{ errorMsg }}</p>
          </div>

          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">Nombre del Servicio</label>
            <input v-model="form.nombre" type="text" placeholder="Ej: Corte de Cabello" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
          </div>
          
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">Descripción (Opcional)</label>
            <textarea v-model="form.descripcion" rows="2" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary"></textarea>
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Precio</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted">€</span>
                <input v-model="form.precio" type="number" step="0.01" min="0" class="w-full bg-black/20 border border-border rounded-xl pl-8 pr-4 py-2.5 text-white focus:outline-none focus:border-primary" />
              </div>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Duración (minutos)</label>
              <input v-model="form.duracion_min" type="number" step="5" min="5" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
            </div>
          </div>
        </div>
        
        <div class="p-6 border-t border-border/50 bg-black/20 flex justify-end gap-3">
          <button @click="cerrarModal" class="px-5 py-2.5 text-text-muted hover:text-white transition-colors">Cancelar</button>
          <button @click="guardarServicio" :disabled="saving" class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2">
            <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
            {{ saving ? 'Guardando...' : 'Guardar' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Plus, Scissors, Clock, Banknote, Edit2, Trash2, X, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const servicios = ref([]);
const loading = ref(true);
const saving = ref(false);
const errorMsg = ref('');

const mostrarModal = ref(false);
const modoEdit = ref(false);
const editId = ref(null);

const form = ref({
  nombre: '',
  descripcion: '',
  precio: 0,
  duracion_min: 30
});

const cargarServicios = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/servicios');
    servicios.value = res.data.servicios || [];
  } catch (error) {
    console.error("Error cargando servicios:", error);
  } finally {
    loading.value = false;
  }
};

const abrirModal = (servicio = null) => {
  errorMsg.value = '';
  if (servicio) {
    modoEdit.value = true;
    editId.value = servicio.id;
    form.value = {
      nombre: servicio.nombre,
      descripcion: servicio.descripcion || '',
      precio: servicio.precio,
      duracion_min: servicio.duracion_min
    };
  } else {
    modoEdit.value = false;
    editId.value = null;
    form.value = {
      nombre: '',
      descripcion: '',
      precio: 0,
      duracion_min: 30
    };
  }
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
};

const guardarServicio = async () => {
  errorMsg.value = '';
  if (!form.value.nombre) {
    errorMsg.value = 'El nombre del servicio es obligatorio.';
    return;
  }
  
  saving.value = true;
  try {
    if (modoEdit.value) {
      await axios.patch(`/api/servicios/${editId.value}`, form.value);
    } else {
      await axios.post('/api/servicios', form.value);
    }
    cerrarModal();
    cargarServicios();
  } catch (error) {
    console.error("Error guardando servicio:", error);
    errorMsg.value = error.response?.data?.message || 'Hubo un error al guardar el servicio.';
  } finally {
    saving.value = false;
  }
};

const eliminarServicio = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar este servicio?')) return;
  try {
    await axios.delete(`/api/servicios/${id}`);
    cargarServicios();
  } catch (error) {
    console.error("Error eliminando servicio:", error);
  }
};

onMounted(() => {
  cargarServicios();
});
</script>
