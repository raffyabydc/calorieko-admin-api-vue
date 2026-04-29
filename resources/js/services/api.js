/**
 * CalorieKo API Service
 * 
 * Fetches real data from the Laravel calorieko-api backend.
 * All requests go through the Vite proxy (configured in vite.config.js)
 * which forwards /api/* requests to the Laravel server on port 8000.
 *
 * Authentication uses Laravel Sanctum Bearer tokens stored in sessionStorage.
 * All admin endpoints attach the token via the Authorization header.
 */

const API_BASE = '/api/admin'

/**
 * Returns the Authorization header with the Sanctum token.
 */
function getAuthHeaders() {
    const token = sessionStorage.getItem('ck_token')
    return token ? { 'Authorization': `Bearer ${token}` } : {}
}

/**
 * Unified fetch wrapper that intercepts 401 Unauthorized errors
 * globally and forcefully logs out the user.
 */
async function authenticatedFetch(url, options = {}) {
    const headers = { ...getAuthHeaders(), ...(options.headers || {}) }
    const res = await fetch(url, { ...options, headers })
    
    // Auto-logout security wall
    if (res.status === 401) {
        sessionStorage.removeItem('ck_token')
        sessionStorage.removeItem('ck_email')
        sessionStorage.removeItem('ck_role')
        sessionStorage.removeItem('ck_logged_in')
        window.location.href = '/' // Kick user to login screen
        throw new Error('Session expired. Please log in again.')
    }
    
    return res
}

async function fetchJSON(endpoint) {
    const res = await authenticatedFetch(`${API_BASE}${endpoint}`)
    if (!res.ok) throw new Error(`API error: ${res.status} ${res.statusText}`)
    return res.json()
}

// ── Authentication ──

export async function adminLogin(email, password) {
    const res = await fetch('/api/admin/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    })
    
    let data;
    try {
        data = await res.json()
    } catch (err) {
        throw new Error('Our servers are currently unreachable or malfunctioning. Please verify your connection or contact support.')
    }
    
    if (!res.ok) throw new Error(data?.error || 'Login failed')
    return data
}

export async function adminLogout() {
    try {
        await authenticatedFetch('/api/admin/logout', { method: 'POST' })
    } catch (e) {
        // Silently fail — we clear local state regardless
    }
    sessionStorage.removeItem('ck_token')
    sessionStorage.removeItem('ck_email')
    sessionStorage.removeItem('ck_role')
    sessionStorage.removeItem('ck_logged_in')
}

export async function verifyToken() {
    const token = sessionStorage.getItem('ck_token')
    if (!token) return false
    
    try {
        const res = await fetch('/api/admin/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`
            }
        })
        return res.ok
    } catch {
        return false
    }
}

// ── User Profiles ──
export async function getProfiles() {
    return fetchJSON('/profiles')
}

export async function getProfile(uid) {
    return fetchJSON(`/profiles/${uid}`)
}

export async function deactivateProfile(uid) {
    const res = await authenticatedFetch(`${API_BASE}/profiles/${uid}/deactivate`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }
    })
    if (!res.ok) {
        const err = await res.json().catch(() => ({}))
        throw new Error(err.error || err.message || 'Failed to toggle active status')
    }
    return res.json()
}

export async function resetProfilePassword(uid) {
    const res = await authenticatedFetch(`${API_BASE}/profiles/${uid}/reset-password`, {
        method: 'POST'
    })
    if (!res.ok) throw new Error('Failed to request password reset')
    return res.json()
}

export async function deleteProfile(uid) {
    const res = await authenticatedFetch(`${API_BASE}/profiles/${uid}`, {
        method: 'DELETE'
    })
    if (!res.ok) throw new Error('Failed to delete user profile')
    return res.json()
}

// ── Activity Logs ──
export async function getActivityLogs(uid = null) {
    const url = uid ? `/activity-logs?uid=${uid}` : '/activity-logs'
    return fetchJSON(url)
}

export async function getActivityLog(id) {
    return fetchJSON(`/activity-logs/${id}`)
}

// ── Meal Logs ──
export async function getMealLogs(uid = null) {
    const url = uid ? `/meal-logs?uid=${uid}` : '/meal-logs'
    return fetchJSON(url)
}

export async function getMealLog(id) {
    return fetchJSON(`/meal-logs/${id}`)
}

// ── Food Items ──
export async function getFoods() {
    return fetchJSON('/foods')
}

export async function createFood(data) {
    const res = await authenticatedFetch(`${API_BASE}/foods`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    if (!res.ok) throw new Error('Failed to create food item')
    return res.json()
}

export async function updateFood(id, data) {
    const res = await authenticatedFetch(`${API_BASE}/foods/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    if (!res.ok) throw new Error('Failed to update food item')
    return res.json()
}

export async function deleteFood(id) {
    const res = await authenticatedFetch(`${API_BASE}/foods/${id}`, {
        method: 'DELETE'
    })
    if (!res.ok) throw new Error('Failed to delete food item')
    return res.json()
}

export async function bulkImportFoods(csvFile) {
    const formData = new FormData()
    formData.append('csv_file', csvFile)
    const res = await authenticatedFetch(`${API_BASE}/foods/bulk-import`, {
        method: 'POST',
        body: formData
    })
    if (!res.ok) {
        const err = await res.json().catch(() => ({}))
        throw new Error(err.message || 'Failed to import CSV')
    }
    return res.json()
}

// ── System Logs ──
export async function getSystemLogs() {
    return fetchJSON('/system-logs')
}

// ── Daily Nutrition Summaries ──
export async function getNutritionSummaries() {
    return fetchJSON('/nutrition-summaries')
}

// ── Dashboard Stats (server-side aggregation) ──
export async function getDashboardStats() {
    return fetchJSON('/dashboard/stats')
}

// ── Analytics Endpoints (server-side chart data) ──
export async function getNutritionTrends() {
    return fetchJSON('/analytics/nutrition-trends')
}

export async function getTopDishes() {
    return fetchJSON('/analytics/top-dishes')
}

export async function getUserConsistency() {
    return fetchJSON('/analytics/user-consistency')
}

export async function getStepTrends() {
    return fetchJSON('/analytics/step-trends')
}

// ── NEW: Individual User Analytics ──

export async function getCorrelation(uid, days = 30) {
    return fetchJSON(`/analytics/correlation/${uid}?days=${days}`)
}

export async function getWeightTrend(uid, days = 90) {
    return fetchJSON(`/analytics/weight-trend/${uid}?days=${days}`)
}

// ── NEW: Weekly Progress Reports ──

export async function getWeeklyReport(uid, startDate, endDate) {
    let url = `/reports/weekly/${uid}`
    const params = []
    if (startDate) params.push(`start_date=${startDate}`)
    if (endDate) params.push(`end_date=${endDate}`)
    if (params.length) url += '?' + params.join('&')
    return fetchJSON(url)
}

// ═══════════════════════════════════════════════════════════════
// Admin & Moderator Management (Super Admin Only)
// ═══════════════════════════════════════════════════════════════

export async function getModerators() {
  const res = await api.get('/admin/moderators')
  return res.data
}

export async function createModerator(data) {
  const res = await api.post('/admin/moderators', data)
  return res.data
}

export async function toggleModerator(id) {
  const res = await api.put(`/admin/moderators/${id}/toggle`)
  return res.data
}

export async function deleteModerator(id) {
  const res = await api.delete(`/admin/moderators/${id}`)
  return res.data
}

