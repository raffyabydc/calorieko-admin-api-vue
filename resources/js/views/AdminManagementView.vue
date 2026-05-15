<template>
  <div class="admin-mgmt">
    <!-- Header -->
    <div class="ck-card ck-card--elevated">
      <div class="mgmt-header">
        <div>
          <h2 class="page-title">Admin Management</h2>
          <p class="page-subtitle">Role-Based Access Control & System Administrators</p>
        </div>
        <button @click="openAddModal" class="ck-btn ck-btn--primary">
          <UserPlusIcon :size="16" />
          Add Moderator
        </button>
      </div>
      
      <!-- Success Alert -->
      <Transition name="fade">
        <div v-if="successMessage" class="success-alert">
          <CheckIcon :size="18" />
          <span>{{ successMessage }}</span>
          <button @click="successMessage = null" class="close-alert">&times;</button>
        </div>
      </Transition>

      <!-- Loading State -->
      <div v-if="loading" class="empty-state">
        <p>Loading administrators...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="empty-state" style="color: #ef4444;">
        <p>{{ error }}</p>
        <button @click="fetchAdmins" class="ck-btn ck-btn--outline mt-2">Try Again</button>
      </div>

      <!-- Admins Table -->
      <template v-else>
        <div class="table-wrapper" v-if="admins.length > 0">
          <table class="ck-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email Address</th>
                <th style="text-align: center;">Role</th>
                <th style="text-align: center;">Status</th>
                <th>Joined Date</th>
                <th style="text-align: right;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="admin in admins" :key="admin.id" :class="{ 'inactive-row': !admin.is_active }">
                <td style="font-weight: 600; color: var(--ck-gray-900);">{{ admin.name }}</td>
                <td>{{ admin.email }}</td>
                <td style="text-align: center;">
                  <span class="ck-badge" :class="admin.role === 'Super Admin' ? 'ck-badge--primary' : 'ck-badge--info'">
                    <ShieldAlertIcon v-if="admin.role === 'Super Admin'" :size="12" style="margin-right: 4px;" />
                    <ShieldIcon v-else :size="12" style="margin-right: 4px;" />
                    {{ admin.role }}
                  </span>
                </td>
                <td style="text-align: center;">
                  <span v-if="admin.is_active" class="ck-badge ck-badge--success">Active</span>
                  <span v-else class="ck-badge ck-badge--warning">Restricted</span>
                </td>
                <td style="font-size: 0.8125rem; color: var(--ck-gray-500);">
                  {{ new Date(admin.created_at).toLocaleDateString() }}
                </td>
                <td style="text-align: right;">
                  <div class="action-buttons" v-if="admin.role !== 'Super Admin'">
                    <button class="ck-btn ck-btn--sm" :class="admin.is_active ? 'ck-btn--outline' : 'ck-btn--primary'" @click="toggleStatus(admin)">
                      {{ admin.is_active ? 'Restrict' : 'Reactivate' }}
                    </button>
                    <button class="ck-btn ck-btn--sm ck-btn--danger" style="margin-left: 0.5rem;" @click="confirmDelete(admin)">
                      <TrashIcon :size="14" />
                    </button>
                  </div>
                  <span v-else style="font-size: 0.75rem; color: var(--ck-gray-400);">Protected</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <p>No administrators found.</p>
        </div>
      </template>
    </div>

    <!-- Add Moderator Modal -->
    <Teleport to="body">
      <div v-if="showAddModal" class="ck-overlay">
        <div class="modal animate-fade-in" style="max-width: 450px;">
          <div class="modal__header">
            <h3>Add New Moderator</h3>
            <button @click="closeAddModal" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <form @submit.prevent="submitAddModerator">
            <div class="modal__body" style="display: flex; flex-direction: column; gap: 1rem;">
              <p style="font-size: 0.8125rem; color: var(--ck-gray-500); margin-bottom: 0.5rem;">
                Moderators have read-only access to most dashboard features. They cannot delete users, restrict accounts, or manage other admins.
              </p>
              
              <div class="form-group">
                <label class="form-label">Full Name</label>
                <input v-model="form.name" type="text" class="ck-input" required placeholder="e.g. John Doe">
              </div>
              
              <div class="form-group">
                <label class="form-label">Email Address</label>
                <input v-model="form.email" type="email" class="ck-input" required placeholder="moderator@calorieko.ph">
              </div>
              
              <div class="form-group">
                <label class="form-label">Initial Password (System-Generated)</label>
                <div class="password-display-box">
                  <code class="password-text">{{ showPassword ? form.password : '••••••••' }}</code>
                  <div class="password-actions">
                    <button type="button" class="action-btn" @click="showPassword = !showPassword" title="Toggle Visibility">
                      <EyeIcon v-if="!showPassword" :size="16" />
                      <EyeOffIcon v-else :size="16" />
                    </button>
                    <button type="button" class="action-btn" @click="generatePassword" title="Regenerate Password">
                      <RefreshIcon :size="16" />
                    </button>
                    <button type="button" class="action-btn" @click="copyToClipboard" :title="copied ? 'Copied!' : 'Copy to Clipboard'">
                      <CheckIcon v-if="copied" :size="16" style="color: var(--ck-primary-600);" />
                      <CopyIcon v-else :size="16" />
                    </button>
                  </div>
                </div>
              </div>
              
              <div v-if="formError" style="color: #ef4444; font-size: 0.875rem; padding: 0.5rem; background: #fef2f2; border-radius: 6px;">
                {{ formError }}
              </div>
            </div>
            <div class="modal__footer">
              <button type="button" class="ck-btn ck-btn--ghost" @click="closeAddModal">Cancel</button>
              <button type="submit" class="ck-btn ck-btn--primary" :disabled="formSubmitting">
                {{ formSubmitting ? 'Creating...' : 'Create Moderator' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showDeleteModal" class="ck-overlay" @click.self="showDeleteModal = false">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3 style="color: #ef4444;">Delete Moderator</h3>
          </div>
          <div class="modal__body">
            <p>Are you absolutely sure you want to permanently delete the Moderator account for <strong>{{ selectedAdmin?.email }}</strong>?</p>
            <p style="margin-top: 0.5rem; font-size: 0.8125rem; color: #ef4444;">This action cannot be undone.</p>
          </div>
          <div class="modal__footer">
            <button class="ck-btn ck-btn--ghost" @click="showDeleteModal = false">Cancel</button>
            <button class="ck-btn ck-btn--danger" @click="executeDelete">Yes, Delete Account</button>
          </div>
        </div>
      </div>
    </Teleport>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import {
  UserPlus as UserPlusIcon,
  Shield as ShieldIcon,
  ShieldAlert as ShieldAlertIcon,
  Trash as TrashIcon,
  X as XIcon,
  Copy as CopyIcon,
  RefreshCcw as RefreshIcon,
  Eye as EyeIcon,
  EyeOff as EyeOffIcon,
  Check as CheckIcon
} from 'lucide-vue-next'
import { getModerators, createModerator, toggleModerator, deleteModerator } from '../services/api.js'

const admins = ref([])
const loading = ref(false)
const error = ref(null)

// Modal States
const showAddModal = ref(false)
const showDeleteModal = ref(false)
const selectedAdmin = ref(null)

// Form State
const form = ref({ name: '', email: '', password: '' })
const formSubmitting = ref(false)
const formError = ref(null)
const successMessage = ref(null)
const showPassword = ref(false)
const copied = ref(false)

const generatePassword = () => {
  const length = 8
  const upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ"
  const lower = "abcdefghijklmnopqrstuvwxyz"
  const numbers = "0123456789"
  const symbols = "!@#$%^&*"
  
  // Guarantee at least one of each required type
  let password = ""
  password += upper.charAt(Math.floor(Math.random() * upper.length))
  password += lower.charAt(Math.floor(Math.random() * lower.length))
  password += numbers.charAt(Math.floor(Math.random() * numbers.length))
  password += symbols.charAt(Math.floor(Math.random() * symbols.length))
  
  // Fill the remaining 4 characters
  const all = upper + lower + numbers + symbols
  for (let i = 0; i < 4; i++) {
    password += all.charAt(Math.floor(Math.random() * all.length))
  }
  
  // Shuffle to randomize positions
  form.value.password = password.split('').sort(() => Math.random() - 0.5).join('')
}

const copyToClipboard = async () => {
  if (!form.value.password) return
  try {
    await navigator.clipboard.writeText(form.value.password)
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
  } catch (err) {
    console.error('Failed to copy password', err)
  }
}

const openAddModal = () => {
  generatePassword()
  showAddModal.value = true
}

const fetchAdmins = async () => {
  loading.value = true
  error.value = null
  try {
    admins.value = await getModerators()
  } catch (err) {
    error.value = err.message || "Failed to load administrators."
  } finally {
    loading.value = false
  }
}

const closeAddModal = (force = false) => {
  if (!force && (form.value.name || form.value.email || form.value.password) && !confirm('You have unsaved changes. Are you sure you want to discard them?')) {
    return
  }
  showAddModal.value = false
  form.value = { name: '', email: '', password: '' }
  formError.value = null
  showPassword.value = false
}

const submitAddModerator = async () => {
  formSubmitting.value = true
  formError.value = null
  try {
    const res = await createModerator(form.value)
    admins.value.push(res.admin)
    successMessage.value = `Moderator "${res.admin.name}" created successfully! The credentials have been sent to ${res.admin.email}.`
    closeAddModal(true)
    setTimeout(() => { successMessage.value = null }, 8000)
  } catch (err) {
    formError.value = err.message || "Failed to create moderator account."
  } finally {
    formSubmitting.value = false
  }
}

const toggleStatus = async (admin) => {
  try {
    const res = await toggleModerator(admin.id)
    admin.is_active = res.is_active
  } catch (err) {
    alert(err.message || "Failed to toggle status.")
  }
}

const confirmDelete = (admin) => {
  selectedAdmin.value = admin
  showDeleteModal.value = true
}

const executeDelete = async () => {
  if (!selectedAdmin.value) return
  try {
    await deleteModerator(selectedAdmin.value.id)
    admins.value = admins.value.filter(a => a.id !== selectedAdmin.value.id)
    showDeleteModal.value = false
    selectedAdmin.value = null
  } catch (err) {
    alert(err.message || "Failed to delete moderator.")
  }
}

onMounted(() => {
  fetchAdmins()
})
</script>

<style scoped>
.admin-mgmt { display: flex; flex-direction: column; gap: 1.5rem; }
.mgmt-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; font-weight: 600; color: var(--ck-gray-900); }
.page-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }

