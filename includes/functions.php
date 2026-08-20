<?php
// ----------------------------------------------------------------------------------------------\n// GENERAL FUNCTIONS

function randomString($chars=10) { //generate random string
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$randstring = '';
	for ($i = 0; $i < $chars; $i++) { $randstring .= $characters[rand(0, strlen($characters) -1)]; }
	return $randstring;
}

function currentFileName() { //return current file name
	return basename($_SERVER['REQUEST_URI'], '?' . $_SERVER['QUERY_STRING']);
}

function baseURL($sub=0) { //return base url for cron jobs

	if(getConfigValue("app_url") != "") return getConfigValue("app_url");

	$requesturi = explode("?",$_SERVER["REQUEST_URI"]);
	$subdir =  $requesturi[0];
	$pageURL = 'http';
	if(isset($_SERVER["HTTPS"])) { if($_SERVER["HTTPS"] == "on") {$pageURL .= "s";} }
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443") {
	$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"] . $subdir;
	} else {
	$pageURL .= $_SERVER["SERVER_NAME"] . $subdir;
	}


	return $pageURL;

}

function getGravatar($email,$size) { //get gravatar image for the given email address
	global $database;

	$grav_url = "https://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?d=mm" . "&s=" . $size;

	$avatar = $database->get("core_users", "avatar", [ "email" => strtolower($email) ]);

	if($avatar != "") { return "data:image/jpeg;base64," . base64_encode($avatar); }

	else return $grav_url;
}

function curlReturn($url) { //get url with curl
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)");
	curl_setopt($ch,CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}

function rand_color() { //generate random color
    return '#' . str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
}

function ttruncat($text,$numb=30) { //truncate text
	if (strlen($text) > $numb) {
	 	$text = substr($text, 0, $numb);
	 	$text = substr($text,0,strrpos($text," "));
	 	$etc = " ...";
	 	$text = $text.$etc;
	  }
	return $text;
}

function smartDate($timestamp) {

	if($timestamp == "") return __('Never');

	if (strpos($timestamp, ' ') !== false) { $timestamp = strtotime($timestamp); }

	$diff = time() - $timestamp;

	if ($diff <= 0) {
		return __('Now');
	}
	else if ($diff < 60) {
		return _x("%d second ago","%d seconds ago",floor($diff));
	}
	else if ($diff < 60*60) {
		return _x("%d minute ago","%d minutes ago",floor($diff/60));
	}
	else if ($diff < 60*60*24) {
		return _x("%d hour ago","%d hours ago",floor($diff/(60*60)));
	}
	else if ($diff < 60*60*24*30) {
		return _x("%d day ago","%d days ago",floor($diff/(60*60*24)));
	}
	else if ($diff < 60*60*24*30*12) {
		return _x("%d month ago","%d months ago",floor($diff/(60*60*24*30)));
	}
	else {
		return _x("%d year ago","%d years ago",floor($diff/(60*60*24*30*12)));
	}
}

function escapeJavaScriptText($string) {
    return str_replace("\\n", '\\n', str_replace('"', '\\\"', addcslashes(str_replace("\\r", '', (string)$string), "\\0..\\37'\\\\")));
}


function deleteBetween($beginning, $end, $string) {
	$beginningPos = strpos($string, $beginning);
	$endPos = strpos($string, $end);
	if ($beginningPos === false || $endPos === false) { return $string;	}

	$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);

	return str_replace($textToDelete, '', $string);
}


function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');

    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);

    $bytes /= (1 << (10 * $pow));

    return round($bytes, $precision) . ' ' . $units[$pow];
}


function objectToArray ($object) {
    if(!is_object($object) && !is_array($object))
        return $object;

    return array_map('objectToArray', (array) $object);
}


function is_decimal( $val )
{
    return is_numeric( $val ) && floor( $val ) != $val;
}


// ----------------------------------------------------------------------------------------------\n// CSRF TOKEN FUNCTIONS

function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

function verifyCSRFToken() {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

function requireCSRF() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!verifyCSRFToken()) {
            http_response_code(403);
            die('Invalid CSRF token');
        }
    }
}


// ----------------------------------------------------------------------------------------------\n// INPUT SANITIZATION FUNCTIONS

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function sanitizeEmail($email) {
    $email = strtolower(trim($email));
    return filter_var($email, FILTER_SANITIZE_EMAIL);
}

