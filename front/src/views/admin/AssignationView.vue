<!-- src/views/admin/AssignationView.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()

const tickets = ref([])
const techniciens = ref([])
const loading = ref(true)
const showModal = ref(false)
const selectedTicket = ref(null)
const selectedTechnicienId = ref('')

async function fetchTickets() {
  try {
    const response = await api.get('/tickets', { params: { statut: 'nouveau' } })
    tickets.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement des tickets.')
  } finally {
    loading.value = false
  }
}

async function fetchTechniciens() {
  try {
    const response = await api.get('/admin/users', { params: { role: 'technicien' } })
    techniciens.value = response.data.data
  } catch (error) {
    // silencieux
  }
}

function openAssignModal(ticket) {
  selectedTicket.value = ticket
  selectedTechnicienId.value = ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
  selectedTicket.value = null
}

async function assignTicket() {
  if (!selectedTechnicienId.value) {
    notifyError('Veuillez sélectionner un technicien ou vous-même.')
    return
  }

  // Si l'admin choisit "Moi-même", utiliser la prise en charge directe
  if (selectedTechnicienId.value == authStore.user.id) {
    await selfAssign()
  } else {
    await assignToTechnicien()
  }
}

async function selfAssign() {
  const confirmed = await confirmAction({
    titre: 'Prendre en charge',
    texte: 'Voulez-vous prendre en charge ce ticket vous-même ?',
    texteConfirmation: 'Prendre en charge'
  })
  if (!confirmed) return

  try {
    await api.post(`/staff/tickets/${selectedTicket.value.id}/prendre-en-charge`)
    notifySuccess('Ticket pris en charge.')
    closeModal()
    await fetchTickets()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de la prise en charge.'
    notifyError(message)
  }
}

async function assignToTechnicien() {
  const technicien = techniciens.value.find(t => t.id == selectedTechnicienId.value)
  const technicienNom = technicien ? `${technicien.prenom} ${technicien.nom}` : 'sélectionné'

  const confirmed = await confirmAction({
    titre: 'Assigner le ticket',
    texte: `Confirmer l'assignation de ce ticket à ${technicienNom} ?`,
    texteConfirmation: 'Assigner'
  })
  if (!confirmed) return

  try {
    await api.post(`/admin/tickets/${selectedTicket.value.id}/assigner`, {
      technicien_id: selectedTechnicienId.value
    })
    notifySuccess('Ticket assigné avec succès.')
    closeModal()
    await fetchTickets()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de l\'assignation.'
    notifyError(message)
  }
}

function tempsAttente(dateCreation) {
  const creation = new Date(dateCreation)
  const maintenant = new Date()
  const diffMs = maintenant - creation
  const diffMinutes = Math.floor(diffMs / 60000)
  if (diffMinutes < 60) return `${diffMinutes} min`
  const diffHeures = Math.floor(diffMinutes / 60)
  if (diffHeures < 24) return `${diffHeures}h ${diffMinutes % 60}min`
  const diffJours = Math.floor(diffHeures / 24)
  return `${diffJours} jour(s)`
}

function prioriteClass(priorite) {
  switch (priorite) {
    case 'basse': return 'badge-basse'
    case 'normale': return 'badge-normale'
    case 'urgente': return 'badge-urgente'
    default: return ''
  }
}

function technicienLabel(tech) {
  return `${tech.prenom} ${tech.nom}`
}

onMounted(async () => {
  loading.value = true
  await Promise.all([fetchTickets(), fetchTechniciens()])
})
</script>

