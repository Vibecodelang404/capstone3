/**
 * Orders API
 */

import { api, type ApiResponse, type PaginatedResponse } from './client';

export interface OrderItem {
  id: string;
  product_id: string;
  product_name: string;
  quantity: number;
  unit_price: number;
  total: number;
}

export interface Order {
  id: string;
  order_number: string;
  customer_id: string;
  customer_name?: string;
  customer_email?: string;
  subtotal: number;
  tax_rate: number;
  tax_amount: number;
  delivery_fee: number;
  total: number;
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'delivered' | 'cancelled';
  payment_method: 'cash' | 'card' | 'gcash' | 'maya';
  payment_status: 'pending' | 'paid' | 'refunded';
  delivery_address?: string;
  delivery_notes?: string;
  scheduled_at?: string;
  delivered_at?: string;
  created_at: string;
  updated_at: string;
  items?: OrderItem[];
}

export interface CreateOrderData {
  items: Array<{
    product_id: string;
    quantity: number;
  }>;
  payment_method?: 'cash' | 'card' | 'gcash' | 'maya';
  delivery_address?: string;
  delivery_notes?: string;
  scheduled_at?: string;
  tax_rate?: number;
  delivery_fee?: number;
}

export interface OrderListParams {
  status?: string;
  customer_id?: string;
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
}

export type OrderStatus = Order['status'];

export const ordersApi = {
  /**
   * List orders with pagination
   */
  list: (params?: OrderListParams): Promise<PaginatedResponse<Order>> => {
    return api.get('/orders', params) as Promise<PaginatedResponse<Order>>;
  },

  /**
   * Get order by ID with items
   */
  get: (id: string): Promise<ApiResponse<Order>> => {
    return api.get<Order>(`/orders/${id}`);
  },

  /**
   * Get orders for specific customer
   */
  getByCustomer: (customerId: string): Promise<ApiResponse<Order[]>> => {
    return api.get<Order[]>(`/orders/customer/${customerId}`);
  },

  /**
   * Create new order
   */
  create: (data: CreateOrderData): Promise<ApiResponse<Order>> => {
    return api.post<Order>('/orders', data);
  },

  /**
   * Update order status
   */
  updateStatus: (id: string, status: OrderStatus): Promise<ApiResponse<{ status: OrderStatus }>> => {
    return api.put(`/orders/${id}/status`, { status });
  },

  /**
   * Cancel order (customer endpoint)
   */
  cancel: (id: string): Promise<ApiResponse<void>> => {
    return api.put(`/orders/${id}/cancel`);
  },

  /**
   * Delete order (admin only)
   */
  delete: (id: string): Promise<ApiResponse<void>> => {
    return api.delete(`/orders/${id}`);
  },
};
