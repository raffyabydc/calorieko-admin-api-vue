<template>
  <div class="force-pw-page">
    <div class="split-container">
      
      <!-- Left Branding Side -->
      <div class="split-left">
        <div class="overlay"></div>
        <div class="content">
          <div class="logo-circle">
            <svg viewBox="0 0 80 80" width="60" height="60" xmlns="http://www.w3.org/2000/svg">
              <circle cx="40" cy="40" r="32" fill="rgba(255,255,255,0.95)"/>
              <g transform="translate(14,14) scale(2.16)">
                <path fill="#4CAF50" d="M19.95 5.97a2.018 2.018 0 0 0-1.92-1.92c-.7-.03-1.37-.05-2.02-.05c-5.03 0-8.52.97-10.46 2.91c-3.68 3.68-3.15 8.9.09 11.9h.01a16 16 0 0 1 7.67-8.8c-.21.18-4.7 3.58-5.51 10.25c1.05.48 2.2.75 3.36.75c2.05 0 4.16-.8 5.92-2.55c2.19-2.2 3.14-6.36 2.86-12.49z"/>
              </g>
            </svg>
          </div>
          <h1 class="brand-title">Secure Your Account</h1>
          <p class="brand-desc">To continue, please update your temporary password to a permanent one.</p>
        </div>
      </div>

      <!-- Right Form Side -->
      <div class="split-right">
        <div class="form-wrapper">
          <header class="form-header">
            <h2>Account Security</h2>
            <p>Update your credentials</p>
          </header>

          <form @submit.prevent="handleSubmit" class="security-form">
            <div class="field-group">
              <label>Current Password</label>
              <div class="input-wrap">
                <LockIcon :size="16" class="field-icon" />
                <input 
                  v-model="form.current_password" 
                  :type="showPasswords.current ? 'text' : 'password'" 
                  placeholder="Enter temporary password" 
                  required
                >
                <button 
                  type="button" 
                  class="toggle-visibility" 
                  @click="showPasswords.current = !showPasswords.current"
                  tabindex="-1"
                >
                  <EyeIcon v-if="!showPasswords.current" :size="16" />
                  <EyeOffIcon v-else :size="16" />
                </button>
              </div>
            </div>

            <div class="field-group">
              <label>New Password</label>
              <div class="input-wrap">
                <ShieldCheckIcon :size="16" class="field-icon" />
                <input 
                  v-model="form.new_password" 
                  :type="showPasswords.new ? 'text' : 'password'" 
                  placeholder="Minimum 8 characters" 
                  required
                  @input="checkStrength"
                >
                <button 
                  type="button" 
                  class="toggle-visibility" 
                  @click="showPasswords.new = !showPasswords.new"
                  tabindex="-1"
                >
                  <EyeIcon v-if="!showPasswords.new" :size="16" />
                  <EyeOffIcon v-else :size="16" />
                </button>
              </div>
              
              <!-- Password Strength UI -->
              <div class="strength-meter">
                <div class="meter-bar">
                  <div class="meter-fill" :class="strengthClass" :style="{ width: strengthPercent + '%' }"></div>
                </div>
                <ul class="requirements">
                  <li :class="{ 'met': checks.length }"><CheckCircleIcon :size="12" /> 8 characters minimum</li>
                  <li :class="{ 'met': checks.case }"><CheckCircleIcon :size="12" /> Lower & uppercase letters</li>
                  <li :class="{ 'met': checks.number }"><CheckCircleIcon :size="12" /> At least one number</li>
                  <li :class="{ 'met': checks.symbol }"><CheckCircleIcon :size="12" /> At least one symbol</li>
                </ul>
              </div>
            </div>

            <div class="field-group">
              <label>Confirm New Password</label>
              <div class="input-wrap">
                <KeyIcon :size="16" class="field-icon" />
                <input 
                  v-model="form.new_password_confirmation" 
                  :type="showPasswords.confirm ? 'text' : 'password'" 
                  placeholder="Repeat new password" 
                  required
                >
                <button 
                  type="button" 
                  class="toggle-visibility" 
                  @click="showPasswords.confirm = !showPasswords.confirm"
                  tabindex="-1"
                >
                  <EyeIcon v-if="!showPasswords.confirm" :size="16" />
                  <EyeOffIcon v-else :size="16" />
                </button>
              </div>
            </div>

            <div v-if="error" class="error-alert">
              <AlertCircleIcon :size="16" />
              <p>{{ error }}</p>
            </div>

            <button type="submit" class="submit-btn" :disabled="loading || !isFormValid">
              <span v-if="!loading">Update Password & Continue</span>
              <span v-else class="loading-spinner"></span>
            </button>
          </form>

          <button @click="handleLogout" class="logout-link">
            Log out and try again later
          </button>
        </div>
      </div>

    </div>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { 
  Lock as LockIcon, 
  ShieldCheck as ShieldCheckIcon, 
  Key as KeyIcon, 
  CheckCircle as CheckCircleIcon,
  AlertCircle as AlertCircleIcon,
  LogOut as LogOutIcon,
  Eye as EyeIcon,
  EyeOff as EyeOffIcon
} from 'lucide-vue-next'
import { updateAdminPassword, adminLogout } from '../services/api.js'

