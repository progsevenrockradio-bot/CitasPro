<template>
  <div class="space-y-6">
    <div class="flex justify-between items-center">
      <h2 class="text-2xl font-bold">Mi Agenda</h2>
      <button class="bg-primary hover:bg-primary-hover px-4 py-2 rounded-xl font-medium transition-all shadow-[0_0_15px_rgba(99,102,241,0.25)]">
        + Nueva Cita
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="bg-bg-card border border-border p-6 rounded-2xl">
        <p class="text-text-muted text-sm font-medium mb-1">Citas Hoy</p>
        <p class="text-3xl font-bold">8</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl">
        <p class="text-text-muted text-sm font-medium mb-1">Citas Semana</p>
        <p class="text-3xl font-bold">32</p>
      </div>
      <div class="bg-bg-card border border-border p-6 rounded-2xl">
        <p class="text-text-muted text-sm font-medium mb-1">Canceladas</p>
        <p class="text-3xl font-bold text-red-400">1</p>
      </div>
    </div>

    <!-- Calendar Placeholders -->
    <div class="bg-bg-card border border-border rounded-2xl p-6 min-h-[500px] flex items-center justify-center">
      <div class="text-center text-text-muted">
        <Calendar class="w-12 h-12 mx-auto mb-3 opacity-50" />
        <p>El calendario se cargará aquí</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar } from 'lucide-vue-next';
</script>
