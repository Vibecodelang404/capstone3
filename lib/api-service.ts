// Base API configuration - set NEXT_PUBLIC_API_URL to your PHP API URL
const API_BASE_URL = process.env.NEXT_PUBLIC_API_URL || '/api';

// Token storage
let authToken: string | null = null;

export const setAuthToken = (token: string | null) => {
  authToken = token;
  if (token) {
    localStorage.setItem('auth_token', token);
  } else {
    localStorage.removeItem('auth_token');
  }
};

export const getAuthToken = () => {
  if (!authToken && typeof window !== 'undefined') {
    authToken = localStorage.getItem('auth_token');
  }
  return authToken;
};

// API request helper
async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<T> {
  const url = `${API_BASE_URL}${endpoint}`;
  const token = getAuthToken();

  const headers: HeadersInit = {
    'Content-Type': 'application/json',
    ...options.headers,
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  const response = await fetch(url, {
    ...options,
    headers,
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message || `API Error: ${response.status}`);
  }

  return response.json();
}

// Auth Service
export const authService = {
  login: async (email: string, password: string) => {
    const data = await apiRequest('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });
    setAuthToken(data.token);
    return data;
  },

  register: async (name: string, email: string, password: string) => {
    const data = await apiRequest('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ name, email, password }),
    });
    setAuthToken(data.token);
    return data;
  },

  logout: () => {
    setAuthToken(null);
  },

  me: () => apiRequest('/auth/me'),
};

// Product Service
export const productService = {
  list: (params?: { category?: string; search?: string; page?: number }) =>
    apiRequest(`/products${new URLSearchParams(params as any).toString() ? `?${new URLSearchParams(params as any)}` : ''}`),

  get: (id: string) => apiRequest(`/products/${id}`),

  create: (data: any) =>
    apiRequest('/products', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/products/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: string) =>
    apiRequest(`/products/${id}`, {
      method: 'DELETE',
    }),
};

// Category Service
export const categoryService = {
  list: () => apiRequest('/categories'),

  get: (id: string) => apiRequest(`/categories/${id}`),

  create: (data: any) =>
    apiRequest('/categories', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/categories/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: string) =>
    apiRequest(`/categories/${id}`, {
      method: 'DELETE',
    }),
};

// Transaction Service
export const transactionService = {
  list: (params?: { date?: string; status?: string; page?: number }) =>
    apiRequest(`/transactions${new URLSearchParams(params as any).toString() ? `?${new URLSearchParams(params as any)}` : ''}`),

  get: (id: string) => apiRequest(`/transactions/${id}`),

  create: (data: any) =>
    apiRequest('/transactions', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/transactions/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  getReport: (params: any) =>
    apiRequest(`/transactions/report?${new URLSearchParams(params).toString()}`),
};

// Inventory Service
export const inventoryService = {
  list: () => apiRequest('/inventory'),

  get: (productId: string) => apiRequest(`/inventory/${productId}`),

  updateStock: (productId: string, quantity: number) =>
    apiRequest(`/inventory/${productId}`, {
      method: 'PUT',
      body: JSON.stringify({ quantity }),
    }),

  getAlerts: () => apiRequest('/inventory/alerts'),
};

// Order Service
export const orderService = {
  list: (params?: { status?: string; page?: number }) =>
    apiRequest(`/orders${new URLSearchParams(params as any).toString() ? `?${new URLSearchParams(params as any)}` : ''}`),

  get: (id: string) => apiRequest(`/orders/${id}`),

  create: (data: any) =>
    apiRequest('/orders', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/orders/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  updateStatus: (id: string, status: string) =>
    apiRequest(`/orders/${id}/status`, {
      method: 'PUT',
      body: JSON.stringify({ status }),
    }),
};

// User Service
export const userService = {
  list: (params?: { role?: string; page?: number }) =>
    apiRequest(`/users${new URLSearchParams(params as any).toString() ? `?${new URLSearchParams(params as any)}` : ''}`),

  get: (id: string) => apiRequest(`/users/${id}`),

  create: (data: any) =>
    apiRequest('/users', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/users/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: string) =>
    apiRequest(`/users/${id}`, {
      method: 'DELETE',
    }),
};

// Settings Service
export const settingsService = {
  get: () => apiRequest('/settings'),

  update: (data: any) =>
    apiRequest('/settings', {
      method: 'PUT',
      body: JSON.stringify(data),
    }),
};

// Supplier Service
export const supplierService = {
  list: () => apiRequest('/suppliers'),

  get: (id: string) => apiRequest(`/suppliers/${id}`),

  create: (data: any) =>
    apiRequest('/suppliers', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/suppliers/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  delete: (id: string) =>
    apiRequest(`/suppliers/${id}`, {
      method: 'DELETE',
    }),
};

// Batch Service
export const batchService = {
  list: (params?: { product_id?: string; status?: string }) =>
    apiRequest(`/batches${new URLSearchParams(params as any).toString() ? `?${new URLSearchParams(params as any)}` : ''}`),

  get: (id: string) => apiRequest(`/batches/${id}`),

  create: (data: any) =>
    apiRequest('/batches', {
      method: 'POST',
      body: JSON.stringify(data),
    }),

  update: (id: string, data: any) =>
    apiRequest(`/batches/${id}`, {
      method: 'PUT',
      body: JSON.stringify(data),
    }),

  dispose: (id: string, reason?: string) =>
    apiRequest(`/batches/${id}/dispose`, {
      method: 'POST',
      body: JSON.stringify({ reason }),
    }),
};

// Alert Service
export const alertService = {
  list: () => apiRequest('/alerts'),
  
  getUnreadCount: () => apiRequest('/alerts/unread-count'),
  
  markAsRead: (id: string) =>
    apiRequest(`/alerts/${id}/read`, {
      method: 'POST',
    }),
};
