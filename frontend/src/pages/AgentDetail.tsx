import { useEffect, useState, useRef } from 'react';
import { useParams } from 'react-router-dom';
import { 
  Server, 
  Cpu, 
  HardDrive, 
  Wifi, 
  Clock,
  Terminal,
  Send,
  ArrowLeft
} from 'lucide-react';
import { 
  LineChart, 
  Line, 
  XAxis, 
  YAxis, 
  CartesianGrid, 
  Tooltip, 
  ResponsiveContainer 
} from 'recharts';
import { useWebSocket } from '../contexts/WebSocketContext';
import axios from 'axios';

interface Agent {
  _id: string;
  name: string;
  hostname: string;
  status: string;
  type: string;
  os: {
    name: string;
    version: string;
    arch: string;
  };
  metrics: {
    cpu: {
      usage: number;
      cores: number;
      model: string;
    };
    memory: {
      total: number;
      used: number;
      free: number;
      usage: number;
    };
    disk: Array<{
      mountPoint: string;
      total: number;
      used: number;
      usage: number;
    }>;
    network: Array<{
      interface: string;
      ip: string;
      rxBytes: number;
      txBytes: number;
    }>;
    load: {
      load1: number;
      load5: number;
      load15: number;
    };
    uptime: number;
  };
}

interface TerminalLine {
  type: 'command' | 'output' | 'error';
  content: string;
}

