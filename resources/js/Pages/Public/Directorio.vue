<template>
  <div class="min-h-screen bg-bg text-text font-sans selection:bg-primary selection:text-white overflow-x-hidden">
    <!-- Header CitasPro -->
    <header class="bg-bg/80 backdrop-blur-xl border-b border-border sticky top-0 z-40 transition-all duration-300">
      <div class="max-w-[95%] mx-auto px-4 md:px-8 h-20 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <!-- Logo Real -->
          <img src="/images/logo.png" alt="CitasPro Logo" class="h-9 object-contain" @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex';" />
          <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-primary to-secondary flex items-center justify-center font-black text-white shadow-lg shadow-primary/20">C</div>
          <span class="font-black text-xl tracking-tight bg-gradient-to-r from-white via-gray-100 to-gray-400 bg-clip-text text-transparent">CitasPro</span>
          <span class="text-text-secondary text-sm hidden sm:inline ml-2 border-l border-border pl-3">Directorio Editorial</span>
        </div>
        
        <div class="flex items-center gap-4">
          <!-- Botón de Ubicación Rápido -->
          <button 
            @click="toggleLocationPanel" 
            class="flex items-center gap-2 px-4 py-2 rounded-full bg-white/5 hover:bg-white/10 border border-border text-xs font-semibold text-text-secondary hover:text-white transition-all cursor-pointer"
          >
            <MapPin class="w-3.5 h-3.5 text-primary" />
            <span>{{ locationText }}</span>
          </button>

          <a href="/login" class="text-xs font-bold text-primary hover:text-white transition-all bg-primary/10 hover:bg-primary px-5 py-2.5 rounded-full border border-primary/20 shadow-lg hover:shadow-primary/20">
            ¿Eres un negocio?
          </a>
        </div>
      </div>
    </header>

    <!-- Contenido Principal -->
    <main class="max-w-[95%] mx-auto px-4 md:px-8 py-10 relative">
      <!-- Decoraciones de Fondo Sutiles -->
      <div class="absolute top-1/4 left-1/3 w-96 h-96 bg-indigo-500/5 rounded-full blur-[120px] pointer-events-none"></div>
      <div class="absolute bottom-1/3 right-1/4 w-[500px] h-[500px] bg-violet-600/5 rounded-full blur-[160px] pointer-events-none"></div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
        
        <!-- ================= COLUMNA IZQUIERDA ================= -->
        <section class="lg:col-span-4 lg:sticky lg:top-28 space-y-8">
          <div>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight leading-none text-white uppercase">
              Directorio de <br/>
              <span class="bg-gradient-to-r from-primary via-secondary to-accent bg-clip-text text-transparent">Negocios</span>
            </h1>
            <p class="text-text-secondary text-sm mt-3 font-light leading-relaxed max-w-sm">
              Una cuidada selección editorial de los mejores profesionales y centros. Reserva de forma inmediata.
            </p>
          </div>

          <!-- Selector de Ubicación Expandible -->
          <div v-if="showLocationPanel" class="bg-bg-card border border-border rounded-2xl p-5 space-y-4 animate-fade-in">
            <div class="flex items-center justify-between border-b border-border-sutil pb-2">
              <span class="text-xs font-bold uppercase text-text-secondary tracking-wider">Configurar Ubicación</span>
              <button @click="showLocationPanel = false" class="text-text-secondary hover:text-white transition-colors">
                <X class="w-4 h-4" />
              </button>
            </div>

            <!-- Selección de País -->
            <div class="space-y-1">
              <label class="text-[10px] uppercase font-bold text-text-secondary tracking-wide">País</label>
              <CustomSelect 
                v-model="manualPais"
                :options="paisesOptions"
                placeholder="Seleccionar País"
                buttonClass="px-4 py-2.5 bg-black/40 border border-border rounded-xl text-sm"
              />
            </div>

            <!-- Selección de Estado/Provincia -->
            <div v-if="manualPais" class="space-y-1">
              <label class="text-[10px] uppercase font-bold text-text-secondary tracking-wide">Estado / Provincia</label>
              <CustomSelect 
                v-model="manualEstado"
                :options="estadosOptions"
                placeholder="Seleccionar Estado"
                buttonClass="px-4 py-2.5 bg-black/40 border border-border rounded-xl text-sm"
              />
            </div>

            <!-- Selección de Ciudad -->
            <div v-if="manualEstado" class="space-y-1">
              <label class="text-[10px] uppercase font-bold text-text-secondary tracking-wide">Ciudad</label>
              <CustomSelect 
                v-model="manualCiudad"
                :options="ciudadesOptions"
                placeholder="Seleccionar Ciudad"
                buttonClass="px-4 py-2.5 bg-black/40 border border-border rounded-xl text-sm"
              />
            </div>

            <div class="flex gap-2 pt-2">
              <button 
                @click="aplicarUbicacionManual" 
                class="flex-1 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-xl text-xs font-bold uppercase tracking-wider transition-all cursor-pointer shadow-lg shadow-primary/10"
              >
                Aplicar
              </button>
              <button 
                @click="resetearUbicacion" 
                class="px-4 py-2.5 bg-white/5 hover:bg-white/10 text-text-secondary hover:text-white rounded-xl text-xs font-semibold transition-all cursor-pointer"
              >
                Auto (GPS/IP)
              </button>
            </div>
          </div>

          <!-- Listado de Categorías Circulares -->
          <div class="space-y-3">
            <span class="text-xs font-bold uppercase text-text-secondary tracking-widest block">Categorías</span>
            
            <div class="flex flex-wrap lg:grid lg:grid-cols-4 gap-4">
              <!-- Todos -->
              <button
                @click="seleccionarCategoria(null)"
                class="group flex flex-col items-center gap-1.5 transition-all cursor-pointer"
              >
                <div 
                  :class="[
                    'w-14 h-14 rounded-full flex items-center justify-center text-xl transition-all duration-300 relative',
                    !filtroCategoria 
                      ? 'bg-gradient-to-tr from-primary to-secondary text-white shadow-xl shadow-primary/30 scale-110 border-2 border-primary' 
                      : 'bg-white/5 text-text-secondary hover:bg-white/10 hover:text-white border border-border-sutil'
                  ]"
                >
                  <Sparkles class="w-5 h-5" />
                  <span v-if="!filtroCategoria" class="absolute -bottom-1 w-2 h-2 bg-primary rounded-full"></span>
                </div>
                <span :class="['text-[10px] font-black uppercase tracking-wide transition-colors text-center max-w-[70px] truncate', !filtroCategoria ? 'text-primary' : 'text-text-secondary group-hover:text-gray-300']">Todos</span>
              </button>
              
              <!-- Dinámicas -->
              <button
                v-for="cat in categorias"
                :key="cat.id"
                @click="seleccionarCategoria(cat.id)"
                class="group flex flex-col items-center gap-1.5 transition-all cursor-pointer"
              >
                <div 
                  :class="[
                    'w-14 h-14 rounded-full flex items-center justify-center text-xl transition-all duration-300 relative group-hover:scale-105',
                    filtroCategoria === cat.id 
                      ? 'bg-gradient-to-tr from-primary to-secondary text-white shadow-xl shadow-primary/30 scale-110 border-2 border-primary' 
                      : 'bg-white/5 text-text-secondary hover:bg-white/10 hover:text-white border border-border-sutil'
                  ]"
                >
                  <span>{{ cat.icono }}</span>
                  <span v-if="filtroCategoria === cat.id" class="absolute -bottom-1 w-2 h-2 bg-primary rounded-full"></span>
                </div>
                <span :class="['text-[10px] font-black uppercase tracking-wide transition-colors text-center max-w-[70px] truncate', filtroCategoria === cat.id ? 'text-primary' : 'text-text-secondary group-hover:text-gray-300']">
                  {{ cat.nombre }}
                </span>
              </button>
            </div>
          </div>

          <!-- Buscador debajo de Categorías -->
          <div class="space-y-2 relative" ref="searchContainerRef">
            <span class="text-xs font-bold uppercase text-text-secondary tracking-widest block">¿Qué estás buscando?</span>
            <div class="relative flex items-center">
              <input
                v-model="busqueda"
                @input="onSearchInput"
                @keydown.enter="buscarConQuery(busqueda)"
                type="text"
                placeholder="Peluquerías, dentistas, spa..."
                class="w-full bg-white/5 border border-border rounded-2xl pl-12 pr-10 py-4 text-white placeholder-gray-600 focus:outline-none focus:border-primary focus:bg-white/10 transition-all text-sm font-medium"
              />
              <Search class="w-5 h-5 text-text-secondary absolute left-4" />
              <button 
                v-if="busqueda" 
                @click="clearSearch"
                class="absolute right-4 text-text-secondary hover:text-white transition-colors"
              >
                <X class="w-4 h-4" />
              </button>
            </div>

            <!-- Sugerencias en tiempo real -->
            <transition
              enter-active-class="transition duration-100 ease-out"
              enter-from-class="transform scale-95 opacity-0"
              enter-to-class="transform scale-100 opacity-100"
              leave-active-class="transition duration-75 ease-in"
              leave-from-class="transform scale-100 opacity-100"
              leave-to-class="transform scale-95 opacity-0"
            >
              <div 
                v-if="showSuggestions && sugerencias.length > 0" 
                class="absolute z-50 w-full mt-2 bg-bg-card/95 backdrop-blur-xl border border-border rounded-2xl shadow-2xl overflow-hidden divide-y divide-border-sutil"
              >
                <div 
                  v-for="(sug, index) in sugerencias" 
                  :key="index"
                  @click="selectSuggestion(sug)"
                  class="px-4 py-3 hover:bg-primary/20 hover:text-white text-text-secondary text-xs font-medium cursor-pointer transition-all flex items-center justify-between"
                >
                  <div class="flex items-center gap-2">
                    <span class="text-sm">{{ sug.icono }}</span>
                    <span class="text-white">{{ sug.texto }}</span>
                  </div>
                  <span class="text-[9px] uppercase tracking-widest text-text-secondary font-bold bg-white/5 px-2 py-0.5 rounded border border-border-sutil">{{ sug.tipo }}</span>
                </div>
              </div>
            </transition>
          </div>

          <!-- Filtros Premium -->
          <div class="space-y-3">
            <span class="text-xs font-bold uppercase text-text-secondary tracking-widest block">Filtros</span>
            <div class="flex flex-wrap gap-2">
              <button 
                @click="toggleFilter('verificado')"
                :class="['px-4 py-2 rounded-full text-xs font-black tracking-wide uppercase transition-all border cursor-pointer active:scale-95', filters.verificado ? 'bg-primary/10 border-primary text-primary shadow-md shadow-primary/5' : 'bg-white/5 border-border text-text-secondary hover:text-white']"
              >
                ✓ Verificados
              </button>
              <button 
                @click="toggleFilter('reserva_inmediata')"
                :class="['px-4 py-2 rounded-full text-xs font-black tracking-wide uppercase transition-all border cursor-pointer active:scale-95', filters.reserva_inmediata ? 'bg-primary/10 border-primary text-primary shadow-md shadow-primary/5' : 'bg-white/5 border-border text-text-secondary hover:text-white']"
              >
                ⚡ Reserva Inmediata
              </button>
              <button 
                @click="toggleFilter('recomendado')"
                :class="['px-4 py-2 rounded-full text-xs font-black tracking-wide uppercase transition-all border cursor-pointer active:scale-95', filters.recomendado ? 'bg-primary/10 border-primary text-primary shadow-md shadow-primary/5' : 'bg-white/5 border-border text-text-secondary hover:text-white']"
              >
                ⭐ Recomendados
              </button>
              <button 
                @click="toggleFilter('mas_cercano')"
                :class="['px-4 py-2 rounded-full text-xs font-black tracking-wide uppercase transition-all border cursor-pointer active:scale-95', filters.mas_cercano ? 'bg-primary/10 border-primary text-primary shadow-md shadow-primary/5' : 'bg-white/5 border-border text-text-secondary hover:text-white']"
              >
                📍 Más Cercanos
              </button>
              <button 
                @click="toggleFilter('mejor_valorado')"
                :class="['px-4 py-2 rounded-full text-xs font-black tracking-wide uppercase transition-all border cursor-pointer active:scale-95', filters.mejor_valorado ? 'bg-primary/10 border-primary text-primary shadow-md shadow-primary/5' : 'bg-white/5 border-border text-text-secondary hover:text-white']"
              >
                🏆 Mejor Valorados
              </button>
            </div>
          </div>
        </section>

        <!-- ================= COLUMNA DERECHA (MOSAICO) ================= -->
        <section class="lg:col-span-8 space-y-10">
          
          <!-- Sección de Sugerencia Inteligente / Descubrimiento -->
          <div v-if="negociosDestacados.length > 0" class="space-y-4">
            <div class="flex items-center gap-2">
              <Sparkles class="w-4 h-4 text-primary" />
              <h2 class="text-xs font-bold uppercase tracking-widest text-primary">✨ Descubre nuevos negocios</h2>
            </div>
            
            <div class="flex gap-6 overflow-x-auto pb-4 scrollbar-thin scrollbar-track-transparent scrollbar-thumb-white/10 snap-x">
              <div 
                v-for="dest in negociosDestacados" 
                :key="'dest-'+dest.id"
                class="min-w-[280px] sm:min-w-[340px] bg-bg-card border border-border rounded-2xl p-5 snap-start hover:border-primary/30 transition-all duration-300 group flex gap-4"
              >
                <div class="w-16 h-16 rounded-xl border border-border overflow-hidden bg-gray-900 flex-shrink-0">
                  <img v-if="dest.logo" :src="dest.logo" :alt="dest.nombre" class="w-full h-full object-cover" />
                  <div v-else class="w-full h-full bg-primary/20 flex items-center justify-center font-bold text-lg text-primary">{{ dest.nombre[0] }}</div>
                </div>
                <div class="flex flex-col justify-between flex-grow">
                  <div>
                    <span class="text-[9px] font-black uppercase text-primary tracking-wider">{{ dest.categoria?.nombre }}</span>
                    <h3 class="text-sm font-bold text-white leading-tight group-hover:text-primary transition-colors line-clamp-1 mt-0.5">{{ dest.nombre }}</h3>
                    <p class="text-[10px] text-text-secondary line-clamp-1 mt-1">{{ dest.especialidad || dest.ciudad }}</p>
                  </div>
                  <div class="flex items-center justify-between mt-3 pt-2 border-t border-border-sutil">
                    <div class="flex items-center gap-1 text-[10px] font-bold text-yellow-400">
                      <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                      <span>{{ dest.rating_avg || 'N/A' }}</span>
                    </div>
                    <a 
                      :href="`/${dest.slug}/book`" 
                      @click="trackClick(dest.id)"
                      class="text-[10px] font-bold text-accent hover:text-white transition-colors"
                    >
                      Reservar ⚡
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Corrección de búsqueda sugerida -->
          <div v-if="correccionBusqueda" class="bg-primary/10 border border-primary/20 rounded-xl p-4 text-xs text-text-secondary flex items-center gap-2">
            <span>¿Quisiste decir:</span>
            <button @click="aplicarBusquedaCorregida" class="font-black text-primary hover:underline hover:text-primary-hover transition-all">
              "{{ correccionBusqueda }}"
            </button>
            <span>?</span>
          </div>

          <!-- Listado del Mosaico Grid -->
          <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div v-for="i in 6" :key="i" class="h-60 rounded-3xl bg-white/5 animate-pulse border border-white/5"></div>
          </div>

          <div v-else-if="negocios.length === 0" class="text-center py-24 bg-white/5 rounded-3xl border border-white/5">
            <span class="text-5xl block mb-4">🛸</span>
            <h3 class="text-lg font-black text-white uppercase tracking-wider">No se encontraron negocios</h3>
            <p class="text-gray-500 text-xs mt-2 max-w-xs mx-auto">Hemos ampliado la búsqueda al máximo pero no hay resultados. Prueba cambiando los filtros o la ubicación.</p>
          </div>

          <!-- Grid Masonry -->
          <div v-else class="directory-grid">
            <template v-for="(negocio, index) in negocios" :key="negocio.id">
              
              <!-- FECHA GIGANTE DEL DÍA EN EL CENTRO DEL MOSAICO (Estilo Imagen Ref 13/3) -->
              <div 
                v-if="index === Math.floor(negocios.length / 2) || (negocios.length === 1 && index === 0)" 
                class="flex flex-col items-center justify-center select-none"
                style="grid-row: span 3; grid-column: span 2;"
              >
                <span class="text-[160px] font-black text-white/5 leading-none tracking-tighter tabular-nums drop-shadow-2xl">
                  {{ new Date().getDate().toString().padStart(2, '0') }}
                </span>
                <div class="flex items-center justify-center gap-3 mt-2">
                  <span class="text-xl font-bold uppercase tracking-widest text-text-secondary">
                    {{ new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(new Date()) }}
                  </span>
                  <span class="text-xl font-black uppercase tracking-wider text-accent">
                    {{ new Intl.DateTimeFormat('es-ES', { month: 'long' }).format(new Date()) }}
                  </span>
                </div>
              </div>

            <div
              :class="[
                'directory-card group relative rounded-2xl overflow-hidden bg-bg-card border border-border transition-all duration-300 p-4',
              ]"
              :style="{ 
                borderLeft: '4px solid ' + getCategoryColor(negocio.categoria?.nombre)
              }"
            >
              <!-- Glowing sutil en hover -->
              <div class="absolute inset-0 bg-primary/[0.02] opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>

              <!-- Decoración Diagonal Asimétrica -->
              <div class="absolute inset-0 pointer-events-none overflow-hidden rounded-3xl opacity-20">
                <svg class="absolute w-[200%] h-[200%] top-[-50%] left-[-50%] text-white/5" viewBox="0 0 100 100" preserveAspectRatio="none">
                  <line x1="0" y1="0" x2="100" y2="100" stroke="currentColor" stroke-width="0.3" />
                </svg>
              </div>

              <!-- Contenido Compacto Horizontal de la Tarjeta -->
              <div class="flex items-center justify-between gap-4 relative z-10 w-full">
                <!-- Izquierda: Imagen de Portada o Logo -->
                <div class="flex items-center gap-3 overflow-hidden">
                  <div class="w-14 h-14 rounded-xl border border-border overflow-hidden bg-gray-900 flex items-center justify-center shadow-md shrink-0">
                    <img v-if="negocio.cover_imagen" :src="negocio.cover_imagen" :alt="negocio.nombre" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                    <img v-else-if="negocio.logo" :src="negocio.logo" :alt="negocio.nombre" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-700" />
                    <span v-else class="text-xl font-black text-white">{{ negocio.nombre[0] }}</span>
                  </div>
                  
                  <!-- Centro: Info del Negocio (Categoría, Nombre, Estrellas) -->
                  <div class="flex flex-col justify-center min-w-0">
                    <span class="block text-[8px] font-black uppercase tracking-widest text-primary mb-0.5 truncate">
                      {{ negocio.categoria?.nombre }}
                    </span>
                    <div class="flex items-center gap-2">
                      <h3 class="font-bold text-white text-sm leading-tight truncate group-hover:text-primary transition-colors">
                        {{ negocio.nombre }}
                      </h3>
                      <span v-if="negocio.verificado" class="w-3.5 h-3.5 rounded-full bg-primary/10 border border-primary/20 flex items-center justify-center text-[8px] text-primary shrink-0" title="Verificado">✓</span>
                    </div>
                    <div class="flex items-center gap-1 mt-1 text-[10px] font-bold text-yellow-400">
                      <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                      <span class="text-white">{{ negocio.rating_avg || '4.8' }}</span>
                      <span class="text-text-secondary font-medium text-[9px]">({{ negocio.rating_count || 12 }})</span>
                    </div>
                  </div>
                </div>

                <!-- Derecha: Botón Pedir Cita -->
                <a
                  :href="`/${negocio.slug}/book`"
                  @click="trackClick(negocio.id)"
                  class="px-4 py-2 bg-accent hover:bg-accent-hover text-white rounded-xl font-black uppercase tracking-widest text-[9px] transition-all duration-300 text-center shadow-cta-glow active:scale-95 shrink-0 hover:-translate-y-0.5"
                >
                  Pedir Cita
                </a>
              </div>
            </div>
            </template>
          </div>

          <!-- Paginación -->
          <div v-if="pagination && pagination.last_page > 1" class="flex justify-center gap-2 mt-12 border-t border-border-sutil pt-8">
            <button
              v-for="page in pagination.last_page"
              :key="page"
              @click="cargarPagina(page)"
              :class="[
                'w-10 h-10 rounded-xl text-xs font-black transition-all shadow-md cursor-pointer active:scale-95',
                page === pagination.current_page
                  ? 'bg-primary text-white shadow-primary/30'
                  : 'bg-white/5 text-text-secondary hover:bg-white/10 hover:text-white border border-border'
              ]"
            >
              {{ page }}
            </button>
          </div>

        </section>

      </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-white/5 py-12 mt-20 bg-black/40 text-center text-gray-600 text-xs relative z-10">
      <div class="flex items-center justify-center gap-2 mb-4 opacity-30 grayscale hover:opacity-100 transition-opacity duration-300">
        <img src="/images/logo.png" alt="CitasPro Logo" class="h-6 object-contain" @error="$event.target.style.display='none'" />
        <span class="font-black tracking-widest uppercase">CitasPro</span>
      </div>
      <p class="max-w-md mx-auto leading-relaxed">© 2026 CitasPro App. Plataforma de reservas inteligentes y geolocalizadas. Todos los derechos reservados.</p>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import CustomSelect from '../Components/CustomSelect.vue';
