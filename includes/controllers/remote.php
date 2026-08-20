<?php

// Remote Control Controller
// Handles remote commands for agents

// Check if user is logged in and authorized
if (!isset($liu) || !in_array('remoteControl', $perms)) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Check if remote commands table exists (for fresh installs or missing migrations)
try {
    $tableCheck = $database->select('app_remote_commands', 'id', ['LIMIT' => 1]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Remote control feature not available. Please run the database upgrade: install/upgrade.php']);
    exit;
}

// Get action
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'send':
        sendRemoteCommand();
        break;
    
    case 'status':
        getCommandStatus();
        break;
    
    case 'result':
        getCommandResult();
        break;
    
    case 'list':
        listCommands();
        break;
    
    case 'agents':
        listAgents();
        break;
    
    case 'map':
        getMapData();
        break;
    
    default:
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid action']);
        break;
}

//============
// Functions
//============

function sendRemoteCommand() {
    global $database, $liu;
    
    $serverId = intval($_POST['server_id'] ?? 0);
    $command = trim($_POST['command'] ?? '');
    
    if ($serverId <= 0 || empty($command)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid parameters']);
        return;
    }
    
    // Verify server exists
    $server = $database->get('app_servers', '*', ['id' => $serverId]);
    if (!$server) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Server not found']);
        return;
    }
    
    // Verify user has access to this server
    $liu_groups = unserialize($liu['groups']);
    if (!in_array('0', $liu_groups) && !in_array($server['groupid'], $liu_groups)) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Access denied']);
        return;
    }
    
    // Save command to database
    $cmdId = $database->insert('app_remote_commands', [
        'server_id' => $serverId,
        'command' => $command,
        'user_id' => $liu['id'],
        'user_name' => $liu['name'],
        'status' => 'pending',
        'created_at' => date('Y-m-d H:i:s'),
        'executed_at' => null,
        'completed_at' => null
    ]);
    
    // Log the action
    logSystem("Remote command sent - Server: {$server['name']} - Command: {$command}");
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'command_id' => $cmdId,
        'message' => 'Command sent successfully'
    ]);
}

function getCommandStatus() {
    global $database;
    
    $cmdId = intval($_GET['id'] ?? 0);
    
    if ($cmdId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid command ID']);
        return;
    }
    
    $command = $database->get('app_remote_commands', '*', ['id' => $cmdId]);
    if (!$command) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Command not found']);
        return;
    }
    
    header('Content-Type: application/json');
    echo json_encode($command);
}

function getCommandResult() {
    global $database;
    
    $cmdId = intval($_GET['id'] ?? 0);
    
    if ($cmdId <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid command ID']);
        return;
    }
    
    $command = $database->get('app_remote_commands', '*', ['id' => $cmdId]);
    if (!$command) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Command not found']);
        return;
    }
    
    // Decode output if exists
    $output = '';
    if (!empty($command['output'])) {
        $output = base64_decode($command['output']);
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'id' => $command['id'],
        'command' => $command['command'],
        'status' => $command['status'],
        'exit_code' => $command['exit_code'] ?? null,
        'output' => $output,
        'executed_at' => $command['executed_at'],
        'completed_at' => $command['completed_at']
    ]);
}

function listCommands() {
    global $database;
    
    $serverId = intval($_GET['server_id'] ?? 0);
    $limit = intval($_GET['limit'] ?? 50);
    
    $where = [];
    if ($serverId > 0) {
        $where['server_id'] = $serverId;
    }
    
    $commands = $database->select('app_remote_commands', '*', [
        $where,
        'ORDER' => ['id' => 'DESC'],
        'LIMIT' => $limit
    ]);
    
    header('Content-Type: application/json');
    echo json_encode($commands);
}

function listAgents() {
    global $database, $liu;
    
    $liu_groups = unserialize($liu['groups']);
    
    $servers = $database->select('app_servers', '*', [
        'ORDER' => ['name' => 'ASC']
    ]);
    
    // Filter by user groups
    $filteredServers = [];
    foreach ($servers as $server) {
        if (in_array('0', $liu_groups) || in_array($server['groupid'], $liu_groups)) {
            $filteredServers[] = $server;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($filteredServers);
}

function getMapData() {
    global $database, $liu;
    
    $liu_groups = unserialize($liu['groups']);
    
    $servers = $database->select('app_servers', '*', [
        'on_map' => 1,
        'ORDER' => ['name' => 'ASC']
    ]);
    
    // Filter by user groups and add location data
    $mapData = [];
    foreach ($servers as $server) {
        if (in_array('0', $liu_groups) || in_array($server['groupid'], $liu_groups)) {
            $latest = Server::latestData($server['id']);
            $location = getServerLocation($server, $latest);
            
            $mapData[] = [
                'id' => $server['id'],
                'name' => $server['name'],
                'type' => $server['type'],
                'status' => $server['status'],
                'lat' => $location['lat'],
                'lng' => $location['lng'],
                'location_name' => $location['name'],
                'ip' => $location['ip'],
                'uptime' => Server::uptimePercentage($server['id'], '24h')
            ];
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($mapData);
}

function getServerLocation($server, $latest) {
    $location = [
        'lat' => floatval($server['lat'] ?? 0),
        'lng' => floatval($server['lng'] ?? 0),
        'name' => $server['name'],
        'ip' => ''
    ];
    
    if (empty($latest)) {
        return $location;
    }
    
    // Try to get IP from geodata or network info
    if (!empty($server['geodata'])) {
        $geodata = @unserialize($server['geodata']);
        if (!empty($geodata['lat']) && !empty($geodata['lon'])) {
            $location['lat'] = floatval($geodata['lat']);
            $location['lng'] = floatval($geodata['lon']);
        }
        if (!empty($geodata['countryName'])) {
            $location['name'] .= ' - ' . $geodata['countryName'];
        }
    }
    
    // Get IP from network info
    if ($server['type'] == 'linux') {
        $ipv4 = Server::extractData('ipv4_addresses', $latest['data'], true);
        if (!empty($ipv4)) {
            $parts = explode(',', $ipv4);
            if (isset($parts[1])) {
                $location['ip'] = $parts[1];
            }
        }
    } elseif ($server['type'] == 'windows') {
        $netInterfaces = json_decode(Server::extractData('net_interfaces', $latest['data'], true), true);
        if (!empty($netInterfaces)) {
            foreach ($netInterfaces as $iface) {
                if (!empty($iface['ip4'])) {
                    $location['ip'] = $iface['ip4'];
                    break;
                }
            }
        }
    }
    
    return $location;
}

?>
