<template>
  <div class="bg-bg-card border border-border-sutil rounded-2xl p-6 shadow-xl text-white">
    <div class="flex justify-between items-center mb-4">
      <div>
        <h4 class="text-sm font-bold text-gray-400 uppercase tracking-wider">Semáforo Fiscal</h4>
        <p class="text-xs text-gray-500">Límite de exclusión de módulos o IVA</p>
      </div>
      <!-- Indicador luminoso / Semáforo -->
      <div class="flex items-center gap-2">
        <span class="relative flex h-4 w-4">
          <span :class="[
            'animate-ping absolute inline-flex h-full w-full rounded-full opacity-75',
            trafficLightColorClass
          ]"></span>
          <span :class="[
            'relative inline-flex rounded-full h-4 w-4 border border-black/10',
            trafficLightColorClass
          ]"></span>
        </span>
        <span class="text-xs font-semibold uppercase" :class="trafficLightTextClass">
          {{ statusText }}
        </span>
      </div>
    </div>

    <!-- Progreso Visual -->
    <div class="space-y-2">
      <div class="flex justify-between text-sm">
        <span class="font-medium">Acumulado: <span class="text-white font-bold">{{ formatCurrency(currentIncome) }}</span></span>
        <span class="text-gray-400">Límite: {{ formatCurrency(limitIncome) }}</span>
      </div>

      <!-- Barra de progreso con gradiente y efecto brillo -->
      <div class="w-full bg-white/5 border border-white/10 rounded-full h-3 overflow-hidden relative shadow-inner">
        <div 
          class="h-full rounded-full transition-all duration-500 ease-out"
          :class="progressBarClass"
          :style="{ width: `${Math.min(percentage, 100)}%` }"
        ></div>
      </div>

      <div class="flex justify-between items-center text-xs mt-3 pt-2 border-t border-white/5">
        <span class="text-gray-400">Porcentaje de consumo</span>
        <span class="font-bold" :class="trafficLightTextClass">{{ percentage.toFixed(1) }}%</span>
      </div>
      
      <p v-if="remainingIncome > 0" class="text-xs text-gray-400 mt-2">
        Te quedan <strong class="text-white">{{ formatCurrency(remainingIncome) }}</strong> antes de alcanzar el límite de tu régimen.
      </p>
      <p v-else class="text-xs text-red-400 font-semibold mt-2 animate-pulse">
        ¡Has superado el límite del régimen fiscal en {{ formatCurrency(Math.abs(remainingIncome)) }}!
      </p>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentIncome: {
    type: Number,
    required: true,
    default: 0
  },
  limitIncome: {
    type: Number,
    required: true,
    default: 80000
  }
});

const percentage = computed(() => {
  if (props.limitIncome <= 0) return 0;
  return (props.currentIncome / props.limitIncome) * 100;
});

const remainingIncome = computed(() => {
  return props.limitIncome - props.currentIncome;
});

const statusText = computed(() => {
  const p = percentage.value;
  if (p < 70) return 'Óptimo (Verde)';
  if (p >= 70 && p <= 90) return 'Precaución (Amarillo)';
  return 'Límite Crítico (Rojo)';
});

const trafficLightColorClass = computed(() => {
  const p = percentage.value;
  if (p < 70) return 'bg-green-500';
  if (p >= 70 && p <= 90) return 'bg-yellow-500';
  return 'bg-red-500';
});

const trafficLightTextClass = computed(() => {
  const p = percentage.value;
  if (p < 70) return 'text-green-400';
  if (p >= 70 && p <= 90) return 'text-yellow-400';
  return 'text-red-400';
});

const progressBarClass = computed(() => {
  const p = percentage.value;
  if (p < 70) return 'bg-gradient-to-r from-green-600 to-green-400 shadow-[0_0_10px_rgba(34,197,94,0.3)]';
  if (p >= 70 && p <= 90) return 'bg-gradient-to-r from-yellow-600 to-yellow-400 shadow-[0_0_10px_rgba(234,179,8,0.3)]';
  return 'bg-gradient-to-r from-red-600 to-red-400 shadow-[0_0_10px_rgba(239,68,68,0.3)]';
});

const formatCurrency = (value) => {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
};
</script>
