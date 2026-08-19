import axios from 'axios';

interface TelegramConfig {
  botToken: string;
  chatId: string;
  parseMode?: 'HTML' | 'Markdown' | 'MarkdownV2';
}

interface TelegramMessage {
  text: string;
  parseMode?: 'HTML' | 'Markdown' | 'MarkdownV2';
  disablePreview?: boolean;
}

export class TelegramNotifier {
  private config: TelegramConfig;
  private apiUrl: string;

  constructor(config: TelegramConfig) {
    this.config = config;
    this.apiUrl = `https://api.telegram.org/bot${config.botToken}`;
  }

  async sendMessage(message: TelegramMessage): Promise<boolean> {
    try {
      const response = await axios.post(`${this.apiUrl}/sendMessage`, {
        chat_id: this.config.chatId,
        text: message.text,
        parse_mode: message.parseMode || this.config.parseMode || 'HTML',
        disable_web_page_preview: message.disablePreview ?? true
      });

      return response.data.ok;
    } catch (error: any) {
      console.error('Telegram send error:', error.response?.data || error.message);
      return false;
    }
  }

  async sendAlert(alert: {
    severity: 'info' | 'warning' | 'critical';
    agentName: string;
    type: string;
    message: string;
    value?: number;
    threshold?: number;
  }): Promise<boolean> {
    const emoji = {
      info: 'ℹ️',
      warning: '⚠️',
      critical: '🚨'
    }[alert.severity];

    const color = {
      info: '🔵',
      warning: '🟡',
      critical: '🔴'
    }[alert.severity];

    const text = `
${emoji} <b>nMon Alert - ${alert.severity.toUpperCase()}</b>

${color} <b>Agent:</b> ${alert.agentName}
📊 <b>Type:</b> ${alert.type}
💬 <b>Message:</b> ${alert.message}
${alert.value !== undefined ? `📈 <b>Current Value:</b> ${alert.value}%` : ''}
${alert.threshold !== undefined ? `🎯 <b>Threshold:</b> ${alert.threshold}%` : ''}
⏰ <b>Time:</b> ${new Date().toLocaleString()}
    `.trim();

    return this.sendMessage({ text });
  }

  async sendIncident(incident: {
    type: 'opened' | 'resolved';
    agentName: string;
    alertType: string;
    severity: string;
    duration?: string;
  }): Promise<boolean> {
    const emoji = incident.type === 'opened' ? '🚨' : '✅';
    const status = incident.type === 'opened' ? 'OPENED' : 'RESOLVED';

    const text = `
${emoji} <b>Incident ${status}</b>

🖥️ <b>Agent:</b> ${incident.agentName}
📊 <b>Alert Type:</b> ${incident.alertType}
⚡ <b>Severity:</b> ${incident.severity}
${incident.duration ? `⏱️ <b>Duration:</b> ${incident.duration}` : ''}
⏰ <b>Time:</b> ${new Date().toLocaleString()}
    `.trim();

    return this.sendMessage({ text });
  }

  async sendDailySummary(summary: {
    totalAgents: number;
    onlineAgents: number;
    alerts: number;
    incidents: number;
  }): Promise<boolean> {
    const text = `
📊 <b>nMon Daily Summary</b>

🖥️ <b>Total Agents:</b> ${summary.totalAgents}
✅ <b>Online:</b> ${summary.onlineAgents}
⚠️ <b>Alerts:</b> ${summary.alerts}
🚨 <b>Incidents:</b> ${summary.incidents}

📅 <b>Date:</b> ${new Date().toLocaleDateString()}
    `.trim();

    return this.sendMessage({ text });
  }

  async testConnection(): Promise<boolean> {
    try {
      const response = await axios.get(`${this.apiUrl}/getMe`);
      return response.data.ok;
    } catch (error) {
      console.error('Telegram connection test failed:', error);
      return false;
    }
  }
}

// Factory function to create Telegram notifier from environment
export function createTelegramNotifier(): TelegramNotifier | null {
  const botToken = process.env.TELEGRAM_BOT_TOKEN;
  const chatId = process.env.TELEGRAM_CHAT_ID;

  if (!botToken || !chatId) {
    console.log('⚠️ Telegram not configured (missing TELEGRAM_BOT_TOKEN or TELEGRAM_CHAT_ID)');
    return null;
  }

  return new TelegramNotifier({ botToken, chatId });
}
