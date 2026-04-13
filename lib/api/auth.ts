/**
 * Authentication API
 */

import { api, setTokens, clearTokens, type ApiResponse } from './client';

export interface User {
  id: string;
  email: string;
  first_name: string;
  last_name: string;
  role: 'admin' | 'manager' | 'stockman' | 'cashier' | 'customer';
  phone?: string;
  is_active: boolean;
  last_login_at?: string;
  created_at: string;
}

export interface LoginResponse {
  access_token: string;
  refresh_token: string;
  user: User;
  expires_in: number;
}

export interface RegisterData {
  email: string;
  password: string;
  first_name: string;
  last_name: string;
  phone?: string;
}

export const authApi = {
  /**
   * Login user
   */
  login: async (email: string, password: string): Promise<ApiResponse<LoginResponse>> => {
    const response = await api.post<LoginResponse>('/auth/login', { email, password });
    
    if (response.success && response.data) {
      setTokens(response.data.access_token, response.data.refresh_token);
    }
    
    return response;
  },

  /**
   * Register new customer
   */
  register: async (data: RegisterData): Promise<ApiResponse<LoginResponse>> => {
    const response = await api.post<LoginResponse>('/auth/register', data);
    
    if (response.success && response.data) {
      setTokens(response.data.access_token, response.data.refresh_token);
    }
    
    return response;
  },

  /**
   * Refresh access token
   */
  refresh: async (refreshToken: string): Promise<ApiResponse<{ access_token: string; expires_in: number }>> => {
    return api.post('/auth/refresh', { refresh_token: refreshToken });
  },

  /**
   * Logout user
   */
  logout: async (refreshToken?: string): Promise<void> => {
    try {
      await api.post('/auth/logout', { refresh_token: refreshToken });
    } finally {
      clearTokens();
    }
  },

  /**
   * Get current user
   */
  me: async (): Promise<ApiResponse<User>> => {
    return api.get<User>('/auth/me');
  },
};
