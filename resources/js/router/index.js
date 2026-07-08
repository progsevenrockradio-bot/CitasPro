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
        path: 'pro',
        name: 'AgendaPro',
        component: () => import('../Pages/Appointments/Pro/AgendaPro.vue')
      },
      {
        path: 'medical',
        name: 'AgendaMedical',
        component: () => import('../Pages/Appointments/Medical/AgendaMedical.vue')
      },
      {
        path: 'dental',
        name: 'AgendaDental',
        component: () => import('../Pages/Appointments/Dental/AgendaDental.vue')
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
        path: 'pagos',
        name: 'HistorialPagos',
        component: () => import('../Pages/Dashboard/Pagos/ListadoPagos.vue')
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
      },
      {
        path: 'configuracion/horarios',
        name: 'ConfiguracionHorarios',
        component: () => import('../Pages/Dashboard/Configuracion/HorariosConfig.vue')
      },
      {
        path: 'superadmin/web-config',
        name: 'SuperAdminWebConfig',
        component: () => import('../Pages/Dashboard/Configuracion/WebConfigPanel.vue')
      }
    ]
  },
  // ── Autenticación ──────────────────────────────────────────────
  {
    path: '/login',
    name: 'Login',
    component: () => import('../Pages/Auth/Login.vue'),
    meta: { public: true }
  },
  {
    path: '/registro',
    name: 'Registro',
    component: () => import('../Pages/Auth/Registro.vue'),
    meta: { public: true }
  },
  // ── Páginas Públicas (sin autenticación) ───────────────────────
  {
    path: '/',
    name: 'Home',
    component: () => import('../Pages/Public/Home.vue'),
    meta: { public: true }
  },
  {
    path: '/directorio',
    redirect: '/'
  },
  {
    // Página de reserva pública: el profesional comparte /{slug}/book
    path: '/:slug/book',
    name: 'ReservaPublica',
    component: () => import('../Pages/Public/ReservaPublica.vue'),
    meta: { public: true }
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

// Guard de navegación
router.beforeEach((to, from, next) => {
  const isAuthenticated = localStorage.getItem('token');
  const isPublic = to.meta?.public === true;

  if (isPublic) {
    // Rutas públicas: siempre accesibles
    next();
  } else if (!isAuthenticated) {
    // Ruta privada sin sesión → Login
    next({ name: 'Login' });
  } else if ((to.name === 'Login' || to.name === 'Registro') && isAuthenticated) {
    // Ya logueado intentando ir al login → Panel
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;

