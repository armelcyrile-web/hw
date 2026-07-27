<!-- src/views/technicien/TicketsView.vue -->
<script setup>
import { ref, onMounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'

const router = useRouter()
const filtreActif = ref('tous') // 'tous' | 'nouveau' | 'assigne' | 'resolu'
const tickets = ref([])
const loading = ref(false)

async function fetchTickets() {
  loading.value = true
  try {
    const params = {}
    if (filtreActif.value !== 'tous') {
      // Le backend attend exactement les valeurs d'enum : 'nouveau', 'assigne', 'resolu'
      params.statut = filtreActif.value
    }
    const response = await api.get('/tickets', { params })
    tickets.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement des tickets.')
  } finally {
    loading.value = false
  }
}

// Surveiller le changement de filtre
watch(filtreActif, () => {
  fetchTickets()
})

function goToDetail(ticketId) {
  router.push(`/technicien/tickets/${ticketId}`)
}

async function prendreEnCharge(ticketId) {
  const confirmed = await confirmAction({
    titre: 'Prendre en charge',
    texte: 'Voulez-vous vous assigner ce ticket ?',
    texteConfirmation: 'Confirmer'
  })
  if (!confirmed) return

  try {
    await api.post(`/staff/tickets/${ticketId}/prendre-en-charge`)
    notifySuccess('Ticket pris en charge.')
    await fetchTickets()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de la prise en charge'
    notifyError(message)
    await fetchTickets()
  }
}

function statutClass(statut) {
  switch (statut) {
    case 'nouveau': return 'badge-nouveau'
    case 'assigne': return 'badge-assigne'
    case 'resolu': return 'badge-resolu'
    default: return ''
  }
}

function prioriteClass(priorite) {
  switch (priorite) {
    case 'basse': return 'badge-priorite-basse'
    case 'normale': return 'badge-priorite-normale'
    case 'urgente': return 'badge-priorite-urgente'
    default: return ''
  }
}

onMounted(fetchTickets)
</script>

<template>
  <div class="tickets-view">
    <div class="top-bar">
      <h2 class="page-title">Tickets</h2>
      <div class="filters">
        <button
          v-for="f in [
            { key: 'tous', label: 'Tous' },
            { key: 'nouveau', label: 'Nouveau' },
            { key: 'assigne', label: 'Assigné' },
            { key: 'resolu', label: 'Résolu' }
          ]"
          :key="f.key"
          :class="['filter-btn', { active: filtreActif === f.key }]"
          @click="filtreActif = f.key"
        >
          {{ f.label }}
        </button>
      </div>
    </div>

    <div v-if="loading" class="state-message">Chargement...</div>
    <template v-else>
      <div v-if="tickets.length === 0" class="state-message">
        <template v-if="filtreActif === 'nouveau'">Aucun ticket nouveau pour le moment.</template>
        <template v-else-if="filtreActif === 'assigne'">Aucun ticket assigné.</template>
        <template v-else-if="filtreActif === 'resolu'">Aucun ticket résolu.</template>
        <template v-else>Aucun ticket trouvé.</template>
      </div>

      <!-- Version desktop : tableau -->
      <div v-else class="table-wrapper desktop-only">
        <table class="tickets-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Site</th>
              <th>Titre</th>
              <th>Priorité</th>
              <th>Statut</th>
              <th>Création</th>
              <th class="action-col">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="ticket in tickets"
              :key="ticket.id"
              class="ticket-row"
              @click="goToDetail(ticket.id)"
            >
              <td class="col-id">#{{ ticket.id }}</td>
              <td class="col-site">{{ ticket.site?.nom || '—' }}</td>
              <td class="col-titre">{{ ticket.titre }}</td>
              <td>
                <span class="badge" :class="prioriteClass(ticket.priorite)">{{ ticket.priorite }}</span>
              </td>
              <td>
                <span class="badge" :class="statutClass(ticket.statut)">
                  {{ ticket.statut === 'nouveau' ? 'Nouveau' : ticket.statut === 'assigne' ? 'Assigné' : 'Résolu' }}
                </span>
              </td>
              <td>{{ new Date(ticket.date_creation).toLocaleDateString('fr-FR') }}</td>
              <td class="col-action" @click.stop>
                <button
                  v-if="ticket.statut === 'nouveau'"
                  class="btn btn-take"
                  @click="prendreEnCharge(ticket.id)"
                >
                  Prendre en charge
                </button>
                <!-- Pas de bouton pour les autres statuts, le clic sur la ligne redirige -->
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Version mobile : cartes -->
      <div class="mobile-cards mobile-only">
        <div
          v-for="ticket in tickets"
          :key="ticket.id"
          class="ticket-card"
          @click="goToDetail(ticket.id)"
        >
          <div class="card-top">
            <span class="card-id">#{{ ticket.id }}</span>
            <span class="card-titre">{{ ticket.titre }}</span>
          </div>
          <div class="card-badges">
            <span class="badge" :class="prioriteClass(ticket.priorite)">{{ ticket.priorite }}</span>
            <span class="badge" :class="statutClass(ticket.statut)">
              {{ ticket.statut === 'nouveau' ? 'Nouveau' : ticket.statut === 'assigne' ? 'Assigné' : 'Résolu' }}
            </span>
            <span class="card-site">{{ ticket.site?.nom }}</span>
          </div>
          <div class="card-date">{{ new Date(ticket.date_creation).toLocaleDateString('fr-FR') }}</div>
          <div class="card-action" @click.stop>
            <button
              v-if="ticket.statut === 'nouveau'"
              class="btn btn-take"
              @click="prendreEnCharge(ticket.id)"
            >
              Prendre en charge
            </button>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

