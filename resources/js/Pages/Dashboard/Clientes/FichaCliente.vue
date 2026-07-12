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
            {{ $t('ficha.titulo') }}
            <span v-if="cliente" class="text-primary">{{ cliente.nombre }} {{ cliente.apellido }}</span>
          </h2>
          <p class="text-text-muted mt-1">{{ $t('ficha.subtitulo') }}</p>
        </div>
      </div>
    </div>

    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader2 class="w-8 h-8 animate-spin text-primary" />
    </div>

    <div v-else-if="loadError" class="flex flex-col items-center justify-center py-20 text-center gap-4">
      <div class="w-16 h-16 rounded-full bg-red-500/10 flex items-center justify-center">
        <AlertTriangle class="w-8 h-8 text-red-400" />
      </div>
      <p class="text-xl font-bold text-white">{{ $t('ficha.error_carga') }}</p>
      <p class="text-text-muted max-w-sm">{{ loadError }}</p>
      <button @click="cargarDatos" class="bg-primary hover:bg-primary-hover text-white px-6 py-2 rounded-xl font-medium transition-all">
        {{ $t('ficha.reintentar') }}
      </button>
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
              <p class="text-text-muted text-sm">{{ $t('ficha.cliente_desde') }} {{ formatDate(cliente.created_at) }}</p>
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
            {{ $t('ficha.metricas') }}
          </h4>
          <div class="grid grid-cols-2 gap-4">
            <div class="bg-black/20 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-white">{{ stats.total_citas || 0 }}</p>
              <p class="text-xs text-text-muted">{{ $t('ficha.total_citas') }}</p>
            </div>
            <div class="bg-black/20 rounded-xl p-4 text-center">
              <p class="text-2xl font-bold text-green-400">{{ stats.citas_completadas || 0 }}</p>
              <p class="text-xs text-text-muted">{{ $t('ficha.citas_completadas') }}</p>
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
              {{ $t('ficha.tab_clinica') }}
            </button>
            <button 
              @click="activeTab = 'citas'"
              :class="['flex-1 py-4 font-medium transition-colors border-b-2', activeTab === 'citas' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-text-muted hover:text-white hover:bg-white/5']"
            >
              {{ $t('ficha.tab_historial') }}
            </button>
            <button 
              v-if="['medical', 'dental'].includes(tipoClinica)"
              @click="activeTab = 'historia_clinica'"
              :class="['flex-1 py-4 font-medium transition-colors border-b-2', activeTab === 'historia_clinica' ? 'border-primary text-primary bg-primary/5' : 'border-transparent text-text-muted hover:text-white hover:bg-white/5']"
            >
              Historia Clínica
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
                  {{ $t('ficha.alergias') }}
                </label>
                <textarea 
                  v-model="ficha.condiciones_medicas" 
                  rows="3" 
                  class="w-full bg-black/40 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors placeholder-text-muted/50"
                  :placeholder="$t('ficha.alergias_placeholder')"
                ></textarea>
              </div>

              <!-- Notas Privadas -->
              <div>
                <label class="block text-sm font-medium text-text-muted mb-2 flex items-center gap-2">
                  <FileText class="w-4 h-4 text-blue-400" />
                  {{ $t('ficha.notas_privadas') }}
                </label>
                <textarea 
                  v-model="ficha.notas_privadas" 
                  rows="6" 
                  class="w-full bg-black/40 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors placeholder-text-muted/50 font-mono text-sm"
                  :placeholder="$t('ficha.notas_placeholder')"
                ></textarea>
              </div>

              <div class="flex justify-end pt-4">
                <button @click="guardarFicha" :disabled="savingFicha" class="bg-primary hover:bg-primary-hover text-white px-8 py-3 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
                  <Loader2 v-if="savingFicha" class="w-5 h-5 animate-spin" />
                  <Save v-else class="w-5 h-5" />
                  {{ savingFicha ? $t('acciones.guardando') : $t('ficha.btn_guardar_ficha') }}
                </button>
              </div>
            </div>

            <!-- Taba: Historial Citas -->
            <div v-if="activeTab === 'citas'">
              <div v-if="citas.length === 0" class="py-12 text-center text-text-muted">
                {{ $t('ficha.sin_citas') }}
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

            <!-- Taba: Historia Clínica -->
            <div v-if="activeTab === 'historia_clinica'" class="space-y-6">
              <div v-if="historialClinico.length === 0" class="py-12 text-center text-text-muted">
                Este paciente no tiene registros de historia clínica guardados en este negocio.
              </div>
              <div v-else class="space-y-6">
                <div 
                  v-for="entry in historialClinico" 
                  :key="entry.id" 
                  :class="[
                    'bg-black/30 border border-border/50 rounded-2xl p-6 space-y-4',
                    printingEntryId === entry.id ? 'print-area' : ''
                  ]"
                >
                  <div class="flex justify-between items-center border-b border-border/30 pb-3">
                    <div>
                      <h4 class="font-bold text-white text-md">{{ entry.plantilla?.nombre || 'Historia Clínica' }}</h4>
                      <p class="text-xs text-text-muted mt-0.5">Fecha de registro: {{ formatCitaDate(entry.created_at) }}</p>
                    </div>
                    <button 
                      @click="imprimirRegistro(entry.id)"
                      class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white/5 border border-border text-xs font-semibold text-text-muted hover:text-white hover:bg-white/10 transition-colors no-print"
                    >
                      <Printer class="w-3.5 h-3.5 text-primary" />
                      Imprimir / PDF
                    </button>
                  </div>
                  
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div v-for="(val, key) in entry.respuestas" :key="key" class="space-y-1">
                      <span class="text-xs text-text-muted font-semibold block capitalize">{{ formatLabel(key, entry.plantilla?.campos) }}</span>
                      <span class="text-white whitespace-pre-wrap text-sm block mt-1">{{ formatValue(val) }}</span>
                    </div>
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
import { ref, onMounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { ArrowLeft, Loader2, Phone, Mail, Activity, AlertTriangle, FileText, Save, CheckCircle2, Calendar, Printer } from 'lucide-vue-next';
import axios from 'axios';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const loadError = ref('');
const cliente = ref(null);
const stats = ref({ total_citas: 0, canceladas: 0 });
const citas = ref([]);
const historialClinico = ref([]);
const tipoClinica = ref('general');
const activeTab = ref('clinica');

const savingFicha = ref(false);
const successMsg = ref('');
const printingEntryId = ref(null);

const imprimirRegistro = (id) => {
  printingEntryId.value = id;
  nextTick(() => {
    window.print();
    printingEntryId.value = null;
  });
};

const ficha = ref({
  condiciones_medicas: '',
  notas_privadas: ''
});

const getInitials = (n, a) => {
  return `${n?.charAt(0) || ''}${a?.charAt(0) || ''}`.toUpperCase();
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  try {
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return '';
    return format(d, "MMMM yyyy", { locale: es });
  } catch (e) {
    return '';
  }
};

const formatCitaDate = (fecha, hora) => {
  if (!fecha) return '';
  try {
    const horaStr = hora ? hora.substring(0, 5) : '00:00';
    const dateObj = new Date(`${fecha}T${horaStr}`);
    if (isNaN(dateObj.getTime())) return fecha;
    return format(dateObj, "dd MMM yyyy, HH:mm", { locale: es });
  } catch (e) {
    return fecha;
  }
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
  loadError.value = '';
  try {
    const id = route.params.id;
    const res = await axios.get(`/api/clientes/${id}`);
    if (res.data && res.data.cliente) {
      cliente.value = res.data.cliente;
      stats.value = res.data.estadisticas || { total_citas: 0, canceladas: 0 };
      citas.value = res.data.historial_citas || [];
      historialClinico.value = res.data.historial_clinico || [];
      tipoClinica.value = res.data.tipo_clinica || 'general';
      ficha.value = {
        condiciones_medicas: res.data.cliente.condiciones_medicas || '',
        notas_privadas: res.data.cliente.notas_internas || ''
      };
    } else {
      loadError.value = 'El servidor no devolvió datos del cliente. Verifica que exista.';
    }
  } catch (error) {
    console.error("Error cargando cliente:", error);
    const msg = error.response?.data?.message || 'Error de conexión con el servidor.';
    loadError.value = `${msg} (${error.response?.status || 'Sin conexión'})`;
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
    successMsg.value = t('ficha.exito_guardar');
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (error) {
    console.error("Error guardando ficha:", error);
    alert(t('ficha.error_guardar'));
  } finally {
    savingFicha.value = false;
  }
};

const formatLabel = (key, campos) => {
  if (!campos) return key;
  const field = campos.find(c => c.key === key);
  return field ? field.label : key;
};

const formatValue = (val) => {
  if (Array.isArray(val)) {
    return val.join(', ');
  }
  if (val === true || val === 'true') return 'Sí';
  if (val === false || val === 'false') return 'No';
  if (typeof val === 'object' && val !== null) {
    let resumen = [];
    for (const [key, data] of Object.entries(val)) {
      if (typeof data === 'object' && data !== null) {
        if (data.tipo !== undefined) {
           // Es Odontograma Anatómico (estructura plana: { tipo: '...', nota: '...' })
           const notaStr = data.nota ? ` (${data.nota})` : '';
           resumen.push(`Pieza/Zona ${key.toUpperCase()} -> ${data.tipo}${notaStr}`);
        } else {
           // Es Odontograma Topológico o Mamas (estructura anidada: { oclusal: {tipo: '', nota: ''} })
           const subData = Object.entries(data).map(([k, v]) => {
             if (typeof v === 'object' && v !== null) {
                return `${k.replace('_', ' ')}: ${v.tipo}` + (v.nota ? ` (${v.nota})` : '');
             }
             return `${k}: ${v}`;
           }).join(', ');
           if (subData) resumen.push(`Pieza/Zona ${key.toUpperCase()} -> ${subData}`);
        }
      } else {
        resumen.push(`${key}: ${data}`);
      }
    }
    return resumen.length > 0 ? resumen.join('\n') : 'Sin hallazgos';
  }
  return val || 'No especificado';
};

onMounted(() => {
  cargarDatos();
  if (route.query.tab) {
    activeTab.value = route.query.tab;
  }
});
</script>

<style>
@media print {
  /* Habilitar fondos y colores exactos en la impresión */
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }

  /* Ocultar absolutamente todo */
  body, #app, #app * {
    visibility: hidden !important;
  }

  /* Hacer visible la ficha a imprimir y sus hijos */
  .print-area, .print-area * {
    visibility: visible !important;
  }

  /* Posicionar el área de impresión para que ocupe toda la página */
  .print-area {
    position: absolute !important;
    left: 0 !important;
    top: 0 !important;
    width: 100% !important;
    background-color: white !important;
    color: black !important;
    padding: 20px !important;
    border: none !important;
    box-shadow: none !important;
  }

  /* Forzar que los textos dentro de la ficha sean oscuros sobre fondo claro */
  .print-area span,
  .print-area p,
  .print-area h4,
  .print-area h3,
  .print-area div {
    color: #111827 !important; /* text-gray-900 */
  }

  /* Ocultar botones de impresión al imprimir */
  .print-area .no-print,
  .print-area button {
    display: none !important;
  }
}
</style>
