export interface User {
    id: number
    username: string
    email: string
    role: 'admin' | 'user'
    created_at: string
}

export interface Task {
    id: number
    title: string
    description: string
    assigned_to: number
    assigned_by: number
    assigned_to_username: string
    assigned_by_username: string
    status: 'Pending' | 'In Progress' | 'Completed'
    priority: 'Low' | 'Medium' | 'High'
    deadline?: string
    created_at: string
}

export interface NewTask {
    title: string
    description: string
    assigned_to: string
    priority: 'Low' | 'Medium' | 'High'
    deadline: string
}
