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


function randomString($chars=10) { //generate random string
	$characters = '0123456789abcdef';
	$randstring = '';
	for ($i = 0; $i < $chars; $i++) { $randstring .= $characters[rand(0, strlen($characters) -1)]; }
	return $randstring;
}

$encryption_key = randomString(64);

$ok = true;
$errorMessage = '';
$configWritten = false;

require('../vendor/classes/class.medoo.php');

try {
    $database = new medoo([
        "database_type"=>"mysql",
        "database_name"=> $_POST['dbname'],
        "server"=> $_POST['dbserver'],
        "username"=> $_POST['dbuser'],
        "password"=> $_POST['dbpassword'],
        "charset"=>"utf8",
        "port"=>3306
    ]);

    $sql = file_get_contents('sql/db.sql');
    $database->query($sql);

    $password = password_hash($_POST['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $email = strtolower($_POST['email']);
    $name = $_POST['name'];

    $database = new medoo([
        "database_type"=>"mysql",
        "database_name"=> $_POST['dbname'],
        "server"=> $_POST['dbserver'],
        "username"=> $_POST['dbuser'],
        "password"=> $_POST['dbpassword'],
        "charset"=>"utf8",
        "port"=>3306
    ]);

    $database->insert("core_users", [
        "roleid" => "1",
        "name" => $name,
        "email" => $email,
        "password" => $password,
        "groups" => 'a:1:{i:0;s:1:"0";}',
        "theme" => "skin-green",
        "sidebar" => "opened",
        "layout" => "",
        "notes" => "",
        "sessionid" => "",
        "resetkey" => "",
        "lang" => "en",
	"autorefresh" => 0,
    ]);

    $database->insert("app_contacts", [
        "groupid" => 1,
	"status" => 1,
        "name" => $name,
        "email" => $email,
        "mobilenumber" => "",
        "pushbullet" => "",
        "twitter" => "",
        "pushover" => "",
    ]);

    $database->update("core_config", ["value" => rtrim($_POST['app_url'], '/') . '/'], ["name" => "app_url"]);

    // --- Write config.php with explicit error checking ---
    $configPath = dirname(__FILE__) . '/../config.php';
    $configDir = dirname($configPath);

    // Check if directory is writable
    if (!is_writable($configDir)) {
        throw new Exception("Cannot write config.php: directory '$configDir' is not writable. Please set permissions (chmod 755 or 775) on the nMon root folder.");
    }

    $configContent = '<?php $config = array(
    "database_type"=>"mysql",
    "database_name"=>"' . addslashes($_POST['dbname']) . '",
    "server"=>"' . addslashes($_POST['dbserver']) . '",
    "username"=>"' . addslashes($_POST['dbuser']) . '",
    "password"=>"' . addslashes($_POST['dbpassword']) . '",
    "charset"=>"utf8",
    "port"=>3306,
    "encryption_key"=>"' . $encryption_key . '" ); ?>';

    // Method 1: file_put_contents (preferred)
    $bytesWritten = @file_put_contents($configPath, $configContent);

    if ($bytesWritten === false) {
        // Method 2: fopen/fwrite fallback
        $file = @fopen($configPath, "w");
        if ($file === false) {
            throw new Exception("Cannot create config.php: fopen() failed. Check that the nMon root folder is writable by the web server (chmod 755 or 775).");
        }
        $writeResult = fwrite($file, $configContent);
        fclose($file);

        if ($writeResult === false) {
            throw new Exception("Cannot write to config.php: fwrite() failed. Check disk space and folder permissions.");
        }
    }

    // Verify config.php was actually created and is readable
    if (!file_exists($configPath)) {
        throw new Exception("config.php was not created. Unknown error occurred. Check folder permissions.");
    }

    $verifyContent = @file_get_contents($configPath);
    if ($verifyContent === false || strpos($verifyContent, 'database_name') === false) {
        throw new Exception("config.php exists but appears empty or corrupted. Check folder permissions and disk space.");
    }

    $configWritten = true;
    $ok = true;

} catch(Exception $e) {
    $ok = false;
    $errorMessage = $e->getMessage();
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
    <div class="login-box" style="width: 600px;">
      <div class="login-logo">
        <b>n</b>Mon Installer
      </div><!-- /.login-logo -->
      <div class="login-box-body">

          <?php if($ok == true && $configWritten): ?>
                  <div class="row"><div class='col-md-12'><div class="alert alert-success" role="alert">Installation Successful!</div></div></div>
                        <p class="login-box-msg">Please delete the "install" folder before signing in.</p>
                        <p>
                            <b>Admin Email </b><?php echo htmlspecialchars($_POST['email']); ?><br>
                            <b>Admin Password </b><?php echo htmlspecialchars($_POST['password']); ?><br>
                        </p>
                        <p class="login-box-msg">Click <a href="../">here</a> to login.</p>
          <?php endif; ?>

          <?php if($ok == false || !$configWritten): ?>
                  <div class="row"><div class='col-md-12'><div class="alert alert-danger" role="alert">Installation Error!</div></div></div>
                        <p class="login-box-msg">We were unable to install nMon. Please try again.</p>
                        <?php if(!empty($errorMessage)): ?>
                        <div class="alert alert-warning">
                            <p class="text-bold"><i class="icon fa fa-ban"></i> Error Details</p>
                            <p><?php echo htmlspecialchars($errorMessage); ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="row">
                          <div class="col-xs-6"><button onclick="window.history.back()" class="btn btn-default btn-block btn-flat">Back</button></div><!-- /.col -->
                          <div class="col-xs-6"></div><!-- /.col -->
                        </div>
          <?php endif; ?>


      </div><!-- /.login-box-body -->
    </div><!-- /.login-box -->


    <!-- jQuery 2.1.3 -->
    <script src="../template/assets/plugins/jQuery/jQuery-2.2.3.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="../template/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>

  </body>


</html>
