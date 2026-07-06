<template>
  <div class="min-h-screen bg-bg text-text flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-bg-card backdrop-blur-md border-r border-border hidden md:flex flex-col">
      <div class="p-6 flex items-center gap-3">
        <div v-if="logoNegocio" class="w-8 h-8 rounded-lg overflow-hidden shadow-lg border border-border shrink-0">
          <img :src="logoNegocio" class="w-full h-full object-cover" />
        </div>
        <div v-else :class="['w-8 h-8 rounded-lg flex items-center justify-center shadow-lg shrink-0', theme.badgeBg]">
          <span :class="['font-bold', theme.text]">C</span>
        </div>
        <span class="font-bold text-xl tracking-tight truncate">CitasPro</span>
      </div>

      <!-- Selector de Área (para Administradores o Dueños) -->
      <div v-if="esAdminODueno" class="px-6 pb-4">
        <label class="block text-xs font-bold text-text-muted tracking-wider mb-2">ÁREA ACTUAL</label>
        <CustomSelect 
          :modelValue="areaSeleccionada"
          @update:modelValue="(val) => { areaSeleccionada = val; cambiarArea(); }"
          :options="areasOptions"
          buttonClass="px-3 py-2 text-sm"
        />
      </div>

      <nav class="flex-1 px-4 space-y-2 mt-2">
        <!-- Enlaces dinámicos según el área activa -->
        <router-link :to="'/panel/' + areaSeleccionada" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Calendar class="w-5 h-5" /> {{ $t('sidebar.agenda') }} {{ theme.name }}
        </router-link>
        
        <router-link to="/panel/clientes" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Users class="w-5 h-5" /> {{ areaSeleccionada === 'pro' ? $t('sidebar.clientes') : 'Pacientes' }}
        </router-link>
        
        <div class="pt-6 pb-2 px-4">
          <p class="text-xs font-bold text-text-muted tracking-wider mb-3 px-4">CONFIGURACIÓN</p>
        </div>
        
        <router-link to="/panel/configuracion/negocio" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Settings class="w-5 h-5" /> {{ $t('sidebar.negocio') }}
        </router-link>
        <router-link :to="{ path: '/panel/configuracion/servicios', query: { type: areaSeleccionada } }" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Scissors class="w-5 h-5" /> {{ $t('sidebar.servicios') }}
        </router-link>
        <router-link :to="{ path: '/panel/configuracion/profesionales', query: { type: areaSeleccionada } }" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Briefcase class="w-5 h-5" /> {{ areaSeleccionada === 'pro' ? $t('sidebar.profesionales') : 'Médicos / Staff' }}
        </router-link>
        <router-link to="/panel/configuracion/whatsapp" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <MessageCircle class="w-5 h-5" /> WhatsApp QR
        </router-link>
      </nav>

      <!-- Enlace de Reserva Pública -->
      <div v-if="bookingUrl" class="px-4 pb-4">
        <div class="rounded-xl border border-border bg-black/30 p-3">
          <p class="text-xs font-bold text-text-muted tracking-wider mb-2">🔗 {{ $t('sidebar.enlace_reserva') }}</p>
          <p class="text-xs text-white/70 truncate mb-2 font-mono">{{ bookingUrl }}</p>
          <div class="flex gap-2">
            <button
              @click="copiarEnlace"
              :class="[
                'flex-1 text-xs py-1.5 rounded-lg font-medium transition-all',
                copiado ? 'bg-green-600 text-white' : 'bg-primary/20 text-primary hover:bg-primary/30'
              ]"
            >
              {{ copiado ? '✅ ' + $t('sidebar.copiado') : '📋 ' + $t('sidebar.copiar') }}
            </button>
            <a
              :href="bookingUrl"
              target="_blank"
              class="flex-1 text-xs py-1.5 rounded-lg font-medium bg-white/5 text-text-muted hover:bg-white/10 text-center transition-all"
            >
              🔍 {{ $t('sidebar.ver') }}
            </a>
          </div>
        </div>
      </div>

      <div class="p-4 border-t border-border mt-auto">
        <div class="px-4 py-2 mb-3 rounded-xl bg-white/5 text-xs text-text-muted">
          <p class="font-medium text-white truncate">{{ userProfile?.nombre }} {{ userProfile?.apellido }}</p>
          <p class="capitalize">{{ userProfile?.rol }} • {{ userProfile?.type }}</p>
        </div>
        <button @click="logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all w-full text-left">
          <LogOut class="w-5 h-5" /> {{ $t('sidebar.cerrar_sesion') }}
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <header class="h-16 border-b border-border bg-bg-card/50 backdrop-blur-sm flex items-center justify-between px-6 sticky top-0 z-10">
        <h1 class="text-lg font-semibold">{{ $route.name }}</h1>
        <div class="flex items-center gap-3">
          <!-- Selector móvil -->
          <div v-if="esAdminODueno" class="w-28">
            <CustomSelect 
              :modelValue="areaSeleccionada"
              @update:modelValue="(val) => { areaSeleccionada = val; cambiarArea(); }"
              :options="areasMobileOptions"
              buttonClass="px-2 py-1.5 text-xs"
            />
          </div>
          <LanguageSwitcher />
        </div>
      </header>
      
      <div class="flex-1 overflow-auto p-6">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Calendar, Users, MessageCircle, LogOut, Settings, Scissors, Briefcase } from 'lucide-vue-next';
