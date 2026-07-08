<template>
  <!--
    ╔══════════════════════════════════════════════════════════════╗
    ║  CitasPro · Directorio Editorial                            ║
    ║  Diseño: Mosaico asimétrico tipo revista moderna            ║
    ╚══════════════════════════════════════════════════════════════╝
  -->
  <div class="dir-root">

    <!-- ═══════════════════════════════════════════════════════════
         HEADER STICKY
    ═══════════════════════════════════════════════════════════ -->
    <header class="dir-header">
      <div class="dir-header-inner">
        <div class="dir-brand">
          <img src="/images/logo.png" alt="CitasPro" class="dir-brand-logo"
               @error="$event.target.style.display='none'; $event.target.nextElementSibling.style.display='flex'" />
          <div class="dir-brand-fallback">C</div>
          <span class="dir-brand-name">CitasPro</span>
          <span class="dir-brand-sep">Directorio</span>
        </div>
        <div class="dir-header-actions">
          <button @click="toggleLocationPanel" class="dir-location-btn">
            <MapPin class="dir-location-icon" />
            <span class="dir-location-text">{{ locationText }}</span>
          </button>
          <a href="/login" class="dir-cta-header">¿Eres un negocio?</a>
        </div>
      </div>
    </header>

    <!-- ═══════════════════════════════════════════════════════════
         HERO COMPACTO (Título + Buscador prominente)
    ═══════════════════════════════════════════════════════════ -->
    <section class="dir-hero">
      <div class="dir-hero-inner">
        <div class="dir-hero-text">
          <h1 class="dir-hero-title">
            Directorio de
            <span class="dir-hero-highlight">Negocios</span>
          </h1>
          <p class="dir-hero-sub">Los mejores profesionales cerca de ti. Reserva al instante.</p>
        </div>

        <div class="dir-search-wrap" ref="searchContainerRef">
          <div class="dir-search-box">
            <Search class="dir-search-icon" />
            <input
              v-model="busqueda"
              @input="onSearchInput"
              @keydown.enter="buscarConQuery(busqueda)"
              type="text"
              placeholder="Peluquería, dentista, spa…"
              class="dir-search-input"
            />
            <button v-if="busqueda" @click="clearSearch" class="dir-search-clear">
              <X class="w-4 h-4" />
            </button>
            <button @click="buscarConQuery(busqueda)" class="dir-search-submit">Buscar</button>
          </div>

          <div v-if="correccionBusqueda" class="dir-correction">
            ¿Quisiste decir:
            <button @click="aplicarBusquedaCorregida" class="dir-correction-btn">"{{ correccionBusqueda }}"</button>?
          </div>

          <transition
            enter-active-class="dir-suggest-enter-active"
            enter-from-class="dir-suggest-enter-from"
            enter-to-class="dir-suggest-enter-to"
            leave-active-class="dir-suggest-leave-active"
            leave-from-class="dir-suggest-enter-to"
            leave-to-class="dir-suggest-enter-from"
          >
            <div v-if="showSuggestions && sugerencias.length > 0" class="dir-suggestions">
              <div
                v-for="(sug, i) in sugerencias" :key="i"
                @click="selectSuggestion(sug)"
                class="dir-suggestion-item"
              >
                <div class="dir-sug-left">
                  <span class="dir-sug-icon">{{ sug.icono }}</span>
                  <span class="dir-sug-text">{{ sug.texto }}</span>
                </div>
                <span class="dir-sug-badge">{{ sug.tipo }}</span>
              </div>
            </div>
          </transition>
        </div>
      </div>
    </section>

    <!-- ═══════════════════════════════════════════════════════════
         CUERPO PRINCIPAL
    ═══════════════════════════════════════════════════════════ -->
    <main class="dir-main">
      <div class="dir-layout">

        <!-- ─── SIDEBAR ─────────────────────────────────────── -->
        <aside class="dir-sidebar">

          <div v-if="showLocationPanel" class="dir-location-panel">
            <div class="dir-lp-header">
              <span class="dir-lp-title">Ubicación</span>
              <button @click="showLocationPanel = false" class="dir-lp-close"><X class="w-3.5 h-3.5" /></button>
            </div>
            <div class="dir-lp-fields">
              <div class="dir-field">
                <label class="dir-field-label">País</label>
                <CustomSelect v-model="manualPais" :options="paisesOptions" placeholder="País" buttonClass="dir-select-btn" />
              </div>
              <div v-if="manualPais" class="dir-field">
                <label class="dir-field-label">Estado</label>
                <CustomSelect v-model="manualEstado" :options="estadosOptions" placeholder="Estado" buttonClass="dir-select-btn" />
              </div>
              <div v-if="manualEstado" class="dir-field">
                <label class="dir-field-label">Ciudad</label>
                <CustomSelect v-model="manualCiudad" :options="ciudadesOptions" placeholder="Ciudad" buttonClass="dir-select-btn" />
              </div>
              <div class="dir-lp-actions">
                <button @click="aplicarUbicacionManual" class="dir-btn-primary">Aplicar</button>
                <button @click="resetearUbicacion" class="dir-btn-ghost">Auto</button>
              </div>
            </div>
          </div>

          <!-- Categorías pills -->
          <div class="dir-cats-section">
            <span class="dir-section-label">Categorías</span>
            <div class="dir-cats-grid">
              <button @click="seleccionarCategoria(null)" :class="['dir-cat-pill', !filtroCategoria && 'dir-cat-pill--active']">
                <Sparkles class="dir-cat-pill-icon" />
                <span>Todos</span>
              </button>
              <button
                v-for="cat in categorias" :key="cat.id"
                @click="seleccionarCategoria(cat.id)"
                :class="['dir-cat-pill', filtroCategoria === cat.id && 'dir-cat-pill--active']"
              >
                <span class="dir-cat-pill-emoji">{{ cat.icono }}</span>
                <span class="dir-cat-pill-name">{{ cat.nombre }}</span>
              </button>
            </div>
          </div>

          <!-- Filtros -->
          <div class="dir-filters-section">
            <span class="dir-section-label">Filtros</span>
            <div class="dir-filters-list">
              <button @click="toggleFilter('verificado')" :class="['dir-filter-btn', filters.verificado && 'dir-filter-btn--on']">✓ Verificados</button>
              <button @click="toggleFilter('reserva_inmediata')" :class="['dir-filter-btn', filters.reserva_inmediata && 'dir-filter-btn--on']">⚡ Reserva Inmediata</button>
              <button @click="toggleFilter('recomendado')" :class="['dir-filter-btn', filters.recomendado && 'dir-filter-btn--on']">⭐ Recomendados</button>
              <button @click="toggleFilter('mas_cercano')" :class="['dir-filter-btn', filters.mas_cercano && 'dir-filter-btn--on']">📍 Más Cercanos</button>
              <button @click="toggleFilter('mejor_valorado')" :class="['dir-filter-btn', filters.mejor_valorado && 'dir-filter-btn--on']">🏆 Mejor Valorados</button>
            </div>
          </div>

          <!-- Destacados sidebar -->
          <div v-if="negociosDestacados.length > 0" class="dir-featured-section">
            <span class="dir-section-label">Destacados</span>
            <div class="dir-featured-list">
              <a
                v-for="dest in negociosDestacados" :key="'f-'+dest.id"
                :href="`/${dest.slug}/book`"
                @click="trackClick(dest.id)"
                class="dir-featured-item"
              >
                <div class="dir-fi-img">
                  <img v-if="dest.logo" :src="dest.logo" :alt="dest.nombre" class="w-full h-full object-cover" />
                  <span v-else class="dir-fi-initial">{{ dest.nombre[0] }}</span>
                </div>
                <div class="dir-fi-info">
                  <span class="dir-fi-cat">{{ dest.categoria?.nombre }}</span>
                  <span class="dir-fi-name">{{ dest.nombre }}</span>
                </div>
                <div class="dir-fi-rating">
                  <Star class="w-3 h-3 fill-yellow-400 text-yellow-400" />
                  <span>{{ dest.rating_avg || '–' }}</span>
                </div>
              </a>
            </div>
          </div>

        </aside>

        <!-- ─── MOSAICO PRINCIPAL ────────────────────────────── -->
        <section class="dir-mosaic-section">

          <div class="dir-mosaic-bar">
            <span class="dir-mosaic-count">
              <template v-if="!loading">{{ pagination?.total || negocios.length }} negocios encontrados</template>
              <template v-else>Buscando…</template>
            </span>
            <span v-if="busqueda" class="dir-mosaic-query">para "{{ busqueda }}"</span>
          </div>

          <!-- Skeleton -->
          <div v-if="loading" class="dir-grid">
            <div v-for="i in 8" :key="i"
              :class="['dir-skeleton', i===1?'card-xl': i===2||i===3?'card-l': i%4===0?'card-v':'card-s']">
            </div>
          </div>

          <!-- Vacío -->
          <div v-else-if="negocios.length === 0" class="dir-empty">
            <span class="dir-empty-icon">🛸</span>
            <h3 class="dir-empty-title">Sin resultados</h3>
            <p class="dir-empty-sub">Intenta cambiar los filtros o ampliar la ubicación.</p>
          </div>

          <!-- ═══ MOSAICO EDITORIAL ════════════════════════════ -->
          <div v-else class="dir-grid">
            <article
              v-for="(negocio) in negocios"
              :key="negocio.id"
              :class="['dir-card', `card-${negocio.layout_size || 's'}`, `dir-card--offset-${negocio.offset_y || 0}`]"
              :style="{ '--accent-color': getCategoryColor(negocio.categoria?.nombre) }"
            >
              <!-- Portada (XL, L, V) -->
              <div v-if="negocio.cover_imagen && ['xl','l','v'].includes(negocio.layout_size)" class="dir-card-cover">
                <img :src="negocio.cover_imagen" :alt="negocio.nombre" class="dir-card-cover-img" loading="lazy" />
                <div class="dir-card-cover-overlay"></div>
              </div>

              <!-- Barra acento categoría -->
              <div class="dir-card-accent-bar"></div>

              <!-- Cuerpo -->
              <div :class="['dir-card-body', negocio.cover_imagen && ['xl','l','v'].includes(negocio.layout_size) ? 'dir-card-body--over-image' : '']">

                <div class="dir-card-header">
                  <div class="dir-card-logo-wrap">
                    <img v-if="negocio.logo" :src="negocio.logo" :alt="negocio.nombre" class="dir-card-logo" loading="lazy" />
                    <span v-else class="dir-card-logo-initial">{{ negocio.nombre[0] }}</span>
                  </div>
                  <div class="dir-card-meta">
                    <span class="dir-card-cat">{{ negocio.categoria?.nombre }}</span>
                    <h3 class="dir-card-name">{{ negocio.nombre }}</h3>
                    <div class="dir-card-stats">
                      <div class="dir-card-rating">
                        <Star class="dir-star-icon" />
                        <span class="dir-rating-val">{{ negocio.rating_avg || '–' }}</span>
                        <span class="dir-rating-count">({{ negocio.rating_count || 0 }})</span>
                      </div>
                      <span v-if="negocio.ciudad" class="dir-card-city">📍 {{ negocio.ciudad }}</span>
                    </div>
                  </div>
                  <span v-if="negocio.verificado" class="dir-verified-badge" title="Verificado">✓</span>
                </div>

                <div class="dir-card-footer">
                  <div class="dir-card-date">
                    <span class="dir-date-day">{{ negocio.next_available_day }}</span>
                    <div class="dir-date-labels">
                      <span class="dir-date-weekday">{{ negocio.next_available_weekday }}</span>
                      <span class="dir-date-month">{{ negocio.next_available_month }}</span>
                    </div>
                  </div>
                  <a :href="`/${negocio.slug}/book`" @click="trackClick(negocio.id)" class="dir-cta-btn">
                    <span>Reservar cita</span>
                    <span class="dir-cta-arrow">→</span>
                  </a>
                </div>

              </div>
            </article>
          </div>

          <!-- Paginación -->
          <nav v-if="pagination && pagination.last_page > 1" class="dir-pagination">
            <button
              v-for="page in pagination.last_page" :key="page"
              @click="cargarPagina(page)"
              :class="['dir-page-btn', page === pagination.current_page && 'dir-page-btn--active']"
            >{{ page }}</button>
          </nav>

        </section>
      </div>
    </main>

    <footer class="dir-footer">
      <div class="dir-footer-brand">
        <img src="/images/logo.png" alt="CitasPro" class="h-5 object-contain opacity-40" @error="$event.target.style.display='none'" />
        <span class="dir-footer-name">CitasPro</span>
      </div>
      <p class="dir-footer-copy">© 2026 CitasPro App. Plataforma de reservas inteligentes. Todos los derechos reservados.</p>
    </footer>

  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';
