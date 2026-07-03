<template>
  <div class="min-h-screen bg-bg text-text flex">
    <!-- Sidebar -->
    <aside class="w-64 bg-bg-card backdrop-blur-md border-r border-border hidden md:flex flex-col">
      <div class="p-6 flex items-center gap-3">
        <div class="w-8 h-8 rounded-lg bg-primary flex items-center justify-center shadow-[0_0_15px_rgba(99,102,241,0.25)]">
          <span class="text-white font-bold">C</span>
        </div>
        <span class="font-bold text-xl tracking-tight">CitasPro</span>
      </div>

      <nav class="flex-1 px-4 space-y-2 mt-4">
        <router-link to="/panel" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Calendar class="w-5 h-5" /> Agenda
        </router-link>
        <router-link to="/panel/clientes" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Users class="w-5 h-5" /> Clientes
        </router-link>
        
        <div class="pt-6 pb-2 px-4">
          <p class="text-xs font-bold text-text-muted tracking-wider mb-3 px-4">CONFIGURACIÓN</p>
        </div>
        
        <router-link to="/panel/configuracion/negocio" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <Settings class="w-5 h-5" /> Mi Negocio
        </router-link>
        <router-link to="/panel/configuracion/whatsapp" class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all" exact-active-class="bg-primary/10 text-primary font-medium" class-active="text-text-muted hover:bg-white/5 hover:text-white">
          <MessageCircle class="w-5 h-5" /> WhatsApp QR
        </router-link>
      </nav>

      <div class="p-4 border-t border-border mt-auto">
        <button @click="logout" class="flex items-center gap-3 px-4 py-3 rounded-xl text-red-400 hover:bg-red-500/10 transition-all w-full text-left">
          <LogOut class="w-5 h-5" /> Cerrar Sesión
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <header class="h-16 border-b border-border bg-bg-card/50 backdrop-blur-sm flex items-center px-6 sticky top-0 z-10">
        <h1 class="text-lg font-semibold">{{ $route.name }}</h1>
      </header>
      
      <div class="flex-1 overflow-auto p-6">
        <router-view></router-view>
      </div>
    </main>
  </div>
</template>

<script setup>
import { Calendar, Users, MessageCircle, LogOut, Settings } from 'lucide-vue-next';
import { useRouter } from 'vue-router';
import axios from 'axios';

const router = useRouter();

const logout = async () => {
  try {
    await axios.post('/logout');
    localStorage.removeItem('token');
    router.push('/login');
  } catch (error) {
    console.error('Logout failed', error);
  }
};
</script>