import { MapPin, X, Star, Sparkles, Search } from 'lucide-vue-next';

const { t } = useI18n();

// Estado
const negocios = ref([]);
const negociosDestacados = ref([]);
const categorias = ref([]);
const loading = ref(true);
const busqueda = ref('');
const correccionBusqueda = ref('');
const filtroCategoria = ref(null);
const pagination = ref(null);
const seed = ref(Math.floor(Math.random() * 9999) + 1);

// Ubicación y Geolocalización
const locationText = ref('Cargando ubicación...');
const userLat = ref(null);
const userLng = ref(null);
const detectedPaisId = ref(null);
const detectedEstadoId = ref(null);
const detectedCiudadId = ref(null);

// Panel de Ubicación Manual
const showLocationPanel = ref(false);
const manualPais = ref('');
const manualEstado = ref('');
const manualCiudad = ref('');

// Opciones de Selects
const paisesOptions = ref([]);
const estadosOptions = ref([]);
const ciudadesOptions = ref([]);

// Sugerencias / Autocompletado
const sugerencias = ref([]);
const showSuggestions = ref(false);
const searchContainerRef = ref(null);
let debounceTimer = null;

// Filtros Activos
const filters = ref({
  verificado: false,
  reserva_inmediata: false,
  recomendado: false,
  mas_cercano: false,
  mejor_valorado: false
});

