<template>
  <div class="food-db">
    <div class="ck-card ck-card--elevated">
      <div class="db-header">
        <div>
          <h2 class="page-title">Food Database (PhilFCT)</h2>
          <p class="page-subtitle">Manage macro-nutritional values per 100g and class labels</p>
        </div>
        <div class="header-actions">
          <button @click="openImportModal" class="ck-btn ck-btn--outline" style="display: flex; align-items: center; gap: 0.5rem;">
            <UploadIcon :size="16" /> Import CSV
          </button>
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
          <option value="Main Dish">Main Dish</option>
          <option value="Soup">Soup</option>
          <option value="Fish">Fish</option>
          <option value="Seafood">Seafood</option>
          <option value="Meat">Meat</option>
          <option value="Egg">Egg</option>
          <option value="Vegetable Dish">Vegetable Dish</option>
          <option value="Rice">Rice</option>
          <option value="Noodles">Noodles</option>
          <option value="Snacks">Snacks</option>
          <option value="Street Food">Street Food</option>
          <option value="Fruits">Fruits</option>
          <option value="Vegetables">Vegetables</option>
          <option value="Beverages">Beverages</option>
          <option value="Bread">Bread</option>
          <option value="Dairy">Dairy</option>
          <option value="Dessert">Dessert</option>
          <option value="Condiment">Condiment</option>
        </select>
      </div>

      <!-- Stats Bar -->
      <div v-if="!loading && foods.length > 0" class="stats-bar">
        <span class="stats-chip">📊 {{ foods.length }} total items</span>
        <span class="stats-chip">🇵🇭 {{ fnriCount }} FNRI</span>
        <span class="stats-chip">🇺🇸 {{ usdaCount }} USDA</span>
        <span class="stats-chip">🤖 {{ mlCount }} ML-labeled</span>
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
                <th>ML Label</th>
                <th style="text-align: center;">Calories / 100g</th>
                <th style="text-align: center;">Protein / 100g</th>
                <th style="text-align: center;">Carbs / 100g</th>
                <th style="text-align: center;">Fat / 100g</th>
                <th>Source</th>
                <th style="text-align: center;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in filteredFoods" :key="item.food_id">
                <td style="font-weight: 500; color: var(--ck-gray-900);">{{ item.name_en }}</td>
                <td>{{ item.name_ph }}</td>
                <td><span class="ck-badge ck-badge--outline">{{ item.category }}</span></td>
                <td>
                  <span class="ml-label-chip" :class="item.ml_label === 'manual_entry' ? 'ml-label-chip--manual' : 'ml-label-chip--ai'">
                    {{ item.ml_label || 'manual_entry' }}
                  </span>
                </td>
                <td style="text-align: center; font-weight: 600;">{{ item.calories_per_100g }}</td>
                <td style="text-align: center;">{{ item.protein_per_100g }}g</td>
                <td style="text-align: center;">{{ item.carbs_per_100g }}g</td>
                <td style="text-align: center;">{{ item.fat_per_100g }}g</td>
                <td>
                  <span class="source-chip" :class="sourceClass(item.data_source)">
                    {{ item.data_source || 'DOST_FNRI_FCT' }}
                  </span>
                </td>
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

    <!-- Add/Edit Modal (Full Nutrient Profile) -->
    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay">
        <div class="modal animate-fade-in" style="max-width: 640px; max-height: 90vh; display: flex; flex-direction: column;">
          <div class="modal__header">
            <h3>{{ isEditMode ? 'Edit Food Item' : 'Add New Food Item' }}</h3>
            <button @click="closeModal" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <div class="modal__body" style="overflow-y: auto; flex: 1;">
            <form @submit.prevent="submitForm">
              <div class="form-grid">
                <!-- Identity Section -->
                <div class="form-section-title" style="grid-column: span 2;">Identity</div>
                <div class="form-group" style="grid-column: span 2;">
                  <label class="form-label">English Name (ML Class Label)</label>
                  <input v-model="formData.name_en" type="text" class="ck-input" required placeholder="e.g., Chicken Adobo" />
                </div>
                <div class="form-group">
                  <label class="form-label">Local Name (PH)</label>
                  <input v-model="formData.name_ph" type="text" class="ck-input" required placeholder="e.g., Adobong Manok" />
                </div>
                <div class="form-group">
                  <label class="form-label">Category</label>
                  <select v-model="formData.category" class="ck-select" required>
                    <option value="" disabled>Select category</option>
                    <option value="Meals">Meals</option>
                    <option value="Main Dish">Main Dish</option>
                    <option value="Soup">Soup</option>
                    <option value="Fish">Fish</option>
                    <option value="Seafood">Seafood</option>
                    <option value="Meat">Meat</option>
                    <option value="Egg">Egg</option>
                    <option value="Vegetable Dish">Vegetable Dish</option>
                    <option value="Rice">Rice</option>
                    <option value="Noodles">Noodles</option>
                    <option value="Snacks">Snacks</option>
                    <option value="Street Food">Street Food</option>
                    <option value="Fruits">Fruits</option>
                    <option value="Vegetables">Vegetables</option>
                    <option value="Beverages">Beverages</option>
                    <option value="Bread">Bread</option>
                    <option value="Dairy">Dairy</option>
                    <option value="Dessert">Dessert</option>
                    <option value="Condiment">Condiment</option>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">ML Label</label>
                  <input v-model="formData.ml_label" type="text" class="ck-input" placeholder="manual_entry" />
                </div>
                <div class="form-group">
                  <label class="form-label">Data Source</label>
                  <select v-model="formData.data_source" class="ck-select">
                    <option value="DOST_FNRI_FCT">DOST_FNRI_FCT</option>
                    <option value="DOST_FNRI_MENU_GUIDE">DOST_FNRI_MENU_GUIDE</option>
                    <option value="USDA_FNDDS">USDA_FNDDS</option>
                    <option value="USDA_SR_LEGACY">USDA_SR_LEGACY</option>
                  </select>
                </div>

                <!-- Energy Section -->
                <div class="form-section-title" style="grid-column: span 2;">Energy (per 100g)</div>
                <div class="form-group">
                  <label class="form-label">Calories (kcal)</label>
                  <input v-model.number="formData.calories_per_100g" type="number" step="0.1" class="ck-input" required />
                </div>

                <!-- Macronutrients Section -->
                <div class="form-section-title" style="grid-column: span 2;">Macronutrients (g per 100g)</div>
                <div class="form-group">
                  <label class="form-label">Protein</label>
                  <input v-model.number="formData.protein_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Carbohydrates</label>
                  <input v-model.number="formData.carbs_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Total Fat</label>
                  <input v-model.number="formData.fat_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Fiber</label>
                  <input v-model.number="formData.fiber_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Sugar</label>
                  <input v-model.number="formData.sugar_per_100g" type="number" step="0.01" class="ck-input" />
                </div>

                <!-- Fat Details Section -->
                <div class="form-section-title" style="grid-column: span 2;">Fat Breakdown (g per 100g)</div>
                <div class="form-group">
                  <label class="form-label">Saturated Fat</label>
                  <input v-model.number="formData.saturated_fat_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Polyunsat. Fat</label>
                  <input v-model.number="formData.polyunsaturated_fat_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Monounsat. Fat</label>
                  <input v-model.number="formData.monounsaturated_fat_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Trans Fat</label>
                  <input v-model.number="formData.trans_fat_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Cholesterol (mg)</label>
                  <input v-model.number="formData.cholesterol_per_100g" type="number" step="0.01" class="ck-input" />
                </div>

                <!-- Minerals & Vitamins Section -->
                <div class="form-section-title" style="grid-column: span 2;">Minerals &amp; Vitamins (per 100g)</div>
                <div class="form-group">
                  <label class="form-label">Sodium (mg)</label>
                  <input v-model.number="formData.sodium_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Potassium (mg)</label>
                  <input v-model.number="formData.potassium_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Vitamin A (µg)</label>
                  <input v-model.number="formData.vitamin_a_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Vitamin C (mg)</label>
                  <input v-model.number="formData.vitamin_c_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Calcium (mg)</label>
                  <input v-model.number="formData.calcium_per_100g" type="number" step="0.01" class="ck-input" />
                </div>
                <div class="form-group">
                  <label class="form-label">Iron (mg)</label>
                  <input v-model.number="formData.iron_per_100g" type="number" step="0.01" class="ck-input" />
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

    <!-- Bulk Import Modal -->
    <Teleport to="body">
      <div v-if="showImportModal" class="modal-overlay">
        <div class="modal animate-fade-in" style="max-width: 520px;">
          <div class="modal__header">
            <h3>Bulk Import Food Items (CSV)</h3>
            <button @click="closeImportModal" class="modal__close"><XIcon :size="20" /></button>
          </div>
          <div class="modal__body">
            <div class="import-info">
              <p style="font-size: 0.875rem; color: var(--ck-gray-600); margin-bottom: 1rem;">
                Upload a <strong>.csv</strong> or <strong>.xlsx</strong> file with the PhilFCT schema. The first row must be a header row.
              </p>
              <div class="csv-schema-box">
                <code style="font-size: 0.7rem; word-break: break-all; line-height: 1.4;">
                  ml_label, name_en, name_ph, category, calories_kcal, protein_g,
                  carbs_g, fat_g, fiber_g, sugar_g, saturated_fat_g,
                  polyunsaturated_fat_g, monounsaturated_fat_g, trans_fat_g,
                  cholesterol_mg, sodium_mg, potassium_mg, vitamin_a_mcg,
                  vitamin_c_mg, calcium_mg, iron_mg, data_source
                </code>
              </div>
              <div class="file-upload-zone" @dragover.prevent @drop.prevent="handleDrop" @click="triggerFileInput">
                <UploadIcon :size="32" style="color: var(--ck-gray-400); margin-bottom: 0.5rem;" />
                <p v-if="!importFile" style="font-size: 0.875rem; color: var(--ck-gray-500);">
                  Click to select or drag &amp; drop a .csv or .xlsx file
                </p>
                <p v-else style="font-size: 0.875rem; color: var(--ck-primary); font-weight: 600;">
                  📄 {{ importFile.name }} ({{ (importFile.size / 1024).toFixed(1) }} KB)
                </p>
                <input ref="csvFileInput" type="file" accept=".csv,.txt,.xlsx,.xls" @change="handleFileSelect" style="display: none;" />
              </div>
              <div v-if="importResult" class="import-result" :class="importResult.skipped > 0 ? 'import-result--warn' : 'import-result--ok'">
                <p><strong>{{ importResult.message }}</strong></p>
                <ul v-if="importResult.errors && importResult.errors.length > 0" style="margin-top: 0.5rem; font-size: 0.75rem;">
                  <li v-for="(err, i) in importResult.errors" :key="i">{{ err }}</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="modal__footer">
            <button class="btn btn--secondary" @click="closeImportModal">Cancel</button>
            <button class="btn btn--primary" @click="submitImport" :disabled="!importFile || isImporting">
              {{ isImporting ? 'Importing...' : 'Upload & Import' }}
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
  X as XIcon,
  Upload as UploadIcon
} from 'lucide-vue-next'
import { getFoods, createFood, updateFood, deleteFood, bulkImportFoods } from '../services/api.js'

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

