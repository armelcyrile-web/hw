<!-- src/views/client/NewTicketView.vue -->
<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/services/api'
import { notifySuccess, notifyError, confirmAction } from '@/services/alert'

const route = useRoute()
const router = useRouter()

const sites = ref([])
const selectedSiteId = ref(route.query.site || '')
const titre = ref('')
const description = ref('')
const priorite = ref('normale')
const loading = ref(false)

async function fetchSites() {
  try {
    const response = await api.get('/sites')
    sites.value = response.data.data
    // Si le query param est présent mais que le site n'est pas encore chargé, il le sera
    if (route.query.site && !selectedSiteId.value) {
      selectedSiteId.value = route.query.site
    }
  } catch (error) {
    notifyError('Impossible de charger vos sites.')
  }
}

async function submitTicket() {
  if (!selectedSiteId.value || !titre.value || !description.value) {
    notifyError('Veuillez remplir tous les champs.')
    return
  }

  const confirmed = await confirmAction({
    titre: 'Confirmer l\'ouverture',
    texte: 'Voulez-vous vraiment créer ce ticket ?',
    texteConfirmation: 'Confirmer'
  })
  if (!confirmed) return

  loading.value = true
  try {
    await api.post('/tickets', {
      site_id: selectedSiteId.value,
      titre: titre.value,
      description: description.value,
      priorite: priorite.value
    })
    notifySuccess('Ticket créé avec succès.')
    router.push('/client/tickets')
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur lors de la création du ticket'
    notifyError(message)
  } finally {
    loading.value = false
  }
}

function annuler() {
  router.push('/client')
}

onMounted(fetchSites)
</script>

<template>
  <div class="new-ticket-page">
    <div class="form-card">
      <h2 class="form-title">Ouvrir un ticket</h2>
      <form @submit.prevent="submitTicket" class="ticket-form">
        <div class="form-group">
          <label for="site" class="form-label">Site concerné</label>
          <select
            id="site"
            v-model="selectedSiteId"
            class="form-select"
            required
            :disabled="loading"
          >
            <option value="" disabled>Sélectionnez un site</option>
            <option
              v-for="site in sites"
              :key="site.id"
              :value="site.id"
            >
              {{ site.nom }} ({{ site.url }})
            </option>
          </select>
        </div>

        <div class="form-group">
          <label for="titre" class="form-label">Titre du problème</label>
          <input
            id="titre"
            v-model="titre"
            type="text"
            required
            placeholder="Résumé du problème"
            class="form-input"
            :disabled="loading"
          />
        </div>

        <div class="form-group">
          <label for="description" class="form-label">Description détaillée</label>
          <textarea
            id="description"
            v-model="description"
            rows="4"
            required
            placeholder="Décrivez précisément le problème rencontré..."
            class="form-textarea"
            :disabled="loading"
          ></textarea>
        </div>

        <div class="form-group">
          <label for="priorite" class="form-label">Priorité</label>
          <select
            id="priorite"
            v-model="priorite"
            class="form-select"
            :disabled="loading"
          >
            <option value="basse">Basse</option>
            <option value="normale" selected>Normale</option>
            <option value="urgente">Urgente</option>
          </select>
        </div>

        <div class="form-actions">
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="loading"
          >
            {{ loading ? 'Envoi...' : 'Envoyer le ticket' }}
          </button>
          <button
            type="button"
            class="btn btn-secondary"
            @click="annuler"
            :disabled="loading"
          >
            Annuler
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

.new-ticket-page {
  padding: $spacing-lg $spacing-md;
  background-color: $color-neutral-light; // fond gris clair pour contraste
  min-height: 100%;
  display: flex;
  justify-content: center;
}

.form-card {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08);
  padding: $spacing-xl;
  width: 100%;
  max-width: 600px;
}

.form-title {
  font-size: 1.4rem;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-lg;
}

.ticket-form {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.form-label {
  font-size: 0.9rem;
  font-weight: 500;
  color: $color-primary;
}

.form-input,
.form-select,
.form-textarea {
  padding: 0.6rem 0.75rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.15s;
  &:focus {
    border-color: $color-accent;
  }
}

.form-textarea {
  resize: vertical;
}

.form-actions {
  display: flex;
  gap: $spacing-sm;
  margin-top: $spacing-sm;
}

.btn {
  padding: 0.65rem 1.25rem;
  border: none;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s, opacity 0.15s;
  &:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
}

.btn-primary {
  background-color: $color-accent;
  color: white;
  &:hover:not(:disabled) {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
}

.btn-secondary {
  background-color: transparent;
  color: $color-neutral-dark;
  border: 1px solid $color-border;
  &:hover:not(:disabled) {
    background-color: $color-neutral-light;
  }
}

@media (max-width: $breakpoint-mobile) {
  .new-ticket-page {
    padding: $spacing-md;
  }

  .form-card {
    padding: $spacing-md;
  }

  .form-actions {
    flex-direction: column;
    .btn {
      width: 100%;
    }
  }
}
</style>