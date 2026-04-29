<template>
  <div class="admin-mgmt">
    <!-- Header -->
    <div class="ck-card ck-card--elevated">
      <div class="mgmt-header">
        <div>
          <h2 class="page-title">Admin Management</h2>
          <p class="page-subtitle">Role-Based Access Control & System Administrators</p>
        </div>
        <button @click="showAddModal = true" class="ck-btn ck-btn--primary">
          <UserPlusIcon :size="16" />
          Add Moderator
        </button>
      </div>

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
      <div v-if="showAddModal" class="ck-overlay" @click.self="closeAddModal">
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
                <label class="form-label">Initial Password</label>
                <input v-model="form.password" type="password" class="ck-input" required minlength="8" placeholder="At least 8 characters">
              </div>
              
              <div v-if="formError" style="color: #ef4444; font-size: 0.875rem; padding: 0.5rem; background: #fef2f2; border-radius: 6px;">
                {{ formError }}
              </div>
            </div>
            <div class="modal__footer">
              <button type="button" class="btn btn--secondary" @click="closeAddModal">Cancel</button>
              <button type="submit" class="btn btn--primary" :disabled="formSubmitting">
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
            <button class="btn btn--secondary" @click="showDeleteModal = false">Cancel</button>
            <button class="btn btn--danger" @click="executeDelete">Yes, Delete Account</button>
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
  X as XIcon
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

const closeAddModal = () => {
  showAddModal.value = false
  form.value = { name: '', email: '', password: '' }
  formError.value = null
}

const submitAddModerator = async () => {
  formSubmitting.value = true
  formError.value = null
  try {
    const res = await createModerator(form.value)
    admins.value.push(res.admin)
    closeAddModal()
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
</style>
