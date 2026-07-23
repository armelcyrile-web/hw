// src/stores/auth.js

import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '@/services/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || null)
  const user = ref(null)

  const isAuthenticated = computed(() => !!token.value && !!user.value)
  const isClient = computed(() => user.value?.role === 'client')
  const isTechnicien = computed(() => user.value?.role === 'technicien')
  const isAdmin = computed(() => user.value?.role === 'administrateur')

  async function login(email, password) {
    try {
      const response = await api.post('/login', { email, password })
      token.value = response.data.token
      user.value = response.data.user
      localStorage.setItem('token', token.value)
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
    }
  }

  async function fetchUser() {
    try {
      const response = await api.get('/me')
      user.value = response.data.user
    } catch (error) {
      // Token invalide ou expiré
      token.value = null
      user.value = null
      localStorage.removeItem('token')
      throw error
    }
  }

  async function initialize() {
    if (token.value) {
      try {
        await fetchUser()
      } catch {
        // Déjà nettoyé dans fetchUser
      }
    }
  }

  return {
    token,
    user,
    isAuthenticated,
    isClient,
    isTechnicien,
    isAdmin,
    login,
    logout,
    fetchUser,
    initialize
  }
})