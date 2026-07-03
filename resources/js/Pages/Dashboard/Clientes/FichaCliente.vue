<template>
  <div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-4">
        <button @click="router.push('/panel/clientes')" class="w-10 h-10 rounded-full bg-white/5 border border-border flex items-center justify-center text-text-muted hover:text-white hover:bg-white/10 transition-colors">
          <ArrowLeft class="w-5 h-5" />
        </button>
        <div>
          <h2 class="text-2xl font-bold flex items-center gap-2">
            Ficha del Cliente
            <span v-if="cliente" class="text-primary">{{ cliente.nombre }} {{ cliente.apellido }}</span>
          </h2>
          <p class="text-text-muted mt-1">Gestión integral de historial clínico y citas.</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
    </div>

    <div v-else-if="cliente" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      
      <!-- Panel Izquierdo: Info Rápida -->
      <div class="lg:col-span-1 space-y-6">
        <div class="bg-bg-card border border-border rounded-2xl p-6">
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-xl border border-primary/30 shadow-[0_0_15px_rgba(99,102,241,0.2)]">
              {{ getInitials(cliente.nombre, cliente.apellido) }}
            </div>
            <div>
              <h3 class="text-xl font-bold">{{ cliente.nombre }} {{ cliente.apellido }}</h3>
              <p class="text-text-muted text-sm">Cliente desde {{ formatDate(cliente.created_at) }}</p>
            </div>
          </div>
          
          <div class="space-y-4">
            <div class="flex items-center gap-3 text-text-muted">
              <Phone class="w-5 h-5 text-primary" />
              <span>{{ cliente.telefono }}</span>
            </div>
            <div class="flex items-center gap-3 text-text-muted" v-if="cliente.email">
              <Mail class="w-5 h-5 text-primary" />
              <span>{{ cliente.email }}</span>
            </div>
          </div>
        </div>

        <div class="bg-bg-card border border-border rounded-2xl p-6">
          <h4 class="font-bold mb-4 flex items-center gap-2">
            <Activity class="w-5 h-5 text-primary" />
            Métricas
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-black/20 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-white">{{ stats.total_citas || 0 }}</p>
              <p class="text-xs text-text-muted">Total Citas</p>
            </div>
            <div class="bg-black/20 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-green-400">{{ stats.citas_completadas || 0 }}</p>
              <p class="text-xs text-text-muted">Completadas</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Panel Derecho: Pestañas (Ficha Clínica / Historial) -->
      <div class="lg:col-span-2">
        <div class="bg-bg-card border border-border rounded-2xl overflow-hidden flex flex-col h-full min-h-[500px]">
          <!-- Tabs -->
          <div class="flex border-b border-border/50 bg-black/20">
            <button 
              @click="activeTab = 'clinica'"
              :class="['flex-1 py-4 font-medium transition-colors border-b-2', activeTab === 'clinica' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-text-muted hover:text-white hover:bg-white/5']"
            >
              Ficha Clínica
            </button>
            <button 
              @click="activeTab = 'citas'"
              :class="['flex-1 py-4 font-medium transition-colors border-b-2', activeTab === 'citas' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-text-muted hover:text-white hover:bg-white/5']"
            >
              Historial de Citas
            </button>
          </div>

          <!-- Contenido Tabs -->
          <div class="p-6 flex-1 bg-gradient-to-b from-transparent to-black/20">
            
            <!-- Taba: Ficha Clínica -->
            <div v-if="activeTab === 'clinica'" class="space-y-6">
              
              <div v-if="successMsg" class="bg-green-500/10 border border-green-500/50 text-green-400 p-3 rounded-xl mb-4 flex items-center gap-2">
                <CheckCircle2 class="w-5 h-5 flex-shrink-0" />
                <p>{{ successMsg }}</p>
              </div>

              <!-- Anamnesis / Alergias -->
              <div>
                <label class="block text-sm font-medium text-text-muted mb-2 flex items-center gap-2">
                  <AlertTriangle class="w-4 h-4 text-orange-400" />
                  Alergias y Condiciones Médicas
                </label>
                <textarea 
                  v-model="ficha.condiciones_medicas" 
                  rows="3" 
                  class="w-full bg-black/40 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors placeholder-text-muted/50"
                  placeholder="Ej: Alergia al látex, diabetes tipo 2..."
                ></textarea>
              </div>

              <!-- Notas Privadas -->
              <div>
                <label class="block text-sm font-medium text-text-muted mb-2 flex items-center gap-2">
                  <FileText class="w-4 h-4 text-blue-400" />
                  Notas Privadas de Evolución
                </label>
                <textarea 
                  v-model="ficha.notas_privadas" 
                  rows="6" 
                  class="w-full bg-black/40 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors placeholder-text-muted/50 font-mono text-sm"
                  placeholder="Escribe aquí las notas del tratamiento de hoy. Ej: 2024-03-10: Se realiza extracción de uña encarnada..."
                ></textarea>
              </div>

              <div class="flex justify-end pt-4">
                <button @click="guardarFicha" :disabled="savingFicha" class="bg-primary hover:bg-primary-hover text-white px-8 py-3 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
                  <Loader2 v-if="savingFicha" class="w-5 h-5 animate-spin" />
                  <Save v-else class="w-5 h-5" />
                  {{ savingFicha ? 'Guardando...' : 'Guardar Ficha Clínica' }}
                </button>
              </div>
            </div>

            <!-- Taba: Historial Citas -->
            <div v-if="activeTab === 'citas'">
              <div v-if="citas.length === 0" class="py-12 text-center text-text-muted">
                No hay citas registradas para este cliente.
              </div>
              <div v-else class="space-y-3">
                <div v-for="cita in citas" :key="cita.id" class="bg-black/30 border border-border/50 rounded-xl p-4 flex items-center justify-between hover:border-primary/30 transition-colors">
                  <div>
                    <p class="font-bold text-white">{{ cita.servicio?.nombre || 'Servicio' }}</p>
                    <div class="flex items-center gap-2 text-sm text-text-muted mt-1">
                      <Calendar class="w-4 h-4" />
                      {{ formatCitaDate(cita.fecha, cita.hora_inicio) }}
                    </div>
                  </div>
                  <div class="text-right">
                    <span :class="getStatusBadgeClass(cita.estado)" class="px-3 py-1 rounded-full text-xs font-medium border">
                      {{ cita.estado.toUpperCase() }}
                    </span>
                    <p class="text-sm font-bold mt-2 text-white">{{ cita.precio_total }}€</p>
                  </div>
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
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Loader2, Phone, Mail, Activity, AlertTriangle, FileText, Save, CheckCircle2, Calendar } from 'lucide-vue-next';
import axios from 'axios';
import { format, parseISO } from 'date-fns';
import { es } from 'date-fns/locale';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const cliente = ref(null);
const stats = ref({ total_citas: 0, canceladas: 0 });
const citas = ref([]);
const activeTab = ref('clinica');

