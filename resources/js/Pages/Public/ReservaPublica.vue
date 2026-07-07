<template>
  <div class="min-h-screen bg-gray-950 text-white relative">
    <!-- Selector de Idioma Flotante -->
    <div class="absolute top-4 right-4 z-50">
      <LanguageSwitcher />
    </div>

    <!-- Hero Header del Negocio -->
    <div v-if="negocio" class="relative">
      <!-- Cover Image / Gradiente conteniendo la info para estar en la franja -->
      <div
        :class="[
          'bg-gradient-to-br from-violet-900 via-indigo-900 to-gray-950 relative overflow-hidden flex flex-col justify-center border-b border-indigo-500/10 transition-all duration-300',
          confirmacion ? 'py-6 min-h-[110px]' : 'min-h-[280px] py-12 md:py-16'
        ]"
        :style="negocio.cover_imagen ? `background-image: url(${negocio.cover_imagen}); background-size: cover; background-position: center;` : ''"
      >
        <div class="absolute inset-0 bg-black/65"></div>
        
        <!-- Info del negocio adentro del banner -->
        <div class="max-w-2xl w-full mx-auto px-4 relative z-10 space-y-6">
          
          <!-- Logo, Nombre, Categoría, Ciudad -->
          <div class="flex items-center gap-4">
            <div class="w-16 h-16 md:w-20 md:h-20 rounded-2xl border-2 border-white/10 overflow-hidden bg-indigo-900 flex items-center justify-center shadow-2xl flex-shrink-0">
              <img v-if="negocio.logo" :src="negocio.logo" :alt="negocio.nombre" class="w-full h-full object-cover" />
              <span v-else class="text-2xl font-black text-white">{{ negocio.nombre?.charAt(0) }}</span>
            </div>
            <div>
              <h1 class="text-xl md:text-2xl font-black text-white leading-tight drop-shadow-md">{{ negocio.nombre }}</h1>
              <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs md:text-sm">
                <span v-if="negocio.categoria" class="text-indigo-300 font-semibold drop-shadow-sm">{{ negocio.categoria.icono }} {{ negocio.categoria.nombre }}</span>
                <span v-if="negocio.ciudad" class="text-gray-300 drop-shadow-sm">📍 {{ negocio.ciudad }}</span>
              </div>
            </div>
          </div>

          <!-- Detalles del negocio y Contacto (Solo si no hay confirmación) -->
          <template v-if="!confirmacion">
            <p v-if="negocio.booking_mensaje" class="text-gray-300 text-sm leading-relaxed drop-shadow-sm max-w-xl">
              {{ negocio.booking_mensaje }}
            </p>
            <p v-else-if="negocio.descripcion" class="text-gray-400 text-sm leading-relaxed drop-shadow-sm max-w-xl">{{ negocio.descripcion }}</p>

            <!-- Información de Contacto, Nro Fiscal y Horarios -->
            <div class="bg-black/45 border border-white/10 rounded-2xl p-4 space-y-4 text-xs text-gray-300 backdrop-blur-md">
              <div class="flex flex-wrap items-start justify-between gap-4">
                <div>
                  <p class="font-bold text-white mb-1">Contacto:</p>
                  <ul class="space-y-1">
                    <li class="flex items-center gap-1">
                      <span class="text-gray-400">Principal:</span>
                      <a :href="'tel:' + negocio.telefono" class="text-indigo-300 hover:underline font-semibold">{{ negocio.telefono }}</a>
                      <span v-if="!negocio.telefonos_adicionales?.length || negocio.verification_phone_index === null || negocio.verification_phone_index === undefined" class="bg-green-500/20 text-green-400 px-1.5 py-0.5 rounded text-[10px]">Verif. SMS</span>
                    </li>
                    <li v-for="(phone, idx) in negocio.telefonos_adicionales" :key="idx" class="flex items-center gap-1">
                      <span class="text-gray-400 capitalize">{{ phone.type === 'mobile' ? 'Móvil' : (phone.type === 'fax' ? 'Fax' : 'Local') }}:</span>
                      <a :href="'tel:' + phone.number" class="text-indigo-300 hover:underline font-semibold">{{ phone.number }}</a>
                      <span v-if="negocio.verification_phone_index === idx" class="bg-green-500/20 text-green-400 px-1.5 py-0.5 rounded text-[10px]">Verif. SMS</span>
                    </li>
                  </ul>
                </div>
                
                <div v-if="negocio.numero_fiscal">
                  <p class="font-bold text-white mb-1">Nro Fiscal:</p>
                  <p class="text-xs bg-white/5 border border-white/10 px-2.5 py-1 rounded-lg text-gray-200 font-mono">{{ negocio.numero_fiscal }}</p>
                </div>

                <div>
                  <p class="font-bold text-white mb-1">Horario de hoy:</p>
                  <p class="text-indigo-300 font-semibold">{{ todayHours }}</p>
                  <button 
                    @click="showFullSchedule = !showFullSchedule"
                    class="text-[10px] text-gray-400 hover:text-white underline mt-1 block"
                  >
                    {{ showFullSchedule ? 'Ocultar horarios' : 'Ver todos los horarios' }}
                  </button>
                </div>
              </div>

              <!-- Tabla completa de horarios -->
              <div v-if="showFullSchedule" class="pt-3 border-t border-white/10 animate-in fade-in duration-200">
                <p class="font-bold text-white mb-2 text-xs">Horarios Semanales:</p>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                  <div 
                    v-for="d in diasList" 
                    :key="d.key"
                    class="bg-white/5 p-2 rounded-lg border border-white/5 flex flex-col justify-between"
                  >
                    <span class="font-semibold text-gray-200 capitalize text-[10px]">{{ d.label }}</span>
                    <span v-if="negocio.horario_apertura?.[d.key]?.cerrado" class="text-red-400 font-medium">Cerrado</span>
                    <span v-else class="text-gray-400 font-mono">
                      {{ negocio.horario_apertura?.[d.key]?.inicio || '09:00' }} - {{ negocio.horario_apertura?.[d.key]?.fin || '18:00' }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </template>

        </div>
      </div>
    </div>

    <!-- Loading inicial -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-20">
      <div class="w-12 h-12 border-4 border-indigo-500 border-t-transparent rounded-full animate-spin mb-4"></div>
      <p class="text-gray-400">{{ $t ? $t('reserva.cargando') : 'Cargando...' }}</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="max-w-2xl mx-auto px-4 py-20 text-center">
      <p class="text-5xl mb-4">😕</p>
      <h2 class="text-xl font-bold text-white mb-2">{{ $t ? $t('reserva.no_disponible') : 'No disponible' }}</h2>
      <p class="text-gray-400">{{ error }}</p>
    </div>

    <!-- Formulario de Reserva -->
    <div v-else-if="negocio" class="max-w-2xl mx-auto px-4 py-8 pb-16">

      <!-- PASO 1: Confirmar Reserva (éxito) -->
      <div v-if="confirmacion" class="text-center py-6 md:py-12 animate-in fade-in duration-300">


        <div class="w-20 h-20 rounded-full bg-green-500/20 border-2 border-green-500 flex items-center justify-center mx-auto mb-6">
          <span class="text-4xl">✅</span>
        </div>
        <h2 class="text-2xl font-black text-white mb-2">{{ $t ? $t('reserva.cita_confirmada') : '¡Cita Confirmada!' }}</h2>
        <p class="text-gray-400 mb-6">{{ $t ? $t('reserva.reserva_registrada') : 'Tu reserva fue registrada exitosamente.' }}</p>

        <div class="bg-white/5 border border-white/10 rounded-2xl p-6 text-left space-y-3 mb-8">
          <div class="flex justify-between"><span class="text-gray-400">{{ $t ? $t('reserva.servicio') : 'Servicio:' }}</span><span class="font-medium">{{ confirmacion.servicio }}</span></div>
          <div class="flex justify-between"><span class="text-gray-400">{{ $t ? $t('reserva.profesional') : 'Profesional:' }}</span><span class="font-medium">{{ confirmacion.profesional }}</span></div>
          <div class="flex justify-between"><span class="text-gray-400">{{ $t ? $t('reserva.fecha') : 'Fecha:' }}</span><span class="font-medium">{{ confirmacion.fecha }}</span></div>
          <div class="flex justify-between"><span class="text-gray-400">{{ $t ? $t('reserva.hora') : 'Hora:' }}</span><span class="font-medium">{{ confirmacion.hora }}</span></div>
          <div class="flex justify-between"><span class="text-gray-400">{{ $t ? $t('reserva.codigo') : 'Código:' }}</span><span class="font-mono text-indigo-300 font-bold">{{ confirmacion.codigo_referencia }}</span></div>
        </div>

        <button @click="reiniciar" class="w-full py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold transition-all">
          {{ $t ? $t('reserva.otra_reserva') : 'Hacer otra reserva' }}
        </button>
      </div>

      <!-- Flujo de Reserva -->
      <div v-else class="space-y-6 pt-4">

        <!-- ── Paso 1: Elegir Servicio ── -->
        <section>
          <h2 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-black">1</span>
            {{ $t ? $t('reserva.elige_servicio') : 'Elige un servicio' }}
          </h2>
          <div class="grid grid-cols-1 gap-3">
            <button
              v-for="s in servicios"
              :key="s.id"
              @click="form.servicio_id = s.id; cargarProfesionalesYFecha()"
              :class="[
                'text-left p-4 rounded-xl border transition-all',
                form.servicio_id === s.id
                  ? 'border-indigo-500 bg-indigo-500/10 shadow-[0_0_15px_rgba(99,102,241,0.2)]'
                  : 'border-white/10 bg-white/5 hover:border-white/20'
              ]"
            >
              <div class="flex justify-between items-start">
                <div>
                  <p class="font-bold text-white">{{ s.nombre }}</p>
                  <p v-if="s.descripcion" class="text-sm text-gray-400 mt-0.5">{{ s.descripcion }}</p>
                </div>
                <div class="text-right ml-4 flex-shrink-0">
                  <p class="font-bold text-indigo-300">{{ s.precio_desde ? 'Desde ' : '' }}{{ s.precio }} {{ s.moneda || '€' }}</p>
                  <p class="text-xs text-gray-400">{{ s.duracion_min }} min</p>
                </div>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Paso 2: Elegir Profesional ── -->
        <section v-if="form.servicio_id && profesionales.length > 0" ref="step2">
          <h2 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-black">2</span>
            {{ $t ? $t('reserva.elige_profesional') : 'Elige tu profesional' }}
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <button
              v-for="p in profesionales"
              :key="p.id"
              @click="form.profesional_id = p.id; slots = []; form.hora = ''"
              :class="[
                'text-left p-4 rounded-xl border transition-all flex gap-3 items-center',
                form.profesional_id === p.id
                  ? 'border-indigo-500 bg-indigo-500/10'
                  : 'border-white/10 bg-white/5 hover:border-white/20'
              ]"
            >
              <div class="w-12 h-12 rounded-xl bg-indigo-900 flex items-center justify-center flex-shrink-0 overflow-hidden">
                <img v-if="p.foto" :src="p.foto" class="w-full h-full object-cover" :alt="p.nombre_completo" />
                <span v-else class="text-lg font-black">{{ p.nombre_completo?.charAt(0) }}</span>
              </div>
              <div>
                <p class="font-bold text-white">{{ p.nombre_completo }}</p>
                <p v-if="p.titulo" class="text-xs text-indigo-300">{{ p.titulo }}</p>
                <p v-if="p.calificacion_promedio" class="text-xs text-yellow-400">⭐ {{ p.calificacion_promedio }}</p>
              </div>
            </button>
          </div>
        </section>

        <!-- ── Paso 3: Elegir Fecha y Hora ── -->
        <section v-if="form.profesional_id" ref="step3">
          <h2 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-black">3</span>
            {{ $t ? $t('reserva.elige_fecha_hora') : 'Elige fecha y hora' }}
          </h2>
          <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-4">
            <div>
              <label class="block text-sm text-gray-400 mb-1">{{ $t ? $t('reserva.selecciona_dia') : 'Fecha' }}</label>
              <input
                type="date"
                v-model="form.fecha"
                :min="hoy"
                @change="cargarSlots"
                class="w-full bg-black/50 border border-white/20 rounded-xl px-4 py-2.5 text-white focus:outline-none focus:border-indigo-500"
              />
            </div>

            <!-- Slots de Hora -->
            <div v-if="form.fecha" ref="slotsContainer">
              <label class="block text-sm text-gray-400 mb-2">{{ $t ? $t('reserva.selecciona_hora') : 'Hora disponible' }}</label>
              <div v-if="loadingSlots" class="text-gray-400 text-sm">{{ $t ? $t('reserva.cargando_horarios') : 'Cargando horarios...' }}</div>
              <div v-else-if="slots.length === 0" class="text-gray-500 text-sm italic">{{ $t ? $t('reserva.sin_horarios') : 'No hay horarios disponibles para esta fecha.' }}</div>
              <div v-else class="flex flex-wrap gap-2">
                <button
                  v-for="slot in slots"
                  :key="slot"
                  @click="form.hora = slot"
                  :class="[
                    'px-4 py-2 rounded-xl text-sm font-bold transition-all',
                    form.hora === slot
                      ? 'bg-indigo-600 text-white shadow-[0_0_12px_rgba(99,102,241,0.4)]'
                      : 'bg-white/5 border border-white/10 hover:border-indigo-500/50 text-gray-300'
                  ]"
                >
                  {{ slot }}
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- ── Paso 4: Tus Datos ── -->
        <section v-if="form.hora" ref="step4">
          <h2 class="text-lg font-bold mb-3 flex items-center gap-2">
            <span class="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-sm font-black">4</span>
            {{ $t ? $t('reserva.datos_personales') : 'Tus datos de contacto' }}
          </h2>
          <div class="bg-white/5 border border-white/10 rounded-xl p-4 space-y-3">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs text-gray-400 mb-1">{{ $t ? $t('reserva.nombre_req') : 'Nombre *' }}</label>
                <input v-model="form.cliente_nombre" type="text" :placeholder="$t ? $t('reserva.tu_nombre') : 'Tu nombre'"
                  class="w-full bg-black/50 border border-white/20 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500" />
              </div>
              <div>
                <label class="block text-xs text-gray-400 mb-1">{{ $t ? $t('reserva.apellido') : 'Apellido' }}</label>
                <input v-model="form.cliente_apellido" type="text" :placeholder="$t ? $t('reserva.tu_apellido') : 'Tu apellido'"
                  class="w-full bg-black/50 border border-white/20 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">{{ $t ? $t('reserva.telefono_req') : 'Teléfono *' }}</label>
              <div class="flex gap-2">
                <div class="w-32 shrink-0">
                  <CustomSelect 
                    :modelValue="form.pais_prefijo"
                    @update:modelValue="form.pais_prefijo = $event"
                    :options="prefijosOptions"
                    buttonClass="px-2 py-2 text-sm bg-black/50 border-white/20 focus:border-indigo-500"
                  />
                </div>
                <input v-model="form.telefono_numero" type="tel" :placeholder="$t ? $t('reserva.numero') : 'Número'"
                  class="flex-1 bg-black/50 border border-white/20 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500" />
              </div>
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">{{ $t ? $t('reserva.email_opt') : 'Email (opcional)' }}</label>
              <input v-model="form.cliente_email" type="email" placeholder="tu@email.com"
                class="w-full bg-black/50 border border-white/20 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500" />
            </div>
            <div>
              <label class="block text-xs text-gray-400 mb-1">{{ $t ? $t('reserva.nota_opt') : 'Nota (opcional)' }}</label>
              <textarea v-model="form.notas_cliente" rows="2" :placeholder="$t ? $t('reserva.indicacion') : 'Alguna indicación especial...'"
                class="w-full bg-black/50 border border-white/20 rounded-xl px-3 py-2 text-white text-sm focus:outline-none focus:border-indigo-500 resize-none">
              </textarea>
            </div>
          </div>

          <!-- Historia Clínica Dinámica -->
          <div v-if="clinicalTemplate && requiereHistorial" class="mt-4" ref="clinicalFormSection">
            <ClinicalForm 
              :fields="clinicalTemplate"
              v-model="form.respuestas_clinicas"
            />
          </div>
        </section>

        <!-- Error de envío -->
        <div v-if="submitError" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-red-400 text-sm">
          {{ submitError }}
        </div>

        <!-- Botón Reservar -->
        <button
          v-if="form.hora && form.cliente_nombre && form.telefono_numero"
          @click="reservar"
          :disabled="sending"
          class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed font-black text-lg transition-all shadow-[0_0_20px_rgba(99,102,241,0.3)] hover:shadow-[0_0_30px_rgba(99,102,241,0.5)]"
        >
          {{ sending ? ($t ? $t('reserva.reservando') : 'Reservando...') : ($t ? $t('reserva.confirmar_reserva') : '✅ Confirmar Cita') }}
        </button>
      </div>
    </div>

    <!-- Modal de Confirmación Reusable -->
    <ConfirmModal 
      v-model:show="showConfirmModal"
      title="Confirmar Cita"
      message="¿Está de acuerdo en completar la cita y enviar su información?"
      type="question"
      confirmText="Sí, Confirmar"
      cancelText="Cancelar"
      @confirm="confirmarReservaFinal"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, watch, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import LanguageSwitcher from '../Components/LanguageSwitcher.vue';
