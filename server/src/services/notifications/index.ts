import { TelegramNotifier, createTelegramNotifier } from './telegram';
import { EmailNotifier, createEmailNotifier } from './email';
import { IAlert } from '../../models/Alert';
import { IAgent } from '../../models/Agent';

export interface NotificationChannel {
  name: string;
  enabled: boolean;
  send: (message: any) => Promise<boolean>;
}

export interface NotificationPreferences {
  email?: string[];
  telegram?: boolean;
  enabled: boolean;
}

export class NotificationService {
  private telegram: TelegramNotifier | null;
  private email: EmailNotifier | null;
  private channels: Map<string, NotificationChannel> = new Map();

  constructor() {
    this.telegram = createTelegramNotifier();
    this.email = createEmailNotifier();

    // Register channels
    if (this.telegram) {
      this.channels.set('telegram', {
        name: 'Telegram',
        enabled: true,
        send: (msg) => this.telegram!.sendMessage(msg)
      });
    }

    if (this.email) {
      this.channels.set('email', {
        name: 'Email',
        enabled: true,
        send: (msg) => this.email!.sendEmail(msg)
      });
    }

    console.log(`📢 Notification channels: ${Array.from(this.channels.keys()).join(', ') || 'none'}`);
  }

  async sendAlert(alert: IAlert, agent: IAgent, recipients?: string[]): Promise<void> {
    const agentName = agent.name || agent.hostname;
    const agentIp = agent.metrics?.network?.[0]?.ip;

    // Send via Telegram
    if (this.telegram && this.shouldNotify('telegram', agent)) {
      await this.telegram.sendAlert({
        severity: alert.severity as 'info' | 'warning' | 'critical',
        agentName,
        type: alert.type,
        message: alert.message,
        value: alert.condition?.value,
        threshold: alert.condition?.value
      });
    }

    // Send via Email
    if (this.email && recipients && recipients.length > 0) {
      await this.email.sendAlert({
        severity: alert.severity as 'info' | 'warning' | 'critical',
        agentName,
        agentIp,
        type: alert.type,
        message: alert.message,
        value: alert.condition?.value,
        threshold: alert.condition?.value,
        recipients
      });
    }
  }

  async sendIncident(
    type: 'opened' | 'resolved',
    alert: IAlert,
    agent: IAgent,
    duration?: string,
    recipients?: string[]
  ): Promise<void> {
    const agentName = agent.name || agent.hostname;

    // Send via Telegram
    if (this.telegram && this.shouldNotify('telegram', agent)) {
      await this.telegram.sendIncident({
        type,
        agentName,
        alertType: alert.type,
        severity: alert.severity,
        duration
      });
    }

    // Send via Email
    if (this.email && recipients && recipients.length > 0) {
      await this.email.sendIncident({
        type,
        agentName,
        alertType: alert.type,
        severity: alert.severity,
        duration,
        recipients
      });
    }
  }

  async sendDailySummary(): Promise<void> {
    // Dynamic import to avoid circular dependency
    const { Agent } = await import('../../models/Agent');
    const { Alert } = await import('../../models/Alert');
    
    const totalAgents = await Agent.countDocuments();
    const onlineAgents = await Agent.countDocuments({ status: 'online' });
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const alerts = await Alert.countDocuments({ 
      triggeredAt: { $gte: today } 
    });
    
    const incidents = await Alert.countDocuments({
      triggeredAt: { $gte: today },
      status: { $in: ['active', 'acknowledged'] }
    });

    // Send via Telegram
    if (this.telegram) {
      await this.telegram.sendDailySummary({
        totalAgents,
        onlineAgents,
        alerts,
        incidents
      });
    }

    // Send via Email to all configured recipients
    if (this.email) {
      const emailRecipients = this.getDailySummaryRecipients();
      if (emailRecipients.length > 0) {
        await this.email.sendEmail({
          to: emailRecipients,
          subject: `[nMon] Daily Summary - ${new Date().toLocaleDateString()}`,
          html: this.generateDailySummaryHTML({
            totalAgents,
            onlineAgents,
            alerts,
            incidents
          })
        });
      }
    }
  }

  private shouldNotify(channel: string, agent: IAgent): boolean {
    // Check agent-specific notification preferences
    const prefs = agent.get('notificationPreferences') as NotificationPreferences | null;
    if (prefs && prefs.enabled === false) {
      return false;
    }

    if (channel === 'telegram' && prefs?.telegram === false) {
      return false;
    }

    return true;
  }

  private getDailySummaryRecipients(): string[] {
    // In production, this would come from settings/database
    const emailEnv = process.env.DAILY_SUMMARY_EMAILS;
    if (emailEnv) {
      return emailEnv.split(',').map(e => e.trim());
    }
    return [];
  }

  private generateDailySummaryHTML(stats: {
    totalAgents: number;
    onlineAgents: number;
    alerts: number;
    incidents: number;
  }): string {
    return `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: #3b82f6; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
    .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
    .stat { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #e5e7eb; }
    .stat:last-child { border-bottom: none; }
    .stat-label { font-weight: bold; color: #374151; }
    .stat-value { font-size: 24px; font-weight: bold; color: #1f2937; }
    .footer { background: #f3f4f6; padding: 15px; border-radius: 0 0 8px 8px; font-size: 12px; color: #6b7280; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin:0;">📊 nMon Daily Summary</h2>
      <p style="margin:5px 0 0 0; opacity:0.9;">${new Date().toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</p>
    </div>
    <div class="content">
      <div class="stat">
        <span class="stat-label">Total Agents</span>
        <span class="stat-value">${stats.totalAgents}</span>
      </div>
      <div class="stat">
        <span class="stat-label">Online Agents</span>
        <span class="stat-value" style="color: #22c55e;">${stats.onlineAgents}</span>
      </div>
      <div class="stat">
        <span class="stat-label">Alerts Today</span>
        <span class="stat-value" style="color: #eab308;">${stats.alerts}</span>
      </div>
      <div class="stat">
        <span class="stat-label">Active Incidents</span>
        <span class="stat-value" style="color: #ef4444;">${stats.incidents}</span>
      </div>
    </div>
    <div class="footer">
      nMon Monitoring System • Automated Daily Report
    </div>
  </div>
</body>
</html>
    `.trim();
  }

  // Test all notification channels
  async testChannels(): Promise<Map<string, boolean>> {
    const results = new Map<string, boolean>();

    if (this.telegram) {
      results.set('telegram', await this.telegram.testConnection());
    }

    if (this.email) {
      results.set('email', await this.email.verifyConnection());
    }

    return results;
  }

  // Get available channels
  getChannels(): { name: string; enabled: boolean }[] {
    return Array.from(this.channels.values()).map(ch => ({
      name: ch.name,
      enabled: ch.enabled
    }));
  }
}

// Singleton instance
let instance: NotificationService | null = null;

export function getNotificationService(): NotificationService {
  if (!instance) {
    instance = new NotificationService();
  }
  return instance;
}
