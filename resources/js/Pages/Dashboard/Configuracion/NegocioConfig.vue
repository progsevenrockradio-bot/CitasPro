<template>
  <div class="space-y-6 max-w-4xl">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">{{ $t('config.titulo') }}</h2>
        <p class="text-text-muted text-sm mt-1">{{ $t('config.subtitulo') }}</p>
      </div>
      <button 
        @click="guardarCambios"
        :disabled="loading || saving"
        class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2">
        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        {{ $t('acciones.guardar_cambios') }}
      </button>
    </div>

    <!-- Mensajes de feedback -->
    <div v-if="successMsg" class="bg-green-500/10 border border-green-500/50 text-green-400 p-4 rounded-xl flex items-center gap-3">
      <CheckCircle class="w-5 h-5" />
      <p>{{ successMsg }}</p>
    </div>
    
    <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-4 rounded-xl flex items-center gap-3">
      <AlertCircle class="w-5 h-5" />
      <p>{{ errorMsg }}</p>
    </div>

    <div v-if="loading" class="flex flex-col items-center justify-center py-12 text-primary">
      <Loader2 class="w-10 h-10 animate-spin mb-4" />
      <p>{{ $t('acciones.cargando') }}</p>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Información Básica -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">{{ $t('config.info_basica') }}</h3>
        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Logo del Negocio (1024x1024 recomendado)</label>
          <div class="flex items-center gap-4">
            <div class="w-20 h-20 rounded-xl border-2 border-dashed border-border bg-black/20 flex items-center justify-center overflow-hidden relative group">
              <img v-if="logoPreview || logoUrl" :src="logoPreview || logoUrl" class="w-full h-full object-cover" />
              <div v-else class="text-text-muted">
                <ImageIcon class="w-8 h-8 opacity-50" />
              </div>
              <div v-if="logoPreview || logoUrl" class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition-opacity">
                <button @click.prevent="quitarLogo" class="text-white hover:text-red-400 p-1">
                  <X class="w-5 h-5" />
                </button>
              </div>
            </div>
            
            <div class="flex-1">
              <label class="cursor-pointer bg-black/20 hover:bg-black/40 border border-border rounded-xl px-4 py-2 text-white text-sm font-medium transition-all inline-flex items-center gap-2">
                <Upload class="w-4 h-4" />
                Subir Imagen
                <input type="file" class="hidden" accept="image/*" @change="onLogoChange" />
              </label>
              <p class="text-xs text-text-muted mt-2">Formatos: JPG, PNG, WEBP. Máx: 2MB.</p>
            </div>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.nombre') }}</label>
          <input 
            v-model="form.nombre" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.descripcion') }}</label>
          <textarea 
            v-model="form.descripcion" 
            rows="3"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          ></textarea>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.sitio_web') }}</label>
          <input 
            v-model="form.sitio_web" 
            type="url" 
            placeholder="https://..."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">Número Fiscal / Identificación de Impuestos</label>
          <input 
            v-model="form.numero_fiscal" 
            type="text" 
            placeholder="Ej: B12345678, RFC, RUT"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div class="mt-6 pt-6 border-t border-border/50">
          <div class="flex items-center justify-between">
            <div>
              <h4 class="font-bold text-white">{{ $t('config.modulo_medico') }}</h4>
              <p class="text-sm text-text-muted mt-1">{{ $t('config.activa_fichas') }}</p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
              <input type="checkbox" v-model="form.es_medico" class="sr-only peer">
              <div class="w-11 h-6 bg-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary"></div>
            </label>
          </div>
        </div>
      </div>

      <!-- Contacto y Ubicación -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">{{ $t('config.contacto_ubicacion') }}</h3>
        
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.telefono') }}</label>
            <input 
              v-model="form.telefono" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
          <div>
            <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.whatsapp') }}</label>
            <input 
              v-model="form.whatsapp" 
              type="text" 
              class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
            />
          </div>
        </div>

        <!-- Teléfonos adicionales -->
        <div class="mt-4 pt-4 border-t border-border/30">
          <label class="block text-sm font-medium text-text-muted mb-3">Teléfonos Adicionales</label>
          
          <div v-for="(phone, index) in form.telefonos_adicionales" :key="index" class="flex gap-2 mb-3 items-center">
            <input 
              v-model="phone.number" 
              type="text" 
              placeholder="Número"
              class="flex-1 bg-black/20 border border-border rounded-xl px-3 py-2 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
            />
            
            <select 
              v-model="phone.type" 
              class="w-28 bg-black/20 border border-border rounded-xl px-2 py-2 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent text-sm"
            >
              <option value="local">Local</option>
              <option value="mobile">Móvil</option>
              <option value="fax">Fax</option>
            </select>
            
            <label class="flex items-center gap-1 cursor-pointer select-none">
              <input 
                type="radio" 
                :value="index" 
                v-model="form.verification_phone_index"
                class="w-4 h-4 text-primary focus:ring-primary bg-black/20 border-border"
              />
              <span class="text-xs text-text-muted">Verif.</span>
            </label>

            <button 
              @click.prevent="quitarTelefonoAdicional(index)"
              class="text-red-400 hover:text-red-500 p-2 transition-colors"
            >
              <X class="w-4 h-4" />
            </button>
          </div>
          
          <button 
            @click.prevent="agregarTelefonoAdicional"
            class="text-xs text-primary hover:text-primary-hover font-medium flex items-center gap-1 mt-2 transition-colors"
          >
            <Plus class="w-3.5 h-3.5" />
            Añadir teléfono adicional
          </button>
          
          <p class="text-[11px] text-text-muted mt-2">
            * Marca la casilla "Verif." para seleccionar el número que recibirá notificaciones y códigos de verificación por SMS/WhatsApp. Si no se marca ninguno, se usará el número principal.
          </p>
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.email') }}</label>
          <input 
            v-model="form.email" 
            type="email" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('config.direccion') }}</label>
          <input 
            v-model="form.direccion" 
            type="text" 
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
          />
        </div>
        
        <LocationSelects
          v-model:model-country-id="form.pais_id"
          v-model:model-state-id="form.estado_id"
          v-model:model-city-id="form.ciudad_id"
          v-model:city-text="form.ciudad"
        />
      </div>

      <!-- Horarios de Atención -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 md:col-span-2 space-y-5">
        <h3 class="text-lg font-bold border-b border-border/50 pb-3">Horario de Atención</h3>
        
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div 
            v-for="day in diasSemana" 
            :key="day.key" 
            class="bg-black/10 border border-border/50 rounded-xl p-4 flex flex-col justify-between space-y-3"
          >
            <div class="flex items-center justify-between">
              <span class="font-bold text-white text-sm capitalize">{{ day.label }}</span>
              <label class="relative inline-flex items-center cursor-pointer">
                <input 
                  type="checkbox" 
                  v-model="form.horario_apertura[day.key].cerrado" 
                  class="sr-only peer"
                />
                <div class="w-9 h-5 bg-border peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-500"></div>
                <span class="text-xs text-text-muted ml-2">Cerrado</span>
              </label>
            </div>
            
            <div class="flex items-center gap-2 text-xs" v-if="!form.horario_apertura[day.key].cerrado">
              <div class="flex-1">
                <label class="block text-[10px] text-text-muted mb-1">Apertura</label>
                <input 
                  v-model="form.horario_apertura[day.key].inicio" 
                  type="time" 
                  class="w-full bg-black/20 border border-border rounded-lg px-2 py-1 text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent"
                />
              </div>
              <div class="flex-1">
                <label class="block text-[10px] text-text-muted mb-1">Cierre</label>
                <input 
                  v-model="form.horario_apertura[day.key].fin" 
                  type="time" 
                  class="w-full bg-black/20 border border-border rounded-lg px-2 py-1 text-white focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent"
                />
              </div>
            </div>
            <div v-else class="text-xs text-red-400 font-semibold py-2">
              Día no laboral / Cerrado
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Zona Peligrosa -->
    <div class="bg-red-500/5 border border-red-500/20 rounded-2xl p-6 mt-6">
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <h3 class="text-lg font-bold text-red-400">{{ $t('config.zona_peligro') }}</h3>
          <p class="text-sm text-text-muted mt-1">{{ $t('config.aviso_eliminar') }}</p>
        </div>
        <button 
          @click="mostrarModalEliminar = true"
          class="bg-red-500 hover:bg-red-600 text-white px-5 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(239,68,68,0.2)] flex items-center justify-center gap-2 self-start md:self-center">
          <Trash2 class="w-4 h-4" />
          {{ $t('config.eliminar_btn') }}
        </button>
      </div>
    </div>

    <!-- Modal Confirmar Eliminación -->
    <div v-if="mostrarModalEliminar" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-bg-card border border-border rounded-2xl max-w-md w-full p-6 shadow-2xl relative animate-in fade-in zoom-in-95 duration-200">
        <h3 class="text-xl font-bold text-white mb-2">{{ $t('config.modal_eliminar_titulo') }}</h3>
        <p class="text-text-muted text-sm mb-4">
          {{ $t('config.aviso_eliminar') }} {{ $t('config.para_confirmar') }} <strong>{{ form.nombre }}</strong>
        </p>

        <input 
          v-model="nombreConfirmacion" 
          type="text" 
          :placeholder="form.nombre" 
          class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-red-500 transition-all mb-4 text-center"
        />

        <div class="flex gap-3">
          <button 
            @click="mostrarModalEliminar = false; nombreConfirmacion = ''" 
            class="flex-1 bg-white/5 hover:bg-white/10 text-white py-3 rounded-xl transition-all text-sm font-medium">
            {{ $t('acciones.cancelar') }}
          </button>
          <button 
            @click="eliminarNegocioDefinitivo" 
            :disabled="nombreConfirmacion !== form.nombre || deleting"
            class="flex-1 bg-red-500 hover:bg-red-600 disabled:opacity-50 text-white py-3 rounded-xl transition-all text-sm font-medium flex justify-center items-center gap-2">
            <Loader2 v-if="deleting" class="w-4 h-4 animate-spin" />
            <Trash2 v-else class="w-4 h-4" />
            {{ deleting ? $t('acciones.eliminando') : $t('acciones.si_eliminar') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { Save, AlertCircle, CheckCircle, Loader2, Trash2, Upload, X, Image as ImageIcon, Plus } from 'lucide-vue-next';
import axios from 'axios';
import { useRouter } from 'vue-router';
import LocationSelects from '../../Components/LocationSelects.vue';

const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const deleting = ref(false);
const successMsg = ref('');
const errorMsg = ref('');

const mostrarModalEliminar = ref(false);
const nombreConfirmacion = ref('');

const logoUrl = ref(null);
const logoPreview = ref(null);
const fileToUpload = ref(null);

const onLogoChange = (e) => {
  const file = e.target.files[0];
  if (!file) return;
  fileToUpload.value = file;
  logoPreview.value = URL.createObjectURL(file);
};

const quitarLogo = () => {
  fileToUpload.value = null;
  logoPreview.value = null;
  logoUrl.value = null;
};

const form = ref({
  nombre: '',
  descripcion: '',
  sitio_web: '',
  telefono: '',
  whatsapp: '',
  email: '',
  direccion: '',
  ciudad: '',
  pais_id: null,
  estado_id: null,
  ciudad_id: null,
  horario_apertura: {
    lun: { inicio: '09:00', fin: '18:00', cerrado: false },
    mar: { inicio: '09:00', fin: '18:00', cerrado: false },
    'mié': { inicio: '09:00', fin: '18:00', cerrado: false },
    jue: { inicio: '09:00', fin: '18:00', cerrado: false },
    vie: { inicio: '09:00', fin: '18:00', cerrado: false },
    'sáb': { inicio: '09:00', fin: '18:00', cerrado: false },
    dom: { inicio: '09:00', fin: '18:00', cerrado: true }
  },
  es_medico: false,
  telefonos_adicionales: [],
  verification_phone_index: null,
  numero_fiscal: ''
});

const diasSemana = [
  { key: 'lun', label: 'Lunes' },
  { key: 'mar', label: 'Martes' },
  { key: 'mié', label: 'Miércoles' },
  { key: 'jue', label: 'Jueves' },
  { key: 'vie', label: 'Viernes' },
  { key: 'sáb', label: 'Sábado' },
  { key: 'dom', label: 'Domingo' }
];

const agregarTelefonoAdicional = () => {
  if (!form.value.telefonos_adicionales) {
    form.value.telefonos_adicionales = [];
  }
  form.value.telefonos_adicionales.push({ number: '', type: 'mobile' });
};

const quitarTelefonoAdicional = (index) => {
  form.value.telefonos_adicionales.splice(index, 1);
  if (form.value.verification_phone_index === index) {
    form.value.verification_phone_index = null;
  } else if (form.value.verification_phone_index > index) {
    form.value.verification_phone_index--;
  }
};

const cargarNegocio = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/negocio');
    const d = res.data.negocio || {};
    logoUrl.value = d.logo_url || d.logo || null;

    const horario = d.horario_apertura || {};
    diasSemana.forEach(day => {
      if (!horario[day.key]) {
        horario[day.key] = { inicio: '09:00', fin: '18:00', cerrado: false };
      } else {
        horario[day.key] = {
          inicio: horario[day.key].inicio || '09:00',
          fin: horario[day.key].fin || '18:00',
          cerrado: Boolean(horario[day.key].cerrado)
        };
      }
    });

    form.value = {
      nombre: d.nombre || '',
      descripcion: d.descripcion || '',
      sitio_web: d.sitio_web || '',
      telefono: d.telefono || '',
      whatsapp: d.whatsapp || '',
      email: d.email || '',
      direccion: d.direccion || '',
      ciudad: d.ciudad || '',
      pais_id: d.pais_id || null,
      estado_id: d.estado_id || null,
      ciudad_id: d.ciudad_id || null,
      horario_apertura: horario,
      es_medico: Boolean(d.es_medico),
      telefonos_adicionales: d.telefonos_adicionales || [],
      verification_phone_index: d.verification_phone_index !== undefined ? d.verification_phone_index : null,
      numero_fiscal: d.numero_fiscal || ''
    };
  } catch (error) {
    console.error("Error al cargar negocio:", error);
    errorMsg.value = "No se pudo cargar la información del negocio.";
  } finally {
    loading.value = false;
  }
};

