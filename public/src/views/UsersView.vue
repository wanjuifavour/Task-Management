<template>
  <div class="users-page">
    <header class="page-header">
      <div class="header-content">
        <h1>User Management</h1>
        <div class="header-actions">
          <button @click="showCreateModal = true" class="create-btn">
            Add User
          </button>
          <router-link to="/dashboard" class="back-btn">Back to Dashboard</router-link>
        </div>
      </div>
    </header>

    <main class="users-content">
      <!-- Users List -->
      <div class="users-list">
        <div v-for="user in users" :key="user.id" class="user-card">
          <div class="user-info">
            <div class="user-details">
              <h3>{{ user.username }}</h3>
              <p class="user-email">{{ user.email }}</p>
              <span :class="['role-badge', user.role]">{{ user.role }}</span>
            </div>
            
            <div class="user-meta">
              <span>Created: {{ formatDate(user.created_at) }}</span>
            </div>
          </div>
          
          <div class="user-actions">
            <button @click="editUser(user)" class="edit-btn">Edit</button>
            <button @click="deleteUser(user.id)" class="delete-btn">Delete</button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="users.length === 0" class="empty-state">
        <h3>No users found</h3>
        <p>Start by adding your first user.</p>
      </div>
    </main>

    <!-- Create/Edit User Modal -->
    <div v-if="showCreateModal || showEditModal" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <h2>{{ showEditModal ? 'Edit User' : 'Add New User' }}</h2>
        <form @submit.prevent="showEditModal ? updateUser() : createUser()">
          <div class="form-group">
            <label>Username</label>
            <input v-model="userForm.username" type="text" required />
          </div>
          
          <div class="form-group">
            <label>Email</label>
            <input v-model="userForm.email" type="email" required />
          </div>
          
          <div class="form-group">
            <label>Password</label>
            <input 
              v-model="userForm.password" 
              type="password" 
              :required="!showEditModal" 
              :disabled="showEditModal && userForm.role === 'admin'"
            />
            <small v-if="showEditModal && userForm.role === 'admin'">
              Password changes are disabled for administrator accounts
            </small>
            <small v-else-if="showEditModal">
              Leave blank to keep current password
            </small>
          </div>
          
          <div class="form-group">
            <label>Role</label>
            <select v-model="userForm.role">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="cancel-btn">
              Cancel
            </button>
            <button type="submit" class="submit-btn">
              {{ showEditModal ? 'Update' : 'Create' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface User {
  id: number
  username: string
  email: string
  role: string
  created_at: string
}
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()

const users = ref<User[]>([])
const showCreateModal = ref(false)
const showEditModal = ref(false)
const editingUserId = ref<number | null>(null)
const userForm = ref({
  username: '',
  email: '',
  password: '',
  role: 'user'
})

const API_BASE = import.meta.env.VITE_API_URL

async function loadUsers() {
  try {
    const response = await fetch(`${API_BASE}/users`, {
      credentials: 'include'
    })
    
    if (response.ok) {
      const data = await response.json()
      users.value = data.users || []
    }
  } catch (error) {
    console.error('Failed to load users:', error)
  }
}

async function createUser() {
  try {
    const response = await fetch(`${API_BASE}/users`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userForm.value),
      credentials: 'include'
    })
    
    if (response.ok) {
      closeModal()
      await loadUsers()
    }
  } catch (error) {
    console.error('Failed to create user:', error)
  }
}

async function updateUser() {
  if (!editingUserId.value) return
  
  try {
    const response = await fetch(`${API_BASE}/users/${editingUserId.value}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(userForm.value),
      credentials: 'include'
    })
    
    if (response.ok) {
      closeModal()
      await loadUsers()
    }
  } catch (error) {
    console.error('Failed to update user:', error)
  }
}

async function deleteUser(userId: number) {
  if (!confirm('Are you sure you want to delete this user?')) return
  
  try {
    const response = await fetch(`${API_BASE}/users/${userId}`, {
      method: 'DELETE',
      credentials: 'include'
    })
    
    if (response.ok) {
      await loadUsers()
    }
  } catch (error) {
    console.error('Failed to delete user:', error)
  }
}

function editUser(user: any) {
  editingUserId.value = user.id
  userForm.value = {
    username: user.username,
    email: user.email,
    password: user.role === 'admin' ? '' : '', // Always clear password for security
    role: user.role
  }
  showEditModal.value = true
}

function closeModal() {
  showCreateModal.value = false
  showEditModal.value = false
  editingUserId.value = null
  userForm.value = {
    username: '',
    email: '',
    password: '',
    role: 'user'
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

onMounted(() => {
  if (!authStore.isAdmin) {
    router.push('/dashboard')
    return
  }
  
  loadUsers()
})
</script>

<style scoped>
.users-page {
  min-height: 100vh;
  width: 100%;
  background-color: #f8f9fa;
  overflow-x: hidden;
}

.page-header {
  background: white;
  border-bottom: 1px solid #e9ecef;
  padding: 20px 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-content h1 {
  margin: 0;
  color: #333;
  font-size: 24px;
  font-weight: 600;
}

.header-actions {
  display: flex;
  gap: 16px;
}

.create-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: transform 0.2s;
}

.create-btn:hover {
  transform: translateY(-2px);
}

.back-btn {
  background: #6c757d;
  color: white;
  text-decoration: none;
  padding: 10px 20px;
  border-radius: 6px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.back-btn:hover {
  background: #5a6268;
}

.users-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}

.users-list {
  display: grid;
  gap: 20px;
}

.user-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.user-info {
  flex: 1;
}

.user-details {
  margin-bottom: 8px;
}

.user-details h3 {
  margin: 0 0 4px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.user-email {
  margin: 0 0 8px 0;
  color: #666;
  font-size: 14px;
}

.role-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.role-badge.admin {
  background: #f8d7da;
  color: #721c24;
}

.role-badge.user {
  background: #d4edda;
  color: #155724;
}

.user-meta {
  font-size: 12px;
  color: #999;
}

.user-actions {
  display: flex;
  gap: 12px;
}

.edit-btn, .delete-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.edit-btn {
  background: #28a745;
  color: white;
}

.edit-btn:hover {
  background: #1e7e34;
}

.delete-btn {
  background: #dc3545;
  color: white;
}

.delete-btn:hover {
  background: #c82333;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.empty-state h3 {
  margin: 0 0 12px 0;
  color: #333;
}

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
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  padding: 30px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
}

.modal h2 {
  margin: 0 0 20px 0;
  color: #333;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-group input:disabled {
  background-color: #f5f5f5;
  color: #999;
  cursor: not-allowed;
}

.form-group small {
  display: block;
  margin-top: 4px;
  color: #666;
  font-size: 12px;
}

.modal-actions {
  display: flex;
  gap: 12px;
  justify-content: flex-end;
  margin-top: 20px;
}

.cancel-btn {
  background: #6c757d;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

.submit-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 10px 20px;
  border-radius: 4px;
  cursor: pointer;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }
  
  .user-card {
    flex-direction: column;
    align-items: flex-start;
    gap: 16px;
  }
  
  .user-actions {
    width: 100%;
    justify-content: flex-end;
  }
}
</style> 