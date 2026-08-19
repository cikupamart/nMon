# nMon v2.0 - Modern Monitoring System

Sistem monitoring server modern dengan fitur remote control dan live maps.

## 🚀 Fitur Utama

### Backend (Node.js + TypeScript)
- **Real-time monitoring** via WebSocket
- **RESTful API** untuk integrasi
- **MongoDB** untuk penyimpanan data
- **Auto-discovery** agent baru
- **Alert system** dengan threshold

### Frontend (React + TypeScript + Tailwind)
- **Dashboard** dengan grafik real-time
- **Live Maps** dengan Leaflet.js
- **Remote Terminal** untuk command execution
- **Agent Management** (CRUD)
- **Alert Management**

### Agent (Go - Cross Platform)
- **Linux Agent** - Menggunakan /proc filesystem
- **Windows Agent** - Menggunakan WMI/PowerShell
- **Remote Control** - Execute commands via WebSocket
- **Auto-update** mechanism
- **Encrypted transport** (TLS)

## 📁 Struktur Project

```
nmon-modern/
├── server/                 # Backend API (Node.js)
│   ├── src/
│   │   ├── index.ts       # Entry point
│   │   ├── models/        # Mongoose models
│   │   ├── routes/        # Express routes
│   │   ├── services/      # Business logic
│   │   └── websocket/     # WebSocket handler
│   └── package.json
│
├── frontend/              # React Dashboard
│   ├── src/
│   │   ├── pages/         # React pages
│   │   ├── components/    # Reusable components
│   │   └── contexts/      # React contexts
│   └── package.json
│
└── nmon-agent/            # Go Agent
    ├── internal/
    │   ├── collector/     # System metrics collection
    │   ├── remote/        # Remote control handler
    │   ├── sender/        # Data transmission
    │   └── config/        # Configuration
    └── main.go
```

## 🛠️ Installation

### 1. Backend Server

```bash
cd server
npm install
npm run dev
```

Server akan berjalan di `http://localhost:3000`

### 2. Frontend Dashboard

```bash
cd frontend
npm install
npm run dev
```

Dashboard akan berjalan di `http://localhost:5173`

### 3. Build & Install Agent

#### Linux Agent
```bash
cd nmon-agent
go build -o nmon-agent-linux .

# Install
sudo ./nmon-agent-linux -server-key YOUR_SERVER_KEY -gateway http://YOUR_SERVER:3000/api/v1/agent/data
```

#### Windows Agent
```bash
cd nmon-agent
GOOS=windows GOARCH=amd64 go build -o nmon-agent.exe .

# Install (Run as Administrator)
.\nmon-agent.exe -server-key YOUR_SERVER_KEY -gateway http://YOUR_SERVER:3000/api/v1/agent/data
```

## 📡 API Endpoints

### Agent Endpoints
- `POST /api/v1/agent/data` - Submit agent metrics
- `GET /api/agents` - List all agents
- `GET /api/agents/:id` - Get agent details
- `PUT /api/agents/:id` - Update agent
- `DELETE /api/agents/:id` - Delete agent
- `GET /api/agents/:id/metrics` - Get metrics history
- `POST /api/agents/:id/execute` - Execute command

### Alert Endpoints
- `GET /api/alerts` - List alerts
- `PUT /api/alerts/:id/acknowledge` - Acknowledge alert
- `PUT /api/alerts/:id/resolve` - Resolve alert

### WebSocket Events
- `agent:update` - Agent metrics update
- `alert:new` - New alert triggered
- `execute:command` - Execute command on agent
- `command:result` - Command execution result

## 🗺️ Live Maps

Fitur live maps menggunakan Leaflet.js untuk menampilkan lokasi semua agent secara real-time.

### Konfigurasi Lokasi Agent

```json
{
  "location": {
    "name": "Server Room Jakarta",
    "lat": -6.2088,
    "lng": 106.8456
  }
}
```

### Fitur Maps
- Marker berwarna berdasarkan status (hijau=kuning=merah=abu-abu)
- Popup dengan detail agent
- Auto-zoom ke semua agent
- Real-time updates

## 🔧 Remote Control

### Execute Commands via WebSocket

```javascript
socket.emit('execute:command', {
  agentId: 'agent-id',
  command: 'df',
  args: ['-h']
});

socket.on('command:result', (result) => {
  console.log(result.output);
});
```

### Execute Commands via REST API

```bash
curl -X POST http://localhost:3000/api/agents/AGENT_ID/execute \
  -H "Content-Type: application/json" \
  -d '{"command": "uname", "args": ["-a"]}'
```

### Allowed Commands
- System: `ls`, `pwd`, `uptime`, `uname`, `hostname`
- Processes: `ps`, `top`, `htop`
- Network: `netstat`, `ss`, `ip`, `ping`
- Disk: `df`, `du`, `mount`
- Memory: `free`
- Service: `systemctl`, `service`

### Blocked Commands
- `rm`, `del` - File deletion
- `mkfs`, `fdisk` - Disk formatting
- `shutdown`, `reboot` - System shutdown
- `passwd` - Password changes

## 📊 Metrics Collected

### CPU
- Usage percentage
- Core count
- Model name
- Temperature (if available)

### Memory
- Total, Used, Free
- Buffers, Cached
- Swap usage

### Disk
- Per-mount usage
- Total, Used, Free
- Inode usage

### Network
- Per-interface stats
- RX/TX bytes
- RX/TX packets
- Errors

### System
- Hostname
- OS info
- Kernel version
- Uptime
- Load average
- Process count

## 🔔 Alert System

### Threshold Alerts
- CPU > 80% (warning), > 90% (critical)
- Memory > 85% (warning), > 95% (critical)
- Disk > 90% (warning), > 95% (critical)

### Alert Actions
- Dashboard notification
- WebSocket broadcast
- Email notification (configurable)
- Webhook integration (configurable)

## 🚀 Deployment

### Docker Compose

```yaml
version: '3.8'
services:
  mongodb:
    image: mongo:6
    ports:
      - "27017:27017"
    volumes:
      - mongo-data:/data/db

  server:
    build: ./server
    ports:
      - "3000:3000"
    environment:
      - MONGODB_URI=mongodb://mongodb:27017/nmon
    depends_on:
      - mongodb

  frontend:
    build: ./frontend
    ports:
      - "80:80"
    depends_on:
      - server

volumes:
  mongo-data:
```

```bash
docker-compose up -d
```

## 📝 License

MIT License

## 👥 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## 📞 Support

For support, email support@nmon.dev or open an issue on GitHub.
