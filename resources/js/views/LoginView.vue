<template>
  <div class="login-page">
    <!-- Background gradient -->
    <div class="login-page__bg"></div>

    <!-- Version number -->
    <div class="login-page__version font-mono">v1.0.2-Stable</div>

    <!-- Main login card -->
    <div class="login-page__card-wrapper">
      <div class="login-page__card ck-glass">
        <!-- Logo & Title -->
        <div class="login-page__header">
          <div class="login-page__logo">
            <span>CK</span>
          </div>
          <h1 class="login-page__title">CalorieKo</h1>
          <p class="login-page__subtitle">Admin & Data Management Portal</p>
        </div>

        <!-- Login Form -->
        <form @submit.prevent="handleLogin" class="login-page__form">
          <!-- Email -->
          <div class="login-page__field">
            <label for="email" class="login-page__label">Admin ID / Email</label>
            <div class="login-page__input-wrapper">
              <UserIcon class="login-page__input-icon" />
              <input
                id="email"
                type="text"
                v-model="email"
                placeholder="researcher@calorieko.ph"
                class="ck-input ck-input--with-icon"
              />
            </div>
          </div>

          <!-- Password -->
          <div class="login-page__field">
            <label for="password" class="login-page__label">Password</label>
            <div class="login-page__input-wrapper">
              <LockIcon class="login-page__input-icon" />
              <input
                id="password"
                type="password"
                v-model="password"
                placeholder="••••••••••••"
                class="ck-input ck-input--with-icon"
              />
            </div>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="login-page__error ck-alert ck-alert--error" style="background-color: #fef2f2; border: 1px solid #fecaca; color: #dc2626; padding: 0.75rem 1rem; border-radius: 0.5rem; font-size: 0.875rem; display: flex; align-items: flex-start; gap: 0.5rem; animation: fadeInUp 0.3s ease-out;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0; margin-top: 0.125rem;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
            <p style="margin: 0; line-height: 1.4;">{{ errorMessage }}</p>
          </div>

          <!-- Submit -->
          <button type="submit" class="ck-btn ck-btn--primary ck-btn--full login-page__submit" :disabled="loading">
            {{ loading ? 'Authenticating...' : 'Authorize Access' }}
          </button>
        </form>

        <!-- Security Footer -->
        <div class="login-page__security">
          <div class="login-page__security-row">
            <ShieldIcon class="login-page__security-icon" />
            <div>
              <p class="login-page__security-text">

              </p>
            </div>
          </div>
          <p class="login-page__security-text">
            <strong>Compliance:</strong> RA 10173 (Data Privacy Act of 2012)
          </p>
        </div>
      </div>

      <!-- Glow effect -->
      <div class="login-page__glow"></div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { User as UserIcon, Lock as LockIcon, Shield as ShieldIcon } from 'lucide-vue-next'
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
.login-page {
  width: 100%;
  min-height: 100vh;
  background: #e8eef3;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  position: relative;
  overflow: hidden;
}

.login-page__bg {
  position: absolute;
  inset: 0;
  background: linear-gradient(135deg, #e8eef3, #f0f4f8, #e8eef3);
  opacity: 0.7;
}

.login-page__version {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  font-size: 0.75rem;
  color: var(--ck-gray-600);
}

.login-page__card-wrapper {
  position: relative;
  width: 100%;
  max-width: 28rem;
  animation: fadeInUp 0.6s ease-out;
}

.login-page__card {
  position: relative;
  border-radius: var(--ck-radius-2xl);
  padding: 2.5rem;
}

.login-page__header {
  text-align: center;
  margin-bottom: 2.5rem;
}

.login-page__logo {
  width: 3rem;
  height: 3rem;
  background: var(--ck-primary);
  border-radius: var(--ck-radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 0.75rem;
}

.login-page__logo span {
  color: white;
  font-size: 1.5rem;
  font-weight: 700;
}

.login-page__title {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--ck-gray-800);
  margin-bottom: 0.5rem;
}

.login-page__subtitle {
  font-size: 0.875rem;
  color: var(--ck-gray-600);
  letter-spacing: 0.025em;
}

.login-page__form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.login-page__field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.login-page__label {
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--ck-gray-800);
}

.login-page__input-wrapper {
  position: relative;
}

.login-page__input-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  width: 1.25rem;
  height: 1.25rem;
  color: var(--ck-gray-500);
  pointer-events: none;
}

.login-page__submit {
  padding: 0.875rem;
  font-size: 0.9375rem;
  letter-spacing: 0.025em;
  box-shadow: var(--ck-shadow-lg);
}

.login-page__submit:hover {
  box-shadow: var(--ck-shadow-xl);
}

.login-page__session {
  display: flex;
  align-items: center;
  padding-top: 0.5rem;
}

.login-page__toggle {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.login-page__toggle-input {
  display: none;
}

.login-page__toggle-track {
  width: 2.75rem;
  height: 1.5rem;
  background: var(--ck-gray-300);
  border-radius: var(--ck-radius-full);
  position: relative;
  transition: background var(--ck-transition-base);
  flex-shrink: 0;
}

.login-page__toggle-input:checked + .login-page__toggle-track {
  background: var(--ck-primary);
}

.login-page__toggle-thumb {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 1.25rem;
  height: 1.25rem;
  background: white;
  border-radius: 50%;
  transition: transform var(--ck-transition-base);
  box-shadow: var(--ck-shadow-sm);
}

.login-page__toggle-input:checked + .login-page__toggle-track .login-page__toggle-thumb {
  transform: translateX(1.25rem);
}

.login-page__toggle-label {
  font-size: 0.875rem;
  color: var(--ck-gray-600);
}

.login-page__security {
  margin-top: 2rem;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(209, 213, 219, 0.5);
}

.login-page__security-row {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.login-page__security-icon {
  width: 1rem;
  height: 1rem;
  color: var(--ck-primary);
  margin-top: 2px;
  flex-shrink: 0;
}

.login-page__security-text {
  font-size: 0.75rem;
  color: var(--ck-gray-600);
  line-height: 1.5;
}

.login-page__security-text strong {
  color: var(--ck-gray-700);
  font-weight: 600;
}

.login-page__glow {
  position: absolute;
  inset: 0;
  z-index: -1;
  filter: blur(48px);
  opacity: 0.1;
  background: linear-gradient(135deg, var(--ck-primary), var(--ck-primary-hover));
  border-radius: var(--ck-radius-2xl);
  transform: scale(1.05);
}
</style>