const savingFicha = ref(false);
const successMsg = ref('');
const ficha = ref({
  condiciones_medicas: '',
  notas_privadas: ''
});

const getInitials = (n, a) => {
  return `${n?.charAt(0) || ''}${a?.charAt(0) || ''}`.toUpperCase();
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  return format(parseISO(dateString), "MMMM yyyy", { locale: es });
};

const formatCitaDate = (fecha, hora) => {
  if (!fecha) return '';
  const dateObj = new Date(`${fecha}T${hora || '00:00'}`);
  return format(dateObj, "dd MMM yyyy, HH:mm", { locale: es });
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'confirmada': return 'bg-green-500/10 text-green-400 border-green-500/30';
    case 'pendiente': return 'bg-yellow-500/10 text-yellow-400 border-yellow-500/30';
    case 'cancelada': return 'bg-red-500/10 text-red-400 border-red-500/30';
    default: return 'bg-gray-500/10 text-gray-400 border-gray-500/30';
  }
};

const cargarDatos = async () => {
  loading.value = true;
  try {
    const id = route.params.id;
    const res = await axios.get(`/api/clientes/${id}`);
    if (res.data && res.data.cliente) {
      cliente.value = res.data.cliente;
      stats.value = res.data.estadisticas || { total_citas: 0, canceladas: 0 };
      citas.value = res.data.historial_citas || [];
      
      if (res.data.cliente) {
        ficha.value = {
          condiciones_medicas: res.data.cliente.condiciones_medicas || '',
          notas_privadas: res.data.cliente.notas_internas || ''
        };
      }
    }
  } catch (error) {
    console.error("Error cargando cliente:", error);
  } finally {
    loading.value = false;
  }
};

const guardarFicha = async () => {
  savingFicha.value = true;
  successMsg.value = '';
  try {
    const id = route.params.id;
    await axios.patch(`/api/clientes/${id}`, {
      notas_internas: ficha.value.notas_privadas,
      condiciones_medicas: ficha.value.condiciones_medicas
    });
    successMsg.value = 'Notas médicas actualizadas con éxito.';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (error) {
    console.error("Error guardando ficha:", error);
    alert('Hubo un error al guardar las notas médicas.');
  } finally {
    savingFicha.value = false;
  }
};

onMounted(() => {
  cargarDatos();
});
</script>
