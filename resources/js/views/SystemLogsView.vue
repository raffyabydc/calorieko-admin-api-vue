<template>
  <div class="system-logs">
    <!-- Rule 1: Distinct container styling to reinforce boundaries -->
    <div class="ck-card ck-card--elevated local-refresh-container">
      <div class="logs-header local-refresh-header">
        <div>
          <h2 class="page-title">System Logs</h2>
          <p class="page-subtitle">Security and Administrative Audit Trail</p>
        </div>
        <div class="header-actions">
          <div class="auto-refresh-toggle">
            <span class="status-text">Ready</span>
            <label class="toggle-switch">
              <input type="checkbox" v-model="autoRefresh" />
              <span class="slider"></span>
            </label>
            <span class="toggle-label">Auto-refresh (30s)</span>
          </div>
          <!-- Rule 3: Distinct Labelling -->
          <button @click="fetchLogs" class="btn-local-refresh" title="Refresh System Logs">
            <RefreshCwIcon :size="14" class="icon-spacing" /> Refresh
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

      <div v-if="error" class="empty-state" style="color: var(--ck-red-500);">
        <p>{{ error }}</p>
      </div>

      <div class="data-container relative" v-else>
        <!-- Rule 2: Isolated State Indication -->
        <div v-if="isLoading" class="local-loading-overlay">
          <div class="spinner-orange"></div>
        </div>
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
      </div>

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
const autoRefresh = ref(true)

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

.local-refresh-container {
  overflow: hidden;
  position: relative;
  padding: 0;
}

.local-refresh-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  background: var(--ck-gray-50);
  border-bottom: 1px solid var(--ck-gray-200);
  margin-bottom: 1.5rem;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.auto-refresh-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.status-text {
  font-size: 0.875rem;
  color: var(--ck-gray-500);
}

.toggle-switch {
  position: relative;
  display: inline-block;
  width: 32px;
  height: 18px;
}

.toggle-switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

.slider {
  position: absolute;
  cursor: pointer;
  top: 0; left: 0; right: 0; bottom: 0;
  background-color: #ccc;
  transition: .4s;
  border-radius: 34px;
}

.slider:before {
  position: absolute;
  content: "";
  height: 14px;
  width: 14px;
  left: 2px;
  bottom: 2px;
  background-color: white;
  transition: .4s;
  border-radius: 50%;
}

input:checked + .slider {
  background-color: #f97316;
}

input:checked + .slider:before {
  transform: translateX(14px);
}

.toggle-label {
  font-size: 0.875rem;
  color: var(--ck-gray-700);
}

.btn-local-refresh {
  background-color: #f97316; /* bg-orange-500 */
  color: white;
  padding: 0.375rem 1rem;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
  font-size: 0.875rem;
  font-weight: 500;
  transition: all 0.2s;
  border: none;
  cursor: pointer;
}

.btn-local-refresh:hover {
  background-color: #ea580c; /* hover:bg-orange-600 */
}

.icon-spacing {
  margin-right: 0.375rem;
}

.relative {
  position: relative;
}

.local-loading-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  min-height: 200px;
}

.spinner-orange {
  border: 3px solid rgba(249, 115, 22, 0.2);
  border-top-color: #f97316;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  animation: spin 1s linear infinite;
}

.data-container {
  padding: 0 1.5rem 1.5rem 1.5rem;
  min-height: 200px;
}

.filters-bar {
  padding: 0 1.5rem;
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
  flex-wrap: wrap;
  align-items: center;
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
