<template>
  <div class="dashboard">
    <header class="dashboard-header">
      <div class="header-content">
        <h1>Dashboard</h1>
        <div class="user-info">
          <span>Welcome, {{ authStore.user?.username }}</span>
          <button @click="handleLogout" class="logout-btn">Logout</button>
        </div>
      </div>
    </header>

    <main class="dashboard-content">
      <!-- Admin Dashboard -->
      <div v-if="authStore.isAdmin" class="admin-dashboard">
        <div class="stats-grid">
          <div class="stat-card">
            <h3>System Overview</h3>
            <div class="stat-number">{{ userStats.total_users || 0 }}</div>
            <div class="stat-detail">
              <span>{{ userStats.admin_count || 0 }} Admins</span>
              <span>{{ userStats.user_count || 0 }} Regular Users</span>
            </div>
          </div>
          
          <div class="stat-card">
            <h3>Total Tasks</h3>
            <div class="stat-number">{{ taskStats.total_tasks || 0 }}</div>
            <div class="stat-detail">
              <span>{{ taskStats.pending_count || 0 }} Pending</span>
              <span>{{ taskStats.in_progress_count || 0 }} In Progress</span>
              <span>{{ taskStats.completed_count || 0 }} Completed</span>
            </div>
          </div>
          
          <div class="stat-card">
            <h3>Task Completion</h3>
            <div class="stat-number">{{ taskStats.completed_count ?? 0 }}</div>
            <div class="stat-detail">
              <span>{{ (taskStats.total_tasks && taskStats.completed_count !== undefined)
                ? Math.round((taskStats.completed_count / taskStats.total_tasks) * 100)
                : 0 }}% Complete</span>
            </div>
          </div>
          
          <div class="stat-card">
            <h3>Overdue Tasks</h3>
            <div class="stat-number overdue">{{ taskStats.overdue_count || 0 }}</div>
            <div class="stat-detail">Require attention</div>
          </div>
        </div>

        <div class="admin-sections">
          <div class="section-row">
            <div class="quick-actions">
              <h3>Quick Actions</h3>
              <div class="action-buttons">
                <router-link to="/users" class="action-btn">
                  <span>👥 Manage Users</span>
                </router-link>
                <router-link to="/tasks" class="action-btn">
                  <span>📋 Manage Tasks</span>
                </router-link>
              </div>
            </div>
            
            <div class="system-alerts">
              <h3>System Overview</h3>
              <div class="alert-list">
                <div v-if="(taskStats.overdue_count ?? 0) > 0" class="alert-item warning">
                  <span>⚠️ {{ taskStats.overdue_count ?? 0 }} overdue tasks need attention</span>
                </div>
                <div v-if="(taskStats.total_tasks ?? 0) > 0" class="alert-item success">
                  <span>✅ {{ taskStats.completed_count ?? 0 }} of {{ taskStats.total_tasks ?? 0 }} tasks completed</span>
                </div>
                <div v-if="(taskStats.pending_count ?? 0) > 0" class="alert-item info">
                  <span>📊 {{ taskStats.pending_count ?? 0 }} tasks pending</span>
                </div>
                <div v-if="(taskStats.in_progress_count ?? 0) > 0" class="alert-item info">
                  <span>🔄 {{ taskStats.in_progress_count ?? 0 }} tasks in progress</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="recent-section">
          <h2>Recent System Activity</h2>
          <div class="task-list">
            <div v-for="task in recentTasks" :key="task.id" class="task-item">
              <div class="task-header">
                <h4>{{ task.title }}</h4>
                <span :class="['status-badge', task.status.toLowerCase()]">
                  {{ task.status }}
                </span>
              </div>
              <p class="task-description">{{ task.description }}</p>
              <div class="task-meta">
                <span>👤 Assigned to: {{ task.assigned_to_username }}</span>
                <span v-if="task.deadline">📅 Due: {{ formatDate(task.deadline) }}</span>
                <span>🎯 Priority: {{ task.priority }}</span>
              </div>
            </div>
            <div v-if="recentTasks.length === 0" class="empty-state">
              <p>No recent tasks to display</p>
            </div>
          </div>
        </div>
      </div>

      <!-- User Dashboard -->
      <div v-else class="user-dashboard">
        <div class="stats-grid">
          <div class="stat-card">
            <h3>My Tasks</h3>
            <div class="stat-number">{{ userTaskStats.total_tasks || 0 }}</div>
            <div class="stat-detail">
              <span>{{ userTaskStats.pending_count || 0 }} Pending</span>
              <span>{{ userTaskStats.completed_count || 0 }} Completed</span>
            </div>
          </div>
          
          <div class="stat-card">
            <h3>In Progress</h3>
            <div class="stat-number in-progress">{{ userTaskStats.in_progress_count || 0 }}</div>
            <div class="stat-detail">Currently working on</div>
          </div>
          
          <div class="stat-card">
            <h3>Overdue</h3>
            <div class="stat-number overdue">{{ userTaskStats.overdue_count || 0 }}</div>
            <div class="stat-detail">Past deadline</div>
          </div>
        </div>

        <div class="quick-actions">
          <router-link to="/tasks" class="action-btn">
            <span>View My Tasks</span>
          </router-link>
        </div>

        <div class="recent-section">
          <h2>My Recent Tasks</h2>
          <div class="task-list">
            <div v-for="task in userTasks" :key="task.id" class="task-item">
              <div class="task-header">
                <h4>{{ task.title }}</h4>
                <span :class="['status-badge', task.status.toLowerCase()]">
                  {{ task.status }}
                </span>
              </div>
              <p class="task-description">{{ task.description }}</p>
              <div class="task-meta">
                <span v-if="task.deadline">Due: {{ formatDate(task.deadline) }}</span>
                <span>Priority: {{ task.priority }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
interface Stats {
  total_users?: number
  admin_count?: number
  user_count?: number
  total_tasks?: number
  pending_count?: number
  in_progress_count?: number
  completed_count?: number
  overdue_count?: number
}

interface Task {
  id: number
  title: string
  description: string
  status: string
  assigned_to_username?: string
  deadline?: string
  priority: string
}
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const userStats = ref<Stats>({})
const taskStats = ref<Stats>({})
const userTaskStats = ref<Stats>({})
const recentTasks = ref<Task[]>([])
const userTasks = ref<Task[]>([])

const API_BASE = import.meta.env.VITE_API_URL

async function loadDashboardData() {
  try {
    console.log('Loading dashboard data...')
    const response = await fetch(`${API_BASE}/dashboard`, {
      credentials: 'include'
    })
    
    console.log('Dashboard response status:', response.status)
    
    if (response.ok) {
      const data = await response.json()
      console.log('Dashboard data received:', data)
      
      if (authStore.isAdmin) {
        userStats.value = data.user_stats || {}
        taskStats.value = data.task_stats || {}
        recentTasks.value = data.upcoming_tasks || []
        console.log('Admin stats set:', { userStats: userStats.value, taskStats: taskStats.value })
      } else {
        userTaskStats.value = data.task_stats || {}
        userTasks.value = data.recent_tasks || []
        console.log('User stats set:', { userTaskStats: userTaskStats.value })
      }
    } else {
      console.error('Dashboard request failed:', response.status, response.statusText)
      const errorText = await response.text()
      console.error('Error response:', errorText)
    }
  } catch (error) {
    console.error('Failed to load dashboard data:', error)
  }
}

function formatDate(dateString: string) {
  return new Date(dateString).toLocaleDateString()
}



async function handleLogout() {
  await authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  // Initialize auth store if not already done
  if (!authStore.user) {
    authStore.initAuth()
  }
  
  if (!authStore.isAuthenticated) {
    router.push('/login')
    return
  }
  
  console.log('DashboardView mounted, user:', authStore.user)
  await loadDashboardData()
})
</script>

