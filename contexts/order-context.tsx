'use client'

import { createContext, useContext, useState, useCallback, useRef, useEffect, type ReactNode } from 'react'
import { useAuth } from '@/contexts/auth-context'
import { orderService } from '@/lib/api-service'
import { ordersApi } from '@/lib/api/orders'
import type { Order, OrderStatus } from '@/lib/types'
import { isValidPhoneNumber } from '@/lib/utils/validation'

interface OrderContextType {
  orders: Order[]
  addOrder: (order: Omit<Order, 'id' | 'orderNo' | 'createdAt'>) => Order | null
  updateOrderStatus: (orderId: string, status: OrderStatus) => void
  cancelOrder: (orderId: string, guestData?: { order_number: string; customer_phone: string }) => Promise<{ success: boolean; error?: string }>
  getOrdersByStatus: (status: OrderStatus) => Order[]
  getPendingOrdersCount: () => number
  getOrdersForUser: (userId: string) => Order[]
  lookupOrder: (orderNo: string, phone: string) => Promise<Order | null>
  validateOrder: (order: Omit<Order, 'id' | 'orderNo' | 'createdAt'>) => { valid: boolean; error?: string }
}

const OrderContext = createContext<OrderContextType | undefined>(undefined)

const MAX_ORDERS_PER_MINUTE = 5

