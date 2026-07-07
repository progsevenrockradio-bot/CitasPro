<template>
  <div class="space-y-6 max-w-4xl">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold">Mi Horario y Calendario</h2>
        <p class="text-text-muted text-sm mt-1">Configura tus días de atención al cliente y sincroniza tus citas con Google Calendar.</p>
      </div>
      <button 
        @click="guardarHorario"
        :disabled="loading || saving"
        class="bg-primary hover:bg-primary-hover text-white px-6 py-2.5 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)] flex items-center gap-2"
      >
        <Loader2 v-if="saving" class="w-4 h-4 animate-spin" />
        <Save v-else class="w-4 h-4" />
        Guardar Horario
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
      <p>Cargando configuración...</p>
    </div>

    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Sección de Horarios de Trabajo -->
      <div class="lg:col-span-2 bg-bg-card border border-border rounded-2xl p-6 space-y-6">
        <div class="flex items-center justify-between border-b border-border/50 pb-3">
          <h3 class="text-lg font-bold">Horas de Atención Disponible</h3>
          <span class="text-xs text-text-muted">Horarios individuales para tu agenda</span>
        </div>

        <div class="space-y-4">
          <div 
            v-for="day in diasSemana" 
            :key="day.key" 
            class="flex flex-col sm:flex-row sm:items-center justify-between p-4 rounded-xl bg-black/10 border border-border/40 gap-4"
          >
            <div class="flex items-center gap-3 min-w-[120px]">
              <!-- Checkbox de activo -->
              <input 
                type="checkbox" 
                v-model="horario[day.key].activo" 
                class="w-4 h-4 rounded text-primary focus:ring-primary bg-black/20 border-border"
              />
              <span :class="['font-semibold text-sm capitalize', horario[day.key].activo ? 'text-white' : 'text-text-muted']">
                {{ day.label }}
              </span>
            </div>

            <div v-if="horario[day.key].activo" class="flex flex-1 items-center gap-2 max-w-xs">
              <input 
                type="time" 
                v-model="horario[day.key].inicio" 
                class="flex-1 bg-black/20 border border-border rounded-lg px-3 py-1.5 text-white text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent"
              />
              <span class="text-text-muted text-xs">a</span>
              <input 
                type="time" 
                v-model="horario[day.key].fin" 
                class="flex-1 bg-black/20 border border-border rounded-lg px-3 py-1.5 text-white text-sm focus:outline-none focus:ring-1 focus:ring-primary focus:border-transparent"
              />
            </div>
            
            <div v-else class="flex-1 text-right text-xs text-text-muted italic py-1.5">
              No laborable
            </div>
          </div>
        </div>
      </div>

      <!-- Sección de Google Calendar -->
      <div class="bg-bg-card border border-border rounded-2xl p-6 space-y-6 flex flex-col justify-between">
        <div>
          <div class="flex items-center gap-3 border-b border-border/50 pb-3 mb-4">
            <span class="text-2xl">📅</span>
            <h3 class="text-lg font-bold">Google Calendar</h3>
          </div>
          
          <p class="text-sm text-text-muted leading-relaxed">
            Sincroniza tus citas de CitasPro automáticamente con tu calendario de Google. Las citas creadas, actualizadas o canceladas se reflejarán en tiempo real en tu agenda personal.
          </p>

          <!-- Estado de la Conexión -->
          <div class="mt-6 p-4 rounded-xl border flex items-center gap-3" :class="googleConnected ? 'bg-green-500/5 border-green-500/20 text-green-400' : 'bg-black/20 border-border text-text-muted'">
            <div class="w-8 h-8 rounded-lg flex items-center justify-center" :class="googleConnected ? 'bg-green-500/10' : 'bg-white/5'">
              <span class="text-sm">{{ googleConnected ? '✅' : '💤' }}</span>
            </div>
            <div>
              <p class="text-xs font-semibold">Estado de la cuenta</p>
              <p class="text-sm font-bold text-white mt-0.5">
                {{ googleConnected ? 'Calendario Sincronizado' : 'Sin vincular' }}
              </p>
            </div>
          </div>
        </div>

        <div class="pt-6 border-t border-border/50 mt-6 space-y-3">
          <button 
            v-if="!googleConnected"
            @click="conectarGoogle"
            :disabled="connectingGoogle"
            class="w-full bg-white text-black hover:bg-gray-200 py-3 rounded-xl font-bold transition-all flex items-center justify-center gap-2 active:scale-95 cursor-pointer"
          >
            <Loader2 v-if="connectingGoogle" class="w-4 h-4 animate-spin" />
            <span v-else class="text-base">🌐</span>
            Vincular Google Calendar
          </button>
          
          <button 
            v-else
            @click="desconectarGoogle"
            :disabled="connectingGoogle"
            class="w-full bg-red-500/10 hover:bg-red-500/20 text-red-400 py-3 rounded-xl font-semibold transition-all flex items-center justify-center gap-2 active:scale-95 border border-red-500/20 cursor-pointer"
          >
            <Loader2 v-if="connectingGoogle" class="w-4 h-4 animate-spin" />
            Desconectar Calendario
          </button>

          <!-- Aviso de advertencia de verificación de Google para el usuario final -->
          <div v-if="!googleConnected" class="p-3 rounded-xl bg-yellow-500/5 border border-yellow-500/20 text-[11px] text-yellow-400 leading-normal space-y-1">
            <p class="font-bold flex items-center gap-1">⚠️ Aviso importante durante la vinculación:</p>
            <p>Es posible que Google muestre una pantalla de advertencia indicando que <i>"Google no ha verificado esta aplicación"</i>.</p>
            <p>Es totalmente seguro. Para continuar, haz clic abajo a la izquierda en <b>"Configuración Avanzada" (Advanced)</b> y luego selecciona <b>"Ir a citaspro.app (no seguro)"</b>.</p>
          </div>

          <p class="text-[11px] text-text-muted text-center mt-2">
            Serás redirigido a las páginas de autorización de Google para conceder permisos de acceso a tu agenda.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Save, Loader2, CheckCircle, AlertCircle } from 'lucide-vue-next';