<style scoped>
.dashboard {
  min-height: 100vh;
  width: 100%;
  background-color: #f8f9fa;
  overflow-x: hidden;
}

.dashboard-header {
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
  width: 100%;
  box-sizing: border-box;
}

.header-content h1 {
  margin: 0;
  color: #333;
  font-size: 24px;
  font-weight: 600;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 20px;
}

.user-info span {
  color: #666;
  font-size: 14px;
}

.logout-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 14px;
  transition: background-color 0.2s;
}

.logout-btn:hover {
  background: #c82333;
}

.dashboard-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 40px 20px;
  width: 100%;
  box-sizing: border-box;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}

.stat-card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

.stat-card h3 {
  margin: 0 0 12px 0;
  color: #666;
  font-size: 14px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.stat-number {
  font-size: 32px;
  font-weight: 700;
  color: #333;
  margin-bottom: 8px;
}

.stat-number.overdue {
  color: #dc3545;
}

.stat-number.in-progress {
  color: #007bff;
}

.stat-detail {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: #666;
}

.admin-sections {
  margin-bottom: 30px;
}

.section-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.quick-actions, .system-alerts {
  background: white;
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

.quick-actions h3, .system-alerts h3 {
  margin: 0 0 16px 0;
  color: #333;
  font-size: 18px;
  font-weight: 600;
}

.action-buttons {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.quick-actions {
  display: block;
  margin-bottom: 0;
}

.action-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-weight: 500;
  transition: transform 0.2s, box-shadow 0.2s;
  border: none;
  cursor: pointer;
  font-size: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.create-task-btn {
  background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
}

.create-task-btn:hover {
  box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
}

.alert-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.alert-item {
  padding: 8px 12px;
  border-radius: 4px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.alert-item.warning {
  background: #fff3cd;
  color: #856404;
  border: 1px solid #ffeaa7;
}

.alert-item.info {
  background: #d1ecf1;
  color: #0c5460;
  border: 1px solid #bee5eb;
}

.alert-item.success {
  background: #d4edda;
  color: #155724;
  border: 1px solid #c3e6cb;
}

.recent-section {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid #e9ecef;
}

.recent-section h2 {
  margin: 0 0 20px 0;
  color: #333;
  font-size: 20px;
  font-weight: 600;
}

.task-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.task-item {
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 16px;
  transition: border-color 0.2s;
}

.task-item:hover {
  border-color: #667eea;
}

.task-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.task-header h4 {
  margin: 0;
  color: #333;
  font-size: 16px;
  font-weight: 600;
}

.status-badge {
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

.task-description {
  color: #666;
  font-size: 14px;
  margin: 8px 0;
  line-height: 1.5;
}

.task-meta {
  display: flex;
  gap: 16px;
  font-size: 12px;
  color: #999;
}

@media (max-width: 768px) {
  .header-content {
    flex-direction: column;
    gap: 16px;
    text-align: center;
  }
  
  .section-row {
    grid-template-columns: 1fr;
    gap: 16px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    gap: 8px;
  }
  
  .action-btn {
    padding: 10px 16px;
    font-size: 13px;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
  
  .quick-actions {
    flex-direction: column;
  }
  
  .task-meta {
    flex-direction: column;
    gap: 4px;
  }
}
</style> 