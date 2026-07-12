<template>
  <div class="max-w-2xl mx-auto bg-bg-card border border-border rounded-2xl p-6 shadow-xl text-white">
    <h2 class="text-xl font-bold mb-6 flex items-center gap-2">
      <span class="p-2 bg-primary/10 rounded-lg text-primary text-xl">💸</span>
      Registrar Nuevo Gasto
    </h2>

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <!-- Concepto del Gasto -->
      <div>
        <label class="block text-sm font-semibold text-gray-300 mb-2">Concepto del Gasto *</label>
        <input
          v-model="form.concepto"
          type="text"
          required
          placeholder="Ej: Factura de electricidad, Compra de ordenador, etc."
          class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
        />
      </div>

      <!-- Proveedor -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-300 mb-2">Nombre Proveedor</label>
          <input
            v-model="form.proveedor_nombre"
            type="text"
            placeholder="Ej: Iberdrola S.A."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-300 mb-2">CIF/NIF Proveedor</label>
          <input
            v-model="form.proveedor_nif"
            type="text"
            placeholder="Ej: A82138982"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
          />
        </div>
      </div>

      <!-- Categoría de Gasto (USANDO CustomSelect) -->
      <div>
        <label class="block text-sm font-semibold text-gray-300 mb-2">Categoría del Gasto *</label>
        <CustomSelect
          v-model="form.categoria"
          :options="categorias"
          placeholder="Selecciona una categoría"
        />
      </div>

      <!-- Importe y Moneda -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="md:col-span-2">
          <label class="block text-sm font-semibold text-gray-300 mb-2">Subtotal (Base Imponible) *</label>
          <div class="relative">
            <input
              v-model.number="form.subtotal"
              type="number"
              step="0.01"
              min="0"
              required
              @input="calcularTotales"
              placeholder="0.00"
              class="w-full bg-black/20 border border-border rounded-xl pl-4 pr-12 py-3 text-white focus:outline-none focus:border-primary transition-all"
            />
            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 font-semibold">
              {{ form.moneda }}
            </span>
          </div>
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-300 mb-2">Moneda *</label>
          <CustomSelect
            v-model="form.moneda"
            :options="monedas"
            placeholder="Moneda"
          />
        </div>
      </div>

      <!-- Impuestos e IVA (USANDO CustomSelect) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-semibold text-gray-300 mb-2">Tipo de IVA *</label>
          <CustomSelect
            v-model="form.iva_porcentaje"
            :options="ivas"
            placeholder="Selecciona IVA"
            @update:modelValue="calcularTotales"
          />
        </div>
        <div>
          <label class="block text-sm font-semibold text-gray-300 mb-2">Fecha del Gasto *</label>
          <input
            v-model="form.fecha_gasto"
            type="datetime-local"
            required
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:border-primary transition-all"
          />
        </div>
      </div>

      <!-- SECCIÓN DINÁMICA: Home Office -->
      <div v-if="form.categoria === 'home_office'" class="p-4 bg-primary/5 border border-primary/20 rounded-xl space-y-4">
        <h4 class="text-sm font-bold text-primary">🏡 Configuración Oficina en Casa (Home Office)</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Metros Totales Vivienda</label>
            <input
              v-model.number="form.detalles_categoria.metros_totales"
              type="number"
              @input="calcularAfectacionHomeOffice"
              class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Metros Afectos Oficina</label>
            <input
              v-model.number="form.detalles_categoria.metros_afectos"
              type="number"
              @input="calcularAfectacionHomeOffice"
              class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">% Deducible Final</label>
            <input
              :value="form.afectacion_porcentaje.toFixed(2) + '%'"
              disabled
              class="w-full bg-white/5 border border-border rounded-lg px-3 py-2 text-gray-400 text-sm"
            />
          </div>
        </div>
        <p class="text-xs text-gray-400">
          Nota: Se calcula el porcentaje de metros afectos y se aplica un 30% adicional sobre los suministros (criterio estándar AEAT).
        </p>
      </div>

      <!-- SECCIÓN DINÁMICA: Bienes de Inversión -->
      <div v-if="form.categoria === 'inversion'" class="p-4 bg-yellow-500/5 border border-yellow-500/20 rounded-xl space-y-4">
        <h4 class="text-sm font-bold text-yellow-400">📈 Configuración Amortización (Bien de Inversión)</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Vida Útil (Años)</label>
            <input
              v-model.number="form.detalles_categoria.vida_util_anios"
              type="number"
              @input="calcularAmortizacion"
              class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">% Amortización Anual</label>
            <input
              v-model.number="form.detalles_categoria.porcentaje_amortizacion_anual"
              type="number"
              disabled
              class="w-full bg-white/5 border border-border rounded-lg px-3 py-2 text-gray-400 text-sm"
            />
          </div>
        </div>
        <p v-if="form.subtotal < 300" class="text-xs text-red-400 font-semibold">
          Aviso: Los bienes inferiores a 300€ pueden amortizarse de forma acelerada en un único ejercicio (no requieren amortización plurianual).
        </p>
      </div>

      <!-- SECCIÓN DINÁMICA: Dietas -->
      <div v-if="form.categoria === 'dietas'" class="p-4 bg-green-500/5 border border-green-500/20 rounded-xl space-y-4">
        <h4 class="text-sm font-bold text-green-400">🍽️ Detalle de Dietas y Representación</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block text-xs text-gray-400 mb-1">Establecimiento</label>
            <input
              v-model="form.detalles_categoria.establecimiento"
              type="text"
              placeholder="Ej: Restaurante Miramar"
              class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
            />
          </div>
          <div>
            <label class="block text-xs text-gray-400 mb-1">Motivo de la reunión</label>
            <input
              v-model="form.detalles_categoria.motivo"
              type="text"
              placeholder="Ej: Almuerzo de negocios con cliente"
              class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
            />
          </div>
        </div>
        <div>
          <label class="block text-xs text-gray-400 mb-1">Comensales (Nombres separados por comas)</label>
          <input
            v-model="form.detalles_categoria.comensales_raw"
            type="text"
            placeholder="Ej: José Font, Ana Gómez"
            class="w-full bg-black/20 border border-border rounded-lg px-3 py-2 text-white text-sm"
          />
        </div>
      </div>

      <!-- Resumen de Totales y Deducible -->
      <div class="bg-black/30 border border-border rounded-xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
          <p class="text-sm text-gray-400">Total Factura Gasto</p>
          <p class="text-2xl font-bold text-white">{{ form.total.toFixed(2) }} {{ form.moneda }}</p>
        </div>
        <div class="text-left md:text-right">
          <p class="text-sm text-primary font-bold">Importe Neto Deducible</p>
          <p class="text-2xl font-bold text-primary">{{ form.importe_deducible.toFixed(2) }} {{ form.moneda }}</p>
        </div>
      </div>

      <!-- Botones de Acción -->
      <div class="flex justify-end gap-3 mt-8">
        <button
          type="button"
          @click="resetForm"
          class="px-6 py-3 bg-white/5 hover:bg-white/10 border border-border text-white font-semibold rounded-xl transition-all cursor-pointer"
        >
          Limpiar
        </button>
        <button
          type="submit"
          :disabled="loading"
          class="px-6 py-3 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl transition-all shadow-[0_0_15px_rgba(99,102,241,0.2)] disabled:opacity-50 cursor-pointer flex items-center gap-2"
        >
          <span v-if="loading" class="animate-spin text-sm">⌛</span>
          Guardar Gasto
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from 'vue';
import CustomSelect from '../../Components/CustomSelect.vue';

