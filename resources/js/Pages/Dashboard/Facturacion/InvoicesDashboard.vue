<template>
  <div class="max-w-6xl mx-auto p-6 space-y-8 text-white">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-bg-card border border-border p-6 rounded-2xl">
      <div>
        <h1 class="text-2xl font-bold flex items-center gap-2">
          <span class="p-2 bg-primary/10 rounded-lg text-primary text-xl">📄</span>
          Facturas (VeriFactu)
        </h1>
        <p class="text-sm text-gray-400 mt-1">
          Historial y facturación electrónica inalterable y reglamentaria de tu negocio.
        </p>
      </div>
      <button
        @click="openEmissionConfirmation"
        class="px-6 py-3 bg-primary hover:bg-primary-hover text-white font-bold rounded-xl transition-all shadow-[0_0_15px_rgba(99,102,241,0.2)] cursor-pointer active:scale-95 flex items-center gap-2"
      >
        <span>➕</span> Emitir Nueva Factura
      </button>
    </div>

    <!-- Stats summary -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-bg-card border border-border p-5 rounded-2xl">
        <p class="text-sm text-gray-400">Total Facturado (Mes)</p>
        <p class="text-3xl font-extrabold text-white mt-1">{{ totalFacturado }} EUR</p>
      </div>
      <div class="bg-bg-card border border-border p-5 rounded-2xl">
        <p class="text-sm text-gray-400">Facturas Emitidas</p>
        <p class="text-3xl font-extrabold text-green-400 mt-1">{{ invoices.length }}</p>
      </div>
      <div class="bg-bg-card border border-border p-5 rounded-2xl">
        <p class="text-sm text-gray-400">Pendiente AEAT</p>
        <p class="text-3xl font-extrabold text-yellow-400 mt-1">{{ pendientesAeat }}</p>
      </div>
    </div>

    <!-- Invoices List -->
    <div class="bg-bg-card border border-border rounded-2xl overflow-hidden shadow-xl">
      <div class="p-6 border-b border-border flex justify-between items-center">
        <h3 class="font-bold text-lg">Listado de Facturas</h3>
        <span class="px-3 py-1 bg-green-500/10 text-green-400 text-xs font-semibold rounded-full border border-green-500/20">
          Sincronizado VeriFactu
        </span>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-border bg-black/10 text-gray-400 text-sm font-semibold">
              <th class="p-4">Número</th>
              <th class="p-4">Cliente</th>
              <th class="p-4">Fecha</th>
              <th class="p-4">Monto</th>
              <th class="p-4">Estado VeriFactu</th>
              <th class="p-4 text-right">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border/50">
            <tr v-for="item in invoices" :key="item.id" class="hover:bg-white/5 transition-all">
              <td class="p-4 font-mono font-bold">{{ item.serie }}-{{ item.numero }}</td>
              <td class="p-4">
                <p class="font-semibold">{{ item.datos_cliente_snapshot.nombre }}</p>
                <p class="text-xs text-gray-400 font-mono">{{ item.datos_cliente_snapshot.nif }}</p>
              </td>
              <td class="p-4 text-sm">{{ formatFecha(item.fecha_emision) }}</td>
              <td class="p-4 font-bold">{{ parseFloat(item.total).toFixed(2) }} {{ item.moneda }}</td>
              <td class="p-4">
                <span 
                  class="px-2 py-1 text-xs font-semibold rounded-full border"
                  :class="item.enviado_aeat 
                    ? 'bg-green-500/10 text-green-400 border-green-500/20' 
                    : 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20'"
                >
                  {{ item.enviado_aeat ? 'Enviado' : 'Pendiente' }}
                </span>
              </td>
              <td class="p-4 text-right space-x-2">
                <a 
                  v-if="item.datos_qr" 
                  :href="item.datos_qr" 
                  target="_blank" 
                  class="px-3 py-1 bg-white/5 hover:bg-white/10 border border-border text-xs rounded-lg transition-all inline-block"
                >
                  🔍 Ver QR
                </a>
                <button class="px-3 py-1 bg-white/5 hover:bg-white/10 border border-border text-xs rounded-lg transition-all">
                  🖨️ Imprimir
                </button>
              </td>
            </tr>
            <tr v-if="invoices.length === 0">
              <td colspan="6" class="p-8 text-center text-gray-500">
                No hay facturas emitidas en esta serie.
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ConfirmModal (USANDO ConfirmModal) -->
    <ConfirmModal
      :show="showConfirmationModal"
      title="¿Confirmar Emisión de Factura?"
      message="Esta acción generará un registro fiscal inalterable y encadenado criptográficamente en cumplimiento con la normativa VeriFactu. La factura no podrá ser modificada ni eliminada una vez emitida."
      type="warning"
      confirmText="Sí, Emitir Factura"
      cancelText="Cancelar"
      @confirm="emitirFactura"
      @cancel="showConfirmationModal = false"
      @update:show="showConfirmationModal = $event"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import ConfirmModal from '../../Components/ConfirmModal.vue';

