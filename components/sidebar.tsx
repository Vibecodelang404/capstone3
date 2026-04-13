'use client';

import Link from 'next/link';
import { usePathname } from 'next/navigation';
import { useAuth } from '@/lib/contexts/auth-context';
import { Button } from '@/components/ui/button';
import {
  LayoutDashboard,
  ShoppingCart,
  Package,
  ClipboardList,
  BarChart3,
  Settings,
  LogOut,
  Store,
  Menu,
  X,
} from 'lucide-react';
import { useState } from 'react';

interface NavItem {
  icon: React.ReactNode;
  label: string;
  href: string;
  color: string;
}

const navItems: NavItem[] = [
  {
    icon: <LayoutDashboard className="w-5 h-5" />,
    label: 'Dashboard',
    href: '/dashboard',
    color: 'text-blue-600 dark:text-blue-400',
  },
  {
    icon: <ShoppingCart className="w-5 h-5" />,
    label: 'POS',
    href: '/pos',
    color: 'text-green-600 dark:text-green-400',
  },
  {
    icon: <Package className="w-5 h-5" />,
    label: 'Products',
    href: '/products',
    color: 'text-purple-600 dark:text-purple-400',
  },
  {
    icon: <ClipboardList className="w-5 h-5" />,
    label: 'Inventory',
    href: '/inventory',
    color: 'text-orange-600 dark:text-orange-400',
  },
  {
    icon: <Store className="w-5 h-5" />,
    label: 'Orders',
    href: '/orders',
    color: 'text-red-600 dark:text-red-400',
  },
  {
    icon: <BarChart3 className="w-5 h-5" />,
    label: 'Reports',
    href: '/reports',
    color: 'text-indigo-600 dark:text-indigo-400',
  },
  {
    icon: <Settings className="w-5 h-5" />,
    label: 'Settings',
    href: '/settings',
    color: 'text-gray-600 dark:text-gray-400',
  },
];

export function Sidebar() {
  const pathname = usePathname();
  const { logout } = useAuth();
  const [isOpen, setIsOpen] = useState(false);

  return (
    <>
      {/* Mobile Menu Button */}
      <div className="lg:hidden fixed top-4 left-4 z-50">
        <Button
          variant="outline"
          size="sm"
          onClick={() => setIsOpen(!isOpen)}
          className="rounded-lg"
        >
          {isOpen ? (
            <X className="w-5 h-5" />
          ) : (
            <Menu className="w-5 h-5" />
          )}
        </Button>
      </div>

      {/* Overlay for mobile */}
      {isOpen && (
        <div
          className="fixed inset-0 bg-black/50 lg:hidden z-40"
          onClick={() => setIsOpen(false)}
        />
      )}

      {/* Sidebar */}
      <aside
        className={`fixed left-0 top-0 h-screen w-64 bg-card border-r transform transition-transform lg:transform-none z-40 ${
          isOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        }`}
      >
        <div className="p-6 border-b">
          <Link href="/dashboard" className="flex items-center gap-2">
            <div className="w-10 h-10 bg-gradient-to-br from-primary to-primary/80 rounded-lg flex items-center justify-center">
              <Store className="w-6 h-6 text-primary-foreground" />
            </div>
            <div>
              <h1 className="font-bold text-lg">POS System</h1>
              <p className="text-xs text-muted-foreground">Business Manager</p>
            </div>
          </Link>
        </div>

        {/* Navigation Items */}
        <nav className="px-4 py-6 space-y-2 flex-1">
          {navItems.map((item) => {
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                onClick={() => setIsOpen(false)}
                className={`flex items-center gap-3 px-4 py-2.5 rounded-lg transition-all ${
                  isActive
                    ? 'bg-primary/10 text-primary font-medium'
                    : 'text-muted-foreground hover:bg-muted'
                }`}
              >
                <span className={isActive ? item.color : ''}>
                  {item.icon}
                </span>
                <span>{item.label}</span>
                {isActive && (
                  <div className="ml-auto w-1 h-6 bg-primary rounded-full" />
                )}
              </Link>
            );
          })}
        </nav>

        {/* Logout Button */}
        <div className="px-4 py-4 border-t">
          <Button
            onClick={() => {
              logout();
              setIsOpen(false);
            }}
            variant="outline"
            className="w-full justify-start gap-2"
          >
            <LogOut className="w-4 h-4" />
            Logout
          </Button>
        </div>
      </aside>

      {/* Main content wrapper - add margin on desktop */}
      <style jsx>{`
        @media (min-width: 1024px) {
          main {
            margin-left: 16rem;
          }
        }
      `}</style>
    </>
  );
}
