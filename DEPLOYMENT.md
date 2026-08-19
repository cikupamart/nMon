# nMon v2.0 Deployment Guide

Panduan lengkap untuk deploy nMon dengan Docker, termasuk Docker/Kubernetes monitoring dan alert channels.

---

## 📋 Prerequisites

- Docker & Docker Compose v2.0+
- Go 1.21+ (untuk build agent)
- Node.js 18+ (untuk development)
- Telegram Bot Token (opsional, untuk Telegram alerts)
- SMTP Server (opsional, untuk Email alerts)

---

## 🐳 Docker Deployment

### 1. Clone & Configure

```bash
# Clone repository
git clone <repository-url>
cd nmon-modern

# Copy dan edit environment file
cp .env.example .env
nano .env
```

### 2. Environment Variables (.env)

```bash
# Database
MONGO_USERNAME=admin
MONGO_PASSWORD=your-secure-password-here

# Server
JWT_SECRET=your-jwt-secret-change-this
SERVER_PORT=3000
FRONTEND_PORT=80

# Telegram (opsional)
TELEGRAM_BOT_TOKEN=123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11
TELEGRAM_CHAT_ID=-1001234567890

# Email SMTP (opsional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=nmon@yourdomain.com

# Daily Summary
DAILY_SUMMARY_EMAILS=admin@yourdomain.com,ops@yourdomain.com
```

### 3. Start Services

```bash
# Build and start all services
docker-compose up -d

# Check status
docker-compose ps

# View logs
docker-compose logs -f server
docker-compose logs -f frontend
```

### 4. Access Services

- **Dashboard**: http://localhost (port 80)
- **API**: http://localhost:3000/api
- **MongoDB**: localhost:27017

---

## 🔧 Build Agent

### Linux Agent

```bash
cd nmon-agent

# Build for Linux
go build -o nmon-agent-linux .

# Make executable
chmod +x nmon-agent-linux

# Test version
./nmon-agent-linux --version
```

### Windows Agent

```bash
cd nmon-agent

# Build for Windows (cross-compile)
GOOS=windows GOARCH=amd64 go build -o nmon-agent.exe .

# Test version
.\nmon-agent.exe --version
```

### Docker Agent (untuk monitoring container)

```bash
# Agent sudah mendukung Docker monitoring secara otomatis
# Pastikan Docker socket accessible:
./nmon-agent-linux \
  -server-key YOUR_SERVER_KEY \
  -gateway http://YOUR_SERVER:3000/api/v1/agent/data \
  -enable-docker=true \
  -enable-kubernetes=true
```

---

## 📡 Install Agent

### Linux (systemd service)

```bash
# Create user
sudo useradd -r -s /bin/false nmonagent

# Create directory
sudo mkdir -p /opt/nmon
sudo cp nmon-agent-linux /opt/nmon/
sudo cp -r config.json /opt/nmon/

# Set permissions
sudo chown -R nmonagent:nmonagent /opt/nmon

# Create systemd service
sudo tee /etc/systemd/system/nmon-agent.service << EOF
[Unit]
Description=nMon Agent
After=network.target docker.service

[Service]
Type=simple
User=nmonagent
Group=nmonagent
ExecStart=/opt/nmon/nmon-agent-linux -config /opt/nmon/config.json
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF

# Enable and start
sudo systemctl daemon-reload
sudo systemctl enable nmon-agent
sudo systemctl start nmon-agent

# Check status
sudo systemctl status nmon-agent
```

### Windows (Windows Service)

```powershell
# Run as Administrator
.\nmon-agent.exe -install

# Or manual run
.\nmon-agent.exe -server-key YOUR_KEY -gateway http://YOUR_SERVER:3000/api/v1/agent/data
```

---

## 🐳 Docker Monitoring

Agent akan otomatis mendeteksi Docker dan mengumpulkan metrics:

- **Container stats**: CPU, Memory, Network per container
- **Container list**: Status, image, uptime
- **Docker info**: Version, total containers, images

### Requirements

- Docker socket accessible: `/var/run/docker.sock`
- Or run agent inside Docker:

```bash
docker run -d \
  --name nmon-agent \
  -v /var/run/docker.sock:/var/run/docker.sock \
  -e SERVER_KEY=YOUR_KEY \
  -e GATEWAY=http://YOUR_SERVER:3000/api/v1/agent/data \
  nmon-agent:latest
```

---

## ☸️ Kubernetes Monitoring

Agent akan otomatis mendeteksi cluster Kubernetes:

