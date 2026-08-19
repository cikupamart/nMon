import { NavLink } from 'react-router-dom';
import { 
  LayoutDashboard, 
  Server, 
  Map, 
  Bell, 
  Settings,
  Activity,
  Container,
  Box
} from 'lucide-react';

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
  { name: 'Agents', href: '/agents', icon: Server },
  { name: 'Live Map', href: '/map', icon: Map },
  { name: 'Docker', href: '/docker', icon: Container },
  { name: 'Kubernetes', href: '/kubernetes', icon: Box },
  { name: 'Alerts', href: '/alerts', icon: Bell },
  { name: 'Settings', href: '/settings', icon: Settings },
];

export function Sidebar() {
  return (
    <div className="fixed inset-y-0 left-0 w-64 bg-gray-800 border-r border-gray-700">
      <div className="flex items-center h-16 px-6 border-b border-gray-700">
        <Activity className="w-8 h-8 text-blue-500" />
        <span className="ml-3 text-xl font-bold text-white">nMon</span>
      </div>
      
      <nav className="mt-6 px-3">
        {navigation.map((item) => (
          <NavLink
            key={item.name}
            to={item.href}
            className={({ isActive }) =>
              `flex items-center px-3 py-2 mb-1 text-sm font-medium rounded-lg transition-colors ${
                isActive
                  ? 'bg-blue-600 text-white'
                  : 'text-gray-400 hover:bg-gray-700 hover:text-white'
              }`
            }
          >
            <item.icon className="w-5 h-5 mr-3" />
            {item.name}
          </NavLink>
        ))}
      </nav>
      
      <div className="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-700">
        <div className="text-xs text-gray-500">
          nMon v2.0.0
        </div>
      </div>
    </div>
  );
}
