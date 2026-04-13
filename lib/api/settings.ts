/**
 * Settings API
 */

import { api, type ApiResponse } from './client';

export interface StoreSettings {
  // Store Information
  store_name: string;
  store_logo?: string;
  store_address?: string;
  store_phone?: string;
  store_email?: string;
  
  // Business Settings
  currency: string;
  currency_symbol: string;
  tax_rate: number;
  timezone: string;
  
  // Receipt Settings
  receipt_header?: string;
  receipt_footer?: string;
  show_logo_on_receipt: boolean;
  
  // Inventory Settings
  low_stock_threshold: number;
  enable_negative_stock: boolean;
  auto_reorder: boolean;
  
  // Notification Settings
  email_notifications: boolean;
  low_stock_alerts: boolean;
  expiry_alerts: boolean;
  expiry_alert_days: number;
  
  // Order Settings
  enable_online_orders: boolean;
  min_order_amount: number;
  delivery_fee: number;
  free_delivery_threshold: number;
}

export const settingsApi = {
  /**
   * Get all settings
   */
  getAll: (): Promise<ApiResponse<StoreSettings>> => {
    return api.get<StoreSettings>('/settings');
  },

  /**
   * Get specific setting
   */
  get: (key: keyof StoreSettings): Promise<ApiResponse<{ key: string; value: any }>> => {
    return api.get(`/settings/${key}`);
  },

  /**
   * Update multiple settings
   */
  update: (settings: Partial<StoreSettings>): Promise<ApiResponse<StoreSettings>> => {
    return api.put<StoreSettings>('/settings', settings);
  },

  /**
   * Update single setting
   */
  set: (key: keyof StoreSettings, value: any): Promise<ApiResponse<{ key: string; value: any }>> => {
    return api.put(`/settings/${key}`, { value });
  },
};
