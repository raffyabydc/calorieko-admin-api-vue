/**
 * CalorieKo API Service
 * 
 * Fetches real data from the Laravel calorieko-api backend.
 * All requests go through the Vite proxy (configured in vite.config.js)
 * which forwards /api/* requests to the Laravel server on port 8000.
 *
 * All admin endpoints send the auth token from sessionStorage
 * in the Authorization header.
 */

const API_BASE = '/api/admin'

/**
 * Returns the Authorization header with the admin token.
 * The token is stored in sessionStorage by the login flow.
 */
function getAuthHeaders() {
    const token = sessionStorage.getItem('ck_token')
    return token ? { 'Authorization': `Bearer ${token}` } : {}
}

async function fetchJSON(endpoint) {
    const res = await fetch(`${API_BASE}${endpoint}`, {
        headers: {
            ...getAuthHeaders()
        }
    })
    if (!res.ok) throw new Error(`API error: ${res.status} ${res.statusText}`)
    return res.json()
}

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

export async function verifyToken(token) {
    const res = await fetch('/api/admin/verify', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        }
    })
    return res.ok
}

// ── User Profiles ──
export async function getProfiles() {
    return fetchJSON('/profiles')
}

export async function getProfile(uid) {
    return fetchJSON(`/profiles/${uid}`)
}

export async function deactivateProfile(uid) {
    const res = await fetch(`${API_BASE}/profiles/${uid}/deactivate`, {
        method: 'PUT',
        headers: { ...getAuthHeaders() }
    })
    if (!res.ok) throw new Error('Failed to toggle active status')
    return res.json()
}

export async function resetProfilePassword(uid) {
    const res = await fetch(`${API_BASE}/profiles/${uid}/reset-password`, {
        method: 'POST',
        headers: { ...getAuthHeaders() }
    })
    if (!res.ok) throw new Error('Failed to request password reset')
    return res.json()
}

export async function deleteProfile(uid) {
    const res = await fetch(`${API_BASE}/profiles/${uid}`, {
        method: 'DELETE',
        headers: { ...getAuthHeaders() }
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
    const res = await fetch(`${API_BASE}/foods`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
        body: JSON.stringify(data)
    })
    if (!res.ok) throw new Error('Failed to create food item')
    return res.json()
}

export async function updateFood(id, data) {
    const res = await fetch(`${API_BASE}/foods/${id}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', ...getAuthHeaders() },
        body: JSON.stringify(data)
    })
    if (!res.ok) throw new Error('Failed to update food item')
    return res.json()
}

export async function deleteFood(id) {
    const res = await fetch(`${API_BASE}/foods/${id}`, {
        method: 'DELETE',
        headers: { ...getAuthHeaders() }
    })
    if (!res.ok) throw new Error('Failed to delete food item')
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

