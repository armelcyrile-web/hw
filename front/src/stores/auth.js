// src/stores/auth.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  // Restauration synchrone depuis localStorage
  const savedToken = localStorage.getItem('token')
  const savedUser = localStorage.getItem('user')

  const token = ref(savedToken || null)
  const user = ref(savedUser ? JSON.parse(savedUser) : null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isClient = computed(() => user.value?.role === 'client')
  const isTechnicien = computed(() => user.value?.role === 'technicien')
  const isAdmin = computed(() => user.value?.role === 'administrateur')

  async function login(email, password) {
    try {
      const response = await api.post('/login', { email, password })
      token.value = response.data.token
      user.value = response.data.user

      // Persistance dans localStorage
      localStorage.setItem('token', token.value)
      localStorage.setItem('user', JSON.stringify(user.value))
      return true
    } catch (error) {
      throw error
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } catch (error) {
      // Même si la requête échoue, on nettoie côté client
    } finally {
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }

  // Rafraîchissement optionnel (non bloquant au démarrage)
  async function fetchUser() {
    try {
      const response = await api.get('/me')
      user.value = response.data.user
      localStorage.setItem('user', JSON.stringify(user.value))
    } catch (error) {
      // Token invalide ou expiré
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      throw error
    }
  }

  // Plus nécessaire, la restauration est synchrone
  // async function initialize() { ... }

  return {
    token,
    user,
    isAuthenticated,
    isClient,
    isTechnicien,
    isAdmin,
    login,
    logout,
    fetchUser
  }
})