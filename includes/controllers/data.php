<?php

##################################
###     GET DATA FOR PAGES     ###
##################################


// GENERAL

// autorefresh pages
$autorefresh_pages = ['dashboard','servers','servers/manage','websites','websites/manage','checks','checks/manage'];

// reset datetime range when switching assets
if($_SESSION['range_type'] == "manual") {
	if ($route == "servers/manage") {
		if($_SESSION['asset'] != "server-".$_GET['id']) App::resetRange();
	}
	if ($route == "websites/manage") {
		if($_SESSION['asset'] != "website-".$_GET['id']) App::resetRange();
	}
	if ($route == "checks/manage") {
		if($_SESSION['asset'] != "check-".$_GET['id']) App::resetRange();
	}
}

// dashboard and alerts data
$main_servers_unresolved = getTableFiltered("app_servers_incidents","status[!]","1","","","*","id","ASC");
$main_websites_unresolved = getTableFiltered("app_websites_incidents","status[!]","1","","","*","id","ASC");
$main_checks_unresolved = getTableFiltered("app_checks_incidents","status[!]","1","","","*","id","ASC");

foreach ($main_servers_unresolved as $key => $value) {
	if(!checkGroup(get_group("app_servers", $value['serverid']))) {
		unset($main_servers_unresolved[$key]);
	}
}

foreach ($main_websites_unresolved as $key => $value) {
	if(!checkGroup(get_group("app_websites", $value['websiteid']))) {
		unset($main_websites_unresolved[$key]);
	}
}

foreach ($main_checks_unresolved as $key => $value) {
	if(!checkGroup(get_group("app_checks", $value['checkid']))) {
		unset($main_checks_unresolved[$key]);
	}
}



// LIVE MAPS
if ($route == "maps") {
	isAuthorized("viewMaps");
	$pageTitle = __("Live Maps");
}

// REMOTE CONTROL
if ($route == "remote") {
	isAuthorized("remoteControl");
	$pageTitle = __("Remote Control");
}

// SEARCH
if ($route == "search") {
	$servers = $database->select("app_servers", "*", [ "AND" => [ "groupid" => $liu_groups, "OR" => [
		"name[~]" => $_GET['q'],
	]]]);

	$websites = $database->select("app_websites", "*", [ "AND" => [ "groupid" => $liu_groups, "OR" => [
		"name[~]" => $_GET['q'],
		"url[~]" => $_GET['q'],
	]]]);

	$checks = $database->select("app_checks", "*", [ "AND" => [ "groupid" => $liu_groups, "OR" => [
		"name[~]" => $_GET['q'],
		"host[~]" => $_GET['q'],
	]]]);


	$pageTitle = __("Search");
}

// DASHBOARD
if ($route == "dashboard") {
	$servers_count = countTableFiltered("app_servers","groupid",$liu_groups);
	$websites_count = countTableFiltered("app_websites","groupid",$liu_groups);
	$checks_count = countTableFiltered("app_checks","groupid",$liu_groups);
	$contacts_count = countTableFiltered("app_contacts","groupid",$liu_groups);

	$checks = getTableFiltered("app_checks","on_map","1","groupid",$liu_groups,"*","id","ASC");
	$servers = getTableFiltered("app_servers","on_map","1","groupid",$liu_groups,"*","id","ASC");
	$websites = getTableFiltered("app_websites","on_map","1","groupid",$liu_groups,"*","id","ASC");
}

// SERVERS
if ($route == "servers") {
	isAuthorized("viewServers");
	//$servers = getTable("app_servers","*","id","ASC");

	$pageTitle = __("Servers");
}

