<template>
  <div class="overview">
    <!-- Sync Toolbar -->
    <div class="sync-toolbar">
      <div class="sync-toolbar__left">
        <div class="sync-toolbar__status" :class="{ 'sync-toolbar__status--syncing': syncing }">
          <RefreshCwIcon
            class="sync-toolbar__status-icon"
            :class="{ 'spin': syncing }"
            :size="16"
          />
          <span v-if="syncing">Refreshing data...</span>
          <span v-else-if="lastRefresh">Last synced: {{ lastRefreshLabel }}</span>
          <span v-else>Ready</span>
        </div>
        <div class="sync-toolbar__auto" v-if="!syncing">
          <label class="toggle-label">
            <input type="checkbox" v-model="autoRefresh" class="toggle-checkbox" />
            <span class="toggle-switch"></span>
            <span class="toggle-text">Auto-refresh (30s)</span>
          </label>
        </div>
      </div>
      <button
        class="sync-toolbar__btn"
        :disabled="syncing"
        @click="refreshAll"
      >
        <RefreshCwIcon :size="16" :class="{ 'spin': syncing }" />
        <span>{{ syncing ? 'Syncing...' : ' Refresh' }}</span>
      </button>
    </div>

    <KpiTiles ref="kpiRef" :refreshKey="refreshKey" />

    <div class="grid-2" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
        <NutritionTrendsChart ref="trendsRef" :refreshKey="refreshKey" />
        <DailyStepsChart ref="stepsRef" :refreshKey="refreshKey" />
    </div>

    <div class="grid-2" style="display: grid; grid-template-columns: 3fr 2fr; gap: 1.5rem; margin-top: 1.5rem;">
        <UserConsistencyChart ref="consistencyRef" :refreshKey="refreshKey" />
        <TopDishesChart ref="dishesRef" :refreshKey="refreshKey" />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { RefreshCw as RefreshCwIcon } from 'lucide-vue-next'
import KpiTiles from '../components/overview/KpiTiles.vue'
import UserConsistencyChart from '../components/overview/UserConsistencyChart.vue'
import TopDishesChart from '../components/overview/TopDishesChart.vue'
import NutritionTrendsChart from '../components/overview/NutritionTrendsChart.vue'
import DailyStepsChart from '../components/overview/DailyStepsChart.vue'

const refreshKey = ref(0)
const syncing = ref(false)
const lastRefresh = ref(null)
const autoRefresh = ref(false)
let autoRefreshTimer = null

const lastRefreshLabel = computed(() => {
  if (!lastRefresh.value) return ''
  const seconds = Math.round((Date.now() - lastRefresh.value) / 1000)
  if (seconds < 5) return 'just now'
  if (seconds < 60) return `${seconds}s ago`
  const minutes = Math.round(seconds / 60)
  return `${minutes}m ago`
})

// Update the label text reactively
const labelTick = ref(0)
let labelTimer = null

onMounted(() => {
  labelTimer = setInterval(() => labelTick.value++, 10000)
})

async function refreshAll() {
  syncing.value = true
  // Increment the key — child components watch this and re-fetch
  refreshKey.value++
  // Give children time to fetch (they'll handle their own loading states)
  await new Promise(resolve => setTimeout(resolve, 1500))
  syncing.value = false
  lastRefresh.value = Date.now()
}

// Watch autoRefresh toggle
import { watch } from 'vue'
watch(autoRefresh, (enabled) => {
  if (autoRefreshTimer) {
    clearInterval(autoRefreshTimer)
    autoRefreshTimer = null
  }
  if (enabled) {
    autoRefreshTimer = setInterval(() => {
      refreshAll()
    }, 30000) // 30 seconds
  }
})

onUnmounted(() => {
  if (autoRefreshTimer) clearInterval(autoRefreshTimer)
  if (labelTimer) clearInterval(labelTimer)
})
</script>

<style scoped>
.overview {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Sync Toolbar ── */
.sync-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--ck-surface, #ffffff);
  border: 1px solid rgba(209, 213, 219, 0.5);
  border-radius: var(--ck-radius-xl, 0.75rem);
  padding: 0.75rem 1.25rem;
  box-shadow: var(--ck-shadow-sm);
}

.sync-toolbar__left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.sync-toolbar__status {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  color: var(--ck-gray-500, #6b7280);
}

.sync-toolbar__status--syncing {
  color: var(--ck-primary, #f59e0b);
  font-weight: 500;
}

.sync-toolbar__status-icon {
  flex-shrink: 0;
}

.sync-toolbar__btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1.25rem;
  background: linear-gradient(135deg, #f59e0b, #f97316);
  color: white;
  border: none;
  border-radius: var(--ck-radius-lg, 0.5rem);
  font-size: 0.8125rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
}

.sync-toolbar__btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
}

.sync-toolbar__btn:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

/* Spin animation */
.spin {
  animation: spin 1s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* ── Auto-refresh toggle ── */
.sync-toolbar__auto {
  display: flex;
  align-items: center;
}

.toggle-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
  user-select: none;
}

.toggle-checkbox {
  display: none;
}

.toggle-switch {
  position: relative;
  width: 2.25rem;
  height: 1.25rem;
  background: var(--ck-gray-300, #d1d5db);
  border-radius: 999px;
  transition: background 0.2s ease;
}

.toggle-switch::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 1rem;
  height: 1rem;
  background: white;
  border-radius: 50%;
  transition: transform 0.2s ease;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.15);
}

.toggle-checkbox:checked + .toggle-switch {
  background: #10b981;
}

.toggle-checkbox:checked + .toggle-switch::after {
  transform: translateX(1rem);
}

.toggle-text {
  font-size: 0.8125rem;
  color: var(--ck-gray-500, #6b7280);
}
</style>
