'use client';

import React, { createContext, useContext, useState, useCallback } from 'react';
import { inventoryService } from '@/lib/api-service';

interface InventoryItem {
  id: string;
  product_id: string;
  quantity: number;
  reorder_level: number;
  reorder_quantity: number;
  last_updated: string;
}

interface InventoryContextType {
  inventory: InventoryItem[];
  isLoading: boolean;
  error: string | null;
  fetchInventory: () => Promise<void>;
  getInventoryItem: (productId: string) => Promise<InventoryItem>;
  updateStock: (productId: string, quantity: number) => Promise<void>;
  getAlerts: () => Promise<any[]>;
}

const InventoryContext = createContext<InventoryContextType | undefined>(undefined);

export function InventoryProvider({ children }: { children: React.ReactNode }) {
  const [inventory, setInventory] = useState<InventoryItem[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchInventory = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await inventoryService.list();
      setInventory(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const getInventoryItem = useCallback(async (productId: string) => {
    const data = await inventoryService.get(productId);
    return data;
  }, []);

  const updateStock = useCallback(async (productId: string, quantity: number) => {
    await inventoryService.updateStock(productId, quantity);
    await fetchInventory();
  }, [fetchInventory]);

  const getAlerts = useCallback(async () => {
    const data = await inventoryService.getAlerts();
    return data;
  }, []);

  return (
    <InventoryContext.Provider
      value={{
        inventory,
        isLoading,
        error,
        fetchInventory,
        getInventoryItem,
        updateStock,
        getAlerts,
      }}
    >
      {children}
    </InventoryContext.Provider>
  );
}

export function useInventory() {
  const context = useContext(InventoryContext);
  if (context === undefined) {
    throw new Error('useInventory must be used within an InventoryProvider');
  }
  return context;
}
