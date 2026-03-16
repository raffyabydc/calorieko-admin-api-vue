<template>
  <div class="grid-3" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem;">
    <div
      v-for="tile in tiles"
      :key="tile.title"
      class="ck-stat-tile"
    >
      <div class="kpi-tile">
        <div class="kpi-tile__info">
          <p class="kpi-tile__label">{{ tile.title }}</p>
          <p class="kpi-tile__value">{{ loading ? '...' : tile.value }}</p>
          <p v-if="tile.trend" class="kpi-tile__trend">{{ tile.trend }}</p>
        </div>
        <div
          class="kpi-tile__icon"
          :style="{ backgroundColor: tile.color + '20', color: tile.color }"
        >
          <component :is="tile.icon" :size="24" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Users, Target, Activity } from 'lucide-vue-next'
import { getDashboardStats } from '../../services/api.js'

const loading = ref(true)

const tiles = ref([
  {
    title: 'Total Active Participants',
    value: 0,
    icon: Users,
    trend: 'Loading...',
    color: '#10b981'
  },
  {
    title: 'Total Meals Logged This Week',
    value: 0,
    icon: Target,
    trend: 'Loading...',
    color: '#3b82f6'
  },
  {
    title: 'Avg. Caloric Target Adherence',
    value: 0,
    icon: Activity,
    trend: 'Loading...',
    color: '#f59e0b'
  }
])

onMounted(async () => {
  try {
    const stats = await getDashboardStats()

    tiles.value = [
      {
        title: 'Total Active Participants',
        value: stats.activeParticipants,
        icon: Users,
        trend: 'Users with tracking streak',
        color: '#10b981'
      },
      {
        title: 'Total Meals Logged This Week',
        value: stats.mealsThisWeek,
        icon: Target,
        trend: 'Past 7 days',
        color: '#3b82f6'
      },
      {
        title: 'Avg. Caloric Target Adherence',
        value: `${stats.adherence}%`,
        icon: Activity,
        trend: 'Estimated from avg daily intake',
        color: '#f59e0b'
      }
    ]
  } catch (err) {
    console.error('Failed to load dashboard stats:', err)
    tiles.value[0].trend = 'Could not connect to API'
  } finally {
    loading.value = false
  }
})
</script>

<style scoped>
.kpi-tile {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.kpi-tile__info {
  flex: 1;
}

.kpi-tile__label {
  font-size: 0.875rem;
  color: var(--ck-gray-600);
  margin-bottom: 0.5rem;
}

.kpi-tile__value {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--ck-gray-800);
  margin-bottom: 0.25rem;
}

.kpi-tile__trend {
  font-size: 0.75rem;
  color: var(--ck-gray-500);
}

.kpi-tile__icon {
  width: 3rem;
  height: 3rem;
  border-radius: var(--ck-radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
</style>
