/**
 * API Module Exports
 * Central export point for all API services
 */

// Client and utilities
export {
  api,
  apiRequest,
  setTokens,
  getAccessToken,
  getRefreshToken,
  clearTokens,
  ApiError,
  type ApiResponse,
  type PaginatedResponse,
} from './client';

// Auth API
export { authApi, type User, type LoginResponse, type RegisterData } from './auth';

// Products API
export {
  productsApi,
  type Product,
  type ProductWithInventory,
  type ProductVariant,
  type CreateProductData,
  type ProductListParams,
} from './products';

// Inventory API
export {
  inventoryApi,
  type InventoryLevel,
  type LowStockItem,
  type TransferData,
  type AdjustData,
  type StockMovement,
} from './inventory';

// Transactions API
export {
  transactionsApi,
  type Transaction,
  type TransactionItem,
  type CreateTransactionData,
  type SalesReport,
  type DailySummary,
  type TransactionListParams,
} from './transactions';

// Orders API
export {
  ordersApi,
  type Order,
  type OrderItem,
  type CreateOrderData,
  type OrderListParams,
  type OrderStatus,
} from './orders';

// Settings API
export { settingsApi, type StoreSettings } from './settings';
