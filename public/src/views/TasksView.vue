<template>
  <!-- Auth Loading State -->
  <div v-if="authLoading" class="auth-loading">
    <h3>Loading authentication...</h3>
  </div>
  
  <!-- Main Content -->
  <div v-else-if="isAuthReady" class="tasks-page">
    <header class="page-header">
      <div class="header-content">
        <h1>Tasks</h1>
        <div class="header-actions">
          <button v-if="isCurrentUserAdmin" @click="openCreateModal" class="create-btn">
            ➕ Create New Task
          </button>
          <router-link to="/dashboard" class="back-btn">Back to Dashboard</router-link>
        </div>
      </div>
    </header>

    <main class="tasks-content">
      <!-- Loading State -->
      <div v-if="isLoading" class="loading-state">
        <h3>Loading tasks...</h3>
      </div>

      <!-- Filters -->
      <div v-else class="filters">
        <div class="filter-group">
          <label>Status:</label>
          <select v-model="statusFilter" class="filter-select">
            <option value="">All</option>
            <option value="Pending">Pending</option>
            <option value="In Progress">In Progress</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
        
        <div class="filter-group">
          <label>Priority:</label>
          <select v-model="priorityFilter" class="filter-select">
            <option value="">All</option>
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>
      </div>

      <!-- Task List -->
      <div v-if="!isLoading" class="task-list">
        <div v-for="task in filteredTasks" :key="task.id" class="task-card">
          <div class="task-header">
            <h3>{{ task.title }}</h3>
            <div class="task-badges">
              <span :class="['status-badge', task.status.toLowerCase()]">
                {{ task.status }}
              </span>
              <span :class="['priority-badge', task.priority.toLowerCase()]">
                {{ task.priority }}
              </span>
            </div>
          </div>
          
          <p class="task-description">{{ task.description }}</p>
          
          <div class="task-meta">
            <div class="meta-item">
              <strong>Assigned to:</strong> {{ task.assigned_to_username }}
            </div>
            <div class="meta-item">
              <strong>Assigned by:</strong> {{ task.assigned_by_username }}
            </div>
            <div v-if="task.deadline" class="meta-item">
              <strong>Due:</strong> {{ formatDate(task.deadline) }}
            </div>
            <div class="meta-item">
              <strong>Created:</strong> {{ formatDate(task.created_at) }}
            </div>
          </div>
          
          <div class="task-actions">
            <button 
              v-if="!isCurrentUserAdmin || (currentUserId && task.assigned_to === currentUserId)"
              @click="updateTaskStatus(task.id, getNextStatus(task.status))"
              :disabled="task.status === 'Completed'"
              class="status-btn"
            >
              Mark as {{ getNextStatus(task.status) }}
            </button>
            
            <button v-if="isCurrentUserAdmin" @click="editTask(task)" class="edit-btn">
              Edit
            </button>
            
            <button v-if="isCurrentUserAdmin" @click="deleteTask(task.id)" class="delete-btn">
              Delete
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="filteredTasks.length === 0" class="empty-state">
        <h3>No tasks found</h3>
        <p v-if="isCurrentUserAdmin && tasks.length === 0">
          No tasks have been created yet. Click "Create New Task" to get started.
        </p>
        <p v-else-if="!isCurrentUserAdmin && tasks.length === 0">
          No tasks have been assigned to you yet.
        </p>
        <p v-else>
          There are no tasks matching your current filters.
        </p>
        <button v-if="isCurrentUserAdmin" @click="openCreateModal" class="create-btn empty-state-btn">
          ➕ Create Your First Task
        </button>
      </div>
    </main>

    <!-- Create/Edit Task Modal -->
    <div v-if="showCreateModal" class="modal-overlay" @click="closeModal">
      <div class="modal" @click.stop>
        <div class="modal-header">
          <h2>{{ editingTaskId ? 'Edit Task' : 'Create New Task' }}</h2>
          <button @click="closeModal" class="modal-close">&times;</button>
        </div>
        <form @submit.prevent="createTask">
          <div class="form-group">
            <label>Title *</label>
            <input v-model="newTask.title" type="text" required />
          </div>
          
          <div class="form-group">
            <label>Description</label>
            <textarea v-model="newTask.description" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label>Assign to *</label>
            <select v-model="newTask.assigned_to" required>
              <option value="">Select user</option>
              <option v-for="userItem in nonAdminUsers" :key="userItem.id" :value="userItem.id">
                {{ userItem.username }} ({{ userItem.email }})
              </option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Priority</label>
            <select v-model="newTask.priority">
              <option value="Low">Low</option>
              <option value="Medium">Medium</option>
              <option value="High">High</option>
            </select>
          </div>
          
          <div class="form-group">
            <label>Deadline</label>
            <input v-model="newTask.deadline" type="date" />
          </div>
          
          <div class="modal-actions">
            <button type="button" @click="closeModal" class="cancel-btn">
              Cancel
            </button>
            <button type="submit" class="submit-btn">
              {{ editingTaskId ? 'Update Task' : 'Create Task' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
  
  <!-- Auth Loading State -->
  <div v-else class="auth-loading">
    <h3>Loading authentication...</h3>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'
import type { Task, User, NewTask } from '@/types'

const authStore = useAuthStore()
const router = useRouter()

const tasks = ref<Task[]>([])
const users = ref<User[]>([])
const statusFilter = ref('')
const priorityFilter = ref('')
const showCreateModal = ref(false)
const editingTaskId = ref<number | null>(null)
const isLoading = ref(true)
const authLoading = ref(true)
const newTask = ref<NewTask>({
  title: '',
  description: '',
  assigned_to: '',
  priority: 'Medium',
  deadline: ''
})

const API_BASE = import.meta.env.VITE_API_URL

const filteredTasks = computed(() => {
  return tasks.value.filter(task => {
    const statusMatch = !statusFilter.value || task.status === statusFilter.value
    const priorityMatch = !priorityFilter.value || task.priority === priorityFilter.value
    return statusMatch && priorityMatch
  })
})

// Computed property for non-admin users
const nonAdminUsers = computed(() => {
  return users.value.filter(u => u.role !== 'admin')
})

// Safe computed property for user ID
const currentUserId = computed(() => {
  return authStore.user?.id || null
})

// Safe computed property for admin check
const isCurrentUserAdmin = computed(() => {
  return authStore.isAdmin || false
})

// Check if auth is ready
const isAuthReady = computed(() => {
  return !authLoading.value && authStore.user !== null
})

async function loadTasks() {
  try {
    const response = await fetch(`${API_BASE}/tasks`, {
      credentials: 'include'
    })
    
    if (response.ok) {
      const data = await response.json()
      tasks.value = data.tasks || []
    }
  } catch (error) {
    console.error('Failed to load tasks:', error)
  }
}

async function loadUsers() {
  if (!isCurrentUserAdmin.value) return
  
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

async function updateTaskStatus(taskId: number, newStatus: string) {
  try {
    const response = await fetch(`${API_BASE}/tasks/${taskId}`, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ status: newStatus }),
      credentials: 'include'
    })
    
    if (response.ok) {
      await loadTasks()
    }
  } catch (error) {
    console.error('Failed to update task status:', error)
  }
}

