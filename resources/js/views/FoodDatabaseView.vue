<template>
  <div class="food-db">
    <div class="ck-card ck-card--elevated">
      <div class="db-header">
        <div>
          <h2 class="page-title">Food Database (PhilFCT)</h2>
          <p class="page-subtitle">Manage macro-nutritional values per 100g and class labels</p>
        </div>
        <div class="header-actions">
          <button @click="openAddModal" class="ck-btn ck-btn--primary" style="display: flex; align-items: center; gap: 0.5rem;">
            <PlusIcon :size="16" /> Add New Food
          </button>
        </div>
      </div>

      <!-- Search & Filters -->
      <div class="filters-bar">
        <div class="search-wrapper">
          <SearchIcon :size="16" class="search-icon" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search by English or local name..."
            class="ck-input ck-input--with-icon"
          />
        </div>
        <select v-model="categoryFilter" class="ck-select" style="width: auto;">
          <option value="all">All Categories</option>
          <option value="Meals">Meals</option>
          <option value="Snacks">Snacks</option>
          <option value="Fruits">Fruits</option>
          <option value="Vegetables">Vegetables</option>
          <option value="Beverages">Beverages</option>
        </select>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="empty-state">
        <p>Loading food database...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="empty-state" style="color: var(--ck-red-500);">
        <p>{{ error }}</p>
        <button @click="fetchFoodsList" class="ck-btn ck-btn--outline mt-2">Try Again</button>
      </div>

      <!-- Data Table -->
      <template v-else>
        <div class="table-wrapper" v-if="filteredFoods.length > 0">
          <table class="ck-table">
            <thead>
              <tr>
                <th>Food Name (EN)</th>
                <th>Local Name (PH)</th>
                <th>Category</th>
                <th style="text-align: center;">Calories / 100g</th>
                <th style="text-align: center;">Protein / 100g</th>
                <th style="text-align: center;">Carbs / 100g</th>
                <th style="text-align: center;">Fat / 100g</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredFoods" :key="item.food_id">
                <td style="font-weight: 500; color: var(--ck-gray-900);">{{ item.name_en }}</td>
                <td>{{ item.name_ph }}</td>
                <td><span class="ck-badge ck-badge--outline">{{ item.category }}</span></td>
                <td style="text-align: center; font-weight: 600;">{{ item.calories_per_100g }}</td>
                <td style="text-align: center;">{{ item.protein_per_100g }}g</td>
                <td style="text-align: center;">{{ item.carbs_per_100g }}g</td>
                <td style="text-align: center;">{{ item.fat_per_100g }}g</td>
                <td style="text-align: center;">
                  <button class="action-btn" @click="openEditModal(item)" title="Edit">
                    <EditIcon :size="16" />
                  </button>
                  <button class="action-btn action-btn--danger" @click="openDeleteModal(item)" title="Delete">
                    <TrashIcon :size="16" />
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="empty-state">
          <p>No food items found matching your search.</p>
        </div>
      </template>
    </div>

    <!-- Add/Edit Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
        <div class="modal animate-fade-in" style="max-width: 500px;">
          <div class="modal__header">
            <h3>{{ isEditMode ? 'Edit Food Item' : 'Add New Food Item' }}</h3>
            <button @click="closeModal" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <div class="modal__body">
            <form @submit.prevent="submitForm">
              <div class="form-grid">
                <div class="form-group" style="grid-column: span 2;">
                  <label class="form-label">English Name (ML Class Label)</label>
                  <input v-model="formData.name_en" type="text" class="ck-input" required placeholder="e.g., Chicken Adobo" />
                </div>
                <div class="form-group" style="grid-column: span 2;">
                  <label class="form-label">Local Name (PH)</label>
                  <input v-model="formData.name_ph" type="text" class="ck-input" required placeholder="e.g., Adobong Manok" />
                </div>
                <div class="form-group" style="grid-column: span 2;">
                  <label class="form-label">Category</label>
                  <select v-model="formData.category" class="ck-select" required>
                    <option value="" disabled>Select category</option>
                    <option value="Meals">Meals</option>
                    <option value="Snacks">Snacks</option>
                    <option value="Fruits">Fruits</option>
                    <option value="Vegetables">Vegetables</option>
                    <option value="Beverages">Beverages</option>
                  </select>
                </div>
                
                <div class="form-horizontal-divider" style="grid-column: span 2; margin-top: 0.5rem;">
                   <h4 style="font-size: 0.875rem; color: var(--ck-gray-500); border-bottom: 1px solid var(--ck-gray-200); padding-bottom: 0.25rem;">Nutritional Values (per 100g)</h4>
                </div>
                
                <div class="form-group">
                  <label class="form-label">Calories (kcal)</label>
                  <input v-model.number="formData.calories_per_100g" type="number" step="0.1" class="ck-input" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Protein (g)</label>
                  <input v-model.number="formData.protein_per_100g" type="number" step="0.1" class="ck-input" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Carbs (g)</label>
                  <input v-model.number="formData.carbs_per_100g" type="number" step="0.1" class="ck-input" required />
                </div>
                <div class="form-group">
                  <label class="form-label">Fat (g)</label>
                  <input v-model.number="formData.fat_per_100g" type="number" step="0.1" class="ck-input" required />
                </div>
              </div>
            </form>
          </div>
          <div class="modal__footer">
            <button type="button" class="btn btn--secondary" @click="closeModal">Cancel</button>
            <button type="button" class="btn btn--primary" @click="submitForm" :disabled="isSubmitting">
              {{ isSubmitting ? 'Saving...' : (isEditMode ? 'Update Item' : 'Save Item') }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="deleteModal.show" class="modal-overlay" @click.self="closeDeleteModal">
        <div class="modal animate-fade-in">
          <div class="modal__header">
            <h3 style="color: #ef4444;">Delete Food Item</h3>
          </div>
          <div class="modal__body">
            <p>Are you sure you want to delete <strong>{{ deleteModal.item?.name_en }}</strong> from the database?</p>
            <p style="margin-top: 0.5rem; font-size: 0.8125rem; color: #ef4444;">
              This will permanently remove it from the system and may affect un-synced meal logs relying on this ID.
            </p>
          </div>
          <div class="modal__footer">
            <button class="btn btn--secondary" @click="closeDeleteModal">Cancel</button>
            <button class="btn btn--danger" @click="confirmDelete" :disabled="isDeleting">
              {{ isDeleting ? 'Deleting...' : 'Yes, Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
    <!-- Toast Notification -->
    <Teleport to="body">
      <Transition name="toast-slide">
        <div v-if="toast.show" class="ck-toast" :class="`ck-toast--${toast.type}`">
          <span>{{ toast.message }}</span>
          <button @click="toast.show = false" class="toast-close"><XIcon :size="14" /></button>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import {
  Search as SearchIcon,
  Plus as PlusIcon,
  Edit as EditIcon,
  Trash as TrashIcon,
  X as XIcon
} from 'lucide-vue-next'
import { getFoods, createFood, updateFood, deleteFood } from '../services/api.js'

// State
const foods = ref([])
const loading = ref(true)
const error = ref(null)

const searchQuery = ref('')
const categoryFilter = ref('all')

// Toast State
const toast = ref({ show: false, message: '', type: 'success' })
const showToast = (message, type = 'success') => {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 4000)
}

// Modals
const showModal = ref(false)
const isEditMode = ref(false)
const isSubmitting = ref(false)
const formData = ref({
  food_id: null,
  name_en: '',
  name_ph: '',
  category: '',
  calories_per_100g: 0,
  protein_per_100g: 0,
  carbs_per_100g: 0,
  fat_per_100g: 0
})

const deleteModal = ref({ show: false, item: null })
const isDeleting = ref(false)

// Init API Call
const fetchFoodsList = async () => {
  loading.value = true
  error.value = null
  try {
    const data = await getFoods()
    foods.value = data
  } catch (err) {
    console.error("Failed to fetch food database:", err)
    error.value = "Failed to load food database. Is the API running?"
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchFoodsList()
})

// Computeds
const filteredFoods = computed(() => {
  return foods.value.filter(item => {
    const searchMatch = item.name_en.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                        item.name_ph.toLowerCase().includes(searchQuery.value.toLowerCase())
    const catMatch = categoryFilter.value === 'all' || item.category === categoryFilter.value
    return searchMatch && catMatch
  })
})

// Add/Edit Triggers
const openAddModal = () => {
  isEditMode.value = false
  formData.value = {
    food_id: null,
    name_en: '',
    name_ph: '',
    category: '',
    calories_per_100g: 0,
    protein_per_100g: 0,
    carbs_per_100g: 0,
    fat_per_100g: 0
  }
  showModal.value = true
}

const openEditModal = (item) => {
  isEditMode.value = true
  formData.value = { ...item } // Clone object
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
}

// Delete Triggers
const openDeleteModal = (item) => {
  deleteModal.value = { show: true, item }
}

const closeDeleteModal = () => {
  deleteModal.value = { show: false, item: null }
}

// Form Submissions
const submitForm = async () => {
  if (!formData.value.name_en || !formData.value.category) return

  isSubmitting.value = true
  try {
    if (isEditMode.value) {
      const updated = await updateFood(formData.value.food_id, formData.value)
      const index = foods.value.findIndex(f => f.food_id === updated.food_id)
      if (index !== -1) foods.value[index] = updated
      showToast("Food item updated successfully.")
    } else {
      const created = await createFood(formData.value)
      foods.value.push(created)
      showToast("Food item created successfully.")
    }
    closeModal()
  } catch (err) {
    showToast(err.message || "An error occurred while saving the food item.", 'error')
    console.error(err)
  } finally {
    isSubmitting.value = false
  }
}

const confirmDelete = async () => {
  if (!deleteModal.value.item) return
  isDeleting.value = true
  try {
    const id = deleteModal.value.item.food_id
    await deleteFood(id)
    foods.value = foods.value.filter(f => f.food_id !== id)
    closeDeleteModal()
    showToast("Food item successfully deleted.")
  } catch (err) {
    showToast(err.message || "An error occurred while deleting.", 'error')
    console.error(err)
  } finally {
    isDeleting.value = false
  }
}
</script>

<style scoped>
.food-db { display: flex; flex-direction: column; gap: 1.5rem; }
.db-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem; }
.page-title { font-size: 1.5rem; font-weight: 600; color: var(--ck-gray-900); }
.page-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }

