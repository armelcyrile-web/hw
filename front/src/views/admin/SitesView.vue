<!-- src/views/admin/StatsView.vue -->
<script setup>
import { ref, onMounted, computed } from 'vue'
import api from '@/services/api'
import { notifyError } from '@/services/alert'
import { Line, Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler // ajouté pour corriger l'erreur
} from 'chart.js'

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  BarElement,
  Title,
  Tooltip,
  Legend,
  Filler // enregistrement du plugin
)

const stats = ref(null)
const loading = ref(true)

async function fetchStats() {
  loading.value = true
  try {
    const response = await api.get('/admin/stats')
    stats.value = response.data
  } catch (error) {
    notifyError('Impossible de charger les statistiques.')
  } finally {
    loading.value = false
  }
}

// Cartes de synthèse
const resume = computed(() => stats.value?.resume || {})

// Formater le temps moyen
function formatTemps(minutes) {
  if (!minutes) return '0 min'
  if (minutes >= 60) {
    const h = Math.floor(minutes / 60)
    const m = minutes % 60
    return `${h}h ${m > 0 ? m + 'min' : ''}`
  }
  return `${minutes} min`
}

// Graphique d'évolution
const evolutionData = computed(() => {
  if (!stats.value?.evolution_tickets) return null
  const dates = stats.value.evolution_tickets.map(item => {
    const d = new Date(item.date)
    return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })
  })
  const totals = stats.value.evolution_tickets.map(item => item.total)
  return {
    labels: dates,
    datasets: [
      {
        label: 'Tickets créés',
        data: totals,
        borderColor: '#2563eb',
        backgroundColor: 'rgba(37, 99, 235, 0.05)',
        pointBackgroundColor: '#2563eb',
        pointRadius: 2,
        pointHoverRadius: 4,
        borderWidth: 2,
        tension: 0.3,
        fill: true // maintenant supporté grâce à Filler
      }
    ]
  }
})

const evolutionOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    }
  },
  scales: {
    x: {
      ticks: {
        maxTicksLimit: 10,
        font: { size: 10 }
      },
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
        font: { size: 10 }
      }
    }
  }
}))

// Graphique de charge par technicien
const chargeData = computed(() => {
  if (!stats.value?.charge_par_technicien?.length) return null
  const labels = stats.value.charge_par_technicien.map(t => `${t.prenom} ${t.nom}`)
  const assignes = stats.value.charge_par_technicien.map(t => t.tickets_assignes)
  const resolus = stats.value.charge_par_technicien.map(t => t.tickets_resolus)
  return {
    labels,
    datasets: [
      {
        label: 'Tickets assignés',
        data: assignes,
        backgroundColor: 'rgba(37, 99, 235, 0.7)',
        borderRadius: 4
      },
      {
        label: 'Tickets résolus',
        data: resolus,
        backgroundColor: 'rgba(16, 185, 129, 0.7)',
        borderRadius: 4
      }
    ]
  }
})

const chargeOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        boxWidth: 12,
        padding: 15,
        font: { size: 11 }
      }
    }
  },
  scales: {
    x: {
      ticks: {
        font: { size: 10 }
      },
      grid: {
        display: false
      }
    },
    y: {
      beginAtZero: true,
      ticks: {
        precision: 0,
        font: { size: 10 }
      }
    }
  }
}))

// Répartition par priorité et statut
const repartitionPriorite = computed(() => {
  if (!stats.value?.repartition_par_priorite) return {}
  return stats.value.repartition_par_priorite
})

const repartitionStatut = computed(() => {
  if (!stats.value?.repartition_par_statut) return {}
  return stats.value.repartition_par_statut
})

function prioriteBadgeClass(priorite) {
  switch (priorite) {
    case 'basse': return 'badge-basse'
    case 'normale': return 'badge-normale'
    case 'urgente': return 'badge-urgente'
    default: return ''
  }
}

function statutBadgeClass(statut) {
  switch (statut) {
    case 'nouveau': return 'badge-nouveau'
    case 'assigne': return 'badge-assigne'
    case 'resolu': return 'badge-resolu'
    default: return ''
  }
}

onMounted(fetchStats)
</script>

