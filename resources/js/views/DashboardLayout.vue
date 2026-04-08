<template>
  <div class="dashboard">
    <!-- Sidebar -->
    <aside class="sidebar">
      <!-- Logo -->
      <div class="sidebar__header">
        <div class="sidebar__logo">
          <div class="sidebar__logo-icon">
            <span>CK</span>
          </div>
          <div class="sidebar__logo-text">
            <h2>CalorieKo</h2>
            <p>Research Portal</p>
          </div>
        </div>
      </div>

      <!-- Navigation -->
      <nav class="sidebar__nav">
        <router-link
          v-for="item in navItems"
          :key="item.name"
          :to="item.to"
          class="sidebar__nav-item"
          :class="{ 'sidebar__nav-item--active': isActive(item.routeName) }"
        >
          <component :is="item.icon" class="sidebar__nav-icon" />
          <span>{{ item.name }}</span>
        </router-link>

        <div style="flex-grow: 1"></div>

        <button @click="handleLogout" class="sidebar__nav-item sidebar__nav-item--logout">
          <LogOutIcon class="sidebar__nav-icon" />
          <span>Logout</span>
        </button>
      </nav>

      <!-- Version Footer -->
      <div class="sidebar__footer">
        <p class="sidebar__version font-mono">v1.0.2-Stable</p>
        <p class="sidebar__status">System Operational</p>
      </div>
    </aside>

    <!-- Main Content -->
    <div class="main">
  <!-- Top Header Bar -->
      <header class="topbar">
        <div class="topbar__left">
          <h1 class="topbar__title">{{ pageTitle }}</h1>
          <div class="topbar__location">
            <MapPinIcon class="topbar__location-icon" />
            <span>Node Location: {{ nodeLocation }}</span>
          </div>
          <button class="topbar__refresh" @click="forceRefresh" :disabled="isRefreshing" title="Refresh Data">
            <RefreshCwIcon class="topbar__refresh-icon" :class="{ 'topbar__refresh-icon--spinning': isRefreshing }" />
            <span>Refresh Data</span>
          </button>
        </div>
        <div class="topbar__encryption" :class="{ 'topbar__encryption--inactive': !isEncrypted }">
          <LockIcon v-if="isEncrypted" class="topbar__encryption-icon" />
          <UnlockIcon v-else class="topbar__encryption-icon" />
          <span>AES-256 Data Protection: {{ isEncrypted ? 'Active' : 'Inactive' }}</span>
        </div>
      </header>

      <!-- Page Content -->
      <main class="content custom-scrollbar">
        <div class="content__inner">
          <router-view v-slot="{ Component }">
            <transition name="fade" mode="out-in">
              <component :is="Component" :key="componentKey" />
            </transition>
          </router-view>
        </div>
      </main>
    </div>
  </div>

  <!-- Logout Confirmation Modal -->
  <div v-if="showLogoutModal" class="modal-overlay">
    <div class="modal">
      <div class="modal__header">
        <h3>Confirm Logout</h3>
      </div>
      <div class="modal__body">
        <p>Are you sure you want to log out of your current session?</p>
      </div>
      <div class="modal__footer">
        <button class="btn btn--secondary" @click="cancelLogout">Cancel</button>
        <button class="btn btn--danger" @click="confirmLogout">Log Out</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  LayoutDashboard,
  Users,
  FileBarChart,
  Activity,
  Database,
  MapPin as MapPinIcon,
  Lock as LockIcon,
  Unlock as UnlockIcon,
  LogOut as LogOutIcon,
  RefreshCw as RefreshCwIcon
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter() // Initialize router

const navItems = [
  { name: 'Overview', icon: LayoutDashboard, to: '/dashboard', routeName: 'Overview' },
  { name: 'User Management', icon: Users, to: '/dashboard/user-management', routeName: 'UserManagement' },
  { name: 'Food Database', icon: Database, to: '/dashboard/food-database', routeName: 'FoodDatabase' },
  { name: 'Report Generator', icon: FileBarChart, to: '/dashboard/report-generator', routeName: 'ReportGenerator' },
  { name: 'System Logs', icon: Activity, to: '/dashboard/system-logs', routeName: 'SystemLogs' }
]
const isActive = (routeName) => route.name === routeName

const pageTitle = computed(() => {
  const currentNav = navItems.find(item => item.routeName === route.name)
  if (route.name === 'Overview') return 'Admin Overview'
  return currentNav?.name || 'Dashboard'
})

