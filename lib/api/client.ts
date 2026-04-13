/**
 * API Client - Base fetch wrapper with JWT handling
 */

// Set NEXT_PUBLIC_API_URL to your PHP API URL (e.g., https://your-server.com/api)
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

// Token management
let accessToken: string | null = null;
let refreshToken: string | null = null;

export const setTokens = (access: string | null, refresh?: string | null) => {
  accessToken = access;
  if (refresh !== undefined) {
    refreshToken = refresh;
  }
  
  if (typeof window !== 'undefined') {
    if (access) {
      localStorage.setItem('access_token', access);
    } else {
      localStorage.removeItem('access_token');
    }
    if (refresh) {
      localStorage.setItem('refresh_token', refresh);
    } else if (refresh === null) {
      localStorage.removeItem('refresh_token');
    }
  }
};

export const getAccessToken = (): string | null => {
  if (!accessToken && typeof window !== 'undefined') {
    accessToken = localStorage.getItem('access_token');
  }
  return accessToken;
};

export const getRefreshToken = (): string | null => {
  if (!refreshToken && typeof window !== 'undefined') {
    refreshToken = localStorage.getItem('refresh_token');
  }
  return refreshToken;
};

export const clearTokens = () => {
  setTokens(null, null);
};

// API Response types
export interface ApiResponse<T = any> {
  success: boolean;
  message?: string;
  data?: T;
  errors?: Record<string, string[]>;
}

export interface PaginatedResponse<T = any> extends ApiResponse<T[]> {
  pagination: {
    total: number;
    per_page: number;
    current_page: number;
    total_pages: number;
    has_more: boolean;
  };
}

// API Error class
export class ApiError extends Error {
  status: number;
  errors?: Record<string, string[]>;

  constructor(message: string, status: number, errors?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

// Token refresh logic
let isRefreshing = false;
let refreshSubscribers: ((token: string) => void)[] = [];

const subscribeTokenRefresh = (callback: (token: string) => void) => {
  refreshSubscribers.push(callback);
};

const onTokenRefreshed = (token: string) => {
  refreshSubscribers.forEach((callback) => callback(token));
  refreshSubscribers = [];
};

const refreshAccessToken = async (): Promise<string | null> => {
  const refresh = getRefreshToken();
  if (!refresh) return null;

  try {
    const response = await fetch(`${API_BASE_URL}/auth/refresh`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ refresh_token: refresh }),
    });

    if (!response.ok) {
      clearTokens();
      return null;
    }

    const data = await response.json();
    if (data.success && data.data?.access_token) {
      setTokens(data.data.access_token);
      return data.data.access_token;
    }

    return null;
  } catch {
    clearTokens();
    return null;
  }
};

// Main API request function
export async function apiRequest<T = any>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const url = `${API_BASE_URL}${endpoint}`;
  const token = getAccessToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...(options.headers || {}),
  };

  if (token) {
    (headers as Record<string, string>).Authorization = `Bearer ${token}`;
  }

  const config: RequestInit = {
    ...options,
    headers,
  };

  let response = await fetch(url, config);

  // Handle 401 - try to refresh token
  if (response.status === 401 && token) {
    if (!isRefreshing) {
      isRefreshing = true;
      const newToken = await refreshAccessToken();
      isRefreshing = false;

      if (newToken) {
        onTokenRefreshed(newToken);
        (headers as Record<string, string>).Authorization = `Bearer ${newToken}`;
        response = await fetch(url, { ...config, headers });
      } else {
        // Redirect to login
        if (typeof window !== 'undefined') {
          window.location.href = '/login';
        }
        throw new ApiError('Session expired', 401);
      }
    } else {
      // Wait for token refresh
      return new Promise((resolve, reject) => {
        subscribeTokenRefresh(async (newToken) => {
          (headers as Record<string, string>).Authorization = `Bearer ${newToken}`;
          try {
            const retryResponse = await fetch(url, { ...config, headers });
            const data = await retryResponse.json();
            resolve(data);
          } catch (error) {
            reject(error);
          }
        });
      });
    }
  }

  const data = await response.json();

  if (!response.ok) {
    throw new ApiError(
      data.message || `API Error: ${response.status}`,
      response.status,
      data.errors
    );
  }

  return data;
}

// HTTP method helpers
export const api = {
  get: <T = any>(endpoint: string, params?: Record<string, any>) => {
    const queryString = params
      ? '?' + new URLSearchParams(
          Object.entries(params)
            .filter(([, v]) => v !== undefined && v !== null)
            .map(([k, v]) => [k, String(v)])
        ).toString()
      : '';
    return apiRequest<T>(`${endpoint}${queryString}`);
  },

  post: <T = any>(endpoint: string, data?: any) =>
    apiRequest<T>(endpoint, {
      method: 'POST',
      body: data ? JSON.stringify(data) : undefined,
    }),

  put: <T = any>(endpoint: string, data?: any) =>
    apiRequest<T>(endpoint, {
      method: 'PUT',
      body: data ? JSON.stringify(data) : undefined,
    }),

  delete: <T = any>(endpoint: string) =>
    apiRequest<T>(endpoint, { method: 'DELETE' }),
};