// Ciclo de Vida
onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  
  // Cargar categorías y países inicialmente
  await Promise.all([cargarCategorias(), cargarPaises()]);
  
  // Intentar recuperar ubicación guardada del localStorage
  const savedCityName = localStorage.getItem('citaspro_manual_ciudad_nombre');
  const savedPaisId = localStorage.getItem('citaspro_manual_pais_id');
  const savedEstadoId = localStorage.getItem('citaspro_manual_estado_id');
  const savedCiudadId = localStorage.getItem('citaspro_manual_ciudad_id');

  if (savedCiudadId && savedCityName) {
    // Si hay ubicación guardada
    detectedPaisId.value = parseInt(savedPaisId);
    detectedEstadoId.value = parseInt(savedEstadoId);
    detectedCiudadId.value = parseInt(savedCiudadId);
    manualPais.value = detectedPaisId.value;
    manualEstado.value = detectedEstadoId.value;
    manualCiudad.value = detectedCiudadId.value;
    locationText.value = savedCityName;
    await buscar();
  } else {
    // Intentar Geolocalización HTML5 GPS (Nivel 1)
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        async (position) => {
          userLat.value = position.coords.latitude;
          userLng.value = position.coords.longitude;
          locationText.value = 'Ubicación precisa (GPS)';
          await buscar();
        },
        async (error) => {
          // Denegado o fallo -> El backend usará Nivel 2 (IP-Geo)
          locationText.value = 'Auto (IP Detectada)';
          await buscar();
        },
        { timeout: 5000 }
      );
    } else {
      locationText.value = 'Auto (IP Detectada)';
      await buscar();
    }
  }
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
});

