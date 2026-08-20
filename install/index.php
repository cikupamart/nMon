<?php

$debug = false;

if($debug == false) {
    error_reporting(0);
    ini_set('display_errors', '0');
}

if($debug == true) {
    error_reporting(E_ALL & ~E_NOTICE);
    ini_set('display_errors', '1');
}


function baseURL() { //return base url for cron jobs
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
	 return str_ireplace("install/","",$pageURL);
}
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>nMon Installer</title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <link rel="shortcut icon" href="../template/assets/icon.png"/>
        <link rel="apple-touch-icon image_src" href="../template/assets/icon-large.png"/>
        <link href="../template/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <!-- Theme style -->
		<link href="../template/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    </head>


    <body class="login-page">
        <div class="login-box" style="width: 720px;">
            <div class="login-logo"><b>n</b>Mon Installer</div><!-- /.login-logo -->

            <div class="login-box-body">
                <?php if(isset($status)): ?>
                <div class="row"><div class='col-md-12'><div class="alert alert-<?php echo $status['type']; ?> alert-auto" role="alert"><?php echo $status['message']; ?></div></div></div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-xs-6">
                        <form action="check.php" method="post">
                            <p class="login-box-msg">Installation Settings</p>


                            <div class="form-group has-feedback">
                                <input type="text" name="dbserver" class="form-control" placeholder="Database Server" value="localhost" required/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" name="dbname" class="form-control" placeholder="Database Name" required/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" name="dbuser" class="form-control" placeholder="Database User" required/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="text" name="dbpassword" class="form-control" placeholder="Database Password"/>
                            </div>

                            <div class="form-group has-feedback">
                                <input type="text" name="app_url" class="form-control" value="<?php echo baseURL();?>" placeholder="nMon URL" required/>
                            </div>

                            <p class="login-box-msg">Admin User</p>

                            <div class="form-group has-feedback">
                                <input type="text" name="name" class="form-control" placeholder="Admin Name" required/>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="email" name="email" class="form-control" placeholder="Email Address" required/>
                            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                            </div>
                            <div class="form-group has-feedback">
                                <input type="password" name="password" class="form-control" placeholder="Password" required/>
                            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                            </div>

                            <div class="row">
                                <div class="col-xs-12">
                                    <button type="submit" class="btn btn-primary btn-block btn-flat">Continue</button>
                                </div><!-- /.col -->

                            </div>
                        </form>
                    </div>

                    <div class="col-xs-6">
                        <p class="login-box-msg">Requirements</p>

                        <?php if (!is_writable(dirname('../config.php'))) { ?>
                            <div class="alert alert-danger">
                                <p class="text-bold"><i class="icon fa fa-ban"></i> nMon Directory is not writable</p>
                                nMon cannot write config.php to the project root folder.
                            </div>
                            <div class="alert alert-warning">
                                <p class="text-bold"><i class="icon fa fa-wrench"></i> How to fix:</p>
                                <p>Run this command on your server:</p>
                                <code style="display:block; padding:8px; background:#f5f5f5; margin-top:5px; word-break:break-all;">chmod 755 <?php echo dirname(dirname(__FILE__)); ?></code>
                                <p style="margin-top:8px;">Or if using cPanel: File Manager &rarr; Select folder &rarr; Permissions &rarr; Set to 755</p>
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success ">
                                <p class="text-bold"><i class="icon fa fa-check"></i> nMon Directory is writable</p>
                            </div>
                        <?php } ?>



                        <?php if (version_compare(PHP_VERSION, '5.4.0', '<')) { ?>
                            <div class="alert alert-danger">
                                <p class="text-bold"><i class="icon fa fa-ban"></i> PHP Version</p>
                                onTrack requires PHP 5.4.0 or later.<br>Your version is <?php echo PHP_VERSION; ?>.
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success ">
                                <p class="text-bold"><i class="icon fa fa-check"></i> PHP Version</p>
                            </div>
                        <?php } ?>

                        <?php if (!extension_loaded('pdo_mysql')) { ?>
                            <div class="alert alert-danger">
                                <p class="text-bold"><i class="icon fa fa-ban"></i> PHP PDO MySQL</p>
                                PHP extension <b>pdo_mysql</b> is missing.
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success ">
                                <p class="text-bold"><i class="icon fa fa-check"></i> PHP PDO MySQL</p>
                            </div>
                        <?php } ?>

                        <?php if (!function_exists('fsockopen')) { ?>
                            <div class="alert alert-warning">
                                <p class="text-bold"><i class="icon fa fa-warning"></i> PHP FSOCKOPEN</p>
                                PHP function <b>fsockopen</b> is disabled, this is required for service monitoring.
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success ">
                                <p class="text-bold"><i class="icon fa fa-check"></i> PHP FSOCKOPEN</p>
                            </div>
                        <?php } ?>

                        <?php if (!function_exists('exec')) { ?>
                            <div class="alert alert-warning">
                                <p class="text-bold"><i class="icon fa fa-warning"></i> PHP EXEC</p>
                                PHP function <b>exec</b> is disabled, this is required if you wish to run PING checks.
                            </div>
                        <?php } else { ?>
                            <div class="alert alert-success ">
                                <p class="text-bold"><i class="icon fa fa-check"></i> PHP EXEC</p>
                            </div>
                        <?php } ?>


                    </div>
                </div>

            </div><!-- /.login-box-body -->
        </div><!-- /.login-box -->



        <!-- jQuery 2.1.3 -->
        <script src="../template/assets/plugins/jQuery/jQuery-2.2.3.min.js"></script>
        <!-- Bootstrap 3.3.2 JS -->
        <script src="../template/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>

    </body>


</html>