import { useI18n } from 'vue-i18n';
import CustomSelect from '../Components/CustomSelect.vue';
import { MapPin, X, Star, Sparkles, Search } from 'lucide-vue-next';

const { t } = useI18n();

const negocios           = ref([]);
const negociosDestacados = ref([]);
const categorias         = ref([]);
const loading            = ref(true);
const busqueda           = ref('');
const correccionBusqueda = ref('');
const filtroCategoria    = ref(null);
const pagination         = ref(null);
const seed               = ref(Math.floor(Math.random() * 9999) + 1);

const locationText     = ref('Cargando ubicación…');
const userLat          = ref(null);
const userLng          = ref(null);
const detectedPaisId   = ref(null);
const detectedEstadoId = ref(null);
const detectedCiudadId = ref(null);

const showLocationPanel = ref(false);
const manualPais        = ref('');
const manualEstado      = ref('');
const manualCiudad      = ref('');

const paisesOptions   = ref([]);
const estadosOptions  = ref([]);
const ciudadesOptions = ref([]);

const sugerencias        = ref([]);
const showSuggestions    = ref(false);
const searchContainerRef = ref(null);
let debounceTimer        = null;

const filters = ref({
  verificado: false,
  reserva_inmediata: false,
  recomendado: false,
  mas_cercano: false,
  mejor_valorado: false
});

