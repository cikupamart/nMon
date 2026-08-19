import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  Server, 
  Plus, 
  Search, 
  Filter,
  Cpu,
  HardDrive,
  Wifi
} from 'lucide-react';
import { useWebSocket } from '../contexts/WebSocketContext';
import axios from 'axios';

interface Agent {
  _id: string;
  name: string;
  hostname: string;
  status: string;
  type: string;
  group: string;
  metrics: {
    cpu: { usage: number };
    memory: { usage: number };
  };
  lastSeen: string;
}

export function AgentList() {
  const { agents: wsAgents } = useWebSocket();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');

  useEffect(() => {
    fetchAgents();
  }, []);

  useEffect(() => {
    // Update agents from WebSocket
    wsAgents.forEach((data, agentId) => {
      setAgents(prev => {
        const index = prev.findIndex(a => a._id === agentId);
        if (index >= 0) {
          const updated = [...prev];
          updated[index] = { ...updated[index], ...data };
          return updated;
        }
        return prev;
      });
    });
  }, [wsAgents]);

  const fetchAgents = async () => {
    try {
      const response = await axios.get('/api/agents');
      setAgents(response.data);
    } catch (error) {
      console.error('Failed to fetch agents:', error);
    } finally {
      setLoading(false);
    }
  };

  const filteredAgents = agents.filter(agent => {
    const matchesSearch = 
      agent.name.toLowerCase().includes(search.toLowerCase()) ||
      agent.hostname.toLowerCase().includes(search.toLowerCase());
    const matchesStatus = statusFilter === 'all' || agent.status === statusFilter;
    return matchesSearch && matchesStatus;
  });

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
        <h1 className="text-2xl font-bold text-white">Agents</h1>
        <button className="flex items-center px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700">
          <Plus className="w-4 h-4 mr-2" />
          Add Agent
        </button>
      </div>

      {/* Filters */}
      <div className="flex items-center space-x-4">
        <div className="relative flex-1 max-w-md">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
          <input
            type="text"
            placeholder="Search agents..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            className="w-full pl-10 pr-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
          />
        </div>
        
        <select
          value={statusFilter}
          onChange={(e) => setStatusFilter(e.target.value)}
          className="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
          <option value="all">All Status</option>
          <option value="online">Online</option>
          <option value="warning">Warning</option>
          <option value="critical">Critical</option>
          <option value="offline">Offline</option>
        </select>
      </div>

      {/* Agent Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        {filteredAgents.map((agent) => (
          <Link
            key={agent._id}
            to={`/agents/${agent._id}`}
            className="block bg-gray-800 rounded-lg p-6 border border-gray-700 hover:border-blue-500 transition-colors"
          >
            <div className="flex items-center justify-between mb-4">
              <div className="flex items-center">
                <Server className="w-5 h-5 mr-2 text-blue-500" />
                <h3 className="font-semibold text-white">{agent.name}</h3>
              </div>
              <span className={`px-2 py-1 rounded text-xs text-white ${
                agent.status === 'online' ? 'bg-green-500' :
                agent.status === 'warning' ? 'bg-yellow-500' :
                agent.status === 'critical' ? 'bg-red-500' : 'bg-gray-500'
              }`}>
                {agent.status}
              </span>
            </div>
            
            <p className="text-gray-400 text-sm mb-4">{agent.hostname}</p>
            
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <div className="flex items-center text-gray-400">
                  <Cpu className="w-4 h-4 mr-2" />
                  CPU
                </div>
                <span className="text-white">{agent.metrics.cpu.usage.toFixed(1)}%</span>
              </div>
              
              <div className="flex items-center justify-between text-sm">
                <div className="flex items-center text-gray-400">
                  <HardDrive className="w-4 h-4 mr-2" />
                  Memory
                </div>
                <span className="text-white">{agent.metrics.memory.usage.toFixed(1)}%</span>
              </div>
            </div>
            
            <div className="mt-4 pt-4 border-t border-gray-700 text-xs text-gray-500">
              Last seen: {new Date(agent.lastSeen).toLocaleString()}
            </div>
          </Link>
        ))}
      </div>

      {filteredAgents.length === 0 && (
        <div className="text-center py-12">
          <Server className="w-12 h-12 mx-auto text-gray-500 mb-4" />
          <p className="text-gray-400">No agents found</p>
        </div>
      )}
    </div>
  );
}
