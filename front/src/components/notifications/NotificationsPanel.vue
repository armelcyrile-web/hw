<!-- src/components/notifications/NotificationsPanel.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'
import { notifyError } from '@/services/alert'

const router = useRouter()
const authStore = useAuthStore()

// v-model pour le nombre de notifications non lues (communiqué à la NavBar)
const unreadCount = defineModel({ type: Number, default: 0 })

// Émission de fermeture demandée par le parent (NavBar)
const emit = defineEmits(['close'])

const notifications = ref([])
const loading = ref(false)

async function fetchUnreadCount() {
  try {
    const res = await api.get('/notifications/non-lues-count')
    unreadCount.value = res.data.count
  } catch (e) {
    // silencieux, le badge reste sur la valeur précédente
  }
}

async function fetchNotifications() {
  loading.value = true
  try {
    const res = await api.get('/notifications')
    notifications.value = res.data.data
  } catch (e) {
    notifyError('Impossible de charger les notifications.')
  } finally {
    loading.value = false
  }
}

async function markAsRead(notificationId) {
  try {
    await api.post(`/notifications/${notificationId}/lue`)
  } catch (e) {
    // silencieux
  }
}

async function markAllAsRead() {
  try {
    await api.post('/notifications/toutes-lues')
    // Mise à jour locale
    notifications.value.forEach(n => (n.lu = true))
    unreadCount.value = 0
  } catch (e) {
    notifyError('Erreur lors du marquage.')
  }
}

function closePanel() {
  emit('close')
}

function getTicketLink(notification) {
  const ticketId = notification.data?.ticket_id
  if (!ticketId) return null
  // Redirection en fonction du rôle
  if (authStore.isClient) {
    return '/client/tickets' // pas de vue détail côté client, redirige vers la liste
  }
  // Technicien et administrateur partagent la même vue de détail
  if (authStore.isTechnicien || authStore.isAdmin) {
    return `/technicien/tickets/${ticketId}`
  }
  return '/'
}

async function handleNotifClick(notification) {
  // Marquer comme lue
  if (!notification.lu) {
    await markAsRead(notification.id)
    notification.lu = true
    unreadCount.value = Math.max(0, unreadCount.value - 1)
  }

  const link = getTicketLink(notification)
  if (link) {
    closePanel()
    router.push(link)
  } else {
    closePanel()
  }
}

function formatDateRelative(dateString) {
  const now = new Date()
  const date = new Date(dateString)
  const diffMs = now - date
  const diffSec = Math.floor(diffMs / 1000)
  if (diffSec < 5) return "à l'instant"
  if (diffSec < 60) return `il y a ${diffSec} s`
  const diffMin = Math.floor(diffSec / 60)
  if (diffMin < 60) return `il y a ${diffMin} min`
  const diffH = Math.floor(diffMin / 60)
  if (diffH < 24) return `il y a ${diffH} h`
  const diffJ = Math.floor(diffH / 24)
  return `il y a ${diffJ} jour(s)`
}

onMounted(() => {
  fetchUnreadCount()
  fetchNotifications()
})
</script>

<template>
  <Teleport to="body">
    <div class="notif-overlay" @click.self="closePanel">
      <div class="notif-panel" @click.stop>
        <div class="panel-header">
          <h3>Notifications</h3>
          <button
            class="mark-all-read"
            @click="markAllAsRead"
            :disabled="unreadCount === 0"
          >
            Tout marquer comme lu
          </button>
        </div>

        <div v-if="loading" class="state-message">Chargement...</div>
        <div v-else-if="notifications.length === 0" class="state-message">
          Aucune notification pour le moment.
        </div>
        <ul v-else class="notif-list">
          <li
            v-for="notif in notifications"
            :key="notif.id"
            class="notif-item"
            :class="{ 'non-lue': !notif.lu }"
            @click="handleNotifClick(notif)"
          >
            <span v-if="!notif.lu" class="dot"></span>
            <div class="notif-content">
              <p class="notif-message">{{ notif.data.message }}</p>
              <span class="notif-time">{{ formatDateRelative(notif.date) }}</span>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

.notif-overlay {
  position: fixed;
  inset: 0;
  background: transparent;
  z-index: 200;
}

.notif-panel {
  position: absolute;
  top: 56px;
  right: 1rem;
  width: 380px;
  max-width: calc(100vw - 2rem);
  max-height: calc(100vh - 70px);
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
  overflow: hidden;

  @media (max-width: $breakpoint-mobile) {
    top: 56px;
    left: 0;
    right: 0;
    width: 100%;
    max-width: 100%;
    border-radius: 0;
    border: none;
    border-top: 1px solid $color-border;
  }
}

.panel-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $color-border;
  h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: $color-primary;
  }
}

.mark-all-read {
  background: none;
  border: none;
  color: $color-accent;
  cursor: pointer;
  font-size: 0.8rem;
  &:hover {
    text-decoration: underline;
  }
  &:disabled {
    opacity: 0.5;
    cursor: default;
    text-decoration: none;
  }
}

.state-message {
  padding: $spacing-lg;
  text-align: center;
  color: $color-neutral-dark;
  font-size: 0.9rem;
}

.notif-list {
  list-style: none;
  padding: 0;
  margin: 0;
  overflow-y: auto;
  flex: 1;
}

.notif-item {
  display: flex;
  align-items: flex-start;
  padding: $spacing-sm $spacing-lg;
  border-bottom: 1px solid $color-border;
  cursor: pointer;
  transition: background-color 0.1s;
  &:hover {
    background-color: $color-neutral-light;
  }
  &.non-lue {
    background-color: rgba($color-accent, 0.02);
  }
  &:last-child {
    border-bottom: none;
  }
}

.dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background-color: $color-accent;
  flex-shrink: 0;
  margin-top: 0.5rem;
  margin-right: $spacing-sm;
}

.notif-content {
  flex: 1;
}

.notif-message {
  margin: 0 0 0.2rem 0;
  font-size: 0.9rem;
  color: $color-primary;
  line-height: 1.4;
}

.notif-time {
  font-size: 0.75rem;
  color: $color-neutral-dark;
}
</style>