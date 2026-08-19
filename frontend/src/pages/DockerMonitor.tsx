import { useEffect, useState } from 'react';
import { 
  Container, 
  Cpu, 
  HardDrive, 
  Wifi, 
  RefreshCw,
  Play,
  Pause,
  Trash2
} from 'lucide-react';
import { 
  BarChart, 
  Bar, 
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

interface DockerContainer {
  id: string;
  name: string;
  image: string;
  state: string;
  status: string;
  stats?: {
    cpu_percent: number;
    memory_usage: number;
    memory_limit: number;
    memory_percent: number;
    network_rx: number;
    network_tx: number;
  };
}

interface DockerMetrics {
  info: {
    version: string;
    total_containers: number;
    running_containers: number;
    stopped_containers: number;
    images: number;
    cpus: number;
  };
  containers: DockerContainer[];
  summary: {
    total_cpu: number;
    total_memory: number;
    total_network_rx: number;
    total_network_tx: number;
  };
}

export function DockerMonitor() {
  const { agents } = useWebSocket();
  const [metrics, setMetrics] = useState<DockerMetrics | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedAgent, setSelectedAgent] = useState<string>('');

  useEffect(() => {
    if (selectedAgent) {
      fetchDockerMetrics();
    }
  }, [selectedAgent]);

  const fetchDockerMetrics = async () => {
    try {
      const response = await axios.get(`/api/agents/${selectedAgent}/docker`);
      setMetrics(response.data);
    } catch (error) {
      console.error('Failed to fetch Docker metrics:', error);
    } finally {
      setLoading(false);
    }
  };

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const getStateColor = (state: string) => {
    switch (state) {
      case 'running': return 'bg-green-500';
      case 'paused': return 'bg-yellow-500';
      case 'stopped': return 'bg-gray-500';
      case 'restarting': return 'bg-blue-500';
      default: return 'bg-gray-500';
    }
  };

  const pieData = metrics ? [
    { name: 'Running', value: metrics.info.running_containers, color: '#22c55e' },
    { name: 'Stopped', value: metrics.info.stopped_containers, color: '#6b7280' }
  ] : [];

  const containerChartData = metrics?.containers
    .filter(c => c.state === 'running' && c.stats)
    .slice(0, 10)
    .map(c => ({
      name: c.name.substring(0, 15),
      cpu: c.stats?.cpu_percent || 0,
      memory: c.stats?.memory_percent || 0
    })) || [];

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
        <h1 className="text-2xl font-bold text-white">Docker Monitor</h1>
        <select
          value={selectedAgent}
          onChange={(e) => setSelectedAgent(e.target.value)}
          className="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
        >
          <option value="">Select Agent</option>
          {/* Populated from agents */}
        </select>
      </div>

      {metrics ? (
        <>
          {/* Docker Info */}
          <div className="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <p className="text-gray-400 text-sm">Docker Version</p>
              <p className="text-xl font-bold text-white">{metrics.info.version}</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <p className="text-gray-400 text-sm">Total Containers</p>
              <p className="text-xl font-bold text-white">{metrics.info.total_containers}</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <p className="text-gray-400 text-sm">Running</p>
              <p className="text-xl font-bold text-green-500">{metrics.info.running_containers}</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <p className="text-gray-400 text-sm">Stopped</p>
              <p className="text-xl font-bold text-gray-400">{metrics.info.stopped_containers}</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <p className="text-gray-400 text-sm">Images</p>
              <p className="text-xl font-bold text-white">{metrics.info.images}</p>
            </div>
          </div>

          {/* Charts */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
              <h2 className="text-lg font-semibold text-white mb-4">Container Status</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={pieData}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={100}
                      paddingAngle={5}
                      dataKey="value"
                    >
                      {pieData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>

            <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
              <h2 className="text-lg font-semibold text-white mb-4">Resource Usage (Top 10)</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <BarChart data={containerChartData}>
                    <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
                    <XAxis dataKey="name" stroke="#9ca3af" />
                    <YAxis stroke="#9ca3af" />
                    <Tooltip
                      contentStyle={{
                        backgroundColor: '#1f2937',
                        border: '1px solid #374151',
                        borderRadius: '8px'
                      }}
                    />
                    <Bar dataKey="cpu" fill="#3b82f6" name="CPU %" />
                    <Bar dataKey="memory" fill="#8b5cf6" name="Memory %" />
                  </BarChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          {/* Container List */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 className="text-lg font-semibold text-white mb-4">Containers</h2>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="text-left text-gray-400 border-b border-gray-700">
                    <th className="pb-3">Name</th>
                    <th className="pb-3">Image</th>
                    <th className="pb-3">State</th>
                    <th className="pb-3">CPU</th>
                    <th className="pb-3">Memory</th>
                    <th className="pb-3">Network I/O</th>
                    <th className="pb-3">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {metrics.containers.map((container) => (
                    <tr key={container.id} className="border-b border-gray-700 hover:bg-gray-700/50">
                      <td className="py-3">
                        <div className="flex items-center">
                          <Container className="w-4 h-4 mr-2 text-blue-500" />
                          <span className="text-white">{container.name}</span>
                        </div>
                      </td>
                      <td className="py-3 text-gray-300 text-sm">{container.image}</td>
                      <td className="py-3">
                        <span className={`px-2 py-1 rounded text-xs text-white ${getStateColor(container.state)}`}>
                          {container.state}
                        </span>
                      </td>
                      <td className="py-3 text-gray-300">
                        {container.stats?.cpu_percent.toFixed(1) || '-'}%
                      </td>
                      <td className="py-3 text-gray-300">
                        {container.stats ? formatBytes(container.stats.memory_usage) : '-'}
                      </td>
                      <td className="py-3 text-gray-300 text-sm">
                        {container.stats ? (
                          <>↓{formatBytes(container.stats.network_rx)} ↑{formatBytes(container.stats.network_tx)}</>
                        ) : '-'}
                      </td>
                      <td className="py-3">
                        <div className="flex items-center space-x-2">
                          {container.state === 'running' ? (
                            <button className="p-1 text-yellow-500 hover:text-yellow-400">
                              <Pause className="w-4 h-4" />
                            </button>
                          ) : (
                            <button className="p-1 text-green-500 hover:text-green-400">
                              <Play className="w-4 h-4" />
                            </button>
                          )}
                          <button className="p-1 text-red-500 hover:text-red-400">
                            <Trash2 className="w-4 h-4" />
                          </button>
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
        </>
      ) : (
        <div className="text-center py-12 bg-gray-800 rounded-lg border border-gray-700">
          <Container className="w-12 h-12 mx-auto text-gray-500 mb-4" />
          <p className="text-gray-400">Select an agent with Docker to view metrics</p>
        </div>
      )}
    </div>
  );
}
