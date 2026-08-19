import { useState } from 'react';
import { Settings as SettingsIcon, Save, RefreshCw } from 'lucide-react';

export function Settings() {
  const [settings, setSettings] = useState({
    serverName: 'nMon Server',
    refreshInterval: 60,
    alertThresholds: {
      cpu: 80,
      memory: 85,
      disk: 90
    },
    notifications: {
      email: true,
      webhook: false,
      webhookUrl: ''
    }
  });

  const [saving, setSaving] = useState(false);

  const handleSave = async () => {
    setSaving(true);
    // Simulate save
    await new Promise(resolve => setTimeout(resolve, 1000));
    setSaving(false);
    alert('Settings saved successfully!');
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-white">Settings</h1>
        <button
          onClick={handleSave}
          disabled={saving}
          className="flex items-center px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"
        >
          {saving ? (
            <RefreshCw className="w-4 h-4 mr-2 animate-spin" />
          ) : (
            <Save className="w-4 h-4 mr-2" />
          )}
          Save Settings
        </button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* General Settings */}
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <div className="flex items-center mb-4">
            <SettingsIcon className="w-5 h-5 mr-2 text-blue-500" />
            <h2 className="text-lg font-semibold text-white">General</h2>
          </div>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm text-gray-400 mb-1">Server Name</label>
              <input
                type="text"
                value={settings.serverName}
                onChange={(e) => setSettings({ ...settings, serverName: e.target.value })}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label className="block text-sm text-gray-400 mb-1">
                Refresh Interval (seconds)
              </label>
              <input
                type="number"
                value={settings.refreshInterval}
                onChange={(e) => setSettings({ ...settings, refreshInterval: parseInt(e.target.value) })}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        {/* Alert Thresholds */}
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <div className="flex items-center mb-4">
            <SettingsIcon className="w-5 h-5 mr-2 text-yellow-500" />
            <h2 className="text-lg font-semibold text-white">Alert Thresholds</h2>
          </div>
          
          <div className="space-y-4">
            <div>
              <label className="block text-sm text-gray-400 mb-1">CPU Usage (%)</label>
              <input
                type="number"
                value={settings.alertThresholds.cpu}
                onChange={(e) => setSettings({
                  ...settings,
                  alertThresholds: { ...settings.alertThresholds, cpu: parseInt(e.target.value) }
                })}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label className="block text-sm text-gray-400 mb-1">Memory Usage (%)</label>
              <input
                type="number"
                value={settings.alertThresholds.memory}
                onChange={(e) => setSettings({
                  ...settings,
                  alertThresholds: { ...settings.alertThresholds, memory: parseInt(e.target.value) }
                })}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
            
            <div>
              <label className="block text-sm text-gray-400 mb-1">Disk Usage (%)</label>
              <input
                type="number"
                value={settings.alertThresholds.disk}
                onChange={(e) => setSettings({
                  ...settings,
                  alertThresholds: { ...settings.alertThresholds, disk: parseInt(e.target.value) }
                })}
                className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>
          </div>
        </div>

        {/* Notifications */}
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700 lg:col-span-2">
          <div className="flex items-center mb-4">
            <SettingsIcon className="w-5 h-5 mr-2 text-green-500" />
            <h2 className="text-lg font-semibold text-white">Notifications</h2>
          </div>
          
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-white">Email Notifications</p>
                <p className="text-sm text-gray-400">Receive alerts via email</p>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.notifications.email}
                  onChange={(e) => setSettings({
                    ...settings,
                    notifications: { ...settings.notifications, email: e.target.checked }
                  })}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            
            <div className="flex items-center justify-between">
              <div>
                <p className="text-white">Webhook Notifications</p>
                <p className="text-sm text-gray-400">Send alerts to a webhook URL</p>
              </div>
              <label className="relative inline-flex items-center cursor-pointer">
                <input
                  type="checkbox"
                  checked={settings.notifications.webhook}
                  onChange={(e) => setSettings({
                    ...settings,
                    notifications: { ...settings.notifications, webhook: e.target.checked }
                  })}
                  className="sr-only peer"
                />
                <div className="w-11 h-6 bg-gray-600 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-800 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
              </label>
            </div>
            
            {settings.notifications.webhook && (
              <div>
                <label className="block text-sm text-gray-400 mb-1">Webhook URL</label>
                <input
                  type="url"
                  value={settings.notifications.webhookUrl}
                  onChange={(e) => setSettings({
                    ...settings,
                    notifications: { ...settings.notifications, webhookUrl: e.target.value }
                  })}
                  placeholder="https://your-webhook-url.com"
                  className="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
