<!-- src/views/client/TicketsView.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { notifyError } from '@/services/alert'

const router = useRouter()

const tickets = ref([])
const loading = ref(true)
const ticketSelectionne = ref(null)
const loadingDetail = ref(false)

async function fetchTickets() {
  loading.value = true
  try {
    const response = await api.get('/tickets')
    tickets.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement de vos tickets.')
  } finally {
    loading.value = false
  }
}

async function selectTicket(ticketId) {
  loadingDetail.value = true
  try {
    const response = await api.get(`/tickets/${ticketId}`)
    ticketSelectionne.value = response.data.data
  } catch (error) {
    notifyError('Impossible de charger le détail du ticket.')
    ticketSelectionne.value = null
  } finally {
    loadingDetail.value = false
  }
}

function retourListe() {
  ticketSelectionne.value = null
}

function statutClass(statut) {
  switch (statut) {
    case 'nouveau': return 'badge-nouveau'
    case 'assigne': return 'badge-assigne'
    case 'resolu': return 'badge-resolu'
    default: return ''
  }
}

function statutLabel(statut) {
  switch (statut) {
    case 'nouveau': return 'Nouveau'
    case 'assigne': return 'Assigné'
    case 'resolu': return 'Résolu'
    default: return statut
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(fetchTickets)
</script>

<template>
  <div class="tickets-page">
    <!-- Liste des tickets (toujours visible) -->
    <div class="tickets-list-panel" :class="{ 'is-hidden-mobile': ticketSelectionne && isMobile }">
      <h2 class="page-title">Mes tickets</h2>
      <div v-if="loading" class="state-message">Chargement...</div>
      <template v-else>
        <div v-if="tickets.length === 0" class="empty-state">
          <p>Vous n'avez pas encore de ticket.</p>
          <button class="btn btn-primary" @click="router.push('/client/tickets/nouveau')">
            Ouvrir un ticket
          </button>
        </div>
        <div v-else class="list-container">
          <div
            v-for="ticket in tickets"
            :key="ticket.id"
            class="ticket-item"
            :class="{ 'is-active': ticketSelectionne?.id === ticket.id }"
            @click="selectTicket(ticket.id)"
          >
            <div class="item-id">#{{ ticket.id }}</div>
            <div class="item-content">
              <div class="item-title">{{ ticket.titre }}</div>
              <div class="item-meta">
                <span class="badge" :class="statutClass(ticket.statut)">
                  {{ statutLabel(ticket.statut) }}
                </span>
                <span class="item-date">{{ formatDate(ticket.date_creation) }}</span>
              </div>
            </div>
          </div>
        </div>
      </template>
    </div>

    <!-- Panneau de détail -->
    <div class="ticket-detail-panel" :class="{ 'is-visible-mobile': ticketSelectionne }">
      <div v-if="!ticketSelectionne" class="state-message desktop-only">
        Sélectionnez un ticket pour voir le détail
      </div>
      <template v-else>
        <div class="detail-header">
          <button class="btn-retour" @click="retourListe">
            ← Retour à la liste
          </button>
        </div>
        <div v-if="loadingDetail" class="state-message">Chargement du détail...</div>
        <div v-else class="detail-content">
          <div class="detail-top">
            <h3>{{ ticketSelectionne.titre }}</h3>
            <span class="badge" :class="statutClass(ticketSelectionne.statut)">
              {{ statutLabel(ticketSelectionne.statut) }}
            </span>
          </div>
          <div class="detail-infos">
            <p><strong>Site :</strong> {{ ticketSelectionne.site?.nom }}</p>
            <p><strong>Priorité :</strong> {{ ticketSelectionne.priorite }}</p>
            <p><strong>Ouvert le :</strong> {{ formatDate(ticketSelectionne.date_creation) }}</p>
            <p v-if="ticketSelectionne.date_resolution"><strong>Résolu le :</strong> {{ formatDate(ticketSelectionne.date_resolution) }}</p>
          </div>
          <div class="detail-description">
            <h4>Description</h4>
            <p>{{ ticketSelectionne.description }}</p>
          </div>
          <!-- Historique -->
          <div v-if="ticketSelectionne.historique?.length" class="detail-history">
            <h4>Historique</h4>
            <ul>
              <li v-for="h in ticketSelectionne.historique" :key="h.id">
                <span class="history-action">{{ h.type_action }}</span>
                <span class="history-user" v-if="h.utilisateur"> par {{ h.utilisateur.nom }} {{ h.utilisateur.prenom }}</span>
                <span class="history-date"> le {{ formatDate(h.date_action) }}</span>
                <span v-if="h.duree_intervention"> ({{ h.duree_intervention }} min)</span>
                <span v-if="h.commentaire"> - {{ h.commentaire }}</span>
              </li>
            </ul>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

// Badge colors for ticket status
$badge-nouveau-bg: #dbeafe;
$badge-nouveau-text: #1e40af;
$badge-assigne-bg: #fef3c7;
$badge-assigne-text: #92400e;
$badge-resolu-bg: #dcfce7;
$badge-resolu-text: #166534;

.tickets-page {
  display: grid;
  grid-template-columns: 35% 65%;
  min-height: calc(100vh - 64px); // navbar height
  background-color: $color-neutral-light;
  @media (max-width: $breakpoint-tablet) {
    grid-template-columns: 1fr;
  }
}

.tickets-list-panel {
  border-right: 1px solid $color-border;
  background: $color-white;
  padding: $spacing-lg;
  overflow-y: auto;
  @media (max-width: $breakpoint-tablet) {
    border-right: none;
    padding: $spacing-md;
    &.is-hidden-mobile {
      display: none; // on mobile, hide list when detail is shown
    }
  }
}

.page-title {
  font-size: 1.3rem;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-md;
}

.state-message {
  color: $color-neutral-dark;
  text-align: center;
  padding: $spacing-xl;
}

.desktop-only {
  display: block;
  @media (max-width: $breakpoint-tablet) {
    display: none;
  }
}

.empty-state {
  text-align: center;
  margin-top: $spacing-xl;
  p {
    color: $color-neutral-dark;
    margin-bottom: $spacing-md;
  }
}

.list-container {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.ticket-item {
  display: flex;
  align-items: center;
  padding: $spacing-sm $spacing-xs;
  border-radius: $border-radius;
  cursor: pointer;
  transition: background-color 0.1s;
  border: 1px solid transparent;
  &:hover {
    background-color: $color-neutral-light;
  }
  &.is-active {
    background-color: rgba($color-accent, 0.06);
    border-color: $color-accent;
  }
}

.item-id {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  width: 40px;
  flex-shrink: 0;
}

.item-content {
  flex: 1;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: $spacing-sm;
}

.item-title {
  font-weight: 500;
  font-size: 0.9rem;
  color: $color-primary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.item-meta {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  flex-shrink: 0;
}

.item-date {
  font-size: 0.8rem;
  color: $color-neutral-dark;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}

.badge-nouveau {
  background-color: $badge-nouveau-bg;
  color: $badge-nouveau-text;
}
.badge-assigne {
  background-color: $badge-assigne-bg;
  color: $badge-assigne-text;
}
.badge-resolu {
  background-color: $badge-resolu-bg;
  color: $badge-resolu-text;
}

// Détail panel
.ticket-detail-panel {
  padding: $spacing-lg;
  overflow-y: auto;
  background: $color-white;
  @media (max-width: $breakpoint-tablet) {
    display: none;
    padding: $spacing-md;
    &.is-visible-mobile {
      display: block;
    }
  }
}

.detail-header {
  margin-bottom: $spacing-md;
}

.btn-retour {
  background: none;
  border: none;
  color: $color-accent;
  cursor: pointer;
  padding: 0;
  font-size: 0.9rem;
  display: inline-flex;
  align-items: center;
  &:hover {
    text-decoration: underline;
  }
}

.detail-top {
  display: flex;
  align-items: center;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;
  h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: $color-primary;
    margin: 0;
  }
}

.detail-infos {
  margin-bottom: $spacing-md;
  p {
    margin-bottom: 0.4rem;
    font-size: 0.9rem;
  }
}

.detail-description {
  margin-bottom: $spacing-md;
  h4 {
    font-size: 1rem;
    margin-bottom: 0.5rem;
  }
  p {
    white-space: pre-wrap;
    font-size: 0.9rem;
    color: $color-neutral-dark;
  }
}

.detail-history {
  h4 {
    margin-bottom: 0.5rem;
  }
  ul {
    list-style: none;
    padding: 0;
    li {
      padding: 0.4rem 0;
      border-bottom: 1px solid $color-border;
      font-size: 0.85rem;
    }
  }
}

.history-action {
  font-weight: 600;
  text-transform: lowercase;
}

// Button for empty state only (new ticket creation)
.btn {
  padding: 0.5rem 1rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-primary {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}
</style>