import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User } from '@/types'

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const isAuthenticated = computed(() => !!user.value)
    const isAdmin = computed(() => user.value?.role === 'admin')

    const API_BASE = import.meta.env.VITE_API_URL

    async function login(email: string, password: string) {
        try {
            const response = await fetch(`${API_BASE}/auth`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'login',
                    email,
                    password
                }),
                credentials: 'include'
            })

            if (!response.ok) {
                const error = await response.json()
                throw new Error(error.error || 'Login failed')
            }

            const data = await response.json()
            user.value = data.user

            // Store user in localStorage for persistence
            localStorage.setItem('user', JSON.stringify(data.user))

            return data
        } catch (error) {
            console.error('Login error:', error)
            throw error
        }
    }

    async function register(username: string, email: string, password: string) {
        try {
            const response = await fetch(`${API_BASE}/auth`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'register',
                    username,
                    email,
                    password
                }),
                credentials: 'include'
            })

            if (!response.ok) {
                const error = await response.json()
                throw new Error(error.error || 'Registration failed')
            }

            const data = await response.json()
            user.value = data.user

            // Store user in localStorage for persistence
            localStorage.setItem('user', JSON.stringify(data.user))

            return data
        } catch (error) {
            console.error('Registration error:', error)
            throw error
        }
    }

    async function logout() {
        try {
            await fetch(`${API_BASE}/auth`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'logout'
                }),
                credentials: 'include'
            })
        } catch (error) {
            console.error('Logout error:', error)
        } finally {
            user.value = null
            localStorage.removeItem('user')
        }
    }

    async function checkAuth() {
        try {
            const response = await fetch(`${API_BASE}/auth`, {
                credentials: 'include'
            })

            if (response.ok) {
                const data = await response.json()
                if (data.authenticated) {
                    user.value = data.user
                    return true
                }
            }

            // Check localStorage as fallback
            const storedUser = localStorage.getItem('user')
            if (storedUser) {
                user.value = JSON.parse(storedUser)
                return true
            }

            return false
        } catch (error) {
            console.error('Auth check error:', error)
            return false
        }
    }

    // Initialize auth state from localStorage
    function initAuth() {
        try {
            const storedUser = localStorage.getItem('user')
            if (storedUser) {
                const parsedUser = JSON.parse(storedUser)
                user.value = parsedUser
                console.log('Auth initialized from localStorage:', parsedUser)
            } else {
                console.log('No stored user found in localStorage')
            }
        } catch (error) {
            console.error('Error initializing auth from localStorage:', error)
            localStorage.removeItem('user') // Clear corrupted data
        }
    }

    // Auto-initialize on store creation
    initAuth()

    return {
        user,
        isAuthenticated,
        isAdmin,
        login,
        register,
        logout,
        checkAuth,
        initAuth
    }
}) 