const showLogoutModal = ref(false)
const nodeLocation = ref('Determining Node Network...')
const isEncrypted = ref(true)

// ── Global Refresh ──
// Changing the :key on <component> forces Vue to destroy and remount the
// current route view, re-triggering all onMounted hooks and data fetches.
const componentKey = ref(0)
const isRefreshing = ref(false)
const forceRefresh = async () => {
  isRefreshing.value = true
  componentKey.value++
  // Brief visual feedback, then reset the spinning icon
  setTimeout(() => { isRefreshing.value = false }, 1200)
}

onMounted(() => {
  // Check for secure context (HTTPS or localhost)
  const isTransitSecure = window.isSecureContext || window.location.protocol === 'https:'
  
  // Actually verify with the API if encryption is configured correctly
  const verifyEncryption = async () => {
    try {
      // Use your API service if available, or fetch directly
      const response = await fetch('/api/health')
      const data = await response.json()
      // Only set to true if BOTH transit is secure and backend reports encryption is active
      isEncrypted.value = isTransitSecure && data?.encryption?.active === true
    } catch (err) {
      console.error('Failed to verify backend encryption status:', err)
      // Fallback to transit security only if API is down
      isEncrypted.value = isTransitSecure 
    }
  }

  verifyEncryption()

  const fetchFallbackIPLocation = async () => {
    try {
      const response = await fetch('https://ipapi.co/json/')
      const data = await response.json()
      if (data.city && (data.region || data.country_name)) {
        nodeLocation.value = `${data.city}, ${data.region || data.country_name} (Approx)`
      } else {
        nodeLocation.value = 'Tagum City, Davao'
      }
    } catch (error) {
      console.error('Failed to pinpoint node location via IP:', error)
      nodeLocation.value = 'Tagum City, Davao' // Final fallback
    }
  }

  const fetchPreciseLocation = async (lat, lon) => {
    try {
      // Using OpenStreetMap's free Nominatim reverse geocoding API
      const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
      const data = await response.json()
      
      if (data && data.address) {
        const address = data.address
        const city = address.city || address.town || address.municipality || address.county || 'Unknown City'
        const region = address.state || address.region || 'Philippines'
        nodeLocation.value = `${city}, ${region}`
      } else {
        fetchFallbackIPLocation()
      }
    } catch (err) {
      console.error('Reverse geocoding failed:', err)
      fetchFallbackIPLocation()
    }
  }

  // Attempt to use Geolocation API for real hardware location instead of ISP routing hubs
  if ("geolocation" in navigator) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        fetchPreciseLocation(position.coords.latitude, position.coords.longitude)
      },
      (error) => {
        console.warn('Precise geolocation failed or was denied. Falling back to IP estimation.', error)
        fetchFallbackIPLocation()
      },
      { timeout: 10000 }
    )
  } else {
    fetchFallbackIPLocation()
  }
})

// Add Logout Function
const handleLogout = () => {
  showLogoutModal.value = true
}

const confirmLogout = () => {
  sessionStorage.removeItem('ck_logged_in')
  sessionStorage.removeItem('ck_token')
  sessionStorage.removeItem('ck_email')
  router.push({ name: 'Login' })
  showLogoutModal.value = false
}

const cancelLogout = () => {
  showLogoutModal.value = false
}
</script>

<style scoped>

.dashboard {
  display: flex;
  height: 100vh;
  background: var(--ck-bg);
  color: var(--ck-gray-800);
}

/* --- Sidebar --- */
.sidebar {
  width: var(--ck-sidebar-width);
  background: var(--ck-surface);
  border-right: 1px solid rgba(209, 213, 219, 0.5);
  display: flex;
  flex-direction: column;
  box-shadow: var(--ck-shadow-lg);
  flex-shrink: 0;
}

.sidebar__header {
  padding: 1.5rem;
  border-bottom: 1px solid rgba(209, 213, 219, 0.5);
}