<template>
  <div class="assignation-view">
    <h2 class="page-title">Assignation manuelle</h2>

    <div v-if="loading" class="state-message">Chargement...</div>
    <template v-else>
      <div v-if="tickets.length === 0" class="state-message positive">
        Aucun ticket en attente d'assignation. Tout est sous contrôle.
      </div>

      <div v-else class="table-wrapper desktop-only">
        <table class="tickets-table">
          <thead>
            <tr>
              <th>#</th>
              <th>Site</th>
              <th>Titre</th>
              <th>Priorité</th>
              <th>Attente</th>
              <th class="action-col">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="ticket in tickets" :key="ticket.id" class="ticket-row">
              <td class="col-id">#{{ ticket.id }}</td>
              <td>{{ ticket.site?.nom || '—' }}</td>
              <td class="col-titre">{{ ticket.titre }}</td>
              <td>
                <span class="badge" :class="prioriteClass(ticket.priorite)">{{ ticket.priorite }}</span>
              </td>
              <td>{{ tempsAttente(ticket.date_creation) }}</td>
              <td class="col-action">
                <button class="btn btn-assign" @click="openAssignModal(ticket)">Assigner</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="mobile-cards mobile-only">
        <div v-for="ticket in tickets" :key="ticket.id" class="ticket-card">
          <div class="card-top">
            <span class="card-id">#{{ ticket.id }}</span>
            <span class="badge" :class="prioriteClass(ticket.priorite)">{{ ticket.priorite }}</span>
          </div>
          <div class="card-info">
            <div class="card-site">{{ ticket.site?.nom }}</div>
            <div class="card-title">{{ ticket.titre }}</div>
            <div class="card-attente">En attente : {{ tempsAttente(ticket.date_creation) }}</div>
          </div>
          <button class="btn btn-assign" @click="openAssignModal(ticket)">Assigner</button>
        </div>
      </div>
    </template>

    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Assigner le ticket #{{ selectedTicket?.id }}</h3>
              <button class="close-btn" @click="closeModal">✕</button>
            </div>
            <div class="modal-body">
              <div class="ticket-resume">
                <p><strong>{{ selectedTicket?.titre }}</strong></p>
                <p>{{ selectedTicket?.site?.nom }} ({{ selectedTicket?.site?.url }})</p>
                <p class="priorite">Priorité : <span class="badge" :class="prioriteClass(selectedTicket?.priorite)">{{ selectedTicket?.priorite }}</span></p>
                <p class="description">{{ selectedTicket?.description }}</p>
              </div>

              <div class="form-group">
                <label for="technicien">Assigner à</label>
                <select v-model="selectedTechnicienId" id="technicien" class="form-select" required>
                  <option value="" disabled>Sélectionner un technicien</option>
                  <option :value="authStore.user.id">Moi-même (Administrateur)</option>
                  <option v-for="tech in techniciens" :key="tech.id" :value="tech.id">
                    {{ technicienLabel(tech) }}
                  </option>
                </select>
              </div>

              <div class="modal-actions">
                <button type="button" class="btn btn-cancel" @click="closeModal">Annuler</button>
                <button type="button" class="btn btn-primary" @click="assignTicket">Confirmer l'assignation</button>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

$badge-basse-bg: #f3f4f6;
$badge-basse-text: #4b5563;
$badge-normale-bg: #e0f2fe;
$badge-normale-text: #075985;
$badge-urgente-bg: #fee2e2;
$badge-urgente-text: #991b1b;

.assignation-view {
  background-color: $color-neutral-light;
  padding: $spacing-lg $spacing-md;
  min-height: 100%;
  max-width: 1200px;
  margin: 0 auto;
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
  &.positive {
    color: #166534;
  }
}

.desktop-only {
  display: block;
}
@media (max-width: $breakpoint-tablet) {
  .desktop-only {
    display: none !important;
  }
}

.mobile-only {
  display: none;
}
@media (max-width: $breakpoint-tablet) {
  .mobile-only {
    display: block;
  }
}

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
    width: 100px;
  }
}

.ticket-row {
  &:hover {
    background-color: $color-neutral-light;
  }
  &:last-child td {
    border-bottom: none;
  }
}

.col-id { color: $color-neutral-dark; }
.col-titre { max-width: 200px; overflow: hidden; text-overflow: ellipsis; font-weight: 500; }
.col-action { text-align: right; }

.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: capitalize;
}

.badge-basse { background-color: $badge-basse-bg; color: $badge-basse-text; }
.badge-normale { background-color: $badge-normale-bg; color: $badge-normale-text; }
.badge-urgente { background-color: $badge-urgente-bg; color: $badge-urgente-text; }

.btn {
  padding: 0.35rem 0.9rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.85rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s;
  background: none;
  color: $color-neutral-dark;
}
.btn-assign {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}
.btn-primary {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}
.btn-cancel {
  border: 1px solid $color-border;
  &:hover {
    background-color: $color-neutral-light;
  }
}

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
  .btn-assign {
    width: 100%;
    margin-top: $spacing-sm;
  }
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-sm;
}

.card-id { font-weight: 600; color: $color-primary; }
.card-info > div { margin-bottom: 0.25rem; font-size: 0.85rem; }
.card-site { font-weight: 500; }
.card-title { color: $color-neutral-dark; }
.card-attente { color: $color-neutral-dark; font-size: 0.8rem; }

.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0,0,0,0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  padding: $spacing-md;
}

.modal-content {
  background: $color-white;
  border-radius: $border-radius;
  box-shadow: 0 4px 12px rgba(0,0,0,0.12);
  width: 100%;
  max-width: 550px;
  max-height: 90vh;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: $spacing-md $spacing-lg;
  border-bottom: 1px solid $color-border;
  h3 { font-size: 1.1rem; margin: 0; }
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  cursor: pointer;
  color: $color-neutral-dark;
}

.modal-body {
  padding: $spacing-lg;
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.ticket-resume {
  p { margin-bottom: 0.5rem; font-size: 0.9rem; }
  .priorite { display: flex; align-items: center; gap: 0.5rem; }
  .description { white-space: pre-wrap; color: $color-neutral-dark; }
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
  label { font-weight: 500; color: $color-primary; font-size: 0.9rem; }
}

.form-select {
  padding: 0.6rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
  margin-top: $spacing-sm;
  @media (max-width: $breakpoint-mobile) {
    flex-direction: column;
    button { width: 100%; }
  }
}

.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease;
  .modal-content {
    transition: transform 0.2s ease;
  }
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
  .modal-content {
    transform: scale(0.95);
  }
}
</style>