import { Server, Socket } from 'socket.io';
import { AgentManager } from '../services/agentManager';
import { Agent } from '../models/Agent';

export class WebSocketHandler {
  private io: Server;
  private agentManager: AgentManager;

  constructor(io: Server, agentManager: AgentManager) {
    this.io = io;
    this.agentManager = agentManager;
    this.setupSocketHandlers();
  }

  private setupSocketHandlers(): void {
    this.io.on('connection', (socket: Socket) => {
      console.log(`📡 New client connected: ${socket.id}`);

      // Handle agent registration
      socket.on('agent:register', async (data: { serverKey: string }) => {
        try {
          const agent = await Agent.findOne({ serverKey: data.serverKey });
          if (agent) {
            await this.agentManager.registerConnection(agent._id.toString(), socket);
            socket.join(`agent:${agent._id}`);
            socket.emit('agent:registered', { 
              agentId: agent._id,
              status: 'success' 
            });
            console.log(`✅ Agent registered: ${agent.name}`);
          } else {
            socket.emit('error', { message: 'Agent not found' });
          }
        } catch (error) {
          socket.emit('error', { message: 'Registration failed' });
        }
      });

      // Handle agent data updates
      socket.on('agent:metrics', async (data: { serverKey: string; metrics: any }) => {
        try {
          await this.agentManager.processAgentData(data.serverKey, data.metrics);
        } catch (error) {
          console.error('Error processing metrics:', error);
        }
      });

      // Handle command execution request from dashboard
      socket.on('execute:command', async (data: { 
        agentId: string; 
        command: string; 
        args: string[] 
      }) => {
        try {
          const result = await this.agentManager.sendCommand(
            data.agentId, 
            data.command, 
            data.args
          );
          socket.emit('command:result', {
            agentId: data.agentId,
            command: data.command,
            result
          });
        } catch (error: any) {
          socket.emit('command:error', {
            agentId: data.agentId,
            command: data.command,
            error: error.message
          });
        }
      });

      // Handle agent status check
      socket.on('agent:ping', async (data: { serverKey: string }) => {
        const agent = await Agent.findOne({ serverKey: data.serverKey });
        if (agent) {
          agent.lastSeen = new Date();
          agent.status = 'online';
          await agent.save();
          socket.emit('agent:pong', { status: 'ok' });
        }
      });

      // Handle disconnect
      socket.on('disconnect', async () => {
        console.log(`📡 Client disconnected: ${socket.id}`);
        
        // Find and update agent status
        const agent = await Agent.findOne({ 
          // This is simplified - in production, track socket-to-agent mapping
        });
        
        if (agent) {
          await this.agentManager.unregisterConnection(agent._id.toString());
        }
      });
    });
  }

  // Send command to specific agent
  async sendCommandToAgent(agentId: string, command: string, args: string[]): Promise<any> {
    const agent = await Agent.findById(agentId);
    if (!agent) {
      throw new Error('Agent not found');
    }

    return this.agentManager.sendCommand(agentId, command, args);
  }

  // Broadcast alert to all connected clients
  broadcastAlert(alert: any): void {
    this.io.emit('alert:new', alert);
  }

  // Send metrics update to dashboard
  broadcastMetrics(agentId: string, metrics: any): void {
    this.io.emit('metrics:update', { agentId, metrics });
  }
}
