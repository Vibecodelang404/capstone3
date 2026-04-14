'use client'

import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react'
import { useRouter } from 'next/navigation'
import type { User, UserRole } from '@/lib/types'
import { authApi } from '@/lib/api'
import { getDefaultPath } from '@/lib/utils/permissions'

interface RegisterUserData {
  name: string
  email: string
  password: string
  role?: UserRole
}

interface AuthContextType {
  user: User | null
  isLoading: boolean
  isAuthenticated: boolean
  login: (email: string, password: string) => Promise<{ success: boolean; error?: string }>
  register: (data: RegisterUserData) => Promise<{ success: boolean; error?: string }>
  logout: () => void
  checkAuth: () => boolean
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

const AUTH_STORAGE_KEY = 'mystore_auth_user'

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(null)
  const [isLoading, setIsLoading] = useState(true)
  const [hasMounted, setHasMounted] = useState(false)
  const router = useRouter()

  // Check for existing session on mount
  useEffect(() => {
    setHasMounted(true)
    const storedUser = localStorage.getItem(AUTH_STORAGE_KEY)
    if (storedUser) {
      try {
        const parsedUser = JSON.parse(storedUser)
        // Convert date strings back to Date objects
        parsedUser.createdAt = new Date(parsedUser.createdAt)
        if (parsedUser.lastLogin) {
          parsedUser.lastLogin = new Date(parsedUser.lastLogin)
        }
        setUser(parsedUser)
      } catch {
        localStorage.removeItem(AUTH_STORAGE_KEY)
      }
    }
    setIsLoading(false)
  }, [])

  const login = useCallback(async (email: string, password: string) => {
    setIsLoading(true)
    
    try {
      const response = await authApi.login(email, password)
      
      if (response.success && response.data) {
        const userWithLogin = { ...response.data.user, lastLogin: new Date() }
        setUser(userWithLogin)
        localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(userWithLogin))
        
        setIsLoading(false)
        
        // Redirect to role-specific dashboard
        const redirectPath = getDefaultPath(response.data.user.role)
        router.push(redirectPath)
        
        return { success: true }
      }
      
      setIsLoading(false)
      return { success: false, error: 'Login failed' }
    } catch (error) {
      setIsLoading(false)
      return { success: false, error: error instanceof Error ? error.message : 'Invalid email or password' }
    }
  }, [router])

  const register = useCallback(async (data: RegisterUserData) => {
    setIsLoading(true)
    
    try {
      const result = await authApi.register(data)
      
      if (result.success && result.data) {
        const userWithLogin = { ...result.data.user, lastLogin: new Date() }
        setUser(userWithLogin as User)
        localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(userWithLogin))
        setIsLoading(false)
        
        // Redirect to shop for customers
        router.push('/shop')
        
        return { success: true }
      }
      
      setIsLoading(false)
      return { success: false, error: 'Registration failed' }
    } catch (error) {
      setIsLoading(false)
      return { success: false, error: error instanceof Error ? error.message : 'Registration failed' }
    }
  }, [router])

  const logout = useCallback(() => {
    const currentRole = user?.role
    setUser(null)
    localStorage.removeItem(AUTH_STORAGE_KEY)
    authApi.logout() // Call API logout (clears HttpOnly cookies)
    // Redirect staff to staff login, customers to customer login
    if (currentRole && currentRole !== 'customer') {
      router.push('/admin/login')
    } else {
      router.push('/login')
    }
  }, [router, user?.role])

  const checkAuth = useCallback(() => {
    return !!user
  }, [user])

  // Prevent hydration mismatch by not rendering children until mounted
  if (!hasMounted) {
    return null
  }

  return (
    <AuthContext.Provider
      value={{
        user,
        isLoading,
        isAuthenticated: !!user,
        login,
        register,
        logout,
        checkAuth,
      }}
    >
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (context === undefined) {
    throw new Error('useAuth must be used within an AuthProvider')
  }
  return context
}

// Hook to require authentication
export function useRequireAuth(allowedRoles?: UserRole[], isStaffArea?: boolean) {
  const { user, isLoading, isAuthenticated } = useAuth()
  const router = useRouter()

  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      // Redirect to appropriate login page
      router.push(isStaffArea ? '/admin/login' : '/login')
    } else if (!isLoading && user && allowedRoles && !allowedRoles.includes(user.role)) {
      // User is authenticated but doesn't have the required role
      router.push(getDefaultPath(user.role))
    }
  }, [isLoading, isAuthenticated, user, allowedRoles, router, isStaffArea])

  return { user, isLoading, isAuthenticated }
}