const router = useRouter()
const loading = ref(false)
const error = ref('')

const form = reactive({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

const showPasswords = reactive({
  current: false,
  new: false,
  confirm: false
})

const checks = reactive({
  length: false,
  case: false,
  number: false,
  symbol: false
})

const checkStrength = () => {
  const pw = form.new_password
  checks.length = pw.length >= 8
  checks.case = /[a-z]/.test(pw) && /[A-Z]/.test(pw)
  checks.number = /[0-9]/.test(pw)
  checks.symbol = /[!@#$%^&*(),.?":{}|<>]/.test(pw)
}

const strengthPercent = computed(() => {
  const count = Object.values(checks).filter(Boolean).length
  return (count / 4) * 100
})

const strengthClass = computed(() => {
  if (strengthPercent.value <= 25) return 'weak'
  if (strengthPercent.value <= 75) return 'medium'
  return 'strong'
})

const isFormValid = computed(() => {
  return Object.values(checks).every(Boolean) && 
         form.new_password === form.new_password_confirmation &&
         form.current_password.length > 0
})

const handleSubmit = async () => {
  if (!isFormValid.value) return
  
  error.value = ''
  loading.value = true
  
  try {
    await updateAdminPassword(form)
    // Clear flag and redirect
    sessionStorage.setItem('ck_must_change_password', 'false')
    router.push({ name: 'Overview' })
  } catch (err) {
    error.value = err.message || 'Failed to update password. Please verify your current password.'
  } finally {
    loading.value = false
  }
}

const handleLogout = async () => {
  await adminLogout()
  router.push({ name: 'Login' })
}
</script>

<style scoped>
.force-pw-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eef2f6;
  padding: 1.5rem;
}

.split-container {
  display: flex;
  width: 100%;
  max-width: 900px;
  background: white;
  border-radius: 1.5rem;
  overflow: hidden;
  box-shadow: 0 20px 50px rgba(0,0,0,0.1);
}

.split-left {
  flex: 1;
  background: linear-gradient(135deg, #1a6b3c 0%, #3aaa68 100%);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: white;
  text-align: center;
}

.overlay {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at top right, rgba(255,255,255,0.1), transparent);
}

.content { position: relative; z-index: 2; }
.logo-circle { margin-bottom: 1.5rem; }
.brand-title { font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; }
.brand-desc { font-size: 0.9375rem; opacity: 0.9; line-height: 1.6; }

.split-right {
  flex: 1.2;
  padding: 3.5rem;
  display: flex;
  align-items: center;
}

.form-wrapper { width: 100%; max-width: 400px; margin: 0 auto; }
.form-header h2 { font-size: 1.5rem; font-weight: 700; color: #1e293b; margin-bottom: 0.25rem; }
.form-header p { font-size: 0.875rem; color: #64748b; margin-bottom: 2rem; }

.security-form { display: flex; flex-direction: column; gap: 1.25rem; }

.field-group { display: flex; flex-direction: column; gap: 0.5rem; }
.field-group label { font-size: 0.8125rem; font-weight: 600; color: #475569; }

.input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.field-icon {
  position: absolute;
  left: 1rem;
  color: #94a3b8;
}

.input-wrap input {
  width: 100%;
  padding: 0.75rem 3rem 0.75rem 2.75rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.toggle-visibility {
  position: absolute;
  right: 1rem;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  transition: color 0.2s;
  z-index: 10;
}

.toggle-visibility:hover {
  color: #3aaa68;
}

.input-wrap input:focus {
  outline: none;
  border-color: #3aaa68;
  box-shadow: 0 0 0 3px rgba(58, 170, 104, 0.1);
}

/* Strength Meter */
.strength-meter { margin-top: 0.5rem; }
.meter-bar { height: 4px; background: #e2e8f0; border-radius: 2px; overflow: hidden; margin-bottom: 0.75rem; }
.meter-fill { height: 100%; transition: all 0.4s ease; }
.meter-fill.weak { background: #ef4444; }
.meter-fill.medium { background: #f59e0b; }
.meter-fill.strong { background: #10b981; }

.requirements { list-style: none; padding: 0; display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; }
.requirements li { 
  font-size: 0.75rem; 
  color: #94a3b8; 
  display: flex; 
  align-items: center; 
  gap: 0.35rem; 
  transition: color 0.3s;
}
.requirements li.met { color: #10b981; font-weight: 500; }

.error-alert {
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border: 1px solid #fee2e2;
  border-radius: 0.75rem;
  color: #b91c1c;
  font-size: 0.8125rem;
}

.submit-btn {
  margin-top: 1rem;
  width: 100%;
  padding: 0.875rem;
  background: #1a6b3c;
  color: white;
  font-weight: 600;
  border: none;
  border-radius: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.submit-btn:hover:not(:disabled) { background: #14532d; transform: translateY(-1px); }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.logout-link {
  margin-top: 2rem;
  width: 100%;
  background: none;
  border: none;
  color: #64748b;
  font-size: 0.8125rem;
  text-decoration: underline;
  cursor: pointer;
}

@media (max-width: 768px) {
  .split-container { flex-direction: column; max-width: 450px; }
  .split-left { padding: 2rem; }
  .split-right { padding: 2rem; }
}

.loading-spinner {
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }
</style>
