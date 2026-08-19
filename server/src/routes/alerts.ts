import { Router, Request, Response } from 'express';
import { Alert } from '../models/Alert';
import { Agent } from '../models/Agent';

const router = Router();

// Get all alerts
router.get('/', async (req: Request, res: Response) => {
  try {
    const { status, severity, agentId } = req.query;
    
    const filter: any = {};
    if (status) filter.status = status;
    if (severity) filter.severity = severity;
    if (agentId) filter.agentId = agentId;

    const alerts = await Alert.find(filter)
      .sort({ triggeredAt: -1 })
      .limit(100);

    res.json(alerts);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch alerts' });
  }
});

// Get single alert
router.get('/:id', async (req: Request, res: Response) => {
  try {
    const alert = await Alert.findById(req.params.id);
    if (!alert) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    res.json(alert);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch alert' });
  }
});

// Acknowledge alert
router.put('/:id/acknowledge', async (req: Request, res: Response) => {
  try {
    const alert = await Alert.findByIdAndUpdate(
      req.params.id,
      { status: 'acknowledged', acknowledgedAt: new Date() },
      { new: true }
    );
    
    if (!alert) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    
    res.json(alert);
  } catch (error) {
    res.status(500).json({ error: 'Failed to acknowledge alert' });
  }
});

// Resolve alert
router.put('/:id/resolve', async (req: Request, res: Response) => {
  try {
    const alert = await Alert.findByIdAndUpdate(
      req.params.id,
      { 
        status: 'resolved', 
        resolvedAt: new Date(),
        duration: req.body.duration || undefined
      },
      { new: true }
    );
    
    if (!alert) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    
    res.json(alert);
  } catch (error) {
    res.status(500).json({ error: 'Failed to resolve alert' });
  }
});

// Delete alert
router.delete('/:id', async (req: Request, res: Response) => {
  try {
    const alert = await Alert.findByIdAndDelete(req.params.id);
    if (!alert) {
      return res.status(404).json({ error: 'Alert not found' });
    }
    res.json({ message: 'Alert deleted' });
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete alert' });
  }
});

// Get alert statistics
router.get('/stats/summary', async (req: Request, res: Response) => {
  try {
    const active = await Alert.countDocuments({ status: 'active' });
    const acknowledged = await Alert.countDocuments({ status: 'acknowledged' });
    const resolved = await Alert.countDocuments({ status: 'resolved' });
    
    const critical = await Alert.countDocuments({ status: 'active', severity: 'critical' });
    const warning = await Alert.countDocuments({ status: 'active', severity: 'warning' });
    const info = await Alert.countDocuments({ status: 'active', severity: 'info' });

    res.json({
      active,
      acknowledged,
      resolved,
      bySeverity: { critical, warning, info },
      total: active + acknowledged + resolved
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch alert stats' });
  }
});

export { router as alertRoutes };