.sidebar__logo {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.sidebar__logo-icon {
  width: 2.5rem;
  height: 2.5rem;
  background: var(--ck-primary);
  border-radius: var(--ck-radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.sidebar__logo-icon span {
  color: white;
  font-weight: 700;
  font-size: 1.125rem;
}

.sidebar__logo-text h2 {
  font-size: 0.9375rem;
  font-weight: 600;
  color: var(--ck-gray-900);
  line-height: 1.25;
}

.sidebar__logo-text p {
  font-size: 0.75rem;
  color: var(--ck-gray-500);
}

.sidebar__nav {
  flex: 1;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  overflow-y: auto;
}

.sidebar__nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-radius: var(--ck-radius-lg);
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ck-gray-600);
  transition: all var(--ck-transition-base);
  text-decoration: none;
}

.sidebar__nav-item:hover {
  background: var(--ck-gray-100);
  color: var(--ck-gray-900);
}

.sidebar__nav-item--active {
  background: var(--ck-primary) !important;
  color: white !important;
  box-shadow: var(--ck-shadow-lg);
}
.sidebar__nav-item--logout {
  background: transparent;
  border: none;
  width: 100%;
  cursor: pointer;
  color: #ef4444; /* Using a red tone for destructive action */
  margin-top: 1rem;
}
.sidebar__nav-item--logout:hover {
  background: #fef2f2;
  color: #dc2626;
}

.sidebar__nav-icon {
  width: 1.25rem;
  height: 1.25rem;
  flex-shrink: 0;
}

.sidebar__footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid rgba(209, 213, 219, 0.5);
}

.sidebar__version {
  font-size: 0.75rem;
  color: var(--ck-gray-500);
}

.sidebar__status {
  font-size: 0.75rem;
  color: var(--ck-gray-400);
  margin-top: 0.25rem;
}

/* --- Main Area --- */
.main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  min-width: 0;
}

.topbar {
  height: var(--ck-header-height);
  background: var(--ck-surface);
  border-bottom: 1px solid rgba(209, 213, 219, 0.5);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 1.5rem;
  box-shadow: var(--ck-shadow-sm);
  flex-shrink: 0;
}

.topbar__left {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.topbar__title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ck-gray-900);
}

.topbar__location {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--ck-gray-600);
}

.topbar__location-icon {
  width: 1rem;
  height: 1rem;
}

.topbar__encryption {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #ecfdf5;
  border: 1px solid #a7f3d0;
  padding: 0.5rem 1rem;
  border-radius: var(--ck-radius-lg);
  font-size: 0.875rem;
  font-weight: 500;
  color: #10b981;
  transition: all 0.3s ease;
}

.topbar__encryption--inactive {
  background: #fef2f2;
  border-color: #fca5a5;
  color: #ef4444;
}

.topbar__encryption-icon {
  width: 1rem;
  height: 1rem;
}

.content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
}

.content__inner {
  max-width: 80rem;
  margin: 0 auto;
}

/* --- Modal --- */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal {
  background: var(--ck-surface, #ffffff);
  border-radius: var(--ck-radius-lg, 0.5rem);
  width: 90%;
  max-width: 400px;
  box-shadow: var(--ck-shadow-lg, 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05));
  overflow: hidden;
  animation: modal-fade-in 0.2s ease-out;
}

@keyframes modal-fade-in {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.modal__header {
  padding: 1.5rem 1.5rem 1rem;
}

.modal__header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--ck-gray-900, #111827);
}

.modal__body {
  padding: 0 1.5rem 1.5rem;
  color: var(--ck-gray-600, #4b5563);
  font-size: 0.9375rem;
  line-height: 1.5;
}

.modal__footer {
  padding: 1rem 1.5rem;
  background: var(--ck-gray-50, #f9fafb);
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  border-top: 1px solid rgba(209, 213, 219, 0.5);
}

.btn {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  border-radius: var(--ck-radius-md, 0.375rem);
  cursor: pointer;
  transition: all 0.2s;
  border: 1px solid transparent;
}

.btn--secondary {
  background: white;
  border-color: var(--ck-gray-300, #d1d5db);
  color: var(--ck-gray-700, #374151);
}

.btn--secondary:hover {
  background: var(--ck-gray-50, #f9fafb);
}

.btn--danger {
  background: #ef4444;
  color: white;
}

.btn--danger:hover {
  background: #dc2626;
}

/* --- Refresh Button --- */
.topbar__refresh {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.4rem 0.75rem;
  font-size: 0.8125rem;
  font-weight: 500;
  color: var(--ck-primary);
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.25);
  border-radius: var(--ck-radius-md);
  cursor: pointer;
  transition: all 0.2s;
}
.topbar__refresh:hover {
  background: rgba(16, 185, 129, 0.15);
  border-color: rgba(16, 185, 129, 0.4);
}
.topbar__refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
.topbar__refresh-icon {
  width: 0.875rem;
  height: 0.875rem;
}
.topbar__refresh-icon--spinning {
  animation: spin 0.8s linear infinite;
}
@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