export function OrderProvider({ children }: { children: ReactNode }) {
  const { user } = useAuth()
  const [orders, setOrders] = useState<Order[]>([])
  const [isLoading, setIsLoading] = useState(true)
  
  // Fetch orders from API on mount - only if user is authenticated
  useEffect(() => {
    const fetchOrders = async () => {
      try {
        // Only fetch orders for authenticated users
        if (!user) {
          setIsLoading(false)
          return
        }
        
        const data = await orderService.list()
        const ordersData = Array.isArray(data)
          ? data
          : data?.data && Array.isArray(data.data)
          ? data.data
          : []
        if (ordersData.length > 0 || Array.isArray(data)) {
          setOrders(ordersData)
        }
      } catch (error) {
        console.error('Failed to fetch orders:', error)
      } finally {
        setIsLoading(false)
      }
    }
    fetchOrders()
  }, [user])
  
  // Track recent orders using useRef to persist across renders but not HMR
  const recentOrderTimestamps = useRef<number[]>([])

  // Validate order data to prevent fake orders
  const validateOrder = useCallback((orderData: Omit<Order, 'id' | 'orderNo' | 'createdAt'>): { valid: boolean; error?: string } => {
    // Check for required fields
    if (!orderData.customerName || orderData.customerName.trim().length < 2) {
      return { valid: false, error: 'Customer name is required and must be at least 2 characters' }
    }

    // Validate phone number format using shared utility
    if (!isValidPhoneNumber(orderData.customerPhone)) {
      return { valid: false, error: 'Please enter a valid Philippine phone number' }
    }

    // Check for empty cart
    if (!orderData.items || orderData.items.length === 0) {
      return { valid: false, error: 'Cannot place an order with no items' }
    }

    // Validate each item
    for (const item of orderData.items) {
      if (!item.productId || !item.productName) {
        return { valid: false, error: 'Invalid product in order' }
      }
      if (item.quantity < 1 || !Number.isInteger(item.quantity)) {
        return { valid: false, error: 'Invalid quantity for ' + item.productName }
      }
      if (item.unitPrice <= 0) {
        return { valid: false, error: 'Invalid price for ' + item.productName }
      }
    }

    // Validate total matches computed total
    const computedTotal = orderData.items.reduce((sum, item) => sum + (item.unitPrice * item.quantity), 0)
    if (Math.abs(computedTotal - orderData.total) > 0.01) {
      return { valid: false, error: 'Order total mismatch - please refresh and try again' }
    }

    // Rate limiting - prevent order spam
    // NOTE: This is client-side rate limiting only. In production, this should be implemented
    // server-side using Redis or a database to prevent bypass and ensure data integrity.
    const now = Date.now()
    const oneMinuteAgo = now - 60000
    
    // Remove old timestamps
    while (recentOrderTimestamps.current.length > 0 && recentOrderTimestamps.current[0] < oneMinuteAgo) {
      recentOrderTimestamps.current.shift()
    }
    
    if (recentOrderTimestamps.current.length >= MAX_ORDERS_PER_MINUTE) {
      return { valid: false, error: 'Too many orders. Please wait a moment and try again.' }
    }

    return { valid: true }
  }, [])

  const addOrder = useCallback((orderData: Omit<Order, 'id' | 'orderNo' | 'createdAt'>) => {
    // Validate order before creating
    const validation = validateOrder(orderData)
    if (!validation.valid) {
      // Validation error is returned via the validation result - caller should handle it
      return null
    }

    // Track order timestamp for rate limiting
    recentOrderTimestamps.current.push(Date.now())

    const newOrder: Order = {
      ...orderData,
      id: `ord_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`,
      orderNo: `ORD-${String(Date.now()).slice(-6)}`,
      createdAt: new Date(),
    }
    
    setOrders(prev => [newOrder, ...prev])
    
    return newOrder
  }, [validateOrder])

  const updateOrderStatus = useCallback((orderId: string, status: OrderStatus) => {
    setOrders(prev => 
      prev.map(order => 
        order.id === orderId 
          ? { ...order, status }
          : order
      )
    )
  }, [])

  // Cancel an order — only allowed if status is 'pending'
  const cancelOrder = useCallback(async (orderId: string, guestData?: { order_number: string; customer_phone: string }): Promise<{ success: boolean; error?: string }> => {
    try {
      // Call the API to cancel the order
      await ordersApi.cancel(orderId, guestData)

      // Update local state if the order exists (for authenticated users)
      setOrders(prev =>
        prev.map(o => o.id === orderId ? { ...o, status: 'cancelled' as OrderStatus } : o)
      )

      return { success: true }
    } catch (error: any) {
      console.error('Cancel order error:', error)
      return { 
        success: false, 
        error: error?.message || 'Failed to cancel order. Please try again.' 
      }
    }
  }, [])

  const getOrdersByStatus = useCallback((status: OrderStatus) => {
    return orders.filter(order => order.status === status)
  }, [orders])

  const getPendingOrdersCount = useCallback(() => {
    return orders.filter(order => 
      order.status === 'pending' || order.status === 'preparing' || order.status === 'ready'
    ).length
  }, [orders])

  // Get orders for a specific logged-in user
  const getOrdersForUser = useCallback((userId: string) => {
    return orders.filter(order => order.userId === userId)
  }, [orders])

  // Lookup order by order number and phone for guests using the backend tracking endpoint
  const lookupOrder = useCallback(async (orderNo: string, phone: string): Promise<Order | null> => {
    const response = await ordersApi.track(orderNo, phone)
    const orderData = response.data
    if (!orderData) {
      return null
    }

    return {
      id: orderData.id,
      orderNo: orderData.order_number,
      source: 'website',
      userId: orderData.customer_id ?? undefined,
      customerName: orderData.customer_name ?? '',
      customerPhone: orderData.customer_phone ?? '',
      items: (orderData.items ?? []).map(item => ({
        productId: item.product_id,
        productName: item.product_name,
        quantity: item.quantity,
        unitPrice: item.unit_price,
      })),
      total: orderData.total,
      paymentMethod: orderData.payment_method as any,
      status: orderData.status as OrderStatus,
      notes: orderData.delivery_notes ?? orderData.shipping_address ?? undefined,
      createdAt: new Date(orderData.created_at),
    }
  }, [])

  return (
    <OrderContext.Provider value={{
      orders,
      addOrder,
      updateOrderStatus,
      cancelOrder,
      getOrdersByStatus,
      getPendingOrdersCount,
      getOrdersForUser,
      lookupOrder,
      validateOrder,
    }}>
      {children}
    </OrderContext.Provider>
  )
}

export function useOrders() {
  const context = useContext(OrderContext)
  if (context === undefined) {
    throw new Error('useOrders must be used within an OrderProvider')
  }
  return context
}
