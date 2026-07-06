<template>
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <h2 class="text-2xl font-bold">{{ $t('whatsapp.titulo') }}</h2>
      <p class="text-text-muted mt-1">{{ $t('whatsapp.subtitulo') }}</p>
    </div>

    <div class="bg-bg-card border border-border rounded-2xl p-8 text-center">
      <div v-if="loading" class="flex flex-col items-center justify-center py-12">
        <Loader2 class="w-12 h-12 animate-spin text-primary mb-4" />
        <p class="text-text-muted">{{ $t('whatsapp.conectando') }}</p>
      </div>

      <div v-else-if="qrCode" class="flex flex-col items-center">
        <div class="bg-white p-4 rounded-2xl mb-6 shadow-[0_0_30px_rgba(99,102,241,0.2)]">
          <!-- Mostrar Base64 del QR -->
          <img :src="qrCode" alt="WhatsApp QR Code" class="w-64 h-64 object-contain" />
        </div>
        
        <h3 class="text-xl font-bold text-white mb-2">{{ $t('whatsapp.escanea') }}</h3>
        <ol class="text-left text-text-muted space-y-2 mb-8 max-w-sm">
          <li>{{ $t('whatsapp.paso1') }}</li>
          <li>{{ $t('whatsapp.paso2') }}</li>
          <li>{{ $t('whatsapp.paso3') }}</li>
          <li>{{ $t('whatsapp.paso4') }}</li>
        </ol>

        <button @click="generarQR" class="text-primary hover:text-primary-hover font-medium underline">
          {{ $t('whatsapp.refrescar') }}
        </button>
      </div>

      <div v-else class="py-8">
        <div class="w-16 h-16 rounded-full bg-primary/10 flex items-center justify-center mx-auto mb-4 text-primary">
          <MessageCircle class="w-8 h-8" />
        </div>
        <h3 class="text-xl font-bold text-white mb-2">{{ $t('whatsapp.desconectado') }}</h3>
        <p class="text-text-muted mb-6">{{ $t('whatsapp.activa') }}</p>
        
        <button @click="generarQR" class="bg-primary hover:bg-primary-hover text-white font-medium py-3 px-8 rounded-xl transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)]">
          {{ $t('whatsapp.generar') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { Loader2, MessageCircle } from 'lucide-vue-next';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

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
    alert(t('whatsapp.error'));
  } finally {
    loading.value = false;
  }
};
</script>