onMounted(async () => {
  document.addEventListener('click', handleClickOutside);
  await Promise.all([cargarCategorias(), cargarPaises()]);

  const savedCityName = localStorage.getItem('citaspro_manual_ciudad_nombre');
  const savedPaisId   = localStorage.getItem('citaspro_manual_pais_id');
  const savedEstadoId = localStorage.getItem('citaspro_manual_estado_id');
  const savedCiudadId = localStorage.getItem('citaspro_manual_ciudad_id');

  if (savedCiudadId && savedCityName) {
    detectedPaisId.value   = parseInt(savedPaisId);
    detectedEstadoId.value = parseInt(savedEstadoId);
    detectedCiudadId.value = parseInt(savedCiudadId);
    manualPais.value       = detectedPaisId.value;
    manualEstado.value     = detectedEstadoId.value;
    manualCiudad.value     = detectedCiudadId.value;
    locationText.value     = savedCityName;
    await buscar();
  } else if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (pos) => {
        userLat.value      = pos.coords.latitude;
        userLng.value      = pos.coords.longitude;
        locationText.value = 'Ubicación precisa (GPS)';
        await buscar();
      },
      async () => { locationText.value = 'Auto (IP Detectada)'; await buscar(); },
      { timeout: 5000 }
    );
  } else {
    locationText.value = 'Auto (IP Detectada)';
    await buscar();
  }
});

onUnmounted(() => { document.removeEventListener('click', handleClickOutside); });

watch(manualPais, async (val) => {
  manualEstado.value = ''; manualCiudad.value = '';
  estadosOptions.value = []; ciudadesOptions.value = [];
  if (val) await cargarEstados(val);
});
watch(manualEstado, async (val) => {
  manualCiudad.value = ''; ciudadesOptions.value = [];
  if (val) await cargarCiudades(val);
});

const cargarCategorias = async () => {
  try { const res = await axios.get('/api/categorias'); categorias.value = res.data || []; }
  catch { categorias.value = []; }
};
const cargarPaises = async () => {
  try {
    const res = await axios.get('/api/paises');
    paisesOptions.value = (res.data || []).map(p => ({ value: p.id, label: `${p.nombre} (${p.prefijo_telefonico})`, icon: '🌍' }));
  } catch { paisesOptions.value = []; }
};
const cargarEstados = async (paisId) => {
  try {
    const res = await axios.get(`/api/locations/states/${paisId}`);
    if (res.data.success) estadosOptions.value = res.data.data.map(e => ({ value: e.id, label: e.nombre, icon: '📍' }));
  } catch { estadosOptions.value = []; }
};
const cargarCiudades = async (estadoId) => {
  try {
    const res = await axios.get(`/api/locations/cities/${estadoId}`);
    if (res.data.success) ciudadesOptions.value = res.data.data.map(c => ({ value: c.id, label: c.nombre, icon: '🏢' }));
  } catch { ciudadesOptions.value = []; }
};

const buscar = async (pagina = 1) => {
  loading.value = true;
  try {
    const params = {
      page: pagina, seed: seed.value,
      q: busqueda.value || undefined,
      categoria_id: filtroCategoria.value || undefined,
      verificado: filters.value.verificado || undefined,
      reserva_inmediata: filters.value.reserva_inmediata || undefined,
      recomendado: filters.value.recomendado || undefined,
      mas_cercano: filters.value.mas_cercano || undefined,
      mejor_valorado: filters.value.mejor_valorado || undefined,
    };
    if (userLat.value && userLng.value) {
      params.lat = userLat.value; params.lng = userLng.value;
    } else {
      params.pais_id = detectedPaisId.value || undefined;
      params.estado_id = detectedEstadoId.value || undefined;
      params.ciudad_id = detectedCiudadId.value || undefined;
    }
    const res = await axios.get('/api/directorio', { params });
    if (res.data.success) {
      negocios.value   = res.data.data.data;
      pagination.value = res.data.data;
      correccionBusqueda.value = res.data.query_corrected || '';
      const detected = res.data.location_detected;
      if (detected && !userLat.value && !userLng.value && !detectedCiudadId.value) {
        detectedPaisId.value = detected.pais_id;
        detectedEstadoId.value = detected.estado_id;
        detectedCiudadId.value = detected.ciudad_id;
        if (detected.ciudad_id) locationText.value = 'Ubicación Detectada';
      }
      negociosDestacados.value = negocios.value.filter(n => n.destacado || n.plan === 'enterprise').slice(0, 4);
    }
  } catch { negocios.value = []; }
  finally  { loading.value = false; }
};