// Watchers encadenados para la ubicación manual
watch(manualPais, async (newVal) => {
  manualEstado.value = '';
  manualCiudad.value = '';
  estadosOptions.value = [];
  ciudadesOptions.value = [];
  if (newVal) {
    await cargarEstados(newVal);
  }
});

watch(manualEstado, async (newVal) => {
  manualCiudad.value = '';
  ciudadesOptions.value = [];
  if (newVal) {
    await cargarCiudades(newVal);
  }
});

// Cargar Datos API
const cargarCategorias = async () => {
  try {
    const res = await axios.get('/api/categorias');
    categorias.value = res.data || [];
  } catch {
    categorias.value = [];
  }
};

const cargarPaises = async () => {
  try {
    const res = await axios.get('/api/paises');
    paisesOptions.value = (res.data || []).map(p => ({
      value: p.id,
      label: `${p.nombre} (${p.prefijo_telefonico})`,
      icon: '🌍'
    }));
  } catch {
    paisesOptions.value = [];
  }
};

const cargarEstados = async (paisId) => {
  try {
    const res = await axios.get(`/api/locations/states/${paisId}`);
    if (res.data.success) {
      estadosOptions.value = res.data.data.map(e => ({
        value: e.id,
        label: e.nombre,
        icon: '📍'
      }));
    }
  } catch {
    estadosOptions.value = [];
  }
};

