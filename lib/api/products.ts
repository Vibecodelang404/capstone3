/**
 * Products API
 */

import { api, type ApiResponse, type PaginatedResponse } from './client';

export interface Product {
  id: string;
  sku: string;
  barcode?: string;
  name: string;
  description?: string;
  category_id: string;
  category_name?: string;
  supplier_id?: string;
  supplier_name?: string;
  cost_price: number;
  wholesale_price: number;
  retail_price: number;
  image_url?: string;
  is_active: boolean;
  created_at: string;
  updated_at: string;
}

export interface ProductWithInventory extends Product {
  inventory?: {
    wholesale_qty: number;
    retail_qty: number;
    shelf_qty: number;
    total_qty: number;
    reorder_point: number;
  };
  variants?: ProductVariant[];
}

export interface ProductVariant {
  id: string;
  product_id: string;
  sku: string;
  name: string;
  additional_price: number;
  is_active: boolean;
}

export interface CreateProductData {
  sku: string;
  barcode?: string;
  name: string;
  description?: string;
  category_id: string;
  supplier_id?: string;
  cost_price: number;
  wholesale_price: number;
  retail_price: number;
  image_url?: string;
}

export interface ProductListParams {
  category?: string;
  search?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'ASC' | 'DESC';
}

export const productsApi = {
  /**
   * List all products
   */
  list: (params?: ProductListParams): Promise<ApiResponse<Product[]>> => {
    return api.get<Product[]>('/products', params);
  },

  /**
   * Get product by ID with details
   */
  get: (id: string): Promise<ApiResponse<ProductWithInventory>> => {
    return api.get<ProductWithInventory>(`/products/${id}`);
  },

  /**
   * Search products
   */
  search: (query: string, limit?: number): Promise<ApiResponse<Product[]>> => {
    return api.get<Product[]>('/products/search', { q: query, limit });
  },

  /**
   * Create new product
   */
  create: (data: CreateProductData): Promise<ApiResponse<Product>> => {
    return api.post<Product>('/products', data);
  },

  /**
   * Update product
   */
  update: (id: string, data: Partial<CreateProductData>): Promise<ApiResponse<Product>> => {
    return api.put<Product>(`/products/${id}`, data);
  },

  /**
   * Delete product
   */
  delete: (id: string): Promise<ApiResponse<void>> => {
    return api.delete(`/products/${id}`);
  },
};
