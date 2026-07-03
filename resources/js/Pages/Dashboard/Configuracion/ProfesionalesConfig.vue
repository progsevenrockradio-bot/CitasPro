<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">Profesionales</h2>
        <p class="text-text-muted text-sm mt-1">Gestiona tu equipo de profesionales y especialistas.</p>
      </div>
      <button @click="abrirModal()" class="bg-primary hover:bg-primary-hover text-white px-5 py-2.5 rounded-xl font-medium flex items-center gap-2 transition-all">
        <Plus class="w-5 h-5" />
        Nuevo Profesional
      </button>
    </div>

    <!-- Lista de Profesionales -->
    <div v-if="loading" class="flex justify-center py-12">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
    </div>

    <div v-else-if="profesionales.length === 0" class="bg-bg-card border border-border rounded-2xl p-12 text-center">
      <div class="bg-primary/10 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
        <Users class="w-8 h-8 text-primary" />
      </div>
      <h3 class="text-xl font-bold mb-2">No tienes profesionales registrados</h3>
      <p class="text-text-muted mb-6">Agrega al menos un profesional (puedes ser tú mismo) para poder asignar citas.</p>
      <button @click="abrirModal()" class="bg-primary/20 text-primary hover:bg-primary/30 px-6 py-2 rounded-lg font-medium transition-colors">
        Agregar mi primer profesional
      </button>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div v-for="profesional in profesionales" :key="profesional.id" class="bg-bg-card border border-border rounded-2xl p-5 hover:border-primary/50 transition-colors group">
        <div class="flex items-start gap-4 mb-4">
          <div class="w-12 h-12 rounded-full bg-gradient-to-br from-primary/40 to-primary/10 flex items-center justify-center text-lg font-bold border border-primary/20 shrink-0">
            {{ profesional.nombre.charAt(0) }}{{ profesional.apellido ? profesional.apellido.charAt(0) : '' }}
          </div>
          <div class="flex-1">
            <div class="flex justify-between items-start">
              <h3 class="font-bold text-lg leading-tight">{{ profesional.nombre }} {{ profesional.apellido }}</h3>
              <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                <button @click="abrirModal(profesional)" class="text-text-muted hover:text-white transition-colors">
                  <Edit2 class="w-4 h-4" />
                </button>
                <button @click="eliminarProfesional(profesional.id)" class="text-text-muted hover:text-red-400 transition-colors">
                  <Trash2 class="w-4 h-4" />
                </button>
              </div>
            </div>
            <p class="text-sm text-primary font-medium">{{ profesional.especialidad || 'Especialista' }}</p>
          </div>
        </div>
        
        <div class="space-y-2 text-sm text-text-muted">
          <div class="flex items-center gap-2">
            <Phone class="w-4 h-4" />
            {{ profesional.telefono }}
          </div>
          <div class="flex items-center gap-2">
            <Mail class="w-4 h-4" />
            {{ profesional.email }}
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Formulario -->
    <div v-if="mostrarModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-bg-card border border-border rounded-2xl w-full max-w-lg overflow-hidden animate-fade-in-up">
        <div class="p-6 border-b border-border/50 flex justify-between items-center">
          <h3 class="font-bold text-lg">{{ modoEdit ? 'Editar Profesional' : 'Nuevo Profesional' }}</h3>
          <button @click="cerrarModal" class="text-text-muted hover:text-white">
            <X class="w-5 h-5" />
          </button>
        </div>
        
        <div class="p-6 space-y-4">
          <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-xl flex items-center gap-2 mb-2 text-sm">
            <X class="w-4 h-4 flex-shrink-0" />
            <p>{{ errorMsg }}</p>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Nombre</label>
              <input v-model="form.nombre" type="text" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Apellido</label>
              <input v-model="form.apellido" type="text" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
            </div>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-text-muted mb-1">Especialidad</label>
            <input v-model="form.especialidad" type="text" placeholder="Ej: Dermatólogo" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
          </div>
          
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Teléfono</label>
              <input v-model="form.telefono" type="tel" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
            </div>
            <div>
              <label class="block text-sm font-medium text-text-muted mb-1">Correo Electrónico</label>
              <input v-model="form.email" type="email" class="w-full bg-black/20 border border-border rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-primary" />
            </div>
          </div>
        </div>
        
        <div class="p-6 border-t border-border/50 bg-black/20 flex justify-end gap-3">
          <button @click="cerrarModal" class="px-5 py-2.5 text-text-muted hover:text-white transition-colors">Cancelar</button>
          <button @click="guardarProfesional" :disabled="saving" class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2">
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
import { Plus, Users, Phone, Mail, Edit2, Trash2, X, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const profesionales = ref([]);
const loading = ref(true);
const saving = ref(false);
const errorMsg = ref('');

const mostrarModal = ref(false);
const modoEdit = ref(false);
const editId = ref(null);

const form = ref({
  nombre: '',
  apellido: '',
  telefono: '',
  email: '',
  especialidad: ''
});

const cargarProfesionales = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/profesionales');
    profesionales.value = res.data.profesionales || [];
  } catch (error) {
    console.error("Error cargando profesionales:", error);
  } finally {
    loading.value = false;
  }
};

const abrirModal = (prof = null) => {
  if (prof) {
    modoEdit.value = true;
    editId.value = prof.id;
    form.value = {
      nombre: prof.nombre,
      apellido: prof.apellido,
      telefono: prof.telefono,
      email: prof.email,
      especialidad: prof.especialidad || ''
    };
  } else {
    modoEdit.value = false;
    editId.value = null;
    form.value = {
      nombre: '',
      apellido: '',
      telefono: '',
      email: '',
      especialidad: ''
    };
  }
  mostrarModal.value = true;
};

const cerrarModal = () => {
  mostrarModal.value = false;
};

const guardarProfesional = async () => {
  errorMsg.value = '';
  if (!form.value.nombre || !form.value.telefono || !form.value.email) {
    errorMsg.value = 'Nombre, teléfono y correo son obligatorios';
    return;
  }
  
  saving.value = true;
  try {
    if (modoEdit.value) {
      await axios.patch(`/api/profesionales/${editId.value}`, form.value);
    } else {
      await axios.post('/api/profesionales', form.value);
    }
    cerrarModal();
    cargarProfesionales();
  } catch (error) {
    console.error("Error guardando profesional:", error);
    errorMsg.value = error.response?.data?.message || 'Hubo un error al guardar el profesional';
  } finally {
    saving.value = false;
  }
};

const eliminarProfesional = async (id) => {
  if (!confirm('¿Estás seguro de que deseas eliminar a este profesional?')) return;
  try {
    await axios.delete(`/api/profesionales/${id}`);
    cargarProfesionales();
  } catch (error) {
    console.error("Error eliminando profesional:", error);
  }
};

onMounted(() => {
  cargarProfesionales();
});
</script>
