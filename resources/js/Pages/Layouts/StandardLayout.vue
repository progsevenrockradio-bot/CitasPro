<template>
  <div class="min-h-screen bg-bg text-text flex flex-col">
    <header class="bg-primary-dark p-4 shadow-lg">
      <h1 class="text-2xl font-bold text-white">Panel Negocio</h1>
    </header>
    <div class="flex flex-1 overflow-hidden">
      <nav class="w-64 bg-bg-card border-r border-border p-4 overflow-y-auto">
        <ul class="space-y-2">
          <li>
            <router-link to="/citaspro" class="block py-2 px-3 rounded hover:bg-primary/10" active-class="bg-primary/20">Agenda</router-link>
          </li>
          <li>
            <router-link to="/citaspro/clientes" class="block py-2 px-3 rounded hover:bg-primary/10" active-class="bg-primary/20">Clientes</router-link>
          </li>
          <li>
            <router-link to="/citaspro/configuracion/whatsapp" class="block py-2 px-3 rounded hover:bg-primary/10" active-class="bg-primary/20">WhatsApp QR</router-link>
          </li>
        </ul>
      </nav>
      <main class="flex-1 overflow-y-auto p-6">
        <router-view />
      </main>
    </div>
  </div>
</template>

<script setup>
// Layout para negocios sin módulo médico
</script>

<style scoped>
/* estilos opcionales */
</style>
