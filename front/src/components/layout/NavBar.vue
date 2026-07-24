<!-- src/components/layout/NavBar.vue -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { notifySuccess } from '@/services/alert'
import NotificationsPanel from '@/components/notifications/NotificationsPanel.vue'

const router = useRouter()
const authStore = useAuthStore()

const drawerOpen = ref(false)
const showNotifications = ref(false)
const unreadCount = ref(0)

// ----------------------------------- Icônes SVG inline -----------------------------------
function shieldIcon() {
  return `<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>`
}
function homeIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>`
}
function ticketIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><line x1="9" y1="9" x2="15" y2="9"/><line x1="9" y1="13" x2="15" y2="13"/></svg>`
}
function plusCircleIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>`
}
function globeIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>`
}
function usersIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>`
}
function checkIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>`
}
function bellIconSVG() {
  return `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>`
}
function logOutIcon() {
  return `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>`
}
function menuIcon() {
  return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="18" y2="18"/></svg>`
}
function xIcon() {
  return `<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>`
}

// Mapping des labels vers les fonctions d'icône
const iconFuncMap = {
  'Tableau de bord': homeIcon,
  'Mes tickets': ticketIcon,
  'Tickets': ticketIcon,
  'Billets': ticketIcon,
  'Nouveau ticket': plusCircleIcon,
  'Sites': globeIcon,
  'Comptes': usersIcon,
  'Assignation': checkIcon,
}

// ----------------------------------- Liens de navigation -----------------------------------
const menuLinks = computed(() => {
  if (authStore.isClient) {
    return [
      { label: 'Tableau de bord', path: '/client', icon: 'home' },
      { label: 'Mes tickets', path: '/client/tickets', icon: 'ticket' },
      { label: 'Nouveau ticket', path: '/client/tickets/nouveau', icon: 'plus-circle' }
    ]
  } else if (authStore.isTechnicien) {
    return [
      { label: 'Tickets', path: '/technicien', icon: 'ticket' }
    ]
  } else if (authStore.isAdmin) {
    return [
      { label: 'Tableau de bord', path: '/admin', icon: 'home' },
      { label: 'Billets', path: '/technicien', icon: 'ticket' },
      { label: 'Sites', path: '/admin/sites', icon: 'globe' },
      { label: 'Comptes', path: '/admin/comptes', icon: 'users' },
      { label: 'Assignation', path: '/admin/assignation', icon: 'check' }
    ]
  }
  return []
})

// ----------------------------------- Méthodes -----------------------------------
function toggleDrawer() {
  drawerOpen.value = !drawerOpen.value
}

function closeDrawer() {
  drawerOpen.value = false
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
}

function closeNotifications() {
  showNotifications.value = false
}

async function handleLogout() {
  await authStore.logout()
  notifySuccess('Déconnexion réussie')
  router.push('/login')
}

function handleResize() {
  if (window.innerWidth > 768) {
    drawerOpen.value = false
  }
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
})

onUnmounted(() => {
  window.removeEventListener('resize', handleResize)
})
</script>

<template>
  <header class="navbar">
    <div class="navbar-inner">
      <!-- Logo + marque -->
      <router-link to="/" class="brand" @click="closeDrawer">
        <span class="brand-icon" v-html="shieldIcon()"></span>
        <span class="brand-text">HostWatch</span>
      </router-link>

      <!-- Navigation desktop -->
      <nav class="nav-links">
        <router-link
          v-for="link in menuLinks"
          :key="link.path"
          :to="link.path"
          class="nav-link"
          active-class="nav-link--active"
        >
          <span class="link-icon" v-html="iconFuncMap[link.label] ? iconFuncMap[link.label]() : ''"></span>
          <span>{{ link.label }}</span>
        </router-link>
      </nav>

      <!-- Actions à droite -->
      <div class="nav-actions">
        <!-- Notifications -->
        <button
          class="icon-btn notification-btn"
          aria-label="Notifications"
          @click="toggleNotifications"
        >
          <span v-html="bellIconSVG()"></span>
          <span v-if="unreadCount > 0" class="badge">{{ unreadCount }}</span>
        </button>

        <!-- Déconnexion -->
        <button class="icon-btn logout-btn" @click="handleLogout" aria-label="Déconnexion">
          <span v-html="logOutIcon()"></span>
          <span class="logout-text">Déconnexion</span>
        </button>

        <!-- Hamburger mobile -->
        <button class="icon-btn hamburger-btn" @click="toggleDrawer" aria-label="Menu">
          <span v-html="menuIcon()"></span>
        </button>
      </div>
    </div>

    <!-- Panel de notifications -->
    <NotificationsPanel
      v-if="showNotifications"
      v-model="unreadCount"
      @close="closeNotifications"
    />

    <!-- Drawer mobile -->
    <Transition name="drawer">
      <div v-if="drawerOpen" class="drawer-overlay" @click.self="closeDrawer">
        <nav class="drawer">
          <div class="drawer-header">
            <span class="drawer-title">Menu</span>
            <button class="icon-btn drawer-close-btn" @click="closeDrawer" v-html="xIcon()"></button>
          </div>
          <router-link
            v-for="link in menuLinks"
            :key="link.path"
            :to="link.path"
            class="drawer-link"
            active-class="drawer-link--active"
            @click="closeDrawer"
          >
            <span class="link-icon" v-html="iconFuncMap[link.label] ? iconFuncMap[link.label]() : ''"></span>
            <span>{{ link.label }}</span>
          </router-link>
        </nav>
      </div>
    </Transition>
  </header>
</template>

<style lang="scss" scoped>
@use '@/assets/styles/variables.scss' as *;

.navbar {
  background-color: $color-white;
  border-bottom: 1px solid #e5e7eb;
  box-shadow: 0 1px 2px rgba(0,0,0,0.04);
  position: sticky;
  top: 0;
  z-index: 100;
}

.navbar-inner {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 24px;
  height: 64px;
  @media (max-width: $breakpoint-tablet) {
    padding: 0 16px;
  }
}

// ------ Logo ------
.brand {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  flex-shrink: 0;
}

.brand-icon {
  display: flex;
  align-items: center;
  justify-content: center;
}

.brand-text {
  font-size: 1.3rem;
  font-weight: 600;
  color: $color-primary;
  letter-spacing: -0.5px;
}

// ------ Navigation desktop ------
.nav-links {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-left: 40px;
  flex: 1;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 6px;
  color: $color-neutral-dark;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
  transition: background-color 0.15s, color 0.15s;
  white-space: nowrap;
}

.nav-link:hover {
  background-color: $color-neutral-light;
}

.nav-link--active {
  color: $color-accent;
  background-color: rgba($color-accent, 0.06);
}

.link-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 18px;
  height: 18px;
}

// Cacher les liens sur mobile (le hamburger prend le relais)
@media (max-width: $breakpoint-tablet) {
  .nav-links {
    display: none;
  }
}

// ------ Actions ------
.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.icon-btn {
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px 10px;
  border-radius: 6px;
  color: $color-neutral-dark;
  transition: background-color 0.15s;
  font-size: 0.9rem;
  &:hover {
    background-color: $color-neutral-light;
  }
}

.notification-btn {
  position: relative;
}

.badge {
  position: absolute;
  top: 2px;
  right: 2px;
  background-color: $color-danger;
  color: white;
  border-radius: 10px;
  font-size: 0.7rem;
  padding: 0.1rem 0.35rem;
  min-width: 16px;
  text-align: center;
  line-height: 1.2;
}

.logout-btn {
  border-left: 1px solid $color-border;
  padding-left: 14px;
  margin-left: 4px;
}

.logout-text {
  font-size: 0.85rem;
}

// Masquer le hamburger en desktop
.hamburger-btn {
  display: none;
}

@media (max-width: $breakpoint-tablet) {
  .hamburger-btn {
    display: flex;
  }
  .logout-text {
    display: none;
  }
}

// ------ Drawer mobile ------
.drawer-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0,0,0,0.2);
  z-index: 200;
  display: flex;
  justify-content: flex-end;
}

.drawer {
  background-color: $color-white;
  width: 280px;
  max-width: 80vw;
  height: 100%;
  padding: 24px;
  display: flex;
  flex-direction: column;
  gap: 12px;
  box-shadow: -2px 0 10px rgba(0,0,0,0.1);
}

.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 8px;
}

.drawer-title {
  font-weight: 600;
  font-size: 1.1rem;
  color: $color-primary;
}

.drawer-close-btn {
  padding: 4px;
}

.drawer-link {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border-radius: 6px;
  text-decoration: none;
  color: $color-neutral-dark;
  font-size: 0.9rem;
  transition: background-color 0.15s;
  &:hover {
    background-color: $color-neutral-light;
  }
  &--active {
    color: $color-accent;
    background-color: rgba($color-accent, 0.06);
  }
}

// Transition drawer
.drawer-enter-active,
.drawer-leave-active {
  transition: opacity 0.2s ease;
  .drawer {
    transition: transform 0.2s ease;
  }
}
.drawer-enter-from,
.drawer-leave-to {
  opacity: 0;
  .drawer {
    transform: translateX(100%);
  }
}
.drawer-enter-to,
.drawer-leave-from {
  opacity: 1;
  .drawer {
    transform: translateX(0);
  }
}
</style>