// MongoDB Initialization Script
// This runs when the container starts for the first time

db = db.getSiblingDB('nmon');

// Create collections
db.createCollection('agents');
db.createCollection('alerts');
db.createCollection('metricshistories');

// Create indexes for performance
db.agents.createIndex({ "serverKey": 1 }, { unique: true });
db.agents.createIndex({ "status": 1 });
db.agents.createIndex({ "group": 1 });
db.agents.createIndex({ "lastSeen": -1 });
db.agents.createIndex({ "location.lat": 1, "location.lng": 1 });

db.alerts.createIndex({ "agentId": 1, "status": 1 });
db.alerts.createIndex({ "triggeredAt": -1 });
db.alerts.createIndex({ "type": 1, "severity": 1 });

db.metricshistories.createIndex({ "agentId": 1, "timestamp": -1 });
db.metricshistories.createIndex({ "timestamp": 1 }, { expireAfterSeconds: 7776000 }); // 90 days

// Create user for the application
db.createUser({
  user: "nmon_user",
  pwd: "nmon_password_change_me",
  roles: [
    { role: "readWrite", db: "nmon" }
  ]
});

print("✅ nMon database initialized successfully");
