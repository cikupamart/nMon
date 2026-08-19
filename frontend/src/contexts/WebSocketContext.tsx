import { createContext, useContext, useEffect, useState, ReactNode } from 'react';
import { io, Socket } from 'socket.io-client';

interface WebSocketContextType {
  socket: Socket | null;
  isConnected: boolean;
  agents: Map<string, any>;
  alerts: any[];
}

const WebSocketContext = createContext<WebSocketContextType>({
  socket: null,
  isConnected: false,
  agents: new Map(),
  alerts: []
});

export function WebSocketProvider({ children }: { children: ReactNode }) {
  const [socket, setSocket] = useState<Socket | null>(null);
  const [isConnected, setIsConnected] = useState(false);
  const [agents, setAgents] = useState<Map<string, any>>(new Map());
  const [alerts, setAlerts] = useState<any[]>([]);

  useEffect(() => {
    const socketUrl = import.meta.env.VITE_WS_URL || 'http://localhost:3000';
    const newSocket = io(socketUrl, {
      transports: ['websocket', 'polling']
    });

    newSocket.on('connect', () => {
      console.log('🔗 Connected to server');
      setIsConnected(true);
    });

    newSocket.on('disconnect', () => {
      console.log('❌ Disconnected from server');
      setIsConnected(false);
    });

    newSocket.on('agent:update', (data) => {
      setAgents(prev => {
        const newMap = new Map(prev);
        newMap.set(data.agentId, {
          ...newMap.get(data.agentId),
          ...data
        });
        return newMap;
      });
    });

    newSocket.on('alert:new', (alert) => {
      setAlerts(prev => [alert, ...prev].slice(0, 100));
    });

    setSocket(newSocket);

    return () => {
      newSocket.disconnect();
    };
  }, []);

  return (
    <WebSocketContext.Provider value={{ socket, isConnected, agents, alerts }}>
      {children}
    </WebSocketContext.Provider>
  );
}

export function useWebSocket() {
  const context = useContext(WebSocketContext);
  if (!context) {
    throw new Error('useWebSocket must be used within a WebSocketProvider');
  }
  return context;
}