const cargarCiudades = async (estadoId) => {
  try {
    const res = await axios.get(`/api/locations/cities/${estadoId}`);
    if (res.data.success) {
      ciudadesOptions.value = res.data.data.map(c => ({
        value: c.id,
        label: c.nombre,
        icon: '🏢'
      }));
    }
  } catch {
    ciudadesOptions.value = [];
  }
};

// Buscar Negocios
const buscar = async (pagina = 1) => {
  loading.value = true;
  try {
    const params = {
      page: pagina,
      seed: seed.value,
      q: busqueda.value || undefined,
      categoria_id: filtroCategoria.value || undefined,
      verificado: filters.value.verificado || undefined,
      reserva_inmediata: filters.value.reserva_inmediata || undefined,
      recomendado: filters.value.recomendado || undefined,
      mas_cercano: filters.value.mas_cercano || undefined,
      mejor_valorado: filters.value.mejor_valorado || undefined,
    };

    // Agregar parámetros de ubicación
    if (userLat.value && userLng.value) {
      params.lat = userLat.value;
      params.lng = userLng.value;
    } else {
      params.pais_id = detectedPaisId.value || undefined;
      params.estado_id = detectedEstadoId.value || undefined;
      params.ciudad_id = detectedCiudadId.value || undefined;
    }

    const res = await axios.get('/api/directorio', { params });
    if (res.data.success) {
      negocios.value = res.data.data.data;
      pagination.value = res.data.data;
      correccionBusqueda.value = res.data.query_corrected || '';
      
      // Si la ubicación fue detectada por el backend en el Nivel 2
      const detected = res.data.location_detected;
      if (detected && !userLat.value && !userLng.value && !detectedCiudadId.value) {
        detectedPaisId.value = detected.pais_id;
        detectedEstadoId.value = detected.estado_id;
        detectedCiudadId.value = detected.ciudad_id;
        
        if (detected.ciudad_id) {
          locationText.value = 'Ubicación Detectada';
        }
      }

      // Separar los destacados para el carrusel de descubrimiento (plan enterprise/pro o destacado)
      negociosDestacados.value = negocios.value.filter(n => n.destacado || n.plan === 'enterprise').slice(0, 5);
    }
  } catch {
    negocios.value = [];
  } finally {
    loading.value = false;
  }
};

