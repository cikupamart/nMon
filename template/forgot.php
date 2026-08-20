<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo strip_tags ( getConfigValue("app_name") ); ?></title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

        <?php if(file_exists($scriptpath . "/assets/icon.png")) { ?>
            <link rel="shortcut icon" href="assets/icon.png"/>
        <?php } else { ?>
            <link rel="shortcut icon" href="template/assets/icon.png"/>
        <?php } ?>

        <?php if(file_exists($scriptpath . "/assets/icon.png")) { ?>
            <link rel="apple-touch-icon" href="assets/icon-large.png"/>
            <link rel="image_src" href="assets/icon-large.png"/>
        <?php } else { ?>
            <link rel="apple-touch-icon" href="template/assets/icon-large.png"/>
            <link rel="image_src" href="template/assets/icon-large.png"/>
        <?php } ?>


        <!-- Bootstrap 3.4.1 (CDN) -->
		    <link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
        <!-- Font Awesome 4.7 (CDN) -->
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <!-- Theme style -->
		    <link href="template/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    </head>


  <body class="login-page">
    <div class="login-box">
      <div class="login-logo">

          <?php if(file_exists($scriptpath . "/assets/logo.png")) { ?>
              <img src="assets/logo.png" class="img-responsive" style="margin: 0 auto;">
          <?php } ?>

        <?php echo getConfigValue("app_name"); ?>
      </div><!-- /.login-logo -->


<?php if(!isset($_GET['resetkey'])) { ?>
      <div class="login-box-body">
        <p class="login-box-msg"><?php _e('Enter email address to reset your password'); ?></p>
        <?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
        <form action="?route=forgot" method="post">
          <div class="form-group has-feedback">
            <input type="email" name="email" class="form-control" placeholder="<?php _e('Email'); ?>" required autofocus/>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
          </div>
          <div class="row">
            <div class="col-xs-6">

            </div><!-- /.col -->
            <div class="col-xs-6">
              <button type="submit" class="btn btn-primary btn-block btn-flat" ><?php _e('Continue'); ?></button>
            </div><!-- /.col -->
          </div>
		<input type="hidden" name="resetConfirmation"/>
        </form>
      </div><!-- /.login-box-body -->
      <br><p class="text-center"><a href="?route=login"><?php _e('Log In'); ?></a></p>
<?php } ?>

<?php if(isset($_GET['resetkey'])) { ?>
      <div class="login-box-body">
        <p class="login-box-msg">Enter new password</p>
        <?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
        <form action="?route=forgot" method="post">
            <div class="form-group has-feedback">
              <input type="password" name="password" class="form-control" placeholder="<?php _e('New Password'); ?>" required autofocus/>
              <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
          <div class="row">
            <div class="col-xs-8">

            </div><!-- /.col -->
            <div class="col-xs-4">
              <button type="submit" class="btn btn-primary btn-block btn-flat"><?php _e('Continue'); ?></button>
            </div><!-- /.col -->
          </div>
		<input type="hidden" name="resetPassword"/>
        <input type="hidden" name="resetkey" value="<?php echo $_GET['resetkey']; ?>"/>
        </form>
      </div><!-- /.login-box-body -->
      <br><p class="text-center"><a href="?route=login"><?php _e('Log In'); ?></a></p>
<?php } ?>
    </div><!-- /.login-box -->

    <!-- jQuery 3.7.1 (CDN) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <!-- Bootstrap 3.4.1 (CDN) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

  </body>


</html>
