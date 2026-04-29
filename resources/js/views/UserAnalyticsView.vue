<template>
  <div class="user-analytics">
    <!-- Back Nav + User Info Header -->
    <div class="analytics-header">
      <button class="back-btn" @click="$router.push({ name: 'UserManagement' })">
        <ArrowLeftIcon :size="18" />
        <span>Back to Users</span>
      </button>
      <div class="user-info" v-if="profile">
        <div class="user-avatar">{{ initials }}</div>
        <div class="user-meta">
          <h2 class="user-name">{{ profile.name }}</h2>
          <span class="user-uid">{{ profile.uid }}</span>
        </div>
        <div class="user-badges">
          <span class="ck-badge" :class="profile.is_active ? 'ck-badge--success' : 'ck-badge--danger'">
            {{ profile.is_active ? 'Active' : 'Inactive' }}
          </span>
          <span class="ck-badge ck-badge--outline">{{ profile.goal }}</span>
        </div>
      </div>
    </div>

    <!-- Profile Quick Stats -->
    <div class="quick-stats" v-if="profile">
      <div class="quick-stat">
        <span class="quick-stat__label">Age</span>
        <span class="quick-stat__value">{{ profile.age }}</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat__label">Weight</span>
        <span class="quick-stat__value">{{ profile.weight }} kg</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat__label">Height</span>
        <span class="quick-stat__value">{{ profile.height }} cm</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat__label">Activity</span>
        <span class="quick-stat__value">{{ formatActivityLevel(profile.activityLevel) }}</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat__label">Streak</span>
        <span class="quick-stat__value">{{ profile.streak }} days</span>
      </div>
    </div>

    <!-- Charts Grid -->
    <div class="charts-grid">
      <CorrelationChart :uid="uid" :refreshKey="refreshKey" />
      <WeightTrendChart :uid="uid" :refreshKey="refreshKey" />
    </div>

    <!-- Weekly Report Section -->
    <div class="ck-card ck-card--elevated report-section">
      <div class="report-header">
        <h3 class="section-title">
          <ClipboardListIcon :size="20" />
          Weekly Progress Report
        </h3>
        <div class="report-controls">
          <div class="date-range">
            <input type="date" v-model="reportStartDate" class="ck-input" />
            <span class="date-sep">to</span>
            <input type="date" v-model="reportEndDate" class="ck-input" />
          </div>
          <button @click="generateReport" class="ck-btn ck-btn--primary" :disabled="reportLoading">
            <FileTextIcon :size="16" />
            {{ reportLoading ? 'Generating...' : 'Generate' }}
          </button>
        </div>
      </div>

      <!-- Report Content -->
      <div v-if="reportLoading" class="report-loading">
        <div class="chart-spinner"></div>
        <span>Aggregating analytics data...</span>
      </div>

      <div v-else-if="report" class="report-content">
        <!-- Period Summary -->
        <div class="report-period">
          <span>📅 {{ report.period.start }} — {{ report.period.end }} ({{ report.period.num_days }} days)</span>
        </div>

        <!-- KPI Row -->
        <div class="report-kpis">
          <div class="report-kpi">
            <span class="kpi-label">Avg. Daily Calories</span>
            <span class="kpi-value">{{ report.nutrition.avg_daily_calories }}</span>
            <span class="kpi-unit">kcal / day</span>
          </div>
          <div class="report-kpi">
            <span class="kpi-label">TDEE Baseline</span>
            <span class="kpi-value">{{ report.tdee }}</span>
            <span class="kpi-unit">kcal / day</span>
          </div>
          <div class="report-kpi" :class="report.caloric_balance.status === 'Deficit' ? 'kpi--success' : 'kpi--warning'">
            <span class="kpi-label">Caloric Balance</span>
            <span class="kpi-value">{{ report.caloric_balance.balance > 0 ? '+' : '' }}{{ report.caloric_balance.balance }}</span>
            <span class="kpi-unit">{{ report.caloric_balance.status }} ({{ report.caloric_balance.daily_avg_gap }} kcal/day)</span>
          </div>
          <div class="report-kpi">
            <span class="kpi-label">Avg. Daily Steps</span>
            <span class="kpi-value">{{ report.activity.avg_daily_steps.toLocaleString() }}</span>
            <span class="kpi-unit">steps / day</span>
          </div>
        </div>

        <!-- Macro Breakdown -->
        <div class="report-section-block">
          <h4 class="subsection-title">Macro Nutrient Averages</h4>
          <div class="macro-row">
            <div class="macro-pill macro-pill--protein">
              <span>Protein</span>
              <strong>{{ report.nutrition.avg_protein }}g</strong>
            </div>
            <div class="macro-pill macro-pill--carbs">
              <span>Carbs</span>
              <strong>{{ report.nutrition.avg_carbs }}g</strong>
            </div>
            <div class="macro-pill macro-pill--fat">
              <span>Fat</span>
              <strong>{{ report.nutrition.avg_fat }}g</strong>
            </div>
          </div>
        </div>

        <!-- Top Foods -->
        <div class="report-section-block" v-if="report.top_foods.length > 0">
          <h4 class="subsection-title">🏆 Top 3 Most Consumed Foods</h4>
          <div class="top-foods">
            <div class="food-item" v-for="(food, idx) in report.top_foods" :key="idx">
              <span class="food-rank">#{{ idx + 1 }}</span>
              <div class="food-info">
                <strong>{{ food.dish_name }}</strong>
                <span class="food-meta">{{ food.times_consumed }}x consumed · ~{{ food.avg_calories_per_serving }} kcal/serving</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Compliance -->
        <div class="report-section-block">
          <h4 class="subsection-title">📊 Logging Compliance</h4>
          <div class="compliance-bar-container">
            <div class="compliance-bar" :style="{ width: report.nutrition.compliance_pct + '%' }">
              {{ report.nutrition.compliance_pct }}%
            </div>
          </div>
          <span class="compliance-text">{{ report.nutrition.days_logged }} of {{ report.period.num_days }} days logged</span>
        </div>

        <!-- Export Button -->
        <div class="report-actions">
          <button @click="exportReportPDF" class="ck-btn ck-btn--dark">
            <FileTextIcon :size="16" />
            Export as PDF
          </button>
        </div>
      </div>

      <div v-else class="report-empty">
        <ClipboardListIcon :size="32" style="color: var(--ck-gray-400);" />
        <p>Select a date range and click Generate to view the analytical progress report.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import {
  ArrowLeft as ArrowLeftIcon,
  ClipboardList as ClipboardListIcon,
  FileText as FileTextIcon,
} from 'lucide-vue-next'
import { getProfile, getWeeklyReport } from '../services/api.js'
import CorrelationChart from '../components/overview/CorrelationChart.vue'
import WeightTrendChart from '../components/overview/WeightTrendChart.vue'
import { jsPDF } from 'jspdf'
import { autoTable } from 'jspdf-autotable'

