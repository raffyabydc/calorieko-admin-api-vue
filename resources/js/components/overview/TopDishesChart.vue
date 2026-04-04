<template>
  <div class="ck-card">
    <div class="chart-header" style="margin-bottom: 1.5rem;">
      <h3 class="chart-title" style="font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-800); margin-bottom: 0.25rem;">AI Identification Frequency</h3>
      <p class="chart-subtitle" style="font-size: 0.875rem; color: var(--ck-gray-600);">Top 5 most pictured and logged dishes</p>
    </div>

    <div v-if="loading" style="text-align: center; padding: 3rem 0; color: var(--ck-gray-500); display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px;">
      <p style="font-size: 0.875rem;">Processing image log data...</p>
    </div>

    <div v-else-if="chartData.labels.length === 0" style="text-align: center; padding: 3rem 0; color: var(--ck-gray-500); display: flex; flex-direction: column; align-items: center; justify-content: center; height: 300px; background: var(--ck-gray-50); border-radius: var(--ck-radius-lg); border: 1px dashed var(--ck-gray-300);">
      <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem; color: var(--ck-gray-400);"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
      <p style="font-size: 0.875rem; font-weight: 500;">Insufficient data to generate trends.</p>
      <p style="font-size: 0.75rem; margin-top: 0.25rem;">Waiting for users to log meal images.</p>
    </div>

    <div v-else class="chart-container" style="height: 300px; position: relative;">
      <Bar :data="chartData" :options="chartOptions" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { Bar } from 'vue-chartjs'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { getTopDishes } from '../../services/api.js'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const props = defineProps({ refreshKey: { type: Number, default: 0 } })
const loading = ref(true)
const chartData = ref({ labels: [], datasets: [] })

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  indexAxis: 'y', // Renders the bar chart horizontally
  plugins: {
    legend: { display: false },
    tooltip: {
      callbacks: {
        label: (context) => ` ${context.raw} logs`
      }
    }
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: { display: true, color: 'rgba(0,0,0,0.05)' }
    },
    y: {
      grid: { display: false }
    }
  }
}

async function fetchData() {
  loading.value = true
  try {
    const data = await getTopDishes()

    chartData.value = {
      labels: data.labels,
      datasets: [
        {
          label: 'Times Logged',
          data: data.data,
          backgroundColor: '#10b981',
          borderRadius: 4,
          barThickness: 24
        }
      ]
    }
  } catch (err) {
    console.error('Failed to load top dishes data:', err)
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
}
</style>
