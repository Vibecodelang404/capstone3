'use client';

import { ProtectedRoute } from '@/components/protected-route';
import { useAuth } from '@/lib/contexts/auth-context';
import { useProducts } from '@/lib/contexts/product-context';
import { useTransactions } from '@/lib/contexts/transaction-context';
import { useInventory } from '@/lib/contexts/inventory-context';
import { useEffect } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from 'lucide-react';
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';

export default function DashboardPage() {
  const { user, logout } = useAuth();
  const { products, fetchProducts } = useProducts();
  const { transactions, fetchTransactions } = useTransactions();
  const { inventory, fetchInventory } = useInventory();

  useEffect(() => {
    fetchProducts();
    fetchTransactions();
    fetchInventory();
  }, [fetchProducts, fetchTransactions, fetchInventory]);

  const totalRevenue = transactions.reduce((sum, t) => {
    if (t.transaction_type === 'SALE') {
      return sum + t.total_amount;
    }
    return sum;
  }, 0);

  const totalProducts = products.length;
  const lowStockItems = inventory.filter((item) => item.quantity <= item.reorder_level).length;

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <header className="border-b bg-card">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold">POS Management System</h1>
            <div className="flex items-center gap-4">
              <span className="text-sm text-muted-foreground">{user?.name}</span>
              <Button variant="outline" onClick={logout}>
                Logout
              </Button>
            </div>
          </div>
        </header>

        <main className="container mx-auto px-4 py-8">
          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Total Revenue</div>
              <div className="text-3xl font-bold mt-2">${totalRevenue.toFixed(2)}</div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Products</div>
              <div className="text-3xl font-bold mt-2">{totalProducts}</div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Transactions</div>
              <div className="text-3xl font-bold mt-2">{transactions.length}</div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Low Stock Items</div>
              <div className="text-3xl font-bold mt-2 text-orange-600">{lowStockItems}</div>
            </Card>
          </div>

          {/* Navigation Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">POS</h3>
                  <p className="text-sm text-muted-foreground">Process sales transactions</p>
                </div>
              </div>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-green-100 dark:bg-green-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-green-600 dark:text-green-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">Products</h3>
                  <p className="text-sm text-muted-foreground">Manage product catalog</p>
                </div>
              </div>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-orange-100 dark:bg-orange-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-orange-600 dark:text-orange-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">Inventory</h3>
                  <p className="text-sm text-muted-foreground">Track stock levels</p>
                </div>
              </div>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-purple-100 dark:bg-purple-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-purple-600 dark:text-purple-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">Orders</h3>
                  <p className="text-sm text-muted-foreground">Manage supplier orders</p>
                </div>
              </div>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-red-100 dark:bg-red-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-red-600 dark:text-red-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">Reports</h3>
                  <p className="text-sm text-muted-foreground">View analytics and reports</p>
                </div>
              </div>
            </Card>

            <Card className="p-6 hover:shadow-lg transition-shadow cursor-pointer">
              <div className="flex items-start gap-4">
                <div className="bg-indigo-100 dark:bg-indigo-900 p-3 rounded-lg">
                  <Link className="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                </div>
                <div className="flex-1">
                  <h3 className="font-semibold">Settings</h3>
                  <p className="text-sm text-muted-foreground">Configure system</p>
                </div>
              </div>
            </Card>
          </div>

          {/* Recent Transactions Chart */}
          <Card className="p-6 mt-8">
            <h2 className="text-xl font-semibold mb-4">Recent Transactions</h2>
            <div className="w-full h-80">
              <ResponsiveContainer width="100%" height="100%">
                <BarChart data={transactions.slice(0, 7)}>
                  <CartesianGrid strokeDasharray="3 3" />
                  <XAxis dataKey="id" />
                  <YAxis />
                  <Tooltip />
                  <Legend />
                  <Bar dataKey="total_amount" fill="#3b82f6" />
                </BarChart>
              </ResponsiveContainer>
            </div>
          </Card>
        </main>
      </div>
    </ProtectedRoute>
  );
}