const guardarCambios = async () => {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    const formData = new FormData();
    Object.keys(form.value).forEach(key => {
      if (key === 'horario_apertura' || key === 'telefonos_adicionales') {
        formData.append(key, JSON.stringify(form.value[key]));
      } else if (form.value[key] !== null && form.value[key] !== undefined) {
        if (typeof form.value[key] === 'boolean') {
           formData.append(key, form.value[key] ? 1 : 0);
        } else {
           formData.append(key, form.value[key]);
        }
      }
    });

    if (fileToUpload.value) {
      formData.append('logo', fileToUpload.value);
    }

    const res = await axios.post('/api/negocio', formData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    });
    
    if (res.data.negocio?.logo_url || res.data.negocio?.logo) {
      logoUrl.value = res.data.negocio.logo_url || res.data.negocio.logo;
    }
    fileToUpload.value = null;
    logoPreview.value = null;

    successMsg.value = 'Configuración guardada exitosamente.';
    setTimeout(() => { successMsg.value = ''; }, 3000);
  } catch (error) {
    console.error("Error al guardar:", error);
    errorMsg.value = error.response?.data?.message || 'Hubo un error al guardar los cambios.';
  } finally {
    saving.value = false;
  }
};

const eliminarNegocioDefinitivo = async () => {
  if (nombreConfirmacion.value !== form.value.nombre) return;
  
  deleting.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.delete('/api/negocio');
    mostrarModalEliminar.value = false;
    
    // Limpiamos token local y mandamos a login
    localStorage.removeItem('token');
    delete axios.defaults.headers.common['Authorization'];
    
    alert(res.data.message);
    router.push('/login');
  } catch (error) {
    console.error("Error al borrar negocio:", error);
    errorMsg.value = error.response?.data?.message || 'Ocurrió un error al eliminar tu negocio.';
    mostrarModalEliminar.value = false;
  } finally {
    deleting.value = false;
    nombreConfirmacion.value = '';
  }
};

onMounted(() => {
  cargarNegocio();
});
</script>
