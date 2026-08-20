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

try {
    require($scriptpath . '/includes/loader.php');
} catch (Throwable $e) {
    // If loader fails, show a helpful error page instead of blank
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>nMon - Application Error</title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <link rel="shortcut icon" href="template/assets/icon.png"/>
        <link href="template/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <link href="template/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    </head>
    <body class="login-page">
        <div class="login-box" style="width: 720px;">
            <div class="login-logo"><b>n</b>Mon - Error</div>
            <div class="login-box-body">
                <div class="alert alert-danger">
                    <h4><i class="icon fa fa-ban"></i> Application Error</h4>
                    <p>nMon encountered an error while loading. This usually means:</p>
                    <ul style="text-align: left;">
                        <li>The database has not been set up yet &mdash; run the installer: <a href="install/"><strong>install/</strong></a></li>
                        <li>The config file is missing &mdash; run the installer: <a href="install/"><strong>install/</strong></a></li>
                        <li>Database tables are missing &mdash; delete <code>config.php</code> and run the installer again</li>
                        <li>The install folder was not removed &mdash; delete the <code>install/</code> folder for security</li>
                    </ul>
                </div>
                <?php if ($debug): ?>
                <div class="alert alert-warning" style="text-align: left;">
                    <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?><br>
                    <strong>File:</strong> <?php echo htmlspecialchars($e->getFile()); ?> (line <?php echo $e->getLine(); ?>)
                </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-xs-6">
                        <a href="install/" class="btn btn-primary btn-block btn-flat"><i class="fa fa-download"></i> Run Installer</a>
                    </div>
                    <div class="col-xs-6">
                        <a href="install/upgrade.php" class="btn btn-warning btn-block btn-flat"><i class="fa fa-refresh"></i> Run Upgrade</a>
                    </div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

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
        // Check if page file exists
        $pageFile = $scriptpath . '/template/pages/' . $route . '.php';
        if (!file_exists($pageFile)) {
            // Route not found, redirect to dashboard
            header("Location:?route=dashboard");
            exit;
        }
        require($scriptpath . '/template/' . 'header.php');
        require($pageFile);
        require($scriptpath . '/template/' . 'footer.php');
    }

}


?>
