import mongoose, { Document, Schema } from 'mongoose';

export interface IMetricsHistory extends Document {
  agentId: mongoose.Types.ObjectId;
  timestamp: Date;
  cpu: {
    usage: number;
    user: number;
    system: number;
    idle: number;
  };
  memory: {
    total: number;
    used: number;
    usage: number;
  };
  disk: Array<{
    mountPoint: string;
    usage: number;
  }>;
  network: Array<{
    interface: string;
    rxBytes: number;
    txBytes: number;
  }>;
  load: {
    load1: number;
    load5: number;
    load15: number;
  };
}

const metricsHistorySchema = new Schema<IMetricsHistory>({
  agentId: { 
    type: Schema.Types.ObjectId, 
    ref: 'Agent', 
    required: true 
  },
  timestamp: { type: Date, required: true, index: true },
  cpu: {
    usage: { type: Number, required: true },
    user: { type: Number, default: 0 },
    system: { type: Number, default: 0 },
    idle: { type: Number, default: 0 }
  },
  memory: {
    total: { type: Number, required: true },
    used: { type: Number, required: true },
    usage: { type: Number, required: true }
  },
  disk: [{
    mountPoint: String,
    usage: Number
  }],
  network: [{
    interface: String,
    rxBytes: Number,
    txBytes: Number
  }],
  load: {
    load1: { type: Number, default: 0 },
    load5: { type: Number, default: 0 },
    load15: { type: Number, default: 0 }
  }
}, {
  timestamps: false
});

// Index for time-series queries
metricsHistorySchema.index({ agentId: 1, timestamp: -1 });
metricsHistorySchema.index({ timestamp: 1 }, { expireAfterSeconds: 90 * 24 * 60 * 60 }); // 90 days retention

export const MetricsHistory = mongoose.model<IMetricsHistory>('MetricsHistory', metricsHistorySchema);
