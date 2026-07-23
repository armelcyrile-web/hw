<!-- src/components/layout/NavBar.vue -->
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { notifySuccess } from '@/services/alert'

const router = useRouter()
const authStore = useAuthStore()

const drawerOpen = ref(false)
const notificationsCount = ref(0) // TODO: brancher plus tard

const menuLinks = computed(() => {
  if (authStore.isClient) {
    return [
      { label: 'Tableau de bord', path: '/client' },
      { label: 'Mes tickets', path: '/client/tickets' },
      { label: 'Nouveau ticket', path: '/client/tickets/nouveau' }
    ]
  } else if (authStore.isTechnicien) {
    return [
      { label: 'Tickets', path: '/technicien' }
    ]
  } else if (authStore.isAdmin) {
    return [
      { label: 'Tableau de bord', path: '/admin' },
      { label: 'Sites', path: '/admin/sites' },
      { label: 'Comptes', path: '/admin/comptes' },
      { label: 'Assignation', path: '/admin/assignation' }
    ]
  }
  return []
})

function toggleDrawer() {
  drawerOpen.value = !drawerOpen.value
}

function closeDrawer() {
  drawerOpen.value = false
}

function navigate(path) {
  closeDrawer()
  router.push(path)
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
      <router-link to="/" class="brand" @click="closeDrawer">HostWatch</router-link>
      <nav class="nav-links">
        <router-link
          v-for="link in menuLinks"
          :key="link.path"
          :to="link.path"
          class="nav-link"
          active-class="nav-link--active"
        >
          {{ link.label }}
        </router-link>
      </nav>
      <div class="nav-actions">
        <button class="icon-btn notification-btn" aria-label="Notifications">
          <span class="bell-icon">🔔</span>
          <span v-if="notificationsCount > 0" class="badge">{{ notificationsCount }}</span>
        </button>
        <button class="icon-btn logout-btn" @click="handleLogout" aria-label="Déconnexion">
          <span class="logout-icon">⏻</span>
        </button>
        <button class="icon-btn hamburger-btn" @click="toggleDrawer" aria-label="Menu">
          <span class="hamburger-icon">☰</span>
        </button>
      </div>
    </div>
    <!-- Drawer mobile -->
    <Transition name="drawer">
      <div v-if="drawerOpen" class="drawer-overlay" @click.self="closeDrawer">
        <nav class="drawer">
          <div class="drawer-header">
            <span class="drawer-title">Menu</span>
            <button class="icon-btn" @click="closeDrawer">✕</button>
          </div>
          <router-link
            v-for="link in menuLinks"
            :key="link.path"
            :to="link.path"
            class="drawer-link"
            active-class="drawer-link--active"
            @click="closeDrawer"
          >
            {{ link.label }}
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
  border-bottom: 1px solid $color-border; // bordure basse nette
  box-shadow: 0 1px 2px rgba(0,0,0,0.04); // ombre très légère
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
  padding: 0 $spacing-md;
  height: 56px;
}

.brand {
  font-size: 1.25rem;
  font-weight: 600;
  color: $color-primary;
  text-decoration: none;
  letter-spacing: -0.5px;
}

.nav-links {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
}

.nav-link {
  color: $color-neutral-dark;
  text-decoration: none;
  padding: 0.35rem 0.75rem;
  border-radius: $border-radius;
  font-size: 0.9rem;
  transition: background-color 0.15s;
  &:hover {
    background-color: $color-neutral-light;
  }
  &--active {
    color: $color-accent;
    font-weight: 500;
  }
}

@media (max-width: $breakpoint-tablet) {
  .nav-links {
    display: none;
  }
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: $spacing-xs;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  padding: 0.35rem;
  border-radius: $border-radius;
  display: flex;
  align-items: center;
  justify-content: center;
  &:hover {
    background-color: $color-neutral-light;
  }
}

.notification-btn {
  position: relative;
}

.bell-icon {
  font-size: 1.1rem;
  color: $color-neutral-dark;
}

.badge {
  position: absolute;
  top: -2px;
  right: -2px;
  background-color: $color-danger;
  color: white;
  border-radius: 10px;
  font-size: 0.7rem;
  padding: 0.1rem 0.35rem;
  min-width: 16px;
  text-align: center;
}

.logout-icon {
  font-size: 1.1rem;
  color: $color-neutral-dark;
}

.hamburger-btn {
  display: none;
  @media (max-width: $breakpoint-tablet) {
    display: flex;
  }
}

.hamburger-icon {
  font-size: 1.3rem;
  color: $color-neutral-dark;
}

/* Drawer */
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
  padding: $spacing-lg;
  display: flex;
  flex-direction: column;
  gap: $spacing-sm;
  box-shadow: -2px 0 10px rgba(0,0,0,0.1);
}

.drawer-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: $spacing-md;
}

.drawer-title {
  font-weight: 600;
  font-size: 1.1rem;
  color: $color-primary;
}

.drawer-link {
  padding: 0.75rem $spacing-sm;
  text-decoration: none;
  color: $color-neutral-dark;
  border-radius: $border-radius;
  transition: background-color 0.15s;
  &:hover {
    background-color: $color-neutral-light;
  }
  &--active {
    color: $color-accent;
    font-weight: 500;
  }
}

/* Transition */
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