if ($route == "servers/manage-linux") {
	isAuthorized("viewServers");
	$server = getRowById("app_servers", $_GET['id']);
	checkGroupRedirect($server['groupid']);
	$alerts = getTableFiltered("app_servers_alerts","serverid",$_GET['id'],"","","*","id","ASC");
	$incidents = getTableFiltered("app_servers_incidents","serverid",$_GET['id'],"","","*","id","DESC");
	$unresolved_incidents = getTableFiltered("app_servers_incidents","serverid",$_GET['id'],"status[!]","1","*","id","ASC");
	$unresolved_status = "primary";
	foreach($unresolved_incidents as $incident) {
		if($incident['status'] == 2) $unresolved_status = 'warning';
		if($incident['status'] == 3) $unresolved_status = 'danger';
	}

	$latest = Server::latestData($_GET['id']);

	$start = $_SESSION['range_start'];
	$end = $_SESSION['range_end'];


	$all_history = $database->select("app_servers_history", "*", [ "AND" => ["serverid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);

	// date range adjustment if no data for the given range
	if(empty($all_history) && !empty($latest) && $_SESSION['range_type'] == "auto") {
		$end = $latest['timestamp'];
		$start = date("Y-m-d H:i:s", strtotime('-3 hours',strtotime($end)));

		$_SESSION['range_start'] = $start;
		$_SESSION['range_end'] = $end;
		$all_history = $database->select("app_servers_history", "*", [ "AND" => ["serverid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}

	$all_history_count = count($all_history);
	$max_entries = 50;


	if($all_history_count <= $max_entries) {
		$history = $all_history;
	} else {
		$interval = floor($all_history_count/$max_entries);
		$history = array();

		$i = 0;
		foreach($all_history as $item) {
			if($i == $interval) { array_push($history, $item); $i = 0; }
			else $i++;
		}

		//$history = $database->select("app_servers_history", "*", [ "id" => $fetch_history ] );
	}

	$charts = array();
	if(!empty($history)) {
		$i = 0;
		foreach($history as $item) {
			$item['data'] = gzuncompress($item['data']);
			$label = date("Y-m-d H:i",strtotime($item['timestamp']));

			// ram
			$charts['ram'][$i]['date'] = $label;
			$charts['ram'][$i]['used'] = round((Server::extractData('ram_total', $item['data'], true)-Server::extractData('ram_free', $item['data'], true))/1024);
			$charts['ram'][$i]['caches'] = round(Server::extractData('ram_caches', $item['data'], true)/1024);
			$charts['ram'][$i]['buffers'] = round(Server::extractData('ram_buffers', $item['data'], true)/1024);
			$charts['ram'][$i]['real'] = $charts['ram'][$i]['used'] - $charts['ram'][$i]['caches'] - $charts['ram'][$i]['buffers'];

			// swap
			$charts['swap'][$i]['date'] = $label;
			$charts['swap'][$i]['used'] = round(Server::extractData('swap_usage', $item['data'], true)/1024);

			// ping
			$charts['ping'][$i]['date'] = $label;
			$charts['ping'][$i]['latency'] = Server::extractData('ping_latency', $item['data'], true);

			// connections
			$charts['connections'][$i]['date'] = $label;
			$charts['connections'][$i]['ssh'] = Server::extractData('ssh_sessions', $item['data'], true);
			$charts['connections'][$i]['all'] = Server::extractData('active_connections', $item['data'], true);

			// load
			$cpu_load = explode(',', Server::extractData('cpu_load', $item['data']));
			$charts['load'][$i]['date'] = $label;
			$charts['load'][$i]['1min'] = $cpu_load[0];
			$charts['load'][$i]['5min'] = $cpu_load[1];
			$charts['load'][$i]['15min'] = $cpu_load[2];

			// disks
			$disks = explode(";", Server::extractData('disks', $item['data'])); array_pop($disks);
			$charts['disks'][$i]['date'] = $label;
			$charts['disks_keys'] = array();
			foreach ($disks as $row) {
				$cells = explode(",", $row);
				$charts['disks'][$i][$cells[6]] = str_replace("%", "", $cells[5]);
				array_push($charts['disks_keys'], $cells[6]);
			}

			// inodes
			$disks_inodes = explode(";", Server::extractData('disks_inodes', $item['data'])); array_pop($disks_inodes);
			$charts['disks_inodes'][$i]['date'] = $label;
			$charts['disks_inodes_keys'] = array();
			foreach ($disks_inodes as $row) {
				$cells = explode(",", $row);
				$charts['disks_inodes'][$i][$cells[5]] = str_replace("%", "", $cells[4]);
				array_push($charts['disks_inodes_keys'], $cells[5]);
			}

			// network speed
			$all_interfaces = explode(";", Server::extractData('all_interfaces', $item['data'])); array_pop($all_interfaces);
			$all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $item['data'])); array_pop($all_interfaces_current);
			$ifcount = count($all_interfaces);

			$charts['netspeed'][$i]['date'] = $label;
			$charts['netspeed_keys'] = array();

			for ($x = 0; $x < $ifcount; $x++) {
				$interface_prev = explode(",", $all_interfaces[$x]);
				$interface_curr = explode(",", $all_interfaces_current[$x]);

				$charts['netspeed'][$i][$interface_prev[0]] = round((($interface_curr[1] + $interface_curr[2]) - ($interface_prev[1] + $interface_prev[2]))/1048576,4);

				array_push($charts['netspeed_keys'], $interface_prev[0]);
			}

			// cpu
			$cpu_info = explode(";", Server::extractData('cpu_info', $item['data'])); array_pop($cpu_info);
			$cpu_info_current = explode(";", Server::extractData('cpu_info_current', $item['data'])); array_pop($cpu_info_current);
			$cpucount = count($cpu_info); // aggregated + actual number of cores

			$stats = Server::cpuAllStats(Server::extractData('cpu_info_current', $item['data']),Server::extractData('cpu_info', $item['data']));

			for ($x = 0; $x < $cpucount; $x++) {
				$charts['cpu'][$x][$i]['date'] = $label;

				$charts['cpu'][$x][$i]['user'] = $stats['cpu'][$x]['user'];
				$charts['cpu'][$x][$i]['nice'] = $stats['cpu'][$x]['nice'];
				$charts['cpu'][$x][$i]['system'] = $stats['cpu'][$x]['system'];
				$charts['cpu'][$x][$i]['idle'] = $stats['cpu'][$x]['idle'];
				$charts['cpu'][$x][$i]['iowait'] = $stats['cpu'][$x]['iowait'];
				$charts['cpu'][$x][$i]['irq'] = $stats['cpu'][$x]['irq'];
				$charts['cpu'][$x][$i]['softirq'] = $stats['cpu'][$x]['softirq'];
				$charts['cpu'][$x][$i]['steal'] = $stats['cpu'][$x]['steal'];

				$charts['cpu'][$x][$i]['guest'] = $stats['cpu'][$x]['guest'];
				$charts['cpu'][$x][$i]['guestnice'] = $stats['cpu'][$x]['guestnice'];

				$charts['cpu'][$x][$i]['usage'] = $stats['cpu'][$x]['usage'];
			}



			$i++;
		}

	}

	$pageTitle = $server['name'];
}



if ($route == "servers/manage-windows") {
	isAuthorized("viewServers");
	$server = getRowById("app_servers", $_GET['id']);
	checkGroupRedirect($server['groupid']);
	$alerts = getTableFiltered("app_servers_alerts","serverid",$_GET['id'],"","","*","id","ASC");
	$incidents = getTableFiltered("app_servers_incidents","serverid",$_GET['id'],"","","*","id","DESC");
	$unresolved_incidents = getTableFiltered("app_servers_incidents","serverid",$_GET['id'],"status[!]","1","*","id","ASC");
	$unresolved_status = "primary";
	foreach($unresolved_incidents as $incident) {
		if($incident['status'] == 2) $unresolved_status = 'warning';
		if($incident['status'] == 3) $unresolved_status = 'danger';
	}

	$latest = Server::latestData($_GET['id']);

	$start = $_SESSION['range_start'];
	$end = $_SESSION['range_end'];


	$all_history = $database->select("app_servers_history", "*", [ "AND" => ["serverid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);

	// date range adjustment if no data for the given range
	if(empty($all_history) && !empty($latest) && $_SESSION['range_type'] == "auto") {
		$end = $latest['timestamp'];
		$start = date("Y-m-d H:i:s", strtotime('-3 hours',strtotime($end)));

		$_SESSION['range_start'] = $start;
		$_SESSION['range_end'] = $end;
		$all_history = $database->select("app_servers_history", "*", [ "AND" => ["serverid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}

	$all_history_count = count($all_history);
	$max_entries = 50;


	if($all_history_count <= $max_entries) {
		$history = $all_history;
	} else {
		$interval = floor($all_history_count/$max_entries);
		$history = array();

		$i = 0;
		foreach($all_history as $item) {
			if($i == $interval) { array_push($history, $item); $i = 0; }
			else $i++;
		}

		//$history = $database->select("app_servers_history", "*", [ "id" => $fetch_history ] );
	}

	$charts = array();
	if(!empty($history)) {
		$i = 0;
		foreach($history as $item) {
			$item['data'] = gzuncompress($item['data']);
			$label = date("Y-m-d H:i",strtotime($item['timestamp']));

			// ram OK
			$charts['ram'][$i]['date'] = $label;
			$charts['ram'][$i]['used'] = round((Server::extractData('ram_total', $item['data'], true)-Server::extractData('ram_free', $item['data'], true))/1048576);
			$charts['ram'][$i]['caches'] = 0;
			$charts['ram'][$i]['buffers'] = 0;
			$charts['ram'][$i]['real'] = $charts['ram'][$i]['used'] - $charts['ram'][$i]['caches'] - $charts['ram'][$i]['buffers'];

			// swap OK
			$charts['swap'][$i]['date'] = $label;
			$charts['swap'][$i]['used'] = round(Server::extractData('swap_usage', $item['data'], true)/1048576);

			// ping OK
			$charts['ping'][$i]['date'] = $label;
			$charts['ping'][$i]['latency'] = Server::extractData('ping_latency', $item['data'], true);

			// disks OK
			$filesystems = json_decode( Server::extractData('filesystems', $item['data'], true), true);
			$charts['disks'][$i]['date'] = $label;
			$charts['disks_keys'] = array();
			foreach ($filesystems as $filesystem) {
				if(isset($filesystem['size'])) {
					$cells = explode(",", $row);
					$charts['disks'][$i][$filesystem['fs']] = round($filesystem['use'],2);
					array_push($charts['disks_keys'], $filesystem['fs']);
				}
			}

			// network speed
			$net_stats = json_decode( Server::extractData('net_stats', $item['data'], true), true);
			$charts['netspeed'][$i]['date'] = $label;
			$charts['netspeed_keys'] = array();

			foreach($net_stats as $net_stat) {

				$charts['netspeed'][$i][$net_stat['iface']] = round(($net_stat['rx_sec'] - $net_stat['tx_sec'])/1048576,4);

				array_push($charts['netspeed_keys'], $net_stat['iface']);
			}

			// cpu
			$cpus = json_decode( Server::extractData('cpu_load', $item['data'], true), true);

			$charts['cpu'][0][$i]['date'] = $label;
			$charts['cpu'][0][$i]['user'] = round($cpus['currentload_user'],2);
			$charts['cpu'][0][$i]['system'] = round($cpus['currentload_system'],2);
			$charts['cpu'][0][$i]['nice'] = round($cpus['currentload_nice'],2);
			$charts['cpu'][0][$i]['idle'] = round($cpus['currentload_idle'],2);
			$charts['cpu'][0][$i]['irq'] = round($cpus['currentload_irq'],2);
			$charts['cpu'][0][$i]['usage'] = round($cpus['currentload'],2);

			$x=1;
			foreach($cpus['cpus'] as $cpu) {
				$charts['cpu'][$x][$i]['date'] = $label;

				$charts['cpu'][$x][$i]['user'] = round($cpu['load_user'],2);
				$charts['cpu'][$x][$i]['system'] = round($cpu['load_system'],2);
				$charts['cpu'][$x][$i]['nice'] = round($cpu['load_nice'],2);
				$charts['cpu'][$x][$i]['idle'] = round($cpu['load_idle'],2);
				$charts['cpu'][$x][$i]['irq'] = round($cpu['load_irq'],2);
				$charts['cpu'][$x][$i]['usage'] = round($cpu['load'],2);

				$x++;
			}



			$i++;
		}

	}

	$pageTitle = $server['name'];
}


// WEBSITES
if ($route == "websites") {
	isAuthorized("viewWebsites");

	$pageTitle = __("Websites");
}

if ($route == "websites/manage") {
	isAuthorized("viewWebsites");
	$website = getRowById("app_websites", $_GET['id']);
	checkGroupRedirect($website['groupid']);
	$alerts = getTableFiltered("app_websites_alerts","websiteid",$_GET['id'],"","","*","id","ASC");
	$incidents = getTableFiltered("app_websites_incidents","websiteid",$_GET['id'],"","","*","id","DESC");
	$unresolved_incidents = getTableFiltered("app_websites_incidents","websiteid",$_GET['id'],"status[!]","1","*","id","ASC");
	$detailed_log = $database->select("app_websites_history", "*", [ "websiteid" => $_GET['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => 100 ]);

	$unresolved_status = "primary";
	foreach($unresolved_incidents as $incident) {
		if($incident['status'] == 2) $unresolved_status = 'warning';
		if($incident['status'] == 3) $unresolved_status = 'danger';
	}

	$latest = Website::latestData($_GET['id']);

	$start = $_SESSION['range_start'];
	$end = $_SESSION['range_end'];

	$all_history = $database->select("app_websites_history","id", [ "AND" => ["websiteid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);

	// date range adjustment if no data for the given range
	if(empty($all_history) && !empty($latest) && $_SESSION['range_type'] == "auto") {
		$end = $latest['timestamp'];
		$start = date("Y-m-d H:i:s", strtotime('-3 hours',strtotime($end)));

		$_SESSION['range_start'] = $start;
		$_SESSION['range_end'] = $end;
		$all_history = $database->select("app_websites_history","id", [ "AND" => ["websiteid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}

	$all_history_count = count($all_history);


	$max_entries = 50;
	$interval = floor($all_history_count/$max_entries);
	$fetch_history = array();

	$i = 0;
	foreach($all_history as $item) {
		if($i == $interval) { array_push($fetch_history, $item); $i = 0; }
		else $i++;
	}

	if($all_history < $max_entries) {
		$history = $database->select("app_websites_history","*", [ "AND" => ["websiteid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}
	else {
		$history = $database->select("app_websites_history", "*", [ "id" => $fetch_history ] );
	}


	$charts = array();
	if(!empty($history)) {
		$i = 0;
		foreach($history as $item) {
			$label = date("Y-m-d H:i",strtotime($item['timestamp']));

			// performance
			$charts['performance'][$i]['date'] = $label;
			$charts['performance'][$i]['latency'] = $item['latency'];

			$i++;
		}

	}



	$pageTitle = $website['name'];
}


// CHECKS
if ($route == "checks") {
	isAuthorized("viewChecks");

	$pageTitle = __("Checks");
}

if ($route == "checks/manage") {
	isAuthorized("viewChecks");
	$check = getRowById("app_checks", $_GET['id']);
	checkGroupRedirect($check['groupid']);
	$alerts = getTableFiltered("app_checks_alerts","checkid",$_GET['id'],"","","*","id","ASC");
	$incidents = getTableFiltered("app_checks_incidents","checkid",$_GET['id'],"","","*","id","DESC");
	$unresolved_incidents = getTableFiltered("app_checks_incidents","checkid",$_GET['id'],"status[!]","1","*","id","ASC");
	$detailed_log = $database->select("app_checks_history", "*", [ "checkid" => $_GET['id'], "ORDER" => ['id' => 'DESC'], "LIMIT" => 100 ]);

	$unresolved_status = "primary";
	foreach($unresolved_incidents as $incident) {
		if($incident['status'] == 2) $unresolved_status = 'warning';
		if($incident['status'] == 3) $unresolved_status = 'danger';
	}

	$latencyunit = __('ms');
	if($check['type'] == "dns") $latencyunit = __('s');
	if($check['type'] == "blacklist") $latencyunit = __('s');



	$latest = Check::latestData($_GET['id']);

	if($check['type'] == "blacklist") {
		$blacklists = getTable("app_dnsbls");
		$listedin = unserialize($latest['statuscode']);
	}


	$start = $_SESSION['range_start'];
	$end = $_SESSION['range_end'];

	$all_history = $database->select("app_checks_history","id", [ "AND" => ["checkid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);

	// date range adjustment if no data for the given range
	if(empty($all_history) && !empty($latest) && $_SESSION['range_type'] == "auto") {
		$end = $latest['timestamp'];
		$start = date("Y-m-d H:i:s", strtotime('-3 hours',strtotime($end)));

		$_SESSION['range_start'] = $start;
		$_SESSION['range_end'] = $end;
		$all_history = $database->select("app_checks_history","id", [ "AND" => ["checkid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}

	$all_history_count = count($all_history);


	$max_entries = 50;
	$interval = floor($all_history_count/$max_entries);
	$fetch_history = array();

	$i = 0;
	foreach($all_history as $item) {
		if($i == $interval) { array_push($fetch_history, $item); $i = 0; }
		else $i++;
	}

	if($all_history < $max_entries) {
		$history = $database->select("app_checks_history","*", [ "AND" => ["checkid" => $_GET['id'], "timestamp[>=]" => $start,"timestamp[<=]" => $end ] ]);
	}
	else {
		$history = $database->select("app_checks_history", "*", [ "id" => $fetch_history ] );
	}


	$charts = array();
	if(!empty($history)) {
		$i = 0;
		foreach($history as $item) {
			$label = date("Y-m-d H:i",strtotime($item['timestamp']));

			// performance
			$charts['performance'][$i]['date'] = $label;
			$charts['performance'][$i]['latency'] = $item['latency'];

			$i++;
		}

	}


	$pageTitle = $check['name'];
}


// ALERTING - CONTACTS
if ($route == "alerting/contacts") {
	isAuthorized("viewContacts");
	$contacts = getTable("app_contacts","*","id","ASC");
	$pageTitle = __("Contacts");
}


// ALERTING - LOG
if ($route == "alerting/log") {
	isAuthorized("viewAlertLogs");

	$pageTitle = __("Alert Log");
}


// PAGES
if ($route == "pages") {
	isAuthorized("viewPages");
	$pages = getTable("app_pages","*","id","ASC");
	$pageTitle = __("Pages");
}

if ($route == "publicpage") {

	$raise_deprecated = false;

	if(isset($_GET['id'])) {
		$page = getRowById("app_pages",$_GET['id']);
		$raise_deprecated = true;
	}

	if(isset($_GET['key'])) {
		$page = $database->get("app_pages", "*", ["pagekey" => $_GET['key']]);
	}

	if($page) {
		$selected_servers = unserialize($page['servers']);
		if(!$selected_servers) $selected_servers = [];
		if(empty($selected_servers)) $selected_servers = [];
		$servers = getTableFiltered("app_servers","id",$selected_servers,"","","*","id","ASC");

		$selected_websites = unserialize($page['websites']);
		if(!$selected_websites) $selected_websites = [];
		if(empty($selected_websites)) $selected_websites = [];
		$websites = getTableFiltered("app_websites","id",$selected_websites,"","","*","id","ASC");

		$selected_checks = unserialize($page['checks']);
		if(!$selected_checks) $selected_checks = [];
		if(empty($selected_checks)) $selected_checks = [];
		$checks = getTableFiltered("app_checks","id",$selected_checks,"","","*","id","ASC");


		$pageTitle = $page['name'];
	}


}


// USERS
if ($route == "system/users") {
	isAuthorized("viewUsers");
	$users = getTable("core_users","*","id","ASC");
	$pageTitle = __("Users");
}

if ($route == "system/users/edit") {
	isAuthorized("editUser");
	$groups = getTable("app_groups");
	$user = getRowById("core_users",$_GET['id']);
	$current_groups = unserialize($user['groups']);
	$languages = getTable("core_languages");
	$roles = getTable("core_roles");
	$pageTitle = __("Edit User");
}


// ROLES
if ($route == "system/roles") { isAuthorized("viewRoles"); $roles = getTable("core_roles"); $pageTitle = __("Roles"); }
if ($route == "system/roles/add") {
	isAuthorized("addRole");

	$roleperms = [];
	$pageTitle = __("Add Role");
}
if ($route == "system/roles/edit") {
	isAuthorized("editRole");
	$role = getRowById("core_roles",$_GET['id']);
	$roleperms = unserialize($role['perms']);
	$pageTitle = __("Edit Role");
}


// GROUPS
if ($route == "system/groups") {
	isAuthorized("viewGroups");
	$groups = getTable("app_groups","*","id","ASC");
	$pageTitle = __("Groups");
}


// LOGS
if ($route == "system/logs") {
	isAuthorized("viewLogs");

	$pageTitle = __("Logs");
}


// SYSTEM SETTINGS
if ($route == "system/settings") {
	isAuthorized("manageSettings");
	$languages = getTable("core_languages");
	$contacts = getTable("app_contacts");
	$selected_contacts = unserialize(getConfigValue("default_contacts"));
	if(!$selected_contacts) $selected_contacts = [];
	$pageTitle = __("Settings");

	$tzlist = array (
	    '(UTC-11:00) Midway Island' => 'Pacific/Midway',
	    '(UTC-11:00) Samoa' => 'Pacific/Samoa',
	    '(UTC-10:00) Hawaii' => 'Pacific/Honolulu',
	    '(UTC-09:00) Alaska' => 'US/Alaska',
	    '(UTC-08:00) Pacific Time (US &amp; Canada)' => 'America/Los_Angeles',
	    '(UTC-08:00) Tijuana' => 'America/Tijuana',
	    '(UTC-07:00) Arizona' => 'US/Arizona',
	    '(UTC-07:00) Chihuahua' => 'America/Chihuahua',
	    '(UTC-07:00) La Paz' => 'America/Chihuahua',
	    '(UTC-07:00) Mazatlan' => 'America/Mazatlan',
	    '(UTC-07:00) Mountain Time (US &amp; Canada)' => 'US/Mountain',
	    '(UTC-06:00) Central America' => 'America/Managua',
	    '(UTC-06:00) Central Time (US &amp; Canada)' => 'US/Central',
	    '(UTC-06:00) Guadalajara' => 'America/Mexico_City',
	    '(UTC-06:00) Mexico City' => 'America/Mexico_City',
	    '(UTC-06:00) Monterrey' => 'America/Monterrey',
	    '(UTC-06:00) Saskatchewan' => 'Canada/Saskatchewan',
	    '(UTC-05:00) Bogota' => 'America/Bogota',
	    '(UTC-05:00) Eastern Time (US &amp; Canada)' => 'US/Eastern',
	    '(UTC-05:00) Indiana (East)' => 'US/East-Indiana',
	    '(UTC-05:00) Lima' => 'America/Lima',
	    '(UTC-05:00) Quito' => 'America/Bogota',
	    '(UTC-04:00) Atlantic Time (Canada)' => 'Canada/Atlantic',
	    '(UTC-04:30) Caracas' => 'America/Caracas',
	    '(UTC-04:00) La Paz' => 'America/La_Paz',
	    '(UTC-04:00) Santiago' => 'America/Santiago',
	    '(UTC-03:30) Newfoundland' => 'Canada/Newfoundland',
	    '(UTC-03:00) Brasilia' => 'America/Sao_Paulo',
	    '(UTC-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
	    '(UTC-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
	    '(UTC-03:00) Greenland' => 'America/Godthab',
	    '(UTC-02:00) Mid-Atlantic' => 'America/Noronha',
	    '(UTC-01:00) Azores' => 'Atlantic/Azores',
	    '(UTC-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
	    '(UTC+00:00) Casablanca' => 'Africa/Casablanca',
	    '(UTC+00:00) Edinburgh' => 'Europe/London',
	    '(UTC+00:00) Greenwich Mean Time : Dublin' => 'Etc/Greenwich',
	    '(UTC+00:00) Lisbon' => 'Europe/Lisbon',
	    '(UTC+00:00) London' => 'Europe/London',
	    '(UTC+00:00) Monrovia' => 'Africa/Monrovia',
	    '(UTC+00:00) UTC' => 'UTC',
	    '(UTC+01:00) Amsterdam' => 'Europe/Amsterdam',
	    '(UTC+01:00) Belgrade' => 'Europe/Belgrade',
	    '(UTC+01:00) Berlin' => 'Europe/Berlin',
	    '(UTC+01:00) Bern' => 'Europe/Berlin',
	    '(UTC+01:00) Bratislava' => 'Europe/Bratislava',
	    '(UTC+01:00) Brussels' => 'Europe/Brussels',
	    '(UTC+01:00) Budapest' => 'Europe/Budapest',
	    '(UTC+01:00) Copenhagen' => 'Europe/Copenhagen',
	    '(UTC+01:00) Ljubljana' => 'Europe/Ljubljana',
	    '(UTC+01:00) Madrid' => 'Europe/Madrid',
	    '(UTC+01:00) Paris' => 'Europe/Paris',
	    '(UTC+01:00) Prague' => 'Europe/Prague',
	    '(UTC+01:00) Rome' => 'Europe/Rome',
	    '(UTC+01:00) Sarajevo' => 'Europe/Sarajevo',
	    '(UTC+01:00) Skopje' => 'Europe/Skopje',
	    '(UTC+01:00) Stockholm' => 'Europe/Stockholm',
	    '(UTC+01:00) Vienna' => 'Europe/Vienna',
	    '(UTC+01:00) Warsaw' => 'Europe/Warsaw',
	    '(UTC+01:00) West Central Africa' => 'Africa/Lagos',
	    '(UTC+01:00) Zagreb' => 'Europe/Zagreb',
	    '(UTC+02:00) Athens' => 'Europe/Athens',
	    '(UTC+02:00) Bucharest' => 'Europe/Bucharest',
	    '(UTC+02:00) Cairo' => 'Africa/Cairo',
	    '(UTC+02:00) Harare' => 'Africa/Harare',
	    '(UTC+02:00) Helsinki' => 'Europe/Helsinki',
	    '(UTC+02:00) Istanbul' => 'Europe/Istanbul',
	    '(UTC+02:00) Jerusalem' => 'Asia/Jerusalem',
	    '(UTC+02:00) Kyiv' => 'Europe/Helsinki',
	    '(UTC+02:00) Pretoria' => 'Africa/Johannesburg',
	    '(UTC+02:00) Riga' => 'Europe/Riga',
	    '(UTC+02:00) Sofia' => 'Europe/Sofia',
	    '(UTC+02:00) Tallinn' => 'Europe/Tallinn',
	    '(UTC+02:00) Vilnius' => 'Europe/Vilnius',
	    '(UTC+03:00) Baghdad' => 'Asia/Baghdad',
	    '(UTC+03:00) Kuwait' => 'Asia/Kuwait',
	    '(UTC+03:00) Minsk' => 'Europe/Minsk',
	    '(UTC+03:00) Nairobi' => 'Africa/Nairobi',
	    '(UTC+03:00) Riyadh' => 'Asia/Riyadh',
	    '(UTC+03:00) Volgograd' => 'Europe/Volgograd',
	    '(UTC+03:30) Tehran' => 'Asia/Tehran',
	    '(UTC+04:00) Abu Dhabi' => 'Asia/Muscat',
	    '(UTC+04:00) Baku' => 'Asia/Baku',
	    '(UTC+04:00) Moscow' => 'Europe/Moscow',
	    '(UTC+04:00) Muscat' => 'Asia/Muscat',
	    '(UTC+04:00) St. Petersburg' => 'Europe/Moscow',
	    '(UTC+04:00) Tbilisi' => 'Asia/Tbilisi',
	    '(UTC+04:00) Yerevan' => 'Asia/Yerevan',
	    '(UTC+04:30) Kabul' => 'Asia/Kabul',
	    '(UTC+05:00) Islamabad' => 'Asia/Karachi',
	    '(UTC+05:00) Karachi' => 'Asia/Karachi',
	    '(UTC+05:00) Tashkent' => 'Asia/Tashkent',
	    '(UTC+05:30) Chennai' => 'Asia/Calcutta',
	    '(UTC+05:30) Kolkata' => 'Asia/Kolkata',
	    '(UTC+05:30) Mumbai' => 'Asia/Calcutta',
	    '(UTC+05:30) New Delhi' => 'Asia/Calcutta',
	    '(UTC+05:30) Sri Jayawardenepura' => 'Asia/Calcutta',
	    '(UTC+05:45) Kathmandu' => 'Asia/Katmandu',
	    '(UTC+06:00) Almaty' => 'Asia/Almaty',
	    '(UTC+06:00) Astana' => 'Asia/Dhaka',
	    '(UTC+06:00) Dhaka' => 'Asia/Dhaka',
	    '(UTC+06:00) Ekaterinburg' => 'Asia/Yekaterinburg',
	    '(UTC+06:30) Rangoon' => 'Asia/Rangoon',
	    '(UTC+07:00) Bangkok' => 'Asia/Bangkok',
	    '(UTC+07:00) Hanoi' => 'Asia/Bangkok',
	    '(UTC+07:00) Jakarta' => 'Asia/Jakarta',
	    '(UTC+07:00) Novosibirsk' => 'Asia/Novosibirsk',
	    '(UTC+08:00) Beijing' => 'Asia/Hong_Kong',
	    '(UTC+08:00) Chongqing' => 'Asia/Chongqing',
	    '(UTC+08:00) Hong Kong' => 'Asia/Hong_Kong',
	    '(UTC+08:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
	    '(UTC+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
	    '(UTC+08:00) Perth' => 'Australia/Perth',
	    '(UTC+08:00) Singapore' => 'Asia/Singapore',
	    '(UTC+08:00) Taipei' => 'Asia/Taipei',
	    '(UTC+08:00) Ulaan Bataar' => 'Asia/Ulan_Bator',
	    '(UTC+08:00) Urumqi' => 'Asia/Urumqi',
	    '(UTC+09:00) Irkutsk' => 'Asia/Irkutsk',
	    '(UTC+09:00) Osaka' => 'Asia/Tokyo',
	    '(UTC+09:00) Sapporo' => 'Asia/Tokyo',
	    '(UTC+09:00) Seoul' => 'Asia/Seoul',
	    '(UTC+09:00) Tokyo' => 'Asia/Tokyo',
	    '(UTC+09:30) Adelaide' => 'Australia/Adelaide',
	    '(UTC+09:30) Darwin' => 'Australia/Darwin',
	    '(UTC+10:00) Brisbane' => 'Australia/Brisbane',
	    '(UTC+10:00) Canberra' => 'Australia/Canberra',
	    '(UTC+10:00) Guam' => 'Pacific/Guam',
	    '(UTC+10:00) Hobart' => 'Australia/Hobart',
	    '(UTC+10:00) Melbourne' => 'Australia/Melbourne',
	    '(UTC+10:00) Port Moresby' => 'Pacific/Port_Moresby',
	    '(UTC+10:00) Sydney' => 'Australia/Sydney',
	    '(UTC+10:00) Yakutsk' => 'Asia/Yakutsk',
	    '(UTC+11:00) Vladivostok' => 'Asia/Vladivostok',
	    '(UTC+12:00) Auckland' => 'Pacific/Auckland',
	    '(UTC+12:00) Fiji' => 'Pacific/Fiji',
	    '(UTC+12:00) International Date Line West' => 'Pacific/Kwajalein',
	    '(UTC+12:00) Kamchatka' => 'Asia/Kamchatka',
	    '(UTC+12:00) Magadan' => 'Asia/Magadan',
	    '(UTC+12:00) Marshall Is.' => 'Pacific/Fiji',
	    '(UTC+12:00) New Caledonia' => 'Asia/Magadan',
	    '(UTC+12:00) Solomon Is.' => 'Asia/Magadan',
	    '(UTC+12:00) Wellington' => 'Pacific/Auckland',
	    '(UTC+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
	);

}

// PROFILE
if ($route == "profile") { $languages = getTable("core_languages"); $pageTitle = __("Profile"); }




?>
