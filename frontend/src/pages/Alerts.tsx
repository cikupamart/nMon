import { useEffect, useState } from 'react';
import { 
  AlertTriangle, 
  CheckCircle, 
  XCircle,
  Bell,
  Filter
} from 'lucide-react';
import { useWebSocket } from '../contexts/WebSocketContext';
import axios from 'axios';

interface Alert {
  _id: string;
  agentId: string;
  type: string;
  severity: string;
  message: string;
  status: string;
  triggeredAt: string;
  resolvedAt?: string;
  occurrences: number;
}

export function Alerts() {
  const { alerts: wsAlerts } = useWebSocket();
  const [alerts, setAlerts] = useState<Alert[]>([]);
  const [loading, setLoading] = useState(true);
  const [statusFilter, setStatusFilter] = useState('active');

  useEffect(() => {
    fetchAlerts();
  }, [statusFilter]);

  useEffect(() => {
    // Add new WebSocket alerts to the list
    wsAlerts.forEach(wsAlert => {
      setAlerts(prev => {
        const exists = prev.find(a => a._id === wsAlert.alertId);
        if (!exists) {
          return [{
            _id: wsAlert.alertId,
            agentId: wsAlert.agentId,
            type: wsAlert.type,
            severity: wsAlert.severity,
            message: wsAlert.message,
            status: 'active',
            triggeredAt: wsAlert.timestamp,
            occurrences: 1
          }, ...prev];
        }
        return prev;
      });
    });
  }, [wsAlerts]);

  const fetchAlerts = async () => {
    try {
      const response = await axios.get(`/api/alerts?status=${statusFilter}`);
      setAlerts(response.data);
    } catch (error) {
      console.error('Failed to fetch alerts:', error);
    } finally {
      setLoading(false);
    }
  };

  const acknowledgeAlert = async (alertId: string) => {
    try {
      await axios.put(`/api/alerts/${alertId}/acknowledge`);
      setAlerts(prev =>
        prev.map(a =>
          a._id === alertId ? { ...a, status: 'acknowledged' } : a
        )
      );
    } catch (error) {
      console.error('Failed to acknowledge alert:', error);
    }
  };

  const resolveAlert = async (alertId: string) => {
    try {
      await axios.put(`/api/alerts/${alertId}/resolve`);
      setAlerts(prev =>
        prev.map(a =>
          a._id === alertId ? { ...a, status: 'resolved', resolvedAt: new Date().toISOString() } : a
        )
      );
    } catch (error) {
      console.error('Failed to resolve alert:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-white">Alerts</h1>
        <div className="flex items-center space-x-2">
          <Bell className="w-5 h-5 text-gray-400" />
          <span className="text-gray-400">
            {alerts.filter(a => a.status === 'active').length} active
          </span>
        </div>
      </div>

      {/* Filters */}
      <div className="flex items-center space-x-4">
        <Filter className="w-5 h-5 text-gray-400" />
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="active">Active</option>
          <option value="acknowledged">Acknowledged</option>
          <option value="resolved">Resolved</option>
          <option value="all">All</option>
        </select>
      </div>

      {/* Alerts List */}
      <div className="space-y-4">
        {alerts.map((alert) => (
          <div
            key={alert._id}
            className={`bg-gray-800 rounded-lg p-4 border ${
              alert.severity === 'critical' ? 'border-red-500' :
              alert.severity === 'warning' ? 'border-yellow-500' : 'border-gray-700'
            }`}
          >
            <div className="flex items-start justify-between">
              <div className="flex items-start">
                {alert.severity === 'critical' ? (
                  <XCircle className="w-5 h-5 mr-3 text-red-500 mt-0.5" />
                ) : alert.severity === 'warning' ? (
                  <AlertTriangle className="w-5 h-5 mr-3 text-yellow-500 mt-0.5" />
                ) : (
                  <Bell className="w-5 h-5 mr-3 text-blue-500 mt-0.5" />
                )}
                
                <div>
                  <h3 className="text-white font-medium">{alert.message}</h3>
                  <div className="flex items-center mt-1 text-sm text-gray-400">
                    <span className="capitalize">{alert.type}</span>
                    <span className="mx-2">•</span>
                    <span>{alert.occurrences} occurrences</span>
                    <span className="mx-2">•</span>
                    <span>{new Date(alert.triggeredAt).toLocaleString()}</span>
                  </div>
                </div>
              </div>
              
              <div className="flex items-center space-x-2">
                <span className={`px-2 py-1 rounded text-xs ${
                  alert.status === 'active' ? 'bg-red-500/20 text-red-400' :
                  alert.status === 'acknowledged' ? 'bg-yellow-500/20 text-yellow-400' :
                  'bg-green-500/20 text-green-400'
                }`}>
                  {alert.status}
                </span>
                
                {alert.status === 'active' && (
                  <>
                    <button
                      onClick={() => acknowledgeAlert(alert._id)}
                      className="px-3 py-1 bg-gray-700 rounded text-sm text-gray-300 hover:bg-gray-600"
                    >
                      Acknowledge
                    </button>
                    <button
                      onClick={() => resolveAlert(alert._id)}
                      className="px-3 py-1 bg-green-600 rounded text-sm text-white hover:bg-green-700"
                    >
                      Resolve
                    </button>
                  </>
                )}
              </div>
            </div>
          </div>
        ))}

        {alerts.length === 0 && (
          <div className="text-center py-12">
            <CheckCircle className="w-12 h-12 mx-auto text-green-500 mb-4" />
            <p className="text-gray-400">No alerts found</p>
          </div>
        )}
      </div>
    </div>
  );
}
