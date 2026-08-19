import mongoose, { Document, Schema } from 'mongoose';

export interface IAlert extends Document {
  agentId: mongoose.Types.ObjectId;
  type: 'cpu' | 'memory' | 'disk' | 'network' | 'offline' | 'custom';
  severity: 'warning' | 'critical' | 'info';
  condition: {
    metric: string;
    operator: '>' | '<' | '>=' | '<=' | '==' | '!=';
    value: number;
  };
  message: string;
  status: 'active' | 'resolved' | 'acknowledged';
  triggeredAt: Date;
  resolvedAt?: Date;
  acknowledgedAt?: Date;
  acknowledgedBy?: string;
  occurrences: number;
  lastTriggered: Date;
}

const alertSchema = new Schema<IAlert>({
  agentId: { 
    type: Schema.Types.ObjectId, 
    ref: 'Agent', 
    required: true 
  },
  type: { 
    type: String, 
    enum: ['cpu', 'memory', 'disk', 'network', 'offline', 'custom'],
    required: true
  },
  severity: { 
    type: String, 
    enum: ['warning', 'critical', 'info'],
    required: true
  },
  condition: {
    metric: { type: String, required: true },
    operator: { 
      type: String, 
      enum: ['>', '<', '>=', '<=', '==', '!='],
      required: true
    },
    value: { type: Number, required: true }
  },
  message: { type: String, required: true },
  status: { 
    type: String, 
    enum: ['active', 'resolved', 'acknowledged'],
    default: 'active'
  },
  triggeredAt: { type: Date, default: Date.now },
  resolvedAt: { type: Date },
  acknowledgedAt: { type: Date },
  acknowledgedBy: { type: String },
  occurrences: { type: Number, default: 1 },
  lastTriggered: { type: Date, default: Date.now }
}, {
  timestamps: true
});

// Index for efficient queries
alertSchema.index({ agentId: 1, status: 1 });
alertSchema.index({ triggeredAt: -1 });
alertSchema.index({ type: 1, severity: 1 });

export const Alert = mongoose.model<IAlert>('Alert', alertSchema);
