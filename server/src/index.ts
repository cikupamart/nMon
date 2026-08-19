import express from 'express';
import { createServer } from 'http';
import { Server } from 'socket.io';
import cors from 'cors';
import helmet from 'helmet';
import compression from 'compression';
import morgan from 'morgan';
import dotenv from 'dotenv';

import { connectDatabase } from './database/connection';
import { agentRoutes } from './routes/agents';
import { alertRoutes } from './routes/alerts';
import { authRoutes } from './routes/auth';
import { notificationRoutes } from './routes/notifications';
import { WebSocketHandler } from './websocket/handler';
import { AgentManager } from './services/agentManager';
import { getNotificationService } from './services/notifications';

// Load environment variables
dotenv.config();

const app = express();
const httpServer = createServer(app);
const io = new Server(httpServer, {
  cors: {
    origin: process.env.CORS_ORIGIN || '*',
    methods: ['GET', 'POST', 'PUT', 'DELETE']
  }
});

// Middleware
app.use(helmet());
app.use(compression());
app.use(morgan('combined'));
app.use(cors());
app.use(express.json());

// Connect to database
connectDatabase();

// Initialize services
const agentManager = new AgentManager(io);
const wsHandler = new WebSocketHandler(io, agentManager);
const notificationService = getNotificationService();

// Routes
app.use('/api/auth', authRoutes);
app.use('/api/agents', agentRoutes);
app.use('/api/alerts', alertRoutes);
app.use('/api/notifications', notificationRoutes);

// Health check
app.get('/health', (req, res) => {
  res.json({ 
    status: 'healthy', 
    timestamp: new Date().toISOString(),
    version: '2.0.0',
    uptime: process.uptime()
  });
});

// Agent data ingestion endpoint
app.post('/api/v1/agent/data', async (req, res) => {
  try {
    const serverKey = req.headers['x-server-key'] as string;
    const data = req.body;

    if (!serverKey) {
      return res.status(401).json({ error: 'Server key required' });
    }

    // Process agent data
    await agentManager.processAgentData(serverKey, data);

    res.json({ status: 'ok' });
  } catch (error) {
    console.error('Agent data error:', error);
    res.status(500).json({ error: 'Internal server error' });
  }
});

// API Info endpoint
app.get('/api', (req, res) => {
  res.json({
    name: 'nMon API',
    version: '2.0.0',
    endpoints: {
      agents: '/api/agents',
      alerts: '/api/alerts',
      notifications: '/api/notifications',
      health: '/health'
    }
  });
});

// Start server
const PORT = process.env.PORT || 3000;

httpServer.listen(PORT, () => {
  console.log('🚀 nMon Server v2.0.0');
  console.log(`📡 API running on port ${PORT}`);
  console.log(`🔌 WebSocket server ready`);
  console.log(`🔔 Notification channels: ${notificationService.getChannels().map(c => c.name).join(', ') || 'none'}`);
  console.log(`🔗 API: http://localhost:${PORT}/api`);
});

// Schedule daily summary (at 8 AM)
const scheduleDailySummary = () => {
  const now = new Date();
  const target = new Date();
  target.setHours(8, 0, 0, 0);
  
  if (target <= now) {
    target.setDate(target.getDate() + 1);
  }
  
  const delay = target.getTime() - now.getTime();
  
  setTimeout(async () => {
    console.log('📊 Sending daily summary...');
    await notificationService.sendDailySummary();
    scheduleDailySummary(); // Schedule next one
  }, delay);
};

scheduleDailySummary();

export { app, httpServer, io };
