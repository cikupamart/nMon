<?php

class Server extends App {


    public static function add($data) {
    	global $database;

    	// Sanitize inputs
    	$name = sanitizeInput($data['name']);
    	$groupid = sanitizeInt($data['groupid']);
    	$lat = sanitizeFloat($data['lat'] ?? 0);
    	$lng = sanitizeFloat($data['lng'] ?? 0);
    	$on_map = intval($data['on_map'] ?? 0);

    	$lastid = $database->insert("app_servers", [
            "groupid" => $groupid,
            "type" => $data['type'],
    		"name" => $name,
            "serverkey" => randomString(64),
            "status" => 0,
            "geodata" => "",
            "on_map" => $on_map,
            "lat" => $lat,
            "lng" => $lng
    	]);


        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "nodata",
            "comparison" => "==",
            "comparison_limit" => "",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "cpu",
            "comparison" => ">=",
            "comparison_limit" => "90",
            "occurrences" => 5,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "ram",
            "comparison" => ">=",
            "comparison_limit" => "95",
            "occurrences" => 5,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);

        $database->insert("app_servers_alerts", [
            "serverid" => $lastid,
            "type" => "disk",
            "comparison" => ">=",
            "comparison_limit" => "80",
            "occurrences" => 3,
            "contacts" => getConfigValue("default_contacts"),
            "status" => 1,
        ]);


