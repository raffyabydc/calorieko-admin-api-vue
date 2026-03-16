<template>
  <div class="ck-card">
    <div class="chart-header">
      <h3 class="chart-title">User Overall Status</h3>
      <p class="chart-subtitle">Active vs Non-Active users and Consistency</p>
    </div>

    <div v-if="loading" style="text-align: center; padding: 2rem; color: var(--ck-gray-500);">
      Loading cohort data...
    </div>

    <template v-else>
      <div class="chart-container" style="height: 300px; position: relative;">
        <Doughnut :data="chartData" :options="chartOptions" />
      </div>

      <div class="grid-2 stats-footer" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <div class="stat-box" style="flex: 1;">
          <p class="stat-box__label">Total Active Users</p>
          <p class="stat-box__value" style="color: #10b981;">{{ activeCount }}</p>
          <p class="stat-box__unit">Logged >0 days</p>
        </div>
        <div class="stat-box" style="flex: 1;">
          <p class="stat-box__label">Avg. Consistency</p>
          <p class="stat-box__value" style="color: #3b82f6;">{{ avgConsistency }}</p>
          <p class="stat-box__unit">Day Streak</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Doughnut } from 'vue-chartjs'
import {
  Chart as ChartJS,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js'
import { getProfiles } from '../../services/api.js'

ChartJS.register(ArcElement, Tooltip, Legend)

const loading = ref(true)
const activeCount = ref(0)
const inactiveCount = ref(0)
const avgConsistency = ref(0)
const chartData = ref(null)

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        color: '#374151',
        font: { size: 13 },
        usePointStyle: true,
        padding: 20
      }
    }
  },
  cutout: '70%'
}

onMounted(async () => {
  try {
    const profiles = await getProfiles()
    
    // Calculate Active vs Inactive based on streak
    const active = profiles.filter(p => p.streak > 0)
    const inactive = profiles.filter(p => !p.streak || p.streak === 0)
    
    activeCount.value = active.length
    inactiveCount.value = inactive.length
    
    // Average consistency (streak)
    const totalStreak = profiles.reduce((sum, p) => sum + (p.streak || 0), 0)
    avgConsistency.value = profiles.length ? Math.round(totalStreak / profiles.length) : 0

    chartData.value = {
      labels: ['Active Users', 'Non-Active Users'],
      datasets: [
        {
          data: [activeCount.value, inactiveCount.value],
          backgroundColor: ['#10b981', '#ef4444'],
          hoverBackgroundColor: ['#059669', '#dc2626'],
          borderWidth: 0,
          borderRadius: 4
        }
      ]
    }
  } catch (err) {
    console.error('Failed to load chart data:', err)
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.chart-header { margin-bottom: 1.5rem; }
.chart-title { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-800); margin-bottom: 0.25rem; }
.chart-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }
.stat-box { background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200); border-radius: var(--ck-radius-lg); padding: 1rem; }
.stat-box__label { font-size: 0.75rem; color: var(--ck-gray-600); margin-bottom: 0.25rem; }
.stat-box__value { font-size: 1.5rem; font-weight: 600; }
.stat-box__unit { font-size: 0.75rem; color: var(--ck-gray-500); margin-top: 0.25rem; }
</style>
