<?php

class App {


    public static function setRange($data) {
        $_SESSION['range_type'] = "manual";

        $_SESSION['asset'] = $data['asset'] ?? '';

        $_SESSION['range_start'] = $data['range_start'] ?? date("Y-m-d H:i:s");
        $_SESSION['range_end'] = $data['range_end'] ?? date("Y-m-d H:i:s");
        $_SESSION['range_label'] = $data['range_label'] ?? '';
    }

    public static function resetRange() {
        $_SESSION['range_type'] = "auto";

        $_SESSION['asset'] = "";

        $_SESSION['range_start'] = date("Y-m-d H:i:s", strtotime('-3 hours'));
        $_SESSION['range_end'] = date("Y-m-d H:i:s");
        $_SESSION['range_label'] = "";
    }



    public static function purgeSystemLogs() {
        global $database;
        $items = 0;
        $log_retention = getConfigValue('log_retention') ?? 90;
        $purge_datetime = date("Y-m-d H:i:s", strtotime('-'.$log_retention.' days'));

        $activitylog_items = $database->delete("core_activitylog", [ "timestamp[<=]" => $purge_datetime ]);
        $emaillog_items = $database->delete("core_emaillog", [ "timestamp[<=]" => $purge_datetime ]);
        $smslog_items = $database->delete("core_smslog", [ "timestamp[<=]" => $purge_datetime ]);
        $cronlog_items = $database->delete("core_cronlog", [ "timestamp[<=]" => $purge_datetime ]);

        $items = $activitylog_items + $emaillog_items + $smslog_items + $cronlog_items;
        return $items;
    }


    public static function purgeMonitoringHistory() {
        global $database;
        $items = 0;
        $history_retention = getConfigValue('history_retention') ?? 90;
        $purge_datetime = date("Y-m-d H:i:s", strtotime('-'.$history_retention.' days'));

        $checks_items = $database->delete("app_checks_history", [ "timestamp[<=]" => $purge_datetime ]);
        $servers_items = $database->delete("app_servers_history", [ "timestamp[<=]" => $purge_datetime ]);
        $websites_items = $database->delete("app_websites_history", [ "timestamp[<=]" => $purge_datetime ]);

        $items = $checks_items + $servers_items + $websites_items;
        return $items;
    }


    public static function updateGeoData() {
        global $database;
        $servers = getTable("app_servers");
        $websites = getTable("app_websites");
        $checks = getTable("app_checks");

        $freegeoip = new FreeGeoIp('json');

        foreach($servers as $server) {
            $latest = Server::latestData($server['id']);
            if(empty($latest)) continue;

            $mainip = "127.0.0.1";

            if($server['type'] == "linux") {
                $default_interface = Server::extractData('default_interface', $latest['data'], true);
                $ipv4_addresses = explode(";",Server::extractData('ipv4_addresses', $latest['data'], true));
                foreach ($ipv4_addresses as $address) {
                    $address_parts = explode(",", $address);
                    if (($address_parts[0] ?? '') == $default_interface) { $mainip = $address_parts[1] ?? '127.0.0.1'; break; }
                }
            }


            if($server['type'] == "windows") {
                $default_interface = Server::extractData('default_interface', $latest['data'], true);
                $net_interfaces = json_decode( Server::extractData('net_interfaces', $latest['data'], true), true) ?? [];
                foreach($net_interfaces as $net_interface) {
                    if(($net_interface['iface'] ?? '') == $default_interface) {
                        $mainip = $net_interface['ip4'] ?? '127.0.0.1';
                        break;
                    }
                }
            }

            $geo_data = [];

            try{
               $geo_data = json_decode($freegeoip->fetch($mainip), true) ?? [];
            }
            catch(Exception $e)
            {
            }


            $database->update("app_servers", [
        		"geodata" => serialize($geo_data)
        	], [ "id" => $server['id'] ]);

        }

        foreach($checks as $check) {
            $geo_data = json_decode($freegeoip->fetch(gethostbyname($check['host'] ?? '')), true) ?? [];

            $database->update("app_checks", [
                "geodata" => serialize($geo_data)
            ], [ "id" => $check['id'] ]);
        }


        foreach($websites as $website) {

            $host = $website['url'] ?? '';

            if (strpos($host, '://') !== false) {
                $host = parse_url($host, PHP_URL_HOST);
            }

            $geo_data = json_decode($freegeoip->fetch(gethostbyname($host)), true) ?? [];

            $database->update("app_websites", [
                "geodata" => serialize($geo_data)
            ], [ "id" => $website['id'] ]);
        }

    }