import CustomSelect from '../Components/CustomSelect.vue';
import ClinicalForm from '../Components/ClinicalForm.vue';
import ConfirmModal from '../Components/ConfirmModal.vue';

const route = useRoute();
const router = useRouter();
const slug = computed(() => route.params.slug);

// Refs para auto-scrolling
const step2 = ref(null);
const step3 = ref(null);
const step4 = ref(null);
const slotsContainer = ref(null);
const clinicalFormSection = ref(null);

const scrollToElement = (el) => {
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
};

// Estado
const loading = ref(true);
const error = ref(null);
const negocio = ref(null);
const servicios = ref([]);
const profesionales = ref([]);
const slots = ref([]);
const loadingSlots = ref(false);
const sending = ref(false);
const submitError = ref(null);
const confirmacion = ref(null);
const paises = ref([]);
const requiereHistorial = ref(false);
const clinicalTemplate = ref(null);
const showConfirmModal = ref(false);

const showFullSchedule = ref(false);

const diasList = [
  { key: 'lun', label: 'Lunes' },
  { key: 'mar', label: 'Martes' },
  { key: 'mié', label: 'Miércoles' },
  { key: 'jue', label: 'Jueves' },
  { key: 'vie', label: 'Viernes' },
  { key: 'sáb', label: 'Sábado' },
  { key: 'dom', label: 'Domingo' }
];

