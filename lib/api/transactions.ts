/**
 * Transactions API
 */

import { api, type ApiResponse, type PaginatedResponse } from './client';

export interface TransactionItem {
  id: string;
  product_id: string;
  product_name: string;
  quantity: number;
  unit_price: number;
  cost_price: number;
  total: number;
}

export interface Transaction {
  id: string;
  transaction_number: string;
  cashier_id: string;
  cashier_name?: string;
  customer_id?: string;
  customer_name?: string;
  subtotal: number;
  tax_rate: number;
  tax_amount: number;
  discount_amount: number;
  total: number;
  payment_method: 'cash' | 'card' | 'check' | 'credit';
  amount_tendered: number;
  change_amount: number;
  status: 'completed' | 'voided' | 'refunded' | 'partial_refund';
  created_at: string;
  items?: TransactionItem[];
}

export interface CreateTransactionData {
  items: Array<{
    product_id: string;
    quantity: number;
    price?: number; // Override price (optional)
  }>;
  payment_method: 'cash' | 'card' | 'check' | 'credit';
  customer_id?: string;
  tax_rate?: number;
  discount_amount?: number;
  amount_tendered?: number;
}

export interface SalesReport {
  total_sales: number;
  total_transactions: number;
  average_transaction: number;
  total_items_sold: number;
  total_profit: number;
  by_payment_method: Record<string, { count: number; total: number }>;
  by_day: Array<{ date: string; total: number; count: number }>;
  top_products: Array<{ product_id: string; product_name: string; quantity: number; total: number }>;
}

export interface DailySummary {
  date: string;
  total_sales: number;
  transaction_count: number;
  average_transaction: number;
  cash_sales: number;
  card_sales: number;
  voided_count: number;
  refunded_amount: number;
}

export interface TransactionListParams {
  start_date?: string;
  end_date?: string;
  payment_method?: string;
  status?: string;
  page?: number;
  per_page?: number;
}

export const transactionsApi = {
  /**
   * List transactions with pagination
   */
  list: (params?: TransactionListParams): Promise<PaginatedResponse<Transaction>> => {
    return api.get('/transactions', params) as Promise<PaginatedResponse<Transaction>>;
  },

  /**
   * Get transaction by ID with items
   */
  get: (id: string): Promise<ApiResponse<Transaction>> => {
    return api.get<Transaction>(`/transactions/${id}`);
  },

  /**
   * Create new transaction (POS sale)
   */
  create: (data: CreateTransactionData): Promise<ApiResponse<Transaction>> => {
    return api.post<Transaction>('/transactions', data);
  },

  /**
   * Void transaction
   */
  void: (id: string): Promise<ApiResponse<void>> => {
    return api.put(`/transactions/${id}/void`);
  },

  /**
   * Refund transaction
   */
  refund: (id: string, amount?: number): Promise<ApiResponse<{ refund_amount: number }>> => {
    return api.put(`/transactions/${id}/refund`, { amount });
  },

  /**
   * Get sales report
   */
  getReport: (params: {
    start_date: string;
    end_date: string;
  }): Promise<ApiResponse<SalesReport>> => {
    return api.get<SalesReport>('/transactions/report', params);
  },

  /**
   * Get daily summary
   */
  getDailySummary: (date?: string): Promise<ApiResponse<DailySummary>> => {
    return api.get<DailySummary>('/transactions/daily', { date });
  },
};