export function AgentDetail() {
  const { id } = useParams<{ id: string }>();
  const { socket } = useWebSocket();
  const [agent, setAgent] = useState<Agent | null>(null);
  const [metricsHistory, setMetricsHistory] = useState<any[]>([]);
  const [terminalLines, setTerminalLines] = useState<TerminalLine[]>([]);
  const [command, setCommand] = useState('');
  const [loading, setLoading] = useState(true);
  const terminalRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (id) {
      fetchAgent();
      fetchMetricsHistory();
    }
  }, [id]);

  useEffect(() => {
    if (socket && id) {
      socket.on('metrics:update', (data) => {
        if (data.agentId === id) {
          setAgent(prev => prev ? { ...prev, metrics: data.metrics } : null);
        }
      });

      socket.on('command:result', (data) => {
        if (data.agentId === id) {
          setTerminalLines(prev => [
            ...prev,
            { type: 'output', content: data.result.output || 'Command executed' }
          ]);
        }
      });

      return () => {
        socket.off('metrics:update');
        socket.off('command:result');
      };
    }
  }, [socket, id]);

  useEffect(() => {
    if (terminalRef.current) {
      terminalRef.current.scrollTop = terminalRef.current.scrollHeight;
    }
  }, [terminalLines]);

  const fetchAgent = async () => {
    try {
      const response = await axios.get(`/api/agents/${id}`);
      setAgent(response.data);
    } catch (error) {
      console.error('Failed to fetch agent:', error);
    } finally {
      setLoading(false);
    }
  };

  const fetchMetricsHistory = async () => {
    try {
      const response = await axios.get(`/api/agents/${id}/metrics?period=24h`);
      setMetricsHistory(response.data);
    } catch (error) {
      console.error('Failed to fetch metrics history:', error);
    }
  };

  const executeCommand = async () => {
    if (!command.trim() || !socket || !id) return;

    setTerminalLines(prev => [
      ...prev,
      { type: 'command', content: `$ ${command}` }
    ]);

    socket.emit('execute:command', {
      agentId: id,
      command: command,
      args: []
    });

    setCommand('');
  };

  const formatBytes = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  };

  const formatUptime = (seconds: number) => {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${days}d ${hours}h ${minutes}m`;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-96">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500" />
      </div>
    );
  }

  if (!agent) {
    return (
      <div className="text-center py-12">
        <p className="text-gray-400">Agent not found</p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center">
          <a href="/agents" className="mr-4 text-gray-400 hover:text-white">
            <ArrowLeft className="w-5 h-5" />
          </a>
          <div>
            <h1 className="text-2xl font-bold text-white">{agent.name}</h1>
            <p className="text-gray-400">{agent.hostname}</p>
          </div>
        </div>
        <div className="flex items-center space-x-4">
          <span className={`px-3 py-1 rounded-full text-sm font-medium ${
            agent.status === 'online' ? 'bg-green-500/20 text-green-400' :
            agent.status === 'warning' ? 'bg-yellow-500/20 text-yellow-400' :
            agent.status === 'critical' ? 'bg-red-500/20 text-red-400' :
            'bg-gray-500/20 text-gray-400'
          }`}>
            {agent.status}
          </span>
          <span className="text-gray-400 capitalize">{agent.type}</span>
        </div>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <div className="flex items-center text-gray-400 mb-2">
            <Cpu className="w-4 h-4 mr-2" />
            <span className="text-sm">CPU</span>
          </div>
          <p className="text-2xl font-bold text-white">
            {agent.metrics.cpu.usage.toFixed(1)}%
          </p>
          <p className="text-xs text-gray-500">{agent.metrics.cpu.cores} cores</p>
        </div>

        <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <div className="flex items-center text-gray-400 mb-2">
            <HardDrive className="w-4 h-4 mr-2" />
            <span className="text-sm">Memory</span>
          </div>
          <p className="text-2xl font-bold text-white">
            {agent.metrics.memory.usage.toFixed(1)}%
          </p>
          <p className="text-xs text-gray-500">
            {formatBytes(agent.metrics.memory.used)} / {formatBytes(agent.metrics.memory.total)}
          </p>
        </div>

        <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <div className="flex items-center text-gray-400 mb-2">
            <Wifi className="w-4 h-4 mr-2" />
            <span className="text-sm">Network</span>
          </div>
          <p className="text-2xl font-bold text-white">
            {agent.metrics.network.length}
          </p>
          <p className="text-xs text-gray-500">interfaces</p>
        </div>

        <div className="bg-gray-800 rounded-lg p-4 border border-gray-700">
          <div className="flex items-center text-gray-400 mb-2">
            <Clock className="w-4 h-4 mr-2" />
            <span className="text-sm">Uptime</span>
          </div>
          <p className="text-2xl font-bold text-white">
            {formatUptime(agent.metrics.uptime)}
          </p>
        </div>
      </div>

      {/* Charts */}
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <h2 className="text-lg font-semibold text-white mb-4">CPU Usage (24h)</h2>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={metricsHistory}>
                <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
                <XAxis dataKey="timestamp" stroke="#9ca3af" />
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
                  dataKey="cpu.usage"
                  stroke="#3b82f6"
                  strokeWidth={2}
                  dot={false}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>

        <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
          <h2 className="text-lg font-semibold text-white mb-4">Memory Usage (24h)</h2>
          <div className="h-64">
            <ResponsiveContainer width="100%" height="100%">
              <LineChart data={metricsHistory}>
                <CartesianGrid strokeDasharray="3 3" stroke="#374151" />
                <XAxis dataKey="timestamp" stroke="#9ca3af" />
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
                  dataKey="memory.usage"
                  stroke="#8b5cf6"
                  strokeWidth={2}
                  dot={false}
                />
              </LineChart>
            </ResponsiveContainer>
          </div>
        </div>
      </div>

      {/* Disk Usage */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 className="text-lg font-semibold text-white mb-4">Disk Usage</h2>
        <div className="space-y-4">
          {agent.metrics.disk.map((disk, index) => (
            <div key={index}>
              <div className="flex justify-between mb-1">
                <span className="text-gray-400">{disk.mountPoint}</span>
                <span className="text-gray-400">
                  {formatBytes(disk.used)} / {formatBytes(disk.total)}
                </span>
              </div>
              <div className="w-full bg-gray-700 rounded-full h-2">
                <div
                  className={`h-2 rounded-full ${
                    disk.usage > 90 ? 'bg-red-500' :
                    disk.usage > 70 ? 'bg-yellow-500' : 'bg-green-500'
                  }`}
                  style={{ width: `${disk.usage}%` }}
                />
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Remote Terminal */}
      <div className="bg-gray-800 rounded-lg border border-gray-700">
        <div className="flex items-center justify-between px-4 py-3 border-b border-gray-700">
          <div className="flex items-center">
            <Terminal className="w-5 h-5 mr-2 text-green-500" />
            <h2 className="text-lg font-semibold text-white">Remote Terminal</h2>
          </div>
          <span className="text-sm text-gray-400">WebSocket Connected</span>
        </div>
        
        <div
          ref={terminalRef}
          className="h-64 overflow-y-auto p-4 font-mono text-sm bg-gray-900"
        >
          {terminalLines.map((line, index) => (
            <div key={index} className={
              line.type === 'command' ? 'text-green-400' :
              line.type === 'error' ? 'text-red-400' : 'text-gray-300'
            }>
              {line.content}
            </div>
          ))}
        </div>
        
        <div className="flex items-center p-4 border-t border-gray-700">
          <span className="text-green-400 mr-2">$</span>
          <input
            type="text"
            value={command}
            onChange={(e) => setCommand(e.target.value)}
            onKeyPress={(e) => e.key === 'Enter' && executeCommand()}
            placeholder="Enter command..."
            className="flex-1 bg-transparent text-white focus:outline-none"
          />
          <button
            onClick={executeCommand}
            className="ml-4 p-2 bg-blue-600 rounded hover:bg-blue-700"
          >
            <Send className="w-4 h-4 text-white" />
          </button>
        </div>
      </div>
    </div>
  );
}