const todayHours = computed(() => {
  if (!negocio.value || !negocio.value.horario_apertura) return 'No disponible';
  const daysMap = ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'];
  const todayKey = daysMap[new Date().getDay()];
  const todaySchedule = negocio.value.horario_apertura[todayKey];
  if (!todaySchedule) return 'No disponible';
  if (todaySchedule.cerrado) return 'Cerrado';
  return `${todaySchedule.inicio} - ${todaySchedule.fin}`;
});

const prefijosOptions = computed(() => paises.value.map(p => ({
  value: p.prefijo,
  label: p.prefijo,
  icon: p.bandera || p.emoji || '🏳️'
})));

const hoy = new Date().toISOString().split('T')[0];

const form = ref({
  servicio_id: null,
  profesional_id: route.query.pro ? parseInt(route.query.pro) : null,
  fecha: '',
  hora: '',
  cliente_nombre: '',
  cliente_apellido: '',
  pais_prefijo: '+34',
  telefono_numero: '',
  cliente_email: '',
  notas_cliente: '',
  respuestas_clinicas: {},
});

let checkTimer = null;
const checkClienteRequiereHistorial = () => {
  if (!form.value.telefono_numero || form.value.telefono_numero.length < 7) {
    requiereHistorial.value = false;
    return;
  }
  
  if (checkTimer) clearTimeout(checkTimer);
  checkTimer = setTimeout(async () => {
    try {
      let prefijo = form.value.pais_prefijo || '';
      if (prefijo && !prefijo.startsWith('+')) prefijo = '+' + prefijo;
      const telefonoCompleto = prefijo + form.value.telefono_numero;

      const res = await axios.get(`/api/public/${slug.value}/check-cliente`, {
        params: { telefono: telefonoCompleto }
      });
      requiereHistorial.value = !!res.data.requiere_historial;
    } catch {
      requiereHistorial.value = false;
    }
  }, 400);
};

