'use client';

import React, { createContext, useContext, useState, useCallback } from 'react';
import { productService } from '@/lib/api-service';

interface Product {
  id: string;
  name: string;
  sku: string;
  category_id: string;
  price: number;
  cost: number;
  description?: string;
  image_url?: string;
  is_active: boolean;
}

interface ProductContextType {
  products: Product[];
  isLoading: boolean;
  error: string | null;
  fetchProducts: (params?: any) => Promise<void>;
  getProduct: (id: string) => Promise<Product>;
  createProduct: (data: any) => Promise<void>;
  updateProduct: (id: string, data: any) => Promise<void>;
  deleteProduct: (id: string) => Promise<void>;
}

const ProductContext = createContext<ProductContextType | undefined>(undefined);

export function ProductProvider({ children }: { children: React.ReactNode }) {
  const [products, setProducts] = useState<Product[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchProducts = useCallback(async (params?: any) => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await productService.list(params);
      setProducts(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const getProduct = useCallback(async (id: string) => {
    const data = await productService.get(id);
    return data;
  }, []);

  const createProduct = useCallback(async (data: any) => {
    await productService.create(data);
    await fetchProducts();
  }, [fetchProducts]);

  const updateProduct = useCallback(async (id: string, data: any) => {
    await productService.update(id, data);
    await fetchProducts();
  }, [fetchProducts]);

  const deleteProduct = useCallback(async (id: string) => {
    await productService.delete(id);
    await fetchProducts();
  }, [fetchProducts]);

  return (
    <ProductContext.Provider
      value={{
        products,
        isLoading,
        error,
        fetchProducts,
        getProduct,
        createProduct,
        updateProduct,
        deleteProduct,
      }}
    >
      {children}
    </ProductContext.Provider>
  );
}

export function useProducts() {
  const context = useContext(ProductContext);
  if (context === undefined) {
    throw new Error('useProducts must be used within a ProductProvider');
  }
  return context;
}
