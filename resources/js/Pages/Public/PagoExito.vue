<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-50 p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
      <div v-if="loading" class="flex flex-col items-center justify-center py-8">
        <svg class="animate-spin h-12 w-12 text-indigo-600 mb-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <h2 class="text-xl font-bold text-slate-800">Verificando tu pago...</h2>
        <p class="text-slate-500 mt-2">Por favor, no cierres esta ventana.</p>
      </div>

      <div v-else-if="success" class="flex flex-col items-center py-8">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mb-6">
          <CheckCircleIcon class="w-12 h-12 text-green-600" />
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">¡Pago Completado!</h2>
        <p class="text-slate-600 mb-8">Tu cita ha sido confirmada y el pago se ha procesado con éxito. Hemos enviado un recibo a tu correo.</p>
        
        <router-link :to="`/${route.params.slug}/book`" class="w-full inline-flex justify-center items-center px-4 py-3 border border-transparent text-sm font-medium rounded-xl text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-md hover:shadow-lg">
          Volver al Inicio
        </router-link>
      </div>

      <div v-else class="flex flex-col items-center py-8">
        <div class="w-20 h-20 bg-red-100 rounded-full flex items-center justify-center mb-6">
          <XCircleIcon class="w-12 h-12 text-red-600" />
        </div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">Error en el Pago</h2>
        <p class="text-slate-600 mb-8">{{ errorMessage || 'No pudimos verificar tu pago. Si el cargo se realizó, por favor contacta con el negocio.' }}</p>
        
        <router-link :to="`/${route.params.slug}/book`" class="w-full inline-flex justify-center items-center px-4 py-3 border border-slate-300 text-sm font-medium rounded-xl text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors shadow-sm">
          Intentar de nuevo
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { CheckCircleIcon, XCircleIcon } from 'lucide-vue-next';
import axios from 'axios';

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const success = ref(false);
const errorMessage = ref('');

onMounted(async () => {
  const sessionId = route.query.session_id;
  const citaId = route.params.id;
  const slug = route.params.slug;

  if (!sessionId || !citaId) {
    loading.value = false;
    errorMessage.value = 'Faltan parámetros de verificación.';
    return;
  }

  try {
    const res = await axios.post(`/api/public/${slug}/confirmar-pago`, {
      cita_id: citaId,
      session_id: sessionId
    });

    if (res.data.success) {
      success.value = true;
    } else {
      success.value = false;
      errorMessage.value = res.data.message;
    }
  } catch (error) {
    success.value = false;
    errorMessage.value = error.response?.data?.message || 'Error de conexión con el servidor.';
  } finally {
    loading.value = false;
  }
});
</script>