watch(() => form.value.telefono_numero, checkClienteRequiereHistorial);
watch(() => form.value.pais_prefijo, checkClienteRequiereHistorial);

// Observadores para auto-scrolling
watch(() => form.value.servicio_id, async (newVal) => {
  if (newVal) {
    await nextTick();
    scrollToElement(step2.value);
  }
});

watch(() => form.value.profesional_id, async (newVal) => {
  if (newVal) {
    await nextTick();
    scrollToElement(step3.value);
  }
});

watch(() => form.value.hora, async (newVal) => {
  if (newVal) {
    await nextTick();
    scrollToElement(step4.value);
  }
});

watch(requiereHistorial, async (newVal) => {
  if (newVal) {
    await nextTick();
    scrollToElement(clinicalFormSection.value);
  }
});

// Carga inicial: info del negocio y países
onMounted(async () => {
  await Promise.all([cargarNegocio(), cargarPaises()]);
});

const cargarNegocio = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`/api/public/${slug.value}`);
    if (res.data.success) {
      negocio.value = res.data.negocio;
      servicios.value = res.data.servicios;
      profesionales.value = res.data.profesionales;
      clinicalTemplate.value = res.data.plantilla_historia_clinica;

      // Si viene ?pro=ID en la URL, pre-seleccionar ese profesional
      if (route.query.pro) {
        const proId = parseInt(route.query.pro);
        const existe = profesionales.value.find(p => p.id === proId);
        if (existe) form.value.profesional_id = proId;
      }

      // Si viene ?service_id=ID en la URL, pre-seleccionar ese servicio y cargar profesionales
      if (route.query.service_id) {
        const serviceId = parseInt(route.query.service_id);
        const existe = servicios.value.find(s => s.id === serviceId);
        if (existe) {
          form.value.servicio_id = serviceId;
          cargarProfesionalesYFecha();
        }
      }
    } else {
      error.value = res.data.message || 'No se pudo cargar el negocio.';
    }
  } catch {
    error.value = 'Este negocio no está disponible para reservas online.';
  } finally {
    loading.value = false;
  }
};