- **Nodes**: Status, capacity, kubelet version
- **Pods**: Status, restarts, failed pods
- **Deployments**: Replicas, health status

### Requirements

Run agent sebagai pod di cluster:

```yaml
apiVersion: apps/v1
kind: DaemonSet
metadata:
  name: nmon-agent
  namespace: monitoring
spec:
  selector:
    matchLabels:
      app: nmon-agent
  template:
    metadata:
      labels:
        app: nmon-agent
    spec:
      containers:
      - name: nmon-agent
        image: nmon-agent:latest
        args:
        - -server-key=YOUR_KEY
        - -gateway=http://nmon-server.monitoring.svc:3000/api/v1/agent/data
        - -enable-kubernetes=true
        volumeMounts:
        - name: docker-sock
          mountPath: /var/run/docker.sock
        env:
        - name: KUBERNETES_SERVICE_HOST
          valueFrom:
            fieldRef:
              fieldPath: status.podIP
      volumes:
      - name: docker-sock
        hostPath:
          path: /var/run/docker.sock
```

---

## 🔔 Alert Channels Setup

### Telegram Setup

1. Buka Telegram, cari @BotFather
2. Buat bot baru: `/newbot`
3. Copy bot token
4. Buat group, tambahkan bot, copy chat ID
5. Set environment variables:

```bash
TELEGRAM_BOT_TOKEN=123456:ABC-DEF
TELEGRAM_CHAT_ID=-1001234567890
```

### Email SMTP Setup

```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM=nmon@yourdomain.com
```

**Gmail App Password:**
1. Go to https://myaccount.google.com/apppasswords
2. Create new app password
3. Use that password (not your regular password)

### Test Notifications

```bash
# Test Telegram
curl -X POST http://localhost:3000/api/notifications/test/telegram

# Test Email
curl -X POST http://localhost:3000/api/notifications/test/email \
  -H "Content-Type: application/json" \
  -d '{"recipient": "test@example.com"}'

# Test Alert
curl -X POST http://localhost:3000/api/notifications/test-alert \
  -H "Content-Type: application/json" \
  -d '{"severity": "warning", "message": "Test alert"}'
```

---

## 🔄 Auto-Update

Agent mendukung auto-update dari server releases:

```bash
# Enable auto-update (default: enabled)
./nmon-agent-linux -enable-autoupdate=true

# Force update check
./nmon-agent-linux -force-update

# Rollback to previous version
./nmon-agent-linux -rollback
```

### Setup Update Server

1. Create releases directory structure:
```
/releases/
  /linux/
    /amd64/
      nmon-agent-linux
  /windows/
    /amd64/
      nmon-agent.exe
  version.json
```

2. Create `version.json`:
```json
{
  "latest": {
    "version": "2.1.0",
    "download_url": "https://releases.nmon.dev",
    "checksum": "sha256-hash-here",
    "release_note": "Bug fixes and improvements"
  }
}
```

---

## 🛠️ Troubleshooting

### Check Logs

```bash
# Docker logs
docker-compose logs -f server
docker-compose logs -f mongodb

# System logs (Linux)
journalctl -u nmon-agent -f
```

### Common Issues

1. **MongoDB Connection Failed**
   - Check if MongoDB is running: `docker-compose ps`
   - Check credentials in `.env`

2. **Agent Not Connecting**
   - Verify server URL and server key
   - Check firewall rules
   - Test API: `curl http://YOUR_SERVER:3000/health`

3. **Docker Monitoring Not Working**
   - Ensure Docker socket is accessible
   - Check agent has permission to read Docker socket

4. **Kubernetes Monitoring Not Working**
   - Ensure agent has cluster access
   - Check RBAC permissions

---

## 📊 API Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/api/agents` | GET | List all agents |
| `/api/agents/:id` | GET | Get agent details |
| `/api/agents/:id/metrics` | GET | Get metrics history |
| `/api/alerts` | GET | List alerts |
| `/api/notifications/channels` | GET | List notification channels |
| `/api/notifications/test/:channel` | POST | Test notification channel |
| `/health` | GET | Health check |

---

## 🔒 Security Notes

1. Change default passwords in production
2. Use HTTPS with reverse proxy (nginx)
3. Restrict MongoDB access
4. Use strong JWT secrets
5. Enable firewall rules

---

## 📞 Support

- GitHub Issues: https://github.com/your-org/nmon/issues
- Documentation: https://docs.nmon.dev
- Email: support@nmon.dev