// Badges pour priorité
$badge-priorite-basse-bg: #f3f4f6;
$badge-priorite-basse-text: #4b5563;
$badge-priorite-normale-bg: #e0f2fe;
$badge-priorite-normale-text: #075985;
$badge-priorite-urgente-bg: #fee2e2;
$badge-priorite-urgente-text: #991b1b;

// Badges pour statut
$badge-nouveau-bg: #dbeafe;
$badge-nouveau-text: #1e40af;
$badge-assigne-bg: #fef3c7;
$badge-assigne-text: #92400e;
$badge-resolu-bg: #dcfce7;
$badge-resolu-text: #166534;

.tickets-view {
  background-color: $color-neutral-light;
  padding: $spacing-lg $spacing-md;
  min-height: 100%;
  max-width: 1200px;
  margin: 0 auto;
}

.top-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-lg;
  flex-wrap: wrap;
  gap: $spacing-sm;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: $color-primary;
  margin: 0;
}

.filters {
  display: flex;
  gap: $spacing-xs;
  overflow-x: auto;
  white-space: nowrap;
  -webkit-overflow-scrolling: touch;
}

.filter-btn {
  background: none;
  border: none;
  padding: 0.4rem 1rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
  color: $color-neutral-dark;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s;
  &.active {
    background-color: $color-white;
    color: $color-primary;
    font-weight: 500;
    box-shadow: 0 1px 2px rgba(0,0,0,0.06);
  }
  &:hover:not(.active) {
    background-color: rgba(0,0,0,0.03);
  }
}

.state-message {
  color: $color-neutral-dark;
  text-align: center;
  padding: $spacing-xl;
}

/* Desktop only */
.desktop-only {
  display: block;
}
@media (max-width: $breakpoint-tablet) {
  .desktop-only {
    display: none !important;
  }
}

/* Mobile only */
.mobile-only {
  display: none;
}
@media (max-width: $breakpoint-tablet) {
  .mobile-only {
    display: block;
  }
}

// Tableau
.table-wrapper {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  overflow-x: auto;
}

.tickets-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.9rem;

  th, td {
    padding: 0.75rem 1rem;
    text-align: left;
    border-bottom: 1px solid $color-border;
    white-space: nowrap;
  }

  th {
    font-weight: 600;
    color: $color-primary;
    background-color: $color-neutral-light;
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }

  .action-col {
    text-align: right;
    width: 160px;
  }
}

.ticket-row {
  cursor: pointer;
  transition: background-color 0.1s;
  &:hover {
    background-color: $color-neutral-light;
  }
  &:last-child td {
    border-bottom: none;
  }
}

.col-id { width: 60px; color: $color-neutral-dark; font-size: 0.85rem; }
.col-site { max-width: 150px; overflow: hidden; text-overflow: ellipsis; }
.col-titre { max-width: 220px; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.col-action { text-align: right; }

// Badges
.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
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

.badge-priorite-basse {
  background-color: $badge-priorite-basse-bg;
  color: $badge-priorite-basse-text;
}
.badge-priorite-normale {
  background-color: $badge-priorite-normale-bg;
  color: $badge-priorite-normale-text;
}
.badge-priorite-urgente {
  background-color: $badge-priorite-urgente-bg;
  color: $badge-priorite-urgente-text;
}

// Boutons
.btn {
  padding: 0.4rem 0.9rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.85rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s;
  background: none;
  color: $color-neutral-dark;
}

.btn-take {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}

// Mobile cards
.mobile-cards {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.ticket-card {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-md;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
  cursor: pointer;
}

.card-top {
  display: flex;
  align-items: baseline;
  gap: $spacing-sm;
  margin-bottom: $spacing-sm;
}

.card-id {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  font-weight: 500;
}

.card-titre {
  font-weight: 600;
  color: $color-primary;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.card-badges {
  display: flex;
  flex-wrap: wrap;
  gap: $spacing-xs;
  margin-bottom: $spacing-sm;
  align-items: center;
}

.card-site {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  margin-left: auto;
}

.card-date {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  margin-bottom: $spacing-sm;
}

.card-action {
  margin-top: $spacing-sm;
  .btn {
    width: 100%;
  }
}
</style>