<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden flex flex-col max-h-[90vh]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-black/20">
        <h3 class="text-xl font-bold text-white">Nueva Cita</h3>
        <button @click="close" class="text-text-muted hover:text-white transition-colors">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 overflow-y-auto flex-1 space-y-5">
        
        <!-- Cliente -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Cliente (Teléfono)</label>
          <input 
            v-model="form.cliente.telefono" 
            type="text" 
            placeholder="+34 600 000 000"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
          <p class="text-xs text-text-muted mt-1">Si el cliente no existe, se creará automáticamente.</p>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Nombre</label>
            <input 
              v-model="form.cliente.nombre" 
              type="text" 
              placeholder="Ej. Ana"
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Apellido</label>
            <input 
              v-model="form.cliente.apellido" 
              type="text" 
              placeholder="Ej. Gómez"
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>

        <!-- Servicio -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Servicio</label>
          <select 
            v-model="form.servicio_id" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent appearance-none">
            <option value="" disabled selected>Selecciona un servicio</option>
            <option v-for="servicio in servicios" :key="servicio.id" :value="servicio.id">
              {{ servicio.nombre }} ({{ servicio.precio }}€ - {{ servicio.duracion_min }} min)
            </option>
          </select>
        </div>

        <!-- Fecha y Hora -->
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Fecha</label>
            <input 
              v-model="form.fecha" 
              type="date" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent [color-scheme:dark]"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">Hora</label>
            <input 
              v-model="form.hora" 
              type="time" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent [color-scheme:dark]"
            />
          </div>
        </div>

        <!-- Notas -->
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Notas (Opcional)</label>
          <textarea 
            v-model="form.notas_profesional" 
            rows="2"
            placeholder="Alguna nota interna sobre esta cita..."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
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
          Cancelar
        </button>
        <button 
          @click="guardarCita" 
          :disabled="loading"
          class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          Guardar Cita
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { X, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
  show: Boolean
});

const emit = defineEmits(['close', 'saved']);

const servicios = ref([]);
const loading = ref(false);
const errorMsg = ref('');

const form = ref({
  cliente: {
    telefono: '',
    nombre: '',
    apellido: ''
  },
  servicio_id: '',
  fecha: new Date().toISOString().split('T')[0],
  hora: '10:00',
  notas_profesional: ''
});

// Reset form when opened
watch(() => props.show, (newVal) => {
  if (newVal) {
    errorMsg.value = '';
    form.value.cliente = { telefono: '', nombre: '', apellido: '' };
    form.value.servicio_id = '';
    form.value.fecha = new Date().toISOString().split('T')[0];
    form.value.hora = '10:00';
    form.value.notas_profesional = '';
    cargarServicios();
  }
});

const cargarServicios = async () => {
  if (servicios.value.length > 0) return;
  try {
    const res = await axios.get('/api/servicios');
    servicios.value = res.data.servicios || [];
  } catch (error) {
    console.error("Error al cargar servicios", error);
  }
};

const guardarCita = async () => {
  errorMsg.value = '';
  
  if (!form.value.cliente.telefono || !form.value.cliente.nombre) {
    errorMsg.value = 'El teléfono y nombre del cliente son obligatorios.';
    return;
  }
  if (!form.value.servicio_id) {
    errorMsg.value = 'Debes seleccionar un servicio.';
    return;
  }

  loading.value = true;
  try {
    await axios.post('/api/citas', form.value);
    emit('saved');
    close();
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Hubo un error al guardar la cita.';
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
