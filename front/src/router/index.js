// src/router/index.js

import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    meta: { guest: true }
  },
  {
    path: '/client',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { roles: ['client'] },
    children: [
      {
        path: '',
        name: 'ClientDashboard',
        component: () => import('@/views/client/DashboardView.vue')
      },
      {
        path: 'tickets',
        name: 'ClientTickets',
        component: () => import('@/views/client/TicketsView.vue') // TODO: créer
      },
      {
        path: 'tickets/nouveau',
        name: 'ClientNewTicket',
        component: () => import('@/views/client/NewTicketView.vue') // TODO: créer
      }
    ]
  },
  {
    path: '/technicien',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { roles: ['technicien', 'administrateur'] },
    children: [
      {
        path: '',
        name: 'TechnicienTickets',
        component: () => import('@/views/technicien/TicketsView.vue') // TODO: créer
      },
      {
        path: 'tickets/:id',
        name: 'TechnicienTicketDetail',
        component: () => import('@/views/technicien/TicketDetailView.vue') // TODO: créer
      }
    ]
  },
  {
    path: '/admin',
    component: () => import('@/layouts/AppLayout.vue'),
    meta: { roles: ['administrateur'] },
    children: [
      {
        path: '',
        name: 'AdminStats',
        component: () => import('@/views/admin/StatsView.vue') // TODO: créer
      },
      {
        path: 'sites',
        name: 'AdminSites',
        component: () => import('@/views/admin/SitesView.vue') // TODO: créer
      },
      {
        path: 'comptes',
        name: 'AdminComptes',
        component: () => import('@/views/admin/ComptesView.vue') // TODO: créer
      },
      {
        path: 'assignation',
        name: 'AdminAssignation',
        component: () => import('@/views/admin/AssignationView.vue') // TODO: créer
      }
    ]
  },
  {
    path: '/',
    redirect: () => {
      const auth = useAuthStore()
      if (!auth.isAuthenticated) return '/login'
      if (auth.isAdmin) return '/admin'
      if (auth.isTechnicien) return '/technicien'
      if (auth.isClient) return '/client'
      return '/login'
    }
  }
]

const router = createRouter({
  history: createWebHistory(),
  routes
})

router.beforeEach((to, from, next) => {
  const auth = useAuthStore()
  if (to.meta.guest && auth.isAuthenticated) {
    if (auth.isAdmin) return next('/admin')
    if (auth.isTechnicien) return next('/technicien')
    return next('/client')
  }
  if (to.matched.some(record => record.meta.roles)) {
    if (!auth.isAuthenticated) {
      return next('/login')
    }
    const allowedRoles = to.meta.roles
    if (!allowedRoles.includes(auth.user?.role)) {
      return next('/login')
    }
  }
  next()
})

export default router