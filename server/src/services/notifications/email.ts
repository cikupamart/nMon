import nodemailer from 'nodemailer';

interface EmailConfig {
  host: string;
  port: number;
  secure?: boolean;
  auth?: {
    user: string;
    pass: string;
  };
  from: string;
}

interface EmailMessage {
  to: string | string[];
  subject: string;
  html: string;
  text?: string;
}

export class EmailNotifier {
  private transporter: nodemailer.Transporter;
  private config: EmailConfig;

  constructor(config: EmailConfig) {
    this.config = config;
    this.transporter = nodemailer.createTransport({
      host: config.host,
      port: config.port,
      secure: config.secure ?? false,
      auth: config.auth,
      tls: {
        rejectUnauthorized: false
      }
    });
  }

  async sendEmail(message: EmailMessage): Promise<boolean> {
    try {
      const info = await this.transporter.sendMail({
        from: this.config.from,
        to: Array.isArray(message.to) ? message.to.join(', ') : message.to,
        subject: message.subject,
        html: message.html,
        text: message.text
      });

      console.log('📧 Email sent:', info.messageId);
      return true;
    } catch (error: any) {
      console.error('Email send error:', error.message);
      return false;
    }
  }

  async sendAlert(alert: {
    severity: 'info' | 'warning' | 'critical';
    agentName: string;
    agentIp?: string;
    type: string;
    message: string;
    value?: number;
    threshold?: number;
    recipients: string[];
  }): Promise<boolean> {
    const color = {
      info: '#3b82f6',
      warning: '#eab308',
      critical: '#ef4444'
    }[alert.severity];

    const html = `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: ${color}; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
    .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
    .footer { background: #f3f4f6; padding: 15px; border-radius: 0 0 8px 8px; font-size: 12px; color: #6b7280; }
    .metric { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
    .metric:last-child { border-bottom: none; }
    .label { font-weight: bold; color: #374151; }
    .value { color: #1f2937; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin:0;">🚨 nMon Alert - ${alert.severity.toUpperCase()}</h2>
    </div>
    <div class="content">
      <div class="metric">
        <span class="label">Agent:</span>
        <span class="value">${alert.agentName}</span>
      </div>
      ${alert.agentIp ? `
      <div class="metric">
        <span class="label">IP Address:</span>
        <span class="value">${alert.agentIp}</span>
      </div>
      ` : ''}
      <div class="metric">
        <span class="label">Alert Type:</span>
        <span class="value">${alert.type}</span>
      </div>
      <div class="metric">
        <span class="label">Message:</span>
        <span class="value">${alert.message}</span>
      </div>
      ${alert.value !== undefined ? `
      <div class="metric">
        <span class="label">Current Value:</span>
        <span class="value">${alert.value}%</span>
      </div>
      ` : ''}
      ${alert.threshold !== undefined ? `
      <div class="metric">
        <span class="label">Threshold:</span>
        <span class="value">${alert.threshold}%</span>
      </div>
      ` : ''}
      <div class="metric">
        <span class="label">Time:</span>
        <span class="value">${new Date().toLocaleString()}</span>
      </div>
    </div>
    <div class="footer">
      nMon Monitoring System • <a href="${process.env.APP_URL || 'http://localhost:3000'}">View Dashboard</a>
    </div>
  </div>
</body>
</html>
    `.trim();

    return this.sendEmail({
      to: alert.recipients,
      subject: `[nMon ${alert.severity.toUpperCase()}] ${alert.agentName} - ${alert.type}`,
      html
    });
  }

  async sendIncident(incident: {
    type: 'opened' | 'resolved';
    agentName: string;
    alertType: string;
    severity: string;
    duration?: string;
    recipients: string[];
  }): Promise<boolean> {
    const color = incident.type === 'opened' ? '#ef4444' : '#22c55e';
    const status = incident.type === 'opened' ? 'OPENED' : 'RESOLVED';

    const html = `
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
    .header { background: ${color}; color: white; padding: 20px; border-radius: 8px 8px 0 0; }
    .content { background: #f9fafb; padding: 20px; border: 1px solid #e5e7eb; }
    .footer { background: #f3f4f6; padding: 15px; border-radius: 0 0 8px 8px; font-size: 12px; color: #6b7280; }
    .metric { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
    .label { font-weight: bold; color: #374151; }
    .value { color: #1f2937; }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h2 style="margin:0;">${incident.type === 'opened' ? '🚨' : '✅'} Incident ${status}</h2>
    </div>
    <div class="content">
      <div class="metric">
        <span class="label">Agent:</span>
        <span class="value">${incident.agentName}</span>
      </div>
      <div class="metric">
        <span class="label">Alert Type:</span>
        <span class="value">${incident.alertType}</span>
      </div>
      <div class="metric">
        <span class="label">Severity:</span>
        <span class="value">${incident.severity}</span>
      </div>
      ${incident.duration ? `
      <div class="metric">
        <span class="label">Duration:</span>
        <span class="value">${incident.duration}</span>
      </div>
      ` : ''}
      <div class="metric">
        <span class="label">Time:</span>
        <span class="value">${new Date().toLocaleString()}</span>
      </div>
    </div>
    <div class="footer">
      nMon Monitoring System • <a href="${process.env.APP_URL || 'http://localhost:3000'}">View Dashboard</a>
    </div>
  </div>
</body>
</html>
    `.trim();

    return this.sendEmail({
      to: incident.recipients,
      subject: `[nMon Incident] ${status} on ${incident.agentName}`,
      html
    });
  }

  async sendTestEmail(recipient: string): Promise<boolean> {
    const html = `
<!DOCTYPE html>
<html>
<body style="font-family: Arial, sans-serif; padding: 20px;">
  <h2>✅ nMon Email Test Successful</h2>
  <p>This is a test email from your nMon monitoring system.</p>
  <p>If you received this, email notifications are working correctly.</p>
  <hr>
  <p style="color: #6b7280; font-size: 12px;">
    Sent at: ${new Date().toLocaleString()}
  </p>
</body>
</html>
    `.trim();

    return this.sendEmail({
      to: recipient,
      subject: '[nMon] Email Test',
      html
    });
  }

  async verifyConnection(): Promise<boolean> {
    try {
      await this.transporter.verify();
      return true;
    } catch (error) {
      console.error('SMTP verification failed:', error);
      return false;
    }
  }
}

// Factory function to create Email notifier from environment
export function createEmailNotifier(): EmailNotifier | null {
  const host = process.env.SMTP_HOST;
  const port = parseInt(process.env.SMTP_PORT || '587');
  const user = process.env.SMTP_USER;
  const pass = process.env.SMTP_PASS;
  const from = process.env.SMTP_FROM || 'nmon@alerts.com';

  if (!host) {
    console.log('⚠️ Email not configured (missing SMTP_HOST)');
    return null;
  }

  return new EmailNotifier({
    host,
    port,
    secure: port === 465,
    auth: user ? { user, pass: pass || '' } : undefined,
    from
  });
}
