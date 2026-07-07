<template>
  <div class="flex items-center gap-2 no-print">
    <button 
      @click="$emit('print')"
      class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors bg-white/5 border border-border text-text-muted hover:text-white hover:bg-white/10"
      :title="$t('acciones.imprimir')"
    >
      <Printer class="w-4 h-4" />
      <span class="hidden sm:inline">{{ $t('acciones.imprimir') || 'Imprimir' }}</span>
    </button>
    
    <button 
      @click="$emit('pdf')"
      class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors bg-white/5 border border-border text-text-muted hover:text-white hover:bg-white/10"
      :title="$t('acciones.exportar_pdf')"
    >
      <FileText class="w-4 h-4 text-red-400" />
      <span class="hidden sm:inline">PDF</span>
    </button>
    
    <button 
      @click="$emit('email')"
      class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-colors bg-white/5 border border-border text-text-muted hover:text-white hover:bg-white/10"
      :title="$t('acciones.enviar_correo')"
    >
      <Mail class="w-4 h-4 text-blue-400" />
      <span class="hidden sm:inline">{{ $t('acciones.correo') || 'Correo' }}</span>
    </button>
  </div>
</template>

<script setup>
import { Printer, FileText, Mail } from 'lucide-vue-next';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

defineEmits(['print', 'pdf', 'email']);
</script>