import { useRouter, useRoute } from 'vue-router';
import { ref, onMounted, computed, watch } from 'vue';
import axios from 'axios';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import CustomSelect from '../Components/CustomSelect.vue';

const areasOptions = [
  { value: 'pro', label: 'Citas Pro (General)' },
  { value: 'medical', label: 'Clínica Médica' },
  { value: 'dental', label: 'Clínica Dental' }
];

const areasMobileOptions = [
  { value: 'pro', label: 'Pro' },
  { value: 'medical', label: 'Médica' },
  { value: 'dental', label: 'Dental' }
];

const router = useRouter();
const route = useRoute();

const userProfile = ref(null);
const esAdminODueno = ref(false);
const areaSeleccionada = ref('pro');
const bookingUrl = ref(null);
const logoNegocio = ref(null);
const copiado = ref(false);

const theme = computed(() => {
  const themes = {
    pro: {
      name: 'Pro',
      text: 'text-primary',
      badgeBg: 'bg-primary/20'
    },
    medical: {
      name: 'Médica',
      text: 'text-teal-400',
      badgeBg: 'bg-teal-500/20'
    },
    dental: {
      name: 'Dental',
      text: 'text-sky-400',
      badgeBg: 'bg-sky-500/20'
    }
  };
  return themes[areaSeleccionada.value] || themes.pro;
});

const loadProfile = async () => {
  try {
    const res = await axios.get('/api/auth/me');
    if (res.data.success && res.data.user) {
      userProfile.value = res.data.user;
      esAdminODueno.value = ['dueño', 'admin'].includes(res.data.user.rol);
      
      // Si no es admin/dueño, forzamos su área específica
      if (!esAdminODueno.value) {
        areaSeleccionada.value = res.data.user.type || 'pro';
      } else {
        // Si es admin/dueño, iniciamos en su tipo o por defecto 'pro'
        areaSeleccionada.value = res.data.user.type || 'pro';
      }
      
      // Ejecutar redirección inicial si está en el root del panel
      redireccionarRaiz();
    }
  } catch (e) {
    console.error('Error fetching profile config', e);
  }
};

const redireccionarRaiz = () => {
  if (route.path === '/panel' || route.path === '/panel/') {
    router.push('/panel/' + areaSeleccionada.value);
  }
};

const cambiarArea = () => {
  router.push('/panel/' + areaSeleccionada.value);
};

// ── Enlace de Reserva Pública ──────────────────────────────────────────────
const loadBookingUrl = async () => {
  try {
    const res = await axios.get('/api/negocio');
    if (res.data.success && res.data.negocio) {
      if (res.data.negocio.booking_url) {
        bookingUrl.value = res.data.negocio.booking_url;
      }
      if (res.data.negocio.logo) {
        logoNegocio.value = res.data.negocio.logo;
      }
    }
  } catch (e) {
    // Silencioso si falla (no bloqueante)
  }
};

const copiarEnlace = async () => {
  if (!bookingUrl.value) return;
  try {
    await navigator.clipboard.writeText(bookingUrl.value);
  } catch {
    // Fallback para navegadores sin clipboard API
    const el = document.createElement('textarea');
    el.value = bookingUrl.value;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }
  copiado.value = true;
  setTimeout(() => (copiado.value = false), 2500);
};


// Monitorizar la ruta para redireccionar si es necesario
watch(() => route.path, () => {
  if (route.path === '/panel' || route.path === '/panel/') {
    redireccionarRaiz();
  } else {
    // Si la ruta cambia y contiene el segmento de área, actualizar selector
    const segments = route.path.split('/');
    if (segments.includes('medical')) areaSeleccionada.value = 'medical';
    else if (segments.includes('dental')) areaSeleccionada.value = 'dental';
    else if (segments.includes('pro')) areaSeleccionada.value = 'pro';
  }
});

onMounted(() => {
  loadProfile();
  loadBookingUrl();
});


const logout = async () => {
  try {
    await axios.post('/api/auth/logout');
    localStorage.removeItem('token');
    router.push('/login');
  } catch (error) {
    console.error('Logout failed', error);
  }
};
</script>