async function createTask() {
  console.log('Creating/updating task:', newTask.value)
  try {
    const url = editingTaskId.value 
      ? `${API_BASE}/tasks/${editingTaskId.value}`
      : `${API_BASE}/tasks`
    
    const method = editingTaskId.value ? 'PUT' : 'POST'
    
    console.log('Making request to:', url, 'with method:', method)
    
    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(newTask.value),
      credentials: 'include'
    })
    
    console.log('Response status:', response.status)
    
    if (response.ok) {
      const result = await response.json()
      console.log('Task created/updated successfully:', result)
      showCreateModal.value = false
      editingTaskId.value = null
      newTask.value = {
        title: '',
        description: '',
        assigned_to: '',
        priority: 'Medium',
        deadline: ''
      }
      await loadTasks()
    } else {
      const errorText = await response.text()
      console.error('Failed to create/update task:', response.status, errorText)
    }
  } catch (error) {
    console.error('Failed to create/update task:', error)
  }
}

async function deleteTask(taskId: number) {
  if (!confirm('Are you sure you want to delete this task?')) return
  
  try {
    const response = await fetch(`${API_BASE}/tasks/${taskId}`, {
      method: 'DELETE',
      credentials: 'include'
    })
    
    if (response.ok) {
      await loadTasks()
    }
  } catch (error) {
    console.error('Failed to delete task:', error)
  }
}

