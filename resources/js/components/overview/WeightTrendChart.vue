<template>
  <div class="ck-card ck-card--elevated" style="padding: 1.5rem;">
    <div class="chart-header">
      <h3 class="section-title">Weight Trend</h3>
      <div class="chart-controls">
        <select v-model="selectedDays" @change="loadData" class="ck-select">
          <option :value="30">Last 30 Days</option>
          <option :value="60">Last 60 Days</option>
          <option :value="90">Last 90 Days</option>
          <option :value="180">Last 6 Months</option>
          <option :value="365">Last Year</option>
        </select>
      </div>
    </div>

    <div v-if="loading" class="chart-loading">
      <div class="chart-spinner"></div>
      <span>Loading weight history...</span>
    </div>

    <div v-else-if="!hasData" class="chart-empty">
      <ScaleIcon :size="32" style="color: var(--ck-gray-400);" />
      <p>No weight history recorded for this user.</p>
      <span class="chart-empty-hint">Weight logs will appear here once the mobile app begins syncing weight entries.</span>
    </div>

    <div v-else class="chart-container" style="height: 280px;">
      <Line :data="chartData" :options="chartOptions" />
    </div>

    <!-- Stats -->
    <div v-if="hasData" class="weight-stats">
      <div class="stat-pill">
        <span class="stat-label">Start</span>
        <span class="stat-value">{{ firstWeight }} kg</span>
      </div>
      <div class="stat-pill stat-pill--accent">
        <span class="stat-label">Current</span>
        <span class="stat-value">{{ lastWeight }} kg</span>
      </div>
      <div class="stat-pill" :class="changeClass">
        <span class="stat-label">Change</span>
        <span class="stat-value">{{ weightChange }}</span>
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
import { Scale as ScaleIcon } from 'lucide-vue-next'
import { getWeightTrend } from '../../services/api.js'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Title, Tooltip, Legend, Filler)

const props = defineProps({
  uid: { type: String, required: true },
  refreshKey: { type: Number, default: 0 }
})

const loading = ref(false)
const selectedDays = ref(90)
const trendData = ref(null)

const hasData = computed(() => trendData.value?.weights?.length > 0)

const firstWeight = computed(() => trendData.value?.weights?.[0] ?? '—')
const lastWeight = computed(() => {
  const w = trendData.value?.weights
  return w?.length > 0 ? w[w.length - 1] : '—'
})

const weightChange = computed(() => {
  const w = trendData.value?.weights
  if (!w || w.length < 2) return '—'
  const diff = (w[w.length - 1] - w[0]).toFixed(1)
  return diff > 0 ? `+${diff} kg` : `${diff} kg`
})

const changeClass = computed(() => {
  const w = trendData.value?.weights
  if (!w || w.length < 2) return ''
  const diff = w[w.length - 1] - w[0]
  return diff < 0 ? 'stat-pill--success' : diff > 0 ? 'stat-pill--warning' : ''
})

const chartData = computed(() => ({
  labels: trendData.value?.labels || [],
  datasets: [{
    label: 'Weight (kg)',
    data: trendData.value?.weights || [],
    borderColor: '#8b5cf6',
    backgroundColor: 'rgba(139, 92, 246, 0.08)',
    borderWidth: 2.5,
    tension: 0.3,
    fill: true,
    pointRadius: 4,
    pointBackgroundColor: '#8b5cf6',
    pointHoverRadius: 7,
    pointHoverBackgroundColor: '#7c3aed',
  }]
}))

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
    tooltip: {
      backgroundColor: 'rgba(15, 23, 42, 0.9)',
      titleFont: { size: 12 },
      bodyFont: { size: 12 },
      padding: 12,
      cornerRadius: 8,
      callbacks: {
        label: (ctx) => `${ctx.parsed.y} kg`
      }
    }
  },
  scales: {
    x: {
      grid: { display: false },
      ticks: { font: { size: 10 }, maxRotation: 45 }
    },
    y: {
      title: { display: true, text: 'Weight (kg)', font: { size: 11 } },
      grid: { color: 'rgba(0,0,0,0.04)' },
      ticks: { font: { size: 10 } }
    }
  }
}

async function loadData() {
  loading.value = true
  try {
    trendData.value = await getWeightTrend(props.uid, selectedDays.value)
  } catch (err) {
    console.error('Failed to load weight trend:', err)
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

.chart-loading, .chart-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.chart-empty-hint {
  font-size: 0.75rem;
  color: var(--ck-gray-400);
}

.chart-spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 2px solid var(--ck-gray-200);
  border-top-color: #8b5cf6;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.chart-container { position: relative; }

.weight-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--ck-gray-100);
}

.stat-pill {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.625rem;
  background: var(--ck-gray-50);
  border-radius: var(--ck-radius-lg, 0.5rem);
  border: 1px solid var(--ck-gray-100);
}

.stat-pill--accent { background: #f5f3ff; border-color: #ede9fe; }
.stat-pill--success { background: #ecfdf5; border-color: #d1fae5; }
.stat-pill--warning { background: #fffbeb; border-color: #fef3c7; }

.stat-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ck-gray-500);
  font-weight: 500;
}

.stat-value {
  font-size: 1rem;
  font-weight: 700;
  color: var(--ck-gray-900);
}

.stat-pill--success .stat-value { color: #059669; }
.stat-pill--warning .stat-value { color: #d97706; }
</style>
