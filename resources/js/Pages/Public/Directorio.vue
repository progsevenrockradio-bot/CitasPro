<template>
  <div class="min-h-screen bg-gray-950 text-white">
    <!-- Header CitasPro -->
    <header class="bg-gray-900/80 backdrop-blur-md border-b border-white/10 sticky top-0 z-20">
      <div class="max-w-5xl mx-auto px-4 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-black text-sm shadow-lg">C</div>
          <span class="font-bold text-lg tracking-tight">CitasPro</span>
          <span class="text-gray-500 text-sm hidden sm:inline">/ Directorio</span>
        </div>
        <a href="/login" class="text-sm text-indigo-400 hover:text-indigo-300 transition-colors">
          {{ $t('directorio.eres_negocio') }}
        </a>
      </div>
    </header>

    <!-- Hero -->
    <div class="bg-gradient-to-b from-indigo-950/50 to-gray-950 py-16 px-4 text-center">
      <h1 class="text-4xl md:text-5xl font-black mb-4">
        {{ $t('directorio.reserva_cita') }} <span class="text-indigo-400">{{ $t('directorio.online') }}</span>
      </h1>
      <p class="text-gray-400 text-lg mb-8 max-w-xl mx-auto">
        {{ $t('directorio.subtitulo') }}
      </p>

      <!-- Buscador -->
      <div class="max-w-xl mx-auto flex gap-2">
        <input
          v-model="busqueda"
          @input="buscarDebounced"
          type="text"
          :placeholder="$t('directorio.buscar_placeholder')"
          class="flex-1 bg-white/10 border border-white/20 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 transition-all"
        />
        <button @click="buscar" class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-xl font-bold transition-all">
          {{ $t('directorio.buscar') }}
        </button>
      </div>

      <!-- Filtros por categoría -->
      <div class="flex flex-wrap justify-center gap-2 mt-6">
        <button
          @click="filtroCategoria = null; buscar()"
          :class="['px-4 py-2 rounded-full text-sm font-medium transition-all', !filtroCategoria ? 'bg-indigo-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10']"
        >
          {{ $t('directorio.todos') }}
        </button>
        <button
          v-for="cat in categorias"
          :key="cat.id"
          @click="filtroCategoria = cat.id; buscar()"
          :class="['px-4 py-2 rounded-full text-sm font-medium transition-all', filtroCategoria === cat.id ? 'bg-indigo-600 text-white' : 'bg-white/5 text-gray-400 hover:bg-white/10']"
        >
          {{ cat.icono }} {{ cat.nombre }}
        </button>
      </div>
    </div>

    <!-- Lista de Negocios -->
    <div class="max-w-5xl mx-auto px-4 py-10">
      <!-- Loading -->
      <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <div v-for="i in 6" :key="i" class="h-52 rounded-2xl bg-white/5 animate-pulse"></div>
      </div>

      <!-- Sin resultados -->
      <div v-else-if="negocios.length === 0" class="text-center py-20">
        <p class="text-5xl mb-4">🔍</p>
        <h3 class="text-xl font-bold text-white mb-2">{{ $t('directorio.sin_resultados') }}</h3>
        <p class="text-gray-400">{{ $t('directorio.intenta_otro') }}</p>
      </div>

      <!-- Grid de Negocios -->
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <a
          v-for="negocio in negocios"
          :key="negocio.id"
          :href="`/${negocio.slug}/book`"
          class="group bg-white/5 border border-white/10 rounded-2xl overflow-hidden hover:border-indigo-500/50 hover:shadow-[0_0_20px_rgba(99,102,241,0.15)] transition-all duration-300 block"
        >
          <!-- Cover / Gradient -->
          <div
            class="h-32 relative overflow-hidden"
            :style="`background: linear-gradient(135deg, ${negocio.categoria?.color_hex || '#6366f1'}33, #0f0f1a)`"
          >
            <div class="absolute inset-0 opacity-20 group-hover:opacity-30 transition-opacity" :style="`background-color: ${negocio.categoria?.color_hex || '#6366f1'}`"></div>
            <!-- Logo -->
            <div class="absolute bottom-3 left-4 w-14 h-14 rounded-xl border-2 border-white/20 overflow-hidden bg-gray-800 flex items-center justify-center shadow-xl">
              <img v-if="negocio.logo" :src="negocio.logo" :alt="negocio.nombre" class="w-full h-full object-cover" />
              <span v-else class="text-xl font-black text-white">{{ negocio.nombre?.charAt(0) }}</span>
            </div>
          </div>

          <div class="p-4">
            <h3 class="font-bold text-white text-lg leading-tight group-hover:text-indigo-300 transition-colors">{{ negocio.nombre }}</h3>
            <p v-if="negocio.categoria" class="text-xs text-gray-400 mt-0.5">{{ negocio.categoria.icono }} {{ negocio.categoria.nombre }}</p>
            <p v-if="negocio.ciudad" class="text-xs text-gray-500 mt-1">📍 {{ negocio.ciudad }}</p>

            <div class="mt-3 pt-3 border-t border-white/5 flex items-center justify-between">
              <span class="text-xs text-indigo-400 font-medium group-hover:text-indigo-300 transition-colors">
                {{ $t('directorio.ver_disponibilidad') }}
              </span>
              <span v-if="negocio.booking_activo" class="w-2 h-2 rounded-full bg-green-500 animate-pulse" title="Acepta reservas online"></span>
            </div>
          </div>
        </a>
      </div>

      <!-- Paginación -->
      <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-10">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="cargarPagina(page)"
          :class="[
            'w-10 h-10 rounded-xl text-sm font-bold transition-all',
            page === pagination.current_page
              ? 'bg-indigo-600 text-white'
              : 'bg-white/5 text-gray-400 hover:bg-white/10'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>

    <!-- Footer mínimo -->
    <footer class="border-t border-white/5 py-8 text-center text-gray-600 text-sm">
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