import axios from 'axios';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const saving = ref(false);
const connectingGoogle = ref(false);
const googleConnected = ref(false);

const successMsg = ref('');
const errorMsg = ref('');

const diasSemana = [
  { key: 'lunes', label: 'Lunes' },
  { key: 'martes', label: 'Martes' },
  { key: 'miercoles', label: 'Miércoles' },
  { key: 'jueves', label: 'Jueves' },
  { key: 'viernes', label: 'Viernes' },
  { key: 'sabado', label: 'Sábado' },
  { key: 'domingo', label: 'Domingo' }
];

const horario = ref({
  lunes: { activo: true, inicio: '09:00', fin: '18:00' },
  martes: { activo: true, inicio: '09:00', fin: '18:00' },
  miercoles: { activo: true, inicio: '09:00', fin: '18:00' },
  jueves: { activo: true, inicio: '09:00', fin: '18:00' },
  viernes: { activo: true, inicio: '09:00', fin: '18:00' },
  sabado: { activo: false, inicio: '09:00', fin: '18:00' },
  domingo: { activo: false, inicio: '09:00', fin: '18:00' }
});

const loadData = async () => {
  loading.value = true;
  errorMsg.value = '';
  try {
    // 1. Cargar horario de apertura
    const resHorario = await axios.get('/api/horarios');
    if (resHorario.data.success && resHorario.data.horario) {
      // Normalizar campos y vacíos
      const data = resHorario.data.horario;
      diasSemana.forEach(d => {
        if (data[d.key]) {
          horario.value[d.key] = {
            activo: Boolean(data[d.key].activo),
            inicio: data[d.key].inicio || '09:00',
            fin: data[d.key].fin || '18:00'
          };
        }
      });
    }

    // 2. Cargar perfil para ver Google Calendar Status
    const resProfile = await axios.get('/api/auth/me');
    if (resProfile.data.success && resProfile.data.user) {
      googleConnected.value = Boolean(resProfile.data.user.google_calendar_connected);
    }
  } catch (error) {
    console.error("Error cargando configuración de horarios/perfil:", error);
    errorMsg.value = "No se pudieron cargar los datos de horarios o vinculación.";
  } finally {
    loading.value = false;
  }
};

const guardarHorario = async () => {
  saving.value = true;
  successMsg.value = '';
  errorMsg.value = '';
  try {
    const res = await axios.put('/api/horarios', {
      horario: horario.value
    });
    if (res.data.success) {
      successMsg.value = 'Horario de atención guardado correctamente.';
      setTimeout(() => { successMsg.value = ''; }, 3000);
    }
  } catch (error) {
    console.error("Error al guardar horario:", error);
    errorMsg.value = error.response?.data?.message || 'Ocurrió un error al guardar los cambios.';
  } finally {
    saving.value = false;
  }
};

const conectarGoogle = async () => {
  connectingGoogle.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.get('/api/medico/google/redirect');
    if (res.data.success && res.data.url) {
      // Redirigir al flujo OAuth de Google
      window.location.href = res.data.url;
    } else {
      errorMsg.value = 'No se pudo iniciar el enlace con Google Calendar.';
      connectingGoogle.value = false;
    }
  } catch (error) {
    console.error("Error al redirigir a Google:", error);
    errorMsg.value = 'Error al contactar con el servidor de Google.';
    connectingGoogle.value = false;
  }
};

const desconectarGoogle = async () => {
  if (!confirm("¿Seguro que deseas desvincular Google Calendar? No se sincronizarán más eventos.")) return;
  connectingGoogle.value = true;
  errorMsg.value = '';
  try {
    const res = await axios.delete('/api/medico/google/disconnect');
    if (res.data.success) {
      googleConnected.value = false;
      successMsg.value = 'Google Calendar desvinculado con éxito.';
      setTimeout(() => { successMsg.value = ''; }, 3000);
    }
  } catch (error) {
    console.error("Error al desconectar Google:", error);
    errorMsg.value = 'Error al desvincular la cuenta.';
  } finally {
    connectingGoogle.value = false;
  }
};

// Capturar parámetros de callback de Google al montar
onMounted(() => {
  loadData();

  if (route.query.google_success) {
    successMsg.value = "¡Google Calendar conectado con éxito!";
    setTimeout(() => { successMsg.value = ''; }, 5000);
    
    // Limpiar query string
    router.replace({ query: {} });
  } else if (route.query.google_error) {
    errorMsg.value = `Error de conexión con Google: ${route.query.google_error}`;
    router.replace({ query: {} });
  }
});
</script>