    	if ($lastid == "0") { return "11"; } else {
            logSystem("Server Added - ID: " . $lastid);

            return "10";
        }


    }


    public static function edit($data) {
    	global $database;

    	// Sanitize inputs
    	$name = sanitizeInput($data['name']);
    	$groupid = sanitizeInt($data['groupid']);
    	$lat = sanitizeFloat($data['lat'] ?? 0);
    	$lng = sanitizeFloat($data['lng'] ?? 0);
    	$on_map = intval($data['on_map'] ?? 0);

    	$database->update("app_servers", [
            "groupid" => $groupid,
    		"name" => $name,
            "on_map" => $on_map,
            "lat" => $lat,
            "lng" => $lng
    	], [ "id" => sanitizeInt($data['id']) ]);
    	logSystem("Server Edited - ID: " . $data['id']);
    	return "20";
    }


    public static function delete($id) {
    	global $database;
    	$id = sanitizeInt($id);
        $database->delete("app_servers", [ "id" => $id ]);
        $database->delete("app_servers_alerts", [ "serverid" => $id ]);
        $database->delete("app_servers_history", [ "serverid" => $id ]);
        $database->delete("app_servers_incidents", [ "serverid" => $id ]);
    	logSystem("Server Deleted - ID: " . $id);
    	return "30";
    }



    ###############################
    ###         ALERTS          ###
    ###############################

    public static function addAlert($data) {
        global $database;
        $lastid = $database->insert("app_servers_alerts", [
            "serverid" => sanitizeInt($data['serverid']),
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => intval($data['occurrences']),
            "contacts" => serialize($data['contacts']),
            "status" => intval($data['status']),
        ]);
        if ($lastid == "0") { return "11"; } else { logSystem("Server Alert Added - ID: " . $lastid); return "10"; }
    }


    public static function editAlert($data) {
        global $database;
        $database->update("app_servers_alerts", [
            "serverid" => sanitizeInt($data['serverid']),
            "type" => $data['type'],
            "comparison" => $data['comparison'],
            "comparison_limit" => $data['comparison_limit'],
            "occurrences" => intval($data['occurrences']),
            "contacts" => serialize($data['contacts']),
            "status" => intval($data['status']),
        ], [ "id" => sanitizeInt($data['id']) ]);
        logSystem("Server Alert Edited - ID: " . $data['id']);
        return "20";
    }


    public static function deleteAlert($id) {
        global $database;
        $database->delete("app_servers_alerts", [ "id" => sanitizeInt($id) ]);
        logSystem("Server Alert Deleted - ID: " . $id);
        return "30";
    }

    public static function markIncident($id) {
        global $database;

        $database->update("app_servers_incidents", [
            "status" => 1,
            'end_time' => date('Y-m-d H:i:s')
        ], [ "id" => sanitizeInt($id) ]);

        logSystem("Server Incident Marked Resolved - ID: " . $id);
        return "20";
    }


    public static function uptimePercentage($serverid,$period) {
        global $database;
        $total_secs_down = 0;

        if($period == "24h") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-24 hours',strtotime($end)));
            $total_secs = 86400;
        }

        elseif($period == "7days") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-7 days',strtotime($end)));
            $total_secs = 604800;
        }

        elseif($period == "30days") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-30 days',strtotime($end)));
            $total_secs = 2592000;
        }

        elseif($period == "12months") {
            $end = date("Y-m-d H:i:s");
            $start = date("Y-m-d H:i:s", strtotime('-365 days',strtotime($end)));
            $total_secs = 31536000;
        }

        elseif($period == "selected") {
            $end = $_SESSION['range_end'];
            $start = $_SESSION['range_start'];
            $total_secs = strtotime($end) - strtotime($start);
        }

        else return 0;


        $incidents = $database->select("app_servers_incidents","*", [
            "AND" => [
                "serverid" => $serverid,
                "type" => "nodata",

            ]
        ]);

        foreach($incidents as $incident) {
            if($incident['end_time'] == "0000-00-00 00:00:00") $incident['end_time'] = date("Y-m-d H:i:s");

            if(
                // Start date is in first date range
                ($incident['start_time'] >= $start && $incident['start_time'] <= $end)
                ||
                // end date is in first date range
                ($incident['end_time'] >= $start && $incident['end_time'] <= $end)
            ) {

                if($incident['start_time'] <= $start) $incident['start_time'] = $start;
                if($incident['end_time'] >= $end) $incident['end_time'] = $end;

                $difference = strtotime($incident['end_time']) - strtotime($incident['start_time']);


                $total_secs_down = $total_secs_down + $difference;
            }
        }

        if($total_secs_down == 0) return 100;
        if($total_secs == $total_secs_down) return 0;

        $percentage = (($total_secs - $total_secs_down) / $total_secs) * 100;

        return round($percentage, 2);

    }


    public static function cpuPercentage($current=0,$last=0,$divider=1) {
        $diff = $current - $last;

        if($diff == 0) return 0;
        else return round((($diff/100)*100)/$divider, 2);
    }


    public static function cpuAllStats($cpu_info_current,$cpu_info) {
        $cpu_info = explode(";", $cpu_info); array_pop($cpu_info);
        $cpu_info_current = explode(";", $cpu_info_current); array_pop($cpu_info_current);
		$cpucount = count($cpu_info_current); // aggregated + actual number of cores

        $stats = array();

        for ($x = 0; $x < $cpucount; $x++) {

            $cpu_prev = explode(",", $cpu_info[$x]);
            $cpu_curr = explode(",", $cpu_info_current[$x]);

            if($cpu_curr[8] ?? '' == "") $cpu_curr[8] = 0; // kernels older than 2.6.11
            if($cpu_curr[9] ?? '' == "") $cpu_curr[9] = 0; // kernels older than 2.6.24
            if($cpu_curr[10] ?? '' == "") $cpu_curr[10] = 0; // kernels older than 2.6.24

            if($cpu_prev[8] ?? '' == "") $cpu_prev[8] = 0; // kernels older than 2.6.11
            if($cpu_prev[9] ?? '' == "") $cpu_prev[9] = 0; // kernels older than 2.6.24
            if($cpu_prev[10] ?? '' == "") $cpu_prev[10] = 0; // kernels older than 2.6.24


            $dif['user'] = ($cpu_curr[1] ?? 0) - ($cpu_prev[1] ?? 0);
            $dif['nice'] = ($cpu_curr[2] ?? 0) - ($cpu_prev[2] ?? 0);
            $dif['system'] = ($cpu_curr[3] ?? 0) - ($cpu_prev[3] ?? 0);
            $dif['idle'] = ($cpu_curr[4] ?? 0) - ($cpu_prev[4] ?? 0);
            $dif['iowait'] = ($cpu_curr[5] ?? 0) - ($cpu_prev[5] ?? 0);
            $dif['irq'] = ($cpu_curr[6] ?? 0) - ($cpu_prev[6] ?? 0);
            $dif['softirq'] = ($cpu_curr[7] ?? 0) - ($cpu_prev[7] ?? 0);
            $dif['steal'] = ($cpu_curr[8] ?? 0) - ($cpu_prev[8] ?? 0);
            $dif['guest'] = ($cpu_curr[9] ?? 0) - ($cpu_prev[9] ?? 0);
            $dif['guestnice'] = ($cpu_curr[10] ?? 0) - ($cpu_prev[10] ?? 0);

            $virttime = $dif['guest'] + $dif['guestnice'];

            $total = array_sum($dif);
            $realtotal = array_sum($dif) - $virttime;
            if($realtotal == 0) $realtotal = 0.01; // pre division by zero


            $stats['cpu'][$x]['user'] = round( $dif['user'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['nice'] = round( $dif['nice'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['system'] = round( $dif['system'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['idle'] = round( $dif['idle'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['iowait'] = round( $dif['iowait'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['irq'] = round( $dif['irq'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['softirq'] = round( $dif['softirq'] / $realtotal * 100, 2 );
            $stats['cpu'][$x]['steal'] = round( $dif['steal'] / $realtotal * 100, 2 );

            $stats['cpu'][$x]['guest'] = round( $dif['guest'] / $total * 100, 2 );
            $stats['cpu'][$x]['guestnice'] = round( $dif['guestnice'] / $total * 100, 2 );

            $stats['cpu'][$x]['usage'] = 100 - $stats['cpu'][$x]['idle'] - $stats['cpu'][$x]['iowait'];

        }

        return $stats;

    }

    public static function quickStats($data, $platform="linux") {
        $qstats = array();

        if($platform == "linux") {
            // disk usage
            $disktotal = 0; $diskused = 0;
            $disks_data = explode(";", Server::extractData('disks', $data, true)); array_pop($disks_data); // delete last
            $disks_count  = count($disks_data);
            for ($x = 0; $x < $disks_count; $x++) {
                $disk_data = explode(",", $disks_data[$x]);
                $disktotal += $disk_data[2] ?? 0;
                $diskused += $disk_data[3] ?? 0;
            }
            $qstats['totaldiskusedp'] = $disktotal > 0 ? round( ($diskused/$disktotal)*100 ) : 0;

            //ram usage
            $qstats['ramtotal'] = Server::extractData('ram_total', $data, true);
            $qstats['ramcaches'] = Server::extractData('ram_caches', $data, true);
            $qstats['rambuffers'] = Server::extractData('ram_buffers', $data, true);

            $qstats['ramfree'] = Server::extractData('ram_free', $data, true) + $qstats['ramcaches'] + $qstats['rambuffers'];
            $qstats['ramused'] = Server::extractData('ram_total', $data, true) - Server::extractData('ram_free', $data, true);
            $qstats['ramreal'] = $qstats['ramused'] - $qstats['ramcaches'] - $qstats['rambuffers'];


            //cpu
            $cpustats = Server::cpuAllStats(Server::extractData('cpu_info_current', $data), Server::extractData('cpu_info', $data));
            $qstats['cpuused'] = $cpustats['cpu'][0]['usage'] ?? 0;


            // Load
            $loadparts = explode(",", Server::extractData('cpu_load', $data, true));
            $qstats['load1'] = $loadparts[0] ?? 0;
            $qstats['load5'] = $loadparts[1] ?? 0;
            $qstats['load15'] = $loadparts[2] ?? 0;

            // Net
            $totalin = 0; $totalout = 0;
            $all_interfaces = explode(";", Server::extractData('all_interfaces', $data)); array_pop($all_interfaces);
            $all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $data)); array_pop($all_interfaces_current);
            $interface_count  = count($all_interfaces_current);
            for ($x = 0; $x < $interface_count; $x++) {
                $interface = explode(",", $all_interfaces[$x] ?? ''); $interface_current = explode(",", $all_interfaces_current[$x] ?? '');
                $totalin += ($interface_current[1] ?? 0)  - ($interface[1] ?? 0);
                $totalout += ($interface_current[2] ?? 0)  - ($interface[2] ?? 0);
            }
            $qstats['totalin'] = $totalin;
            $qstats['totalout'] = $totalout;

        } elseif ($platform == "windows") {


            // disk usage
            $disktotal = 0; $diskused = 0;
            $filesystems = json_decode( Server::extractData('filesystems', $data, true), true) ?? [];
            foreach($filesystems as $filesystem) {
                if(isset($filesystem['size'])) {
                    $disktotal += $filesystem['size'];
                    $diskused += $filesystem['used'];
                }
            }
            $qstats['totaldiskusedp'] = $disktotal > 0 ? round( ($diskused/$disktotal)*100 ) : 0;

            //ram usage
            $qstats['ramtotal'] = Server::extractData('ram_total', $data, true);
            $qstats['ramcaches'] = 0;
            $qstats['rambuffers'] = 0;

            $qstats['ramfree'] = Server::extractData('ram_free', $data, true);
            $qstats['ramused'] = Server::extractData('ram_usage', $data, true);
            $qstats['ramreal'] = $qstats['ramused'] - $qstats['ramcaches'] - $qstats['rambuffers'];


            //cpu
            $cpu_load = json_decode( Server::extractData('cpu_load', $data, true), true) ?? [];
            $qstats['cpuused'] = round($cpu_load['currentload'] ?? 0, 2);

            // Load
            $qstats['load1'] = $cpu_load['avgload'] ?? 0;
            $qstats['load5'] = $cpu_load['avgload'] ?? 0;
            $qstats['load15'] = $cpu_load['avgload'] ?? 0;


            // Net
            $totalin = 0; $totalout = 0;
            $net_stats = json_decode( Server::extractData('net_stats', $data, true), true) ?? [];
            foreach($net_stats as $net_stat) {
                if(($net_stat['rx_sec'] ?? 0) >= 0) {
                    $totalin += $net_stat['rx_sec'] ?? 0;
                    $totalout += $net_stat['tx_sec'] ?? 0;
                }
            }
            $qstats['totalin'] = $totalin;
            $qstats['totalout'] = $totalout;


        }



        return $qstats;

    }

    public static function extractData($key, $data, $trim = true) {
    	$start = "{" . $key . "}";
    	$end = "{/" . $key . "}";

    	$string = " " . $data;
    	$ini = strpos($string, $start);
    	if ($ini == 0) return "";
    	$ini += strlen($start);
    	$len = strpos($string, $end, $ini) - $ini;

    	if ($trim == false) return substr($string, $ini, $len);
    	if ($trim == true) return trim(substr($string, $ini, $len));

    }

    public static function latestData($serverid) {
    	global $database;

        $latestentryid = $database->max("app_servers_history", "id", ["serverid" => $serverid]);
    	$latest = $database->get("app_servers_history", "*", ["id" => $latestentryid]);

        if(isset($latest['data'])) $latest['data'] = @gzuncompress($latest['data']);
    	return $latest;
    }


    // Safe unserialize with allowed classes
    public static function safeUnserialize($data) {
        if (is_string($data)) {
            $result = @unserialize($data, ['allowed_classes' => false]);
            return $result !== false ? $result : [];
        }
        return [];
    }


    # delete old data #
    public static function cleanHistory($id) {
    	global $database;

    	$history = $database->get("app_servers_history", "*", ["id" => $id]);

        if(!empty($history)) {
            $data = @gzuncompress($history['data']);
            if ($data === false) return; // Failed to decompress

            $search = array();
            $replace = array();

            $data = deleteBetween("{agent_version}", "{/agent_version}", $data);
            array_push($search, '{agent_version}', '{/agent_version}'); array_push($replace, '', '');

            $data = deleteBetween("{serverkey}", "{/serverkey}", $data);
            array_push($search, '{serverkey}', '{/serverkey}'); array_push($replace, '', '');

            $data = deleteBetween("{gateway}", "{/gateway}", $data);
            array_push($search, '{gateway}', '{/gateway}'); array_push($replace, '', '');

            $data = deleteBetween("{hostname}", "{/hostname}", $data);
            array_push($search, '{hostname}', '{/hostname}'); array_push($replace, '', '');

            $data = deleteBetween("{kernel}", "{/kernel}", $data);
            array_push($search, '{kernel}', '{/kernel}'); array_push($replace, '', '');

            $data = deleteBetween("{time}", "{/time}", $data);
            array_push($search, '{time}', '{/time}'); array_push($replace, '', '');

            $data = deleteBetween("{os}", "{/os}", $data);
            array_push($search, '{os}', '{/os}'); array_push($replace, '', '');

            $data = deleteBetween("{os_arch}", "{/os_arch}", $data);
            array_push($search, '{os_arch}', '{/os_arch}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_model}", "{/cpu_model}", $data);
            array_push($search, '{cpu_model}', '{/cpu_model}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_cores}", "{/cpu_cores}", $data);
            array_push($search, '{cpu_cores}', '{/cpu_cores}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_speed}", "{/cpu_speed}", $data);
            array_push($search, '{cpu_speed}', '{/cpu_speed}'); array_push($replace, '', '');

            $data = deleteBetween("{default_interface}", "{/default_interface}", $data);
            array_push($search, '{default_interface}', '{/default_interface}'); array_push($replace, '', '');

            $data = deleteBetween("{ipv4_addresses}", "{/ipv4_addresses}", $data);
            array_push($search, '{ipv4_addresses}', '{/ipv4_addresses}'); array_push($replace, '', '');

            $data = deleteBetween("{ipv6_addresses}", "{/ipv6_addresses}", $data);
            array_push($search, '{ipv6_addresses}', '{/ipv6_addresses}'); array_push($replace, '', '');

            $data = deleteBetween("{uptime}", "{/uptime}", $data);
            array_push($search, '{uptime}', '{/uptime}'); array_push($replace, '', '');

            $data = deleteBetween("{processes}", "{/processes}", $data);
            array_push($search, '{processes}', '{/processes}'); array_push($replace, '', '');

            ### WINDOWS SPECIFIC ###
            $data = deleteBetween("{net_interfaces}", "{/net_interfaces}", $data);
            array_push($search, '{net_interfaces}', '{/net_interfaces}'); array_push($replace, '', '');

            $data = deleteBetween("{disk_layout}", "{/disk_layout}", $data);
            array_push($search, '{disk_layout}', '{/disk_layout}'); array_push($replace, '', '');

            $data = deleteBetween("{cpu_load}", "{/cpu_load}", $data);
            array_push($search, '{cpu_load}', '{/cpu_load}'); array_push($replace, '', '');

            $data = deleteBetween("{filesystems}", "{/filesystems}", $data);
            array_push($search, '{filesystems}', '{/filesystems}'); array_push($replace, '', '');

            $data = deleteBetween("{net_stats}", "{/net_stats}", $data);
            array_push($search, '{net_stats}', '{/net_stats}'); array_push($replace, '', '');

            $database->update("app_servers_history", [
                "data" => gzcompress($data)
            ], ["id" => $id]);
        }
    }


}


?>
