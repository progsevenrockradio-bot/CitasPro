<template>
  <div class="min-h-screen bg-bg text-text flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-bg-card border border-border rounded-2xl p-6 space-y-5 shadow-2xl">
      <div class="flex items-center justify-between">
        <div>
          <p class="text-xs uppercase tracking-wider text-primary font-semibold">Área de Cliente</p>
          <h1 class="text-xl font-bold text-white">{{ user?.nombre }} {{ user?.apellido }}</h1>
        </div>
        <button
          @click="logout"
          class="px-3 py-2 rounded-xl text-xs font-medium text-red-400 bg-red-500/10 hover:bg-red-500/20 transition-all"
        >
          Cerrar sesión
        </button>
      </div>

      <div class="rounded-xl bg-black/20 border border-border p-4 space-y-2 text-sm">
        <p><span class="text-text-muted">Correo:</span> <span class="text-white">{{ user?.email || '—' }}</span></p>
        <p><span class="text-text-muted">Teléfono:</span> <span class="text-white">{{ user?.telefono || '—' }}</span></p>
        <p><span class="text-text-muted">Citas registradas:</span> <span class="text-white">{{ user?.total_citas ?? 0 }}</span></p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();
const user = ref(null);

onMounted(async () => {
  try {
    const res = await axios.get('/api/auth/me');
    if (res.data?.role !== 'cliente') {
      router.replace('/panel');
      return;
    }
    user.value = res.data.user;
  } catch (e) {
    localStorage.removeItem('token');
    localStorage.removeItem('auth_role');
    router.replace('/login');
  }
});

const logout = async () => {
  try {
    await axios.post('/api/auth/logout');
  } finally {
    localStorage.removeItem('token');
    localStorage.removeItem('auth_role');
    delete axios.defaults.headers.common['Authorization'];
    router.push('/login');
  }
};
</script>