.filters-bar { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
.search-wrapper { position: relative; flex: 1; min-width: 250px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--ck-gray-400); pointer-events: none; }

.table-wrapper { background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200); border-radius: var(--ck-radius-lg); overflow-x: auto; }
.empty-state { text-align: center; padding: 3rem; color: var(--ck-gray-500); font-size: 0.875rem; }

.action-btn { padding: 0.375rem; color: var(--ck-gray-500); border-radius: var(--ck-radius-md); transition: all var(--ck-transition-fast); background: transparent; border: none; cursor: pointer; }
.action-btn:hover { background: var(--ck-gray-100); color: var(--ck-gray-800); }
.action-btn--danger:hover { background: #fee2e2; color: #dc2626; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.5); display: flex; align-items: center; justify-content: center; z-index: 10000; }
.modal { background: white; border-radius: var(--ck-radius-xl); width: 100%; max-width: 500px; box-shadow: var(--ck-shadow-2xl); overflow: hidden; }
.modal__header { display: flex; justify-content: space-between; align-items: center; padding: 1.5rem; border-bottom: 1px solid var(--ck-gray-200); }
.modal__header h3 { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-900); }
.modal__close { color: var(--ck-gray-400); padding: 0.25rem; border-radius: var(--ck-radius-md); background: transparent; border: none; cursor: pointer; }
.modal__close:hover { background: var(--ck-gray-100); color: var(--ck-gray-700); }
.modal__body { padding: 1.5rem; }
.modal__footer { padding: 1rem 1.5rem; background: var(--ck-gray-50); display: flex; justify-content: flex-end; gap: 0.75rem; border-top: 1px solid var(--ck-gray-200); }

