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


$latestversion = 2.0;
$status = 'ok';

# LOAD CONFIGURAGION FILE
if(file_exists("../config.php")) {
	require('../config.php');
}
else { $status = 'noconfig'; }


if($status == 'ok') {
    # INITIALIZE MEDOO
    require('../vendor/classes/class.medoo.php');
    $database = new medoo($config);
    $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    // UPGRADE to 1.1
    if($currentversion == 1.0) {

        $sql = file_get_contents('sql/db_1.0-1.1.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.1"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }


    // UPGRADE to 1.2
    if($currentversion == 1.1) {

        $sql = file_get_contents('sql/db_1.1-1.2.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.2"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.3
    if($currentversion == 1.2) {

        $sql = file_get_contents('sql/db_1.2-1.3.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.3"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.4
    if($currentversion == 1.3) {

        $sql = file_get_contents('sql/db_1.3-1.4.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.4"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.5
    if($currentversion == 1.4) {

        $database->update("core_config", ["value" => "1.5"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.6
    if($currentversion == 1.5) {

        $database->update("core_config", ["value" => "1.6"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.7
    if($currentversion == 1.6) {

        $database->update("core_config", ["value" => "1.7"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

    // UPGRADE to 1.8
    if($currentversion == 1.7) {

        $sql = file_get_contents('sql/db_1.7-1.8.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.8"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }


    // UPGRADE to 1.9
    if($currentversion == 1.8) {

        $sql = file_get_contents('sql/db_1.8-1.9.sql');
        $database->query($sql);

        $database->update("core_config", ["value" => "1.9"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }


    // UPGRADE to 2.0
    if($currentversion == 1.9) {

        $sql = file_get_contents('sql/db_1.9-2.0.sql');
        $database->query($sql);

        // Add remoteControl permission to Super Administrator role
        $adminPerms = $database->get("core_roles", "perms", ["id" => 1]);
        $permsArray = unserialize($adminPerms);
        if (!in_array("remoteControl", $permsArray)) {
            $permsArray[] = "remoteControl";
            $permsArray[] = "viewMaps";
            $database->update("core_roles", ["perms" => serialize($permsArray)], ["id" => 1]);
        }

        $database->update("core_config", ["value" => "2.0"], ["name" => "db_version"]);
        $status = 'updated';

        $currentversion = $database->get("core_config", "value", [ "name" => "db_version" ]);

    }

}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>nMon Update</title>
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
    <div class="login-box">
      <div class="login-logo">
        <b>n</b>Mon Update
      </div><!-- /.login-logo -->
      <div class="login-box-body">

          <?php if($status == "ok"): ?>
                        <p class="login-box-msg">Nothing to do, database is already at latest version (v2.0).</p>
          <?php endif; ?>
          <?php if($status == "noconfig"): ?>
                        <p class="login-box-msg">Configuration file is missing.</p>
          <?php endif; ?>
          <?php if($status == "updated"): ?>
                        <p class="login-box-msg">Update complete!<br>Please delete the "install" folder.</p>
          <?php endif; ?>

      </div><!-- /.login-box-body -->
    </div><!-- /.login-box -->


    <!-- jQuery 2.2.3 -->
    <script src="../template/assets/plugins/jQuery/jQuery-2.2.3.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="../template/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>

  </body>


</html>