const cargarPaises = async () => {
  try {
    const res = await axios.get('/api/paises');
    paises.value = res.data;
    // Prefijo por defecto según el negocio si hay país
    if (negocio.value?.pais) {
      const pais = paises.value.find(p => p.codigo === negocio.value.pais);
      if (pais) form.value.pais_prefijo = pais.prefijo;
    }
  } catch {
    // Si falla, usar lista mínima de respaldo
    paises.value = [
      { id: 1, prefijo: '+34', emoji: '🇪🇸', codigo: 'ES' },
      { id: 2, prefijo: '+58', emoji: '🇻🇪', codigo: 'VE' },
      { id: 3, prefijo: '+1',  emoji: '🇺🇸', codigo: 'US' },
      { id: 4, prefijo: '+52', emoji: '🇲🇽', codigo: 'MX' },
      { id: 5, prefijo: '+57', emoji: '🇨🇴', codigo: 'CO' },
    ];
  }
};

const cargarProfesionalesYFecha = () => {
  // Al cambiar de servicio, si ya hay profesional seleccionado cargar slots
  form.value.hora = '';
  slots.value = [];
  if (form.value.profesional_id && form.value.fecha) {
    cargarSlots();
  }
};

const cargarSlots = async () => {
  if (!form.value.fecha || !form.value.profesional_id) return;
  loadingSlots.value = true;
  slots.value = [];
  form.value.hora = '';
  try {
    const res = await axios.get(`/api/public/${slug.value}/disponibilidad`, {
      params: {
        fecha: form.value.fecha,
        profesional_id: form.value.profesional_id,
      },
    });
    if (res.data.success) {
      slots.value = res.data.disponibles;
      await nextTick();
      scrollToElement(slotsContainer.value);
    }
  } catch {
    slots.value = [];
  } finally {
    loadingSlots.value = false;
  }
};

