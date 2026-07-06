<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">{{ $t('clientes.titulo') }}</h2>
    </div>

    <!-- Search / List -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[500px]">
      <div class="flex gap-4 mb-6">
        <input 
          v-model="searchQuery"
          @keyup.enter="buscarClientes"
          type="text" 
          :placeholder="$t('clientes.buscar_placeholder')" 
          class="flex-1 bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
        />
        <button 
          @click="buscarClientes"
          :disabled="loading"
          class="bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2">
          <Loader2 v-if="loading" class="w-5 h-5 animate-spin" />
          <Search v-else class="w-5 h-5" /> 
          {{ $t('clientes.buscar') }}
        </button>
      </div>

      <div v-if="loading && clientes.length === 0" class="flex justify-center py-12 text-primary">
        <Loader2 class="w-10 h-10 animate-spin" />
      </div>

      <div v-else-if="clientes.length === 0" class="text-center text-text-muted py-12 border-t border-border/50">
        <Users class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p v-if="searchQuery">{{ $t('clientes.sin_resultados') }} "{{ searchQuery }}".</p>
        <p v-else>{{ $t('clientes.busqueda_vacia') }}</p>
      </div>

      <div v-else class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
          <thead>
            <tr class="border-b border-border text-text-muted text-sm">
              <th class="pb-3 font-medium">{{ $t('clientes.tabla_cliente') }}</th>
              <th class="pb-3 font-medium">{{ $t('clientes.tabla_telefono') }}</th>
              <th class="pb-3 font-medium text-center">{{ $t('clientes.tabla_total_citas') }}</th>
              <th class="pb-3 font-medium text-right">{{ $t('clientes.tabla_acciones') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <tr v-for="cliente in clientes" :key="cliente.id" class="hover:bg-white/5 transition-colors">
              <td class="py-4">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold">
                    {{ cliente.nombre.charAt(0) }}{{ cliente.apellido?.charAt(0) }}
                  </div>
                  <div>
                    <p class="font-bold text-white">{{ cliente.nombre }} {{ cliente.apellido }}</p>
                    <p class="text-xs text-text-muted">{{ cliente.email || $t('ficha.sin_correo') }}</p>
                  </div>
                </div>
              </td>
              <td class="py-4 text-white">{{ cliente.telefono || 'N/A' }}</td>
              <td class="py-4 text-center">
                <span class="bg-primary/20 text-primary px-2 py-1 rounded-full text-xs font-bold">
                  {{ cliente.citas_count || 0 }} {{ $t('clientes.citas_contador') }}
                </span>
              </td>
              <td class="py-4 text-right">
                <button @click="verFicha(cliente.id)" class="text-primary hover:text-primary-hover font-medium underline text-sm">
                  {{ $t('clientes.ver_ficha') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Search, Loader2, Users } from 'lucide-vue-next';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const searchQuery = ref('');
const clientes = ref([]);
const loading = ref(true);

const verFicha = (id) => {
  router.push(`/panel/clientes/${id}`);
};

const buscarClientes = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/clientes', {
      params: { buscar: searchQuery.value }
    });
    clientes.value = response.data.data || response.data; // Paginado o plano
  } catch (error) {
    console.error("Error buscando clientes", error);
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  buscarClientes();
});
</script>
