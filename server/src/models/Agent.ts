import mongoose, { Document, Schema } from 'mongoose';

export interface IAgent extends Document {
  serverKey: string;
  name: string;
  hostname: string;
  status: 'online' | 'offline' | 'warning' | 'critical';
  type: 'linux' | 'windows' | 'macos';
  group: string;
  location: {
    name: string;
    lat: number;
    lng: number;
  };
  os: {
    name: string;
    version: string;
    arch: string;
  };
  metrics: {
    cpu: {
      usage: number;
      cores: number;
      model: string;
      temperature?: number;
    };
    memory: {
      total: number;
      used: number;
      free: number;
      usage: number;
    };
    disk: Array<{
      mountPoint: string;
      total: number;
      used: number;
      usage: number;
    }>;
    network: Array<{
      interface: string;
      ip: string;
      rxBytes: number;
      txBytes: number;
    }>;
    load: {
      load1: number;
      load5: number;
      load15: number;
    };
    uptime: number;
  };
  lastSeen: Date;
  createdAt: Date;
  updatedAt: Date;
  tags: string[];
  notes: string;
}

const agentSchema = new Schema<IAgent>({
  serverKey: { type: String, required: true, unique: true },
  name: { type: String, required: true },
  hostname: { type: String, required: true },
  status: { 
    type: String, 
    enum: ['online', 'offline', 'warning', 'critical'],
    default: 'offline'
  },
  type: { 
    type: String, 
    enum: ['linux', 'windows', 'macos'],
    required: true
  },
  group: { type: String, default: 'default' },
  location: {
    name: { type: String, default: '' },
    lat: { type: Number, default: 0 },
    lng: { type: Number, default: 0 }
  },
  os: {
    name: { type: String, default: '' },
    version: { type: String, default: '' },
    arch: { type: String, default: '' }
  },
  metrics: {
    cpu: {
      usage: { type: Number, default: 0 },
      cores: { type: Number, default: 0 },
      model: { type: String, default: '' },
      temperature: { type: Number }
    },
    memory: {
      total: { type: Number, default: 0 },
      used: { type: Number, default: 0 },
      free: { type: Number, default: 0 },
      usage: { type: Number, default: 0 }
    },
    disk: [{
      mountPoint: String,
      total: Number,
      used: Number,
      usage: Number
    }],
    network: [{
      interface: String,
      ip: String,
      rxBytes: Number,
      txBytes: Number
    }],
    load: {
      load1: { type: Number, default: 0 },
      load5: { type: Number, default: 0 },
      load15: { type: Number, default: 0 }
    },
    uptime: { type: Number, default: 0 }
  },
  lastSeen: { type: Date, default: Date.now },
  tags: [{ type: String }],
  notes: { type: String, default: '' }
}, {
  timestamps: true
});

// Index for efficient queries
agentSchema.index({ status: 1 });
agentSchema.index({ group: 1 });
agentSchema.index({ 'location.lat': 1, 'location.lng': 1 });

export const Agent = mongoose.model<IAgent>('Agent', agentSchema);
