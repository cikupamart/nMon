import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { Dashboard } from './pages/Dashboard';
import { AgentList } from './pages/AgentList';
import { AgentDetail } from './pages/AgentDetail';
import { LiveMap } from './pages/LiveMap';
import { DockerMonitor } from './pages/DockerMonitor';
import { KubernetesMonitor } from './pages/KubernetesMonitor';
import { Alerts } from './pages/Alerts';
import { Settings } from './pages/Settings';
import { Layout } from './components/Layout';
import { WebSocketProvider } from './contexts/WebSocketContext';

function App() {
  return (
    <WebSocketProvider>
      <Router>
        <Routes>
          <Route path="/" element={<Layout />}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="dashboard" element={<Dashboard />} />
            <Route path="agents" element={<AgentList />} />
            <Route path="agents/:id" element={<AgentDetail />} />
            <Route path="map" element={<LiveMap />} />
            <Route path="docker" element={<DockerMonitor />} />
            <Route path="kubernetes" element={<KubernetesMonitor />} />
            <Route path="alerts" element={<Alerts />} />
            <Route path="settings" element={<Settings />} />
          </Route>
        </Routes>
      </Router>
    </WebSocketProvider>
  );
}

export default App;
