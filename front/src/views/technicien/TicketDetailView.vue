<!-- src/views/technicien/TicketDetailView.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const ticket = ref(null)
const loading = ref(true)

async function fetchTicket() {
  loading.value = true
  try {
    const response = await api.get(`/tickets/${route.params.id}`)
    ticket.value = response.data.data
  } catch (error) {
    notifyError('Ticket introuvable.')
    router.push('/technicien')
  } finally {
    loading.value = false
  }
}

async function resoudre() {
  // Demander un commentaire de résolution
  const { value: commentaire } = await Swal.fire({
    title: 'Résoudre le ticket',
    input: 'text',
    inputLabel: 'Commentaire de résolution (optionnel)',
    inputPlaceholder: 'Décrivez la solution apportée...',
    showCancelButton: true,
    confirmButtonText: 'Continuer',
    cancelButtonText: 'Annuler',
    customClass: {
      confirmButton: 'swal2-confirm-btn',
      cancelButton: 'swal2-cancel-btn'
    }
  })
  if (commentaire === undefined) return // Annulé

  const confirmed = await confirmAction({
    titre: 'Confirmer la résolution',
    texte: 'Ce ticket sera marqué comme résolu.',
    texteConfirmation: 'Confirmer'
  })
  if (!confirmed) return

  try {
    await api.post(`/staff/tickets/${ticket.value.id}/resoudre`, { commentaire })
    notifySuccess('Ticket résolu avec succès.')
    router.push('/technicien')
  } catch (error) {
    notifyError(error.response?.data?.message || 'Erreur lors de la résolution.')
  }
}

async function liberer() {
  const confirmed = await confirmAction({
    titre: 'Libérer le ticket',
    texte: 'Ce ticket retournera dans le pool des tickets nouveaux.',
    texteConfirmation: 'Libérer'
  })
  if (!confirmed) return

  try {
    await api.post(`/staff/tickets/${ticket.value.id}/liberer`)
    notifySuccess('Ticket libéré avec succès.')
    router.push('/technicien')
  } catch (error) {
    notifyError(error.response?.data?.message || 'Erreur lors de la libération.')
  }
}

// Vérifier si l'utilisateur peut résoudre/libérer
function peutAgir() {
  if (!ticket.value) return false
  if (ticket.value.statut !== 'assigne') return false
  if (authStore.isAdmin) return true
  return ticket.value.technicien?.id === authStore.user?.id
}

function formaterDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

onMounted(fetchTicket)
</script>

<template>
  <div class="ticket-detail-view">
    <button class="btn-retour" @click="router.push('/technicien')">← Retour à la liste</button>

    <div v-if="loading" class="state-message">Chargement...</div>

    <div v-else-if="ticket" class="detail-card">
      <!-- En-tête -->
      <div class="detail-header">
        <h2 class="ticket-title">#{{ ticket.id }} — {{ ticket.titre }}</h2>
        <div class="header-badges">
          <span class="badge" :class="'badge-' + ticket.statut">
            {{ ticket.statut === 'nouveau' ? 'Nouveau' : ticket.statut === 'assigne' ? 'Assigné' : 'Résolu' }}
          </span>
          <span class="badge" :class="'badge-priorite-' + ticket.priorite">
            {{ ticket.priorite }}
          </span>
        </div>
      </div>

      <!-- Infos principales -->
      <div class="info-grid">
        <div class="info-item">
          <span class="info-label">Site</span>
          <span class="info-value">{{ ticket.site?.nom }} ({{ ticket.site?.url }})</span>
        </div>
        <div class="info-item">
          <span class="info-label">Priorité</span>
          <span class="info-value">{{ ticket.priorite }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Origine</span>
          <span class="info-value">{{ ticket.origine === 'automatique' ? 'Automatique' : 'Manuel' }}</span>
        </div>
        <div class="info-item" v-if="ticket.technicien">
          <span class="info-label">Assigné à</span>
          <span class="info-value">{{ ticket.technicien.nom }} {{ ticket.technicien.prenom }}</span>
        </div>
        <div class="info-item">
          <span class="info-label">Ouvert le</span>
          <span class="info-value">{{ formaterDate(ticket.date_creation) }}</span>
        </div>
        <div class="info-item" v-if="ticket.date_resolution">
          <span class="info-label">Résolu le</span>
          <span class="info-value">{{ formaterDate(ticket.date_resolution) }}</span>
        </div>
      </div>

      <!-- Description -->
      <div class="description-section">
        <h3>Description</h3>
        <p class="description-text">{{ ticket.description }}</p>
      </div>

      <!-- Historique -->
      <div v-if="ticket.historique?.length" class="history-section">
        <h3>Historique d'intervention</h3>
        <ul class="history-list">
          <li v-for="h in ticket.historique" :key="h.id" class="history-item">
            <div class="history-action">
              {{ h.type_action === 'prise_en_charge' ? 'Prise en charge' : h.type_action === 'resolution' ? 'Résolution' : 'Libération' }}
            </div>
            <div class="history-details">
              <span v-if="h.utilisateur">par {{ h.utilisateur.nom }} {{ h.utilisateur.prenom }}</span>
              <span class="history-date">le {{ formaterDate(h.date_action) }}</span>
              <span v-if="h.duree_intervention" class="history-duree"> — {{ h.duree_intervention }} min</span>
              <span v-if="h.commentaire" class="history-comment"> — {{ h.commentaire }}</span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Actions -->
      <div v-if="peutAgir()" class="actions">
        <button class="btn btn-resoudre" @click="resoudre">Résoudre le ticket</button>
        <button class="btn btn-liberer" @click="liberer">Libérer le ticket</button>
      </div>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

.ticket-detail-view {
  padding: $spacing-lg $spacing-md;
  max-width: 800px;
  margin: 0 auto;
  background-color: $color-neutral-light;
  min-height: 100%;
}

.btn-retour {
  background: none;
  border: none;
  color: $color-accent;
  cursor: pointer;
  font-size: 0.9rem;
  padding: 0;
  margin-bottom: $spacing-md;
  display: inline-flex;
  align-items: center;
  &:hover {
    text-decoration: underline;
  }
}

.state-message {
  text-align: center;
  padding: $spacing-xl;
  color: $color-neutral-dark;
}

.detail-card {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-lg;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}

.detail-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-lg;
  flex-wrap: wrap;
  gap: $spacing-sm;
}