.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { display: flex; flex-direction: column; gap: 0.5rem; }
.form-label { font-size: 0.8125rem; font-weight: 500; color: var(--ck-gray-700); }

.btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: var(--ck-radius-md); cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
.btn--secondary { background: white; border-color: var(--ck-gray-300); color: var(--ck-gray-700); }
.btn--secondary:hover { background: var(--ck-gray-50); }
.btn--primary { background: var(--ck-primary); color: white; }
.btn--primary:hover { filter: brightness(1.1); }
.btn--primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn--primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn--danger { background: #ef4444; color: white; }
.btn--danger:hover { background: #dc2626; }

/* Toast */
.ck-toast { position: fixed; bottom: 2rem; right: 2rem; padding: 1rem 1.25rem; border-radius: var(--ck-radius-md); background: white; box-shadow: var(--ck-shadow-2xl); display: flex; align-items: center; gap: 0.75rem; font-size: 0.875rem; font-weight: 500; z-index: 10001; }
.ck-toast--success { border-left: 4px solid #10b981; color: var(--ck-gray-900); }
.ck-toast--error { border-left: 4px solid #ef4444; color: var(--ck-gray-900); }
.toast-close { background: transparent; border: none; cursor: pointer; color: var(--ck-gray-400); padding: 0.125rem; display: flex; align-items: center; margin-left: auto; }
.toast-close:hover { color: var(--ck-gray-700); }
.toast-slide-enter-active, .toast-slide-leave-active { transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
.toast-slide-enter-from { opacity: 0; transform: translateY(1rem) scale(0.95); }
.toast-slide-leave-to { opacity: 0; transform: translateY(1rem) scale(0.95); }
</style>
