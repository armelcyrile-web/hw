<!-- src/views/admin/SitesView.vue -->
<script setup>
import { ref, onMounted, reactive } from 'vue'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'

// État
const sites = ref([])
const loading = ref(true)
const showModal = ref(false)
const isEditing = ref(false)
const editingSiteId = ref(null)

const clients = ref([])

const form = reactive({
  nom: '',
  url: '',
  client_id: ''
})

// Récupération des sites
async function fetchSites() {
  loading.value = true
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement des sites.')
  } finally {
    loading.value = false
  }
}

// Récupération des clients via l'endpoint admin
async function fetchClients() {
  try {
    const response = await api.get('/admin/users', { params: { role: 'client' } })
    clients.value = response.data.data
  } catch (error) {
    notifyError('Impossible de charger la liste des clients.')
  }
}

// Ouvrir la modale en mode création
function openCreateModal() {
  isEditing.value = false
  editingSiteId.value = null
  form.nom = ''
  form.url = ''
  form.client_id = clients.value.length === 1 ? clients.value[0].id : '' // présélection s'il n'y en a qu'un
  showModal.value = true
}

// Ouvrir la modale en mode édition
function openEditModal(site) {
  isEditing.value = true
  editingSiteId.value = site.id
  form.nom = site.nom
  form.url = site.url
  form.client_id = site.client?.id ?? ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

// Soumettre le formulaire (création ou mise à jour)
async function submitForm() {
  if (!form.nom || !form.url || !form.client_id) {
    notifyError('Veuillez remplir tous les champs.')
    return
  }

  const confirmed = await confirmAction({
    titre: isEditing.value ? 'Modifier le site' : 'Ajouter un site',
    texte: isEditing.value ? 'Confirmer la modification de ce site ?' : 'Confirmer l\'ajout de ce site ?',
    texteConfirmation: 'Confirmer'
  })
  if (!confirmed) return

  try {
    if (isEditing.value) {
      await api.put(`/sites/${editingSiteId.value}`, form)
      notifySuccess('Site modifié avec succès.')
    } else {
      await api.post('/sites', form)
      notifySuccess('Site ajouté avec succès.')
    }
    closeModal()
    await fetchSites()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    notifyError(message)
  }
}

// Supprimer un site
async function deleteSite(site) {
  const confirmed = await confirmAction({
    titre: 'Supprimer le site',
    texte: `Êtes-vous sûr de vouloir supprimer définitivement le site "${site.nom}" ? Cette action est irréversible.`,
    texteConfirmation: 'Supprimer'
  })
  if (!confirmed) return

  try {
    await api.delete(`/sites/${site.id}`)
    notifySuccess('Site supprimé.')
    await fetchSites()
  } catch (error) {
    notifyError(error.response?.data?.message || 'Erreur lors de la suppression.')
  }
}

// Classes de badge
function disponibiliteClass(statut) {
  switch (statut) {
    case 'en_ligne': return 'badge-success'
    case 'hors_ligne': return 'badge-danger'
    default: return 'badge-neutral'
  }
}

function formaterDate(dateString) {
  if (!dateString) return 'Jamais'
  return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(async () => {
  await Promise.all([fetchSites(), fetchClients()])
})
</script>

<template>
  <div class="sites-view">
    <div class="top-bar">
      <h2 class="page-title">Gestion des sites</h2>
      <button class="btn btn-primary" @click="openCreateModal">+ Ajouter un site</button>
    </div>

    <div v-if="loading" class="state-message">Chargement...</div>
    <template v-else>
      <div v-if="sites.length === 0" class="state-message">Aucun site enregistré.</div>

      <!-- Tableau desktop -->
      <div v-else class="table-wrapper desktop-only">
        <table class="sites-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>URL</th>
              <th>Statut</th>
              <th>Client</th>
              <th>Dernière vérification</th>
              <th class="actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="site in sites" :key="site.id" class="site-row">
              <td class="col-nom">{{ site.nom }}</td>
              <td class="col-url">{{ site.url }}</td>
              <td>
                <span class="badge" :class="disponibiliteClass(site.statut_disponibilite)">
                  {{ site.statut_disponibilite === 'en_ligne' ? 'En ligne' : site.statut_disponibilite === 'hors_ligne' ? 'Hors ligne' : 'Inconnu' }}
                </span>
              </td>
              <td>{{ site.client?.nom }} {{ site.client?.prenom }}</td>
              <td>{{ formaterDate(site.date_derniere_verification) }}</td>
              <td class="col-actions">
                <button class="btn btn-edit" @click="openEditModal(site)">Modifier</button>
                <button class="btn btn-delete" @click="deleteSite(site)">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Cartes mobile -->
      <div class="mobile-cards mobile-only">
        <div v-for="site in sites" :key="site.id" class="site-card">
          <div class="card-top">
            <span class="card-nom">{{ site.nom }}</span>
            <span class="badge" :class="disponibiliteClass(site.statut_disponibilite)">
              {{ site.statut_disponibilite === 'en_ligne' ? 'En ligne' : site.statut_disponibilite === 'hors_ligne' ? 'Hors ligne' : 'Inconnu' }}
            </span>
          </div>
          <div class="card-info">
            <div>{{ site.url }}</div>
            <div>Client : {{ site.client?.nom }} {{ site.client?.prenom }}</div>
            <div>Vérifié : {{ formaterDate(site.date_derniere_verification) }}</div>
          </div>
          <div class="card-actions">
            <button class="btn btn-edit" @click="openEditModal(site)">Modifier</button>
            <button class="btn btn-delete" @click="deleteSite(site)">Supprimer</button>
          </div>
        </div>
      </div>
    </template>

    <!-- Modale -->
    <Teleport to="body">
      <Transition name="modal">
        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>{{ isEditing ? 'Modifier le site' : 'Ajouter un site' }}</h3>
              <button class="close-btn" @click="closeModal">✕</button>
            </div>
            <form @submit.prevent="submitForm" class="modal-body">
              <div class="form-group">
                <label for="nom">Nom du site</label>
                <input id="nom" v-model="form.nom" type="text" required class="form-input" />
              </div>
              <div class="form-group">
                <label for="url">URL</label>
                <input id="url" v-model="form.url" type="url" required class="form-input" />
              </div>
              <div class="form-group">
                <label for="client_id">Client associé</label>
                <select id="client_id" v-model="form.client_id" required class="form-select">
                  <option value="" disabled>Sélectionner un client</option>
                  <option v-for="client in clients" :key="client.id" :value="client.id">
                    {{ client.nom }} {{ client.prenom }}
                  </option>
                </select>
                <p v-if="clients.length === 0 && !loading" class="hint">Aucun client trouvé.</p>
              </div>
              <div class="modal-actions">
                <button type="button" class="btn btn-cancel" @click="closeModal">Annuler</button>
                <button type="submit" class="btn btn-primary">
                  {{ isEditing ? 'Enregistrer' : 'Ajouter' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

$badge-success-bg: #dcfce7;
$badge-success-text: #166534;
$badge-danger-bg: #fee2e2;
$badge-danger-text: #991b1b;
$badge-neutral-bg: #f3f4f6;
$badge-neutral-text: #4b5563;

.sites-view {
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

.state-message {
  color: $color-neutral-dark;
  text-align: center;
  padding: $spacing-xl;
}

// Desktop only
.desktop-only {
  display: block;
}
@media (max-width: $breakpoint-tablet) {
  .desktop-only {
    display: none !important;
  }
}

// Mobile only
.mobile-only {
  display: none;
}
@media (max-width: $breakpoint-tablet) {
  .mobile-only {
    display: block;
  }
}

// Table
.table-wrapper {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  overflow-x: auto;
}

.sites-table {
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
  .actions-col {
    text-align: right;
    width: 160px;
  }
}

.site-row {
  &:hover {
    background-color: $color-neutral-light;
  }
  &:last-child td {
    border-bottom: none;
  }
}

.col-nom { font-weight: 500; color: $color-primary; }
.col-url { color: $color-neutral-dark; max-width: 200px; overflow: hidden; text-overflow: ellipsis; }
.col-actions {
  text-align: right;
  white-space: nowrap;
}

// Badges
.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 500;
}
.badge-success { background-color: $badge-success-bg; color: $badge-success-text; }
.badge-danger { background-color: $badge-danger-bg; color: $badge-danger-text; }
.badge-neutral { background-color: $badge-neutral-bg; color: $badge-neutral-text; }

// Buttons
.btn {
  padding: 0.4rem 0.9rem;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.85rem;
  border: 1px solid transparent;
  cursor: pointer;
  transition: background-color 0.15s;
  background: none;
  color: $color-neutral-dark;
}
.btn-primary {
  background-color: $color-accent;
  color: white;
  &:hover {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}
.btn-edit {
  border: 1px solid $color-border;
  &:hover {
    background-color: $color-neutral-light;
  }
}
.btn-delete {
  color: $color-danger;
  &:hover {
    background-color: $badge-danger-bg;
  }
}
.btn-cancel {
  border: 1px solid $color-border;
  &:hover {
    background-color: $color-neutral-light;
  }
}

// Mobile cards
.mobile-cards {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.site-card {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-md;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: $spacing-sm;
}
.card-nom {
  font-weight: 600;
  color: $color-primary;
}
.card-info {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  margin-bottom: $spacing-sm;
  > div {
    margin-bottom: 2px;
  }
}
.card-actions {
  display: flex;
  gap: $spacing-sm;
  justify-content: flex-end;
}

// Modale
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
  max-width: 500px;
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
  h3 {
    font-size: 1.1rem;
    margin: 0;
  }
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
  overflow-y: auto;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
  label {
    font-weight: 500;
    color: $color-primary;
    font-size: 0.9rem;
  }
}

.form-input, .form-select {
  padding: 0.6rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
}

.hint {
  font-size: 0.8rem;
  color: $color-neutral-dark;
  margin: 0;
}

.modal-actions {
  display: flex;
  justify-content: flex-end;
  gap: $spacing-sm;
  margin-top: $spacing-sm;
  @media (max-width: $breakpoint-mobile) {
    flex-direction: column;
    button {
      width: 100%;
    }
  }
}

// Transition modale
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