function sanitizeInt($value) {
    return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeFloat($value) {
    return filter_var($value, FILTER_VALIDATE_FLOAT);
}


// ----------------------------------------------------------------------------------------------\n// SECURITY FUNCTIONS

function secureHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

function isHTTPS() {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
}


// ----------------------------------------------------------------------------------------------\n// GENERAL DATABASE FUNCTIONS

function getRowById($table,$id) { //return associative array from one row by id
	global $database;
	$row = $database->get($table, "*", ["id" => $id]);
	return $row;
}

function getSingleValue($table,$column,$id) { //returns single value from table row by id
	global $database;
	$value = $database->get($table, $column, ["id" => $id]);
	return $value;
}

function getTable($table,$columns="*",$sortby="id",$sortway="ASC") { //get entire table
	global $database;
	$table = $database->select($table, $columns, [ "ORDER" => [$sortby => $sortway] ] );
	return $table;
}

function getTableFiltered($table,$filterColumn1,$filterValue1,$filterColumn2="",$filterValue2="",$columns="*",$sortby="id",$sortway="ASC") { //get entire table filtered
	global $database;
	if ($filterColumn2 == "") {
		$table = $database->select($table, $columns, [$filterColumn1 => $filterValue1, "ORDER" => [$sortby => $sortway]]);
	}
	else {
		$table = $database->select($table, $columns, [ "AND" => [$filterColumn1 => $filterValue1, $filterColumn2 => $filterValue2] ], ["ORDER" => [$sortby => $sortway]]);
	}
	return $table;
}

function countTable($table) { //count table rows
	global $database;
	$count = $database->count($table);
	return $count;
}

function countTableFiltered($table,$filterColumn1,$filterValue1,$filterColumn2="",$filterValue2="") { //count table rows with filter
	global $database;
	if ($filterColumn2 == "") { $count = $database->count($table, [$filterColumn1 => $filterValue1]); }
	else { $count = $database->count($table, [ "AND" => [$filterColumn1 => $filterValue1, $filterColumn2 => $filterValue2] ]); }
	return $count;
}

function getConfigValue($name) { //return config value from database
	global $database;
	return $database->get("core_config", "value", ["name" => $name]);
}

function deleteRowById($table,$id) { //detete row(s) by id
	global $database;
    $database->delete($table, [ "id" => $id ]);
}


// ----------------------------------------------------------------------------------------------\n// DATE FUNCTIONS


function phpFormat() {
	$format = explode(";",getConfigValue("date_format"));
	return $format[0];
}

function jsFormat() {
	$format = explode(";",getConfigValue("date_format"));
	return $format[1];
}

function dateDisplay($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "") return date($format[0], strtotime($date) );
	else return "";
}

function dateTimeDisplay($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "") return date($format[0]." H:i:s", strtotime($date) );
	else return "";
}

function dateDb($date) {
	$format = explode(";",getConfigValue("date_format"));

	if($date != "")  {
		$dateObj = date_create_from_format($format[0],$date);
		return date_format($dateObj,"Y-m-d");
	}

	else return "";
}

// ----------------------------------------------------------------------------------------------\n// NAVIGATION

function reroute($data,$status=0) {
	$location = "Location:?route=" . $data['route'];
	if(isset($data['routeid'])) $location .= "&id=" . $data['routeid'];
	if(isset($data['section'])) $location .= "&section=" . $data['section'];
	setStatus($status);
	header($location);
}

function setStatus($status) {
	if($status != 0 && $status != "") $_SESSION["statuscode"] = $status;
}

function clearStatus() {
	$_SESSION["statuscode"] = "";
}


// ----------------------------------------------------------------------------------------------\n// CLASS LOADERS

function vendorClassAutoload($classname) {
	global $scriptpath;
	$file = $scriptpath . '/vendor/classes/class.' . strtolower($classname) . '.php';
	if (file_exists($file)) require($file);
}

function appClassAutoload($classname) {
	global $scriptpath;
	$file = $scriptpath . '/includes/classes/class.' . strtolower($classname) . '.php';
	if (file_exists($file)) require($file);
}


// ----------------------------------------------------------------------------------------------\n// TEXT OUTPUT

function __($text) {
	global $t;
	if(isset($t)) return $t->translate($text);
	else return $text;
}

function _e($text) {
	echo __($text);
}

function _x($sg,$pl,$count) {
	global $t;
	if(isset($t)) return sprintf($t->ngettext($sg,$pl,intval($count)), $count);
	else {
		if($count == "1") return sprintf($sg,$count);
		elseif($count > 1) return sprintf($pl,$count);
	}
}