.table-wrapper {
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg); overflow-x: auto;
}

.inactive-row td {
  opacity: 0.7;
  background: #f9fafb;
}

.empty-state { text-align: center; padding: 2rem; color: var(--ck-gray-500); font-size: 0.875rem; }

.form-group { display: flex; flex-direction: column; gap: 0.25rem; }
.form-label { font-size: 0.8125rem; font-weight: 600; color: var(--ck-gray-700); }

/* Modals */
.modal {
  background: white; border-radius: var(--ck-radius-xl); width: 100%;
  box-shadow: var(--ck-shadow-2xl); overflow: hidden;
}
.modal__header {
  display: flex; justify-content: space-between; align-items: center;
  padding: 1.5rem; border-bottom: 1px solid var(--ck-gray-200);
}
.modal__header h3 { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-900); }
.modal__close { color: var(--ck-gray-400); padding: 0.25rem; border-radius: var(--ck-radius-md); background: transparent; border: none; cursor: pointer; }
.modal__close:hover { background: var(--ck-gray-100); color: var(--ck-gray-700); }
.modal__body { padding: 1.5rem; }
.modal__footer {
  padding: 1.25rem 1.5rem; background: var(--ck-gray-50); border-top: 1px solid var(--ck-gray-200);
  display: flex; justify-content: flex-end; gap: 0.75rem;
}

