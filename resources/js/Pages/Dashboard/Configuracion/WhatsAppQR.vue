<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <h2 class="text-2xl font-bold">Conexión de WhatsApp</h2>
      <p class="text-text-muted mt-1">Conecta tu número personal escaneando este código QR para enviar recordatorios de citas sin coste.</p>
    </div>

    <div class="bg-bg-card border border-border rounded-2xl p-8 text-center">
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <Loader2 class="w-12 h-12 animate-spin text-primary mb-4" />
        <p class="text-text-muted">Conectando con el servidor...</p>
      </div>

      <div v-else-if="qrCode" class="flex flex-col items-center">
        <div class="bg-white p-4 rounded-2xl mb-6 shadow-[0_0_30px_rgba(99,102,241,0.2)]">
          <!-- Mostrar Base64 del QR -->
          <img :src="qrCode" alt="WhatsApp QR Code" class="w-64 h-64 object-contain" />
        </div>
        
        <h3 class="text-xl font-bold text-white mb-2">Escanea el código QR</h3>
        <ol class="text-left text-text-muted space-y-2 mb-8 max-w-sm">
          <li>1. Abre WhatsApp en tu teléfono.</li>
          <li>2. Toca Menú o Configuración y selecciona Dispositivos vinculados.</li>
          <li>3. Toca Vincular un dispositivo.</li>
          <li>4. Apunta tu teléfono a esta pantalla para escanear el código.</li>
        </ol>

        <button @click="generarQR" class="text-primary hover:text-primary-hover font-medium underline">
          Refrescar código QR
        </button>
      </div>

      <div v-else class="py-8">
        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 text-primary">
          <MessageCircle class="w-8 h-8" />
        </div>
        <h3 class="text-xl font-bold text-white mb-2">WhatsApp Desconectado</h3>
        <p class="text-text-muted mb-6">Activa el módulo autónomo de mensajería para tu clínica.</p>
        
        <button @click="generarQR" class="bg-primary hover:bg-primary-hover text-white font-medium py-3 px-8 rounded-xl transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)]">
          Generar Código QR
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Loader2, MessageCircle } from 'lucide-vue-next';
import axios from 'axios';

const loading = ref(false);
const qrCode = ref(null);

const generarQR = async () => {
  loading.value = true;
  try {
    const response = await axios.post('/api/negocio/whatsapp/conectar');
    if (response.data.success) {
      qrCode.value = response.data.qrcode;
    }
  } catch (error) {
    console.error('Error generando QR', error);
    alert('Hubo un error al contactar con el servidor Evolution API.');
  } finally {
    loading.value = false;
  }
};
</script>