// ----------------------------------------------------------------------------------------------\n// PASSWORD FUNCTIONS (Using modern password_hash)

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function needsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
}


// ----------------------------------------------------------------------------------------------\n// AUTHENTICATION FUNCTIONS

function signIn($email,$password) { //login and set session
	global $database;
	$email = strtolower(trim($email));

	// Get user by email
	$person = $database->get("core_users", "*", ["email" => $email]);

	if ($person) {
		// Support both old SHA1 and new bcrypt hashes
		$passwordValid = false;

		if (strlen($person['password']) === 60 && substr($person['password'], 0, 4) === '$2y$') {
			// Modern bcrypt hash
			$passwordValid = verifyPassword($password, $person['password']);
		} else {
			// Legacy SHA1 hash - migrate to bcrypt on successful login
			if ($person['password'] === sha1($password)) {
				$passwordValid = true;
				// Migrate to bcrypt
				$newHash = hashPassword($password);
				$database->update("core_users", ["password" => $newHash], ["id" => $person['id']]);
				logSystem("Password migrated to bcrypt - User ID: " . $person['id']);
			}
		}

		if ($passwordValid) {
			$sessionid = session_id();
			$database->update("core_users", ["sessionid" => $sessionid], ["email" => $email]);
			logSystem("User Logged In - ID: " . $person['id']);
			header("Location:?route=dashboard");
			exit;
		}
	}

	logSystem("User Login Failure - EMAIL: " . $email);
	setStatus(1200);
	header("Location:?route=signin");
	exit;
}

function resetConfirmation($email) { //set password resetkey and send confirmation email for password reset
	global $database;
	$email = strtolower(trim($email));
	$count = $database->count("core_users",["email" => $email]);

	if ($count == "1") {
		$people = $database->get("core_users","*",["email" => $email]);
		$resetkey = randomString(32);
		$database->update("core_users", ["resetkey" => $resetkey], ["email" => $email]);
		$resetlink = baseURL(-14) . "/?route=forgot&resetkey=" . $resetkey;
		Notification::passwordReset($people['id'],$resetlink);
		setStatus(1300);
		header("Location:?route=forgot");
		exit;
	}
	else { setStatus(1400); header("Location:?route=forgot");  exit; }
}

function resetPassword($resetkey,$password) { //reset password
	global $database;
	$count = $database->count("core_users",["resetkey" => $resetkey]);

	if ($count == "1") {
		$people = $database->get("core_users","*",["resetkey" => $resetkey]);
		// Use secure password hashing
		$newPassword = hashPassword($password);
		$database->update("core_users", ["password" => $newPassword,"resetkey" => ""], ["resetkey" => $resetkey]);
		logSystem("User Password Reset - ID: " . $people['id']);
		setStatus(1600);
		header("Location:?route=login");
		exit;
	}
	else { setStatus(1500); header("Location:?route=forgot");  exit; }
}

function signOut($id) { //unset user/admin session
	global $database;
	$database->update("core_users", ["sessionid" => ""], ["id" => $id]);
	logSystem("User Signned Out - ID: " . $id);
	header("Location:?route=signin");
	exit;
}

function isSignedIn() { //check if someone is logged in, if not redirect to login page
	global $database;
	$sessionid = session_id();
	$people = $database->count("core_users", ["sessionid" => $sessionid]);
	if($people != 1) { header("Location:?route=signin"); exit; }
}


function isAuthorized($action) {
	global $perms;
	if(!in_array($action,$perms)) { setStatus("1"); header("Location:?route=dashboard"); exit; }
}

// check if user has permission to view this group
// returns TRUE OR FALSE
function checkGroup($groupid) {
	global $liu_groups;

	if(in_array($groupid, $liu_groups))
		return TRUE;
	else return FALSE;
}

// check if user has permission to view this group
// returns TRUE OR REDIRECTS with error starus
function checkGroupRedirect($groupid) {
	global $liu_groups;

	if(in_array($groupid, $liu_groups))
		return TRUE;
	else {
		setStatus("1"); header("Location:?route=dashboard"); exit;
	}
}

function getGroupsArray() {
	$groups = [];
	$groups_table = getTable("app_groups");

	foreach($groups_table as $item) {
		array_push($groups, $item['id']);
	}

	return $groups;
}

