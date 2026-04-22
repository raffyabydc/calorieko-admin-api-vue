<template>
  <div class="login-page">
    <!-- Split Container -->
    <div class="login-split">

      <!-- LEFT PANEL — Green Nature Side -->
      <div class="login-split__left">
        <div class="left-overlay"></div>
        <!-- Floating leaf shapes -->
        <div class="leaf leaf--1"></div>
        <div class="leaf leaf--2"></div>
        <div class="leaf leaf--3"></div>
        <div class="leaf leaf--4"></div>
        <div class="leaf leaf--5"></div>

        <!-- Logo -->
        <div class="left-content">
          <div class="brand-circle">
            <svg viewBox="0 0 80 80" width="80" height="80" xmlns="http://www.w3.org/2000/svg">
              <!-- White circle background against green leaf -->
              <circle cx="40" cy="40" r="32" fill="rgba(255,255,255,0.95)"/>
              <g transform="translate(14,14) scale(2.16)">
                <path fill="#4CAF50" d="M19.95 5.97a2.018 2.018 0 0 0-1.92-1.92c-.7-.03-1.37-.05-2.02-.05c-5.03 0-8.52.97-10.46 2.91c-3.68 3.68-3.15 8.9.09 11.9h.01a16 16 0 0 1 7.67-8.8c-.21.18-4.7 3.58-5.51 10.25c1.05.48 2.2.75 3.36.75c2.05 0 4.16-.8 5.92-2.55c2.19-2.2 3.14-6.36 2.86-12.49z"/>
              </g>
            </svg>
          </div>
          <h2 class="brand-name">CalorieKo</h2>
          <p class="brand-tagline">Nutrition Intelligence Platform</p>
        </div>

        <!-- Version badge -->
        <div class="version-badge">v1.0.2-Stable</div>
      </div>

      <!-- RIGHT PANEL — Form Side -->
      <div class="login-split__right">
        <div class="form-container">
          <h1 class="form-title">Welcome!</h1>
          <p class="form-subtitle">Admin & Data Management Portal</p>

          <form @submit.prevent="handleLogin" class="login-form">
            <!-- Email Field -->
            <div class="field">
              <div class="field__input-wrap">
                <UserIcon class="field__icon" />
                <input
                  id="login-email"
                  type="text"
                  v-model="email"
                  placeholder="Admin ID / Email"
                  class="field__input"
                  autocomplete="username"
                />
              </div>
            </div>

            <!-- Password Field -->
            <div class="field">
              <div class="field__input-wrap">
                <LockIcon class="field__icon" />
                <input
                  id="login-password"
                  type="password"
                  v-model="password"
                  placeholder="Password"
                  class="field__input"
                  autocomplete="current-password"
                />
              </div>
            </div>

            <!-- Error Message -->
            <Transition name="shake">
              <div v-if="errorMessage" class="error-msg">
                <AlertCircleIcon :size="16" />
                <p>{{ errorMessage }}</p>
              </div>
            </Transition>

            <!-- Submit Button -->
            <button type="submit" class="submit-btn" :disabled="loading">
              <span v-if="!loading">Authorize Access</span>
              <span v-else class="loading-state">
                <span class="spinner"></span>
                Authenticating...
              </span>
            </button>
          </form>

          <!-- Security Footer -->
          <div class="security-footer">
            <div class="security-footer__row">
              <ShieldIcon :size="14" class="security-footer__icon" />
              <span>AES-256 Data Protection: Active</span>
            </div>
            <p class="security-footer__compliance">
              <strong>Compliance:</strong> RA 10173 (Data Privacy Act of 2012)
            </p>
          </div>
        </div>
      </div>
p
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { User as UserIcon, Lock as LockIcon, Shield as ShieldIcon, AlertCircle as AlertCircleIcon } from 'lucide-vue-next'
import { adminLogin } from '../services/api.js'