const cargarPagina = (page) => {
  buscar(page);
  document.querySelector('.dir-grid')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
};
const seleccionarCategoria = (id) => { filtroCategoria.value = id; buscar(); };
const toggleFilter = (key) => {
  filters.value[key] = !filters.value[key];
  if (key === 'mas_cercano' && filters.value.mas_cercano && !userLat.value) {
    navigator.geolocation?.getCurrentPosition(
      (pos) => { userLat.value = pos.coords.latitude; userLng.value = pos.coords.longitude; locationText.value = 'GPS'; buscar(); },
      ()    => { filters.value.mas_cercano = false; }
    );
  } else { buscar(); }
};
const onSearchInput = () => {
  clearTimeout(debounceTimer);
  if (busqueda.value.length >= 2) {
    debounceTimer = setTimeout(async () => {
      try {
        const res = await axios.get('/api/directorio/sugerencias', { params: { q: busqueda.value } });
        if (res.data.success) { sugerencias.value = res.data.data; showSuggestions.value = true; }
      } catch { sugerencias.value = []; }
    }, 300);
  } else { sugerencias.value = []; showSuggestions.value = false; }
};
const selectSuggestion = (sug) => {
  busqueda.value = sug.texto; showSuggestions.value = false;
  if (sug.tipo === 'categoria' && sug.id) filtroCategoria.value = sug.id;
  else if (sug.tipo === 'negocio' && sug.slug) { window.location.href = `/${sug.slug}/book`; return; }
  buscar();
};
const clearSearch = () => { busqueda.value = ''; sugerencias.value = []; showSuggestions.value = false; buscar(); };
const buscarConQuery = () => { showSuggestions.value = false; buscar(); };
const aplicarBusquedaCorregida = () => { busqueda.value = correccionBusqueda.value; correccionBusqueda.value = ''; buscar(); };
const toggleLocationPanel = () => { showLocationPanel.value = !showLocationPanel.value; };
const aplicarUbicacionManual = async () => {
  if (!manualCiudad.value) return;
  const cityObj = ciudadesOptions.value.find(o => o.value === manualCiudad.value);
  const cityName = cityObj?.label || 'Ciudad';
  detectedPaisId.value = manualPais.value; detectedEstadoId.value = manualEstado.value; detectedCiudadId.value = manualCiudad.value;
  userLat.value = userLng.value = null;
  locationText.value = cityName; showLocationPanel.value = false;
  localStorage.setItem('citaspro_manual_pais_id', manualPais.value);
  localStorage.setItem('citaspro_manual_estado_id', manualEstado.value);
  localStorage.setItem('citaspro_manual_ciudad_id', manualCiudad.value);
  localStorage.setItem('citaspro_manual_ciudad_nombre', cityName);
  await buscar();
};
const resetearUbicacion = () => {
  ['pais_id','estado_id','ciudad_id','ciudad_nombre'].forEach(k => localStorage.removeItem(`citaspro_manual_${k}`));
  manualPais.value = manualEstado.value = manualCiudad.value = '';
  detectedPaisId.value = detectedEstadoId.value = detectedCiudadId.value = null;
  locationText.value = 'Buscando…'; showLocationPanel.value = false;
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      async (pos) => { userLat.value = pos.coords.latitude; userLng.value = pos.coords.longitude; locationText.value = 'GPS'; await buscar(); },
      async ()    => { locationText.value = 'Auto (IP)'; await buscar(); }
    );
  } else { buscar(); }
};
const trackClick = (id) => { axios.post(`/api/directorio/track-view/${id}`).catch(() => {}); };
const handleClickOutside = (e) => {
  if (searchContainerRef.value && !searchContainerRef.value.contains(e.target)) showSuggestions.value = false;
};
const getCategoryColor = (nombre) => {
  if (!nombre) return '#3B82F6';
  const n = nombre.toLowerCase();
  if (n.includes('clín') || n.includes('clin') || n.includes('salud') || n.includes('médi') || n.includes('dent') || n.includes('odont')) return '#3B82F6';
  if (n.includes('pelu') || n.includes('belle') || n.includes('esté') || n.includes('este') || n.includes('barb')) return '#EC4899';
  if (n.includes('fit') || n.includes('bien') || n.includes('gimn') || n.includes('depo')) return '#22C55E';
  if (n.includes('educ') || n.includes('clas') || n.includes('acad')) return '#6366F1';
  if (n.includes('veter') || n.includes('masc')) return '#06B6D4';
  if (n.includes('cons') || n.includes('abog') || n.includes('gest')) return '#F59E0B';
  if (n.includes('spa') || n.includes('masa') || n.includes('relax')) return '#A855F7';
  return '#3B82F6';
};
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════
   VARIABLES CSS — Sistema de diseño centralizado CitasPro