const route = useRoute()
const uid = computed(() => route.params.uid)
const refreshKey = ref(0)

const profile = ref(null)
const report = ref(null)
const reportLoading = ref(false)

// Default to last 7 days
const today = new Date()
const weekAgo = new Date(today)
weekAgo.setDate(weekAgo.getDate() - 6)
const reportStartDate = ref(weekAgo.toISOString().split('T')[0])
const reportEndDate = ref(today.toISOString().split('T')[0])

const initials = computed(() => {
  if (!profile.value?.name) return '?'
  return profile.value.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

function formatActivityLevel(level) {
  const map = {
    'not_very_active': 'Sedentary',
    'lightly_active': 'Lightly Active',
    'active': 'Active',
    'very_active': 'Very Active'
  }
  return map[level] || level || '—'
}

async function loadProfile() {
  try {
    profile.value = await getProfile(uid.value)
  } catch (err) {
    console.error('Failed to load profile:', err)
  }
}

async function generateReport() {
  reportLoading.value = true
  try {
    report.value = await getWeeklyReport(uid.value, reportStartDate.value, reportEndDate.value)
  } catch (err) {
    console.error('Failed to generate report:', err)
    alert('Failed to generate report. Check the console for details.')
  } finally {
    reportLoading.value = false
  }
}

function exportReportPDF() {
  if (!report.value) return

  const r = report.value
  const doc = new jsPDF('portrait')

  // Title
  doc.setFontSize(18)
  doc.setTextColor(26, 107, 60) // CalorieKo green
  doc.text('CalorieKo — Weekly Progress Report', 14, 18)

  doc.setFontSize(10)
  doc.setTextColor(100)
  doc.text(`Generated: ${new Date().toLocaleDateString()}`, 14, 25)
  doc.text(`User: ${r.user_name} (${r.uid})`, 14, 31)
  doc.text(`Period: ${r.period.start} — ${r.period.end} (${r.period.num_days} days)`, 14, 37)

  // Caloric Overview Table
  doc.setFontSize(13)
  doc.setTextColor(30)
  doc.text('Caloric Overview', 14, 48)

  autoTable(doc, {
    startY: 52,
    head: [['Metric', 'Value']],
    body: [
      ['TDEE (Baseline)', `${r.tdee} kcal/day`],
      ['Avg. Daily Intake', `${r.nutrition.avg_daily_calories} kcal`],
      ['Total Caloric Intake', `${r.nutrition.total_calories} kcal`],
      ['Caloric Balance', `${r.caloric_balance.balance > 0 ? '+' : ''}${r.caloric_balance.balance} kcal (${r.caloric_balance.status})`],
      ['Daily Avg. Gap', `${r.caloric_balance.daily_avg_gap} kcal/day`],
    ],
    styles: { fontSize: 9 },
    headStyles: { fillColor: [26, 107, 60] }
  })

  // Activity Summary
  const y1 = doc.lastAutoTable.finalY + 10
  doc.setFontSize(13)
  doc.text('Activity Summary', 14, y1)

  autoTable(doc, {
    startY: y1 + 4,
    head: [['Metric', 'Value']],
    body: [
      ['Avg. Daily Steps', `${r.activity.avg_daily_steps.toLocaleString()}`],
      ['Total Steps', `${r.activity.total_steps.toLocaleString()}`],
      ['Total Burned', `${r.activity.total_burned} kcal`],
      ['Total Distance', `${r.activity.total_distance_km} km`],
      ['Activities Logged', `${r.activity.activity_count}`],
    ],
    styles: { fontSize: 9 },
    headStyles: { fillColor: [26, 107, 60] }
  })

  // Macro Nutrients
  const y2 = doc.lastAutoTable.finalY + 10
  doc.setFontSize(13)
  doc.text('Average Macronutrient Intake', 14, y2)

  autoTable(doc, {
    startY: y2 + 4,
    head: [['Macro', 'Average (per day)']],
    body: [
      ['Protein', `${r.nutrition.avg_protein}g`],
      ['Carbohydrates', `${r.nutrition.avg_carbs}g`],
      ['Fat', `${r.nutrition.avg_fat}g`],
    ],
    styles: { fontSize: 9 },
    headStyles: { fillColor: [26, 107, 60] }
  })

  // Top Foods
  if (r.top_foods && r.top_foods.length > 0) {
    const y3 = doc.lastAutoTable.finalY + 10
    doc.setFontSize(13)
    doc.text('Top Consumed Foods', 14, y3)

    autoTable(doc, {
      startY: y3 + 4,
      head: [['Rank', 'Food Item', 'Times Consumed', 'Avg. Calories']],
      body: r.top_foods.map((f, i) => [
        `#${i + 1}`,
        f.dish_name,
        `${f.times_consumed}x`,
        `${f.avg_calories_per_serving} kcal`
      ]),
      styles: { fontSize: 9 },
      headStyles: { fillColor: [26, 107, 60] }
    })
  }

  // Compliance
  const yFinal = doc.lastAutoTable.finalY + 10
  doc.setFontSize(11)
  doc.text(`Logging Compliance: ${r.nutrition.compliance_pct}% (${r.nutrition.days_logged}/${r.period.num_days} days)`, 14, yFinal)

  doc.save(`calorieko_weekly_report_${r.uid}_${r.period.start}.pdf`)
}

onMounted(() => loadProfile())
</script>

<style scoped>
.user-analytics {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* ── Header ── */
.analytics-header {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: var(--ck-gray-50);
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg, 0.5rem);
  font-size: 0.8125rem;
  color: var(--ck-gray-600);
  cursor: pointer;
  width: fit-content;
  transition: all 0.15s;
}

.back-btn:hover {
  background: var(--ck-gray-100);
  color: var(--ck-gray-900);
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.25rem;
  background: var(--ck-surface, white);
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-xl, 0.75rem);
  box-shadow: var(--ck-shadow-sm);
}

.user-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: linear-gradient(135deg, #1a6b3c, #3aaa68);
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 1rem;
  flex-shrink: 0;
}

.user-meta { flex: 1; }

.user-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ck-gray-900);
  margin: 0;
}

