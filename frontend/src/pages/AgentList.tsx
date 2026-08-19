import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { 
  Server, 
  Plus, 
  Search, 
  Cpu,
  HardDrive,
  X,
  Copy,
  Check,
  MapPin,
  Terminal
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
  location?: {
    name: string;
    lat: number;
    lng: number;
  };
  metrics: {
    cpu: { usage: number };
    memory: { usage: number };
  };
  lastSeen: string;
}

interface CreateAgentResponse {
  agent: Agent;
  message: string;
  installCommand: string;
  serverKey: string;
}

export function AgentList() {
  const { agents: wsAgents } = useWebSocket();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  
  // Add Agent modal state
  const [showAddModal, setShowAddModal] = useState(false);
  const [addLoading, setAddLoading] = useState(false);
  const [addResult, setAddResult] = useState<CreateAgentResponse | null>(null);
  const [copiedKey, setCopiedKey] = useState(false);
  const [copiedCmd, setCopiedCmd] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    hostname: '',
    type: 'linux' as 'linux' | 'windows' | 'macos',
    group: 'default',
    locationName: '',
    lat: '',
    lng: '',
    notes: ''
  });
  const [formError, setFormError] = useState('');

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

  const handleCreateAgent = async () => {
    setFormError('');
    
    if (!formData.name.trim() || !formData.hostname.trim()) {
      setFormError('Name and hostname are required');
      return;
    }

    setAddLoading(true);
    try {
      const payload: any = {
        name: formData.name.trim(),
        hostname: formData.hostname.trim(),
        type: formData.type,
        group: formData.group.trim() || 'default',
        notes: formData.notes.trim()
      };

      // Add location if provided
      if (formData.locationName.trim() || (formData.lat && formData.lng)) {
        payload.location = {
          name: formData.locationName.trim(),
          lat: parseFloat(formData.lat) || 0,
          lng: parseFloat(formData.lng) || 0
        };
      }

      const response = await axios.post<CreateAgentResponse>('/api/agents', payload);
      setAddResult(response.data);
      
      // Refresh agent list
      fetchAgents();
    } catch (error: any) {
      const msg = error.response?.data?.error || 'Failed to create agent';
      setFormError(msg);
    } finally {
      setAddLoading(false);
    }
  };

  const resetAddModal = () => {
    setShowAddModal(false);
    setAddResult(null);
    setFormError('');
    setFormData({
      name: '',
      hostname: '',
      type: 'linux',
      group: 'default',
      locationName: '',
      lat: '',
      lng: '',
      notes: ''
    });
  };

  const copyToClipboard = async (text: string, setter: (v: boolean) => void) => {
    try {
      await navigator.clipboard.writeText(text);
      setter(true);
      setTimeout(() => setter(false), 2000);
    } catch {
      // Fallback
      const ta = document.createElement('textarea');
      ta.value = text;
      document.body.appendChild(ta);
      ta.select();
      document.execCommand('copy');
      document.body.removeChild(ta);
      setter(true);
      setTimeout(() => setter(false), 2000);
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
        <button
          onClick={() => setShowAddModal(true)}
          className="flex items-center px-4 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 text-white"
        >
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
            
            <p className="text-gray-400 text-sm mb-2">{agent.hostname}</p>
            
            {agent.location && agent.location.lat !== 0 && (
              <p className="text-gray-500 text-xs mb-4 flex items-center">
                <MapPin className="w-3 h-3 mr-1" />
                {agent.location.name || `${agent.location.lat}, ${agent.location.lng}`}
              </p>
            )}
            
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

      {/* =================== Add Agent Modal =================== */}
      {showAddModal && (
        <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
          <div className="bg-gray-800 rounded-xl border border-gray-700 w-full max-w-lg max-h-[90vh] overflow-y-auto">
            
            {/* Modal Header */}
            <div className="flex items-center justify-between p-6 border-b border-gray-700">
              <h2 className="text-xl font-bold text-white">Add New Agent</h2>
              <button onClick={resetAddModal} className="text-gray-400 hover:text-white">
                <X className="w-6 h-6" />
              </button>
            </div>

            {/* Modal Body */}
            <div className="p-6">
              {!addResult ? (
                /* ---- Form ---- */
                <div className="space-y-4">
                  {formError && (
                    <div className="bg-red-500/20 border border-red-500 rounded-lg p-3 text-red-300 text-sm">
                      {formError}
                    </div>
                  )}

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Agent Name *</label>
                    <input
                      type="text"
                      placeholder="e.g. Web Server 01"
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Hostname / IP *</label>
                    <input
                      type="text"
                      placeholder="e.g. web01.example.com or 192.168.1.100"
                      value={formData.hostname}
                      onChange={(e) => setFormData({ ...formData, hostname: e.target.value })}
                      className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">OS Type</label>
                      <select
                        value={formData.type}
                        onChange={(e) => setFormData({ ...formData, type: e.target.value as any })}
                        className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                      >
                        <option value="linux">Linux</option>
                        <option value="windows">Windows</option>
                        <option value="macos">macOS</option>
                      </select>
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Group</label>
                      <input
                        type="text"
                        placeholder="default"
                        value={formData.group}
                        onChange={(e) => setFormData({ ...formData, group: e.target.value })}
                        className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Location Name</label>
                    <input
                      type="text"
                      placeholder="e.g. Jakarta DC, Singapore Cloud"
                      value={formData.locationName}
                      onChange={(e) => setFormData({ ...formData, locationName: e.target.value })}
                      className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                  </div>

                  <div className="grid grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Latitude</label>
                      <input
                        type="number"
                        step="any"
                        placeholder="-6.2088"
                        value={formData.lat}
                        onChange={(e) => setFormData({ ...formData, lat: e.target.value })}
                        className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                    <div>
                      <label className="block text-sm font-medium text-gray-300 mb-1">Longitude</label>
                      <input
                        type="number"
                        step="any"
                        placeholder="106.8456"
                        value={formData.lng}
                        onChange={(e) => setFormData({ ...formData, lng: e.target.value })}
                        className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
                      />
                    </div>
                  </div>

                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Notes</label>
                    <textarea
                      placeholder="Optional notes about this agent..."
                      value={formData.notes}
                      onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                      rows={3}
                      className="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                    />
                  </div>
                </div>
              ) : (
                /* ---- Success / Install Instructions ---- */
                <div className="space-y-4">
                  <div className="bg-green-500/20 border border-green-500 rounded-lg p-4 text-green-300 text-sm">
                    ✅ Agent <strong>{addResult.agent.name}</strong> created successfully!
                  </div>

                  {/* Server Key */}
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">Server Key</label>
                    <div className="flex items-center space-x-2">
                      <input
                        type="text"
                        readOnly
                        value={addResult.serverKey}
                        className="flex-1 px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-green-400 text-sm font-mono"
                      />
                      <button
                        onClick={() => copyToClipboard(addResult.serverKey, setCopiedKey)}
                        className="px-3 py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-300 hover:text-white hover:border-gray-500"
                      >
                        {copiedKey ? <Check className="w-4 h-4 text-green-400" /> : <Copy className="w-4 h-4" />}
                      </button>
                    </div>
                  </div>

                  {/* Install Command */}
                  <div>
                    <label className="block text-sm font-medium text-gray-300 mb-1">
                      <Terminal className="w-4 h-4 inline mr-1" />
                      Install Command ({addResult.agent.type})
                    </label>
                    <div className="flex items-stretch space-x-2">
                      <textarea
                        readOnly
                        value={addResult.installCommand}
                        rows={3}
                        className="flex-1 px-4 py-2 bg-gray-900 border border-gray-600 rounded-lg text-green-400 text-xs font-mono resize-none"
                      />
                      <button
                        onClick={() => copyToClipboard(addResult.installCommand, setCopiedCmd)}
                        className="px-3 self-start py-2 bg-gray-700 border border-gray-600 rounded-lg text-gray-300 hover:text-white hover:border-gray-500"
                      >
                        {copiedCmd ? <Check className="w-4 h-4 text-green-400" /> : <Copy className="w-4 h-4" />}
                      </button>
                    </div>
                    <p className="mt-2 text-xs text-gray-500">
                      Run this command on the target server to install and connect the agent.
                    </p>
                  </div>
                </div>
              )}
            </div>

            {/* Modal Footer */}
            <div className="flex items-center justify-end space-x-3 p-6 border-t border-gray-700">
              <button
                onClick={resetAddModal}
                className="px-4 py-2 text-gray-400 hover:text-white"
              >
                {addResult ? 'Close' : 'Cancel'}
              </button>
              {!addResult && (
                <button
                  onClick={handleCreateAgent}
                  disabled={addLoading}
                  className="px-6 py-2 bg-blue-600 rounded-lg hover:bg-blue-700 text-white disabled:opacity-50"
                >
                  {addLoading ? 'Creating...' : 'Create Agent'}
                </button>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
