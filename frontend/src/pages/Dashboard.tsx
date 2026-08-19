import { useEffect, useState } from 'react';
import { 
  Server, 
  Activity, 
  AlertTriangle, 
  CheckCircle,
  XCircle,
  TrendingUp
} from 'lucide-react';
import { 
  LineChart, 
  Line, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer,
  PieChart,
  Pie,
  Cell
} from 'recharts';
import { useWebSocket } from '../contexts/WebSocketContext';
import axios from 'axios';

interface DashboardStats {
  totalAgents: number;
  onlineAgents: number;
  warningAgents: number;
  criticalAgents: number;
  offlineAgents: number;
  activeAlerts: number;
}

export function Dashboard() {
  const { agents, alerts } = useWebSocket();
  const [stats, setStats] = useState<DashboardStats>({
    totalAgents: 0,
    onlineAgents: 0,
    warningAgents: 0,
    criticalAgents: 0,
    offlineAgents: 0,
    activeAlerts: 0
  });

  useEffect(() => {
    fetchStats();
  }, []);

  const fetchStats = async () => {
    try {
      const response = await axios.get('/api/agents/stats/overview');
      setStats(response.data);
    } catch (error) {
      console.error('Failed to fetch stats:', error);
    }
  };

  const statusData = [
    { name: 'Online', value: stats.onlineAgents, color: '#22c55e' },
    { name: 'Warning', value: stats.warningAgents, color: '#eab308' },
    { name: 'Critical', value: stats.criticalAgents, color: '#ef4444' },
    { name: 'Offline', value: stats.offlineAgents, color: '#6b7280' }
  ];

  const statCards = [
    { 
      label: 'Total Agents', 
      value: stats.totalAgents, 
      icon: Server, 
      color: 'bg-blue-500' 
    },
    { 
      label: 'Online', 
      value: stats.onlineAgents, 
      icon: CheckCircle, 
      color: 'bg-green-500' 
    },
    { 
      label: 'Active Alerts', 
      value: stats.activeAlerts, 
      icon: AlertTriangle, 
      color: 'bg-yellow-500' 
    },
    { 
      label: 'Critical', 
      value: stats.criticalAgents, 
      icon: XCircle, 
      color: 'bg-red-500' 
    }
  ];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold text-white">Dashboard</h1>
        <div className="text-sm text-gray-400">
          Last updated: {new Date().toLocaleString()}
        </div>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        {statCards.map((stat) => (
          <div
            key={stat.label}
            className="bg-gray-800 rounded-lg p-6 border border-gray-700"
          >
            <div className="flex items-center justify-between">
              <div>
                <p className="text-gray-400 text-sm">{stat.label}</p>
                <p className="text-3xl font-bold text-white mt-1">
                  {stat.value}
                </p>
              </div>
              <div className={`p-3 rounded-lg ${stat.color}`}>
                <stat.icon className="w-6 h-6 text-white" />
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Charts Row */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Agent Status Distribution */}
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <h2 className="text-lg font-semibold text-white mb-4">
            Agent Status Distribution
          </h2>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <PieChart>
                <Pie
                  data={statusData}
                  cx="50%"
                  cy="50%"
                  innerRadius={60}
                  outerRadius={100}
                  paddingAngle={5}
                  dataKey="value"
                >
                  {statusData.map((entry, index) => (
                    <Cell key={`cell-${index}`} fill={entry.color} />
                  ))}
                </Pie>
                <Tooltip />
              </PieChart>
            </ResponsiveContainer>
          </div>
          <div className="flex justify-center space-x-4 mt-4">
            {statusData.map((item) => (
              <div key={item.name} className="flex items-center">
                <div
                  className="w-3 h-3 rounded-full mr-2"
                  style={{ backgroundColor: item.color }}
                />
                <span className="text-sm text-gray-400">
                  {item.name}: {item.value}
                </span>
              </div>
            ))}
          </div>
        </div>

        {/* CPU Usage Chart */}
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <h2 className="text-lg font-semibold text-white mb-4">
            CPU Usage Over Time
          </h2>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={generateMockData()}>
                <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
                <XAxis dataKey="time" stroke="#9ca3af" />
                <YAxis stroke="#9ca3af" />
                <Tooltip
                  contentStyle={{
                    backgroundColor: '#1f2937',
                    border: '1px solid #374151',
                    borderRadius: '8px'
                  }}
                />
                <Line
                  type="monotone"
                  dataKey="cpu"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  dot={false}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Recent Alerts */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 className="text-lg font-semibold text-white mb-4">
          Recent Alerts
        </h2>
        <div className="space-y-3">
          {alerts.length === 0 ? (
            <p className="text-gray-400 text-center py-4">
              No recent alerts
            </p>
          ) : (
            alerts.slice(0, 5).map((alert, index) => (
              <div
                key={index}
                className="flex items-center justify-between p-3 bg-gray-700 rounded-lg"
              >
                <div className="flex items-center">
                  <AlertTriangle
                    className={`w-5 h-5 mr-3 ${
                      alert.severity === 'critical'
                        ? 'text-red-500'
                        : 'text-yellow-500'
                    }`}
                  />
                  <div>
                    <p className="text-white font-medium">{alert.message}</p>
                    <p className="text-sm text-gray-400">
                      {alert.agentName} • {new Date(alert.timestamp).toLocaleString()}
                    </p>
                  </div>
                </div>
                <span
                  className={`px-2 py-1 text-xs rounded ${
                    alert.severity === 'critical'
                      ? 'bg-red-500/20 text-red-400'
                      : 'bg-yellow-500/20 text-yellow-400'
                  }`}
                >
                  {alert.severity}
                </span>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  );
}

function generateMockData() {
  return Array.from({ length: 24 }, (_, i) => ({
    time: `${i}:00`,
    cpu: Math.floor(Math.random() * 40) + 30
  }));
}