function get_group($table, $itemid) {
	$item_row = getRowById($table, $itemid);
	return $item_row['groupid'];
}


// ----------------------------------------------------------------------------------------------\n// APP LOGGING FUNCTIONS

function logSystem($description) { //add to system log
	global $liu;
	if(isset($liu['id'])) $userid = $liu['id']; else $userid = -1;
	global $database;
	$database->insert("core_activitylog", [
		"userid" => $userid,
		"ipaddress" => $_SERVER['REMOTE_ADDR'],
		"description" => $description,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}

function logEmail($userid,$to,$subject,$message) { //add to email log
	global $database;
	$database->insert("core_emaillog", [
		"userid" => $userid,
		"to" => $to,
		"subject" => $subject,
		"message" => $message,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}

function logSMS($mobile,$sms) { //add to sms log
	global $database;
	$database->insert("core_smslog", [
		"to" => $mobile,
		"message" => $sms,
		"timestamp" => date('Y-m-d H:i:s')
	]);
}


// ----------------------------------------------------------------------------------------------\n// COMMUNICATIONS FUNCTIONS

function sendEmail($to,$subject,$message,$userid="0",$ccs=array()) { //send email
	$mail = new PHPMailer;
	$mail->CharSet = "UTF-8";
	if (getConfigValue("email_smtp_enable") == "true") {
		$mail->isSMTP();
		$mail->Host = getConfigValue("email_smtp_host");
		$mail->SMTPAuth = getConfigValue("email_smtp_auth");
		$mail->Username = getConfigValue("email_smtp_username");
		$mail->Password = getConfigValue("email_smtp_password");
		$mail->SMTPSecure = getConfigValue("email_smtp_security");
		$mail->Port = getConfigValue("email_smtp_port");
		if (getConfigValue("email_smtp_domain") != "") {
			$mail->AuthType = 'NTLM';
			$mail->Realm = getConfigValue("email_smtp_domain");
		}
	}

	$mail->SMTPOptions = array(
		'ssl' => array(
			'verify_peer' => false,
			'verify_peer_name' => false,
			'allow_self_signed' => true,
		),
	);

	$mail->From = getConfigValue("email_from_address");
	$mail->FromName = getConfigValue("email_from_name");
	$mail->addAddress($to);
	foreach($ccs as $cc) { $mail->AddCC($cc); }
	$mail->Subject = $subject;
	$mail->Body    = $message;
	$mail->IsHTML(true);

	if(!$mail->send()) {
		logEmail($userid,$to,$subject,$mail->ErrorInfo);
		return 0; //error
	}
	else {
		logEmail($userid,$to,$subject,$message);
		return 1; //success
	}
}


function sendSMS($mobile,$sms) { //send sms
	$provider = getConfigValue("sms_provider");
	$user = getConfigValue("sms_user");
	$password = getConfigValue("sms_password");
	$api_id = getConfigValue("sms_api_id");
	$from = getConfigValue("sms_from");

	if ($provider == "smsglobal") {
		$url = 'https://api.smsglobal.com/http-api.php' . '?action=sendsms' . '&user=' . $user . '&password=' . $password . '&from=' . $from . '&to=' . $mobile . '&text=' . urlencode($sms);
		$returnedData = file_get_contents($url);
	}
	if ($provider == "clickatell") {
		$url = 'https://api.clickatell.com/http/sendmsg?user=' . $user . '&password=' . $password . '&api_id=' . $api_id . '&to=' . $mobile . '&text=' . urlencode($sms);
		$returnedData = file_get_contents($url);
	}

	if ($provider == "1s2u") {
		$sms = urlencode($sms);
		$url = 'https://1s2u.com/sms/sendsms/sendsms.asp?' . "username=$user&password=$password&mno=$mobile&msg=$sms&sid=$from&mt=0&fl=0&ipcl=127.0.0.1";
		$returnedData = file_get_contents($url);
	}

	if ($provider == "twilio") {

		$account_sid = $user;
		$auth_token = $password;
		$client = new Twilio\Rest\Client($account_sid, $auth_token);

		try {
			$messages = $client->messages->create($mobile,
				array(
					'From' => $from,
					'Body' => $sms,
				)
			);
		} catch(Exception $e) { }



	}

	logSMS($mobile,$sms);
}


// ----------------------------------------------------------------------------------------------\n// APP SPECIFIC


// custom compare
function compare($what, $with, $how) {
	$result = false;

	switch($how) {
		case "==":
			if($what == $with) $result = true; else $result = false;
		break;

		case ">=":
			if($what >= $with) $result = true; else $result = false;
		break;

		case "<=":
			if($what <= $with) $result = true; else $result = false;
		break;

		case ">":
			if($what > $with) $result = true; else $result = false;
		break;

		case "<":
			if($what < $with) $result = true; else $result = false;
		break;

		case "!=":
			if($what != $with) $result = true; else $result = false;
		break;
	}

	return $result;
}


// website checker on request done

function website_request_done($content, $url, $websiteid, $expect, $ch, $cookie) {
	global $database;

	$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	$latency = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
	$has_expected = 1;

	if($expect != "") {
		if (stripos($content, $expect) !== false) {
		    $has_expected = 1;
		}
		else $has_expected = 0;
	}

	if($httpcode == "0") $latency = 0;

	$database->insert("app_websites_history", [
		"websiteid" => $websiteid,
		"timestamp" => date('Y-m-d H:i:s'),
		"latency" => $latency,
		"statuscode" => $httpcode,
		"has_expected" => $has_expected,
	]);

}



// DNS blacklist checker

function dns_bl_lookup($ip) {
	global $database;
	$dnsbls = getTable("app_dnsbls");

    $listed = [];

    if ($ip) {
        $reverse_ip = implode(".", array_reverse(explode(".", $ip)));
        foreach ($dnsbls as $dnsbl) {
            if (checkdnsrr($reverse_ip . "." . $dnsbl['host'] . ".", "A")) {
				array_push($listed, $dnsbl['host']);
            }
        }
    }

    return $listed;
}


// ----------------------------------------------------------------------------------------------\n// RATE LIMITING

function checkRateLimit($key, $maxAttempts = 5, $windowMinutes = 15) {
    global $database;

    $windowStart = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));

    // Clean old attempts
    $database->delete("core_ratelimit", [
        "AND" => [
            "key" => $key,
            "timestamp[<]" => $windowStart
        ]
    ]);

    // Count current attempts
    $attempts = $database->count("core_ratelimit", [
        "AND" => [
            "key" => $key,
            "timestamp[>=]" => $windowStart
        ]
    ]);

    if ($attempts >= $maxAttempts) {
        return false; // Rate limit exceeded
    }

    // Log this attempt
    $database->insert("core_ratelimit", [
        "key" => $key,
        "timestamp" => date('Y-m-d H:i:s')
    ]);

    return true; // Within rate limit
}


// ----------------------------------------------------------------------------------------------\n// LOGGING

function logActivity($action, $details = "") {
    global $database, $liu;

    $userid = isset($liu['id']) ? $liu['id'] : 0;

    $database->insert("core_activitylog", [
        "userid" => $userid,
        "ipaddress" => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        "description" => $action . ($details ? ": " . $details : ""),
        "timestamp" => date('Y-m-d H:i:s')
    ]);
}


// ----------------------------------------------------------------------------------------------\n// EMAIL TEMPLATES

function getEmailTemplate($type, $data = []) {
    $appName = getConfigValue("app_name") ?? "nMon";
    $appUrl = getConfigValue("app_url") ?? baseURL();

    $templates = [
        'alert' => [
            'subject' => $data['subject'] ?? "Alert - {$appName}",
            'body' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #f44336; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>⚠️ {$appName} Alert</h1>
                </div>
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p>{$data['message']}</p>
                    <p><strong>Time:</strong> {$data['time']}</p>
                    <p><a href='{$appUrl}' style='display: inline-block; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>View Dashboard</a></p>
                </div>
                <div style='padding: 10px; text-align: center; font-size: 12px; color: #666;'>
                    © {$appName} - Server Monitoring
                </div>
            </div>"
        ],
        'welcome' => [
            'subject' => "Welcome to {$appName}",
            'body' => "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #4CAF50; color: white; padding: 20px; text-align: center;'>
                    <h1 style='margin: 0;'>Welcome to {$appName}</h1>
                </div>
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p>Hello {$data['name']},</p>
                    <p>Your account has been created successfully.</p>
                    <p><a href='{$appUrl}/?route=signin' style='display: inline-block; padding: 10px 20px; background-color: #2196F3; color: white; text-decoration: none; border-radius: 4px;'>Login Now</a></p>
                </div>
            </div>"
        ]
    ];

    return $templates[$type] ?? null;
}


?>\n