<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">Pacientes y Fichas Clínicas</h2>
    </div>

    <!-- Search / List Placeholder -->
    <div class="bg-bg-card border border-border rounded-2xl p-6">
      <div class="flex gap-4 mb-6">
        <input 
          type="text" 
          placeholder="Buscar paciente por nombre o DNI..." 
          class="flex-1 bg-black/20 border border-border rounded-xl px-4 py-3 text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all"
        />
        <button class="bg-primary hover:bg-primary-hover text-white px-6 py-3 rounded-xl font-medium transition-all flex items-center gap-2">
          <Search class="w-5 h-5" /> Buscar
        </button>
      </div>

      <div class="text-center text-text-muted py-12 border-t border-border/50">
        <Users class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p>Busca un paciente para ver su historial médico y antecedentes.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Search, Users } from 'lucide-vue-next';
</script>