═══════════════════════════════════════════════════════════════ */
.dir-root {
  --dir-bg:            #0A0F1E;
  --dir-bg-card:       #111827;
  --dir-bg-card-hover: #162032;
  --dir-bg-surface:    rgba(255,255,255,0.04);
  --dir-border:        rgba(255,255,255,0.07);
  --dir-border-accent: rgba(59,130,246,0.25);
  --dir-text:          #F1F5F9;
  --dir-text-muted:    #64748B;
  --dir-primary:       #3B82F6;
  --dir-primary-glow:  rgba(59,130,246,0.35);
  --dir-secondary:     #06B6D4;
  --dir-cta-gradient:  linear-gradient(135deg, #3B82F6 0%, #06B6D4 100%);
  --dir-cta-glow:      0 4px 20px rgba(59,130,246,0.4);
  --dir-cta-glow-h:    0 6px 28px rgba(6,182,212,0.5);
  --card-radius:       16px;
  --t-fast:   150ms cubic-bezier(0.4,0,0.2,1);
  --t-normal: 300ms cubic-bezier(0.4,0,0.2,1);
  --t-slow:   500ms cubic-bezier(0.4,0,0.2,1);

  min-height: 100vh;
  background-color: var(--dir-bg);
  color: var(--dir-text);
  font-family: 'Inter','Segoe UI',system-ui,sans-serif;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ─── Header ─────────────────────────────────────────────── */
.dir-header {
  position: sticky; top: 0; z-index: 50;
  background: rgba(10,15,30,0.85);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--dir-border);
}
.dir-header-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  height: 64px; display: flex; align-items: center; justify-content: space-between;
}
.dir-brand { display: flex; align-items: center; gap: 0.5rem; }
.dir-brand-logo { height: 32px; object-fit: contain; }
.dir-brand-fallback {
  display: none; width: 32px; height: 32px; border-radius: 10px;
  background: linear-gradient(135deg, var(--dir-primary), var(--dir-secondary));
  align-items: center; justify-content: center;
  font-weight: 900; color: #fff; font-size: 14px;
}
.dir-brand-name {
  font-weight: 900; font-size: 1.1rem;
  background: linear-gradient(135deg, #fff, #94A3B8);
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.dir-brand-sep { font-size: 0.7rem; color: var(--dir-text-muted); border-left: 1px solid var(--dir-border); padding-left: 0.75rem; margin-left: 0.25rem; display: none; }
@media (min-width: 640px) { .dir-brand-sep { display: inline; } }
.dir-header-actions { display: flex; align-items: center; gap: 0.75rem; }
.dir-location-btn {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.4rem 0.9rem; border-radius: 999px;
  background: var(--dir-bg-surface); border: 1px solid var(--dir-border);
  color: var(--dir-text-muted); font-size: 0.72rem; font-weight: 600;
  cursor: pointer; transition: all var(--t-fast);
}
.dir-location-btn:hover { color: var(--dir-text); background: rgba(255,255,255,0.08); }
.dir-location-icon { width: 13px; height: 13px; color: var(--dir-primary); }
.dir-location-text { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dir-cta-header {
  padding: 0.45rem 1.1rem; border-radius: 999px;
  background: rgba(59,130,246,0.1); border: 1px solid rgba(59,130,246,0.2);
  color: var(--dir-primary); font-size: 0.72rem; font-weight: 700;
  transition: all var(--t-fast); text-decoration: none;
}
.dir-cta-header:hover { background: var(--dir-primary); color: #fff; }

/* ─── Hero ───────────────────────────────────────────────── */
.dir-hero {
  background: linear-gradient(180deg, rgba(59,130,246,0.05) 0%, transparent 100%);
  border-bottom: 1px solid var(--dir-border);
  padding: 3rem 0 2.5rem;
}
.dir-hero-inner {
  max-width: 1440px; margin: 0 auto; padding: 0 2rem;
  display: flex; flex-direction: column; gap: 1.75rem; align-items: center;
}
@media (min-width: 768px) { .dir-hero-inner { flex-direction: row; align-items: center; gap: 3rem; } }
.dir-hero-text { flex-shrink: 0; }
.dir-hero-title {
  font-size: clamp(1.6rem, 3vw, 2.5rem); font-weight: 900;
  text-transform: uppercase; letter-spacing: -0.02em; line-height: 1; color: #fff;
}
.dir-hero-highlight {
  background: linear-gradient(135deg, var(--dir-primary), var(--dir-secondary));
  -webkit-background-clip: text; -webkit-text-fill-color: transparent;
}
.dir-hero-sub { margin-top: 0.5rem; font-size: 0.82rem; color: var(--dir-text-muted); max-width: 280px; }

/* Buscador */
.dir-search-wrap { position: relative; width: 100%; max-width: 640px; flex: 1; }
.dir-search-box {
  display: flex; align-items: center;
  background: rgba(255,255,255,0.06); border: 1px solid var(--dir-border);
  border-radius: 14px; overflow: hidden;
  transition: border-color var(--t-fast), box-shadow var(--t-fast);
}
.dir-search-box:focus-within { border-color: var(--dir-primary); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
.dir-search-icon { width: 18px; height: 18px; color: var(--dir-text-muted); margin: 0 0 0 1.1rem; flex-shrink: 0; }
.dir-search-input {
  flex: 1; background: transparent; border: none; outline: none;
  color: var(--dir-text); font-size: 0.9rem; font-weight: 500;
  padding: 0.9rem 0.75rem; caret-color: var(--dir-primary);
}
.dir-search-input::placeholder { color: #475569; }
.dir-search-clear { background: none; border: none; color: var(--dir-text-muted); cursor: pointer; padding: 0 0.5rem; transition: color var(--t-fast); }
.dir-search-clear:hover { color: var(--dir-text); }
.dir-search-submit {
  padding: 0 1.25rem; height: 100%;
  background: var(--dir-cta-gradient); border: none; cursor: pointer;
  color: #fff; font-size: 0.78rem; font-weight: 700;
  letter-spacing: 0.05em; text-transform: uppercase;
  transition: opacity var(--t-fast); min-height: 44px;
}
.dir-search-submit:hover { opacity: 0.88; }
.dir-correction { margin-top: 0.5rem; font-size: 0.75rem; color: var(--dir-text-muted); display: flex; align-items: center; gap: 0.3rem; }
.dir-correction-btn { background: none; border: none; cursor: pointer; color: var(--dir-primary); font-weight: 700; text-decoration: underline; }
.dir-suggestions {
  position: absolute; top: calc(100% + 8px); left: 0; right: 0; z-index: 100;
  background: #111827; border: 1px solid var(--dir-border); border-radius: 14px;
  overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.dir-suggestion-item {
  display: flex; align-items: center; justify-content: space-between;
  padding: 0.65rem 1rem; cursor: pointer;
  transition: background var(--t-fast); border-bottom: 1px solid var(--dir-border);
}
.dir-suggestion-item:last-child { border-bottom: none; }
.dir-suggestion-item:hover { background: rgba(59,130,246,0.12); }
.dir-sug-left { display: flex; align-items: center; gap: 0.6rem; }
.dir-sug-icon { font-size: 1rem; }
.dir-sug-text { font-size: 0.83rem; color: var(--dir-text); font-weight: 500; }
.dir-sug-badge {
  font-size: 0.6rem; text-transform: uppercase; letter-spacing: 0.08em;
  font-weight: 700; color: var(--dir-text-muted);
  background: var(--dir-bg-surface); padding: 0.15rem 0.5rem;
  border-radius: 4px; border: 1px solid var(--dir-border);
}
.dir-suggest-enter-active { transition: all 100ms ease-out; }
.dir-suggest-leave-active { transition: all 75ms ease-in; }
.dir-suggest-enter-from { opacity: 0; transform: scale(0.97) translateY(-4px); }
.dir-suggest-enter-to   { opacity: 1; transform: scale(1) translateY(0); }

/* ─── Layout principal ───────────────────────────────────── */
.dir-main { max-width: 1440px; margin: 0 auto; padding: 2rem; }
.dir-layout { display: grid; grid-template-columns: 1fr; gap: 2rem; }
@media (min-width: 1024px) { .dir-layout { grid-template-columns: 240px 1fr; gap: 2.5rem; align-items: start; } }

/* ─── Sidebar ────────────────────────────────────────────── */
.dir-sidebar { display: flex; flex-direction: column; gap: 1.5rem; }
@media (min-width: 1024px) {
  .dir-sidebar { position: sticky; top: 84px; max-height: calc(100vh - 100px); overflow-y: auto; scrollbar-width: thin; scrollbar-color: var(--dir-border) transparent; }
}
.dir-location-panel { background: var(--dir-bg-card); border: 1px solid var(--dir-border); border-radius: 14px; padding: 1rem; }
.dir-lp-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.75rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--dir-border); }
.dir-lp-title { font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: var(--dir-text-muted); }
.dir-lp-close { background: none; border: none; cursor: pointer; color: var(--dir-text-muted); transition: color var(--t-fast); }
.dir-lp-close:hover { color: var(--dir-text); }
.dir-lp-fields { display: flex; flex-direction: column; gap: 0.6rem; }
.dir-field { display: flex; flex-direction: column; gap: 0.25rem; }
.dir-field-label { font-size: 0.65rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; color: var(--dir-text-muted); }
.dir-select-btn { width: 100%; padding: 0.5rem 0.75rem; background: rgba(0,0,0,0.3); border: 1px solid var(--dir-border); border-radius: 10px; color: var(--dir-text); font-size: 0.8rem; }
.dir-lp-actions { display: flex; gap: 0.5rem; margin-top: 0.5rem; }
.dir-btn-primary { flex: 1; padding: 0.55rem; background: var(--dir-primary); border: none; border-radius: 10px; color: #fff; font-size: 0.75rem; font-weight: 700; cursor: pointer; transition: opacity var(--t-fast); }
.dir-btn-primary:hover { opacity: 0.85; }
.dir-btn-ghost { padding: 0.55rem 0.75rem; background: var(--dir-bg-surface); border: 1px solid var(--dir-border); border-radius: 10px; color: var(--dir-text-muted); font-size: 0.75rem; font-weight: 600; cursor: pointer; transition: all var(--t-fast); }
.dir-btn-ghost:hover { color: var(--dir-text); }

.dir-section-label { display: block; font-size: 0.62rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.12em; color: var(--dir-text-muted); margin-bottom: 0.6rem; }
.dir-cats-grid { display: flex; flex-wrap: wrap; gap: 0.4rem; }
@media (min-width: 1024px) { .dir-cats-grid { flex-direction: column; gap: 0.3rem; } }
.dir-cat-pill {
  display: flex; align-items: center; gap: 0.4rem;
  padding: 0.35rem 0.7rem; border-radius: 999px;
  background: var(--dir-bg-surface); border: 1px solid var(--dir-border);
  color: var(--dir-text-muted); font-size: 0.72rem; font-weight: 600;
  cursor: pointer; transition: all var(--t-fast); text-align: left;
}
.dir-cat-pill:hover { color: var(--dir-text); background: rgba(255,255,255,0.08); }
.dir-cat-pill--active { background: rgba(59,130,246,0.15); border-color: rgba(59,130,246,0.4); color: #93C5FD; }
.dir-cat-pill-icon { width: 11px; height: 11px; color: var(--dir-primary); flex-shrink: 0; }
.dir-cat-pill-emoji { font-size: 0.85rem; flex-shrink: 0; }
.dir-cat-pill-name { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dir-filters-list { display: flex; flex-direction: column; gap: 0.3rem; }
.dir-filter-btn { padding: 0.4rem 0.75rem; border-radius: 10px; background: var(--dir-bg-surface); border: 1px solid var(--dir-border); color: var(--dir-text-muted); font-size: 0.72rem; font-weight: 600; cursor: pointer; text-align: left; transition: all var(--t-fast); }
.dir-filter-btn:hover { color: var(--dir-text); background: rgba(255,255,255,0.07); }
.dir-filter-btn--on { background: rgba(59,130,246,0.1); border-color: rgba(59,130,246,0.35); color: #93C5FD; }
.dir-featured-list { display: flex; flex-direction: column; gap: 0.5rem; }
.dir-featured-item { display: flex; align-items: center; gap: 0.6rem; padding: 0.55rem 0.65rem; border-radius: 12px; background: var(--dir-bg-surface); border: 1px solid var(--dir-border); text-decoration: none; transition: all var(--t-fast); }
.dir-featured-item:hover { background: rgba(255,255,255,0.07); border-color: var(--dir-border-accent); }
.dir-fi-img { width: 36px; height: 36px; border-radius: 9px; overflow: hidden; flex-shrink: 0; background: rgba(59,130,246,0.15); display: flex; align-items: center; justify-content: center; }
.dir-fi-initial { font-size: 1rem; font-weight: 900; color: var(--dir-primary); }
.dir-fi-info { flex: 1; overflow: hidden; }
.dir-fi-cat { display: block; font-size: 0.6rem; color: var(--dir-primary); font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
.dir-fi-name { display: block; font-size: 0.78rem; color: var(--dir-text); font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dir-fi-rating { display: flex; align-items: center; gap: 0.2rem; font-size: 0.68rem; font-weight: 700; color: #FBBF24; flex-shrink: 0; }

/* ─── Info bar ───────────────────────────────────────────── */
.dir-mosaic-section { display: flex; flex-direction: column; gap: 1.25rem; }
.dir-mosaic-bar { display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; color: var(--dir-text-muted); }
.dir-mosaic-count { font-weight: 600; }
.dir-mosaic-query { color: var(--dir-primary); font-weight: 700; }

/* ═══════════════════════════════════════════════════════════════
   MOTOR DE MASONRY — CSS Grid 4 columnas
═══════════════════════════════════════════════════════════════ */
.dir-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  grid-auto-rows: 115px;
  grid-auto-flow: dense;
  gap: 18px;
}
/* Footprints de tarjeta */
.dir-grid .card-xl { grid-column: span 3; grid-row: span 3; }  /* 20% */
.dir-grid .card-l  { grid-column: span 2; grid-row: span 2; }  /* 25% */
.dir-grid .card-m  { grid-column: span 2; grid-row: span 1; }  /* 30% */
.dir-grid .card-v  { grid-column: span 1; grid-row: span 2; }  /* 15% */
.dir-grid .card-s  { grid-column: span 1; grid-row: span 1; }  /* 10% */

/* Desplazamientos orgánicos */
.dir-card--offset-4  { margin-top: 4px;  }
.dir-card--offset-8  { margin-top: 8px;  }
.dir-card--offset-12 { margin-top: 12px; }
.dir-card--offset-16 { margin-top: 16px; }

/* Skeleton */
.dir-skeleton {
  border-radius: var(--card-radius);
  background: linear-gradient(90deg, rgba(255,255,255,0.04) 25%, rgba(255,255,255,0.07) 50%, rgba(255,255,255,0.04) 75%);
  background-size: 200% 100%;
  animation: dir-shimmer 1.6s infinite;
}
@keyframes dir-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.dir-empty { grid-column: 1 / -1; text-align: center; padding: 5rem 2rem; background: var(--dir-bg-surface); border: 1px solid var(--dir-border); border-radius: 20px; }
.dir-empty-icon { font-size: 3rem; display: block; margin-bottom: 1rem; }
.dir-empty-title { font-size: 1.1rem; font-weight: 900; text-transform: uppercase; color: #fff; }
.dir-empty-sub { font-size: 0.8rem; color: var(--dir-text-muted); margin-top: 0.5rem; }

/* ═══════════════════════════════════════════════════════════════
   TARJETA EDITORIAL
═══════════════════════════════════════════════════════════════ */
.dir-card {
  position: relative; border-radius: var(--card-radius);
  background: var(--dir-bg-card); border: 1px solid var(--dir-border);
  overflow: hidden; display: flex; flex-direction: column;
  transition: transform var(--t-normal), box-shadow var(--t-normal), border-color var(--t-normal);
  will-change: transform;
}
.dir-card:hover {
  transform: translateY(-6px) scale(1.01);
  box-shadow: 0 16px 45px rgba(0,0,0,0.45), 0 4px 20px var(--dir-primary-glow);
  border-color: rgba(59,130,246,0.25);
  z-index: 2;
}
/* Barra superior de acento (aparece en hover) */
.dir-card-accent-bar {
  position: absolute; top: 0; left: 0; right: 0; height: 2px;
  background: var(--accent-color, var(--dir-primary));
  opacity: 0; transition: opacity var(--t-normal); z-index: 10;
}
.dir-card:hover .dir-card-accent-bar { opacity: 1; }
/* Imagen de portada */
.dir-card-cover { position: absolute; inset: 0; z-index: 0; }
.dir-card-cover-img { width: 100%; height: 100%; object-fit: cover; transition: transform var(--t-slow); }
.dir-card:hover .dir-card-cover-img { transform: scale(1.03); }
.dir-card-cover-overlay {
  position: absolute; inset: 0;
  background: linear-gradient(to top, rgba(10,15,30,0.96) 0%, rgba(10,15,30,0.5) 55%, rgba(10,15,30,0.08) 100%);
}
/* Cuerpo */
.dir-card-body {
  position: relative; z-index: 5;
  display: flex; flex-direction: column; justify-content: space-between;
  flex: 1; padding: 1rem; gap: 0.75rem;
}
.dir-card-body--over-image {
  position: absolute; bottom: 0; left: 0; right: 0; background: transparent;
}
/* Cabecera */
.dir-card-header { display: flex; align-items: flex-start; gap: 0.75rem; }
.dir-card-logo-wrap {
  width: 48px; height: 48px; border-radius: 12px;
  border: 1px solid rgba(255,255,255,0.1);
  overflow: hidden; background: rgba(0,0,0,0.4);
  flex-shrink: 0; display: flex; align-items: center; justify-content: center;
}
.dir-card-logo { width: 100%; height: 100%; object-fit: cover; transition: transform var(--t-normal); }
.dir-card:hover .dir-card-logo { transform: scale(1.05); }
.dir-card-logo-initial { font-size: 1.25rem; font-weight: 900; color: rgba(255,255,255,0.3); }
.card-xl .dir-card-logo-wrap { width: 60px; height: 60px; border-radius: 14px; }
.dir-card-meta { flex: 1; overflow: hidden; }
.dir-card-cat {
  display: block; font-size: 0.6rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.14em;
  color: var(--accent-color, var(--dir-primary));
  margin-bottom: 0.2rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.dir-card-name {
  font-size: 0.92rem; font-weight: 700; color: #fff; line-height: 1.2;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  transition: color var(--t-fast);
}
.dir-card:hover .dir-card-name { color: #BAE6FD; }
.card-xl .dir-card-name { font-size: 1.25rem; white-space: normal; }
.card-l  .dir-card-name { font-size: 1.05rem; }
.dir-card-stats { display: flex; align-items: center; gap: 0.75rem; margin-top: 0.35rem; flex-wrap: wrap; }
.dir-card-rating { display: flex; align-items: center; gap: 0.25rem; font-size: 0.7rem; font-weight: 700; }
.dir-star-icon { width: 11px; height: 11px; fill: #FBBF24; color: #FBBF24; }
.dir-rating-val { color: #fff; }
.dir-rating-count { color: var(--dir-text-muted); font-weight: 500; }
.dir-card-city { font-size: 0.65rem; color: var(--dir-text-muted); font-weight: 500; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dir-verified-badge {
  width: 18px; height: 18px; border-radius: 50%;
  background: rgba(59,130,246,0.15); border: 1px solid rgba(59,130,246,0.3);
  display: flex; align-items: center; justify-content: center;
  font-size: 0.6rem; font-weight: 900; color: var(--dir-primary); flex-shrink: 0;
}
/* Pie de tarjeta */
.dir-card-footer {
  display: flex; align-items: flex-end; justify-content: space-between; gap: 0.75rem;
  margin-top: auto; padding-top: 0.6rem; border-top: 1px solid rgba(255,255,255,0.05);
}
/* Fecha */
.dir-card-date { display: flex; align-items: center; gap: 0.4rem; user-select: none; flex-shrink: 0; }
.dir-date-day { font-size: 2rem; font-weight: 900; color: #fff; line-height: 1; letter-spacing: -0.04em; }
.card-xl .dir-date-day { font-size: 3rem; }
.card-l  .dir-date-day { font-size: 2.4rem; }
.card-s  .dir-date-day { font-size: 1.4rem; }
.card-v  .dir-date-day { font-size: 1.8rem; }
.dir-date-labels { display: flex; flex-direction: column; }
.dir-date-weekday { font-size: 0.55rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em; color: rgba(255,255,255,0.4); line-height: 1.2; }
.dir-date-month { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; letter-spacing: 0.08em; color: var(--accent-color, var(--dir-secondary)); line-height: 1.2; }
/* CTA Botón Premium */
.dir-cta-btn {
  display: inline-flex; align-items: center; gap: 0.35rem;
  padding: 0.55rem 1.1rem; border-radius: 999px;
  background: var(--dir-cta-gradient); box-shadow: var(--dir-cta-glow);
  color: #fff; font-size: 0.68rem; font-weight: 800;
  text-transform: uppercase; letter-spacing: 0.08em;
  text-decoration: none; white-space: nowrap; flex-shrink: 0;
  transition: box-shadow var(--t-normal), transform var(--t-normal), opacity var(--t-fast);
}
.dir-cta-btn:hover { box-shadow: var(--dir-cta-glow-h); transform: translateY(-1px); }
.dir-cta-btn:active { transform: scale(0.97); opacity: 0.9; }
.dir-cta-arrow { transition: transform var(--t-normal); }
.dir-cta-btn:hover .dir-cta-arrow { transform: translateX(3px); }
/* Tarjetas pequeñas: CTA ocupa todo el ancho */
.card-s .dir-card-footer { flex-direction: column; align-items: stretch; gap: 0.5rem; }
.card-s .dir-cta-btn { width: 100%; justify-content: center; }

/* ─── Paginación ─────────────────────────────────────────── */
.dir-pagination { display: flex; justify-content: center; gap: 0.5rem; padding-top: 2rem; border-top: 1px solid var(--dir-border); }
.dir-page-btn { width: 38px; height: 38px; border-radius: 10px; background: var(--dir-bg-surface); border: 1px solid var(--dir-border); color: var(--dir-text-muted); font-size: 0.78rem; font-weight: 700; cursor: pointer; transition: all var(--t-fast); }
.dir-page-btn:hover { background: rgba(255,255,255,0.08); color: var(--dir-text); }
.dir-page-btn--active { background: var(--dir-primary); border-color: var(--dir-primary); color: #fff; box-shadow: 0 4px 12px rgba(59,130,246,0.35); }

/* ─── Footer ─────────────────────────────────────────────── */
.dir-footer { border-top: 1px solid var(--dir-border); background: rgba(0,0,0,0.3); padding: 2.5rem 2rem; text-align: center; margin-top: 3rem; }
.dir-footer-brand { display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-bottom: 0.75rem; opacity: 0.3; transition: opacity var(--t-normal); }
.dir-footer-brand:hover { opacity: 0.7; }
.dir-footer-name { font-weight: 900; letter-spacing: 0.15em; text-transform: uppercase; font-size: 0.8rem; color: var(--dir-text-muted); }
.dir-footer-copy { font-size: 0.72rem; color: #374151; max-width: 480px; margin: 0 auto; line-height: 1.6; }

/* ═══════════════════════════════════════════════════════════════
   RESPONSIVE
═══════════════════════════════════════════════════════════════ */
@media (min-width: 640px) and (max-width: 1023px) {
  .dir-grid { grid-template-columns: repeat(2, 1fr); grid-auto-rows: 130px; gap: 16px; }
  .dir-grid .card-xl { grid-column: span 2; grid-row: span 2; }
  .dir-grid .card-l  { grid-column: span 2; grid-row: span 2; }
  .dir-grid .card-m  { grid-column: span 2; grid-row: span 1; }
  .dir-grid .card-v  { grid-column: span 1; grid-row: span 2; }
  .dir-grid .card-s  { grid-column: span 1; grid-row: span 1; }
}
@media (max-width: 639px) {
  .dir-hero { padding: 2rem 0 1.5rem; }
  .dir-main { padding: 1.25rem; }
  .dir-grid { grid-template-columns: 1fr; grid-auto-rows: auto; gap: 14px; }
  .dir-grid .card-xl,
  .dir-grid .card-l  { grid-column: span 1; grid-row: span 1; min-height: 260px; }
  .dir-grid .card-v  { grid-column: span 1; grid-row: span 1; min-height: 210px; }
  .dir-grid .card-m,
  .dir-grid .card-s  { grid-column: span 1; grid-row: span 1; min-height: 150px; }
  .dir-card--offset-4, .dir-card--offset-8, .dir-card--offset-12, .dir-card--offset-16 { margin-top: 0; }
  .dir-sidebar { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
  .dir-cats-grid { flex-direction: row; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 0.5rem; }
  .dir-cat-pill-name { display: none; }
}
</style>

