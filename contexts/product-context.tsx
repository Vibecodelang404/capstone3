'use client'

import { createContext, useContext, useState, useCallback, useEffect, type ReactNode } from 'react'
import { productService, categoryService } from '@/lib/api-service'
import type { Product, Category } from '@/lib/types'

interface ProductContextType {
  products: Product[]
  categories: Category[]
  isLoading: boolean
  error: string | null
  fetchProducts: () => Promise<void>
  fetchCategories: () => Promise<void>
  getProductById: (id: string) => Product | undefined
  getCategoryById: (id: string) => Category | undefined
  getCategoryName: (id: string) => string
  getLowStockProducts: () => Product[]
  getOutOfStockProducts: () => Product[]
}

const ProductContext = createContext<ProductContextType | undefined>(undefined)

export function ProductProvider({ children }: { children: ReactNode }) {
  const [products, setProducts] = useState<Product[]>([])
  const [categories, setCategories] = useState<Category[]>([])
  const [isLoading, setIsLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  const fetchProducts = useCallback(async () => {
    try {
      const data = await productService.list()
      const productsData = Array.isArray(data)
        ? data
        : data?.data && Array.isArray(data.data)
        ? data.data
        : []

      if (productsData.length > 0) {
        setProducts(productsData)
      } else if (!Array.isArray(data) && data?.data) {
        console.warn('Unexpected product response shape, loaded data from wrapper:', data)
        setProducts(productsData)
      } else {
        setProducts([])
      }
    } catch (err) {
      console.warn('Could not fetch products:', err)
      setError('Failed to load products')
      setProducts([])
    }
  }, [])

  const fetchCategories = useCallback(async () => {
    try {
      const data = await categoryService.list()
      const categoriesData = Array.isArray(data)
        ? data
        : data?.data && Array.isArray(data.data)
        ? data.data
        : []

      if (categoriesData.length > 0) {
        setCategories(categoriesData)
      } else if (!Array.isArray(data) && data?.data) {
        console.warn('Unexpected category response shape, loaded data from wrapper:', data)
        setCategories(categoriesData)
      } else {
        setCategories([])
      }
    } catch (err) {
      console.warn('Could not fetch categories:', err)
      setError('Failed to load categories')
      setCategories([])
    }
  }, [])

  // Fetch data on mount
  useEffect(() => {
    const loadData = async () => {
      setIsLoading(true)
      await Promise.all([fetchProducts(), fetchCategories()])
      setIsLoading(false)
    }
    loadData()
  }, [fetchProducts, fetchCategories])

  const getProductById = useCallback((id: string) => {
    return products.find(p => p.id === id)
  }, [products])

  const getCategoryById = useCallback((id: string) => {
    return categories.find(c => c.id === id)
  }, [categories])

  const getCategoryName = useCallback((id: string) => {
    const category = categories.find(c => c.id === id)
    return category?.name || 'Unknown'
  }, [categories])

  const getLowStockProducts = useCallback(() => {
    // Products with stock below reorder level
    return products.filter(p => {
      const inventory = (p as any).inventory
      if (!inventory) return false
      const totalStock = (inventory.wholesaleQty || 0) + (inventory.retailQty || 0) + (inventory.shelfQty || 0)
      return totalStock > 0 && totalStock <= (inventory.reorderLevel || 10)
    })
  }, [products])

  const getOutOfStockProducts = useCallback(() => {
    return products.filter(p => {
      const inventory = (p as any).inventory
      if (!inventory) return true
      const totalStock = (inventory.wholesaleQty || 0) + (inventory.retailQty || 0) + (inventory.shelfQty || 0)
      return totalStock === 0
    })
  }, [products])

  return (
    <ProductContext.Provider
      value={{
        products,
        categories,
        isLoading,
        error,
        fetchProducts,
        fetchCategories,
        getProductById,
        getCategoryById,
        getCategoryName,
        getLowStockProducts,
        getOutOfStockProducts,
      }}
    >
      {children}
    </ProductContext.Provider>
  )
}

export function useProductData() {
  const context = useContext(ProductContext)
  if (context === undefined) {
    throw new Error('useProductData must be used within a ProductProvider')
  }
  return context
}

// Alias for backwards compatibility
export const useProducts = useProductData