.ticket-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: $color-primary;
  margin: 0;
}

.header-badges {
  display: flex;
  gap: $spacing-xs;
}

.badge {
  display: inline-block;
  padding: 0.2rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
}

.badge-nouveau { background-color: #dbeafe; color: #1e40af; }
.badge-assigne { background-color: #fef3c7; color: #92400e; }
.badge-resolu { background-color: #dcfce7; color: #166534; }
.badge-priorite-basse { background-color: #f3f4f6; color: #4b5563; }
.badge-priorite-normale { background-color: #e0f2fe; color: #075985; }
.badge-priorite-urgente { background-color: #fee2e2; color: #991b1b; }

.info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
  margin-bottom: $spacing-lg;
  @media (max-width: $breakpoint-mobile) {
    grid-template-columns: 1fr;
  }
}

.info-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.info-label {
  font-size: 0.8rem;
  font-weight: 500;
  color: $color-neutral-dark;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}

.info-value {
  font-size: 0.95rem;
  color: $color-primary;
}

.description-section {
  margin-bottom: $spacing-lg;
  h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: $color-primary;
    margin-bottom: $spacing-sm;
  }
}

.description-text {
  font-size: 0.9rem;
  color: $color-neutral-dark;
  white-space: pre-wrap;
  line-height: 1.5;
}

.history-section {
  margin-bottom: $spacing-lg;
  h3 {
    font-size: 1.1rem;
    font-weight: 600;
    color: $color-primary;
    margin-bottom: $spacing-sm;
  }
}

.history-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.history-item {
  padding: 0.5rem 0;
  border-bottom: 1px solid $color-border;
  display: flex;
  flex-direction: column;
  gap: 2px;
  &:last-child {
    border-bottom: none;
  }
}

.history-action {
  font-weight: 600;
  font-size: 0.9rem;
  color: $color-primary;
}

.history-details {
  font-size: 0.8rem;
  color: $color-neutral-dark;
}

.history-date, .history-duree, .history-comment {
  color: $color-neutral-dark;
}

.actions {
  display: flex;
  gap: $spacing-sm;
  margin-top: $spacing-lg;
  @media (max-width: $breakpoint-mobile) {
    flex-direction: column;
  }
}

.btn {
  padding: 0.65rem 1.5rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s, opacity 0.15s;
  border: 1px solid transparent;
  @media (max-width: $breakpoint-mobile) {
    width: 100%;
  }
}

.btn-resoudre {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}

.btn-liberer {
  background-color: transparent;
  border: 1px solid $color-border;
  color: $color-neutral-dark;
  &:hover {
    background-color: $color-neutral-light;
  }
}
</style>