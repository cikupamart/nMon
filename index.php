<?php

##################################
###       ERROR REPORTING      ###
##################################

$debug = false;

if($debug == false) {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if($debug == true) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '1');
}


##################################
###       START     TIME       ###
##################################

$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start_time = $time;

##################################
###       GENERAL VARS         ###
##################################

$scriptpath = __DIR__;

##################################
###         APP LOADER         ###
##################################

require($scriptpath . '/includes/loader.php');

// Generate CSRF token for forms
$csrf_token = generateCSRFToken();

##################################
###        MODAL LOADER        ###
##################################

if(isset($_GET['modal'])) {
    // Sanitize modal parameter
    $modal = preg_replace('/[^a-zA-Z0-9\/_-]/', '', $_GET['modal']);
    $modalFile = $scriptpath . '/template/modals/' . $modal . '.php';
    if (file_exists($modalFile)) {
        require($modalFile);
    }
}


##################################
###        PAGE LOADER         ###
##################################

// load the page if no modal or quick action was requested
if( !isset($_GET['modal']) && !isset($_GET['qa']) && !isset($_GET['json']) ) {

    // Exclude header and footer for login and forgot password page
    if($route == "signin" || $route == "forgot" || $route == "publicpage") {
        require($scriptpath . '/template/' . $route . '.php');
    }
    // load header + page + footer
    else {
        require($scriptpath . '/template/' . 'header.php');
        require($scriptpath . '/template/' . 'pages/' . $route . '.php');
        require($scriptpath . '/template/' . 'footer.php');
    }

}


?>
