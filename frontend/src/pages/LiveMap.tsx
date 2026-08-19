import { useEffect, useState } from 'react';
import { MapContainer, TileLayer, Marker, Popup, useMap } from 'react-leaflet';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import { useWebSocket } from '../contexts/WebSocketContext';
import axios from 'axios';
import { Server, Cpu, HardDrive, Wifi, Clock } from 'lucide-react';

interface AgentLocation {
  _id: string;
  name: string;
  hostname: string;
  status: 'online' | 'offline' | 'warning' | 'critical';
  type: 'linux' | 'windows' | 'macos';
  location: {
    name: string;
    lat: number;
    lng: number;
  };
  metrics: {
    cpu: { usage: number };
    memory: { usage: number };
    uptime: number;
  };
  lastSeen: string;
}

// Custom marker icons
const createIcon = (status: string) => {
  const colors = {
    online: '#22c55e',
    warning: '#eab308',
    critical: '#ef4444',
    offline: '#6b7280'
  };

  return L.divIcon({
    className: 'custom-marker',
    html: `
      <div style="
        width: 24px;
        height: 24px;
        background-color: ${colors[status as keyof typeof colors]};
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 2px 4px rgba(0,0,0,0.3);
      "></div>
    `,
    iconSize: [24, 24],
    iconAnchor: [12, 12]
  });
};

function MapUpdater({ agents }: { agents: AgentLocation[] }) {
  const map = useMap();
  
  useEffect(() => {
    if (agents.length > 0) {
      const bounds = L.latLngBounds(
        agents.map(a => [a.location.lat, a.location.lng] as [number, number])
      );
      map.fitBounds(bounds, { padding: [50, 50] });
    }
  }, [agents, map]);
  
  return null;
}

function AgentPopup({ agent }: { agent: AgentLocation }) {
  const formatUptime = (seconds: number) => {
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    return `${days}d ${hours}h`;
  };

  return (
    <div className="p-2 min-w-[200px]">
      <div className="flex items-center mb-2">
        <Server className="w-4 h-4 mr-2 text-blue-500" />
        <h3 className="font-bold text-gray-900">{agent.name}</h3>
      </div>
      
      <div className="space-y-2 text-sm">
        <div className="flex items-center text-gray-600">
          <span className="font-medium">Hostname:</span>
          <span className="ml-2">{agent.hostname}</span>
        </div>
        
        <div className="flex items-center text-gray-600">
          <span className="font-medium">Type:</span>
          <span className="ml-2 capitalize">{agent.type}</span>
        </div>
        
        <div className="flex items-center text-gray-600">
          <span className="font-medium">Status:</span>
          <span className={`ml-2 px-2 py-0.5 rounded text-xs text-white ${
            agent.status === 'online' ? 'bg-green-500' :
            agent.status === 'warning' ? 'bg-yellow-500' :
            agent.status === 'critical' ? 'bg-red-500' : 'bg-gray-500'
          }`}>
            {agent.status}
          </span>
        </div>
        
        <div className="border-t pt-2 mt-2">
          <div className="flex items-center text-gray-600">
            <Cpu className="w-4 h-4 mr-2" />
            <span>CPU: {agent.metrics.cpu.usage.toFixed(1)}%</span>
          </div>
          
          <div className="flex items-center text-gray-600">
            <HardDrive className="w-4 h-4 mr-2" />
            <span>Memory: {agent.metrics.memory.usage.toFixed(1)}%</span>
          </div>
          
          <div className="flex items-center text-gray-600">
            <Clock className="w-4 h-4 mr-2" />
            <span>Uptime: {formatUptime(agent.metrics.uptime)}</span>
          </div>
        </div>
      </div>
      
      <a
        href={`/agents/${agent._id}`}
        className="mt-3 block text-center text-sm text-blue-600 hover:text-blue-800"
      >
        View Details →
      </a>
    </div>
  );
}

export function LiveMap() {
  const { agents: wsAgents } = useWebSocket();
  const [agents, setAgents] = useState<AgentLocation[]>([]);
  const [loading, setLoading] = useState(true);

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
      const response = await axios.get('/api/agents/map/all');
      setAgents(response.data);
    } catch (error) {
      console.error('Failed to fetch agents:', error);
    } finally {
      setLoading(false);
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
        <h1 className="text-2xl font-bold text-white">Live Map</h1>
        <div className="flex items-center space-x-4">
          <div className="flex items-center">
            <div className="w-3 h-3 rounded-full bg-green-500 mr-2" />
            <span className="text-sm text-gray-400">Online</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 rounded-full bg-yellow-500 mr-2" />
            <span className="text-sm text-gray-400">Warning</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 rounded-full bg-red-500 mr-2" />
            <span className="text-sm text-gray-400">Critical</span>
          </div>
          <div className="flex items-center">
            <div className="w-3 h-3 rounded-full bg-gray-500 mr-2" />
            <span className="text-sm text-gray-400">Offline</span>
          </div>
        </div>
      </div>

      <div className="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
        <MapContainer
          center={[0, 0]}
          zoom={2}
          className="h-[600px] w-full"
          style={{ backgroundColor: '#1f2937' }}
        >
          <TileLayer
            attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          />
          <MapUpdater agents={agents} />
          
          {agents.map((agent) => (
            <Marker
              key={agent._id}
              position={[agent.location.lat, agent.location.lng]}
              icon={createIcon(agent.status)}
            >
              <Popup>
                <AgentPopup agent={agent} />
              </Popup>
            </Marker>
          ))}
        </MapContainer>
      </div>

      {/* Agent List Below Map */}
      <div className="bg-gray-800 rounded-lg p-6 border border-gray-700">
        <h2 className="text-lg font-semibold text-white mb-4">
          Agents on Map ({agents.length})
        </h2>
        <div className="overflow-x-auto">
          <table className="w-full">
            <thead>
              <tr className="text-left text-gray-400 border-b border-gray-700">
                <th className="pb-3">Name</th>
                <th className="pb-3">Location</th>
                <th className="pb-3">Status</th>
                <th className="pb-3">CPU</th>
                <th className="pb-3">Memory</th>
                <th className="pb-3">Last Seen</th>
              </tr>
            </thead>
            <tbody>
              {agents.map((agent) => (
                <tr
                  key={agent._id}
                  className="border-b border-gray-700 hover:bg-gray-700/50"
                >
                  <td className="py-4">
                    <div className="flex items-center">
                      <Server className="w-4 h-4 mr-2 text-gray-400" />
                      <div>
                        <p className="text-white font-medium">{agent.name}</p>
                        <p className="text-sm text-gray-400">{agent.hostname}</p>
                      </div>
                    </div>
                  </td>
                  <td className="py-4 text-gray-300">
                    {agent.location.name || `${agent.location.lat}, ${agent.location.lng}`}
                  </td>
                  <td className="py-4">
                    <span className={`px-2 py-1 rounded text-xs text-white ${
                      agent.status === 'online' ? 'bg-green-500' :
                      agent.status === 'warning' ? 'bg-yellow-500' :
                      agent.status === 'critical' ? 'bg-red-500' : 'bg-gray-500'
                    }`}>
                      {agent.status}
                    </span>
                  </td>
                  <td className="py-4 text-gray-300">
                    {agent.metrics.cpu.usage.toFixed(1)}%
                  </td>
                  <td className="py-4 text-gray-300">
                    {agent.metrics.memory.usage.toFixed(1)}%
                  </td>
                  <td className="py-4 text-gray-300">
                    {new Date(agent.lastSeen).toLocaleString()}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