const router = useRouter()
const email = ref('')
const password = ref('')
const sessionPersistence = ref(false)
const errorMessage = ref('')
const loading = ref(false)

const handleLogin = async () => {
  errorMessage.value = ''
  loading.value = true

  try {
    const data = await adminLogin(email.value, password.value)

    // Store the token and login state
    sessionStorage.setItem('ck_logged_in', 'true')
    sessionStorage.setItem('ck_token', data.token)
    sessionStorage.setItem('ck_email', data.email)

    router.push({ name: 'Overview' })
  } catch (err) {
    errorMessage.value = err.message || 'Invalid email or password.'
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════
   LOGIN PAGE — Split Panel Nature Theme
   ═══════════════════════════════════════════════ */

.login-page {
  width: 100%;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #e8eef3;
  padding: 1.5rem;
}

.login-split {
  display: flex;
  width: 100%;
  max-width: 860px;
  min-height: 520px;
  border-radius: 1.25rem;
  overflow: hidden;
  box-shadow:
    0 25px 60px rgba(0, 0, 0, 0.12),
    0 8px 24px rgba(0, 0, 0, 0.08);
  animation: cardRise 0.7s cubic-bezier(0.16, 1, 0.3, 1);
}

/* ─── LEFT PANEL ─── */
.login-split__left {
  flex: 1;
  position: relative;
  background: linear-gradient(160deg, #1a6b3c 0%, #2d8f55 30%, #3aaa68 60%, #5dc07e 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.left-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(circle at 30% 70%, rgba(255,255,255,0.08) 0%, transparent 50%),
    radial-gradient(circle at 70% 20%, rgba(0,0,0,0.1) 0%, transparent 50%);
}

/* Floating leaf shapes (CSS-only) */
.leaf {
  position: absolute;
  border-radius: 50% 0 50% 0;
  background: rgba(255, 255, 255, 0.06);
  animation: leafFloat 20s ease-in-out infinite;
}
.leaf--1 { width: 120px; height: 120px; top: -20px; left: -30px; animation-delay: 0s; }
.leaf--2 { width: 80px; height: 80px; bottom: 40px; right: -20px; animation-delay: -5s; transform: rotate(45deg); }
.leaf--3 { width: 60px; height: 60px; top: 30%; right: 15%; animation-delay: -10s; background: rgba(255,255,255,0.04); }
.leaf--4 { width: 100px; height: 100px; bottom: -20px; left: 20%; animation-delay: -3s; transform: rotate(20deg); }
.leaf--5 { width: 40px; height: 40px; top: 15%; left: 25%; animation-delay: -8s; background: rgba(255,255,255,0.08); }

.left-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: white;
}

.brand-circle {
  margin: 0 auto 1.25rem;
  animation: gentlePulse 4s ease-in-out infinite;
}

.brand-name {
  font-size: 1.75rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  margin-bottom: 0.35rem;
  text-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.brand-tagline {
  font-size: 0.8125rem;
  opacity: 0.75;
  font-weight: 400;
  letter-spacing: 0.04em;
}

.version-badge {
  position: absolute;
  bottom: 1rem;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.6875rem;
  color: rgba(255,255,255,0.45);
  font-family: 'JetBrains Mono', 'Fira Code', monospace;
  letter-spacing: 0.03em;
}

/* ─── RIGHT PANEL ─── */
.login-split__right {
  flex: 1;
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 3rem 2.5rem;
}

.form-container {
  width: 100%;
  max-width: 320px;
}

.form-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1a2e;
  margin-bottom: 0.25rem;
  letter-spacing: -0.02em;
}

.form-subtitle {
  font-size: 0.8125rem;
  color: #94a3b8;
  margin-bottom: 2rem;
}

/* ─── FORM FIELDS ─── */
.login-form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.field__input-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.field__icon {
  position: absolute;
  left: 1rem;
  width: 1.125rem;
  height: 1.125rem;
  color: #94a3b8;
  pointer-events: none;
  transition: color 0.2s;
}

.field__input {
  width: 100%;
  padding: 0.875rem 1rem 0.875rem 3rem;
  border: 1.5px solid #e2e8f0;
  border-radius: 0.75rem;
  font-size: 0.875rem;
  color: #1e293b;
  background: #f8fafc;
  outline: none;
  transition: all 0.25s ease;
}

.field__input::placeholder {
  color: #94a3b8;
}

.field__input:focus {
  border-color: #3aaa68;
  background: white;
  box-shadow: 0 0 0 3px rgba(58, 170, 104, 0.1);
}

.field__input:focus + .field__icon,
.field__input-wrap:focus-within .field__icon {
  color: #3aaa68;
}

/* ─── ERROR ─── */
.error-msg {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.625rem;
  color: #dc2626;
  font-size: 0.8125rem;
  line-height: 1.4;
}

.error-msg p {
  margin: 0;
}

/* ─── SUBMIT BUTTON ─── */
.submit-btn {
  margin-top: 0.5rem;
  width: 100%;
  padding: 0.9375rem 1rem;
  background: linear-gradient(135deg, #2d8f55, #3aaa68);
  color: white;
  font-size: 0.9375rem;
  font-weight: 600;
  border: none;
  border-radius: 0.75rem;
  cursor: pointer;
  letter-spacing: 0.02em;
  transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 4px 14px rgba(45, 143, 85, 0.3);
}

.submit-btn:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 6px 20px rgba(45, 143, 85, 0.4);
  background: linear-gradient(135deg, #268049, #34a060);
}

.submit-btn:active:not(:disabled) {
  transform: translateY(0);
}

.submit-btn:disabled {
  opacity: 0.75;
  cursor: not-allowed;
}

.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.625rem;
}

.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

/* ─── SECURITY FOOTER ─── */
.security-footer {
  margin-top: 2rem;
  padding-top: 1.25rem;
  border-top: 1px solid #f1f5f9;
}

.security-footer__row {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  padding: 0.375rem 0.75rem;
  background: #ecfdf5;
  border-radius: 999px;
  width: fit-content;
  font-size: 0.6875rem;
  font-weight: 600;
  color: #059669;
}

.security-footer__icon {
  flex-shrink: 0;
}

.security-footer__compliance {
  font-size: 0.6875rem;
  color: #94a3b8;
  line-height: 1.5;
}

.security-footer__compliance strong {
  color: #64748b;
}

/* ═══════════════════════════════════════════════
   ANIMATIONS
   ═══════════════════════════════════════════════ */

@keyframes cardRise {
  from { opacity: 0; transform: translateY(30px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes leafFloat {
  0%, 100% { transform: translateY(0) rotate(0deg); }
  25%      { transform: translateY(-8px) rotate(3deg); }
  50%      { transform: translateY(4px) rotate(-2deg); }
  75%      { transform: translateY(-4px) rotate(1deg); }
}

@keyframes gentlePulse {
  0%, 100% { transform: scale(1); opacity: 1; }
  50%      { transform: scale(1.04); opacity: 0.9; }
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.shake-enter-active {
  animation: shakeIn 0.4s ease-out;
}

@keyframes shakeIn {
  0%   { opacity: 0; transform: translateX(-10px); }
  25%  { transform: translateX(6px); }
  50%  { transform: translateX(-4px); }
  75%  { transform: translateX(2px); }
  100% { opacity: 1; transform: translateX(0); }
}

/* ═══════════════════════════════════════════════
   RESPONSIVE
   ═══════════════════════════════════════════════ */

@media (max-width: 720px) {
  .login-split {
    flex-direction: column;
    max-width: 420px;
    min-height: auto;
  }

  .login-split__left {
    min-height: 200px;
    padding: 2rem 1rem;
  }

  .brand-name { font-size: 1.375rem; }

  .login-split__right {
    padding: 2rem 1.5rem;
  }

  .form-title { font-size: 1.5rem; }
}
</style>