<template>
  <div class="stats-view">
    <h2 class="page-title">Statistiques</h2>

    <div v-if="loading" class="state-message">Chargement des statistiques...</div>

    <template v-else-if="stats">
      <!-- Cartes de synthèse -->
      <div class="cards-grid">
        <div class="card">
          <span class="card-value">{{ resume.total_tickets }}</span>
          <span class="card-label">Total tickets</span>
        </div>
        <div class="card">
          <span class="card-value">{{ resume.tickets_resolus_ce_mois }}</span>
          <span class="card-label">Résolus ce mois</span>
        </div>
        <div class="card">
          <span class="card-value">{{ formatTemps(resume.temps_moyen_resolution_minutes) }}</span>
          <span class="card-label">Temps moyen résolution</span>
        </div>
        <div class="card">
          <span class="card-value">{{ resume.taux_disponibilite_global }}%</span>
          <span class="card-label">Disponibilité globale</span>
        </div>
      </div>

      <!-- Graphiques -->
      <div class="charts-section">
        <div class="chart-container">
          <h3>Évolution des tickets (30 jours)</h3>
          <div class="chart-wrapper">
            <Line v-if="evolutionData" :data="evolutionData" :options="evolutionOptions" />
          </div>
        </div>

        <div class="chart-container">
          <h3>Charge par technicien</h3>
          <div class="chart-wrapper">
            <Bar v-if="chargeData" :data="chargeData" :options="chargeOptions" />
            <p v-else class="empty-message">Aucun technicien enregistré.</p>
          </div>
        </div>
      </div>

      <!-- Répartition -->
      <div class="repartition-section">
        <div class="repartition-block">
          <h4>Répartition par priorité</h4>
          <ul class="badge-list">
            <li v-for="(count, priorite) in repartitionPriorite" :key="priorite">
              <span class="badge" :class="prioriteBadgeClass(priorite)">{{ priorite }}</span>
              <span class="count">{{ count }}</span>
            </li>
          </ul>
        </div>
        <div class="repartition-block">
          <h4>Répartition par statut</h4>
          <ul class="badge-list">
            <li v-for="(count, statut) in repartitionStatut" :key="statut">
              <span class="badge" :class="statutBadgeClass(statut)">{{ statut }}</span>
              <span class="count">{{ count }}</span>
            </li>
          </ul>
        </div>
      </div>
    </template>

    <div v-else class="state-message">Impossible de charger les statistiques.</div>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

// Badge colors (same as other views)
$badge-basse-bg: #f3f4f6;
$badge-basse-text: #4b5563;
$badge-normale-bg: #e0f2fe;
$badge-normale-text: #075985;
$badge-urgente-bg: #fee2e2;
$badge-urgente-text: #991b1b;
$badge-nouveau-bg: #dbeafe;
$badge-nouveau-text: #1e40af;
$badge-assigne-bg: #fef3c7;
$badge-assigne-text: #92400e;
$badge-resolu-bg: #dcfce7;
$badge-resolu-text: #166534;

.stats-view {
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
}

// Cartes
.cards-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: $spacing-md;
  margin-bottom: $spacing-xl;

  @media (max-width: $breakpoint-tablet) {
    grid-template-columns: repeat(2, 1fr);
  }
  @media (max-width: $breakpoint-mobile) {
    grid-template-columns: 1fr;
  }
}

.card {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-md;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.card-value {
  font-size: 1.8rem;
  font-weight: 600;
  color: $color-primary;
  line-height: 1.2;
}

.card-label {
  font-size: 0.85rem;
  color: $color-neutral-dark;
  margin-top: $spacing-xs;
}

// Graphiques
.charts-section {
  display: flex;
  flex-direction: column;
  gap: $spacing-lg;
  margin-bottom: $spacing-xl;
}

.chart-container {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-md;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);

  h3 {
    font-size: 1rem;
    font-weight: 600;
    color: $color-primary;
    margin-bottom: $spacing-sm;
  }
}

.chart-wrapper {
  height: 300px;
  @media (max-width: $breakpoint-mobile) {
    height: 250px;
  }
}

.empty-message {
  text-align: center;
  color: $color-neutral-dark;
  padding: $spacing-lg;
}

// Répartition
.repartition-section {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: $spacing-md;
  @media (max-width: $breakpoint-tablet) {
    grid-template-columns: 1fr;
  }
}

.repartition-block {
  background: $color-white;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  padding: $spacing-md;
  box-shadow: 0 1px 3px rgba(0,0,0,0.06);
}

.repartition-block h4 {
  font-size: 0.95rem;
  font-weight: 600;
  color: $color-primary;
  margin-bottom: $spacing-sm;
}

.badge-list {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.badge-list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.badge {
  display: inline-block;
  padding: 0.15rem 0.6rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 500;
  text-transform: capitalize;
}

.badge-basse { background-color: $badge-basse-bg; color: $badge-basse-text; }
.badge-normale { background-color: $badge-normale-bg; color: $badge-normale-text; }
.badge-urgente { background-color: $badge-urgente-bg; color: $badge-urgente-text; }
.badge-nouveau { background-color: $badge-nouveau-bg; color: $badge-nouveau-text; }
.badge-assigne { background-color: $badge-assigne-bg; color: $badge-assigne-text; }
.badge-resolu { background-color: $badge-resolu-bg; color: $badge-resolu-text; }

.count {
  font-weight: 600;
  font-size: 1rem;
  color: $color-primary;
}
</style>