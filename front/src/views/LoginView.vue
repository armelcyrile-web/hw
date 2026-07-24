<!-- src/views/LoginView.vue -->
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { notifyError } from '@/services/alert'

const router = useRouter()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const loading = ref(false)
const showPassword = ref(false)

// ---------- Icônes SVG inline ----------
const shieldIcon = `<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>`
const mailIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 4L12 13 2 4"/></svg>`
const lockIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>`
const eyeIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`
const eyeOffIcon = `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
const arrowIcon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>`
const checkIcon = `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>`

async function handleLogin() {
  if (!email.value || !password.value) {
    notifyError('Veuillez remplir tous les champs.')
    return
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email.value)) {
    notifyError('Adresse email invalide.')
    return
  }

  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    if (authStore.isAdmin) {
      router.push('/admin')
    } else if (authStore.isTechnicien) {
      router.push('/technicien')
    } else {
      router.push('/client')
    }
  } catch (error) {
    const message = error.response?.data?.message || 'Erreur de connexion'
    notifyError(message)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="login-page">
    <div class="login-card">
      <!-- Logo -->
      <div class="logo-area">
        <span v-html="shieldIcon"></span>
        <h1 class="login-title">HostWatch</h1>
      </div>
      <p class="login-subtitle">Connectez-vous à votre espace</p>

      <form @submit.prevent="handleLogin" class="login-form" novalidate>
        <!-- Email -->
        <div class="input-group">
          <span class="input-icon left-icon" v-html="mailIcon"></span>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            placeholder="vous@exemple.com"
            class="form-input has-left-icon"
            :disabled="loading"
          />
        </div>

        <!-- Password -->
        <div class="input-group">
          <span class="input-icon left-icon" v-html="lockIcon"></span>
          <input
            id="password"
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            required
            placeholder="••••••••"
            class="form-input has-left-icon has-right-icon"
            :disabled="loading"
          />
          <button
            type="button"
            class="input-icon right-icon toggle-password"
            @click="showPassword = !showPassword"
            tabindex="-1"
            :aria-label="showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'"
          >
            <span v-html="showPassword ? eyeOffIcon : eyeIcon"></span>
          </button>
        </div>

        <!-- Remember me & Forgot password -->
        <div class="form-options">
          <label class="custom-checkbox">
            <input type="checkbox" v-model="rememberMe" class="hidden-checkbox" />
            <span class="checkbox-box" aria-hidden="true">
              <span v-if="rememberMe" class="checkbox-check" v-html="checkIcon"></span>
            </span>
            <span class="checkbox-label">Se souvenir de moi</span>
          </label>
          <a href="#" class="forgot-link">Mot de passe oublié ?</a>
        </div>

        <!-- Submit button -->
        <button type="submit" class="btn-login" :disabled="loading">
          <span v-if="!loading" class="btn-content">
            <span>Se connecter</span>
            <span class="arrow-icon" v-html="arrowIcon"></span>
          </span>
          <span v-else class="loading-dots">
            <span class="dot"></span>
            <span class="dot"></span>
            <span class="dot"></span>
          </span>
        </button>
      </form>
    </div>
  </div>
</template>

<style lang="scss" scoped>
@use "sass:color";
@use '@/assets/styles/variables.scss' as *;

.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: $color-neutral-light;
  padding: $spacing-md;
}

.login-card {
  background: $color-white;
  border-radius: 8px;
  padding: $spacing-xl;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.06);
  border: 1px solid #e5e7eb;
}

.logo-area {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-bottom: $spacing-md;
  gap: $spacing-sm;
}

.login-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: $color-primary;
  text-align: center;
  margin: 0;
  letter-spacing: -0.5px;
}

.login-subtitle {
  text-align: center;
  color: $color-neutral-dark;
  margin-bottom: $spacing-lg;
  font-size: 0.95rem;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: $spacing-md;
}

// ------ Input groups (border on the wrapper, not on the input) ------
.input-group {
  position: relative;
  display: flex;
  align-items: center;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background-color: $color-white;
  overflow: hidden; // ensures icons stay inside the border radius
}

.input-icon {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  color: $color-neutral-dark;
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 1;
}

.left-icon {
  left: 12px;
}

.right-icon {
  right: 12px;
}

.toggle-password {
  pointer-events: auto;
  cursor: pointer;
  background: none;
  border: none;
  padding: 4px;
  z-index: 2;
  &:hover {
    color: $color-primary;
  }
}

.form-input {
  flex: 1;
  border: none;
  outline: none;
  font-family: $font-family;
  font-size: 0.95rem;
  color: $color-primary;
  background: transparent;
  padding: 0.75rem 0.75rem;

  &:focus {
    // border color change handled by input-group's :focus-within (see below)
    outline: none;
  }

  &:disabled {
    background: #f9fafb;
  }
}

.has-left-icon {
  padding-left: 2.5rem;
}

.has-right-icon {
  padding-right: 2.5rem;
}

// Highlight the wrapper on focus
.input-group:focus-within {
  border-color: $color-accent;
}

// ------ Custom checkbox ------
.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
  color: $color-neutral-dark;
}

.custom-checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
}

.hidden-checkbox {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.checkbox-box {
  width: 18px;
  height: 18px;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: white;
  transition: border-color 0.15s, background 0.15s;
}

.hidden-checkbox:checked + .checkbox-box {
  background: $color-accent;
  border-color: $color-accent;
  .checkbox-check {
    color: white;
  }
}

.hidden-checkbox:focus-visible + .checkbox-box {
  outline: 2px solid $color-accent;
  outline-offset: 2px;
}

.checkbox-check {
  display: flex;
  align-items: center;
  justify-content: center;
  color: transparent;
}

.checkbox-label {
  user-select: none;
}

.forgot-link {
  color: $color-accent;
  text-decoration: none;
  &:hover {
    text-decoration: underline;
  }
}

// ------ Submit button ------
.btn-login {
  padding: 0.75rem 1rem;
  background-color: $color-accent;
  color: white;
  border: none;
  border-radius: 6px;
  font-family: $font-family;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
  margin-top: $spacing-sm;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 46px;

  &:hover:not(:disabled) {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }

  &:disabled {
    opacity: 0.7;
    cursor: not-allowed;
  }
}

.btn-content {
  display: flex;
  align-items: center;
  gap: 8px;
}

.arrow-icon {
  display: flex;
  align-items: center;
  transition: transform 0.15s;
}

.btn-login:hover:not(:disabled) .arrow-icon {
  transform: translateX(3px);
}

// ------ Loading dots animation ------
.loading-dots {
  display: flex;
  align-items: center;
  gap: 6px;
}

.dot {
  width: 6px;
  height: 6px;
  background-color: white;
  border-radius: 50%;
  animation: pulse 1.4s infinite ease-in-out;
}

.dot:nth-child(1) { animation-delay: 0s; }
.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes pulse {
  0%, 80%, 100% {
    opacity: 0.4;
    transform: scale(0.8);
  }
  40% {
    opacity: 1;
    transform: scale(1);
  }
}

// ------ Responsive ------
@media (max-width: $breakpoint-mobile) {
  .login-page {
    padding: $spacing-md;
    align-items: flex-start;
  }
  .login-card {
    padding: $spacing-lg;
    box-shadow: none;
    border: none;
  }
}
</style>