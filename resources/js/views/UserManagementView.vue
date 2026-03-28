<template>
  <div class="user-mgmt">
    <!-- Header -->
    <div class="ck-card ck-card--elevated">
      <div class="mgmt-header">
        <div>
          <h2 class="page-title">User Management</h2>
          <p class="page-subtitle">Research Participant Registry & Monitoring</p>
        </div>
        <div class="header-stats">
          <div class="header-stat">
            <span class="header-stat__value">{{ participants.length }}</span>
            <span class="header-stat__label">Total Participants</span>
          </div>
          <div class="header-stat">
            <span class="header-stat__value">{{ activeCount }}</span>
            <span class="header-stat__label">Active</span>
          </div>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="filters-bar">
        <div class="search-wrapper">
          <SearchIcon :size="16" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by Participant ID..."
            class="ck-input ck-input--with-icon"
          />
        </div>
        <select v-model="goalFilter" class="ck-select" style="width: auto; min-width: 120px;">
          <option value="all">All Goals</option>
          <option value="weight_loss">Weight Loss</option>
          <option value="maintain_weight">Maintain</option>
          <option value="gain_muscle">Weight Gain</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="empty-state">
        <p>Loading participants from API...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="empty-state">
        <p style="color: #ef4444;">{{ error }}</p>
        <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--ck-gray-500);">
          Make sure the API server is running: php artisan serve --host=0.0.0.0 --port=8000
        </p>
      </div>

      <div v-if="isLoading" class="empty-state" style="padding: 2rem; text-align: center;">
        <p>Loading participants data...</p>
      </div>

      <div v-else-if="error" class="empty-state" style="padding: 2rem; text-align: center; color: var(--ck-red-500);">
        <p>{{ error }}</p>
        <button @click="fetchParticipants" class="ck-btn ck-btn--outline mt-2">Try Again</button>
      </div>

      <!-- Participants Table -->
      <template v-else>
        <div class="table-wrapper" v-if="filteredParticipants.length > 0">
          <table class="ck-table">
            <thead>
              <tr>
                <th>Participant ID</th>
                <th style="text-align: center;">Age</th>
                <th style="text-align: center;">Sex</th>
                <th style="text-align: center;">Weight (kg)</th>
                <th style="text-align: center;">Height (cm)</th>
                <th style="text-align: center;">BMI</th>
                <th style="text-align: center;">TDEE (kcal)</th>
                <th>Goal</th>
                <th style="text-align: center;">Consistency</th>
                <th style="text-align: center;">Scale Linked</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="p in filteredParticipants" :key="p.uid">
                <td style="font-weight: 500; color: var(--ck-gray-900); font-family: monospace;">{{ p.display_id || '—' }}</td>
                <td style="text-align: center;">{{ p.age || '—' }}</td>
                <td style="text-align: center;">
                  <span v-if="p.sex" class="ck-badge" :class="p.sex === 'Male' ? 'ck-badge--info' : 'ck-badge--primary'">
                    {{ p.sex }}
                  </span>
                  <span v-else>—</span>
                </td>
                <td style="text-align: center;">{{ p.weight ? p.weight.toFixed(1) : '—' }}</td>
                <td style="text-align: center;">{{ p.height ? p.height.toFixed(1) : '—' }}</td>
                <td style="text-align: center; font-weight: 600;">{{ calculateBMI(p) }}</td>
                <td style="text-align: center; font-weight: 600;">{{ calculateTDEE(p) }}</td>
                <td>
                  <span v-if="p.goal" class="ck-badge ck-badge--success">{{ p.goal }}</span>
                  <span v-else>—</span>
                </td>
                <td style="text-align: center;">
                  <span v-if="p.is_engaged" class="ck-badge ck-badge--success">{{ p.streak || 0 }} Days</span>
                  <span v-else class="ck-badge ck-badge--warning">Dormant</span>
                </td>
                <td style="text-align: center;">
                  <span class="ck-badge ck-badge--outline ck-badge--error">No</span>
                </td>
                <td style="text-align: center; position: relative;">
                  <button class="action-btn" @click.stop="toggleMenu($event, p)" title="More Actions">
                    <MoreVerticalIcon :size="16" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="empty-state">
          <p>No participants found.</p>
          <p style="margin-top: 0.5rem; font-size: 0.75rem; color: var(--ck-gray-500);">
            Users will appear here once they sign up and sync data from the CalorieKo mobile app.
          </p>
        </div>
      </template>
    </div>

    <!-- Detail Modal -->
    <Teleport to="body">
      <div v-if="selectedParticipant" class="ck-overlay" @click.self="selectedParticipant = null">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3>Participant Details</h3>
            <button @click="selectedParticipant = null" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <div class="modal__body">
            <div class="detail-grid">
              <div class="detail-row">
                <span class="detail-label">Participant ID:</span>
                <span class="font-mono" style="font-size: 0.875rem; font-weight: 600;">{{ selectedParticipant.display_id }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Age / Sex:</span>
                <span>{{ selectedParticipant.age || '—' }} / {{ selectedParticipant.sex || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Weight:</span>
                <span>{{ selectedParticipant.weight ? selectedParticipant.weight.toFixed(1) + ' kg' : '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Height:</span>
                <span>{{ selectedParticipant.height ? selectedParticipant.height.toFixed(1) + ' cm' : '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">BMI:</span>
                <span style="font-weight: 600;">{{ calculateBMI(selectedParticipant) }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Baseline TDEE:</span>
                <span style="font-weight: 600;">{{ calculateTDEE(selectedParticipant) }} kcal</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Activity Level:</span>
                <span>{{ selectedParticipant.activityLevel || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Goal:</span>
                <span>{{ selectedParticipant.goal || '—' }}</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Consistency:</span>
                <span style="font-weight: 600; color: #f59e0b;">{{ selectedParticipant.streak || 0 }} Days</span>
              </div>
              <div class="detail-row">
                <span class="detail-label">Scale Linked:</span>
                <span style="font-weight: 600; color: var(--ck-red-500);">No</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Logs Modal -->
    <Teleport to="body">
      <div v-if="selectedParticipantLogs" class="ck-overlay" @click.self="selectedParticipantLogs = null">
        <div class="modal animate-fade-in" style="max-width: 600px;">
          <div class="modal__header">
            <h3>Participant Logs: {{ selectedParticipantLogs.display_id || '—' }}</h3>
            <button @click="selectedParticipantLogs = null" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <div class="modal__body">
            
            <div v-if="loadingLogs" class="empty-state" style="padding: 1rem;">
              <p>Loading meal and activity logs...</p>
            </div>
            
            <div v-else>
              <!-- Meal Logs -->
              <div class="log-group">
                <h5 class="log-subtitle">Recent Meals</h5>
                <div class="table-wrapper" style="max-height: 250px;">
                  <table class="ck-table log-table" v-if="participantMealLogs.length > 0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Meal Type</th>
                        <th style="text-align: right;">Cals (kcal)</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="m in participantMealLogs" :key="m.meal_log_id">
                        <td>{{ new Date(m.timestamp).toLocaleDateString() }}</td>
                        <td>{{ m.meal_type }}</td>
                        <td style="text-align: right; font-weight: 500;">
                          {{ m.items ? m.items.reduce((sum, item) => sum + (Number(item.calories) || 0), 0) : 0 }}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <p v-else style="padding: 0.5rem; font-size: 0.8125rem; color: var(--ck-gray-500);">No meals logged yet.</p>
                </div>
              </div>

              <!-- Activity Logs -->
              <div class="log-group" style="margin-top: 1.5rem;">
                <h5 class="log-subtitle">Recent Activities</h5>
                <div class="table-wrapper" style="max-height: 250px;">
                  <table class="ck-table log-table" v-if="participantActivityLogs.length > 0">
                    <thead>
                      <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th style="text-align: right;">Cals Burned</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="a in participantActivityLogs" :key="a.id">
                        <td>{{ new Date(a.timestamp).toLocaleDateString() }}</td>
                        <td style="text-transform: capitalize;">{{ a.type }}</td>
                        <td>{{ a.name }}</td>
                        <td style="text-align: right; font-weight: 500; color: #f59e0b;">{{ a.calories }}</td>
                      </tr>
                    </tbody>
                  </table>
                  <p v-else style="padding: 0.5rem; font-size: 0.8125rem; color: var(--ck-gray-500);">No activities logged yet.</p>
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Teleport>

    <!-- Custom Modals for Actions -->
    <Teleport to="body">
      <!-- 1. Reset Password Confirmation & Success Modal -->
      <div v-if="resetPasswordModal.show" class="modal-overlay" @click.self="closeActionModals">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3>
              {{ resetPasswordModal.status === 'success' ? 'Password Reset Initiated' 
               : resetPasswordModal.status === 'error' ? 'Reset Failed'
               : 'Reset User Password' }}
            </h3>
          </div>
          <div class="modal__body">
            <p v-if="resetPasswordModal.status === 'success'">
              A password reset link has been successfully sent to <strong>{{ resetPasswordModal.user?.display_id }}</strong>.
            </p>
            <div v-else-if="resetPasswordModal.status === 'error'" style="padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #ef4444;">
              <p style="font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><XCircleIcon :size="18" /> Operation Failed</p>
              <p style="font-size: 0.875rem;">{{ resetPasswordModal.errorMessage }}</p>
            </div>
            <p v-else>
              Are you sure you want to send a password reset link to <strong>{{ resetPasswordModal.user?.display_id }}</strong>?
            </p>
          </div>
          <div class="modal__footer">
            <button v-if="resetPasswordModal.status === 'success' || resetPasswordModal.status === 'error'" class="btn btn--secondary" @click="closeActionModals">Close</button>
            <template v-else>
              <button class="btn btn--secondary" @click="closeActionModals">Cancel</button>
              <button class="btn btn--primary" @click="confirmResetPassword">Send Reset Link</button>
            </template>
          </div>
        </div>
      </div>

      <!-- 2. Deactivate/Reactivate Confirmation Modal -->
      <div v-if="deactivateModal.show" class="modal-overlay" @click.self="closeActionModals">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3>
              {{ deactivateModal.status === 'error' ? 'Status Update Failed'
               : deactivateModal.user?.is_active !== false ? 'Deactivate User' : 'Reactivate User' }}
            </h3>
          </div>
          <div class="modal__body">
            <div v-if="deactivateModal.status === 'error'" style="padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #ef4444;">
              <p style="font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><XCircleIcon :size="18" /> Operation Failed</p>
              <p style="font-size: 0.875rem;">{{ deactivateModal.errorMessage }}</p>
            </div>
            <template v-else>
              <p>
                Are you sure you want to <strong>{{ deactivateModal.user?.is_active !== false ? 'deactivate' : 'reactivate' }}</strong>
                the account for <strong>{{ deactivateModal.user?.display_id }}</strong>?
              </p>
              <p v-if="deactivateModal.user?.is_active !== false" style="margin-top: 0.5rem; font-size: 0.8125rem; color: #ef4444;">
                Deactivating will immediately revoke their access to the mobile application.
              </p>
            </template>
          </div>
          <div class="modal__footer">
            <button v-if="deactivateModal.status === 'error'" class="btn btn--secondary" @click="closeActionModals">Close</button>
            <template v-else>
              <button class="btn btn--secondary" @click="closeActionModals">Cancel</button>
              <button class="btn" :class="deactivateModal.user?.is_active !== false ? 'btn--danger' : 'btn--primary'" @click="confirmDeactivate">
                Yes, {{ deactivateModal.user?.is_active !== false ? 'Deactivate' : 'Reactivate' }}
              </button>
            </template>
          </div>
        </div>
      </div>

      <!-- 3. Delete Confirmation Modal -->
      <div v-if="deleteModal.show" class="modal-overlay" @click.self="closeActionModals">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3 :style="deleteModal.status === 'error' ? 'color: #ef4444;' : 'color: #ef4444;'">
              {{ deleteModal.status === 'error' ? 'Deletion Failed' : 'Permanently Delete User' }}
            </h3>
          </div>
          <div class="modal__body">
            <div v-if="deleteModal.status === 'error'" style="padding: 1rem; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #ef4444;">
              <p style="font-weight: 600; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;"><XCircleIcon :size="18" /> Operation Failed</p>
              <p style="font-size: 0.875rem;">{{ deleteModal.errorMessage }}</p>
            </div>
            <template v-else>
              <p>
                Are you absolutely sure you want to delete <strong>{{ deleteModal.user?.display_id }}</strong>?
              </p>
              <p style="margin-top: 0.5rem; font-size: 0.8125rem; font-weight: 500; color: #ef4444; padding: 0.75rem; background: #fef2f2; border-radius: 6px; border: 1px solid #fecaca;">
                This action cannot be undone. All meal logs, activity logs, and personal data associated with this account will be permanently erased.
              </p>
            </template>
          </div>
          <div class="modal__footer">
            <button v-if="deleteModal.status === 'error'" class="btn btn--secondary" @click="closeActionModals">Close</button>
            <template v-else>
              <button class="btn btn--secondary" @click="closeActionModals">Cancel</button>
              <button class="btn btn--danger" @click="confirmDelete">Yes, Delete Account</button>
            </template>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Teleported Action Menu & Overlay -->
    <Teleport to="body">
      <!-- Invisible overlay to close dropdown -->
      <div v-if="activeMenu" class="dropdown-overlay" @click="activeMenu = null"></div>

      <!-- Standalone Fixed Menu -->
      <Transition name="dropdown-slide">
        <div v-if="activeMenu && selectedUserForMenu" 
             class="dropdown-menu" 
             :style="{ position: 'fixed', top: menuPosition.top, right: menuPosition.right, margin: 0 }">
             
          <button class="dropdown-item" @click="showDetail(selectedUserForMenu); activeMenu = null">
            <EyeIcon :size="14" /> View Details
          </button>
          <button class="dropdown-item" @click="resetPassword(selectedUserForMenu); activeMenu = null">
            <KeyIcon :size="14" /> Reset Password
          </button>
          <button class="dropdown-item" @click="showLogs(selectedUserForMenu); activeMenu = null">
            <FileTextIcon :size="14" /> View Local Logs
          </button>
          <button class="dropdown-item" :class="selectedUserForMenu.is_active !== false ? 'dropdown-item--warning' : ''" @click="deactivateUser(selectedUserForMenu); activeMenu = null">
            <UserXIcon :size="14" /> {{ selectedUserForMenu.is_active !== false ? 'Deactivate User' : 'Reactivate User' }}
          </button>
          <button class="dropdown-item dropdown-item--danger" @click="deleteUser(selectedUserForMenu); activeMenu = null">
            <TrashIcon :size="14" /> Delete User
          </button>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Search as SearchIcon,
  Eye as EyeIcon,
  X as XIcon,
  Mail as MailIcon,
  UserX as UserXIcon,
  FileText as FileTextIcon,
  MoreVertical as MoreVerticalIcon,
  Key as KeyIcon,
  Trash as TrashIcon,
  XCircle as XCircleIcon
} from 'lucide-vue-next'

// State Management
const searchQuery = ref('')
const goalFilter = ref('all')
const selectedParticipant = ref(null)
const selectedParticipantLogs = ref(null)
const participantMealLogs = ref([])
const participantActivityLogs = ref([])
const loadingLogs = ref(false)
const activeMenu = ref(null)
const selectedUserForMenu = ref(null)
const menuPosition = ref({ top: '0px', right: '0px' })

// Custom Confirmation Modals State
const resetPasswordModal = ref({ show: false, user: null, status: 'confirm', errorMessage: '' })
const deactivateModal = ref({ show: false, user: null, status: 'confirm', errorMessage: '' })
const deleteModal = ref({ show: false, user: null, status: 'confirm', errorMessage: '' })

import { 
  getProfiles, 
  getMealLogs, 
  getActivityLogs,
  deactivateProfile,
  resetProfilePassword,
  deleteProfile
} from '../services/api.js'

// 1. Change participants to a reactive empty array
const participants = ref([])
const isLoading = ref(false)
const error = ref(null)

// 2. Create the fetch function to hit your Laravel API
const fetchParticipants = async () => {
  isLoading.value = true
  error.value = null

  try {
    // Replace with your actual Laravel API endpoint
    // If testing on the same machine, it might be http://127.0.0.1:8000/api/admin/profiles
    // If using a deployed backend or local network IP, use that instead.
    const data = await getProfiles()

    // Assign the fetched data to our reactive variable
    participants.value = data

  } catch (err) {
    console.error("Failed to fetch participants:", err)
    error.value = "Failed to load participants from the server."
  } finally {
    isLoading.value = false
  }
}

// 3. Trigger the fetch when the component loads
onMounted(() => {
  fetchParticipants()
})

// 4. Update computed properties to use participants.value
const activeCount = computed(() => participants.value.filter(p => p.is_active).length)

const filteredParticipants = computed(() =>
    participants.value.filter(p => {
      const pDisplayId = (p.display_id || '').toLowerCase()
      const matchSearch = pDisplayId.includes(searchQuery.value.toLowerCase())
      const matchGoal = goalFilter.value === 'all' || p.goal === goalFilter.value
      return matchSearch && matchGoal
    })
)

const consistencyColor = (c) => c >= 80 ? '#10b981' : c >= 60 ? '#f59e0b' : '#ef4444'

const showDetail = (p) => { 
  selectedParticipant.value = p 
}

const showLogs = async (p) => {
  selectedParticipantLogs.value = p
  participantMealLogs.value = []
  participantActivityLogs.value = []
  loadingLogs.value = true

  try {
    const [mLogs, aLogs] = await Promise.all([
      getMealLogs(p.uid),
      getActivityLogs(p.uid)
    ])
    participantMealLogs.value = mLogs
    participantActivityLogs.value = aLogs
  } catch (err) {
    console.error("Failed to fetch participant logs:", err)
  } finally {
    loadingLogs.value = false
  }
}

// Actions Menu Logic
const toggleMenu = (event, p) => {
  if (activeMenu.value === p.uid) {
    activeMenu.value = null
    selectedUserForMenu.value = null
  } else {
    activeMenu.value = p.uid
    selectedUserForMenu.value = p
    
    // Calculate fixed viewport positioning
    const rect = event.currentTarget.getBoundingClientRect()
    menuPosition.value = {
      top: `${rect.bottom + 4}px`,
      right: `${window.innerWidth - rect.right}px`
    }
  }
}

// Action Trigger Methods (Opening Modals instead of direct API/alert calls)
const resetPassword = (p) => {
  resetPasswordModal.value = { show: true, user: p, status: 'confirm', errorMessage: '' }
}

const deactivateUser = (p) => {
  deactivateModal.value = { show: true, user: p, status: 'confirm', errorMessage: '' }
}

const deleteUser = (p) => {
  deleteModal.value = { show: true, user: p, status: 'confirm', errorMessage: '' }
}

const closeActionModals = () => {
  resetPasswordModal.value.show = false
  deactivateModal.value.show = false
  deleteModal.value.show = false
}

// Action Confirmation Methods (Actual API calls)
const confirmResetPassword = async () => {
  const p = resetPasswordModal.value.user
  if (!p) return
  
  try {
    const res = await resetProfilePassword(p.uid)
    resetPasswordModal.value.status = 'success' // Change completely to success view
  } catch(err) {
    resetPasswordModal.value.status = 'error'
    resetPasswordModal.value.errorMessage = err.message || "Error reseting password. Please check connection."
  }
}

const confirmDeactivate = async () => {
  const p = deactivateModal.value.user
  if (!p) return
  
  try {
    const res = await deactivateProfile(p.uid)
    // Update local state without refetching everything
    const idx = participants.value.findIndex(u => u.uid === p.uid)
    if (idx !== -1) {
      participants.value[idx].is_active = res.is_active
    }
    closeActionModals()
  } catch(err) {
    deactivateModal.value.status = 'error'
    deactivateModal.value.errorMessage = err.message || "Error changing user status."
  }
}

const confirmDelete = async () => {
  const p = deleteModal.value.user
  if (!p) return

  try {
    await deleteProfile(p.uid)
    // Remove from table visually
    participants.value = participants.value.filter(u => u.uid !== p.uid)
    closeActionModals()
  } catch(err) {
    deleteModal.value.status = 'error'
    deleteModal.value.errorMessage = err.message || "Error deleting user."
  }
}

// Utilities for computed app variables
const calculateBMI = (p) => {
  if (!p.weight || !p.height) return '—'
  const heightInMeters = p.height / 100
  const bmi = p.weight / (heightInMeters * heightInMeters)
  return bmi.toFixed(1)
}

const calculateTDEE = (p) => {
  if (!p.weight || !p.height || !p.age || !p.sex) return '—'
  
  // Mifflin-St Jeor Equation
  let bmr = (10 * p.weight) + (6.25 * p.height) - (5 * p.age)
  bmr = p.sex === 'Male' ? bmr + 5 : bmr - 161
  
  // Activity Multiplier
  let multiplier = 1.2 // Default for mostly inactive
  switch(p.activityLevel) {
    case 'lightly_active': multiplier = 1.375; break;
    case 'active': multiplier = 1.55; break;
    case 'very_active': multiplier = 1.725; break;
  }
  
  return Math.round(bmr * multiplier)
}
</script>

<style scoped>
.user-mgmt { display: flex; flex-direction: column; gap: 1.5rem; }

.mgmt-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; font-weight: 600; color: var(--ck-gray-900); }
.page-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }

.header-stats { display: flex; gap: 1.5rem; }
.header-stat { text-align: center; }
.header-stat__value { font-size: 1.5rem; font-weight: 700; color: var(--ck-primary); display: block; }
.header-stat__label { font-size: 0.75rem; color: var(--ck-gray-500); }

.filters-bar { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
.search-wrapper { position: relative; flex: 1; min-width: 200px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--ck-gray-400); pointer-events: none; }

.table-wrapper {
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg); overflow-x: auto;
}

.action-btn {
  padding: 0.375rem; color: var(--ck-gray-500); border-radius: var(--ck-radius-md);
  transition: all var(--ck-transition-fast);
}
.action-btn:hover { background: var(--ck-gray-100); color: var(--ck-gray-800); }

.empty-state { text-align: center; padding: 2rem; color: var(--ck-gray-500); font-size: 0.875rem; }

/* Modal */
.modal {
  background: white; border-radius: var(--ck-radius-xl); width: 100%; max-width: 500px;
  box-shadow: var(--ck-shadow-2xl); overflow: hidden;
}
.modal__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.5rem; border-bottom: 1px solid var(--ck-gray-200);
}
.modal__header h3 { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-900); }
.modal__close { color: var(--ck-gray-400); padding: 0.25rem; border-radius: var(--ck-radius-md); }
.modal__close:hover { background: var(--ck-gray-100); color: var(--ck-gray-700); }
.modal__body { padding: 1.5rem; }

.detail-grid { display: flex; flex-direction: column; gap: 0.75rem; margin-bottom: 1.5rem; }
.detail-row { display: flex; gap: 1rem; font-size: 0.875rem; }
.detail-label { color: var(--ck-gray-500); width: 120px; flex-shrink: 0; }

.dropdown-overlay {
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  z-index: 9998;
}
.dropdown-menu {
  z-index: 9999;
  min-width: 200px;
  background: white;
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-md);
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  padding: 0.5rem 0;
  text-align: left;
  transform-origin: top right;
}
.dropdown-item {
  width: 100%;
  text-align: left;
  padding: 0.5rem 1rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--ck-gray-700);
  background: transparent;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  border: none;
  cursor: pointer;
  transition: all var(--ck-transition-fast);
}
.dropdown-item:hover {
  background: var(--ck-gray-50);
  color: var(--ck-gray-900);
}
.dropdown-item--warning {
  color: #f59e0b;
}
.dropdown-item--warning:hover {
  background: #fef3c7;
  color: #d97706;
}
.dropdown-item--danger {
  color: var(--ck-red-500);
}
.dropdown-item--danger:hover {
  background: #fee2e2;
  color: var(--ck-red-700);
}

/* Dropdown Animation */
.dropdown-slide-enter-active,
.dropdown-slide-leave-active {
  transition: opacity 0.2s ease, transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.dropdown-slide-enter-from,
.dropdown-slide-leave-to {
  opacity: 0;
  transform: scale(0.95);
}

/* Modals Refactoring & Buttons */
.modal-overlay {
  position: fixed; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 10000;
}
.modal__footer {
  padding: 1rem 1.5rem; background: var(--ck-gray-50); display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--ck-gray-200);
}
.btn {
  padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: var(--ck-radius-md); cursor: pointer; transition: all 0.2s; border: 1px solid transparent;
}
.btn--secondary { background: white; border-color: var(--ck-gray-300); color: var(--ck-gray-700); }
.btn--secondary:hover { background: var(--ck-gray-50); }
.btn--primary { background: var(--ck-primary); color: white; }
.btn--primary:hover { filter: brightness(1.1); }
.btn--danger { background: #ef4444; color: white; }
.btn--danger:hover { background: #dc2626; }
</style>