// Helper: build empty form data with all fields
function emptyFormData() {
  return {
    food_id: null,
    name_en: '',
    name_ph: '',
    category: '',
    ml_label: 'manual_entry',
    data_source: 'DOST_FNRI_FCT',
    calories_per_100g: 0,
    protein_per_100g: 0,
    carbs_per_100g: 0,
    fat_per_100g: 0,
    fiber_per_100g: 0,
    sugar_per_100g: 0,
    saturated_fat_per_100g: 0,
    polyunsaturated_fat_per_100g: 0,
    monounsaturated_fat_per_100g: 0,
    trans_fat_per_100g: 0,
    cholesterol_per_100g: 0,
    sodium_per_100g: 0,
    potassium_per_100g: 0,
    vitamin_a_per_100g: 0,
    vitamin_c_per_100g: 0,
    calcium_per_100g: 0,
    iron_per_100g: 0
  }
}

// Modals
const showModal = ref(false)
const isEditMode = ref(false)
const isSubmitting = ref(false)
const formData = ref(emptyFormData())

const deleteModal = ref({ show: false, item: null })
const isDeleting = ref(false)

// Import Modal
const showImportModal = ref(false)
const importFile = ref(null)
const isImporting = ref(false)
const importResult = ref(null)
const csvFileInput = ref(null)

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

