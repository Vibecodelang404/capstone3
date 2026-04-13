'use client';

import React, { createContext, useContext, useState, useCallback } from 'react';
import { transactionService } from '@/lib/api-service';

interface TransactionItem {
  id: string;
  product_id: string;
  product_name: string;
  quantity: number;
  unit_price: number;
  total: number;
}

interface Transaction {
  id: string;
  transaction_number: string;
  cashier_id: string;
  customer_id?: string;
  subtotal: number;
  tax_amount: number;
  discount_amount: number;
  total: number;
  payment_method: 'cash' | 'card' | 'check' | 'credit';
  amount_tendered: number;
  change_amount: number;
  status: 'completed' | 'voided' | 'refunded';
  created_at: string;
  items?: TransactionItem[];
}

interface TransactionContextType {
  transactions: Transaction[];
  isLoading: boolean;
  error: string | null;
  fetchTransactions: (params?: any) => Promise<void>;
  getTransaction: (id: string) => Promise<Transaction>;
  createTransaction: (data: any) => Promise<Transaction>;
  voidTransaction: (id: string) => Promise<void>;
  refundTransaction: (id: string, amount?: number) => Promise<void>;
  getTodayStats: () => Promise<any>;
}

const TransactionContext = createContext<TransactionContextType | undefined>(undefined);

export function TransactionProvider({ children }: { children: React.ReactNode }) {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const fetchTransactions = useCallback(async (params?: any) => {
    setIsLoading(true);
    setError(null);
    try {
      const data = await transactionService.list(params);
      setTransactions(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  }, []);

  const getTransaction = useCallback(async (id: string) => {
    const data = await transactionService.get(id);
    return data;
  }, []);

  const createTransaction = useCallback(async (data: any) => {
    const transaction = await transactionService.create(data);
    await fetchTransactions();
    return transaction;
  }, [fetchTransactions]);

  const voidTransaction = useCallback(async (id: string) => {
    await transactionService.void(id);
    await fetchTransactions();
  }, [fetchTransactions]);

  const refundTransaction = useCallback(async (id: string, amount?: number) => {
    await transactionService.refund(id, amount);
    await fetchTransactions();
  }, [fetchTransactions]);

  const getTodayStats = useCallback(async () => {
    const data = await transactionService.getDailySummary();
    return data;
  }, []);

  return (
    <TransactionContext.Provider
      value={{
        transactions,
        isLoading,
        error,
        fetchTransactions,
        getTransaction,
        createTransaction,
        voidTransaction,
        refundTransaction,
        getTodayStats,
      }}
    >
      {children}
    </TransactionContext.Provider>
  );
}

export function useTransactions() {
  const context = useContext(TransactionContext);
  if (context === undefined) {
    throw new Error('useTransactions must be used within a TransactionProvider');
  }
  return context;
}
