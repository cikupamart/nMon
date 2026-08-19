import { useEffect, useState } from 'react';
import { 
  Server, 
  Box, 
  Layers,
  AlertTriangle,
  CheckCircle,
  RefreshCw
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

interface Node {
  name: string;
  ip: string;
  ready: boolean;
  os: string;
  arch: string;
  kubelet_version: string;
  capacity_cpu: string;
  capacity_memory: string;
}

interface Pod {
  name: string;
  namespace: string;
  phase: string;
  node: string;
  restarts: number;
}

interface Deployment {
  name: string;
  namespace: string;
  desired: number;
  ready: number;
  available: number;
}

interface K8sMetrics {
  cluster_version: string;
  nodes: Node[];
  node_summary: {
    total: number;
    ready: number;
    not_ready: number;
    total_cpu: number;
  };
  pods: {
    total: number;
    status: Record<string, number>;
    failed: Pod[];
  };
  deployments: {
    total: number;
    list: Deployment[];
    issues: Deployment[];
  };
}

export function KubernetesMonitor() {
  const { agents } = useWebSocket();
  const [metrics, setMetrics] = useState<K8sMetrics | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedAgent, setSelectedAgent] = useState<string>('');

  useEffect(() => {
    if (selectedAgent) {
      fetchK8sMetrics();
    }
  }, [selectedAgent]);

  const fetchK8sMetrics = async () => {
    try {
      const response = await axios.get(`/api/agents/${selectedAgent}/kubernetes`);
      setMetrics(response.data);
    } catch (error) {
      console.error('Failed to fetch K8s metrics:', error);
    } finally {
      setLoading(false);
    }
  };

  const podStatusData = metrics ? Object.entries(metrics.pods.status).map(([status, count]) => ({
    name: status,
    value: count,
    color: status === 'running' ? '#22c55e' :
           status === 'pending' ? '#eab308' :
           status === 'failed' ? '#ef4444' :
           status === 'succeeded' ? '#3b82f6' : '#6b7280'
  })) : [];

  const nodeStatusData = metrics ? [
    { name: 'Ready', value: metrics.node_summary.ready, color: '#22c55e' },
    { name: 'Not Ready', value: metrics.node_summary.not_ready, color: '#ef4444' }
  ] : [];

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
        <h1 className="text-2xl font-bold text-white">Kubernetes Monitor</h1>
        <div className="flex items-center space-x-4">
          {metrics && (
            <span className="text-gray-400">v{metrics.cluster_version}</span>
          )}
          <select
            value={selectedAgent}
            onChange={(e) => setSelectedAgent(e.target.value)}
            className="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white"
          >
            <option value="">Select Agent</option>
          </select>
        </div>
      </div>

      {metrics ? (
        <>
          {/* Summary Cards */}
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <div className="flex items-center text-gray-400 mb-2">
                <Server className="w-4 h-4 mr-2" />
                <span className="text-sm">Nodes</span>
              </div>
              <p className="text-2xl font-bold text-white">{metrics.node_summary.total}</p>
              <p className="text-xs text-green-500">{metrics.node_summary.ready} ready</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <div className="flex items-center text-gray-400 mb-2">
                <Box className="w-4 h-4 mr-2" />
                <span className="text-sm">Pods</span>
              </div>
              <p className="text-2xl font-bold text-white">{metrics.pods.total}</p>
              <p className="text-xs text-gray-500">{metrics.pods.status.running || 0} running</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <div className="flex items-center text-gray-400 mb-2">
                <Layers className="w-4 h-4 mr-2" />
                <span className="text-sm">Deployments</span>
              </div>
              <p className="text-2xl font-bold text-white">{metrics.deployments.total}</p>
              <p className="text-xs text-red-500">{metrics.deployments.issues.length} issues</p>
            </div>
            <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
              <div className="flex items-center text-gray-400 mb-2">
                <AlertTriangle className="w-4 h-4 mr-2" />
                <span className="text-sm">Failed Pods</span>
              </div>
              <p className="text-2xl font-bold text-white">{metrics.pods.failed.length}</p>
            </div>
          </div>

          {/* Charts */}
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
              <h2 className="text-lg font-semibold text-white mb-4">Pod Status</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={podStatusData}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={100}
                      paddingAngle={5}
                      dataKey="value"
                    >
                      {podStatusData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>
              <div className="flex justify-center flex-wrap gap-4 mt-4">
                {podStatusData.map((item) => (
                  <div key={item.name} className="flex items-center">
                    <div className="w-3 h-3 rounded-full mr-2" style={{ backgroundColor: item.color }} />
                    <span className="text-sm text-gray-400 capitalize">{item.name}: {item.value}</span>
                  </div>
                ))}
              </div>
            </div>

            <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
              <h2 className="text-lg font-semibold text-white mb-4">Node Status</h2>
              <div className="h-64">
                <ResponsiveContainer width="100%" height="100%">
                  <PieChart>
                    <Pie
                      data={nodeStatusData}
                      cx="50%"
                      cy="50%"
                      innerRadius={60}
                      outerRadius={100}
                      paddingAngle={5}
                      dataKey="value"
                    >
                      {nodeStatusData.map((entry, index) => (
                        <Cell key={`cell-${index}`} fill={entry.color} />
                      ))}
                    </Pie>
                    <Tooltip />
                  </PieChart>
                </ResponsiveContainer>
              </div>
            </div>
          </div>

          {/* Nodes Table */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 className="text-lg font-semibold text-white mb-4">Nodes</h2>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="text-left text-gray-400 border-b border-gray-700">
                    <th className="pb-3">Name</th>
                    <th className="pb-3">Status</th>
                    <th className="pb-3">OS</th>
                    <th className="pb-3">Arch</th>
                    <th className="pb-3">Kubelet</th>
                    <th className="pb-3">CPU</th>
                    <th className="pb-3">Memory</th>
                  </tr>
                </thead>
                <tbody>
                  {metrics.nodes.map((node) => (
                    <tr key={node.name} className="border-b border-gray-700 hover:bg-gray-700/50">
                      <td className="py-3">
                        <div className="flex items-center">
                          <Server className="w-4 h-4 mr-2 text-blue-500" />
                          <span className="text-white">{node.name}</span>
                        </div>
                      </td>
                      <td className="py-3">
                        {node.ready ? (
                          <span className="flex items-center text-green-500">
                            <CheckCircle className="w-4 h-4 mr-1" /> Ready
                          </span>
                        ) : (
                          <span className="flex items-center text-red-500">
                            <AlertTriangle className="w-4 h-4 mr-1" /> Not Ready
                          </span>
                        )}
                      </td>
                      <td className="py-3 text-gray-300">{node.os}</td>
                      <td className="py-3 text-gray-300">{node.arch}</td>
                      <td className="py-3 text-gray-300">{node.kubelet_version}</td>
                      <td className="py-3 text-gray-300">{node.capacity_cpu}</td>
                      <td className="py-3 text-gray-300">{node.capacity_memory}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Deployments Table */}
          <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 className="text-lg font-semibold text-white mb-4">Deployments</h2>
            <div className="overflow-x-auto">
              <table className="w-full">
                <thead>
                  <tr className="text-left text-gray-400 border-b border-gray-700">
                    <th className="pb-3">Name</th>
                    <th className="pb-3">Namespace</th>
                    <th className="pb-3">Desired</th>
                    <th className="pb-3">Ready</th>
                    <th className="pb-3">Available</th>
                    <th className="pb-3">Status</th>
                  </tr>
                </thead>
                <tbody>
                  {metrics.deployments.list.map((deploy) => (
                    <tr key={`${deploy.namespace}/${deploy.name}`} className="border-b border-gray-700 hover:bg-gray-700/50">
                      <td className="py-3 text-white">{deploy.name}</td>
                      <td className="py-3 text-gray-300">{deploy.namespace}</td>
                      <td className="py-3 text-gray-300">{deploy.desired}</td>
                      <td className="py-3 text-gray-300">{deploy.ready}</td>
                      <td className="py-3 text-gray-300">{deploy.available}</td>
                      <td className="py-3">
                        {deploy.ready === deploy.desired ? (
                          <span className="text-green-500">Healthy</span>
                        ) : (
                          <span className="text-yellow-500">Degraded</span>
                        )}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>

          {/* Failed Pods */}
          {metrics.pods.failed.length > 0 && (
            <div className="bg-gray-800 rounded-lg p-6 border border-red-500">
              <h2 className="text-lg font-semibold text-red-400 mb-4">
                <AlertTriangle className="w-5 h-5 inline mr-2" />
                Failed Pods
              </h2>
              <div className="overflow-x-auto">
                <table className="w-full">
                  <thead>
                    <tr className="text-left text-gray-400 border-b border-gray-700">
                      <th className="pb-3">Name</th>
                      <th className="pb-3">Namespace</th>
                      <th className="pb-3">Node</th>
                      <th className="pb-3">Restarts</th>
                    </tr>
                  </thead>
                  <tbody>
                    {metrics.pods.failed.map((pod) => (
                      <tr key={pod.name} className="border-b border-gray-700">
                        <td className="py-3 text-white">{pod.name}</td>
                        <td className="py-3 text-gray-300">{pod.namespace}</td>
                        <td className="py-3 text-gray-300">{pod.node}</td>
                        <td className="py-3 text-red-400">{pod.restarts}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </>
      ) : (
        <div className="text-center py-12 bg-gray-800 rounded-lg border border-gray-700">
          <Box className="w-12 h-12 mx-auto text-gray-500 mb-4" />
          <p className="text-gray-400">Select an agent with Kubernetes to view cluster metrics</p>
        </div>
      )}
    </div>
  );
}
