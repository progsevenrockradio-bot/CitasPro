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
        path: 'clientes',
        name: 'Clientes',
        component: () => import('../Pages/Dashboard/Clientes/DirectorioClientes.vue')
      },
      {
        path: 'clientes/:id',
        name: 'FichaCliente',
        component: () => import('../Pages/Dashboard/Clientes/FichaCliente.vue')
      },
      {
        path: 'configuracion/negocio',
        name: 'ConfiguracionNegocio',
        component: () => import('../Pages/Dashboard/Configuracion/NegocioConfig.vue')
      },
      {
        path: 'configuracion/servicios',
        name: 'ConfiguracionServicios',
        component: () => import('../Pages/Dashboard/Configuracion/ServiciosConfig.vue')
      },
      {
        path: 'configuracion/profesionales',
        name: 'ConfiguracionProfesionales',
        component: () => import('../Pages/Dashboard/Configuracion/ProfesionalesConfig.vue')
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
  },
  {
    path: '/registro',
    name: 'Registro',
    component: () => import('../Pages/Auth/Registro.vue')
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: '/panel'
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Guard de navegación para proteger las rutas
router.beforeEach((to, from, next) => {
  // Verificamos si existe un token de sesión (lo implementaremos en el Login real)
  const isAuthenticated = localStorage.getItem('token'); 
  
  if (to.name !== 'Login' && to.name !== 'Registro' && !isAuthenticated) {
    // Si la ruta no es Login o Registro y no está autenticado, lo echamos al Login
    next({ name: 'Login' });
  } else if (to.name === 'Login' && isAuthenticated) {
    // Si ya está logueado y quiere ir al login, lo mandamos al panel
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
