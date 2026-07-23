<!-- src/views/admin/ComptesView.vue -->
<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'

// État des données
const users = ref([])
const loading = ref(true)

// Filtres
const filtreRole = ref('tous')
const texteRecherche = ref('')
let debounceTimer = null

// Modale
const showModal = ref(false)
const isEditing = ref(false)
const editingUserId = ref(null)

const form = reactive({
  nom: '',
  prenom: '',
  email: '',
  password: '',
  role: 'client',
  telephone: '',
  specialite: ''
})

// Charger les utilisateurs
async function fetchUsers() {
  loading.value = true
  try {
    const params = {}
    if (filtreRole.value !== 'tous') {
      params.role = filtreRole.value
    }
    if (texteRecherche.value) {
      params.search = texteRecherche.value
    }
    const response = await api.get('/admin/users', { params })
    users.value = response.data.data
  } catch (error) {
    notifyError('Erreur lors du chargement des utilisateurs.')
  } finally {
    loading.value = false
  }
}

// Debounce sur la recherche
watch(texteRecherche, () => {
  if (debounceTimer) clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    fetchUsers()
  }, 300)
})

// Changement de filtre rôle → rechargement immédiat
watch(filtreRole, () => {
  fetchUsers()
})

// Ouvrir modale en création
function openCreateModal() {
  isEditing.value = false
  editingUserId.value = null
  form.nom = ''
  form.prenom = ''
  form.email = ''
  form.password = ''
  form.role = 'client'
  form.telephone = ''
  form.specialite = ''
  showModal.value = true
}

// Ouvrir modale en édition
function openEditModal(user) {
  isEditing.value = true
  editingUserId.value = user.id
  form.nom = user.nom
  form.prenom = user.prenom
  form.email = user.email
  form.password = '' // toujours vide, optionnel
  form.role = user.role
  form.telephone = user.telephone || ''
  form.specialite = user.specialite || ''
  showModal.value = true
}

function closeModal() {
  showModal.value = false
}

// Soumettre le formulaire (création ou mise à jour)
async function submitForm() {
  if (!form.nom || !form.prenom || !form.email || !form.role) {
    notifyError('Veuillez remplir les champs obligatoires.')
    return
  }
  if (!isEditing.value && !form.password) {
    notifyError('Le mot de passe est requis pour la création.')
    return
  }

  const confirmed = await confirmAction({
    titre: isEditing.value ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur',
    texte: isEditing.value ? 'Confirmer la modification ?' : 'Confirmer la création du compte ?',
    texteConfirmation: 'Confirmer'
  })
  if (!confirmed) return

  const payload = {
    nom: form.nom,
    prenom: form.prenom,
    email: form.email,
    role: form.role,
    ...(form.password ? { password: form.password } : {}),
    telephone: form.role === 'client' ? form.telephone : null,
    specialite: form.role === 'technicien' ? form.specialite : null
  }

  try {
    if (isEditing.value) {
      await api.put(`/admin/users/${editingUserId.value}`, payload)
      notifySuccess('Utilisateur modifié avec succès.')
    } else {
      await api.post('/admin/users', payload)
      notifySuccess('Utilisateur créé avec succès.')
    }
    closeModal()
    await fetchUsers()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de l\'enregistrement.'
    notifyError(message)
  }
}

// Supprimer un utilisateur
async function deleteUser(user) {
  const confirmed = await confirmAction({
    titre: 'Supprimer l\'utilisateur',
    texte: `Êtes-vous sûr de vouloir supprimer définitivement le compte de ${user.nom} ${user.prenom} ? Cette action est irréversible.`,
    texteConfirmation: 'Supprimer'
  })
  if (!confirmed) return

  try {
    await api.delete(`/admin/users/${user.id}`)
    notifySuccess('Utilisateur supprimé.')
    await fetchUsers()
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de la suppression.'
    notifyError(message)
  }
}

// Badge de rôle
function roleBadgeClass(role) {
  switch (role) {
    case 'administrateur': return 'badge-admin'
    case 'technicien': return 'badge-tech'
    case 'client': return 'badge-client'
    default: return ''
  }
}

function roleLabel(role) {
  switch (role) {
    case 'administrateur': return 'Admin'
    case 'technicien': return 'Technicien'
    case 'client': return 'Client'
    default: return role
  }
}

function formatDate(dateString) {
  if (!dateString) return ''
  return new Date(dateString).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' })
}

onMounted(fetchUsers)
</script>

