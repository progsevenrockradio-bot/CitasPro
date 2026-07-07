<template>
  <div class="relative inline-block tooth-wrapper" style="width: 34px; height: 34px;">
    <!-- Marca de Extracción / Corona (Whole tooth) -->
    <div v-if="data.whole" class="absolute inset-0 z-10 pointer-events-none flex items-center justify-center">
      <span v-if="data.whole === 'Extracción'" class="text-gray-400 opacity-80 text-3xl font-bold">X</span>
      <span v-if="data.whole === 'Corona'" class="text-yellow-400 opacity-80 text-xl">👑</span>
    </div>

    <!-- SVG Base (5 superficies) -->
    <svg viewBox="0 0 100 100" width="34" height="34" class="cursor-pointer" :class="{ 'opacity-50': data.whole }">
      
      <!-- Top -->
      <polygon points="0,0 100,0 75,25 25,25" 
        :fill="getColor(data.top)" stroke="#374151" stroke-width="2" 
        class="hover:opacity-80 transition-opacity"
        @click.stop="$emit('surface-click', { id, surface: 'top', event: $event })" />
        
      <!-- Right -->
      <polygon points="100,0 100,100 75,75 75,25" 
        :fill="getColor(data.right)" stroke="#374151" stroke-width="2" 
        class="hover:opacity-80 transition-opacity"
        @click.stop="$emit('surface-click', { id, surface: 'right', event: $event })" />
        
      <!-- Bottom -->
      <polygon points="0,100 100,100 75,75 25,75" 
        :fill="getColor(data.bottom)" stroke="#374151" stroke-width="2" 
        class="hover:opacity-80 transition-opacity"
        @click.stop="$emit('surface-click', { id, surface: 'bottom', event: $event })" />
        
      <!-- Left -->
      <polygon points="0,0 0,100 25,75 25,25" 
        :fill="getColor(data.left)" stroke="#374151" stroke-width="2" 
        class="hover:opacity-80 transition-opacity"
        @click.stop="$emit('surface-click', { id, surface: 'left', event: $event })" />
        
      <!-- Center -->
      <rect x="25" y="25" width="50" height="50" 
        :fill="getColor(data.center)" stroke="#374151" stroke-width="2" 
        class="hover:opacity-80 transition-opacity"
        @click.stop="$emit('surface-click', { id, surface: 'center', event: $event })" />

    </svg>
  </div>
</template>

<script setup>
defineProps({
  id: {
    type: Number,
    required: true
  },
  data: {
    type: Object,
    default: () => ({ top: null, right: null, bottom: null, left: null, center: null, whole: null })
  },
  isTemporal: {
    type: Boolean,
    default: false
  }
});

defineEmits(['surface-click']);

const getColor = (diag) => {
  if (diag === 'Caries') return '#ef4444'; // red-500
  if (diag === 'Obturación') return '#3b82f6'; // blue-500
  return '#1f2937'; // gray-800 default (transparent look on dark mode)
};
</script>