.user-uid {
  font-size: 0.75rem;
  color: var(--ck-gray-500);
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

.user-badges {
  display: flex;
  gap: 0.5rem;
}

/* ── Quick Stats ── */
.quick-stats {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.75rem;
}

.quick-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.875rem;
  background: var(--ck-surface, white);
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-lg, 0.5rem);
  box-shadow: var(--ck-shadow-sm);
}

.quick-stat__label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ck-gray-500);
  font-weight: 500;
}

.quick-stat__value {
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--ck-gray-900);
}

/* ── Charts Grid ── */
.charts-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.5rem;
}

@media (max-width: 1024px) {
  .charts-grid { grid-template-columns: 1fr; }
  .quick-stats { grid-template-columns: repeat(3, 1fr); }
}

/* ── Report Section ── */
.report-section { padding: 1.5rem; }

.report-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.section-title {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.125rem;
  font-weight: 600;
  color: var(--ck-gray-900);
}

.report-controls {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.date-range {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.date-sep {
  font-size: 0.8125rem;
  color: var(--ck-gray-500);
}

.ck-input {
  padding: 0.5rem 0.75rem;
  border: 1px solid var(--ck-gray-200);
  border-radius: var(--ck-radius-md, 0.375rem);
  font-size: 0.8125rem;
  color: var(--ck-gray-700);
}

/* Report Content */
.report-loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.chart-spinner {
  width: 1.5rem;
  height: 1.5rem;
  border: 2px solid var(--ck-gray-200);
  border-top-color: #1a6b3c;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.report-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 3rem;
  color: var(--ck-gray-500);
  font-size: 0.875rem;
}

.report-period {
  padding: 0.625rem 1rem;
  background: var(--ck-gray-50);
  border-radius: var(--ck-radius-lg, 0.5rem);
  font-size: 0.8125rem;
  color: var(--ck-gray-700);
  font-weight: 500;
  margin-bottom: 1.25rem;
}

.report-kpis {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.report-kpi {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 1rem;
  background: var(--ck-gray-50);
  border: 1px solid var(--ck-gray-100);
  border-radius: var(--ck-radius-lg, 0.5rem);
}

.kpi--success { background: #ecfdf5; border-color: #d1fae5; }
.kpi--warning { background: #fffbeb; border-color: #fef3c7; }

.kpi-label {
  font-size: 0.6875rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ck-gray-500);
  font-weight: 500;
}

.kpi-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: var(--ck-gray-900);
}

.kpi--success .kpi-value { color: #059669; }
.kpi--warning .kpi-value { color: #d97706; }

.kpi-unit {
  font-size: 0.6875rem;
  color: var(--ck-gray-500);
}

/* Sections */
.report-section-block {
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--ck-gray-100);
}

.subsection-title {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ck-gray-800);
  margin-bottom: 0.75rem;
}

/* Macros */
.macro-row {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.75rem;
}

.macro-pill {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem 1rem;
  border-radius: var(--ck-radius-lg, 0.5rem);
  font-size: 0.875rem;
}

.macro-pill--protein { background: #eff6ff; color: #1d4ed8; }
.macro-pill--carbs { background: #fefce8; color: #a16207; }
.macro-pill--fat { background: #fef2f2; color: #b91c1c; }

/* Top Foods */
.top-foods {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.food-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: var(--ck-gray-50);
  border: 1px solid var(--ck-gray-100);
  border-radius: var(--ck-radius-lg, 0.5rem);
}

.food-rank {
  font-size: 1rem;
  font-weight: 800;
  color: var(--ck-primary, #1a6b3c);
  min-width: 2rem;
}

.food-info { display: flex; flex-direction: column; }
.food-info strong { font-size: 0.875rem; color: var(--ck-gray-900); }
.food-meta { font-size: 0.75rem; color: var(--ck-gray-500); }

/* Compliance */
.compliance-bar-container {
  width: 100%;
  height: 1.5rem;
  background: var(--ck-gray-100);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.compliance-bar {
  height: 100%;
  background: linear-gradient(90deg, #1a6b3c, #3aaa68);
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.6875rem;
  font-weight: 700;
  color: white;
  min-width: 2.5rem;
  transition: width 0.5s ease;
}

.compliance-text {
  font-size: 0.75rem;
  color: var(--ck-gray-500);
}

/* Actions */
.report-actions {
  display: flex;
  justify-content: flex-end;
  padding-top: 1.25rem;
  border-top: 1px solid var(--ck-gray-100);
  margin-top: 1.5rem;
}
</style>
