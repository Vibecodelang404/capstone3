'use client';

import { ProtectedRoute } from '@/components/protected-route';
import { useTransactions } from '@/lib/contexts/transaction-context';
import { useEffect, useState } from 'react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  BarChart,
  Bar,
  LineChart,
  Line,
  XAxis,
  YAxis,
  CartesianGrid,
  Tooltip,
  Legend,
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell,
} from 'recharts';
import { Calendar, Download, RefreshCw } from 'lucide-react';

export default function ReportsPage() {
  const { transactions, fetchTransactions, isLoading } = useTransactions();
  const [dateRange, setDateRange] = useState('7days');
  const [filterType, setFilterType] = useState('all');

  useEffect(() => {
    fetchTransactions();
  }, [fetchTransactions]);

  const salesData = transactions.filter((t) => t.transaction_type === 'SALE');
  const totalSales = salesData.reduce((sum, t) => sum + t.total_amount, 0);
  const totalTransactions = transactions.length;
  const avgTransaction = totalSales / (salesData.length || 1);

  // Payment method breakdown
  const paymentBreakdown = transactions.reduce(
    (acc, t) => {
      const existing = acc.find((item) => item.name === t.payment_method);
      if (existing) {
        existing.value += 1;
      } else {
        acc.push({ name: t.payment_method, value: 1 });
      }
      return acc;
    },
    [] as { name: string; value: number }[]
  );

  const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

  // Daily sales chart data
  const dailyData = transactions.reduce(
    (acc, t) => {
      const date = new Date(t.transaction_date).toLocaleDateString();
      const existing = acc.find((item) => item.date === date);
      if (existing) {
        existing.sales += t.total_amount;
        existing.count += 1;
      } else {
        acc.push({ date, sales: t.total_amount, count: 1 });
      }
      return acc;
    },
    [] as { date: string; sales: number; count: number }[]
  );

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <header className="border-b bg-card">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold">Reports & Analytics</h1>
            <Button
              onClick={() => fetchTransactions()}
              variant="outline"
              disabled={isLoading}
            >
              <RefreshCw className="w-4 h-4 mr-2" />
              Refresh
            </Button>
          </div>
        </header>

        <main className="container mx-auto px-4 py-8">
          {/* Filters */}
          <div className="flex gap-4 mb-6 flex-wrap">
            <div>
              <label className="block text-sm font-medium mb-1">Date Range</label>
              <select
                value={dateRange}
                onChange={(e) => setDateRange(e.target.value)}
                className="px-3 py-2 border rounded-lg bg-background"
              >
                <option value="7days">Last 7 Days</option>
                <option value="30days">Last 30 Days</option>
                <option value="90days">Last 90 Days</option>
                <option value="all">All Time</option>
              </select>
            </div>
            <div>
              <label className="block text-sm font-medium mb-1">Type</label>
              <select
                value={filterType}
                onChange={(e) => setFilterType(e.target.value)}
                className="px-3 py-2 border rounded-lg bg-background"
              >
                <option value="all">All Types</option>
                <option value="sale">Sales</option>
                <option value="refund">Refunds</option>
              </select>
            </div>
            <div className="flex items-end">
              <Button variant="outline">
                <Download className="w-4 h-4 mr-2" />
                Export
              </Button>
            </div>
          </div>

          {/* Key Metrics */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Total Sales</div>
              <div className="text-3xl font-bold mt-2">
                ${totalSales.toFixed(2)}
              </div>
              <p className="text-xs text-muted-foreground mt-2">
                {salesData.length} sales transactions
              </p>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Avg Transaction</div>
              <div className="text-3xl font-bold mt-2">
                ${avgTransaction.toFixed(2)}
              </div>
              <p className="text-xs text-muted-foreground mt-2">
                Per sale
              </p>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Total Transactions</div>
              <div className="text-3xl font-bold mt-2">{totalTransactions}</div>
              <p className="text-xs text-muted-foreground mt-2">
                All types
              </p>
            </Card>
            <Card className="p-6">
              <div className="text-sm text-muted-foreground">Top Payment</div>
              <div className="text-3xl font-bold mt-2">
                {paymentBreakdown[0]?.name || 'N/A'}
              </div>
              <p className="text-xs text-muted-foreground mt-2">
                Most used method
              </p>
            </Card>
          </div>

          {/* Charts */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {/* Daily Sales Chart */}
            <Card className="p-6">
              <h2 className="text-lg font-semibold mb-4">Daily Sales</h2>
              <div className="w-full h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <LineChart data={dailyData}>
                    <CartesianGrid strokeDasharray="3 3" />
                    <XAxis dataKey="date" />
                    <YAxis />
                    <Tooltip />
                    <Legend />
                    <Line
                      type="monotone"
                      dataKey="sales"
                      stroke="#3b82f6"
                      strokeWidth={2}
                    />
                  </LineChart>
                </ResponsiveContainer>
              </div>
            </Card>

            {/* Payment Method Breakdown */}
            <Card className="p-6">
              <h2 className="text-lg font-semibold mb-4">Payment Methods</h2>
              <div className="w-full h-80">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={paymentBreakdown}
                      cx="50%"
                      cy="50%"
                      labelLine={false}
                      label={({ name, value }) =>
                        `${name}: ${value}`
                      }
                      outerRadius={100}
                      fill="#8884d8"
                      dataKey="value"
                    >
                      {paymentBreakdown.map((entry, index) => (
                        <Cell
                          key={`cell-${index}`}
                          fill={COLORS[index % COLORS.length]}
                        />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </Card>
          </div>

          {/* Transactions Table */}
          <Card className="p-6 mt-8">
            <h2 className="text-lg font-semibold mb-4">Recent Transactions</h2>
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead className="border-b bg-muted/50">
                  <tr>
                    <th className="px-4 py-3 text-left font-semibold">Date</th>
                    <th className="px-4 py-3 text-left font-semibold">Type</th>
                    <th className="px-4 py-3 text-left font-semibold">Amount</th>
                    <th className="px-4 py-3 text-left font-semibold">
                      Payment
                    </th>
                    <th className="px-4 py-3 text-left font-semibold">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {transactions.slice(0, 10).map((transaction) => (
                    <tr key={transaction.id} className="border-b hover:bg-muted/50">
                      <td className="px-4 py-3">
                        {new Date(transaction.transaction_date).toLocaleDateString()}
                      </td>
                      <td className="px-4 py-3">
                        <span
                          className={`px-2 py-1 rounded text-xs font-semibold ${
                            transaction.transaction_type === 'SALE'
                              ? 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200'
                              : 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200'
                          }`}
                        >
                          {transaction.transaction_type}
                        </span>
                      </td>
                      <td className="px-4 py-3 font-semibold">
                        ${transaction.total_amount.toFixed(2)}
                      </td>
                      <td className="px-4 py-3">{transaction.payment_method}</td>
                      <td className="px-4 py-3">
                        <span className="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-2 py-1 rounded text-xs font-semibold">
                          {transaction.status}
                        </span>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </Card>
        </main>
      </div>
    </ProtectedRoute>
  );
}
