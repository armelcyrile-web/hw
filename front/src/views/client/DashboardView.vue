<!-- src/views/client/DashboardView.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { notifyError } from '@/services/alert'

const router = useRouter()
const sites = ref([])
const loading = ref(true)

async function fetchSites() {
  loading.value = true
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement de vos sites.')
  } finally {
    loading.value = false
  }
}

function goToNewTicket(siteId) {
  router.push(`/client/tickets/nouveau?site=${siteId}`)
}

function formatDate(dateString) {
  if (!dateString) return 'Jamais vérifié'
  const date = new Date(dateString)
  const now = new Date()
  const diffMinutes = Math.floor((now - date) / 60000)
  if (diffMinutes < 1) return "À l'instant"
  if (diffMinutes < 60) return `Il y a ${diffMinutes} min`
  const diffHours = Math.floor(diffMinutes / 60)
  if (diffHours < 24) return `Il y a ${diffHours}h`
  return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })
}

onMounted(fetchSites)
</script>

<template>
  <div class="dashboard">
    <h2 class="page-title">Tableau de bord</h2>
    <div v-if="loading" class="state-message">Chargement...</div>
    <template v-else>
      <div v-if="sites.length === 0" class="state-message">
        Aucun site associé à votre compte pour le moment.
      </div>
      <div v-else class="sites-grid">
        <article v-for="site in sites" :key="site.id" class="site-card">
          <div class="site-info">
            <h3 class="site-name">{{ site.nom }}</h3>
            <p class="site-url">{{ site.url }}</p>
          </div>
          <div class="site-meta">
            <span class="badge" :class="'badge-' + site.statut_disponibilite">
              {{ site.statut_disponibilite === 'en_ligne' ? 'En ligne' : site.statut_disponibilite === 'hors_ligne' ? 'Hors ligne' : 'Inconnu' }}
            </span>
            <span class="verification-date">Dernière vérification : {{ formatDate(site.date_derniere_verification) }}</span>
          </div>
          <div class="site-actions">
            <button class="btn-open-ticket" @click="goToNewTicket(site.id)">
              Ouvrir un ticket
            </button>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
@use '@/assets/styles/variables.scss' as *;

.dashboard {
  padding: $spacing-lg $spacing-md;
  background-color: $color-neutral-light; // fond gris clair pour faire ressortir les cartes
  min-height: 100%;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-lg;
}

.state-message {
  color: $color-neutral-dark;
  text-align: center;
  padding: $spacing-xl;
}

.sites-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: $spacing-md;
}

.site-card {
  background-color: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-lg;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08); // ombre douce pour la profondeur
  transition: box-shadow 0.15s;
  &:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  }
}

.site-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-xs;
}

.site-url {
  font-size: 0.85rem;
  color: $color-neutral-dark;
  word-break: break-all;
  margin-bottom: $spacing-md;
}

.site-meta {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;
  flex-wrap: wrap;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.badge-en_ligne {
  background-color: $badge-success-bg;
  color: $badge-success-text;
}

.badge-hors_ligne {
  background-color: $badge-danger-bg;
  color: $badge-danger-text;
}

.badge-inconnu {
  background-color: $badge-neutral-bg;
  color: $badge-neutral-text;
}

.verification-date {
  font-size: 0.8rem;
  color: $color-neutral-dark;
}

.site-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: auto;
}

.btn-open-ticket {
  background-color: $color-accent;
  color: white;
  border: none;
  border-radius: $border-radius;
  padding: 0.5rem 1rem;
  font-family: $font-family;
  font-size: 0.85rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s, box-shadow 0.15s;
  &:hover {
    background-color: $color-accent-hover;
    box-shadow: 0 1px 4px rgba(0,0,0,0.1);
  }
}

@media (max-width: $breakpoint-tablet) {
  .sites-grid {
    grid-template-columns: 1fr;
  }
}
</style>