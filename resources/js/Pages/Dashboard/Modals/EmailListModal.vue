<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
    <div class="bg-bg-card border border-border rounded-2xl w-full max-w-md shadow-2xl overflow-hidden flex flex-col">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-border flex justify-between items-center bg-black/20">
        <h3 class="text-xl font-bold text-white flex items-center gap-2">
          <Mail class="w-5 h-5 text-blue-400" />
          {{ $t('agenda.modal_correo.titulo') || 'Enviar por Correo' }}
        </h3>
        <button @click="close" class="text-text-muted hover:text-white transition-colors">
          <X class="w-6 h-6" />
        </button>
      </div>

      <!-- Body -->
      <div class="p-6 space-y-4">
        <p class="text-sm text-text-muted">{{ $t('agenda.modal_correo.descripcion') || 'Se enviará el listado actual de citas a la dirección indicada.' }}</p>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal_correo.email') || 'Correo Electrónico' }}</label>
          <input 
            v-model="form.email" 
            type="email" 
            placeholder="ejemplo@correo.com"
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <div>
          <label class="block text-sm font-medium text-text-muted mb-2">{{ $t('agenda.modal_correo.mensaje') || 'Mensaje (Opcional)' }}</label>
          <textarea 
            v-model="form.mensaje" 
            rows="3"
            placeholder="Añade un mensaje..."
            class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          ></textarea>
        </div>

        <div class="flex items-center gap-2 mt-2">
          <input type="checkbox" id="adjuntarPdf" v-model="form.adjuntar_pdf" class="rounded border-border bg-black/20 text-blue-500 focus:ring-blue-500" />
          <label for="adjuntarPdf" class="text-sm text-text-muted">{{ $t('agenda.modal_correo.adjuntar_pdf') || 'Adjuntar también como PDF' }}</label>
        </div>

        <!-- Mensajes -->
        <div v-if="errorMsg" class="bg-red-500/10 border border-red-500/50 text-red-400 p-3 rounded-lg text-sm">
          {{ errorMsg }}
        </div>
        <div v-if="successMsg" class="bg-green-500/10 border border-green-500/50 text-green-400 p-3 rounded-lg text-sm">
          {{ successMsg }}
        </div>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-border bg-black/20 flex justify-end gap-3">
        <button 
          @click="close" 
          class="px-5 py-2.5 rounded-xl font-medium text-text-muted hover:text-white hover:bg-white/5 transition-colors">
          {{ $t('acciones.cancelar') }}
        </button>
        <button 
          @click="enviar" 
          :disabled="loading"
          class="text-white px-6 py-2.5 rounded-xl font-medium transition-all flex items-center gap-2 bg-blue-600 hover:bg-blue-700 shadow-[0_0_15px_rgba(37,99,235,0.25)]">
          <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
          <Send v-else class="w-4 h-4" />
          {{ $t('acciones.enviar') || 'Enviar' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import { X, Loader2, Mail, Send } from 'lucide-vue-next';
import axios from 'axios';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  show: Boolean,
  type: {
    type: String,
    default: 'general'
  }
});

const emit = defineEmits(['close']);

const loading = ref(false);
const errorMsg = ref('');
const successMsg = ref('');

const form = ref({
  email: '',
  mensaje: '',
  adjuntar_pdf: true
});

watch(() => props.show, (newVal) => {
  if (newVal) {
    errorMsg.value = '';
    successMsg.value = '';
    form.value.email = '';
    form.value.mensaje = '';
    form.value.adjuntar_pdf = true;
  }
});

const enviar = async () => {
  errorMsg.value = '';
  successMsg.value = '';
  
  if (!form.value.email) {
    errorMsg.value = 'El correo electrónico es obligatorio.';
    return;
  }

  loading.value = true;

  try {
    const payload = {
      email: form.value.email,
      mensaje: form.value.mensaje,
      adjuntar_pdf: form.value.adjuntar_pdf,
      type: props.type
    };

    await axios.post('/api/dashboard/citas/send-email', payload);
    successMsg.value = 'Correo enviado correctamente.';
    setTimeout(() => {
      close();
    }, 2000);
  } catch (error) {
    errorMsg.value = error.response?.data?.message || 'Error al enviar el correo.';
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