function editTask(task: Task) {
  console.log('Edit task clicked:', task)
  
  // Validate that we have a user and the task exists
  if (!authStore.user) {
    console.error('No authenticated user found')
    return
  }
  
  if (!task || !task.id) {
    console.error('Invalid task provided to editTask')
    return
  }
  
  // Populate the form with task data
  newTask.value = {
    title: task.title || '',
    description: task.description || '',
    assigned_to: task.assigned_to?.toString() || '',
    priority: task.priority || 'Medium',
    deadline: task.deadline ? task.deadline.split('T')[0] : ''
  }
  
  // Store the task ID for update
  editingTaskId.value = task.id
  showCreateModal.value = true
  console.log('Modal should be visible:', showCreateModal.value)
}

function getNextStatus(currentStatus: string): string {
  switch (currentStatus) {
    case 'Pending': return 'In Progress'
    case 'In Progress': return 'Completed'
    default: return 'Completed'
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}

function closeModal() {
  console.log('Closing modal')
  showCreateModal.value = false
  editingTaskId.value = null
  newTask.value = {
    title: '',
    description: '',
    assigned_to: '',
    priority: 'Medium',
    deadline: ''
  }
}

function openCreateModal() {
  console.log('Opening create modal')
  
  // Validate that we have an authenticated user
  if (!authStore.user) {
    console.error('No authenticated user found')
    router.push('/login')
    return
  }
  
  if (!authStore.isAdmin) {
    console.error('User is not admin')
    return
  }
  
  editingTaskId.value = null
  newTask.value = {
    title: '',
    description: '',
    assigned_to: '',
    priority: 'Medium',
    deadline: ''
  }
  showCreateModal.value = true
}

onMounted(async () => {
  try {
    authLoading.value = true
    
    // Initialize auth and check authentication status
    authStore.initAuth()
    
    // If no user in localStorage, try to check with server
    if (!authStore.user) {
      const isAuth = await authStore.checkAuth()
      if (!isAuth) {
        console.log('User not authenticated, redirecting to login')
        router.push('/login')
        return
      }
    }
    
    authLoading.value = false
    
    console.log('TasksView mounted, user:', authStore.user)
    await loadTasks()
    await loadUsers()
  } catch (error) {
    console.error('Error in TasksView onMounted:', error)
    authLoading.value = false
    router.push('/login')
  } finally {
    isLoading.value = false
  }
})
</script>

<style scoped>
.tasks-page {
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

.tasks-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
}

.loading-state {
  text-align: center;
  padding: 60px 20px;
  color: #666;
}

.loading-state h3 {
  margin: 0 0 12px 0;
  color: #333;
}

.auth-loading {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background-color: #f8f9fa;
}

.auth-loading h3 {
  color: #666;
  font-size: 18px;
}

.filters {
  display: flex;
  gap: 20px;
  margin-bottom: 30px;
  background: white;
  padding: 20px;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.filter-group label {
  font-weight: 500;
  color: #333;
  font-size: 14px;
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.task-list {
  display: grid;
  gap: 20px;
}

.task-card {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.task-header h3 {
  margin: 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.task-badges {
  display: flex;
  gap: 8px;
}

.status-badge, .priority-badge {
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
  font-weight: 500;
  text-transform: uppercase;
}

.status-badge.pending {
  background: #fff3cd;
  color: #856404;
}

.status-badge.in-progress {
  background: #cce5ff;
  color: #004085;
}

.status-badge.completed {
  background: #d4edda;
  color: #155724;
}

.priority-badge.low {
  background: #e2e3e5;
  color: #383d41;
}

.priority-badge.medium {
  background: #fff3cd;
  color: #856404;
}

.priority-badge.high {
  background: #f8d7da;
  color: #721c24;
}

.task-description {
  color: #666;
  font-size: 14px;
  line-height: 1.5;
  margin-bottom: 16px;
}

.task-meta {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
  font-size: 14px;
}

.meta-item strong {
  color: #333;
}

.task-actions {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.status-btn, .edit-btn, .delete-btn {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  font-weight: 500;
  transition: background-color 0.2s;
}

.status-btn {
  background: #007bff;
  color: white;
}

.status-btn:hover:not(:disabled) {
  background: #0056b3;
}

.status-btn:disabled {
  background: #6c757d;
  cursor: not-allowed;
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

.empty-state-btn {
  margin-top: 20px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.empty-state-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
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
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #eee;
}

.modal-header h2 {
  margin: 0;
  color: #333;
  font-size: 20px;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #666;
  transition: color 0.2s;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-close:hover {
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
.form-group textarea,
.form-group select {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-group textarea {
  resize: vertical;
  min-height: 80px;
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
  
  .filters {
    flex-direction: column;
  }
  
  .task-meta {
    grid-template-columns: 1fr;
  }
  
  .task-actions {
    flex-direction: column;
  }
}
</style> 