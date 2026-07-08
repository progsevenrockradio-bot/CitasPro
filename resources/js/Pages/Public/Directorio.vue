<template>
  <div class="min-h-screen bg-gray-950 text-white">
    <!-- Header CitasPro -->
    <header class="bg-gray-900/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-20">
      <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Logo Real -->
          <img src="/images/logo.png" alt="CitasPro Logo" class="h-8 object-contain" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex';" />
          <!-- Fallback en caso de que la imagen no cargue o no esté aún -->
          <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-black text-sm shadow-lg hidden">C</div>
          
          <span class="font-bold text-lg tracking-tight">CitasPro</span>
          <span class="text-gray-500 text-sm hidden sm:inline ml-1">/ Directorio</span>
        </div>
        <a href="/login" class="text-sm font-semibold text-indigo-400 hover:text-indigo-300 transition-colors bg-white/5 px-4 py-2 rounded-full border border-white/10">
          ¿Eres un negocio?
        </a>
      </div>
    </header>

    <!-- Hero Rediseñado (Basado en Imagen 16) -->
    <div class="bg-[url('/images/bg-directorio.png')] bg-cover bg-center relative">
      <div class="absolute inset-0 bg-gradient-to-b from-indigo-950/90 via-gray-950/95 to-gray-950 z-0"></div>
      
      <div class="relative z-10 py-16 px-4 text-center">
        <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tighter">
          Directorio de Negocios <span class="text-indigo-400 block sm:inline">CitasPro</span>
        </h1>
        <p class="text-gray-400 text-lg md:text-xl mb-10 max-w-2xl mx-auto font-light">
          Encuentra y reserva al instante con los mejores profesionales cerca de ti.
        </p>

        <!-- Buscador -->
        <div class="max-w-2xl mx-auto flex gap-2 mb-12">
          <input
            v-model="busqueda"
            @input="buscarDebounced"
            type="text"
            placeholder="Busca peluquerías, dentistas, entrenadores..."
            class="flex-1 bg-white/5 border border-white/10 rounded-xl px-5 py-4 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 focus:bg-white/10 transition-all shadow-inner text-lg"
          />
          <button @click="buscar()" class="px-8 py-4 bg-indigo-600 hover:bg-indigo-500 rounded-xl font-bold transition-all shadow-lg shadow-indigo-600/20 text-lg">
            Buscar
          </button>
        </div>

        <!-- Categorías Estilo "Iconos Redondos" (Basado en Imagen 16) -->
        <div class="flex flex-wrap justify-center gap-6 md:gap-10 mt-4 max-w-4xl mx-auto">
          <button
            @click="filtroCategoria = null; buscar()"
            class="group flex flex-col items-center gap-3 transition-transform hover:scale-105"
          >
            <div :class="['w-16 h-16 md:w-20 md:h-20 rounded-full flex items-center justify-center text-2xl md:text-3xl transition-all shadow-lg', !filtroCategoria ? 'bg-indigo-600 text-white shadow-indigo-500/40 border-2 border-indigo-400' : 'bg-white/5 text-gray-300 hover:bg-white/10 border border-white/10']">
              ✦
            </div>
            <span :class="['text-xs md:text-sm font-bold tracking-wide uppercase', !filtroCategoria ? 'text-indigo-400' : 'text-gray-400']">Todos</span>
          </button>
          
          <button
            v-for="cat in categorias"
            :key="cat.id"
            @click="filtroCategoria = cat.id; buscar()"
            class="group flex flex-col items-center gap-3 transition-transform hover:scale-105"
          >
            <div :class="['w-16 h-16 md:w-20 md:h-20 rounded-full flex items-center justify-center text-2xl md:text-3xl transition-all shadow-lg', filtroCategoria === cat.id ? 'bg-indigo-600 text-white shadow-indigo-500/40 border-2 border-indigo-400' : 'bg-white/5 text-gray-300 hover:bg-white/10 border border-white/10']">
              {{ cat.icono }}
            </div>
            <span :class="['text-xs md:text-sm font-bold tracking-wide uppercase', filtroCategoria === cat.id ? 'text-indigo-400' : 'text-gray-400']">{{ cat.nombre }}</span>
          </button>
        </div>

        <!-- Tags debajo de categorías -->
        <div class="flex flex-wrap justify-center gap-3 mt-12">
          <span class="px-4 py-1.5 rounded-full bg-gray-900/50 text-xs font-semibold text-gray-300 border border-white/5 shadow-sm">✓ Verificados</span>
          <span class="px-4 py-1.5 rounded-full bg-gray-900/50 text-xs font-semibold text-gray-300 border border-white/5 shadow-sm">✓ Reserva Inmediata</span>
          <span class="px-4 py-1.5 rounded-full bg-gray-900/50 text-xs font-semibold text-gray-300 border border-white/5 shadow-sm">✓ Recomendados</span>
        </div>
      </div>
    </div>

    <!-- Lista de Negocios -->
    <div class="max-w-6xl mx-auto px-4 py-12 relative z-10">
      <!-- Loading -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div v-for="i in 8" :key="i" class="h-[400px] rounded-2xl bg-white/5 animate-pulse border border-white/5"></div>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="negocios.length === 0" class="text-center py-20 bg-gray-900/50 rounded-3xl border border-white/5">
        <p class="text-6xl mb-6">🔍</p>
        <h3 class="text-2xl font-black text-white mb-2">Sin resultados</h3>
        <p class="text-gray-400 text-lg">Intenta con otro término de búsqueda o categoría.</p>
      </div>

      <!-- Grid de Negocios (Tarjetas estilo vertical Imagen 13) -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
        <div
          v-for="negocio in negocios"
          :key="negocio.id"
          class="group bg-[#1a1f35] border border-indigo-500/20 rounded-xl overflow-hidden hover:border-indigo-400 hover:shadow-[0_0_30px_rgba(99,102,241,0.2)] transition-all duration-300 flex flex-col relative"
        >
          <!-- Decoración diagonal de fondo estilo "Best Dressed" -->
          <div class="absolute inset-0 pointer-events-none overflow-hidden rounded-xl">
             <svg class="absolute w-[200%] h-[200%] top-[-50%] left-[-50%] opacity-5 text-indigo-400 group-hover:text-indigo-300 transition-colors" viewBox="0 0 100 100" preserveAspectRatio="none">
               <line x1="0" y1="0" x2="100" y2="100" stroke="currentColor" stroke-width="0.5" />
               <line x1="100" y1="0" x2="0" y2="100" stroke="currentColor" stroke-width="0.5" />
             </svg>
          </div>

          <!-- Banner superior del negocio -->
          <div
            class="h-28 relative overflow-hidden flex-shrink-0 border-b border-indigo-500/20"
            :style="`background: linear-gradient(135deg, ${negocio.categoria?.color_hex || '#3730a3'}40, #111827)`"
          >
            <div class="absolute inset-0 opacity-40 group-hover:opacity-60 transition-opacity mix-blend-overlay" :style="`background-color: ${negocio.categoria?.color_hex || '#4f46e5'}`"></div>
          </div>

          <!-- Logo flotante en el centro superior -->
          <div class="absolute top-14 left-1/2 transform -translate-x-1/2 w-20 h-20 rounded-full border-[3px] border-[#1a1f35] overflow-hidden bg-gray-800 flex items-center justify-center shadow-xl z-10">
             <img v-if="negocio.logo" :src="negocio.logo" :alt="negocio.nombre" class="w-full h-full object-cover" />
             <span v-else class="text-3xl font-black text-white">{{ negocio.nombre?.charAt(0) }}</span>
          </div>

          <div class="pt-10 pb-6 px-5 text-center flex-grow flex flex-col items-center relative z-10">
            <h3 class="font-black text-white text-xl leading-tight group-hover:text-indigo-300 transition-colors tracking-wide">{{ negocio.nombre }}</h3>
            <div class="flex items-center gap-1 mt-2">
               <span class="w-6 border-t border-indigo-500/50"></span>
               <p v-if="negocio.categoria" class="text-indigo-400 font-bold tracking-[0.2em] uppercase text-[10px]">
                 {{ negocio.categoria.nombre }}
               </p>
               <span class="w-6 border-t border-indigo-500/50"></span>
            </div>

            <!-- Sección "Fecha" estilo Banner 13 ("28 November") -->
            <div class="mt-8 mb-6 flex flex-col items-center justify-center">
              <span class="text-6xl font-black text-white tabular-nums tracking-tighter drop-shadow-md">{{ new Date().getDate().toString().padStart(2, '0') }}</span>
              <span class="text-sm font-bold text-red-400 uppercase tracking-widest mt-1">{{ getMesActual() }}</span>
            </div>
            
            <p v-if="negocio.ciudad" class="text-[11px] text-gray-400 mb-6 font-medium tracking-wide">
              {{ negocio.ciudad }}<br/>
              <span class="text-gray-500">Reservas Online</span>
            </p>
            <p v-else class="text-[11px] text-gray-400 mb-6 font-medium tracking-wide">
              Horario Flexible<br/>
              <span class="text-gray-500">Reservas Online</span>
            </p>

            <div class="mt-auto w-full pt-4 border-t border-indigo-500/20">
              <a
                :href="`/${negocio.slug}/book`"
                class="block w-full py-3.5 bg-red-500 hover:bg-red-600 text-white rounded-sm font-bold uppercase tracking-[0.15em] text-sm transition-all text-center shadow-[0_4px_14px_rgba(239,68,68,0.4)]"
              >
                Pedir Cita
              </a>
            </div>
          </div>
        </div>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-16">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="cargarPagina(page)"
          :class="[
            'w-12 h-12 rounded-xl text-sm font-black transition-all shadow-md',
            page === pagination.current_page
              ? 'bg-indigo-600 text-white shadow-indigo-600/30'
              : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white border border-white/5'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Footer mínimo -->
    <footer class="border-t border-white/5 py-10 bg-black/40 text-center text-gray-500 text-sm">
      <div class="flex items-center justify-center gap-2 mb-4 opacity-50 grayscale">
          <img src="/images/logo.png" alt="CitasPro Logo" class="h-6 object-contain" @error="$event.target.style.display='none'" />
      </div>
      <p>{{ $t('directorio.footer') }}</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const negocios = ref([]);
const categorias = ref([]);
const loading = ref(true);
const busqueda = ref('');
const filtroCategoria = ref(null);
const pagination = ref(null);
let debounceTimer = null;

onMounted(async () => {
  await Promise.all([cargarCategorias(), buscar()]);
});

const getMesActual = () => {
  const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
  return meses[new Date().getMonth()];
};

const cargarCategorias = async () => {
  try {
    const res = await axios.get('/api/categorias');
    categorias.value = res.data || [];
  } catch {
    categorias.value = [];
  }
};

const buscar = async (pagina = 1) => {
  loading.value = true;
  try {
    const res = await axios.get('/api/directorio', {
      params: {
        q: busqueda.value || undefined,
        categoria_id: filtroCategoria.value || undefined,
        page: pagina,
      },
    });
    if (res.data.success) {
      negocios.value = res.data.data.data;
      pagination.value = res.data.data;
    }
  } catch {
    negocios.value = [];
  } finally {
    loading.value = false;
  }
};

const buscarDebounced = () => {
  clearTimeout(debounceTimer);
  debounceTimer = setTimeout(() => buscar(), 400);
};

const cargarPagina = (page) => buscar(page);
</script>