const loading = ref(false);
const emit = defineEmits(['saved']);

const form = reactive({
  negocio_id: 1, // Simulado o inyectado por store de sesión
  concepto: '',
  proveedor_nombre: '',
  proveedor_nif: '',
  categoria: 'explotacion',
  subtotal: 0.00,
  moneda: 'EUR',
  iva_porcentaje: 21.00,
  fecha_gasto: new Date().toISOString().substring(0, 16),
  afectacion_porcentaje: 100.00,
  importe_deducible: 0.00,
  es_bien_inversion: false,
  total: 0.00,
  detalles_categoria: {
    metros_totales: 0,
    metros_afectos: 0,
    vida_util_anios: 3,
    porcentaje_amortizacion_anual: 33.33,
    establecimiento: '',
    motivo: '',
    comensales_raw: ''
  }
});

// Opciones para CustomSelect
const categorias = [
  { value: 'explotacion', label: 'Gastos de Explotación (Generales)', icon: '💼' },
  { value: 'inversion', label: 'Bien de Inversión (> 300€)', icon: '📈' },
  { value: 'home_office', label: 'Home Office / Oficina en Casa', icon: '🏡' },
  { value: 'dietas', label: 'Dietas y Representación', icon: '🍽' }
];

const monedas = [
  { value: 'EUR', label: 'Euros (€)', icon: '🇪🇺' },
  { value: 'USD', label: 'Dólar ($)', icon: '🇺🇸' },
  { value: 'GBP', label: 'Libra (£)', icon: '🇬🇧' }
];

