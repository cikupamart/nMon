import { Router, Request, Response } from 'express';
import { Agent } from '../models/Agent';
import { MetricsHistory } from '../models/MetricsHistory';
import { Alert } from '../models/Alert';

const router = Router();

// Get all agents
router.get('/', async (req: Request, res: Response) => {
  try {
    const { status, group, search } = req.query;
    
    const filter: any = {};
    if (status) filter.status = status;
    if (group) filter.group = group;
    if (search) {
      filter.$or = [
        { name: { $regex: search, $options: 'i' } },
        { hostname: { $regex: search, $options: 'i' } }
      ];
    }

    const agents = await Agent.find(filter).sort({ lastSeen: -1 });
    res.json(agents);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch agents' });
  }
});

// Get single agent
router.get('/:id', async (req: Request, res: Response) => {
  try {
    const agent = await Agent.findById(req.params.id);
    if (!agent) {
      return res.status(404).json({ error: 'Agent not found' });
    }
    res.json(agent);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch agent' });
  }
});

// Update agent
router.put('/:id', async (req: Request, res: Response) => {
  try {
    const agent = await Agent.findByIdAndUpdate(
      req.params.id,
      req.body,
      { new: true }
    );
    if (!agent) {
      return res.status(404).json({ error: 'Agent not found' });
    }
    res.json(agent);
  } catch (error) {
    res.status(500).json({ error: 'Failed to update agent' });
  }
});

// Delete agent
router.delete('/:id', async (req: Request, res: Response) => {
  try {
    const agent = await Agent.findByIdAndDelete(req.params.id);
    if (!agent) {
      return res.status(404).json({ error: 'Agent not found' });
    }
    
    // Delete related data
    await MetricsHistory.deleteMany({ agentId: req.params.id });
    await Alert.deleteMany({ agentId: req.params.id });
    
    res.json({ message: 'Agent deleted' });
  } catch (error) {
    res.status(500).json({ error: 'Failed to delete agent' });
  }
});

// Get agent metrics history
router.get('/:id/metrics', async (req: Request, res: Response) => {
  try {
    const { period = '24h' } = req.query;
    
    let startDate: Date;
    switch (period) {
      case '1h':
        startDate = new Date(Date.now() - 60 * 60 * 1000);
        break;
      case '24h':
        startDate = new Date(Date.now() - 24 * 60 * 60 * 1000);
        break;
      case '7d':
        startDate = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000);
        break;
      case '30d':
        startDate = new Date(Date.now() - 30 * 24 * 60 * 60 * 1000);
        break;
      default:
        startDate = new Date(Date.now() - 24 * 60 * 60 * 1000);
    }

    const metrics = await MetricsHistory.find({
      agentId: req.params.id,
      timestamp: { $gte: startDate }
    }).sort({ timestamp: 1 });

    res.json(metrics);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch metrics' });
  }
});

// Execute command on agent
router.post('/:id/execute', async (req: Request, res: Response) => {
  try {
    const { command, args = [] } = req.body;
    
    if (!command) {
      return res.status(400).json({ error: 'Command required' });
    }

    // This would be handled by WebSocket in production
    // For HTTP fallback, we'll return a placeholder
    res.json({
      status: 'pending',
      message: 'Command queued for execution',
      command,
      args
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to execute command' });
  }
});

// Get agent alerts
router.get('/:id/alerts', async (req: Request, res: Response) => {
  try {
    const { status } = req.query;
    
    const filter: any = { agentId: req.params.id };
    if (status) filter.status = status;

    const alerts = await Alert.find(filter).sort({ triggeredAt: -1 });
    res.json(alerts);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch alerts' });
  }
});

// Get all agents on map (for live map feature)
router.get('/map/all', async (req: Request, res: Response) => {
  try {
    const agents = await Agent.find({
      'location.lat': { $ne: 0 },
      'location.lng': { $ne: 0 }
    }).select('name hostname status location type metrics.cpu metrics.memory metrics.uptime lastSeen');

    res.json(agents);
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch map data' });
  }
});

// Get dashboard stats
router.get('/stats/overview', async (req: Request, res: Response) => {
  try {
    const totalAgents = await Agent.countDocuments();
    const onlineAgents = await Agent.countDocuments({ status: 'online' });
    const warningAgents = await Agent.countDocuments({ status: 'warning' });
    const criticalAgents = await Agent.countDocuments({ status: 'critical' });
    const offlineAgents = await Agent.countDocuments({ status: 'offline' });

    const activeAlerts = await Alert.countDocuments({ status: 'active' });

    res.json({
      totalAgents,
      onlineAgents,
      warningAgents,
      criticalAgents,
      offlineAgents,
      activeAlerts
    });
  } catch (error) {
    res.status(500).json({ error: 'Failed to fetch stats' });
  }
});

export { router as agentRoutes };
