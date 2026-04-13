'use client';

import { ProtectedRoute } from '@/components/protected-route';
import { useAuth } from '@/lib/contexts/auth-context';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Settings, Save, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function SettingsPage() {
  const { user, logout } = useAuth();
  const [isSaving, setIsSaving] = useState(false);
  const [saveSuccess, setSaveSuccess] = useState(false);
  const [settings, setSettings] = useState({
    storeName: 'My Store',
    storePhone: '+1 (555) 000-0000',
    storeEmail: 'store@example.com',
    storeAddress: '123 Main St, City, State 12345',
    currencySymbol: '$',
    taxRate: 8.5,
    lowStockThreshold: 10,
    enableNotifications: true,
    autoBackup: true,
  });

  const handleSaveSettings = async () => {
    setIsSaving(true);
    try {
      // TODO: Call API to save settings
      await new Promise((resolve) => setTimeout(resolve, 1000));
      setSaveSuccess(true);
      setTimeout(() => setSaveSuccess(false), 3000);
    } catch (error) {
      console.error('Error saving settings:', error);
    } finally {
      setIsSaving(false);
    }
  };

  const handleChange = (key: string, value: any) => {
    setSettings((prev) => ({
      ...prev,
      [key]: value,
    }));
  };

  return (
    <ProtectedRoute>
      <div className="min-h-screen bg-background">
        <header className="border-b bg-card">
          <div className="container mx-auto px-4 py-4 flex items-center justify-between">
            <h1 className="text-2xl font-bold flex items-center gap-2">
              <Settings className="w-6 h-6" />
              Settings
            </h1>
          </div>
        </header>

        <main className="container mx-auto px-4 py-8 max-w-2xl">
          {saveSuccess && (
            <Alert className="mb-6 bg-green-50 dark:bg-green-950/50 border-green-200 dark:border-green-900">
              <AlertDescription className="text-green-800 dark:text-green-200">
                Settings saved successfully!
              </AlertDescription>
            </Alert>
          )}

          {/* Store Information */}
          <Card className="p-6 mb-6">
            <h2 className="text-xl font-semibold mb-4">Store Information</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">
                  Store Name
                </label>
                <Input
                  value={settings.storeName}
                  onChange={(e) => handleChange('storeName', e.target.value)}
                  placeholder="Store name"
                />
              </div>

              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Phone
                  </label>
                  <Input
                    value={settings.storePhone}
                    onChange={(e) => handleChange('storePhone', e.target.value)}
                    placeholder="+1 (555) 000-0000"
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Email
                  </label>
                  <Input
                    type="email"
                    value={settings.storeEmail}
                    onChange={(e) => handleChange('storeEmail', e.target.value)}
                    placeholder="store@example.com"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  Address
                </label>
                <Input
                  value={settings.storeAddress}
                  onChange={(e) => handleChange('storeAddress', e.target.value)}
                  placeholder="Store address"
                />
              </div>
            </div>
          </Card>

          {/* Business Settings */}
          <Card className="p-6 mb-6">
            <h2 className="text-xl font-semibold mb-4">Business Settings</h2>
            <div className="space-y-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Currency Symbol
                  </label>
                  <Input
                    value={settings.currencySymbol}
                    onChange={(e) => handleChange('currencySymbol', e.target.value)}
                    placeholder="$"
                    maxLength={3}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium mb-1">
                    Tax Rate (%)
                  </label>
                  <Input
                    type="number"
                    value={settings.taxRate}
                    onChange={(e) =>
                      handleChange('taxRate', parseFloat(e.target.value))
                    }
                    placeholder="8.5"
                    step="0.1"
                  />
                </div>
              </div>

              <div>
                <label className="block text-sm font-medium mb-1">
                  Low Stock Threshold
                </label>
                <Input
                  type="number"
                  value={settings.lowStockThreshold}
                  onChange={(e) =>
                    handleChange('lowStockThreshold', parseInt(e.target.value))
                  }
                  placeholder="10"
                />
                <p className="text-xs text-muted-foreground mt-1">
                  Alert when inventory falls below this quantity
                </p>
              </div>
            </div>
          </Card>

          {/* Preferences */}
          <Card className="p-6 mb-6">
            <h2 className="text-xl font-semibold mb-4">Preferences</h2>
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium">Enable Notifications</p>
                  <p className="text-sm text-muted-foreground">
                    Receive alerts for low stock and orders
                  </p>
                </div>
                <Switch
                  checked={settings.enableNotifications}
                  onCheckedChange={(checked) =>
                    handleChange('enableNotifications', checked)
                  }
                />
              </div>

              <div className="flex items-center justify-between border-t pt-4">
                <div>
                  <p className="font-medium">Auto Backup</p>
                  <p className="text-sm text-muted-foreground">
                    Automatically backup data daily
                  </p>
                </div>
                <Switch
                  checked={settings.autoBackup}
                  onCheckedChange={(checked) =>
                    handleChange('autoBackup', checked)
                  }
                />
              </div>
            </div>
          </Card>

          {/* User Account */}
          <Card className="p-6 mb-6">
            <h2 className="text-xl font-semibold mb-4">Your Account</h2>
            <div className="space-y-4">
              <div>
                <label className="block text-sm font-medium mb-1">Name</label>
                <Input value={user?.name || ''} disabled />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Email</label>
                <Input value={user?.email || ''} disabled />
              </div>
              <div>
                <label className="block text-sm font-medium mb-1">Role</label>
                <Input value={user?.role || ''} disabled />
              </div>
            </div>
          </Card>

          {/* System Info */}
          <Card className="p-6 mb-6 border-muted">
            <h2 className="text-xl font-semibold mb-4">System Information</h2>
            <div className="space-y-2 text-sm">
              <div className="flex justify-between">
                <span className="text-muted-foreground">Version</span>
                <span className="font-medium">1.0.0</span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Last Backup</span>
                <span className="font-medium">
                  {new Date().toLocaleDateString()}
                </span>
              </div>
              <div className="flex justify-between">
                <span className="text-muted-foreground">Database</span>
                <span className="font-medium">Connected</span>
              </div>
            </div>
          </Card>

          {/* Actions */}
          <div className="space-y-4">
            <Button
              onClick={handleSaveSettings}
              disabled={isSaving}
              className="w-full h-11"
            >
              <Save className="w-4 h-4 mr-2" />
              {isSaving ? 'Saving...' : 'Save Settings'}
            </Button>

            <Button
              onClick={logout}
              variant="outline"
              className="w-full h-11"
            >
              Logout
            </Button>
          </div>
        </main>
      </div>
    </ProtectedRoute>
  );
}
