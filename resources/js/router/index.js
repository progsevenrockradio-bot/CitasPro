import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  {
    path: '/panel',
    component: () => import('../Pages/Layouts/DashboardLayout.vue'),
    children: [
      {
        path: '',
        name: 'Dashboard',
        component: () => import('../Pages/Dashboard/Agenda.vue')
      },
      {
        path: 'pacientes',
        name: 'Pacientes',
        component: () => import('../Pages/Dashboard/Pacientes/HistorialMedico.vue')
      },
      {
        path: 'configuracion/whatsapp',
        name: 'WhatsAppQR',
        component: () => import('../Pages/Dashboard/Configuracion/WhatsAppQR.vue')
      }
    ]
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('../Pages/Auth/Login.vue')
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

export default router;
