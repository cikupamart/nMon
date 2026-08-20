<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title><?php echo $page['name']; ?></title>
        <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
        <link rel="shortcut icon" href="template/assets/icon.png"/>
        <link rel="apple-touch-icon" href="template/assets/icon-large.png"/>
        <link rel="image_src" href="template/assets/icon-large.png"/>
        <!-- Bootstrap 3.4.1 (CDN) -->
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-HSMxcRTRxnN+Bdg0JdbxYKrThecOKuH5zCYotlSAcp1+c8xmyTe9GYg1l9a69psu" crossorigin="anonymous">
        <!-- Font Awesome 4.7 (CDN) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
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
    </head>


    <body class="hold-transition skin-blue layout-top-nav">
        <div class="wrapper">
            <!-- Full Width Column -->
            <div class="content-wrapper">
                <div class="container">

                    <?php if($page) { ?>

                        <?php if($raise_deprecated === true) { ?>
                            <section class="content-header">
                                <div class="alert alert-warning alert-dismissible">
                                    <h4><i class="icon fa fa-warning"></i> <?php _e('Deprecated!'); ?></h4>
                                    <?php _e('Referencing a public page by ID is now deprecated and will be removed in the next version.'); ?><br>
                                    <?php _e('You can get the new link from the admin panel.'); ?>
                                </div>
                            </section>
                        <?php } ?>

                        <!-- Content Header (Page header) -->
                        <section class="content-header">
                            <h1><?php echo $page['name']; ?></h1>

                        </section>

                        <!-- Main content -->
                        <section class="content">

                            <?php if(strlen($page['info']) > 15) { ?>
                                <div class="box box-default">
                                    <div class="box-body">
                                        <?php echo $page['info']; ?>
                                    </div>
                                    <!-- /.box-body -->
                                </div>
                                <!-- /.box -->
                            <?php } ?>


                            <?php if(!empty($servers)) { ?>
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-server"></i> <?php _e('Servers'); ?></h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="no-sort" style="width:1%"></th>
                                                        <th><?php _e('ID'); ?></th>
                                                        <th><?php _e('Name'); ?></th>
                                                        <th><?php _e('Group'); ?></th>

                                                        <th style="width:1%"><?php _e('OS'); ?></th>
                                                        <th style="width:1%"><?php _e('CPU'); ?></th>
                                                        <th style="width:1%"><?php _e('RAM'); ?></th>
                                                        <th style="width:1%"><?php _e('Disk'); ?></th>
                                                        <th style="width:1%"><?php _e('Load'); ?></th>
                                                        <th style=""><?php _e('Net'); ?></th>
                                                        <th><?php _e('Uptime'); ?></th>
                                                        <th><?php _e('Last Seen'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                									<?php foreach ($servers as $server) { $latest = Server::latestData($server['id']); ?>

                		                                <tr>
                											<td>
                												<?php if($server['status'] == 1) { ?>
                													<i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
                												<?php } elseif($server['status'] == 2) { ?>
                													<i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                												<?php } elseif($server['status'] == 3) { ?>
                													<i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                												<?php } else { ?>
                													<i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                												<?php } ?>
                											</td>
                		                                    <td><?php echo $server['id']; ?></td>
                		                                    <td><?php echo $server['name']; ?></td>
                											<td><?php echo getSingleValue("app_groups","name",$server['groupid']); ?></td>
                											<?php if(!empty($latest)) { ?>

                												<td>
                													<?php
                														$os = Server::extractData('os', $latest['data'], true);
                														if(stripos($os, 'centos') !== false) { echo '<img src="template/images/centos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'cloudlinux') !== false) { echo '<img src="template/images/cloudlinux.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'coreos') !== false) { echo '<img src="template/images/coreos.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'debian') !== false) { echo '<img src="template/images/debian.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'fedora') !== false) { echo '<img src="template/images/fedora.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'freebsd') !== false) { echo '<img src="template/images/freebsd.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'proxmox') !== false) { echo '<img src="template/images/proxmox.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'redhat') !== false) { echo '<img src="template/images/redhat.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'routeros') !== false) { echo '<img src="template/images/routeros.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'suse') !== false) { echo '<img src="template/images/suse.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'ubuntu') !== false) { echo '<img src="template/images/ubuntu.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														elseif(stripos($os, 'windows') !== false) { echo '<img src="template/images/windows.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                														else { echo '<img src="template/images/other.png" data-toggle="tooltip" title="'.$os.'" alt="'.$os.'">'; }
                													?>
                												</td>

                												<?php $qstats = Server::quickStats($latest['data'], $server['type']); ?>

                												<td>
                													<span data-toggle="tooltip" title="<?php echo $qstats['cpuused']; ?><?php _e('% Used'); ?>">
                														<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['cpuused']; ?>/100</span>
                													</span>
                												</td> <!-- cpu -->

                												<td>
                													<?php if($server['type'] == "linux") { ?>
                														<span data-toggle="tooltip" data-html="true"
                															title="<?php echo formatBytes($qstats['ramtotal']*1024); ?> <?php _e('Total'); ?> <br> <?php echo formatBytes($qstats['ramreal']*1024); ?> <?php _e('Used'); ?> <br> <?php echo formatBytes($qstats['ramfree']*1024); ?> <?php _e('Free'); ?> ">
                															<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['ramreal']; ?>/<?php echo $qstats['ramtotal']; ?></span>
                														</span>
                													<?php } ?>

                													<?php if($server['type'] == "windows") { ?>
                														<span data-toggle="tooltip" data-html="true"
                															title="<?php echo formatBytes($qstats['ramtotal']); ?> <?php _e('Total'); ?> <br> <?php echo formatBytes($qstats['ramreal']); ?> <?php _e('Used'); ?> <br> <?php echo formatBytes($qstats['ramfree']); ?> <?php _e('Free'); ?> ">
                															<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['ramreal']; ?>/<?php echo $qstats['ramtotal']; ?></span>
                														</span>
                													<?php } ?>
                												</td> <!-- ram -->

                												<td>
                													<span data-toggle="tooltip" title="<?php echo $qstats['totaldiskusedp']; ?><?php _e('% Used'); ?>">
                														<span data-peity='{ "fill": ["#87CEEB", "#eeeeee"], "innerRadius": 10, "radius": 15 }' class="donut"><?php echo $qstats['totaldiskusedp']; ?>/100</span>
                													</span>
                												</td> <!-- disk -->

                												<td>
                													<?php if($server['type'] == "linux") { ?>
                														<span data-toggle="tooltip" data-html="true" title="<?php echo $qstats['load1']; ?> <?php _e('1 Min'); ?> <br><?php echo $qstats['load5']; ?> <?php _e('5 Min'); ?> <br><?php echo $qstats['load15']; ?> <?php _e('15 Min'); ?> <br>">
                															<?php echo $qstats['load1']; ?>
                														</span>
                													<?php } ?>
                													<?php if($server['type'] == "windows") { ?>-<?php } ?>
                												</td> <!-- load -->

                												<td>
                													<?php echo formatBytes($qstats['totalin']); ?><?php _e('/s'); ?> <i class="fa fa-long-arrow-down"></i><br>
                													<?php echo formatBytes($qstats['totalout']); ?><?php _e('/s'); ?> <i class="fa fa-long-arrow-up"></i>
                												</td>

                												<td>
                													<span data-toggle="tooltip" title="<?php _e('Last 24 Hours'); ?> <?php echo Server::uptimePercentage($server['id'],"24h"); ?>%">
                														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"24h"); ?>/100</span>
                													</span>

                													<span data-toggle="tooltip" title="<?php _e('Last 7 Days'); ?> <?php echo Server::uptimePercentage($server['id'],"7days"); ?>%">
                														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"7days"); ?>/100</span>
                													</span>

                													<span data-toggle="tooltip" title="<?php _e('Last 30 Days'); ?> <?php echo Server::uptimePercentage($server['id'],"30days"); ?>%">
                														<span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Server::uptimePercentage($server['id'],"30days"); ?>/100</span>
                													</span>
                												</td>

                												<td>
                													<?php //echo Server::uptime(Server::extractData('uptime', $latest['data'], true)); ?>
                													<?php echo smartDate($latest['timestamp']); ?></td>
                												</td>


                											<?php } ?>

                											<?php if(empty($latest)) { ?>

                												<td></td> <!-- os -->
                												<td></td> <!-- cpu -->
                												<td></td> <!-- ram -->
                												<td></td> <!-- disk -->
                												<td></td> <!-- load -->
                												<td></td> <!-- net -->
                												<td></td> <!-- uptime -->
                												<td>
                													<?php _e('-'); ?><br>
                													<?php _e('Never Seen'); ?>
                												</td> <!-- uptime -->

                											<?php } ?>




                		                                </tr>

                									<?php } ?>
                								</tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
                                </div>
                                <!-- /.box -->
                            <?php } ?>

                            <?php if(!empty($websites)) { ?>
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-globe"></i> <?php _e('Websites'); ?></h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="table-responsive">
                                            <table id="dataTablesFullNoOrder" class="table table-striped table-hover table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="no-sort" style="width:1%"></th>
                                                        <th><?php _e('ID'); ?></th>
                                                        <th><?php _e('Name'); ?></th>
                                                        <th><?php _e('Group'); ?></th>
                                                        <th><?php _e('Last Checked'); ?></th>
                                                        <th><?php _e('Load Time'); ?></th>
                                                        <th><?php _e('Uptime'); ?> <span class="text-gray"></span></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($websites as $website) { ?>
                                                        <tr>
                                                            <td>
                                                                <?php if($website['status'] == 1) { ?>
                                                                    <i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
                                                                <?php } elseif($website['status'] == 2) { ?>
                                                                    <i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                                                                <?php } elseif($website['status'] == 3) { ?>
                                                                    <i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                                                                <?php } else { ?>
                                                                    <i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                                                                <?php } ?>
                                                            </td>
                                                            <td><?php echo $website['id']; ?></td>
                                                            <td><?php echo $website['name']; ?></td>
                                                            <td><?php echo getSingleValue("app_groups","name",$website['groupid']); ?></td>
                                                            <td><?php echo smartDate(Website::lastChecked($website['id'])); ?></td>
                                                            <td><?php echo Website::lastLoadTime($website['id']); ?></td>
                                                            <td>
                                                                <span data-toggle="tooltip" title="<?php _e('Last 24 Hours'); ?> <?php echo Website::uptime($website['id'],"24h"); ?>%">
                                                                    <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Website::uptime($website['id'],"24h"); ?>/100</span>
                                                                </span>

                                                                <span data-toggle="tooltip" title="<?php _e('Last 7 Days'); ?> <?php echo Website::uptime($website['id'],"7days"); ?>%">
                                                                    <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Website::uptime($website['id'],"7days"); ?>/100</span>
                                                                </span>

                                                                <span data-toggle="tooltip" title="<?php _e('Last 30 Days'); ?> <?php echo Website::uptime($website['id'],"30days"); ?>%">
                                                                    <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Website::uptime($website['id'],"30days"); ?>/100</span>
                                                                </span>
                                                            </td>

                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
                                </div>
                                <!-- /.box -->
                            <?php } ?>


                            <?php if(!empty($checks)) { ?>
                                <div class="box box-primary">
                                    <div class="box-header with-border">
                                        <h3 class="box-title"><i class="fa fa-check-circle"></i> <?php _e('Checks'); ?></h3>
                                    </div>
                                    <div class="box-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped table-hover table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="no-sort" style="width:1%"></th>
                                                        <th><?php _e('ID'); ?></th>
                                                        <th><?php _e('Name'); ?></th>
                                                        <th><?php _e('Type'); ?></th>
                                                        <th><?php _e('Group'); ?></th>
                                                        <th><?php _e('Last Checked'); ?></th>
                                                        <th><?php _e('Uptime'); ?></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($checks as $check) { ?>
                                                        <tr>
                                                            <td>
                                                                <?php if($check['status'] == 1) { ?>
                                                                    <i class="fa fa-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
                                                                <?php } elseif($check['status'] == 2) { ?>
                                                                    <i class="fa fa-circle fa-2x text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i>
                                                                <?php } elseif($check['status'] == 3) { ?>
                                                                    <i class="fa fa-circle fa-2x text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i>
                                                                <?php } else { ?>
                                                                    <i class="fa fa-2x fa-circle text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i>
                                                                <?php } ?>
                                                            </td>
                                                            <td><?php echo $check['id']; ?></td>
                                                            <td><?php echo $check['name']; ?></td>
                                                            <td>
                                                                <?php
                                                                    if($check['type'] == "tcp") { _e('TCP Port'); echo ": " . $check['port']; }
                                                                    if($check['type'] == "udp") { _e('UDP Port'); echo ": " . $check['port']; }
                                                                    if($check['type'] == "icmp") { _e('ICMP (Ping)'); echo ": " . $check['host']; }
                                                                    if($check['type'] == "dns") { _e('DNS Lookup'); echo ": " . $check['host']; }
                                                                    if($check['type'] == "callback") { _e('Callback'); }
                                                                    if($check['type'] == "blacklist") { _e('Blacklist Check'); echo ": " . $check['host']; }
                                                                ?>
                                                            </td>
                                                            <td><?php echo getSingleValue("app_groups","name",$check['groupid']); ?></td>
                                                            <td><?php echo smartDate(Check::lastChecked($check['id'])); ?></td>
                                                            <td>
                                                                <?php if($check['type'] != "callback") { ?>
                                                                    <span data-toggle="tooltip" title="<?php _e('Last 24 Hours'); ?> <?php echo Check::uptime($check['id'],"24h"); ?>%">
                                                                        <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"24h"); ?>/100</span>
                                                                    </span>

                                                                    <span data-toggle="tooltip" title="<?php _e('Last 7 Days'); ?> <?php echo Check::uptime($check['id'],"7days"); ?>%">
                                                                        <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"7days"); ?>/100</span>
                                                                    </span>

                                                                    <span data-toggle="tooltip" title="<?php _e('Last 30 Days'); ?> <?php echo Check::uptime($check['id'],"30days"); ?>%">
                                                                        <span data-peity='{ "fill": ["#0dca73", "#f14b4b"], "innerRadius": 8, "radius": 12 }' class="donut"><?php echo Check::uptime($check['id'],"30days"); ?>/100</span>
                                                                    </span>
                                                                <?php } ?>
                                                            </td>

                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    <!-- /.box-body -->
                                </div>
                                <!-- /.box -->
                            <?php } ?>



                        </section>
                        <!-- /.content -->

                    <?php } else { ?>
                        <section class="content-header">
                            <div class="alert alert-danger alert-dismissible">
                                <h4><i class="icon fa fa-ban"></i> <?php _e('Page Not Found!'); ?></h4>
                                <?php _e('This page does not exist or an invalid page key was provided.'); ?>
                            </div>
                        </section>
                    <?php } ?>
                </div>
                <!-- /.container -->
            </div>
            <!-- /.content-wrapper -->


            <footer class="main-footer">
              <div class="container">
                <div class="pull-right hidden-xs">
                    <?php _e('All times are'); ?> <?php echo getConfigValue("timezone"); ?>.
                    <?php _e('The time now is'); ?> <?php echo dateTimeDisplay($datetime); ?>.
                    <b><?php echo strip_tags ( getConfigValue("app_name") ); ?></b> 1.4 - <?php echo $total_time; ?><?php _e('s'); ?>
                </div>

                <strong><?php _e('Copyright'); ?> &copy; <?php echo date('Y'); ?> <?php echo getConfigValue("company_name"); ?>.</strong> <?php _e('All rights reserved.'); ?>
              </div>
              <!-- /.container -->
            </footer>


        </div>



		<!-- jQuery UI 1.11.4 -->
		<script src="template/assets/plugins/jQueryUI/jquery-ui.min.js"></script>
		<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
		<script>
		  $.widget.bridge('uibutton', $.ui.button);
		</script>
		<!-- Bootstrap 3.3.7 -->
		<script src="template/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
		<!-- PACE -->
		<script src="template/assets/plugins/pace/pace.min.js"></script>
		<!-- Select2 -->
	    <script src="template/assets/plugins/select2/select2.full.min.js"></script>
		<!-- DataTables -->
    	<script src="template/assets/plugins/datatables/datatables.min.js"></script>

		<!-- date range picker -->
		<script src="template/assets/plugins/daterangepicker/moment.min.js"></script>
		<script src="template/assets/plugins/daterangepicker/daterangepicker.js"></script>

		<!-- datepicker -->
		<script src="template/assets/plugins/datepicker/bootstrap-datepicker.js" type="text/javascript"></script>
		<!-- Summernote WYSIHTML5 -->
		<script src="template/assets/plugins/summernote/summernote.min.js" type="text/javascript"></script>
		<!-- Slimscroll -->
		<script src="template/assets/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
		<!-- Chart JS 2 -->
		<script src="template/assets/plugins/chartjs2/dist/Chart.bundle.min.js"></script>

		<!-- FastClick -->
		<script src='template/assets/plugins/fastclick/fastclick.min.js'></script>
		<!-- peity -->
		<script src='template/assets/plugins/peity/jquery.peity.min.js'></script>
		<!-- AdminLTE App -->
		<script src="template/assets/dist/js/app.min.js" type="text/javascript"></script>

		<!-- jvectormap  -->
		<script src="template/assets/plugins/jvectormap/jquery-jvectormap-1.2.2.min.js"></script>
		<script src="template/assets/plugins/jvectormap/jquery-jvectormap-world-mill-en.js"></script>

		<!-- nMon -->
		<script src="template/assets/app.js" type="text/javascript"></script>

		<script type="text/javascript">

			$(function() {
				var start = moment("<?php echo $_SESSION['range_start']; ?>");
				var end = moment("<?php echo $_SESSION['range_end']; ?>");

				function rangeSubmit(start, end, label) {
					$('#daterange-btn span').html(start.format('<?php echo strtoupper(jsFormat()); ?> HH:mm:ss') + ' - ' + end.format('<?php echo strtoupper(jsFormat()); ?> HH:mm:ss'));

					$('#range_start').val(start.format('YYYY-MM-DD HH:mm:ss'));
					$('#range_end').val(end.format('YYYY-MM-DD HH:mm:ss'));
					$('#range_label').val(label);
					$("#rangeForm").submit();
				}

				function cb(start, end) {
					$('#daterange-btn span').html(start.format('<?php echo strtoupper(jsFormat()); ?> HH:mm:ss') + ' - ' + end.format('<?php echo strtoupper(jsFormat()); ?> HH:mm:ss'));
				}

				$('#daterange-btn').daterangepicker({
					timePicker: true,
					timePickerIncrement: 5,
					timePicker24Hour: true,
					timePickerSeconds: true,
					locale: { format: '<?php echo strtoupper(jsFormat()); ?>' },
					startDate: start,
					endDate: end,
					ranges: {
						'Last 30 Minutes': [moment().subtract(30, 'minutes'), moment()],
						'Last 60 Minutes': [moment().subtract(1, 'hours'), moment()],
						'Last 3 Hours': [moment().subtract(3, 'hours'), moment()],
						'Last 6 Hours': [moment().subtract(6, 'hours'), moment()],
						'Last 12 Hours': [moment().subtract(12, 'hours'), moment()],
						'Last 24 Hours': [moment().subtract(24, 'hours'), moment()],
						'Last 3 Days': [moment().subtract(3, 'days'), moment()],
						'Last 7 Days': [moment().subtract(7, 'days'), moment()],
						'Last 30 Days': [moment().subtract(30, 'days'), moment()],
					}
				}, rangeSubmit);

				cb(start, end);

			});


			$(document).ready(function() {






                $(document).ready(function() {
                    $(".donut").peity("donut")

                });


				<?php if(in_array($route, $autorefresh_pages) && $liu['autorefresh'] != 0) { ?>
						var myCounter = new Countdown({
						    seconds:<?php echo $liu['autorefresh']/1000; ?>,  // number of seconds to count down
						    onUpdateStatus: function(sec){ $('#timer').text(sec); }, // callback for each second
						    onCounterEnd: function(){ window.location.reload(); } // final action
						});

						myCounter.start();


				<?php } ?>



			});
		</script>
    </body>
</html>