/* Password Display Box (Read-only) */
.password-display-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  background: var(--ck-gray-100);
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg);
  padding: 0.75rem 1rem;
  min-height: 46px;
  position: relative;
}

.password-text {
  font-family: var(--ck-font-mono);
  font-size: 1rem;
  color: var(--ck-gray-900);
  letter-spacing: 0.05em;
  user-select: none; /* Prevent accidental selection since we have a copy button */
}

.password-actions {
  display: flex;
  gap: 4px;
}

.action-btn {
  background: transparent;
  border: none;
  padding: 6px;
  border-radius: 6px;
  color: var(--ck-gray-400);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.action-btn:hover {
  background: var(--ck-gray-100);
  color: var(--ck-gray-700);
}

.action-btn:active {
  transform: scale(0.9);
}

/* Success Alert */
.success-alert {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  background: #ecfdf5;
  border: 1px solid #10b981;
  color: #065f46;
  padding: 1rem 1.25rem;
  border-radius: var(--ck-radius-lg);
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  position: relative;
}

.success-alert span {
  flex: 1;
}

.close-alert {
  background: transparent;
  border: none;
  font-size: 1.25rem;
  color: #065f46;
  cursor: pointer;
  padding: 0 0.5rem;
  opacity: 0.5;
  transition: opacity 0.2s;
}

.close-alert:hover {
  opacity: 1;
}
</style>
