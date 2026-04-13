'use client';

import { ProtectedRoute } from '@/components/protected-route';
import { useInventory } from '@/lib/contexts/inventory-context';
import { useProducts } from '@/lib/contexts/product-context';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AlertTriangle, RefreshCw } from 'lucide-react';

export default function InventoryPage() {
  const { inventory, fetchInventory, isLoading, error, getAlerts } = useInventory();
  const { products } = useProducts();
  const [lowStockAlerts, setLowStockAlerts] = useState<any[]>([]);

  useEffect(() => {
    fetchInventory();
  }, [fetchInventory]);

  useEffect(() => {
    const loadAlerts = async () => {
      try {
        const alerts = await getAlerts();
        setLowStockAlerts(alerts);
      } catch (error) {
        console.error('Error loading alerts:', error);
      }
    };
    loadAlerts();
  }, [getAlerts]);

  const getProductName = (productId: string) => {
    return products.find((p) => p.id === productId)?.name || 'Unknown';
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <header className="border-b bg-card">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold">Inventory Management</h1>
            <Button onClick={() => fetchInventory()} variant="outline">
              <RefreshCw className="w-4 h-4 mr-2" />
              Refresh
            </Button>
          </div>
        </header>

        <main className="container mx-auto px-4 py-8">
          {/* Low Stock Alerts */}
          {lowStockAlerts.length > 0 && (
            <Alert variant="destructive" className="mb-6">
              <AlertTriangle className="h-4 w-4" />
              <AlertDescription>
                <strong>{lowStockAlerts.length} items</strong> are below reorder level
              </AlertDescription>
            </Alert>
          )}

          {error && (
            <Card className="p-4 mb-6 border-destructive bg-destructive/10">
              <p className="text-destructive">{error}</p>
            </Card>
          )}

          {/* Inventory Stats */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Total Items</div>
              <div className="text-3xl font-bold mt-2">
                {inventory.reduce((sum, item) => sum + item.quantity, 0)}
              </div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">SKUs</div>
              <div className="text-3xl font-bold mt-2">{inventory.length}</div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Low Stock Items</div>
              <div className="text-3xl font-bold mt-2 text-orange-600">
                {lowStockAlerts.length}
              </div>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Avg Stock Level</div>
              <div className="text-3xl font-bold mt-2">
                {Math.round(
                  inventory.reduce((sum, item) => sum + item.quantity, 0) / inventory.length || 0
                )}
              </div>
            </Card>
          </div>

          {/* Inventory Table */}
          <Card>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead className="border-b bg-muted/50">
                  <tr>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Product
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Current Stock
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Reorder Level
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Reorder Qty
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Status
                    </th>
                    <th className="px-6 py-3 text-left text-sm font-semibold">
                      Last Updated
                    </th>
                  </tr>
                </thead>
                <tbody>
                  {isLoading ? (
                    <tr>
                      <td colSpan={6} className="px-6 py-8 text-center">
                        Loading inventory...
                      </td>
                    </tr>
                  ) : inventory.length === 0 ? (
                    <tr>
                      <td colSpan={6} className="px-6 py-8 text-center text-muted-foreground">
                        No inventory records found
                      </td>
                    </tr>
                  ) : (
                    inventory.map((item) => {
                      const isLowStock = item.quantity <= item.reorder_level;
                      return (
                        <tr
                          key={item.id}
                          className={`border-b hover:bg-muted/50 ${isLowStock ? 'bg-orange-50 dark:bg-orange-950/20' : ''}`}
                        >
                          <td className="px-6 py-4 text-sm font-medium">
                            {getProductName(item.product_id)}
                          </td>
                          <td className="px-6 py-4 text-sm">
                            <span className={isLowStock ? 'text-orange-600 font-semibold' : ''}>
                              {item.quantity} units
                            </span>
                          </td>
                          <td className="px-6 py-4 text-sm text-muted-foreground">
                            {item.reorder_level}
                          </td>
                          <td className="px-6 py-4 text-sm">
                            {item.reorder_quantity}
                          </td>
                          <td className="px-6 py-4 text-sm">
                            {isLowStock ? (
                              <span className="bg-orange-100 dark:bg-orange-900 text-orange-800 dark:text-orange-200 px-2 py-1 rounded text-xs font-semibold">
                                LOW STOCK
                              </span>
                            ) : (
                              <span className="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded text-xs font-semibold">
                                OK
                              </span>
                            )}
                          </td>
                          <td className="px-6 py-4 text-sm text-muted-foreground">
                            {new Date(item.last_updated).toLocaleDateString()}
                          </td>
                        </tr>
                      );
                    })
                  )}
                </tbody>
              </table>
            </div>
          </Card>
        </main>
      </div>
    </ProtectedRoute>
  );
}
