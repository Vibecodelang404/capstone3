/**
 * Inventory API
 */

import { api, type ApiResponse } from './client';

export interface InventoryLevel {
  id: string;
  product_id: string;
  product_name?: string;
  product_sku?: string;
  wholesale_qty: number;
  retail_qty: number;
  shelf_qty: number;
  total_qty: number;
  reorder_point: number;
  max_stock: number;
  is_low_stock: boolean;
  updated_at: string;
}

export interface LowStockItem extends InventoryLevel {
  category_name?: string;
  deficit: number;
}

export interface TransferData {
  product_id: string;
  from_tier: 'wholesale' | 'retail' | 'shelf';
  to_tier: 'wholesale' | 'retail' | 'shelf';
  quantity: number;
  notes?: string;
}

export interface AdjustData {
  product_id: string;
  tier: 'wholesale' | 'retail' | 'shelf';
  quantity: number; // positive to add, negative to subtract
  reason: string;
}

export interface StockMovement {
  id: string;
  product_id: string;
  product_name: string;
  movement_type: 'transfer' | 'adjustment' | 'sale' | 'receive' | 'return';
  from_tier?: string;
  to_tier?: string;
  quantity: number;
  reason?: string;
  user_id: string;
  user_name: string;
  created_at: string;
}

export const inventoryApi = {
  /**
   * Get all inventory levels
   */
  list: (includeProducts = true): Promise<ApiResponse<InventoryLevel[]>> => {
    return api.get<InventoryLevel[]>('/inventory', { include_products: includeProducts });
  },

  /**
   * Get inventory for specific product
   */
  get: (productId: string): Promise<ApiResponse<InventoryLevel>> => {
    return api.get<InventoryLevel>(`/inventory/${productId}`);
  },

  /**
   * Get low stock items
   */
  getLowStock: (threshold?: number): Promise<ApiResponse<LowStockItem[]>> => {
    return api.get<LowStockItem[]>('/inventory/low-stock', { threshold });
  },

  /**
   * Get inventory alerts
   */
  getAlerts: (): Promise<ApiResponse<LowStockItem[]>> => {
    return api.get<LowStockItem[]>('/inventory/alerts');
  },

  /**
   * Update inventory levels
   */
  update: (productId: string, data: Partial<{
    wholesale_qty: number;
    retail_qty: number;
    shelf_qty: number;
    reorder_point: number;
    max_stock: number;
  }>): Promise<ApiResponse<InventoryLevel>> => {
    return api.put<InventoryLevel>(`/inventory/${productId}`, data);
  },

  /**
   * Transfer stock between tiers
   */
  transfer: (data: TransferData): Promise<ApiResponse<InventoryLevel>> => {
    return api.post<InventoryLevel>('/inventory/transfer', data);
  },

  /**
   * Adjust stock (add/subtract)
   */
  adjust: (data: AdjustData): Promise<ApiResponse<InventoryLevel>> => {
    return api.post<InventoryLevel>('/inventory/adjust', data);
  },

  /**
   * Get stock movements history
   */
  getMovements: (params?: {
    product_id?: string;
    type?: string;
    start_date?: string;
    end_date?: string;
    page?: number;
    per_page?: number;
  }): Promise<ApiResponse<StockMovement[]>> => {
    return api.get<StockMovement[]>('/inventory/movements', params);
  },
};
