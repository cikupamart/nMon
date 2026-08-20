<?php

##################################
###       LOAD FUNCTIONS       ###
##################################

require($scriptpath . '/includes/functions.php');
require($scriptpath . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'ci_common_functions.php');

##################################
###      LOAD CONFIG FILE      ###
##################################

if(file_exists($scriptpath . "/config.php")) { require($scriptpath . '/config.php'); }
else { header("Location:install/"); exit; }


##################################
###      REGISTER CLASSES      ###
##################################

spl_autoload_register('vendorClassAutoload');
spl_autoload_register('appClassAutoload');

// composer autoload
require $scriptpath . '/vendor/autoload.php';


##################################
###          APP INIT          ###
##################################

### INITIALIZE DATABSE CLASS ###
$database = new medoo($config);

### START THE SESSION ###
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

### DATE & TIME ###
date_default_timezone_set(getConfigValue("timezone") ?? 'UTC');
$datetime = date("Y-m-d H:i:s");
$date = date("Y-m-d");


### XSS FILTERING ###
$xss_filtering = getConfigValue("xss_filtering");
if($xss_filtering == "true") {
    // Use FILTER_SANITIZE_FULL_SPECIAL_CHARS instead of deprecated FILTER_SANITIZE_STRING
    if (isset($_GET)) {
        foreach ($_GET as $key => $value) {
            $_GET[$key] = is_string($value) ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
        }
    }
    $security = new Security();
    if (isset($_POST)) {
        $_POST = $security->xss_clean($_POST);
    }
}

### GET PAGE ROUTE (DEFAULTS TO DASHBOARD IF NOT SET) ###
if (empty($_GET['route'])) $route = "dashboard"; else $route = $_GET['route'];

### GET PAGE SECTION (IF ISSET) ###
if (isset($_GET['section'])) $section = $_GET['section']; else $section = "";

### LOAD STATUS MESSAGE FOR DISPLAY AND CLEAR IT ###
if (!empty($_SESSION['statuscode'])) {
    $statuscode = $_SESSION['statuscode'];
    $status = array();
    $statusmessage = $database->get("core_statuses", "*", ["code" => $statuscode]);
    clearStatus();
}

### CHECK IF USER IS SIGNED IN, EXCEPT ON SIGNIN OR RECOVER PASSWORD PAGE ###
if ($route != "signin" && $route != "forgot" && $route != "publicpage") isSignedIn();

### INITIALIZE LOGGED IN USER (LIU) ARRAY & PERMISSIONS ###
if ($route != "signin" && $route != "forgot" && $route != "publicpage") {
    $liu = $database->get("core_users", "*", ["sessionid" => session_id() ]);
    if ($liu) {
        $perms = unserialize(getSingleValue("core_roles","perms",$liu['roleid']));
        $liu_groups = unserialize($liu['groups']);
        if(in_array("0", $liu_groups)) $liu_groups = getGroupsArray();
        $isAdmin = true;
    } else {
        header("Location:?route=signin");
        exit;
    }
}

### GOOGLE MAPS ###
$isGoogleMaps = false;
if(getConfigValue("google_maps_api_key") != "") $isGoogleMaps = true;

### OTHER SESSION VARS ###

if(empty($_SESSION['range_type'])) $_SESSION['range_type'] = "auto";

if($_SESSION['range_type'] == "auto") {
    $_SESSION['range_start'] = date("Y-m-d H:i:s", strtotime('-3 hours'));
    $_SESSION['range_end'] = date("Y-m-d H:i:s");
    $_SESSION['range_label'] = "";
    $_SESSION['asset'] = "";
}


##################################
###        LOAD LANGUAGE       ###
##################################

// get default app language
$lang = getConfigValue("default_lang");

// overwrite default lang if liu has one defined
if(isset($liu)) {
    if($liu['lang'] != "") $lang = $liu['lang'];
    }

// define language file path
$langfile = $scriptpath . "/lang/" . $lang . ".mo";

// define overriden language file path
$orlangfile = $scriptpath . "/lang/override/" . $lang . ".mo";

// load overriden language file (if exists)
if(file_exists($orlangfile)) {
    $streamer = new FileReader($orlangfile);
    $t = new gettext_reader($streamer);
}
// if overridden lang file does not exist, try to load normal language file (if exists)
else {
    if(file_exists($langfile)) {
        $streamer = new FileReader($langfile);
        $t = new gettext_reader($streamer);
    }
}


##################################
###   LOAD APP CONTROLLERS     ###
##################################

// general controller (always loads)
require($scriptpath . '/includes/controllers/general.php');

// modals controller (loads only if a modal is requested)
if(isset($_GET['modal'])) require($scriptpath . '/includes/controllers/modals.php');

// quick actions controller (loads only if a quick action is requested)
if(isset($_GET['qa'])) require($scriptpath . '/includes/controllers/quickactions.php');

// json controller (loads only if ajax data is requested)
if(isset($_GET['json'])) require($scriptpath . '/includes/controllers/json.php');

// actions controller (loads only if an action is requested)
if(isset($_POST['action'])) require($scriptpath . '/includes/controllers/actions.php');

// data controller (loads only if someone is logged in)
//if(isset($liu)) require($scriptpath . '/includes/controllers/data.php');
require($scriptpath . '/includes/controllers/data.php');


?>