<template>
  <div class="comptes-view">
    <div class="top-bar">
      <h2 class="page-title">Gestion des comptes</h2>
      <button class="btn btn-primary" @click="openCreateModal">+ Ajouter un utilisateur</button>
    </div>

    <!-- Filtres -->
    <div class="filters-bar">
      <input
        v-model="texteRecherche"
        type="search"
        placeholder="Rechercher par nom, prénom, email..."
        class="search-input"
      />
      <select v-model="filtreRole" class="filter-select">
        <option value="tous">Tous les rôles</option>
        <option value="client">Client</option>
        <option value="technicien">Technicien</option>
        <option value="administrateur">Administrateur</option>
      </select>
    </div>

    <!-- Contenu -->
    <div v-if="loading" class="state-message">Chargement...</div>
    <template v-else>
      <div v-if="users.length === 0" class="state-message">Aucun utilisateur trouvé.</div>

      <!-- Desktop table -->
      <div v-else class="table-wrapper desktop-only">
        <table class="users-table">
          <thead>
            <tr>
              <th>Nom</th>
              <th>Email</th>
              <th>Rôle</th>
              <th>Statut</th>
              <th>Création</th>
              <th class="actions-col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id" class="user-row">
              <td class="col-nom">{{ user.nom }} {{ user.prenom }}</td>
              <td>{{ user.email }}</td>
              <td>
                <span class="badge" :class="roleBadgeClass(user.role)">
                  {{ roleLabel(user.role) }}
                </span>
              </td>
              <td>
                <span class="badge badge-actif">Actif</span>
                <!-- TODO backend: ajouter colonne is_active si la fonctionnalité de désactivation de compte est requise plus tard -->
              </td>
              <td>{{ formatDate(user.created_at) }}</td>
              <td class="col-actions">
                <button class="btn btn-edit" @click="openEditModal(user)">Modifier</button>
                <button class="btn btn-delete" @click="deleteUser(user)">Supprimer</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Mobile cards -->
      <div class="mobile-cards mobile-only">
        <div v-for="user in users" :key="user.id" class="user-card">
          <div class="card-top">
            <span class="card-name">{{ user.nom }} {{ user.prenom }}</span>
            <span class="badge" :class="roleBadgeClass(user.role)">{{ roleLabel(user.role) }}</span>
          </div>
          <div class="card-info">
            <div>{{ user.email }}</div>
            <div>
              Statut : <span class="badge badge-actif">Actif</span>
            </div>
            <div>Créé le {{ formatDate(user.created_at) }}</div>
          </div>
          <div class="card-actions">
            <button class="btn btn-edit" @click="openEditModal(user)">Modifier</button>
            <button class="btn btn-delete" @click="deleteUser(user)">Supprimer</button>
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
              <h3>{{ isEditing ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' }}</h3>
              <button class="close-btn" @click="closeModal">✕</button>
            </div>
            <form @submit.prevent="submitForm" class="modal-body">
              <div class="form-group">
                <label for="nom">Nom</label>
                <input id="nom" v-model="form.nom" type="text" required class="form-input" />
              </div>
              <div class="form-group">
                <label for="prenom">Prénom</label>
                <input id="prenom" v-model="form.prenom" type="text" required class="form-input" />
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input id="email" v-model="form.email" type="email" required class="form-input" />
              </div>
              <div class="form-group">
                <label for="password">
                  Mot de passe
                  <span v-if="isEditing" class="optional">(laisser vide pour ne pas changer)</span>
                </label>
                <input
                  id="password"
                  v-model="form.password"
                  type="password"
                  :required="!isEditing"
                  class="form-input"
                  :placeholder="isEditing ? 'Laisser vide pour ne pas changer' : ''"
                />
              </div>
              <div class="form-group">
                <label for="role">Rôle</label>
                <select id="role" v-model="form.role" required class="form-select">
                  <option value="client">Client</option>
                  <option value="technicien">Technicien</option>
                  <option value="administrateur">Administrateur</option>
                </select>
              </div>
              <div v-if="form.role === 'client'" class="form-group">
                <label for="telephone">Téléphone</label>
                <input id="telephone" v-model="form.telephone" type="text" class="form-input" />
              </div>
              <div v-if="form.role === 'technicien'" class="form-group">
                <label for="specialite">Spécialité</label>
                <input id="specialite" v-model="form.specialite" type="text" class="form-input" />
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

// Rôle badge colors
$badge-admin-bg: #e0e7ff; // indigo-100
$badge-admin-text: #3730a3; // indigo-800
$badge-tech-bg: #dbeafe; // blue-100
$badge-tech-text: #1e40af; // blue-800
$badge-client-bg: #f3f4f6; // gray-100
$badge-client-text: #4b5563; // gray-600
$badge-actif-bg: #dcfce7; // green-100
$badge-actif-text: #166534; // green-800

.comptes-view {
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
  margin-bottom: $spacing-md;
  flex-wrap: wrap;
  gap: $spacing-sm;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: $color-primary;
  margin: 0;
}

.filters-bar {
  display: flex;
  gap: $spacing-sm;
  margin-bottom: $spacing-md;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  min-width: 200px;
  padding: 0.5rem 0.75rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
  outline: none;
  &:focus {
    border-color: $color-accent;
  }
}

.filter-select {
  padding: 0.5rem 0.75rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.9rem;
  background: $color-white;
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

.users-table {
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

.user-row {
  &:hover {
    background-color: $color-neutral-light;
  }
  &:last-child td {
    border-bottom: none;
  }
}

.col-nom {
  font-weight: 500;
  color: $color-primary;
}

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

.badge-admin {
  background-color: $badge-admin-bg;
  color: $badge-admin-text;
}

.badge-tech {
  background-color: $badge-tech-bg;
  color: $badge-tech-text;
}

.badge-client {
  background-color: $badge-client-bg;
  color: $badge-client-text;
}

.badge-actif {
  background-color: $badge-actif-bg;
  color: $badge-actif-text;
}

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
    background-color: #fee2e2;
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

.user-card {
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

.card-name {
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
  .optional {
    font-weight: 400;
    font-size: 0.8rem;
    color: $color-neutral-dark;
  }
}

.form-input, .form-select {
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