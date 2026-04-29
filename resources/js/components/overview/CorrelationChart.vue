<template>
  <div class="ck-card ck-card--elevated" style="padding: 1.5rem;">
    <div class="chart-header">
      <h3 class="section-title">Activity vs. Dietary Correlation</h3>
      <div class="chart-controls">
        <select v-model="selectedDays" @change="loadData" class="ck-select">
          <option :value="7">Last 7 Days</option>
          <option :value="14">Last 14 Days</option>
          <option :value="30">Last 30 Days</option>
          <option :value="60">Last 60 Days</option>
          <option :value="90">Last 90 Days</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="chart-loading">
      <div class="chart-spinner"></div>
      <span>Loading correlation data...</span>
    </div>

    <div v-else-if="!hasData" class="chart-empty">
      <ActivityIcon :size="32" style="color: var(--ck-gray-400);" />
      <p>No activity or nutrition data found for this user in the selected period.</p>
    </div>

    <div v-else class="chart-container" style="height: 320px;">
      <Line :data="chartData" :options="chartOptions" />
    </div>

    <!-- Summary Cards -->
    <div v-if="hasData" class="correlation-summary">
      <div class="summary-card">
        <span class="summary-label">Avg. Intake</span>
        <span class="summary-value" style="color: #f59e0b;">{{ avgIntake }} kcal</span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Avg. Burned</span>
        <span class="summary-value" style="color: #ef4444;">{{ avgBurned }} kcal</span>
      </div>
      <div class="summary-card">
        <span class="summary-label">Avg. Steps</span>
        <span class="summary-value" style="color: #10b981;">{{ avgSteps }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Line } from 'vue-chartjs'
import {
  Chart as ChartJS, CategoryScale, LinearScale, PointElement,
  LineElement, Title, Tooltip, Legend, Filler
} from 'chart.js'
import { Activity as ActivityIcon } from 'lucide-vue-next'
import { getCorrelation } from '../../services/api.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
  uid: { type: String, required: true },
  refreshKey: { type: Number, default: 0 }
})

const loading = ref(false)
const selectedDays = ref(30)
const correlationData = ref(null)

const hasData = computed(() => {
  if (!correlationData.value) return false
  const d = correlationData.value
  return d.intake.some(v => v > 0) || d.burned.some(v => v > 0)
})

const avgIntake = computed(() => {
  if (!correlationData.value) return 0
  const arr = correlationData.value.intake.filter(v => v > 0)
  return arr.length > 0 ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0
})

const avgBurned = computed(() => {
  if (!correlationData.value) return 0
  const arr = correlationData.value.burned.filter(v => v > 0)
  return arr.length > 0 ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length) : 0
})

const avgSteps = computed(() => {
  if (!correlationData.value) return 0
  const arr = correlationData.value.steps.filter(v => v > 0)
  return arr.length > 0 ? Math.round(arr.reduce((a, b) => a + b, 0) / arr.length).toLocaleString() : '0'
})

const chartData = computed(() => ({
  labels: correlationData.value?.labels || [],
  datasets: [
    {
      label: 'Caloric Intake (kcal)',
      data: correlationData.value?.intake || [],
      borderColor: '#f59e0b',
      backgroundColor: 'rgba(245, 158, 11, 0.08)',
      borderWidth: 2.5,
      tension: 0.35,
      fill: true,
      yAxisID: 'y',
      pointRadius: 3,
      pointHoverRadius: 6,
    },
    {
      label: 'Calories Burned (kcal)',
      data: correlationData.value?.burned || [],
      borderColor: '#ef4444',
      backgroundColor: 'rgba(239, 68, 68, 0.06)',
      borderWidth: 2,
      tension: 0.35,
      fill: true,
      yAxisID: 'y',
      pointRadius: 2,
      pointHoverRadius: 5,
      borderDash: [5, 3],
    },
    {
      label: 'Steps',
      data: correlationData.value?.steps || [],
      borderColor: '#10b981',
      backgroundColor: 'rgba(16, 185, 129, 0.06)',
      borderWidth: 2,
      tension: 0.35,
      fill: false,
      yAxisID: 'y1',
      pointRadius: 2,
      pointHoverRadius: 5,
    }
  ]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  interaction: { mode: 'index', intersect: false },
  plugins: {
    legend: {
      position: 'top',
      labels: { usePointStyle: true, padding: 16, font: { size: 11 } }
    },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      titleFont: { size: 12 },
      bodyFont: { size: 11 },
      padding: 12,
      cornerRadius: 8,
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { size: 10 }, maxRotation: 45 }
    },
    y: {
      type: 'linear',
      position: 'left',
      title: { display: true, text: 'Calories (kcal)', font: { size: 11 } },
      grid: { color: 'rgba(0,0,0,0.04)' },
      ticks: { font: { size: 10 } }
    },
    y1: {
      type: 'linear',
      position: 'right',
      title: { display: true, text: 'Steps', font: { size: 11 } },
      grid: { drawOnChartArea: false },
      ticks: { font: { size: 10 } }
    }
  }
}

async function loadData() {
  loading.value = true
  try {
    correlationData.value = await getCorrelation(props.uid, selectedDays.value)
  } catch (err) {
    console.error('Failed to load correlation data:', err)
  } finally {
    loading.value = false
  }
}

onMounted(() => loadData())
watch(() => props.refreshKey, () => loadData())
watch(() => props.uid, () => loadData())
</script>

<style scoped>
.chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.25rem;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: var(--ck-gray-900);
}

.ck-select {
  padding: 0.375rem 0.75rem;
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-md, 0.375rem);
  font-size: 0.8125rem;
  color: var(--ck-gray-700);
  background: var(--ck-gray-50);
  cursor: pointer;
}

.chart-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.chart-spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 2px solid var(--ck-gray-200);
  border-top-color: var(--ck-primary, #10b981);
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.chart-container { position: relative; }

.correlation-summary {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--ck-gray-100);
}

.summary-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.summary-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ck-gray-500);
  font-weight: 500;
}

.summary-value {
  font-size: 1.125rem;
  font-weight: 700;
}
</style>