const reservar = () => {
  showConfirmModal.value = true;
};

const confirmarReservaFinal = async () => {
  submitError.value = null;
  sending.value = true;

  let prefijo = form.value.pais_prefijo || '';
  if (prefijo && !prefijo.startsWith('+')) prefijo = '+' + prefijo;
  const telefonoCompleto = prefijo + form.value.telefono_numero;

  try {
    const res = await axios.post(`/api/public/${slug.value}/reservar`, {
      servicio_id: form.value.servicio_id,
      profesional_id: form.value.profesional_id,
      fecha: form.value.fecha,
      hora: form.value.hora,
      cliente_nombre: form.value.cliente_nombre,
      cliente_apellido: form.value.cliente_apellido,
      cliente_telefono: telefonoCompleto,
      cliente_email: form.value.cliente_email,
      notas_cliente: form.value.notas_cliente,
      respuestas_clinicas: form.value.respuestas_clinicas,
    });

    if (res.data.success) {
      confirmacion.value = res.data.cita;
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
      submitError.value = res.data.message || 'Ocurrió un error.';
    }
  } catch (err) {
    submitError.value = err.response?.data?.message
      || err.response?.data?.errors
        ? Object.values(err.response.data.errors).flat().join(' ')
        : 'Ocurrió un error al procesar tu reserva.';
  } finally {
    sending.value = false;
  }
};

const reiniciar = () => {
  confirmacion.value = null;
  form.value = {
    servicio_id: null,
    profesional_id: null,
    fecha: '',
    hora: '',
    cliente_nombre: '',
    cliente_apellido: '',
    pais_prefijo: '+34',
    telefono_numero: '',
    cliente_email: '',
    notas_cliente: '',
    respuestas_clinicas: {},
  };
  slots.value = [];
  requiereHistorial.value = false;
};
</script>