    public static function send_alert_notif($action, $assettype, $alertid) {
        global $database;
        global $twittercon;

        $alert = null;
        $asset = null;

        if($assettype == "website") {
            $alert = getRowById("app_websites_alerts", $alertid);
            $asset = getRowById("app_websites", $alert['websiteid'] ?? 0);
            $assettype = __('Website');
        }
        if($assettype == "check") {
            $alert = getRowById("app_checks_alerts", $alertid);
            $asset = getRowById("app_checks", $alert['checkid'] ?? 0);
            $assettype = __('Check');
        }
        if($assettype == "server") {
            $alert = getRowById("app_servers_alerts", $alertid);
            $asset = getRowById("app_servers", $alert['serverid'] ?? 0);
            $assettype = __('Server');
        }

        if (!$alert || !$asset) return;

        //websites
        if($alert['type'] == "responsecode") $typestring = __('HTTP Response Code') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "loadtime") $typestring = __('Load Time') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "searchstringmissing") $typestring = __('Search String Missing');

        //checks
        if($alert['type'] == "offline") $typestring = __('Offline');
        if($alert['type'] == "blacklisted") $typestring = __('Blacklisted');
        if($alert['type'] == "dnsfailed") $typestring = __('DNS Query Failed');
        if($alert['type'] == "callback") $typestring = __('Callback Failed');
        if($alert['type'] == "responsetime") $typestring = __('Response Time') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');

        //servers
        if($alert['type'] == "nodata") $typestring = __('Data Loss');

        if($alert['type'] == "cpu") $typestring = __('CPU Usage %') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "cpuio") $typestring = __('CPU IO Wait %') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "load1min") $typestring = __('System Load 1 Min') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "load5min") $typestring = __('System Load 5 Min') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "load15min") $typestring = __('System Load 15 Min') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "service") $typestring = __('Service/Process Not Running') . " " . ($alert['comparison_limit'] ?? '');

        if($alert['type'] == "ram") $typestring = __('RAM Usage %') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "ramMB") $typestring = __('RAM Usage MB') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "swap") $typestring = __('Swap Usage %') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "swapMB") $typestring = __('Swap Usage MB') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "disk") $typestring = __('Disk Usage % (Aggregated)') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "diskGB") $typestring = __('Disk Usage GB (Aggregated)') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');

        if(strpos($alert['type'],'disk:') !== false) {
            $disk_text = explode(":",$alert['type'],2);
            $typestring = __('Disk Usage %:') . " " . ($disk_text[1] ?? '') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        }

        if(strpos($alert['type'],'diskGB:') !== false) {
            $disk_text = explode(":",$alert['type'],2);
            $typestring = __('Disk Usage GB:') . " " . ($disk_text[1] ?? '') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        }

        if($alert['type'] == "connections") $typestring = __('Connections') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "ssh") $typestring = __('SSH Sessions') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "ping") $typestring = __('Ping Latency') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "netdl") $typestring = __('Network Download Speed MB/s') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');
        if($alert['type'] == "netup") $typestring = __('Network Upload Speed MB/s') . " " . ($alert['comparison'] ?? '') . " " . ($alert['comparison_limit'] ?? '');



        if($action == "open") {
            $subject = __('Incident OPENED:') . " " . $assettype . " " . ($asset['name'] ?? '');
            $message = __('Incident OPENED:') . " " . $assettype . " " . ($asset['name'] ?? '') . " (" . ($typestring ?? '') . ") @ " . dateTimeDisplay(date('Y-m-d H:i:s'));
        }

        if($action == "close") {
            $subject = __('Incident CLOSED:') . " " . $assettype . " " . ($asset['name'] ?? '');
            $message = __('Incident CLOSED:') . " " . $assettype . " " . ($asset['name'] ?? '') . " (" . ($typestring ?? '') . ") @ " . dateTimeDisplay(date('Y-m-d H:i:s'));
        }



        if(getConfigValue("twitter_apikey") != "" && getConfigValue("twitter_apisecret") != "" && getConfigValue("twitter_token") != "" && getConfigValue("twitter_tokensecret") != "") {
            $twittercon = new Twitter(getConfigValue("twitter_apikey"), getConfigValue("twitter_apisecret"), getConfigValue("twitter_token"), getConfigValue("twitter_tokensecret"));
        }

        // Use safe unserialize
        $contactids = unserialize($alert['contacts'] ?? '', ['allowed_classes' => false]);
        if(empty($contactids)) $contactids = [];


        foreach($contactids as $contactid) {
            $contact = getRowById("app_contacts", $contactid);

            if(($contact['email'] ?? '') != "") {
                Notification::incidentAlert($contact['email'], $contact['name'], $subject ?? '', $message ?? '');
            }

            if(($contact['mobilenumber'] ?? '') != "") {
                sendSMS($contact['mobilenumber'], $message ?? '');
            }

            if(($contact['pushbullet'] ?? '') != "") {
                try {
                    $pb = new Pushbullet\Pushbullet($contact['pushbullet']);
                    Pushbullet\Connection::setCurlCallback(function ($curl) {
                        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
                    });

                    $pb->allDevices()->pushNote($subject ?? '', $message ?? '');
                } catch (Exception $e) { }
            }

            if(($contact['twitter'] ?? '') != "") {
                if(isset($twittercon)) {
                    try {
                        $twittercon->sendDirectMessage($contact['twitter'], $message ?? '');
                    } catch (TwitterException $e) { }
                }
            }

            if(($contact['pushover'] ?? '') != "") {
                try {

                    curl_setopt_array($ch = curl_init(), array(
                        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
                        CURLOPT_POSTFIELDS => array(
                          "token" => getConfigValue("pushover_apitoken"),
                          "user" => $contact['pushover'],
                          "message" => $message ?? '',
                          "title" => $subject ?? '',
                        ),
                        CURLOPT_RETURNTRANSFER => true,
                      ));
                      curl_exec($ch);
                      curl_close($ch);


                } catch (Exception $e) { }
            }

            $database->insert("app_alertlog", [
                "contactid" => $contact['id'] ?? 0,
                "contactname" => $contact['name'] ?? '',
                "date" => date('Y-m-d H:i:s'),
                "subject" => $subject ?? '',
                "message" => $message ?? '',
                "email" => $contact['email'] ?? '',
                "mobilenumber" =>  $contact['mobilenumber'] ?? '',
                "pushbullet" => $contact['pushbullet'] ?? '',
                "twitter" => $contact['twitter'] ?? '',
                "pushover" => $contact['pushover'] ?? '',
            ]);


        }

    }




}


?>
