import { Server } from 'socket.io';
import { Agent, IAgent } from '../models/Agent';
import { MetricsHistory } from '../models/MetricsHistory';
import { Alert, IAlert } from '../models/Alert';
import { getNotificationService } from './notifications';

export class AgentManager {
  private io: Server;
  private agentConnections: Map<string, any> = new Map();
  private notificationService;

  constructor(io: Server) {
    this.io = io;
    this.notificationService = getNotificationService();
    this.startStatusChecker();
  }

  async processAgentData(serverKey: string, data: any): Promise<void> {
    try {
      // Find or create agent
      let agent = await Agent.findOne({ serverKey });
      
      if (!agent) {
        // Auto-register new agent
        agent = await Agent.create({
          serverKey,
          name: data.hostname || 'Unknown Agent',
          hostname: data.hostname,
          type: data.os || 'linux',
          os: {
            name: data.os_info?.name || data.os,
            version: data.os_info?.version || '',
            arch: data.arch
          },
          location: data.location || { name: '', lat: 0, lng: 0 },
          status: 'online'
        });
        console.log(`✨ New agent registered: ${agent.name}`);
      }

      // Update agent metrics
      agent.status = 'online';
      agent.lastSeen = new Date();
      agent.hostname = data.hostname || agent.hostname;

      // Update CPU metrics
      if (data.cpu) {
        agent.metrics.cpu = {
          usage: data.cpu.usage || 0,
          cores: data.cpu.cores || 0,
          model: data.cpu.model || '',
          temperature: data.cpu.temperature
        };
      }

      // Update Memory metrics
      if (data.memory) {
        const memUsed = data.memory.used || 0;
        const memTotal = data.memory.total || 1;
        agent.metrics.memory = {
          total: memTotal,
          used: memUsed,
          free: data.memory.free || 0,
          usage: (memUsed / memTotal) * 100
        };
      }

      // Update Disk metrics
      if (data.disk && Array.isArray(data.disk)) {
        agent.metrics.disk = data.disk.map((d: any) => ({
          mountPoint: d.mount_point || d.mountPoint,
          total: d.total || 0,
          used: d.used || 0,
          usage: d.usage || 0
        }));
      }

      // Update Network metrics
      if (data.network && Array.isArray(data.network)) {
        agent.metrics.network = data.network.map((n: any) => ({
          interface: n.interface,
          ip: n.ip_address || n.ip,
          rxBytes: n.rx_bytes || n.rxBytes || 0,
          txBytes: n.tx_bytes || n.txBytes || 0
        }));
      }

      // Update Load metrics
      if (data.load) {
        agent.metrics.load = {
          load1: data.load.load1 || 0,
          load5: data.load.load5 || 0,
          load15: data.load.load15 || 0
        };
      }

      agent.metrics.uptime = data.uptime || 0;

      await agent.save();

      // Store historical metrics
      await MetricsHistory.create({
        agentId: agent._id,
        timestamp: new Date(),
        cpu: {
          usage: agent.metrics.cpu.usage,
          user: data.cpu?.user || 0,
          system: data.cpu?.system || 0,
          idle: data.cpu?.idle || 0
        },
        memory: {
          total: agent.metrics.memory.total,
          used: agent.metrics.memory.used,
          usage: agent.metrics.memory.usage
        },
        disk: agent.metrics.disk,
        network: agent.metrics.network,
        load: agent.metrics.load
      });

      // Check for alerts
      await this.checkAlerts(agent);

      // Broadcast update to connected dashboards
      this.io.emit('agent:update', {
        agentId: agent._id,
        status: agent.status,
        metrics: agent.metrics,
        lastSeen: agent.lastSeen
      });

    } catch (error) {
      console.error('Error processing agent data:', error);
    }
  }