const cargarPagina = (page) => {
  buscar(page);
  // Auto-scroll al inicio de la lista de negocios para mejor UX
  const gridEl = document.querySelector('.directory-grid');
  if (gridEl) {
    gridEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

// Acciones e Interacciones
const seleccionarCategoria = (id) => {
  filtroCategoria.value = id;
  buscar();
};

const toggleFilter = (filterKey) => {
  filters.value[filterKey] = !filters.value[filterKey];
  // Si activa Cercanos y no tenemos coordenadas, pedir permisos
  if (filterKey === 'mas_cercano' && filters.value.mas_cercano && !userLat.value) {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          userLat.value = position.coords.latitude;
          userLng.value = position.coords.longitude;
          locationText.value = 'Ubicación precisa (GPS)';
          buscar();
        },
        () => {
          // Si deniega, desactivamos filtro
          filters.value.mas_cercano = false;
          alert('Por favor, activa los permisos de ubicación en el navegador para ordenar por cercanía.');
        }
      );
    }
  } else {
    buscar();
  }
};

// Búsqueda con autocompletado y debouncing
const onSearchInput = () => {
  clearTimeout(debounceTimer);
  if (busqueda.value.length >= 2) {
    debounceTimer = setTimeout(async () => {
      try {
        const res = await axios.get('/api/directorio/sugerencias', {
          params: { q: busqueda.value }
        });
        if (res.data.success) {
          sugerencias.value = res.data.data;
          showSuggestions.value = true;
        }
      } catch {
        sugerencias.value = [];
      }
    }, 300);
  } else {
    sugerencias.value = [];
    showSuggestions.value = false;
  }
};