const fnriCount = computed(() => foods.value.filter(f => (f.data_source || '').includes('FNRI')).length)
const usdaCount = computed(() => foods.value.filter(f => (f.data_source || '').includes('USDA')).length)
const mlCount = computed(() => foods.value.filter(f => f.ml_label && f.ml_label !== 'manual_entry').length)

// Source tag styling
const sourceClass = (source) => {
  if (!source) return 'source-chip--fnri'
  if (source.includes('USDA')) return 'source-chip--usda'
  return 'source-chip--fnri'
}

// Add/Edit Triggers
// Keep a snapshot of the original form state so we can detect unsaved changes
const originalFormData = ref(emptyFormData())

const openAddModal = () => {
  isEditMode.value = false
  formData.value = emptyFormData()
  originalFormData.value = emptyFormData()
  showModal.value = true
}

const openEditModal = (item) => {
  isEditMode.value = true
  formData.value = { ...item }
  originalFormData.value = { ...item }
  showModal.value = true
}

const hasUnsavedChanges = () => {
  const empty = emptyFormData()
  // In edit mode, compare against the original item; in add mode, compare against empty defaults
  const baseline = isEditMode.value ? originalFormData.value : empty
  return Object.keys(empty).some(key => {
    return String(formData.value[key] ?? '') !== String(baseline[key] ?? '')
  })
}

const closeModal = () => {
  if (hasUnsavedChanges() && !confirm('You have unsaved changes. Are you sure you want to discard them?')) {
    return
  }
  showModal.value = false
}

// Import Triggers
const openImportModal = () => {
  importFile.value = null
  importResult.value = null
  showImportModal.value = true
}

