import { createRouter, createWebHistory } from 'vue-router'

const routes = [
    {
        path: '/',
        name: 'Login',
        component: () => import('../views/LoginView.vue'),
        meta: { requiresAuth: false }
    },
    {
        path: '/dashboard',
        name: 'Dashboard',
        component: () => import('../views/DashboardLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'Overview',
                component: () => import('../views/OverviewView.vue')
            },
            {
                path: 'user-management',
                name: 'UserManagement',
                component: () => import('../views/UserManagementView.vue')
            },
            {
                path: 'food-database',
                name: 'FoodDatabase',
                component: () => import('../views/FoodDatabaseView.vue')
            },
            {
                path: 'report-generator',
                name: 'ReportGenerator',
                component: () => import('../views/ReportGeneratorView.vue')
            },
            {
                path: 'system-logs',
                name: 'SystemLogs',
                component: () => import('../views/SystemLogsView.vue')
            },
            {
                path: 'user-analytics/:uid',
                name: 'UserAnalytics',
                component: () => import('../views/UserAnalyticsView.vue'),
                props: true
            },
            {
                path: 'admins',
                name: 'AdminManagement',
                component: () => import('../views/AdminManagementView.vue'),
                meta: { requiresSuperAdmin: true }
            }
        ]
    }
]

const router = createRouter({
    history: createWebHistory(),
    routes
})

// Navigation guard for auth simulation
router.beforeEach((to, from, next) => {
    const isLoggedIn = sessionStorage.getItem('ck_logged_in') === 'true'
    const role = sessionStorage.getItem('ck_role')

    if (to.meta.requiresAuth && !isLoggedIn) {
        next({ name: 'Login' })
    } else if (to.meta.requiresSuperAdmin && role !== 'Super Admin') {
        next({ name: 'Overview' }) // Redirect moderators away from restricted pages
    } else if (to.name === 'Login' && isLoggedIn) {
        next({ name: 'Overview' })
    } else {
        next()
    }
})

export default router
