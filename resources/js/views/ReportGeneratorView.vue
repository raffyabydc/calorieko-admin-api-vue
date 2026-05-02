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
              <table class="ck-table" style="font-size: 0.75rem;">
                <thead>
                  <tr>
                    <th v-for="h in previewHeaders" :key="h">{{ h }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in previewData" :key="idx">
                    <td v-for="header in previewHeaders" :key="header">
                      <template v-if="header === 'uid'">
                        <span :title="row[header]" class="font-mono" style="cursor: help;">
                          {{ truncateUid(row[header]) }}
                        </span>
                      </template>
                      <template v-else-if="['is_active', 'is_engaged', 'has_recent_activity'].includes(header)">
                        <div class="boolean-badge" :class="row[header] ? 'boolean-badge--true' : 'boolean-badge--false'">
                          <span class="ck-dot" :class="row[header] ? 'ck-dot--green' : 'ck-dot--gray'"></span>
                          <span>{{ row[header] ? 'True' : 'False' }}</span>
                        </div>
                      </template>
                      <template v-else>
                        {{ formatCellValue(row[header], header) }}
                      </template>
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

// Format date strings or epoch timestamps to YYYY-MM-DD HH:mm
// Uses the key name to avoid accidentally formatting non-date numbers (like age or calories)
const formatDate = (val, key = '') => {
  if (!val) return val
  let date = null
  const sVal = String(val)
  const isDateKey = /date|time|updated|created/i.test(key)

  if (typeof val === 'string' && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(val)) {
    date = new Date(val)
  } else if (isDateKey && /^\d+$/.test(sVal)) {
    const num = Number(val)
    if (key === 'date_epoch_day') {
       date = new Date(num * 86400 * 1000)
    } else if (num > 1e9) { // At least 10 digits for epoch seconds
       date = new Date(num > 1e11 ? num : num * 1000)
    }
  }

  if (date && !isNaN(date.getTime())) {
    const y = date.getFullYear()
    const m = String(date.getMonth() + 1).padStart(2, '0')
    const d = String(date.getDate()).padStart(2, '0')
    const hh = String(date.getHours()).padStart(2, '0')
    const mm = String(date.getMinutes()).padStart(2, '0')
    return `${y}-${m}-${d} ${hh}:${mm}`
  }
  return val
}

// Truncate long UID strings for display (e.g. deK24...vr2)
const truncateUid = (uid) => {
  if (!uid || typeof uid !== 'string') return uid
  if (uid.length <= 10) return uid
  return `${uid.slice(0, 5)}...${uid.slice(-3)}`
}

// PDF Column Whitelist & Labels for better readability
const pdfColumnConfig = {
  profiles: ['uid', 'display_id', 'age', 'sex', 'weight', 'height', 'activityLevel', 'goal', 'is_active'],
  nutrition: ['uid', 'date_epoch_day', 'total_calories', 'total_protein', 'total_carbs', 'total_fat', 'total_sodium'],
  meals: ['uid', 'meal_type', 'timestamp', 'notes', 'items'],
  activities: ['uid', 'name', 'type', 'timeString', 'weightOrDuration', 'calories', 'steps']
}

const columnLabels = {
  uid: 'User ID',
  display_id: 'ID',
  age: 'Age',
  sex: 'Sex',
  weight: 'Weight',
  height: 'Height',
  activityLevel: 'Activity',
  goal: 'Goal',
  is_active: 'Active',
  date_epoch_day: 'Date',
  total_calories: 'Calories',
  total_protein: 'Protein',
  total_carbs: 'Carbohydrates',
  total_fat: 'Fat',
  total_sodium: 'Sodium',
  meal_type: 'Meal',
  timestamp: 'Time',
  notes: 'Notes',
  items: 'Items',
  name: 'Activity Name',
  type: 'Type',
  timeString: 'Time',
  weightOrDuration: 'Duration/Weight',
  calories: 'Calories',
  steps: 'Steps'
}

// Format cell values for display
const formatCellValue = (val, key = '') => {
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
  return String(formatDate(val, key))
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
      Object.keys(obj).map(key => {
        const val = obj[key]
        let str = val !== null && val !== undefined ? (typeof val === 'object' ? JSON.stringify(val) : String(formatDate(val, key))) : ''
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
    
    // Use whitelist to prevent column overload in PDF
    const allowedKeys = pdfColumnConfig[selectedType.value] || (data.length > 0 ? Object.keys(data[0]) : [])
    
    const tableData = data.map(obj => {
      const row = {}
      allowedKeys.forEach(key => {
        let val = obj[key]
        if (val === null || val === undefined) val = ''
        else if (key === 'uid') val = truncateUid(val)
        else if (Array.isArray(val)) {
          val = val.map(item => item.dish_name || item.name || JSON.stringify(item)).join(', ')
        }
        else if (typeof val === 'object') val = JSON.stringify(val)
        else val = String(formatDate(val, key))
        row[key] = val
      })
      return row
    })

    autoTable(doc, {
      columns: allowedKeys.map(key => ({ header: columnLabels[key] || key, dataKey: key })),
      body: tableData,
      startY: 40,
      margin: { top: 35 },
      styles: { fontSize: 7, cellPadding: 2, overflow: 'linebreak' },
      columnStyles: {
        uid: { cellWidth: 25 },
        notes: { cellWidth: 'auto' },
        items: { cellWidth: 'auto' }
      },
      headStyles: { fillColor: [16, 185, 129] }, // ck-primary color
      didDrawPage: (data) => {
        // Header
        doc.setFontSize(18)
        doc.setTextColor(17, 24, 39) // ck-gray-900
        doc.setFont('helvetica', 'bold')
        doc.text('CalorieKo Admin Dashboard', data.settings.margin.left, 15)

        doc.setFontSize(10)
        doc.setFont('helvetica', 'normal')
        doc.setTextColor(75, 85, 99) // ck-gray-600
        doc.text(`${title} Report`, data.settings.margin.left, 22)
        doc.text(`Generated on: ${new Date().toLocaleString()}`, data.settings.margin.left, 28)

        // Subtle horizontal line
        doc.setDrawColor(229, 231, 235) // ck-gray-200
        doc.line(data.settings.margin.left, 32, doc.internal.pageSize.width - data.settings.margin.left, 32)

        // Footer
        const str = 'Page ' + doc.internal.getNumberOfPages()
        doc.setFontSize(8)
        doc.setTextColor(156, 163, 175) // ck-gray-400
        const pageSize = doc.internal.pageSize
        const pageHeight = pageSize.height ? pageSize.height : pageSize.getHeight()
        doc.text(str, data.settings.margin.left, pageHeight - 10)
      }
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

.ck-table th, .ck-table td {
  white-space: nowrap;
}

.ck-dot--gray {
  background: var(--ck-gray-400);
}

.boolean-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: var(--ck-radius-full);
  font-size: 0.75rem;
  font-weight: 600;
  border: 1px solid transparent;
}

.boolean-badge--true {
  background: var(--ck-primary-light);
  color: var(--ck-primary);
  border-color: var(--ck-primary-border);
}

.boolean-badge--false {
  background: var(--ck-gray-100);
  color: var(--ck-gray-500);
  border-color: var(--ck-gray-200);
}

.preview-note {
  display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem;
  font-size: 0.75rem; color: var(--ck-gray-600);
}
</style>