const ivas = [
  { value: 21.00, label: 'IVA General (21%)' },
  { value: 10.00, label: 'IVA Reducido (10%)' },
  { value: 4.00, label: 'IVA Superreducido (4%)' },
  { value: 0.00, label: 'Exento / Sin IVA (0%)' }
];

// Cambios de categoría resetean la afectación base
watch(() => form.categoria, (newCat) => {
  form.es_bien_inversion = (newCat === 'inversion');
  if (newCat === 'explotacion' || newCat === 'dietas') {
    form.afectacion_porcentaje = 100.00;
  } else if (newCat === 'home_office') {
    calcularAfectacionHomeOffice();
  } else if (newCat === 'inversion') {
    form.afectacion_porcentaje = 100.00;
    calcularAmortizacion();
  }
  calcularTotales();
});

// Cálculos automáticos
const calcularTotales = () => {
  const sub = Number(form.subtotal) || 0;
  const ivaPct = Number(form.iva_porcentaje) || 0;
  const ivaImporte = sub * (ivaPct / 100);
  
  form.total = sub + ivaImporte;
  form.importe_deducible = form.total * (form.afectacion_porcentaje / 100);
};

const calcularAfectacionHomeOffice = () => {
  const mTotales = Number(form.detalles_categoria.metros_totales) || 0;
  const mAfectos = Number(form.detalles_categoria.metros_afectos) || 0;
  
  if (mTotales > 0 && mAfectos > 0) {
    const ratioMetros = mAfectos / mTotales;
    // Criterio AEAT España: Afectación de suministros = ratio_metros * 30%
    form.afectacion_porcentaje = (ratioMetros * 0.3) * 100;
  } else {
    form.afectacion_porcentaje = 0.00;
  }
  calcularTotales();
};

const calcularAmortizacion = () => {
  const anios = Number(form.detalles_categoria.vida_util_anios) || 1;
  form.detalles_categoria.porcentaje_amortizacion_anual = parseFloat((100 / anios).toFixed(2));
};

const resetForm = () => {
  form.concepto = '';
  form.proveedor_nombre = '';
  form.proveedor_nif = '';
  form.categoria = 'explotacion';
  form.subtotal = 0.00;
  form.total = 0.00;
  form.importe_deducible = 0.00;
  form.detalles_categoria = {
    metros_totales: 0,
    metros_afectos: 0,
    vida_util_anios: 3,
    porcentaje_amortizacion_anual: 33.33,
    establecimiento: '',
    motivo: '',
    comensales_raw: ''
  };
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    // Si la categoría es de dietas, estructuramos comensales en array
    const comensales = form.detalles_categoria.comensales_raw
      ? form.detalles_categoria.comensales_raw.split(',').map(s => s.trim())
      : [];
      
    const payload = {
      ...form,
      detalles_categoria: {
        ...form.detalles_categoria,
        comensales
      }
    };

    // Simulación de envío a API (ej. /api/expenses)
    console.log('Enviando Gasto:', payload);
    
    // Simular retraso de red
    await new Promise(resolve => setTimeout(resolve, 1000));
    
    emit('saved', payload);
    resetForm();
    alert('Gasto guardado correctamente.'); // En un entorno real se usaría Toast o modal, pero para feedback visual rápido sirve.
  } catch (error) {
    console.error('Error al guardar gasto:', error);
  } finally {
    loading.value = false;
  }
};
</script>