const showConfirmationModal = ref(false);
const loading = ref(false);
const invoices = ref([]);

// Datos simulados iniciales
onMounted(() => {
  invoices.value = [
    {
      id: 1,
      serie: 'A',
      numero: '000001',
      fecha_emision: '2026-07-12 10:00:00',
      total: 45.00,
      moneda: 'EUR',
      enviado_aeat: true,
      datos_cliente_snapshot: { nombre: 'Juan Pérez', nif: '12345678Z' },
      datos_qr: 'https://citaspro.app/verifactu/qr?nif=12345678Z&serie=A&num=000001'
    }
  ];
});

const totalFacturado = computed(() => {
  return invoices.value.reduce((acc, inv) => acc + parseFloat(inv.total), 0).toFixed(2);
});

const pendientesAeat = computed(() => {
  return invoices.value.filter(inv => !inv.enviado_aeat).length;
});

const formatFecha = (fechaStr) => {
  return new Date(fechaStr).toLocaleString();
};

const openEmissionConfirmation = () => {
  showConfirmationModal.value = true;
};

const emitirFactura = async () => {
  loading.value = true;
  try {
    // Ejemplo de payload estructurado para la API
    const newInvoicePayload = {
      negocio_id: 1,
      serie: 'A',
      fecha_emision: new Date().toISOString().replace('T', ' ').substring(0, 19),
      tipo_factura: 'B2C',
      moneda: 'EUR',
      tipo_cambio: 1.00,
      datos_cliente_snapshot: {
        nombre: 'Cliente General',
        nif: '77777777T',
        direccion: 'Calle Gran Vía 12',
        ciudad: 'Madrid',
        codigo_postal: '28013',
        pais_codigo: 'ES'
      },
      lines: [
        {
          descripcion: 'Corte de Cabello VIP',
          cantidad: 1,
          precio_unitario: 45.00,
          iva_porcentaje: 21.00
        }
      ]
    };

    // Petición real simulada al endpoint /api/invoices que creamos
    console.log('Emitiendo factura VeriFactu a API:', newInvoicePayload);
    
    // Simular llamada axios/fetch
    await new Promise(resolve => setTimeout(resolve, 1500));

    // Agregar al listado simulando respuesta de API exitosa
    const mockApiResponse = {
      id: invoices.value.length + 1,
      serie: newInvoicePayload.serie,
      numero: strPad(invoices.value.length + 2, 6, '0'),
      fecha_emision: newInvoicePayload.fecha_emision,
      total: 54.45, // 45 + 21% IVA
      moneda: newInvoicePayload.moneda,
      enviado_aeat: true,
      datos_cliente_snapshot: newInvoicePayload.datos_cliente_snapshot,
      datos_qr: 'https://citaspro.app/verifactu/qr?nif=77777777T&serie=A&num=' + strPad(invoices.value.length + 2, 6, '0')
    };

    invoices.value.push(mockApiResponse);
    showConfirmationModal.value = false;
    alert('Factura emitida con éxito y registrada en VeriFactu.');
  } catch (error) {
    console.error('Error al emitir factura:', error);
  } finally {
    loading.value = false;
  }
};

// Helper
const strPad = (str, max, pad) => {
  str = str.toString();
  return str.length < max ? strPad(pad + str, max, pad) : str;
};
</script>
