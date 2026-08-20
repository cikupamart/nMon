<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php if(isset($pageTitle)) echo $pageTitle . " - "; ?><?php echo strip_tags ( getConfigValue("app_name") ); ?></title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">


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


        <!-- Bootstrap 3.4.1 (CDN with security fixes) -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
        <!-- Font Awesome -->
        <link rel="stylesheet" href="template/assets/plugins/font-awesome/css/font-awesome.min.css">
        <!-- Ionicons -->
        <link rel="stylesheet" href="template/assets/plugins/ionicons/css/ionicons.min.css">
        <!-- DataTables -->
        <link rel="stylesheet" href="template/assets/plugins/datatables/datatables.min.css">
        <!-- Select2 -->
        <link rel="stylesheet" href="template/assets/plugins/select2/select2.min.css">
        <!-- Pace style -->
        <link rel="stylesheet" href="template/assets/plugins/pace/pace.min.css">
        <!-- Date Picker -->
		<link href="template/assets/plugins/datepicker/datepicker3.css" rel="stylesheet" type="text/css" />
        <!-- daterange picker -->
        <link href="template/assets/plugins/daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
        <!-- summernote wysihtml5 - text editor -->
		<link href="template/assets/plugins/summernote/summernote.css" rel="stylesheet" type="text/css" />

        <!-- jvectormap -->
        <link rel="stylesheet" href="template/assets/plugins/jvectormap/jquery-jvectormap-1.2.2.css">
        <!-- Google Font -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">

		<!-- Theme style -->
		<link href="template/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
		<!-- AdminLTE Skins. Choose a skin from the css/skins
			 folder instead of downloading all of them to reduce the load. -->
		<link href="template/assets/dist/css/skins/_all-skins.css" rel="stylesheet" type="text/css" />

        <!-- CUSTOM CSS -->
		<link href="template/assets/custom.css" rel="stylesheet" type="text/css" />

        <!-- jQuery 3.7.1 (CDN) -->
		<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

        <!-- DataTables -->
        <script src="template/assets/plugins/datatables/datatables.min.js"></script>

    </head>
    <body class="hold-transition <?php echo $liu['theme']; ?> <?php echo $liu['layout']; ?> <?php if($liu['sidebar']=="collapsed") echo "sidebar-collapse"; ?> sidebar-mini">
		<div class="wrapper">
        <!-- header logo: style can be found in header.less -->
        <header class="main-header">
            <a href="?route=dashboard" class="logo">
              <!-- mini logo for sidebar mini 50x50 pixels -->
              <span class="logo-mini"><b><i class="fa fa-heartbeat"></i></b></span>
              <!-- logo for regular state and mobile devices -->
              <span class="logo-lg"><?php echo getConfigValue("app_name"); ?></span>
            </a>
            <!-- Header Navbar: style can be found in header.less -->
            <nav class="navbar navbar-static-top" role="navigation">
              <!-- Sidebar toggle button-->
              <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                <span class="sr-only"><?php _e('Toggle navigation'); ?></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
              </a>
                <div class="navbar-custom-menu">
                    <ul class="nav navbar-nav">

                        <!-- Servers Notifications -->
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-server"></i>
                                <?php if(count($main_servers_unresolved) == 0) { ?><span class="label label-success"><i class="fa fa-check"></i></span><?php } ?>
                                <?php if(count($main_servers_unresolved) > 0) { ?><span class="label label-warning"><?php echo count($main_servers_unresolved); ?></span><?php } ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header">
                                    <?php if(count($main_servers_unresolved) == 0) { ?><?php _e('Hooray! All servers are healthy.') ?><?php } ?>
                                    <?php if(count($main_servers_unresolved) > 0) { ?><?php _e('You have') ?> <?php echo count($main_servers_unresolved); ?> <?php _e('server alerts.') ?><?php } ?>
                                </li>

                                <li>
                                    <ul class="menu">
                                        <?php if(count($main_servers_unresolved) == 0) { ?>
                                            <li class="text-center"><i class="fa fa-check-circle fa-5x text-green" style="padding:50px;"></i></li>
                                        <?php } ?>

                                        <?php foreach($main_servers_unresolved as $incident) { ?>
                                            <li>
                                                <a href="?route=servers/manage-<?php echo getSingleValue("app_servers","type",$incident['serverid']); ?>&id=<?php echo $incident['serverid']; ?>">
                                                    <?php if($incident['status'] == 2) { ?>
                                                        <i class="fa fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 3) { ?>
                                                        <i class="fa fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 0) { ?>
                                                        <i class="fa fa-warning text-green" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                                                    <?php } ?>

                                                    <?php echo getSingleValue("app_servers","name",$incident['serverid']); ?> -
                                                    <?php if($incident['type'] == "nodata") _e('No Data'); ?>
                                                    <?php if($incident['type'] == "cpu") _e('CPU Usage %'); ?>
                                                    <?php if($incident['type'] == "cpuio") _e('CPU IO Wait %'); ?>
                                                    <?php if($incident['type'] == "load1min") _e('System Load 1 Min'); ?>
                                                    <?php if($incident['type'] == "load5min") _e('System Load 5 Min'); ?>
                                                    <?php if($incident['type'] == "load15min") _e('System Load 15 Min'); ?>
                                                    <?php if($incident['type'] == "service") _e('Service/Process Not Running'); ?>

                                                    <?php if($incident['type'] == "ram") _e('RAM Usage %'); ?>
                                                    <?php if($incident['type'] == "ramMB") _e('RAM Usage MB'); ?>
                                                    <?php if($incident['type'] == "swap") _e('Swap Usage %'); ?>
                                                    <?php if($incident['type'] == "swapMB") _e('Swap Usage MB'); ?>
                                                    <?php if($incident['type'] == "disk") _e('Disk Usage % (Aggregated)'); ?>
                                                    <?php if($incident['type'] == "diskGB") _e('Disk Usage GB (Aggregated)'); ?>
                                                    <?php
														if(strpos($incident['type'],'disk:') !== false) {
															$disk_text = explode(":",$incident['type']);
															_e('Disk Usage %:'); echo " " . $disk_text[1];
														}
													?>

													<?php
														if(strpos($incident['type'],'diskGB:') !== false) {
															$disk_text = explode(":",$incident['type']);
															_e('Disk Usage GB:'); echo " " . $disk_text[1];
														}
													?>

                                                    <?php if($incident['type'] == "connections") _e('Connections'); ?>
                                                    <?php if($incident['type'] == "ssh") _e('SSH Sessions'); ?>
                                                    <?php if($incident['type'] == "ping") _e('Ping Latency'); ?>
                                                    <?php if($incident['type'] == "netdl") _e('Network Download Speed MB/s'); ?>
                                                    <?php if($incident['type'] == "netup") _e('Network Upload Speed MB/s'); ?>

                                                    <?php if($incident['type'] == "nodata") { ?>

                                                    <?php } elseif($incident['type'] == "service") { ?>
														<b><?php echo $incident['comparison_limit']; ?></b>

                                                    <?php } else { ?>
                                                        <?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
                                                    <?php } ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            </ul>
                        </li>


                        <!-- Websites Notifications -->
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-globe"></i>
                                <?php if(count($main_websites_unresolved) == 0) { ?><span class="label label-success"><i class="fa fa-check"></i></span><?php } ?>
                                <?php if(count($main_websites_unresolved) > 0) { ?><span class="label label-warning"><?php echo count($main_websites_unresolved); ?></span><?php } ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header">
                                    <?php if(count($main_websites_unresolved) == 0) { ?><?php _e('Hooray! All websites are healthy.') ?><?php } ?>
                                    <?php if(count($main_websites_unresolved) > 0) { ?><?php _e('You have') ?> <?php echo count($main_websites_unresolved); ?> <?php _e('website alerts.') ?><?php } ?>
                                </li>

                                <li>
                                    <ul class="menu">
                                        <?php if(count($main_websites_unresolved) == 0) { ?>
                                            <li class="text-center"><i class="fa fa-check-circle fa-5x text-green" style="padding:50px;"></i></li>
                                        <?php } ?>

                                        <?php foreach($main_websites_unresolved as $incident) { ?>
                                            <li>
                                                <a href="?route=websites/manage&id=<?php echo $incident['websiteid']; ?>">
                                                    <?php if($incident['status'] == 2) { ?>
                                                        <i class="fa fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 3) { ?>
                                                        <i class="fa fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 0) { ?>
                                                        <i class="fa fa-warning text-green" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                                                    <?php } ?>

                                                    <?php echo getSingleValue("app_websites","name",$incident['websiteid']); ?> -
                                                    <?php if($incident['type'] == "responsecode") _e('HTTP Response Code'); ?>
													<?php if($incident['type'] == "loadtime") _e('Load Time'); ?>
													<?php if($incident['type'] == "searchstringmissing") _e('Search String Missing'); ?>

													<?php if($incident['type'] == "searchstringmissing") { ?>

													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            </ul>
                        </li>



                        <!-- Checks Notifications -->
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-check-circle"></i>
                                <?php if(count($main_checks_unresolved) == 0) { ?><span class="label label-success"><i class="fa fa-check"></i></span><?php } ?>
                                <?php if(count($main_checks_unresolved) > 0) { ?><span class="label label-warning"><?php echo count($main_checks_unresolved); ?></span><?php } ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header">
                                    <?php if(count($main_checks_unresolved) == 0) { ?><?php _e('Hooray! All checks are healthy.') ?><?php } ?>
                                    <?php if(count($main_checks_unresolved) > 0) { ?><?php _e('You have') ?> <?php echo count($main_checks_unresolved); ?> <?php _e('checks alerts.') ?><?php } ?>
                                </li>

                                <li>
                                    <ul class="menu">
                                        <?php if(count($main_checks_unresolved) == 0) { ?>
                                            <li class="text-center"><i class="fa fa-check-circle fa-5x text-green" style="padding:50px;"></i></li>
                                        <?php } ?>

                                        <?php foreach($main_checks_unresolved as $incident) { ?>
                                            <li>
                                                <a href="?route=checks/manage&id=<?php echo $incident['checkid']; ?>">
                                                    <?php if($incident['status'] == 2) { ?>
                                                        <i class="fa fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 3) { ?>
                                                        <i class="fa fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                                                    <?php } ?>

                                                    <?php if($incident['status'] == 0) { ?>
                                                        <i class="fa fa-warning text-green" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                                                    <?php } ?>

                                                    <?php echo getSingleValue("app_checks","name",$incident['checkid']); ?> -
                                                    <?php if($incident['type'] == "offline") _e('Check Offline'); ?>
													<?php if($incident['type'] == "responsetime") _e('Response Time'); ?>
													<?php if($incident['type'] == "blacklisted") _e('Listed In Blacklist'); ?>
													<?php if($incident['type'] == "dnsfailed") _e('DNS Lookup Failed'); ?>
                                                    <?php if($incident['type'] == "unsuccessful") _e('Unsuccessful'); ?>

													<?php if($incident['type'] == "offline" || $incident['type'] == "blacklisted" || $incident['type'] == "dnsfailed" || $incident['type'] == "unsuccessful") { ?>
													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </li>
                            </ul>
                        </li>

                        <!-- Autorefresh -->
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <i class="fa fa-refresh"></i>
                                <?php if($liu['autorefresh'] > 0) { ?>
                                    <?php if(in_array($route, $autorefresh_pages)) { ?>
                                        <span class="label label-info" id="timer"><?php echo $liu['autorefresh']/1000; ?></span>
                                    <?php } else { ?>
                                        <span class="label label-info"><i class="fa fa-check"></i></span>
                                    <?php } ?>
                                <?php } ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li class="header text-center"><?php _e('Autorefresh'); ?></li>
                                <li>
                                    <ul class="menu">
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=0"><i class="fa <?php if($liu['autorefresh'] == 0) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Disabled'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=30000"><i class="fa <?php if($liu['autorefresh'] == 30000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 30 Seconds'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=60000"><i class="fa <?php if($liu['autorefresh'] == 60000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 1 Minute'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=120000"><i class="fa <?php if($liu['autorefresh'] == 120000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 2 Minutes'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=300000"><i class="fa <?php if($liu['autorefresh'] == 300000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 5 Minutes'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=600000"><i class="fa <?php if($liu['autorefresh'] == 600000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 10 Minutes'); ?></a></li>
                                        <li><a href="?qa=setAutorefresh&reroute=<?php echo $route; ?>&routeid=<?php if(isset($_GET['id'])) echo $_GET['id']; ?>&section=<?php if(isset($_GET['section'])) echo $_GET['section']; ?>&autorefresh=900000"><i class="fa <?php if($liu['autorefresh'] == 900000) echo "fa-dot-circle-o"; else echo "fa-circle-o"; ?> text-blue"></i> <?php _e('Every 15 Minutes'); ?></a></li>
                                    </ul>
                                </li>
                            </ul>
                        </li>

                        <!-- Quick Actions Menu -->
                        <li class="dropdown notifications-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown"><i class="fa fa-star"></i></a>
                            <ul class="dropdown-menu">
                                <li class="header"><?php _e('Quick actions'); ?></li>
                                <li>
                                    <ul class="menu">
                                        <?php if(in_array("addServer",$perms)) { ?>
                                            <li><a href="#" onClick='showM("?modal=servers/add&reroute=servers&routeid=&section=");return false' data-toggle="modal"><i class="fa fa-server text-blue"></i> <?php _e('Add Server'); ?></a></li>
                                        <?php } ?>

                                        <?php if(in_array("addWebsite",$perms)) { ?>
                                            <li><a href="#" onClick='showM("?modal=websites/add&reroute=servers&routeid=&section=");return false' data-toggle="modal"><i class="fa fa-globe text-blue"></i> <?php _e('Add Website'); ?></a></li>
                                        <?php } ?>

                                        <?php if(in_array("addCheck",$perms)) { ?>
                                            <li><a href="#" onClick='showM("?modal=checks/add&reroute=servers&routeid=&section=");return false' data-toggle="modal"><i class="fa fa-check-circle text-blue"></i> <?php _e('Add Check'); ?></a></li>
                                        <?php } ?>

                                        <?php if(in_array("addContact",$perms)) { ?>
                                            <li><a href="#" onClick='showM("?modal=contacts/add&reroute=servers&routeid=&section=");return false' data-toggle="modal"><i class="fa fa-user-circle text-blue"></i> <?php _e('Add Contact'); ?></a></li>
                                        <?php } ?>

                                        <?php if(in_array("addPage",$perms)) { ?>
                                            <li><a href="#" onClick='showM("?modal=pages/add&reroute=servers&routeid=&section=");return false' data-toggle="modal"><i class="fa fa-bookmark text-blue"></i> <?php _e('Add Page'); ?></a></li>
                                        <?php } ?>

                                    </ul>
                                </li>
                            </ul>
                        </li>

                        <!-- User Account: style can be found in dropdown.less -->
                        <li class="dropdown user user-menu">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                                <img src="<?php echo getGravatar($liu['email'],"84"); ?>" class="user-image" alt="User Image" />
                                <span class="hidden-xs"><?php echo $liu['name']; ?> <i class="caret"></i></span>
                            </a>
                            <ul class="dropdown-menu">
                                <!-- User image -->
                                <li class="user-header">
                                    <img src="<?php echo getGravatar($liu['email'],"128"); ?>" class="img-circle" />
                                    <p>
                                        <?php echo $liu['name']; ?>
                                        <small><?php echo $liu['email']; ?></small>
                                    </p>
                                </li>
                                <!-- Menu Footer-->
                                <li class="user-footer">
                                    <div class="pull-left">
                                        <a href="?route=profile" class="btn btn-default btn-flat"><?php _e('Profile'); ?></a>
                                    </div>
                                    <div class="pull-right">
                                        <a href="?route=signout" class="btn btn-default btn-flat"><?php _e('Sign Out'); ?></a>
                                    </div>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </nav>
        </header>

        <aside class="main-sidebar">
          <section class="sidebar">

              <!-- Sidebar user panel -->
              <div class="user-panel">
                <div class="pull-left image">
                  <img src="<?php echo getGravatar($liu['email'],"45"); ?>" class="img-circle" alt="User Image"  style="max-height:45px;max-width:45px;">
                </div>
                <div class="pull-left info">
                  <p><?php echo $liu['name']; ?></p>
                  <a href="#"><i class="fa fa-circle text-success"></i> <?php _e('Online'); ?></a>
                </div>
              </div>
              <?php if(in_array("search",$perms)) { ?>
    			<!-- search form -->
    			<form method="get" class="sidebar-form">
    				<div class="input-group">
    					<input type="text" name="q" class="form-control" placeholder="<?php _e('Search...'); ?>" required/>
                    <input type="hidden" name="route" value="search" />
    					<span class="input-group-btn">
    						<button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i></button>
    					</span>
    				</div>
    			</form>
                <?php } ?>
  				<!-- /.search form -->
            <!-- sidebar menu: : style can be found in sidebar.less -->
            <ul class="sidebar-menu" data-widget="tree">
			  <li class="header"><?php _e('MAIN NAVIGATION'); ?></li>

              <li <?php if(strpos($route,'dashboard') !== false) echo 'class="active"'; ?>>
                  <a href="?route=dashboard">
                      <i class="fa fa-dashboard fa-fw"></i> <span><?php _e('Dashboard'); ?></span>
                  </a>
              </li>

              <?php if(in_array("viewServers",$perms)) { ?>
                  <li <?php if(strpos($route,'servers') !== false) echo 'class="active"'; ?>>
                      <a href="?route=servers">
                          <i class="fa fa-server fa-fw"></i> <span><?php _e('Servers'); ?></span>
                      </a>
                  </li>
              <?php } ?>

              <?php if(in_array("viewWebsites",$perms)) { ?>
              <li <?php if(strpos($route,'websites') !== false) echo 'class="active"'; ?>>
                  <a href="?route=websites">
                      <i class="fa fa-globe fa-fw"></i> <span><?php _e('Websites'); ?></span>
                  </a>
              </li>
              <?php } ?>

              <?php if(in_array("viewChecks",$perms)) { ?>
              <li <?php if(strpos($route,'checks') !== false) echo 'class="active"'; ?>>
                  <a href="?route=checks">
                      <i class="fa fa-check-circle fa-fw"></i> <span><?php _e('Checks'); ?></span>
                  </a>
              </li>
              <?php } ?>

              <li class="treeview<?php if(strpos($route,'alerting/') !== false) echo ' active'; ?>">
                  <a href="#">
                      <i class="fa fa-comments fa-fw"></i> <span><?php _e('Alerting'); ?></span>
                      <i class="fa fa-angle-left pull-right"></i>
                  </a>
                  <ul class="treeview-menu">
                      <li <?php if(strpos($route,'alerting/contacts') !== false) echo 'class="active"'; ?>><a href="?route=alerting/contacts"><i class="fa fa-angle-double-right"></i> <?php _e('Contacts'); ?></a></li>
                      <li <?php if(strpos($route,'alerting/log') !== false) echo 'class="active"'; ?>><a href="?route=alerting/log"><i class="fa fa-angle-double-right"></i> <?php _e('Log'); ?></a></li>
                  </ul>
              </li>              <li <?php if(strpos($route,'pages') !== false) echo 'class="active"'; ?>>
                  <a href="?route=pages">
                      <i class="fa fa-bookmark fa-fw"></i> <span><?php _e('Pages'); ?></span>
                  </a>
              </li>

              <?php if(in_array("viewMaps",$perms)) { ?>
              <li <?php if(strpos($route,'maps') !== false) echo 'class="active"'; ?>>
                  <a href="?route=maps">
                      <i class="fa fa-map-marker fa-fw"></i> <span><?php _e('Live Maps'); ?></span>
                  </a>
              </li>
              <?php } ?>




              <?php if(in_array("viewSystem",$perms)) { ?>
              <li class="treeview<?php if(strpos($route,'system/') !== false) echo ' active'; ?>">
                  <a href="#">
                      <i class="fa fa-cogs fa-fw"></i> <span><?php _e('System'); ?></span>
                      <i class="fa fa-angle-left pull-right"></i>
                  </a>
                  <ul class="treeview-menu">
                      <?php if(in_array("viewUsers",$perms)) { ?><li <?php if(strpos($route,'system/users') !== false) echo 'class="active"'; ?>><a href="?route=system/users"><i class="fa fa-angle-double-right"></i> <?php _e('Users'); ?></a></li><?php } ?>
                      <?php if(in_array("viewRoles",$perms)) { ?><li <?php if(strpos($route,'system/roles') !== false) echo 'class="active"'; ?>><a href="?route=system/roles"><i class="fa fa-angle-double-right"></i> <?php _e('Roles'); ?></a></li><?php } ?>
                      <?php if(in_array("viewGroups",$perms)) { ?><li <?php if(strpos($route,'system/groups') !== false) echo 'class="active"'; ?>><a href="?route=system/groups"><i class="fa fa-angle-double-right"></i> <?php _e('Groups'); ?></a></li><?php } ?>

                      <?php if(in_array("viewLogs",$perms)) { ?><li <?php if($route == "system/logs") echo 'class="active"'; ?>><a href="?route=system/logs"><i class="fa fa-angle-double-right"></i> <?php _e('Logs'); ?></a></li><?php } ?>
                      <?php if(in_array("manageSettings",$perms)) { ?><li <?php if($route == "system/settings") echo 'class="active"'; ?>><a href="?route=system/settings"><i class="fa fa-angle-double-right"></i> <?php _e('Settings'); ?></a></li><?php } ?>

                  </ul>
              </li>
              <?php } ?>
          </ul>
        </section>
        <!-- /.sidebar -->
      </aside>