  async checkAlerts(agent: IAgent): Promise<void> {
    // Check CPU usage
    if (agent.metrics.cpu.usage > 90) {
      await this.triggerAlert(agent, 'cpu', 'critical', 
        `CPU usage is critical: ${agent.metrics.cpu.usage.toFixed(1)}%`,
        agent.metrics.cpu.usage,
        90);
    } else if (agent.metrics.cpu.usage > 80) {
      await this.triggerAlert(agent, 'cpu', 'warning',
        `CPU usage is high: ${agent.metrics.cpu.usage.toFixed(1)}%`,
        agent.metrics.cpu.usage,
        80);
    }

    // Check Memory usage
    if (agent.metrics.memory.usage > 95) {
      await this.triggerAlert(agent, 'memory', 'critical',
        `Memory usage is critical: ${agent.metrics.memory.usage.toFixed(1)}%`,
        agent.metrics.memory.usage,
        95);
    } else if (agent.metrics.memory.usage > 85) {
      await this.triggerAlert(agent, 'memory', 'warning',
        `Memory usage is high: ${agent.metrics.memory.usage.toFixed(1)}%`,
        agent.metrics.memory.usage,
        85);
    }

    // Check Disk usage
    for (const disk of agent.metrics.disk) {
      if (disk.usage > 95) {
        await this.triggerAlert(agent, 'disk', 'critical',
          `Disk ${disk.mountPoint} usage is critical: ${disk.usage.toFixed(1)}%`,
          disk.usage,
          95);
      } else if (disk.usage > 85) {
        await this.triggerAlert(agent, 'disk', 'warning',
          `Disk ${disk.mountPoint} usage is high: ${disk.usage.toFixed(1)}%`,
          disk.usage,
          85);
      }
    }

    // Check for offline agents (handled by status checker)
  }

  async triggerAlert(
    agent: IAgent, 
    type: string, 
    severity: string, 
    message: string,
    value?: number,
    threshold?: number
  ): Promise<void> {
    // Check if similar alert already exists
    const existingAlert = await Alert.findOne({
      agentId: agent._id,
      type,
      status: 'active'
    });

    if (existingAlert) {
      // Update existing alert
      existingAlert.occurrences += 1;
      existingAlert.lastTriggered = new Date();
      await existingAlert.save();
    } else {
      // Create new alert
      const alert = await Alert.create({
        agentId: agent._id,
        type,
        severity,
        condition: { metric: type, operator: '>', value: threshold || 0 },
        message,
        status: 'active',
        triggeredAt: new Date(),
        lastTriggered: new Date()
      });

      // Broadcast alert via WebSocket
      this.io.emit('alert:new', {
        alertId: alert._id,
        agentId: agent._id,
        agentName: agent.name,
        type,
        severity,
        message,
        timestamp: alert.triggeredAt
      });

      // Send notifications
      try {
        await this.notificationService.sendAlert(alert, agent);
      } catch (error) {
        console.error('Failed to send notification:', error);
      }
    }
  }

  async registerConnection(agentId: string, socket: any): Promise<void> {
    this.agentConnections.set(agentId, socket);
    
    // Update agent status
    await Agent.findByIdAndUpdate(agentId, { status: 'online' });
    
    // Notify dashboards
    this.io.emit('agent:status', { agentId, status: 'online' });
  }

  async unregisterConnection(agentId: string): Promise<void> {
    this.agentConnections.delete(agentId);
    
    // Update agent status after timeout
    setTimeout(async () => {
      if (!this.agentConnections.has(agentId)) {
        const agent = await Agent.findByIdAndUpdate(
          agentId, 
          { status: 'offline' },
          { new: true }
        );
        
        this.io.emit('agent:status', { agentId, status: 'offline' });
        
        // Check for offline alert
        if (agent) {
          const offlineAlert = await Alert.findOne({
            agentId,
            type: 'offline',
            status: 'active'
          });
          
          if (!offlineAlert) {
            const alert = await Alert.create({
              agentId,
              type: 'offline',
              severity: 'critical',
              condition: { metric: 'uptime', operator: '<', value: 300 },
              message: `Agent ${agent.name} is offline`,
              status: 'active',
              triggeredAt: new Date(),
              lastTriggered: new Date()
            });
            
            // Send offline notification
            try {
              await this.notificationService.sendAlert(alert, agent);
            } catch (error) {
              console.error('Failed to send offline notification:', error);
            }
          }
        }
      }
    }, 60000); // 1 minute timeout
  }

  async sendCommand(agentId: string, command: string, args: string[]): Promise<any> {
    const socket = this.agentConnections.get(agentId);
    if (!socket) {
      throw new Error('Agent not connected');
    }

    return new Promise((resolve, reject) => {
      const commandId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
      
      socket.emit('execute', {
        id: commandId,
        command,
        args,
        timeout: 30
      });

      socket.once('command_result', (result: any) => {
        if (result.id === commandId) {
          resolve(result);
        }
      });

      setTimeout(() => {
        reject(new Error('Command timeout'));
      }, 30000);
    });
  }

  private startStatusChecker(): void {
    // Check agent status every 5 minutes
    setInterval(async () => {
      const fiveMinutesAgo = new Date(Date.now() - 5 * 60 * 1000);
      
      await Agent.updateMany(
        { lastSeen: { $lt: fiveMinutesAgo }, status: 'online' },
        { status: 'offline' }
      );
    }, 5 * 60 * 1000);
  }
}
