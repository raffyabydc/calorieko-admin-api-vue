<template>
  <div class="ck-card">
    <div class="chart-header">
      <h3 class="chart-title">Daily Steps Trends</h3>
      <p class="chart-subtitle">Average Daily Steps per User (Last 7 Days)</p>
    </div>

    <div v-if="loading" style="text-align: center; padding: 3rem 0; color: var(--ck-gray-500); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 250px;">
      <p style="font-size: 0.875rem;">Aggregating system step trends...</p>
    </div>

    <div v-else-if="chartData.labels.length === 0" style="text-align: center; padding: 3rem 0; color: var(--ck-gray-500); display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 250px; background: var(--ck-gray-50); border-radius: var(--ck-radius-lg); border: 1px dashed var(--ck-gray-300);">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem; color: var(--ck-gray-400);"><path d="M3 3v18h18"></path><path d="M18.7 8l-5.1 5.2-2.8-2.7L7 14.3"></path></svg>
      <p style="font-size: 0.875rem; font-weight: 500;">Insufficient data to generate trends.</p>
      <p style="font-size: 0.75rem; margin-top: 0.25rem;">Waiting for historical activity logs.</p>
    </div>

    <div v-else class="chart-container" style="flex: 1; min-height: 250px; position: relative;">
      <LineChart :data="chartData" :options="chartOptions" />
    </div>

    <div v-if="!loading && chartData.labels.length > 0" class="stats-footer grid-2" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
      <div class="stat-box" style="flex: 1;">
        <p class="stat-box__label">Weekly Avg Steps</p>
        <p class="stat-box__value" style="color: #f59e0b;">{{ Math.round(avgSteps).toLocaleString() }}</p>
        <p class="stat-box__unit">steps / day</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Line as LineChart } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  LineElement,
  PointElement,
  CategoryScale,
  LinearScale,
  Filler
} from 'chart.js'
import { getStepTrends } from '../../services/api.js'

ChartJS.register(CategoryScale, LinearScale, LineElement, PointElement, Title, Tooltip, Legend, Filler)

const props = defineProps({ refreshKey: { type: Number, default: 0 } })
const loading = ref(true)
const chartData = ref({ labels: [], datasets: [] })
const avgSteps = ref(0)

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        usePointStyle: true,
        padding: 20
      }
    },
    tooltip: {
      mode: 'index',
      intersect: false,
      callbacks: {
        label: (context) => ` ${context.dataset.label}: ${context.raw} steps`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { display: true, color: 'rgba(0,0,0,0.05)' }
    },
    x: {
      grid: { display: false }
    }
  }
}

async function fetchData() {
  loading.value = true
  try {
    const data = await getStepTrends()

    avgSteps.value = data.averageSteps

    chartData.value = {
      labels: data.labels,
      datasets: [
        {
          label: 'Average Steps Logged',
          data: data.steps,
          borderColor: '#f59e0b',
          backgroundColor: 'rgba(245, 158, 11, 0.1)',
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#f59e0b',
          pointBorderColor: '#fff',
          pointBorderWidth: 2
        }
      ]
    }
  } catch (err) {
    console.error('Failed to aggregate step trends:', err)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
watch(() => props.refreshKey, fetchData)
</script>

<style scoped>
.ck-card {
  background: white;
  border-radius: var(--ck-radius-xl);
  padding: 1.5rem;
  box-shadow: var(--ck-shadow-sm);
  border: 1px solid rgba(209, 213, 219, 0.5);
  display: flex;
  flex-direction: column;
}
.chart-header { margin-bottom: 1.5rem; }
.chart-title { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-800); margin-bottom: 0.25rem; }
.chart-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }
.stat-box { background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200); border-radius: var(--ck-radius-lg); padding: 1rem; }
.stat-box__label { font-size: 0.75rem; color: var(--ck-gray-600); margin-bottom: 0.25rem; }
.stat-box__value { font-size: 1.5rem; font-weight: 600; }
.stat-box__unit { font-size: 0.75rem; color: var(--ck-gray-500); margin-top: 0.25rem; }
</style>
