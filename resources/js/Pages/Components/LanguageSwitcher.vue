<template>
  <div class="relative group">
    <button class="flex items-center gap-2 px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 border border-white/10 transition-all text-sm font-medium text-white">
      <span>{{ currentLangIcon }}</span>
      <span class="uppercase">{{ currentLang }}</span>
    </button>
    
    <div class="absolute right-0 mt-2 w-32 bg-gray-900 border border-white/10 rounded-xl shadow-2xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 overflow-hidden">
      <button 
        v-for="lang in availableLangs" 
        :key="lang.code"
        @click="changeLang(lang.code)"
        class="w-full text-left px-4 py-3 text-sm flex items-center gap-3 transition-colors hover:bg-white/10"
        :class="{'bg-indigo-600/20 text-indigo-400': currentLang === lang.code, 'text-gray-300': currentLang !== lang.code}"
      >
        <span>{{ lang.icon }}</span>
        <span>{{ lang.name }}</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const availableLangs = [
  { code: 'es', name: 'Español', icon: '🇪🇸' },
  { code: 'en', name: 'English', icon: '🇺🇸' },
  { code: 'it', name: 'Italiano', icon: '🇮🇹' },
  { code: 'pt', name: 'Português', icon: '🇵🇹' }
];

const currentLang = computed(() => locale.value);
const currentLangIcon = computed(() => {
  const l = availableLangs.find(x => x.code === currentLang.value);
  return l ? l.icon : '🌐';
});

const changeLang = (code) => {
  locale.value = code;
  localStorage.setItem('app_lang', code);
};
</script>
