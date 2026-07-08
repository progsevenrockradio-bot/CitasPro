<template>
  <Transition name="fade">
    <div 
      v-if="show" 
      class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4"
      @click.self="cancel"
    >
      <Transition name="zoom">
        <div 
          v-if="show"
          class="bg-bg-card border border-border-sutil rounded-2xl max-w-md w-full p-6 shadow-2xl relative overflow-hidden"
        >
          <!-- Decoración superior según el tipo -->
          <div class="absolute top-0 left-0 w-full h-1" :class="typeClass"></div>

          <!-- Cabecera con Icono -->
          <div class="flex items-start gap-4 mb-4">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0" :class="iconBgClass">
              <CheckCircle2 v-if="type === 'success'" class="w-6 h-6 text-green-400" />
              <AlertTriangle v-else-if="type === 'warning'" class="w-6 h-6 text-yellow-400" />
              <XCircle v-else-if="type === 'danger'" class="w-6 h-6 text-red-400" />
              <Info v-else-if="type === 'info'" class="w-6 h-6 text-primary" />
              <HelpCircle v-else class="w-6 h-6 text-primary" />
            </div>
            <div class="flex-1 min-w-0">
              <h3 class="text-lg font-bold text-white leading-snug">{{ title }}</h3>
              <p class="text-sm text-gray-400 mt-1 leading-relaxed">{{ message }}</p>
            </div>
          </div>

          <!-- Ranura para contenido extra opcional -->
          <div v-if="$slots.default" class="mb-4">
            <slot />
          </div>

          <!-- Botones de Acción -->
          <div class="flex gap-3 mt-6">
            <button 
              v-if="cancelText"
              type="button"
              @click="cancel" 
              class="flex-1 bg-white/5 hover:bg-white/10 border border-border text-white py-3 rounded-xl transition-all text-sm font-semibold cursor-pointer active:scale-95"
            >
              {{ cancelText }}
            </button>
            <button 
              type="button"
              @click="confirm" 
              class="flex-1 text-white py-3 rounded-xl transition-all text-sm font-bold shadow-[0_0_15px_rgba(99,102,241,0.2)] active:scale-95 cursor-pointer"
              :class="confirmBtnClass"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </Transition>
    </div>
  </Transition>
</template>

<script setup>
import { computed } from 'vue';
import { CheckCircle2, AlertTriangle, XCircle, Info, HelpCircle } from 'lucide-vue-next';

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  title: {
    type: String,
    default: '¿Confirmar acción?'
  },
  message: {
    type: String,
    default: '¿Está seguro de que desea realizar esta acción?'
  },
  type: {
    type: String,
    default: 'question' // 'question', 'warning', 'danger', 'success', 'info'
  },
  confirmText: {
    type: String,
    default: 'Aceptar'
  },
  cancelText: {
    type: String,
    default: 'Cancelar'
  }
});

const emit = defineEmits(['update:show', 'confirm', 'cancel']);

const typeClass = computed(() => {
  switch (props.type) {
    case 'warning': return 'bg-yellow-500';
    case 'danger': return 'bg-red-500';
    case 'success': return 'bg-green-500';
    case 'info': return 'bg-primary';
    default: return 'bg-primary';
  }
});

const iconBgClass = computed(() => {
  switch (props.type) {
    case 'warning': return 'bg-yellow-500/10 border border-yellow-500/20';
    case 'danger': return 'bg-red-500/10 border border-red-500/20';
    case 'success': return 'bg-green-500/10 border border-green-500/20';
    case 'info': return 'bg-primary/10 border border-primary/20';
    default: return 'bg-primary/10 border border-primary/20';
  }
});

const confirmBtnClass = computed(() => {
  switch (props.type) {
    case 'danger': return 'bg-red-600 hover:bg-red-700 shadow-[0_0_15px_rgba(239,68,68,0.2)]';
    case 'success': return 'bg-primary hover:bg-primary-hover shadow-[0_0_15px_rgba(59,130,246,0.2)]';
    default: return 'bg-primary hover:bg-primary-hover';
  }
});

const confirm = () => {
  emit('update:show', false);
  emit('confirm');
};

const cancel = () => {
  emit('update:show', false);
  emit('cancel');
};
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.zoom-enter-active,
.zoom-leave-active {
  transition: transform 0.25s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.25s ease;
}

.zoom-enter-from,
.zoom-leave-to {
  transform: scale(0.95);
  opacity: 0;
}
</style>
