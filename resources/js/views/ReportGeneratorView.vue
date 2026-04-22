<template>
  <div class="report-gen" style="display: flex; flex-direction: column; gap: 1.5rem;">
    <!-- Top: Live Preview -->
    <div class="ck-card ck-card--elevated preview-panel" style="width: 100%;">
      <div class="preview-header">
        <h3 class="section-title">Live Preview</h3>
        <span class="ck-badge ck-badge--outline">{{ currentReportLabel }}</span>
      </div>

      <!-- Preview Content -->
      <div class="preview-content custom-scrollbar">
        <div v-if="loadingPreview" class="empty-state" style="padding: 2rem; text-align: center;">
            <p>Loading real-time data preview...</p>
        </div>
        <div v-else-if="previewData.length > 0">
            <div class="table-wrapper" style="overflow-x: auto; max-height: 400px;">
              <table class="ck-table" style="font-size: 0.75rem; white-space: nowrap;">
                <thead>
                  <tr>
                    <th v-for="h in previewHeaders" :key="h">{{ h }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in previewData" :key="idx">
                    <td v-for="(val, colIdx) in Object.values(row)" :key="colIdx">
                      {{ formatCellValue(val) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.75rem;">
              <div class="preview-note" style="margin-top: 0;">
                <InfoIcon :size="16" style="color: var(--ck-blue); flex-shrink: 0;" />
                <span style="font-size: 0.75rem; color: var(--ck-gray-600);">Live snapshot. Click Export to download full dataset.</span>
              </div>
              <span style="font-size: 0.75rem; color: var(--ck-gray-500); font-weight: 500;">Showing top {{ previewData.length }} rows</span>
            </div>
        </div>
        <div v-else class="empty-state" style="padding: 2rem; text-align: center;">
            <p>No data available in this table right now.</p>
        </div>
      </div>
    </div>

    <!-- Bottom: Configuration Panel -->
    <div class="ck-card ck-card--elevated config-panel" style="width: 100%;">
      <h2 class="page-title">Report Configuration</h2>
      <p class="page-subtitle" style="margin-bottom: 1.5rem;">
        Generate & Export Research Reports based on the active preview dataset.
      </p>

      <!-- Report Type Selector -->
      <div class="form-section">
        <div class="report-types" style="display: flex; gap: 0.75rem; overflow-x: auto; padding-bottom: 0.5rem;">
          <button
            v-for="type in reportTypes"
            :key="type.id"
            class="report-type-btn"
            :class="{ 'report-type-btn--active': selectedType === type.id }"
            @click="selectedType = type.id"
            style="flex: 1; min-width: 150px; white-space: nowrap;"
          >
            <component :is="type.icon" :size="20" />
            <span>{{ type.label }}</span>
          </button>
        </div>
      </div>

      <!-- Export Buttons -->
      <div class="export-actions" style="margin-top: 1.5rem; display: flex; gap: 1rem;">
        <button @click="exportCSV" class="ck-btn ck-btn--outline" style="flex: 1;" :disabled="isExporting">
          <TableIcon :size="20" />
          <span>{{ isExporting ? '...' : 'Export CSV' }}</span>
        </button>
        <button @click="exportPDF" class="ck-btn ck-btn--dark" style="flex: 1;" :disabled="isExporting">
          <FileTextIcon :size="20" />
          <span>{{ isExporting ? '...' : 'Export PDF' }}</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, markRaw, onMounted, watch } from 'vue'
import {
  Users,
  Utensils,
  Activity,
  Database,
  Table as TableIcon,
  Info as InfoIcon,
  FileText as FileTextIcon
} from 'lucide-vue-next'
import { getProfiles, getNutritionSummaries, getMealLogs, getActivityLogs } from '../services/api.js'
import { jsPDF } from 'jspdf'
import { autoTable } from 'jspdf-autotable'

const selectedType = ref('profiles')
const isExporting = ref(false)

const reportTypes = [
  { id: 'profiles', label: 'User Profiles', icon: markRaw(Users) },
  { id: 'nutrition', label: 'Daily Nutrition Summaries', icon: markRaw(Database) },
  { id: 'meals', label: 'Meal Logs & Items', icon: markRaw(Utensils) },
  { id: 'activities', label: 'Activity Logs', icon: markRaw(Activity) }
]

const currentReportLabel = computed(() => reportTypes.find(t => t.id === selectedType.value)?.label)

// Preview Logic
const previewData = ref([])
const previewHeaders = ref([])
const loadingPreview = ref(false)

const loadPreview = async () => {
  loadingPreview.value = true
  previewData.value = []
  previewHeaders.value = []
  try {
    let data = []
    if (selectedType.value === 'profiles') data = await getProfiles()
    else if (selectedType.value === 'nutrition') data = await getNutritionSummaries()
    else if (selectedType.value === 'meals') data = await getMealLogs()
    else if (selectedType.value === 'activities') data = await getActivityLogs()

    if (data && data.length > 0) {
      previewHeaders.value = Object.keys(data[0])
      previewData.value = data.slice(0, 5) // top 5 rows
    }
  } catch (err) {
    console.error("Preview load error:", err)
  } finally {
    loadingPreview.value = false
  }
}

onMounted(() => loadPreview())
watch(selectedType, () => loadPreview())

// Format cell values for display
const formatCellValue = (val) => {
  if (val === null || val === undefined) return '—'
  if (Array.isArray(val)) {
    if (val.length === 0) return '—'
    return val.map(item => {
      if (item.dish_name) return `${item.dish_name} (${item.weight_grams ?? 0}g)`
      if (item.name) return item.name
      return JSON.stringify(item)
    }).join(', ')
  }
  if (typeof val === 'object') return JSON.stringify(val)
  return String(val)
}

// CSV Export Logic
const exportCSV = async () => {
  if (isExporting.value) return
  isExporting.value = true

  try {
    let data = []
    let filename = ''

    if (selectedType.value === 'profiles') {
      data = await getProfiles()
      filename = `user_profiles_export_${new Date().toISOString().split('T')[0]}.csv`
    } else if (selectedType.value === 'nutrition') {
      data = await getNutritionSummaries()
      filename = `daily_nutrition_export_${new Date().toISOString().split('T')[0]}.csv`
    } else if (selectedType.value === 'meals') {
      data = await getMealLogs()
      filename = `meal_logs_export_${new Date().toISOString().split('T')[0]}.csv`
    } else if (selectedType.value === 'activities') {
      data = await getActivityLogs()
      filename = `activity_logs_export_${new Date().toISOString().split('T')[0]}.csv`
    }

    if (!data || data.length === 0) {
      alert("No data available to export.")
      isExporting.value = false
      return
    }

    // Convert JSON to CSV String
    const headers = Object.keys(data[0]).join(',')
    const rows = data.map(obj =>
      Object.values(obj).map(val => {
        let str = val !== null && val !== undefined ? (typeof val === 'object' ? JSON.stringify(val) : String(val)) : ''
        // Escape quotes and commas
        if (str.includes(',') || str.includes('"') || str.includes('\n')) {
          str = `"${str.replace(/"/g, '""')}"`
        }
        return str
      }).join(',')
    ).join('\n')

    const csvContent = headers + '\n' + rows

    // Create a Blob and trigger download
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' })
    const url = URL.createObjectURL(blob)
    const link = document.createElement("a")
    link.setAttribute("href", url)
    link.setAttribute("download", filename)
    link.style.visibility = 'hidden'
    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    URL.revokeObjectURL(url)

  } catch (error) {
    console.error("Export failed:", error)
    alert("Failed to export CSV. Please check the console.")
  } finally {
    isExporting.value = false
  }
}

// PDF Export Logic
const exportPDF = async () => {
  if (isExporting.value) return
  isExporting.value = true

  try {
    let data = []
    let filename = ''
    let title = ''

    if (selectedType.value === 'profiles') {
      data = await getProfiles()
      filename = `user_profiles_${new Date().toISOString().split('T')[0]}.pdf`
      title = 'User Profiles Report'
    } else if (selectedType.value === 'nutrition') {
      data = await getNutritionSummaries()
      filename = `daily_nutrition_${new Date().toISOString().split('T')[0]}.pdf`
      title = 'Daily Nutrition Summaries'
    } else if (selectedType.value === 'meals') {
      data = await getMealLogs()
      filename = `meal_logs_${new Date().toISOString().split('T')[0]}.pdf`
      title = 'Meal Logs Report'
    } else if (selectedType.value === 'activities') {
      data = await getActivityLogs()
      filename = `activity_logs_${new Date().toISOString().split('T')[0]}.pdf`
      title = 'Activity Logs Report'
    }

    if (!data || data.length === 0) {
      alert("No data available to export.")
      isExporting.value = false
      return
    }

    const doc = new jsPDF('landscape')
    doc.text(title, 14, 15)
    
    const headers = Object.keys(data[0])
    const rows = data.map(obj =>
      Object.values(obj).map(val => {
        if (val === null || val === undefined) return ''
        if (Array.isArray(val)) {
          return val.map(item => item.dish_name || item.name || JSON.stringify(item)).join(', ')
        }
        if (typeof val === 'object') return JSON.stringify(val)
        return String(val)
      })
    )

    autoTable(doc, {
      head: [headers],
      body: rows,
      startY: 20,
      styles: { fontSize: 8 },
      headStyles: { fillColor: [16, 185, 129] } // ck-primary color
    })
    
    doc.save(filename)

  } catch (error) {
    console.error("PDF Export failed:", error)
    alert("Failed to export PDF. Please check the console.")
  } finally {
    isExporting.value = false
  }
}
</script>

<style scoped>
.report-gen { display: flex; flex-direction: column; gap: 1.5rem; }

.report-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
@media (max-width: 1024px) { .report-layout { grid-template-columns: 1fr; } }

.page-title { font-size: 1.5rem; font-weight: 600; color: var(--ck-gray-900); }
.page-subtitle { font-size: 0.875rem; color: var(--ck-gray-600); }
.section-title { font-size: 1.125rem; font-weight: 600; color: var(--ck-gray-900); }

.form-section { margin-bottom: 1.5rem; }
.form-label { font-size: 0.875rem; font-weight: 500; color: var(--ck-gray-700); display: block; margin-bottom: 0.5rem; }

.report-types { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
.report-type-btn {
  display: flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1rem;
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg); font-size: 0.8125rem; font-weight: 500;
  color: var(--ck-gray-700); transition: all var(--ck-transition-fast); cursor: pointer;
}
.report-type-btn:hover { border-color: var(--ck-primary-border); background: var(--ck-primary-light); }
.report-type-btn--active {
  background: var(--ck-primary-light) !important; border-color: var(--ck-primary) !important;
  color: var(--ck-primary) !important;
}

.date-inputs { display: flex; align-items: center; gap: 0.5rem; }
.date-sep { font-size: 0.875rem; color: var(--ck-gray-500); }

.export-actions { display: flex; gap: 0.75rem; }

.preview-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }

.preview-content { max-height: 600px; overflow-y: auto; }

.preview-meta {
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg); padding: 1rem;
}
.preview-meta__row {
  display: flex; justify-content: space-between; font-size: 0.8125rem;
  color: var(--ck-gray-600); padding: 0.25rem 0;
}
.preview-meta__row strong { color: var(--ck-gray-900); }

.preview-divider { height: 1px; background: var(--ck-gray-200); margin: 1.5rem 0; }

.preview-section-title { font-size: 0.875rem; font-weight: 600; color: var(--ck-gray-800); margin-bottom: 0.75rem; }

.table-wrapper {
  background: var(--ck-gray-50); border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg); overflow: hidden;
}

.preview-note {
  display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;
  font-size: 0.75rem; color: var(--ck-gray-600);
}
</style>
