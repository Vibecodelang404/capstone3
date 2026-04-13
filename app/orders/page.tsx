'use client';

import { ProtectedRoute } from '@/components/protected-route';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Plus, Edit2, Trash2, Package, RefreshCw } from 'lucide-react';

interface Order {
  id: string;
  order_date: string;
  supplier_id: string;
  status: string;
  total_amount: number;
  expected_delivery_date: string;
  notes: string;
}

export default function OrdersPage() {
  const [orders, setOrders] = useState<Order[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState('');
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [filterStatus, setFilterStatus] = useState('all');
  const [formData, setFormData] = useState({
    supplier_id: '',
    total_amount: '',
    expected_delivery_date: '',
    notes: '',
  });

  const fetchOrders = async () => {
    setIsLoading(true);
    setError('');
    try {
      const response = await fetch('/api/orders');
      const data = await response.json();
      setOrders(data);
    } catch (err: any) {
      setError(err.message);
    } finally {
      setIsLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, []);

  const filteredOrders = orders.filter((order) => {
    if (filterStatus === 'all') return true;
    return order.status === filterStatus;
  });

  const handleAddOrder = async () => {
    if (!formData.supplier_id || !formData.total_amount) {
      alert('Please fill in required fields');
      return;
    }

    try {
      const response = await fetch('/api/orders', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          ...formData,
          status: 'PENDING',
        }),
      });

      if (response.ok) {
        await fetchOrders();
        setIsDialogOpen(false);
        setFormData({
          supplier_id: '',
          total_amount: '',
          expected_delivery_date: '',
          notes: '',
        });
      }
    } catch (error) {
      alert('Error creating order');
    }
  };

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'PENDING':
        return 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200';
      case 'CONFIRMED':
        return 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200';
      case 'SHIPPED':
        return 'bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200';
      case 'DELIVERED':
        return 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200';
      case 'CANCELLED':
        return 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200';
      default:
        return 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200';
    }
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <header className="border-b bg-card">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold flex items-center gap-2">
              <Package className="w-6 h-6" />
              Supplier Orders
            </h1>
            <div className="flex gap-2">
              <Button onClick={fetchOrders} variant="outline" disabled={isLoading}>
                <RefreshCw className="w-4 h-4 mr-2" />
                Refresh
              </Button>
              <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
                <DialogTrigger asChild>
                  <Button>
                    <Plus className="w-4 h-4 mr-2" />
                    New Order
                  </Button>
                </DialogTrigger>
                <DialogContent>
                  <DialogHeader>
                    <DialogTitle>Create New Order</DialogTitle>
                  </DialogHeader>
                  <div className="space-y-4">
                    <div>
                      <label className="block text-sm font-medium mb-1">
                        Supplier ID *
                      </label>
                      <Input
                        placeholder="Supplier ID"
                        value={formData.supplier_id}
                        onChange={(e) =>
                          setFormData({ ...formData, supplier_id: e.target.value })
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium mb-1">
                        Total Amount *
                      </label>
                      <Input
                        type="number"
                        placeholder="0.00"
                        value={formData.total_amount}
                        onChange={(e) =>
                          setFormData({ ...formData, total_amount: e.target.value })
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium mb-1">
                        Expected Delivery Date
                      </label>
                      <Input
                        type="date"
                        value={formData.expected_delivery_date}
                        onChange={(e) =>
                          setFormData({
                            ...formData,
                            expected_delivery_date: e.target.value,
                          })
                        }
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium mb-1">
                        Notes
                      </label>
                      <textarea
                        placeholder="Order notes"
                        value={formData.notes}
                        onChange={(e) =>
                          setFormData({ ...formData, notes: e.target.value })
                        }
                        className="w-full px-3 py-2 border rounded-lg bg-background"
                        rows={3}
                      />
                    </div>
                    <Button onClick={handleAddOrder} className="w-full">
                      Create Order
                    </Button>
                  </div>
                </DialogContent>
              </Dialog>
            </div>
          </div>
        </header>

        <main className="container mx-auto px-4 py-8">
          {/* Filters */}
          <div className="mb-6 flex gap-2 flex-wrap">
            <Button
              variant={filterStatus === 'all' ? 'default' : 'outline'}
              onClick={() => setFilterStatus('all')}
            >
              All
            </Button>
            <Button
              variant={filterStatus === 'PENDING' ? 'default' : 'outline'}
              onClick={() => setFilterStatus('PENDING')}
            >
              Pending
            </Button>
            <Button
              variant={filterStatus === 'CONFIRMED' ? 'default' : 'outline'}
              onClick={() => setFilterStatus('CONFIRMED')}
            >
              Confirmed
            </Button>
            <Button
              variant={filterStatus === 'SHIPPED' ? 'default' : 'outline'}
              onClick={() => setFilterStatus('SHIPPED')}
            >
              Shipped
            </Button>
            <Button
              variant={filterStatus === 'DELIVERED' ? 'default' : 'outline'}
              onClick={() => setFilterStatus('DELIVERED')}
            >
              Delivered
            </Button>
          </div>

          {error && (
            <Card className="p-4 mb-6 border-destructive bg-destructive/10">
              <p className="text-destructive">{error}</p>
            </Card>
          )}

          {/* Orders Grid */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {isLoading ? (
              <div className="col-span-full text-center py-12">
                <p className="text-muted-foreground">Loading orders...</p>
              </div>
            ) : filteredOrders.length === 0 ? (
              <div className="col-span-full text-center py-12">
                <Package className="w-12 h-12 text-muted-foreground mx-auto mb-2 opacity-50" />
                <p className="text-muted-foreground">No orders found</p>
              </div>
            ) : (
              filteredOrders.map((order) => (
                <Card key={order.id} className="p-6">
                  <div className="flex items-start justify-between mb-4">
                    <div>
                      <h3 className="font-semibold">Order #{order.id.slice(0, 8)}</h3>
                      <p className="text-xs text-muted-foreground">
                        {new Date(order.order_date).toLocaleDateString()}
                      </p>
                    </div>
                    <span
                      className={`px-2 py-1 rounded text-xs font-semibold ${getStatusColor(
                        order.status
                      )}`}
                    >
                      {order.status}
                    </span>
                  </div>

                  <div className="space-y-2 mb-4 text-sm">
                    <div>
                      <p className="text-muted-foreground">Supplier</p>
                      <p className="font-medium">{order.supplier_id}</p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Amount</p>
                      <p className="text-lg font-bold">
                        ${order.total_amount.toFixed(2)}
                      </p>
                    </div>
                    <div>
                      <p className="text-muted-foreground">Expected Delivery</p>
                      <p className="font-medium">
                        {new Date(order.expected_delivery_date).toLocaleDateString()}
                      </p>
                    </div>
                  </div>

                  {order.notes && (
                    <div className="mb-4 p-3 bg-muted rounded text-sm">
                      <p className="text-muted-foreground">Notes</p>
                      <p>{order.notes}</p>
                    </div>
                  )}

                  <div className="flex gap-2 pt-4 border-t">
                    <Button size="sm" variant="outline" className="flex-1">
                      <Edit2 className="w-3 h-3 mr-1" />
                      Edit
                    </Button>
                    <Button
                      size="sm"
                      variant="outline"
                      className="text-destructive flex-1"
                    >
                      <Trash2 className="w-3 h-3 mr-1" />
                      Delete
                    </Button>
                  </div>
                </Card>
              ))
            )}
          </div>
        </main>
      </div>
    </ProtectedRoute>
  );
}