const selectSuggestion = (sug) => {
  busqueda.value = sug.texto;
  showSuggestions.value = false;
  if (sug.tipo === 'categoria' && sug.id) {
    filtroCategoria.value = sug.id;
  } else if (sug.tipo === 'negocio' && sug.slug) {
    window.location.href = `/${sug.slug}/book`;
    return;
  }
  buscar();
};

const clearSearch = () => {
  busqueda.value = '';
  sugerencias.value = [];
  showSuggestions.value = false;
  buscar();
};

const buscarConQuery = (q) => {
  showSuggestions.value = false;
  buscar();
};

const aplicarBusquedaCorregida = () => {
  busqueda.value = correccionBusqueda.value;
  correccionBusqueda.value = '';
  buscar();
};

// Panel de Ubicación Manual
const toggleLocationPanel = () => {
  showLocationPanel.value = !showLocationPanel.value;
};

const aplicarUbicacionManual = async () => {
  if (!manualCiudad.value) {
    alert('Por favor, selecciona hasta el nivel de ciudad.');
    return;
  }

  // Buscar el nombre de la ciudad seleccionada
  const selectedCityObj = ciudadesOptions.value.find(opt => opt.value === manualCiudad.value);
  const cityName = selectedCityObj ? selectedCityObj.label : 'Ciudad Seleccionada';

  // Guardar en el estado
  detectedPaisId.value = manualPais.value;
  detectedEstadoId.value = manualEstado.value;
  detectedCiudadId.value = manualCiudad.value;
  
  // Limpiar GPS para forzar la ubicación manual
  userLat.value = null;
  userLng.value = null;
  
  locationText.value = cityName;
  showLocationPanel.value = false;

  // Persistir en localstorage
  localStorage.setItem('citaspro_manual_pais_id', manualPais.value);
  localStorage.setItem('citaspro_manual_estado_id', manualEstado.value);
  localStorage.setItem('citaspro_manual_ciudad_id', manualCiudad.value);
  localStorage.setItem('citaspro_manual_ciudad_nombre', cityName);

  await buscar();
};

const resetearUbicacion = () => {
  localStorage.removeItem('citaspro_manual_pais_id');
  localStorage.removeItem('citaspro_manual_estado_id');
  localStorage.removeItem('citaspro_manual_ciudad_id');
  localStorage.removeItem('citaspro_manual_ciudad_nombre');
  
  manualPais.value = '';
  manualEstado.value = '';
  manualCiudad.value = '';
  detectedPaisId.value = null;
  detectedEstadoId.value = null;
  detectedCiudadId.value = null;
  
  locationText.value = 'Buscando...';
  showLocationPanel.value = false;

  // Re-intentar GPS o IP
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        userLat.value = position.coords.latitude;
        userLng.value = position.coords.longitude;
        locationText.value = 'Ubicación precisa (GPS)';
        await buscar();
      },
      async () => {
        locationText.value = 'Auto (IP Detectada)';
        await buscar();
      }
    );
  } else {
    locationText.value = 'Auto (IP Detectada)';
    buscar();
  }
};

