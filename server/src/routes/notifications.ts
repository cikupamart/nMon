import { Router, Request, Response } from 'express';
import { getNotificationService } from '../services/notifications';

const router = Router();
const notificationService = getNotificationService();

// Get available notification channels
router.get('/channels', (req: Request, res: Response) => {
  const channels = notificationService.getChannels();
  res.json(channels);
});

// Test notification channels
router.post('/test', async (req: Request, res: Response) => {
  try {
    const results = await notificationService.testChannels();
    const resultsObj: Record<string, boolean> = {};
    results.forEach((value, key) => {
      resultsObj[key] = value;
    });
    res.json({ success: true, channels: resultsObj });
  } catch (error) {
    res.status(500).json({ error: 'Failed to test channels' });
  }
});

// Send test notification
router.post('/test/:channel', async (req: Request, res: Response) => {
  const { channel } = req.params;
  const { recipient } = req.body;

  try {
    let success = false;

    switch (channel) {
      case 'telegram':
        const telegram = notificationService['telegram'];
        if (telegram) {
          success = await telegram.sendMessage({
            text: `🔔 <b>nMon Test Notification</b>\n\nThis is a test notification from your nMon monitoring system.\n\n⏰ ${new Date().toLocaleString()}`
          });
        }
        break;

      case 'email':
        const email = notificationService['email'];
        if (email && recipient) {
          success = await email.sendTestEmail(recipient);
        }
        break;

      default:
        return res.status(400).json({ error: 'Unknown channel' });
    }

    res.json({ success, channel });
  } catch (error) {
    res.status(500).json({ error: `Failed to send test notification via ${channel}` });
  }
});

// Send test alert notification
router.post('/test-alert', async (req: Request, res: Response) => {
  try {
    const { severity, message } = req.body;

    const testAlert = {
      severity: severity || 'warning',
      agentName: 'Test Agent',
      type: 'test',
      message: message || 'This is a test alert from nMon',
      value: 85,
      threshold: 80,
      recipients: req.body.recipients || []
    };

    // Send via all channels
    await notificationService.sendAlert(testAlert as any, testAlert as any, testAlert.recipients);

    res.json({ success: true });
  } catch (error) {
    res.status(500).json({ error: 'Failed to send test alert' });
  }
});

// Send daily summary manually
router.post('/daily-summary', async (req: Request, res: Response) => {
  try {
    await notificationService.sendDailySummary();
    res.json({ success: true, message: 'Daily summary sent' });
  } catch (error) {
    res.status(500).json({ error: 'Failed to send daily summary' });
  }
});

// Get notification preferences for an agent
router.get('/preferences/:agentId', async (req: Request, res: Response) => {
  try {
    // In production, fetch from database
    res.json({
      email: [],
      telegram: true,
      enabled: true
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch preferences' });
  }
});

// Update notification preferences for an agent
router.put('/preferences/:agentId', async (req: Request, res: Response) => {
  try {
    const { agentId } = req.params;
    const { email, telegram, enabled } = req.body;

    // In production, save to database
    res.json({
      success: true,
      preferences: { email, telegram, enabled }
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to update preferences' });
  }
});

export { router as notificationRoutes };
