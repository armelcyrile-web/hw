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
      <h1 class="login-title">HostWatch</h1>
      <p class="login-subtitle">Connexion à votre espace</p>
      <form @submit.prevent="handleLogin" class="login-form" novalidate>
        <div class="form-group">
          <label for="email" class="form-label">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            required
            placeholder="vous@exemple.com"
            class="form-input"
            :disabled="loading"
          />
        </div>
        <div class="form-group">
          <label for="password" class="form-label">Mot de passe</label>
          <input
            id="password"
            v-model="password"
            type="password"
            required
            placeholder="••••••••"
            class="form-input"
            :disabled="loading"
          />
        </div>
        <div class="form-options">
          <label class="checkbox-label">
            <input type="checkbox" v-model="rememberMe" /> Se souvenir de moi
            <!-- TODO gérer la durée de session si besoin -->
          </label>
          <a href="#" class="forgot-link">Mot de passe oublié ?</a>
        </div>
        <button type="submit" class="btn-login" :disabled="loading">
          {{ loading ? 'Connexion...' : 'Se connecter' }}
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
  border-radius: $border-radius;
  padding: $spacing-xl;
  width: 100%;
  max-width: 420px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  border: 1px solid $color-border;
}

.login-title {
  font-size: 2rem;
  font-weight: 600;
  color: $color-primary;
  text-align: center;
  margin-bottom: $spacing-xs;
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

.form-group {
  display: flex;
  flex-direction: column;
  gap: $spacing-xs;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 500;
  color: $color-primary;
}

.form-input {
  padding: 0.65rem 0.75rem;
  border: 1px solid $color-border;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.95rem;
  outline: none;
  transition: border-color 0.15s;
  &:focus {
    border-color: $color-accent;
  }
}

.form-options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
  color: $color-neutral-dark;
}

.checkbox-label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  cursor: pointer;
  input {
    accent-color: $color-accent;
  }
}

.forgot-link {
  color: $color-accent;
  text-decoration: none;
  &:hover {
    text-decoration: underline;
  }
}

.btn-login {
  padding: 0.65rem 1rem;
  background-color: $color-accent;
  color: white;
  border: none;
  border-radius: $border-radius;
  font-family: $font-family;
  font-size: 0.95rem;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
  margin-top: $spacing-sm;
  &:hover:not(:disabled) {
    background-color: color.adjust($color-accent, $lightness: -8%);
  }
  &:disabled {
    opacity: 0.65;
    cursor: not-allowed;
  }
}

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