// Tracking de clicks / vistas
const trackClick = (negocioId) => {
  axios.post(`/api/directorio/track-view/${negocioId}`).catch(() => {});
};

// Helper de clicks fuera
const handleClickOutside = (e) => {
  if (searchContainerRef.value && !searchContainerRef.value.contains(e.target)) {
    showSuggestions.value = false;
  }
};

// Diseño de la Cuadrícula Asimétrica
const getCardTransform = (index, layoutSize) => {
  // Solo aplicamos la deformación orgánica en Desktop para mantener consistencia responsiva
  if (window.innerWidth < 1024) return 'none';
  
  // Rotaciones controladas muy sutiles para romper la monotonía visual
  const rotaciones = [-0.6, 0.5, -0.8, 0.6, -0.5, 0.4, -0.7, 0.8];
  // Pequeños desfases verticales y horizontales
  const offsetsY = [8, -12, 14, -6, 10, 0, -14, 8];
  
  const rotation = rotaciones[index % rotaciones.length];
  const offsetY = offsetsY[index % offsetsY.length];
  
  return `rotate(${rotation}deg) translateY(${offsetY}px)`;
};

const getCategoryColor = (nombre) => {
  if (!nombre) return '#3B82F6';
  const n = nombre.toLowerCase();
  if (n.includes('clínica') || n.includes('clinica') || n.includes('salud') || n.includes('médic') || n.includes('dent') || n.includes('odontolog')) {
    return '#3B82F6'; // Azul primario
  }
  if (n.includes('peluquería') || n.includes('peluqueria') || n.includes('belleza') || n.includes('estética') || n.includes('estetica') || n.includes('barber')) {
    return '#FF5A5F'; // Coral
  }
  if (n.includes('fitness') || n.includes('bienestar') || n.includes('gimnasio') || n.includes('deporte')) {
    return '#22C55E'; // Verde
  }
  if (n.includes('educación') || n.includes('educacion') || n.includes('clases') || n.includes('academia')) {
    return '#6366F1'; // Índigo
  }
  if (n.includes('veterinaria') || n.includes('mascotas') || n.includes('veterinario')) {
    return '#06B6D4'; // Turquesa
  }
  if (n.includes('consultoría') || n.includes('consultoria') || n.includes('asesor') || n.includes('abogado') || n.includes('gestor')) {
    return '#F4B400'; // Dorado
  }
  return '#3B82F6'; // default
};
</script>

<style scoped>
.directory-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  grid-auto-rows: minmax(180px, auto);
  grid-auto-flow: dense;
  gap: 28px;
}

/* Clases de tamaños de tarjetas Masonry en Grid */
.card-small {
  grid-row: span 1;
  grid-column: span 1;
}
.card-medium {
  grid-row: span 2;
  grid-column: span 1;
}
.card-large {
  grid-row: span 3;
  grid-column: span 2;
}
.card-horizontal {
  grid-row: span 1;
  grid-column: span 2;
}
.card-vertical {
  grid-row: span 3;
  grid-column: span 1;
}
.card-featured {
  grid-row: span 3;
  grid-column: span 2;
  box-shadow: 0 0 25px rgba(59, 130, 246, 0.15);
  border: 1px solid rgba(59, 130, 246, 0.25);
}

.directory-card {
  transition: transform 0.3s ease, border-color 0.3s, box-shadow 0.3s;
}

/* Hover de tarjeta premium con glow y escala sutil */
.directory-card:hover {
  transform: rotate(0deg) scale(1.025) translateY(-8px) !important;
  z-index: 20;
  box-shadow: 0 10px 30px rgba(59, 130, 246, 0.35) !important;
  border-color: rgba(59, 130, 246, 0.4) !important;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.animate-fade-in {
  animation: fadeIn 0.25s ease-out forwards;
}

/* Responsivo para Tablets (2 columnas) */
@media (min-width: 640px) and (max-width: 1023px) {
  .directory-grid {
    grid-template-columns: repeat(2, 1fr) !important;
    grid-auto-rows: auto !important;
  }
  .card-large, .card-featured, .card-horizontal {
    grid-column: span 2 !important;
    grid-row: span 1 !important;
  }
  .card-small, .card-medium, .card-vertical {
    grid-column: span 1 !important;
    grid-row: span 1 !important;
  }
}

/* Responsivo para Móviles (1 columna) */
@media (max-width: 639px) {
  .directory-grid {
    display: flex !important;
    flex-direction: column !important;
    gap: 20px !important;
  }
}
</style>