const closeImportModal = () => {
  if (importFile.value && !importResult.value && !confirm('You have a file selected. Are you sure you want to close?')) {
    return
  }
  showImportModal.value = false
  importFile.value = null
  importResult.value = null
}

const triggerFileInput = () => {
  csvFileInput.value?.click()
}

const handleFileSelect = (e) => {
  const file = e.target.files[0]
  if (file) importFile.value = file
}

const handleDrop = (e) => {
  const file = e.dataTransfer.files[0]
  if (file && (file.name.endsWith('.csv') || file.name.endsWith('.txt') || file.name.endsWith('.xlsx') || file.name.endsWith('.xls'))) {
    importFile.value = file
  }
}

const submitImport = async () => {
  if (!importFile.value) return
  isImporting.value = true
  importResult.value = null
  try {
    const result = await bulkImportFoods(importFile.value)
    importResult.value = result
    showToast(`Imported ${result.imported} food items successfully.`)
    await fetchFoodsList()
  } catch (err) {
    importResult.value = { message: err.message, imported: 0, skipped: 0, errors: [] }
    showToast(err.message, 'error')
  } finally {
    isImporting.value = false
  }
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
.header-actions { display: flex; gap: 0.75rem; align-items: center; }

.filters-bar { display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap; align-items: center; }
.search-wrapper { position: relative; flex: 1; min-width: 250px; }
.search-icon { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--ck-gray-400); pointer-events: none; }

/* Stats Bar */
.stats-bar { display: flex; gap: 0.75rem; margin-bottom: 1rem; flex-wrap: wrap; }
.stats-chip {
  display: inline-flex; align-items: center; gap: 0.25rem;
  padding: 0.25rem 0.75rem; border-radius: 999px;
  font-size: 0.75rem; font-weight: 500;
  background: var(--ck-gray-100); color: var(--ck-gray-600);
}

.table-wrapper { background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200); border-radius: var(--ck-radius-lg); overflow-x: auto; }
.empty-state { text-align: center; padding: 3rem; color: var(--ck-gray-500); font-size: 0.875rem; }

.action-btn { padding: 0.375rem; color: var(--ck-gray-500); border-radius: var(--ck-radius-md); transition: all var(--ck-transition-fast); background: transparent; border: none; cursor: pointer; }
.action-btn:hover { background: var(--ck-gray-100); color: var(--ck-gray-800); }
.action-btn--danger:hover { background: #fee2e2; color: #dc2626; }

/* ML Label Chip */
.ml-label-chip {
  display: inline-block; padding: 0.125rem 0.5rem; border-radius: 999px;
  font-size: 0.6875rem; font-weight: 600; font-family: monospace;
}
.ml-label-chip--manual { background: var(--ck-gray-100); color: var(--ck-gray-500); }
.ml-label-chip--ai { background: #dbeafe; color: #1d4ed8; }

/* Source Chip */
.source-chip {
  display: inline-block; padding: 0.125rem 0.5rem; border-radius: 999px;
  font-size: 0.6875rem; font-weight: 600;
}
.source-chip--fnri { background: #dcfce7; color: #166534; }
.source-chip--usda { background: #dbeafe; color: #1e40af; }

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
.form-section-title {
  font-size: 0.8125rem; font-weight: 600; color: var(--ck-gray-400);
  text-transform: uppercase; letter-spacing: 0.05em;
  border-bottom: 1px solid var(--ck-gray-200); padding-bottom: 0.25rem;
  margin-top: 0.5rem;
}

.btn { padding: 0.5rem 1rem; font-size: 0.875rem; font-weight: 500; border-radius: var(--ck-radius-md); cursor: pointer; transition: all 0.2s; border: 1px solid transparent; }
.btn--secondary { background: white; border-color: var(--ck-gray-300); color: var(--ck-gray-700); }
.btn--secondary:hover { background: var(--ck-gray-50); }
.btn--primary { background: var(--ck-primary); color: white; }
.btn--primary:hover { filter: brightness(1.1); }
.btn--primary:disabled { opacity: 0.7; cursor: not-allowed; }
.btn--danger { background: #ef4444; color: white; }
.btn--danger:hover { background: #dc2626; }

/* Import Modal Extras */
.csv-schema-box {
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-md); padding: 0.75rem;
  margin-bottom: 1rem;
}
.file-upload-zone {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  padding: 2rem; border: 2px dashed var(--ck-gray-300); border-radius: var(--ck-radius-lg);
  cursor: pointer; transition: all 0.2s; margin-bottom: 1rem;
}
.file-upload-zone:hover { border-color: var(--ck-primary); background: var(--ck-gray-50); }
.import-result {
  padding: 0.75rem; border-radius: var(--ck-radius-md);
  font-size: 0.8125rem;
}
.import-result--ok { background: #dcfce7; color: #166534; }
.import-result--warn { background: #fef3c7; color: #92400e; }

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
