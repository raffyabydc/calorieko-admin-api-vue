<template>
  <div class="system-logs">
    <div class="ck-card ck-card--elevated">
      <div class="logs-header">
        <div>
          <h2 class="page-title">System Logs</h2>
          <p class="page-subtitle">Security and Administrative Audit Trail</p>
        </div>
        <div class="header-stats">
          <button @click="fetchLogs" class="ck-btn ck-btn--outline" style="display: flex; align-items: center; gap: 0.5rem;">
            <RefreshCwIcon :size="16" :class="{'spin': isLoading}" /> Refresh Data
          </button>
        </div>
      </div>

      <div class="filters-bar">
        <div class="search-wrapper">
          <SearchIcon :size="16" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by action, email, or details..."
            class="ck-input ck-input--with-icon"
          />
        </div>
        <select v-model="statusFilter" class="ck-select" style="width: auto;">
          <option value="all">All Statuses</option>
          <option value="Success">Success</option>
          <option value="Failed">Failed</option>
        </select>
      </div>

      <div v-if="isLoading" class="empty-state">
        <p>Loading system logs from database...</p>
      </div>

      <div v-else-if="error" class="empty-state" style="color: var(--ck-red-500);">
        <p>{{ error }}</p>
      </div>

      <template v-else>
        <div class="table-wrapper" v-if="filteredLogs.length > 0">
          <table class="ck-table">
            <thead>
              <tr>
                <th>Timestamp</th>
                <th>Administrator / User</th>
                <th>Action</th>
                <th>Target Resource</th>
                <th>Status</th>
                <th>IP Address</th>
                <th>Details</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="log in filteredLogs" :key="log.id">
                <td style="white-space: nowrap; font-size: 0.8125rem;">{{ formatDate(log.created_at) }}</td>
                <td style="font-weight: 500;">{{ log.admin_email || '—' }}</td>
                <td><span class="ck-badge" style="background: var(--ck-gray-100); color: var(--ck-gray-800);">{{ log.action }}</span></td>
                <td><span style="font-family: monospace; font-size: 0.75rem;">{{ log.target_resource || '—' }}</span></td>
                <td>
                  <span class="ck-badge" :class="log.status === 'Success' ? 'ck-badge--success' : 'ck-badge--error'">
                    {{ log.status }}
                  </span>
                </td>
                <td style="font-family: monospace; font-size: 0.75rem;">{{ log.ip_address || '—' }}</td>
                <td style="font-size: 0.8125rem; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" :title="log.details">{{ log.details || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <p>No system logs found matching your criteria.</p>
        </div>
      </template>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Search as SearchIcon,
  RefreshCw as RefreshCwIcon
} from 'lucide-vue-next'
import { getSystemLogs } from '../services/api.js'

const logs = ref([])
const isLoading = ref(false)
const error = ref(null)

const searchQuery = ref('')
const statusFilter = ref('all')

const fetchLogs = async () => {
  isLoading.value = true
  error.value = null
  try {
    const data = await getSystemLogs()
    logs.value = data
  } catch (err) {
    console.error("Failed to fetch logs:", err)
    error.value = "Failed to load system logs."
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  fetchLogs()
})

const filteredLogs = computed(() => {
  return logs.value.filter(log => {
    const query = searchQuery.value.toLowerCase()
    const matchSearch = (log.action || '').toLowerCase().includes(query) ||
                        (log.admin_email || '').toLowerCase().includes(query) ||
                        (log.details || '').toLowerCase().includes(query)
    
    const matchStatus = statusFilter.value === 'all' || log.status === statusFilter.value

    return matchSearch && matchStatus
  })
})

const formatDate = (dateString) => {
  if (!dateString) return '—'
  const d = new Date(dateString)
  return d.toLocaleString()
}
</script>

<style scoped>
.system-logs {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.logs-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: var(--ck-gray-900);
}

.page-subtitle {
  font-size: 0.875rem;
  color: var(--ck-gray-600);
}

.filters-bar {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  align-items: center;
}

.search-wrapper {
  position: relative;
  flex: 1;
  min-width: 250px;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ck-gray-400);
  pointer-events: none;
}

.table-wrapper {
  background: var(--ck-gray-50);
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg);
  overflow-x: auto;
}

.empty-state {
  text-align: center;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  100% {
    transform: rotate(360deg);
  }
}
</style>
