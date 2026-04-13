'use client';

import { Sidebar } from '@/components/sidebar';
import { ProtectedRoute } from '@/components/protected-route';

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <Sidebar />
        <div className="lg:ml-64 min-h-screen">
          {children}
        </div>
      </div>
    </ProtectedRoute>
  );
}
