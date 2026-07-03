<template>
  <div class="relative w-full" ref="selectRef">
    <button
      type="button"
      @click="toggleDropdown"
      class="w-full bg-black/20 border border-border rounded-xl px-4 py-3 text-white flex justify-between items-center focus:outline-none focus:border-primary transition-all text-left"
    >
      <span v-if="selectedOption" class="flex items-center gap-2">
        <span v-if="selectedOption.icon" class="text-lg">{{ selectedOption.icon }}</span>
        <span>{{ selectedOption.label }}</span>
      </span>
      <span v-else class="text-text-muted/50">{{ placeholder }}</span>
      <ChevronDown class="w-5 h-5 text-text-muted transition-transform duration-200" :class="{ 'rotate-180': isOpen }" />
    </button>

    <transition
      enter-active-class="transition duration-100 ease-out"
      enter-from-class="transform scale-95 opacity-0"
      enter-to-class="transform scale-100 opacity-100"
      leave-active-class="transition duration-75 ease-in"
      leave-from-class="transform scale-100 opacity-100"
      leave-to-class="transform scale-95 opacity-0"
    >
      <div
        v-if="isOpen"
        class="absolute z-50 w-full mt-2 bg-bg-card/95 backdrop-blur-xl border border-border rounded-xl shadow-2xl max-h-60 overflow-y-auto"
      >
        <div
          v-for="option in options"
          :key="option.value"
          @click="selectOption(option)"
          class="px-4 py-3 hover:bg-primary/10 hover:text-white text-text-muted cursor-pointer transition-all flex items-center gap-2"
          :class="{ 'bg-primary/5 text-primary font-medium': modelValue === option.value }"
        >
          <span v-if="option.icon" class="text-lg">{{ option.icon }}</span>
          <span>{{ option.label }}</span>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  modelValue: {
    type: [String, Number],
    default: ''
  },
  options: {
    type: Array,
    required: true // [{ value: 1, label: 'Option 1', icon: '💅' }]
  },
  placeholder: {
    type: String,
    default: 'Selecciona una opción'
  }
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const selectRef = ref(null);

const toggleDropdown = () => {
  isOpen.value = !isOpen.value;
};

const selectOption = (option) => {
  emit('update:modelValue', option.value);
  isOpen.value = false;
};

const selectedOption = computed(() => {
  return props.options.find(opt => opt.value === props.modelValue) || null;
});

const closeDropdown = (e) => {
  if (selectRef.value && !selectRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

onMounted(() => {
  document.addEventListener('click', closeDropdown);
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
});
</script>
