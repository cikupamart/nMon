<aside class="right-side">
	<!-- Content Header (Page header) -->
	<section class="content-header">
		<h1><?php echo $server['name']; ?><small> <?php echo smartDate($latest['timestamp']); ?></small></h1>
		<ol class="breadcrumb">
            <li><a href="?route=dashboard"><i class="fa fa-dashboard"></i> <?php _e('Home'); ?></a></li>
            <li><a href="?route=servers"><?php _e('Servers'); ?></a></li>
            <li class="active"><?php echo $server['name']; ?></li>
        </ol>
	</section>

	<!-- Main content -->
	<section class="content">
		<?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print __($statusmessage["message"]); ?></div></div></div>
		<?php endif; ?>
	    <div class='row'>
            <div class='col-md-12'>
                <!-- Custom Tabs -->

                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        <li <?php if ($section == "" or $section == "overview") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=" ><?php _e('Overview'); ?></a></li>
						<li <?php if ($section == "cpu") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=cpu"><?php _e('CPU'); ?></a></li>
                        <li <?php if ($section == "ram") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=ram"><?php _e('RAM'); ?></a></li>

                        <li <?php if ($section == "disks") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=disks"><?php _e('Disks'); ?></a></li>
                        <li <?php if ($section == "network") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=network"><?php _e('Network'); ?></a></li>
                        <li <?php if ($section == "processes") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=processes"><?php _e('Processes'); ?></a></li>
                        <li <?php if ($section == "alerting") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=alerting"><?php _e('Alerting'); ?></a></li>

						<li <?php if ($section == "incidents") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=incidents"><?php _e('Incidents'); ?></a></li>

						<?php if(in_array("remoteControl",$perms)) { ?>
						<li <?php if ($section == "remote") echo 'class="active"'; ?> ><a href="?route=servers/manage-linux&id=<?php echo $server['id']; ?>&section=remote"><i class="fa fa-terminal"></i> <?php _e('Remote Control'); ?></a></li>
						<?php } ?>

						<div class="btn-group pull-right" style="padding:6px;">
							<?php if ($section == "alerting") { ?>
								<a data-toggle='tooltip' title='Add Alert' class="btn btn-primary btn-flat btn-sm " href="#" onClick='showM("?modal=serveralerts/add&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>");return false'><i class="fa fa-plus"></i> ADD ALERT</a>
							<?php } ?>

							<button type="button" class="btn btn-default btn-flat btn-sm  pull-right" id="daterange-btn">
								<i class="fa fa-calendar fa-fw"></i> <span><?php _e('Date Range'); ?></span> <i class="fa fa-caret-down fa-fw"></i>
							</button>
							<form role="form" method="post" enctype="multipart/form-data" id="rangeForm">
								<input type="hidden" name="action" value="setRange">

								<input type="hidden" name="range_start" id="range_start" value="">
								<input type="hidden" name="range_end" id="range_end" value="">
								<input type="hidden" name="range_label" id="range_label" value="">

								<input type="hidden" name="asset" value="server-<?php echo $_GET['id']; ?>">

								<input type="hidden" name="route" value="<?php echo $_GET['route']; ?>">
								<input type="hidden" name="routeid" value="<?php echo $_GET['id']; ?>">
								<input type="hidden" name="section" value="<?php if(!empty($_GET['section'])) echo $_GET['section']; ?>">
							</form>

						</div>


                    </ul>
                    <div class="tab-content">

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "") echo 'active'; ?>" id="overview">

							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
	                            <div class='row'>

	                                <div class='col-md-8'>
										<div class='row'>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-cpu-chart" style="height:280px"></canvas>
												</div>
											</div>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-load-chart" style="height:280px"></canvas>
												</div>
											</div>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-realram-chart" style="height:280px"></canvas>
												</div>
											</div>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-netspeed-chart" style="height:280px"></canvas>
												</div>
											</div>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-disks-chart" style="height:280px"></canvas>
												</div>
											</div>

											<div class='col-md-12'>
												<div class="chart">
													<canvas id="cjs-ov-inodes-chart" style="height:280px"></canvas>
												</div>
											</div>

										</div>

	                                </div>

	                                <div class='col-md-4'>

										<?php if(!empty($unresolved_incidents)) { ?>
											<div class="box box-<?php echo $unresolved_status; ?> box-solid">
												<div class="box-header with-border">
													<h3 class="box-title"><?php _e('Incidents'); ?></h3>
													<div class="pull-right box-tools">
														<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
													</div>
												</div>

												<div class="box-body">
													<div class="table-responsive">
														<table class="table table-striped table-hover table-bordered">
															<thead>
																<tr>
																	<th class="no-sort" style="width:1%"></th>
																	<th><?php _e('Incident'); ?></th>
																	<th><?php _e('Start Time'); ?></th>
																</tr>
															</thead>
															<tbody>
																<?php foreach ($unresolved_incidents as $incident) { ?>
																	<tr>
																		<td>
																			<?php if($incident['status'] == 1) { ?>
																				<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
																			<?php } elseif($incident['status'] == 2) { ?>
																				<?php if(in_array("editServer",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i><?php } ?>
																			<?php } elseif($incident['status'] == 3) { ?>
																				<?php if(in_array("editServer",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i><?php } ?>
																			<?php } else { ?>
																				<?php if(in_array("editServer",$perms)) { ?>
																					<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
																				<?php } else { ?><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i><?php } ?>
																			<?php } ?>
																		</td>
																		<td>
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
																		</td>
																		<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
																	</tr>
																<?php } ?>
															</tbody>
														</table>
													</div>

												</div>
											</div>
										<?php } ?>


	                                    <div class="box box-primary">
	            							<div class="box-header">
	            								<h3 class="box-title"><?php _e('Server Info'); ?></h3>
	            								<div class="pull-right box-tools">
	            									<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	            								</div>
	            							</div>

	            							<div class="box-body">
	            								<table id="serverInfoTable" class="table table-striped table-hover">
	            									<tbody>

	            										<tr>
	            											<td><b><?php _e('Hostname'); ?></b></td>
	            											<td><?php echo Server::extractData('hostname', $latest['data'], true); ?></td>
	            										</tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Operating System'); ?></b></td>
	                                                        <td><?php echo Server::extractData('os', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Kernel'); ?></b></td>
	                                                        <td><?php echo Server::extractData('kernel', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Arch'); ?></b></td>
	                                                        <td><?php echo Server::extractData('os_arch', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Server Time'); ?></b></td>
	                                                        <td>
																<?php
																	echo date("Y-m-d H:i:s", Server::extractData('time', $latest['data'], true))
																?>
															</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Uptime'); ?></b></td>
	                                                        <td>
																<?php
																	echo Server::uptime(Server::extractData('uptime', $latest['data'], true));
																?>
															</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('CPU Model'); ?></b></td>
	                                                        <td><?php echo Server::extractData('cpu_model', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('CPU Cores'); ?></b></td>
	                                                        <td><?php echo Server::extractData('cpu_cores', $latest['data'], true); ?> <?php _e('Cores'); ?> @ <?php echo Server::extractData('cpu_speed', $latest['data'], true); ?> MHz</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Load'); ?></b></td>
	                                                        <td>
																<?php
																	$i = 1;
																	foreach (explode(",", Server::extractData('cpu_load', $latest['data'], true)) as $value) {
																		echo $value . " ";
																		if ($i == 1) echo "<span class='text-muted'>" . __('(1 Min)') . "</span> ";
																		if ($i == 2) echo "<span class='text-muted'>" . __('(5 Min)') . "</span> ";
																		if ($i == 3) echo "<span class='text-muted'>" . __('(15 Min)') . "</span> ";
																		$i++;
																	}
																?>
															</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('RAM'); ?></b></td>
	                                                        <td>
																<?php
																$ramtotal = Server::extractData('ram_total', $latest['data'], true);
																$ramcaches = Server::extractData('ram_caches', $latest['data'], true);
																$rambuffers = Server::extractData('ram_buffers', $latest['data'], true);
																$ramfree = Server::extractData('ram_free', $latest['data'], true);

																$actualfree = $ramfree + $rambuffers + $ramcaches;
																?>

																<?php echo formatBytes($actualfree*1024); ?>
																<?php _e('Available'); ?>
																/ <?php echo formatBytes($ramtotal*1024); ?> <?php _e('Total'); ?>
															</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Swap'); ?></b></td>
	                                                        <td><?php echo round(Server::extractData('swap_usage', $latest['data'], true)/1024); ?> <?php _e('MB Used'); ?>, <?php echo round(Server::extractData('swap_total', $latest['data'], true)/1024); ?> <?php _e('MB Total'); ?></td>
	                                                    </tr>

														<tr>
	                                                        <td><b><?php _e('IP Address'); ?></b></td>
	                                                        <td>
																<?php
																	$default_interface = Server::extractData('default_interface', $latest['data'], true);
																	echo $default_interface;
																?>

																<?php
																	$ipv4_addresses = explode(";",Server::extractData('ipv4_addresses', $latest['data'], true));
																	foreach ($ipv4_addresses as $address) {
																		$address_parts = explode(",", $address);
																		if ($address_parts[0] == $default_interface) { echo $address_parts[1]; break; }
																	}

																?>
															</td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('SSH Sessions'); ?></b></td>
	                                                        <td><?php echo Server::extractData('ssh_sessions', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Active Connections'); ?></b></td>
	                                                        <td><?php echo Server::extractData('active_connections', $latest['data'], true); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('PING Latency'); ?></b></td>
	                                                        <td><?php echo Server::extractData('ping_latency', $latest['data'], true); ?> <?php _e('ms'); ?></td>
	                                                    </tr>

	                                                    <tr>
	                                                        <td><b><?php _e('Agent Version'); ?></b></td>
	                                                        <td><?php echo Server::extractData('agent_version', $latest['data'], true); ?></td>
	                                                    </tr>


	            									</tbody>
	            								</table>
	            							</div>
	            						</div>

	                                    <div class="box box-primary">
	                                        <div class="box-header">
	                                            <h3 class="box-title"><?php _e('Disk Usage'); ?></h3>
	                                            <div class="pull-right box-tools">
	                                                <button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	                                            </div>
	                                        </div>

	                                        <div class="box-body">
												<div class="table-responsive">
		                                            <table id="diskInfo" class="table table-striped table-hover table-bordered">
		                	                            <thead>
		                	                                <tr>
		                	                                    <th><?php _e('Mount'); ?></th>
		                	                                    <th><?php _e('Used'); ?></th>
		                	                                </tr>
		                	                            </thead>
		                	                            <tbody>
		                									<?php
		                                                    $rows = explode(";", Server::extractData('disks', $latest['data'], true));
		                                                    array_pop($rows); // delete last
		                                                    foreach ($rows as $row) { $cells = explode(",", $row); ?>
		                		                                <tr>
		                		                                    <td><?php echo $cells[6]; ?></td>
		                		                                    <td><?php echo $cells[5]; ?></td>
		                		                                </tr>
		                									<?php } ?>
		                								</tbody>
		                							</table>
												</div>
	                                        </div>
	                                    </div>

	                                    <div class="box box-primary">
	                                        <div class="box-header">
	                                            <h3 class="box-title"><?php _e('Network Usage'); ?></h3>
	                                            <div class="pull-right box-tools">
	                                                <button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	                                            </div>
	                                        </div>

	                                        <div class="box-body">
												<div class="table-responsive">
		                                            <table id="diskInfo" class="table table-striped table-hover table-bordered">
		                                                <thead>
		                                                    <tr>
		                                                        <th><?php _e('Interface'); ?></th>
		                                                        <th><?php _e('In'); ?></th>
		                                                        <th><?php _e('Out'); ?></th>
																<th><?php _e('Total'); ?></th>
		                                                    </tr>
		                                                </thead>
		                                                <tbody>
		                                                    <?php
															$all_interfaces = explode(";", Server::extractData('all_interfaces', $latest['data'])); array_pop($all_interfaces);
															$all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $latest['data'])); array_pop($all_interfaces_current);
															$interface_count  = count($all_interfaces_current);
		                                                    for ($x = 0; $x < $interface_count; $x++) {
																$interface = explode(",", $all_interfaces[$x]); $interface_current = explode(",", $all_interfaces_current[$x]);

																//if (stripos($interface_current[0], 'veth') !== false) { continue; }
																//if (stripos($interface_current[0], 'tap') !== false) { continue; }
																?>
		                                                        <tr>
		                                                            <td><?php echo $interface_current[0]; ?></td>
		                                                            <td>
																		<?php echo formatBytes($interface_current[1]); ?><br>
																		<?php echo formatBytes( $interface_current[1]  - $interface[1] ); ?><?php _e('/s'); ?>
																	</td>
		                                                            <td>
																		<?php echo formatBytes($interface_current[2]); ?><br>
																		<?php echo formatBytes( $interface_current[2]  - $interface[2] ); ?><?php _e('/s'); ?>
																	</td>
																	<td>
																		<?php echo formatBytes($interface_current[1]+$interface_current[2]); ?><br>
																		<?php echo formatBytes( ($interface_current[1] + $interface_current[2]) - ($interface[1] + $interface[2]) ); ?><?php _e('/s'); ?>
																	</td>
		                                                        </tr>
		                                                    <?php } ?>
		                                                </tbody>
		                                            </table>
												</div>
	                                        </div>
	                                    </div>


	                                    <div class="box box-primary">
	            							<div class="box-header">
	            								<h3 class="box-title"><?php _e('Uptime'); ?></h3>
	            								<div class="pull-right box-tools">
	            									<button type="button" class="btn btn-default btn-sm" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
	            								</div>
	            							</div>

	            							<div class="box-body">
	            								<table id="websiteInfoTable" class="table table-striped table-hover">
	            									<tbody>



	            										<tr>
	            											<td><b><?php _e('Last 24 Hours'); ?></b></td>
	            											<td><?php echo Server::uptimePercentage($server['id'], "24h"); ?> %</td>
	            										</tr>

														<tr>
															<td><b><?php _e('Last 7 Days'); ?></b></td>
															<td><?php echo Server::uptimePercentage($server['id'], "7days"); ?> %</td>
														</tr>

														<tr>
															<td><b><?php _e('Last 30 Days'); ?></b></td>
															<td><?php echo Server::uptimePercentage($server['id'], "30days"); ?> %</td>
														</tr>

														<tr>
															<td><b><?php _e('Last 12 Months'); ?></b></td>
															<td><?php echo Server::uptimePercentage($server['id'], "12months"); ?> %</td>
														</tr>

														<tr>
															<td><b><?php _e('Selected Period'); ?></b></td>
															<td><?php echo Server::uptimePercentage($server['id'], "selected"); ?> %</td>
														</tr>


	            									</tbody>
	            								</table>
	            							</div>
	            						</div>

	                                </div>

	                            </div>

							<?php } ?>

                        </div>
                        <!-- /.tab-pane -->


                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "cpu") echo 'active'; ?>" id="cpu">
							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
								<div class='row'>
									<?php $i = 0; foreach($charts['cpu'] as $cpu) { $core = $i-1; ?>
										<div class='col-md-12'>
											<h4 class="text-center"><?php if($i == 0) _e('CPU Usage'); else echo __('Core') . " " .  $core . " " .  __('Usage'); ?></h4>
											<div class="chart">
												<canvas id="cjs-cpu-chart-<?php echo $i; ?>" style="height:280px"></canvas>
											</div>
										</div>
									<?php $i++; } ?>
								</div>
							<?php } ?>

                        </div>
                        <!-- /.tab-pane -->

                        <!-- tab-pane -->
						<div class="tab-pane <?php if ($section == "ram") echo 'active'; ?>" id="ram">
							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
								<div class='row'>


									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('RAM Usage'); ?></h4>
										<div class="chart">
											<canvas id="cjs-realram-chart" style="height:280px"></canvas>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('RAM Usage'); ?> <small><?php _e('Including Caches & Buffers'); ?></small></h4>
										<div class="chart">
											<canvas id="cjs-ram-chart" style="height:280px"></canvas>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('SWAP Usage'); ?></h4>
										<div class="chart">
											<canvas id="cjs-swap-chart" style="height:280px"></canvas>
										</div>
									</div>
								</div>
							<?php } ?>
                        </div>
                        <!-- /.tab-pane -->

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "disks") echo 'active'; ?>" id="disks">
							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
								<div class='row'>
									<div class="col-md-12">
										<div class="table-responsive">
											<table id="diskInfo" class="table table-striped table-hover table-bordered">
												<thead>
													<tr>
														<th><?php _e('Mount'); ?></th>
														<th><?php _e('Device'); ?></th>
														<th><?php _e('Filesystem'); ?></th>

														<th><?php _e('Size'); ?></th>
														<th><?php _e('Used'); ?></th>
														<th><?php _e('Free'); ?></th>

														<th><?php _e('Used'); ?></th>

														<th><?php _e('Inodes'); ?></th>
														<th><?php _e('I-Used'); ?></th>
														<th><?php _e('I-Free'); ?></th>
														<th><?php _e('I-Used %'); ?></th>

													</tr>
												</thead>
												<tbody>
													<?php
													$disks_data = explode(";", Server::extractData('disks', $latest['data'], true)); array_pop($disks_data); // delete last
													$disks_inodes = explode(";", Server::extractData('disks_inodes', $latest['data'], true)); array_pop($disks_inodes); // delete last
													$disks_count  = count($disks_data);
													for ($x = 0; $x < $disks_count; $x++) { $disk_data = explode(",", $disks_data[$x]); $disk_inodes = explode(",", $disks_inodes[$x]); ?>
														<tr>
															<td><?php echo $disk_data[6]; ?></td>
															<td><?php echo $disk_data[0]; ?></td>
															<td><?php echo $disk_data[1]; ?></td>

															<td><?php echo formatBytes($disk_data[2]*1024); ?></td>
															<td><?php echo formatBytes($disk_data[3]*1024); ?></td>
															<td><?php echo formatBytes($disk_data[4]*1024); ?></td>

															<td><?php echo $disk_data[5]; ?></td>

															<td><?php echo $disk_inodes[1]; ?></td>
															<td><?php echo $disk_inodes[2]; ?></td>
															<td><?php echo $disk_inodes[3]; ?></td>
															<td><?php echo $disk_inodes[4]; ?></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('Disks Usage'); ?></h4>
										<div class="chart">
											<canvas id="cjs-disks-chart" style="height:280px"></canvas>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('Inode Usage'); ?></h4>
										<div class="chart">
											<canvas id="cjs-inodes-chart" style="height:280px"></canvas>
										</div>
									</div>

								</div>
							<?php } ?>
                        </div>
                        <!-- /.tab-pane -->

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "network") echo 'active'; ?>" id="network">
							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
								<div class='row'>
									<div class="col-md-12">
										<div class="table-responsive">
											<table id="networkInfo" class="table table-striped table-hover table-bordered">
												<thead>
													<tr>
														<th><?php _e('Interface'); ?></th>
														<th><?php _e('In'); ?></th>
														<th><?php _e('Out'); ?></th>
														<th><?php _e('Total'); ?></th>
														<th><?php _e('In Speed'); ?></th>
														<th><?php _e('Out Speed'); ?></th>
														<th><?php _e('Total Speed'); ?></th>
														<th><?php _e('In Packets'); ?></th>
														<th><?php _e('Out Packets'); ?></th>
														<th><?php _e('Total Packets'); ?></th>
													</tr>
												</thead>
												<tbody>
													<?php

													$all_interfaces = explode(";", Server::extractData('all_interfaces', $latest['data'])); array_pop($all_interfaces);
													$all_interfaces_current = explode(";", Server::extractData('all_interfaces_current', $latest['data'])); array_pop($all_interfaces_current);
													$interface_count  = count($all_interfaces_current);

													for ($x = 0; $x < $interface_count; $x++) { $interface = explode(",", $all_interfaces[$x]); $interface_current = explode(",", $all_interfaces_current[$x]); ?>
														<tr>
															<td><?php echo $interface_current[0]; ?> </td>
															<td><?php echo formatBytes($interface_current[1]); ?></td>
															<td><?php echo formatBytes($interface_current[2]); ?></td>

															<td><?php echo formatBytes($interface_current[1]+$interface_current[2]); ?></td>

															<td><?php echo formatBytes( $interface_current[1]  - $interface[1] ); ?><?php _e('/s'); ?></td>
															<td><?php echo formatBytes( $interface_current[2]  - $interface[2] ); ?><?php _e('/s'); ?></td>
															<td><?php echo formatBytes( ($interface_current[1] + $interface_current[2]) - ($interface[1] + $interface[2]) ); ?><?php _e('/s'); ?></td>

															<td>
																<?php _e('Total'); ?> <?php echo $interface_current[3]; ?><br>
																<?php _e('Speed'); ?> <?php echo $interface_current[3] - $interface[3] ; ?> <?php _e('p/s'); ?>
															</td>

															<td>
																<?php _e('Total'); ?> <?php echo $interface_current[4]; ?><br>
																<?php _e('Speed'); ?> <?php echo $interface_current[4] - $interface[4] ; ?> <?php _e('p/s'); ?>
															</td>

															<td>
																<?php _e('Total'); ?> <?php echo $interface_current[3]+$interface_current[4]; ?><br>
																<?php _e('Speed'); ?> <?php echo ($interface_current[3] - $interface[3]) + ($interface_current[4] - $interface[4]) ; ?> <?php _e('p/s'); ?>
															</td>

														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('Network Speed'); ?></h4>
										<div class="chart">
											<canvas id="cjs-netspeed-chart" style="height:280px"></canvas>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('Ping Latency'); ?></h4>
										<div class="chart">
											<canvas id="cjs-ping-chart" style="height:280px"></canvas>
										</div>
									</div>

									<div class='col-md-12'>
										<h4 class="text-center"><?php _e('Connections'); ?></h4>
										<div class="chart">
											<canvas id="cjs-connections-chart" style="height:280px"></canvas>
										</div>
									</div>

								</div>
							<?php } ?>
                        </div>
                        <!-- /.tab-pane -->

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "processes") echo 'active'; ?>" id="processes">
							<?php if(empty($history)) { ?>
								<div class="alert alert-warning" role="alert">
									<h4><i class="icon fa fa-warning"></i> <?php _e('No data!'); ?></h4>
									<?php _e('No data available for the selected period or no data has been received yet.'); ?>
								</div>
							<?php } else { ?>
	                            <div class="table-responsive">
	    	                        <table id="dataTablesFullNoOrder" class="table table-striped table-hover table-bordered">
	    	                            <thead>
	    	                                <tr>
	    	                                    <th><?php _e('PID'); ?></th>
	    	                                    <th><?php _e('PPID'); ?></th>
	                                            <th><?php _e('RSS'); ?></th>
	    								        <th><?php _e('VSZ'); ?></th>
	                                            <th><?php _e('USER'); ?></th>
	                                            <th><?php _e('%MEM'); ?></th>
	                                            <th><?php _e('%CPU'); ?></th>
	                                            <th><?php _e('COMMAND'); ?></th>
	                                            <th><?php _e('CMD'); ?></th>
	    	                                </tr>
	    	                            </thead>
	    	                            <tbody>
	    									<?php
	                                        $rows = explode(";", Server::extractData('processes', $latest['data'], true));
	                                        array_shift($rows); // delete first
	                                        array_pop($rows); // delete last
	                                        foreach ($rows as $row) { $cells = explode(",", $row); ?>
	    		                                <tr>
	    		                                    <td><?php echo $cells[0]; ?></td>
	    		                                    <td><?php echo $cells[1]; ?></td>
	                                                <td><?php echo $cells[2]; ?></td>
	                                                <td><?php echo $cells[3]; ?></td>
	                                                <td><?php echo $cells[4]; ?></td>
	                                                <td><?php echo $cells[5]; ?></td>
	                                                <td><?php echo $cells[6]; ?></td>
	                                                <td><?php echo $cells[7]; ?></td>
	                                                <td><?php echo $cells[8]; ?></td>
	    		                                </tr>
	    									<?php } ?>
	    								</tbody>
	    							</table>
	    						</div>
							<?php } ?>
                        </div>
                        <!-- /.tab-pane -->

                        <!-- tab-pane -->
                        <div class="tab-pane <?php if ($section == "alerting") echo 'active'; ?>" id="alerting">
							<div class="table-responsive">
								<table id="dataTablesFullNoOrder2" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Comparison'); ?></th>
											<th><?php _e('Action'); ?></th>
											<th><?php _e('Status'); ?></th>
											<th class="text-right"></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($alerts as $alert) { $selected_contacts = unserialize($alert['contacts']); if(empty($selected_contacts)) $selected_contacts = []; ?>
											<tr>
												<td>
													<?php if($alert['type'] == "nodata") _e('No Data'); ?>
													<?php if($alert['type'] == "cpu") _e('CPU Usage %'); ?>
													<?php if($alert['type'] == "cpuio") _e('CPU IO Wait %'); ?>
													<?php if($alert['type'] == "load1min") _e('System Load 1 Min'); ?>
													<?php if($alert['type'] == "load5min") _e('System Load 5 Min'); ?>
													<?php if($alert['type'] == "load15min") _e('System Load 15 Min'); ?>
													<?php if($alert['type'] == "service") _e('Service/Process Not Running'); ?>

													<?php if($alert['type'] == "ram") _e('RAM Usage %'); ?>
													<?php if($alert['type'] == "ramMB") _e('RAM Usage MB'); ?>
													<?php if($alert['type'] == "swap") _e('Swap Usage %'); ?>
													<?php if($alert['type'] == "swapMB") _e('Swap Usage MB'); ?>
													<?php if($alert['type'] == "disk") _e('Disk Usage % (Aggregated)'); ?>
													<?php if($alert['type'] == "diskGB") _e('Disk Usage GB (Aggregated)'); ?>

													<?php
														if(strpos($alert['type'],'disk:') !== false) {
															$disk_text = explode(":",$alert['type']);
															_e('Disk Usage %:'); echo " " . $disk_text[1];
														}
													?>

													<?php
														if(strpos($alert['type'],'diskGB:') !== false) {
															$disk_text = explode(":",$alert['type']);
															_e('Disk Usage GB:'); echo " " . $disk_text[1];
														}
													?>

													<?php if($alert['type'] == "connections") _e('Connections'); ?>
													<?php if($alert['type'] == "ssh") _e('SSH Sessions'); ?>
													<?php if($alert['type'] == "ping") _e('Ping Latency'); ?>
													<?php if($alert['type'] == "netdl") _e('Network Download Speed MB/s'); ?>
													<?php if($alert['type'] == "netup") _e('Network Upload Speed MB/s'); ?>
												</td>

												<td>
													<?php if($alert['type'] == "nodata") { ?>
														<?php _e('N/A'); ?>

													<?php } elseif($alert['type'] == "service") { ?>
														<?php echo $alert['comparison_limit']; ?>

													<?php } else { ?>
														<?php echo $alert['comparison']; ?> <?php echo $alert['comparison_limit']; ?>
													<?php } ?>

													<?php if($alert['type'] == "service") { ?>
														<i class="fa fa-info-circle fa-fw" data-toggle="tooltip" title="<?php _e('Service name as found in the Processes table > COMMAND column.'); ?>"></i>
													<?php } ?>
												</td>

												<td>
													<?php if($alert['type'] == "service") { ?>
														<?php _e('If Service/Process not running'); ?>,
													<?php } else { ?>
														<?php _e('If occurs'); ?> <?php echo $alert['occurrences']; ?> <?php _e('times'); ?>,

													<?php } ?>

													<?php _e('alert:'); ?>

													<?php foreach ($selected_contacts as $selected_contact) { ?>
														<span class="label bg-gray"><?php echo getSingleValue("app_contacts", "name", $selected_contact); ?></span>&nbsp;
													<?php } ?>
												</td>

												<td>
													<?php if($alert['status'] == 1) { ?>
														<span class="label label-success"><?php _e("Active"); ?></span>
													<?php } ?>
													<?php if($alert['status'] == 0) { ?>
														<span class="label label-default"><?php _e("Inactive"); ?></span>
													<?php } ?>
												</td>
												<td>
													<div class='pull-right'>
														<div class="btn-group">
															 <?php if(in_array("editServer",$perms)) { ?><a href="#" onClick='showM("?modal=serveralerts/edit&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false'  class="btn btn-success btn-flat btn-sm"><i class="fa fa-edit"></i></a><?php } ?>
															 <?php if(in_array("editServer",$perms)) { ?><a href="#" onClick='showM("?modal=serveralerts/delete&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $alert['id']; ?>&section=alerting");return false' class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash-o"></i></a><?php } ?>
														</div>
													</div>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>



                        </div>
                        <!-- /.tab-pane -->

						<!-- tab-pane -->
						<div class="tab-pane <?php if ($section == "incidents") echo 'active'; ?>" id="incidents">

							<div class="table-responsive">
								<table id="dataTablesFullNoOrder3" class="table table-striped table-hover table-bordered">
									<thead>
										<tr>
											<th class="no-sort" style="width:1%"></th>
											<th><?php _e('Type'); ?></th>
											<th><?php _e('Comparison'); ?></th>
											<th><?php _e('Start Time'); ?></th>
											<th><?php _e('End Time'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($incidents as $incident) { ?>
											<tr>
												<td>
													<?php if($incident['status'] == 1) { ?>
														<i class="fa fa-check-circle fa-2x text-green" data-toggle="tooltip" title="<?php _e("OK"); ?>"></i>
													<?php } elseif($incident['status'] == 2) { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-yellow" data-toggle="tooltip" title="<?php _e("Warning"); ?>"></i></a>
														<?php } ?>
													<?php } elseif($incident['status'] == 3) { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-red" data-toggle="tooltip" title="<?php _e("Alert"); ?>"></i></a>
														<?php } ?>
													<?php } else { ?>
														<?php if(in_array("editServer",$perms)) { ?>
															<a href="#" onClick='showM("?modal=serveralerts/markResolved&reroute=servers/manage-linux&routeid=<?php echo $server['id']; ?>&id=<?php echo $incident['id']; ?>&section=incidents");return false'><i class="fa fa-2x fa-warning text-gray" data-toggle="tooltip" title="<?php _e("Unknown"); ?>"></i></a>
														<?php } ?>
													<?php } ?>
												</td>
												<td>
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
												</td>

												<td>
													<?php if($incident['type'] == "nodata") { ?>
														<?php _e('N/A'); ?>

													<?php } elseif($incident['type'] == "service") { ?>
														<?php echo $incident['comparison_limit']; ?>

													<?php } else { ?>
														<?php echo $incident['comparison']; ?> <?php echo $incident['comparison_limit']; ?>
													<?php } ?>
												</td>
												<td><?php echo dateTimeDisplay($incident['start_time']); ?></td>
												<td>
													<?php if($incident['end_time'] != "0000-00-00 00:00:00") echo dateTimeDisplay($incident['end_time']); else echo "-"; ?>
												</td>
											</tr>
										<?php } ?>
									</tbody>
								</table>
							</div>




						</div>
						<!-- /.tab-pane -->


						<?php if(in_array("remoteControl",$perms)) { ?>
						<!-- tab-pane: Remote Control -->
						<div class="tab-pane <?php if ($section == "remote") echo 'active'; ?>" id="remote">
							<div class='row'>
								<div class='col-md-12'>
									<div class="box box-primary">
										<div class="box-header with-border">
											<h3 class="box-title"><i class="fa fa-terminal"></i> <?php _e('Remote Command'); ?></h3>
									</div>
									<div class="box-body">
										<div class="form-group">
											<label><?php _e('Command'); ?></label>
											<div class="input-group">
												<span class="input-group-addon">$</span>
												<input type="text" id="remote-command" class="form-control" placeholder="<?php _e('Enter command...'); ?>" onkeypress="if(event.keyCode==13) sendRemoteCommand()">
												<span class="input-group-btn">
													<button type="button" class="btn btn-primary" onclick="sendRemoteCommand()"><i class="fa fa-paper-plane"></i> <?php _e('Send'); ?></button>
												</span>
											</div>
										</div>
										<div class="form-group">
											<label><?php _e('Quick Commands'); ?></label>
											<div>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('uptime')">uptime</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('df -h')">df -h</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('free -m')">free -m</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('top -bn1 | head -20')">top</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('ps aux --sort=-%cpu | head -15')">top processes</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('netstat -tuln')">netstat</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('docker ps')">docker ps</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('systemctl list-units --type=service --state=running')">services</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('tail -50 /var/log/syslog')">syslog</button>
												<button type="button" class="btn btn-default btn-sm" onclick="setCommand('uname -a')">uname</button>
											</div>
										</div>
										<div id="remote-output" style="display:none;">
											<label><?php _e('Output'); ?></label>
											<pre id="remote-output-content" style="max-height:500px;overflow:auto;background:#1a1a2e;color:#e0e0e0;padding:15px;border-radius:4px;font-size:13px;"></pre>
										</div>
									</div>
								</div>

									<div class="box box-primary">
										<div class="box-header with-border">
											<h3 class="box-title"><i class="fa fa-history"></i> <?php _e('Command History'); ?></h3>
											<div class="box-tools pull-right">
												<button type="button" class="btn btn-box-tool" onclick="loadCommandHistory()"><i class="fa fa-refresh"></i></button>
											</div>
										</div>
										<div class="box-body">
											<div class="table-responsive">
												<table id="remoteHistoryTable" class="table table-striped table-hover table-bordered">
													<thead>
														<tr>
															<th><?php _e('ID'); ?></th>
															<th><?php _e('Command'); ?></th>
															<th><?php _e('User'); ?></th>
															<th><?php _e('Status'); ?></th>
															<th><?php _e('Time'); ?></th>
															<th><?php _e('Action'); ?></th>
														</tr>
													</thead>
													<tbody id="remote-history-body">
													</tbody>
												</table>
											</div>
										</div>
									</div>
								</div>
							</div>

<script>
function setCommand(cmd) {
	$('#remote-command').val(cmd);
}

function sendRemoteCommand() {
	var command = $('#remote-command').val();
	if (!command) { return; }
	
	$.ajax({
		url: '?controller=remote&action=send',
		method: 'POST',
		data: {
			server_id: <?php echo $server['id']; ?>,
			command: command
		},
		dataType: 'json',
		success: function(response) {
			if (response.success) {
				$('#remote-output').show();
				$('#remote-output-content').html('<i class="fa fa-spinner fa-spin"></i> ' + '<?php _e('Sending command...'); ?>');
				pollCommandResult(response.command_id);
			} else {
				$('#remote-output').show();
				$('#remote-output-content').html('<span class="text-red">Error: ' + response.error + '</span>');
			}
		}
	});
}

function pollCommandResult(cmdId) {
	$.ajax({
		url: '?controller=remote&action=result&id=' + cmdId,
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			if (response.status === 'completed') {
				var output = atob(response.output || '');
				var exitCode = response.exit_code;
				var color = exitCode == 0 ? '#00ff00' : '#ff6666';
				$('#remote-output-content').html(
					'<span style="color:#66ff66">$ ' + $('#remote-command').val() + '</span>\n' +
					'<span style="color:' + color + '">Exit Code: ' + exitCode + '</span>\n\n' +
					'<pre style="background:transparent;color:#e0e0e0;margin:0;padding:0;">' + output + '</pre>'
				);
			} else if (response.status === 'failed') {
				$('#remote-output-content').html('<span class="text-red">Command failed</span>');
			} else {
				setTimeout(function() { pollCommandResult(cmdId); }, 2000);
			}
		}
	});
}

function loadCommandHistory() {
	$.ajax({
		url: '?controller=remote&action=list&server_id=<?php echo $server['id']; ?>',
		method: 'GET',
		dataType: 'json',
		success: function(data) {
			var html = '';
			for (var i = 0; i < data.length; i++) {
				var cmd = data[i];
				var statusClass = cmd.status == 'completed' ? 'label-success' : (cmd.status == 'failed' ? 'label-danger' : 'label-warning');
				html += '<tr>';
				 html += '<td>' + cmd.id + '</td>';
				 html += '<td><code>' + cmd.command + '</code></td>';
				 html += '<td>' + cmd.user_name + '</td>';
				 html += '<td><span class="label ' + statusClass + '">' + cmd.status + '</span></td>';
				 html += '<td>' + cmd.created_at + '</td>';
				 html += '<td><button class="btn btn-xs btn-info" onclick="viewCommandResult(' + cmd.id + ')"><i class="fa fa-eye"></i></button></td>';
				 html += '</tr>';
			}
			$('#remote-history-body').html(html);
		}
	});
}

function viewCommandResult(cmdId) {
	$.ajax({
		url: '?controller=remote&action=result&id=' + cmdId,
		method: 'GET',
		dataType: 'json',
		success: function(response) {
			var output = response.output ? atob(response.output) : 'No output';
			$('#remote-output').show();
			$('#remote-output-content').html(
				'<span style="color:#66ff66">$ ' + response.command + '</span>\n' +
				'<span>Exit Code: ' + response.exit_code + '</span>\n' +
				'<span>Executed: ' + response.executed_at + '</span>\n\n' +
				'<pre style="background:transparent;color:#e0e0e0;margin:0;padding:0;">' + output + '</pre>'
			);
		}
	});
}

$(document).ready(function() {
	loadCommandHistory();
});
</script>

						<?php } ?>

                    </div><!-- /.tab-content -->
                </div><!-- nav-tabs-custom -->
            </div><!-- /.col-->
        </div><!-- ./row -->


	</section><!-- /.content -->
</aside><!-- /.right-side -->



<script type="text/javascript">

	<?php if(isset($_GET['popinstall']) or empty($latest)) { ?>
		$(document).ready(function() {
			showM("?modal=servers/install-linux&id=<?php echo $server['id']; ?>");
		});
	<?php } ?>




	$(document).ready(function() {

		var color1 = '#3e95cd';
		var color2 = '#6f6f6f';
		var color3 = '#61e843';
		var color4 = '#8e5ea2';
		var color5 = '#3cba9f';
		var color6 = '#e8c3b9';
		var color7 = '#c45850';
		var color8 = '#0892a5';
		var color9 = '#06908f';
		var color10 = '#0ca4a5';
		var color11 = '#dbb68f';
		var color12 = '#bb7e5d';
		var color13 = '#706c61';
		var color14 = '#899e8b';
		var color15 = '#99c5b5';
		var color16 = '#afece7';
		var color17 = '#81f499';
		var color18 = '#574d68';
		var color19 = '#a38560';
		var color20 = '#ffbd00';
		var color21 = '#c6a15b';
		var color22 = '#f2e86d';
		var color23 = '#d3dfb8';
		var color24 = '#07020d';
		var color25 = '#5db7de';
		var color26 = '#a39b8b';
		var color27 = '#b7b5e4';
		var color28 = '#847979';
		var color29 = '#51a3a3';
		var color30 = '#75485e';
		var color31 = '#cb904d';
		var color32 = '#dfcc74';
		var color33 = '#c3e991';
		var color34 = '#360568';
		var color35 = '#5b2a86';
		var color36 = '#7785ac';
		var color37 = '#114b5f';
		var color38 = '#ff5400';
		var color39 = '#88d498';
		var color40 = '#390099';


		<?php if (!empty($charts)) { ?>
			<?php if ($section == "" or $section == "overview" && !empty($charts)) { ?>

				// CPU CHART
				new Chart(document.getElementById("cjs-ov-cpu-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['cpu'][0] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [{
						    data: [<?php foreach($charts['cpu'][0] as $item) echo "'".$item['usage']."',"; ?>],
							label: '<?php _e('Aggregated Usage'); ?>',
						    borderColor: color1,
							backgroundColor: color1,
						    fill: false,
							pointRadius: 0,
	                        pointHoverRadius: 4,
						  }
						]
					},
					options: {
						title: { display: true, text: '<?php _e('CPU Usage'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});


				// LOAD CHART
				new Chart(document.getElementById("cjs-ov-load-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['load'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [{
							data: [<?php foreach($charts['load'] as $item) echo "'".$item['1min']."',"; ?>],
							label: '<?php _e('1 Min'); ?>',
							borderColor: color1,
							backgroundColor: color1,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						  }, {
	  						data: [<?php foreach($charts['load'] as $item) echo "'".$item['5min']."',"; ?>],
	  						label: '<?php _e('5 Min'); ?>',
	  						borderColor: color2,
	  						backgroundColor: color2,
	  						fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
	  					  }, {
	  						data: [<?php foreach($charts['load'] as $item) echo "'".$item['15min']."',"; ?>],
	  						label: '<?php _e('15 Min'); ?>',
	  						borderColor: color3,
	  						backgroundColor: color3,
	  						fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
	  					  },
						]
					},
					options: {
						title: { display: true, text: '<?php _e('Load Averages'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel; } } },
						hover: { mode: 'index', intersect: false },
					}
				});



				// REAL RAM CHART
				new Chart(document.getElementById("cjs-ov-realram-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['ram'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [
						  {
							data: [<?php foreach($charts['ram'] as $item) echo "'".$item['real']."',"; ?>],
							label: '<?php _e('Used'); ?>',
							borderColor: color1,
							backgroundColor: "rgba(62,149,205,0.6)",
							fill: true,
							pointRadius: 0,
							pointHoverRadius: 4,
						  },
						]
					},
					options: {
						title: { display: true, text: '<?php _e('RAM Usage'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' <?php _e('MB'); ?>'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB'); ?>'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});


				// NETSPEED CHART
				new Chart(document.getElementById("cjs-ov-netspeed-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['netspeed'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [
							<?php $i=0; foreach($charts['netspeed_keys'] as $mainitem) { $i++; ?>
							{
								data: [<?php foreach($charts['netspeed'] as $item) echo "'".$item[$mainitem]."',"; ?>],
								label: '<?php echo $mainitem; ?>',
								borderColor: color<?php echo $i; ?>,
								backgroundColor: color<?php echo $i; ?>,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
						  	},
							<?php } ?>
						]
					},
					options: {
						title: { display: true, text: '<?php _e('Network Speed'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(2) + ' <?php _e('MB/s'); ?>'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB/s'); ?>'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});

				// DISKS CHART
				new Chart(document.getElementById("cjs-ov-disks-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['disks'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [
							<?php $i=0; foreach($charts['disks_keys'] as $mainitem) { $i++; ?>
							{
								data: [<?php foreach($charts['disks'] as $item) echo "'".$item[$mainitem]."',"; ?>],
								label: '<?php echo $mainitem; ?>',
								borderColor: color<?php echo $i; ?>,
								backgroundColor: color<?php echo $i; ?>,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							<?php } ?>
						]
					},
					options: {
						title: { display: true, text: '<?php _e('Disk Usage'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});


				// INODES CHART
				new Chart(document.getElementById("cjs-ov-inodes-chart"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['disks_inodes'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [
							<?php $i=0; foreach($charts['disks_inodes_keys'] as $mainitem) { $i++; ?>
							{
								data: [<?php foreach($charts['disks_inodes'] as $item) echo "'".$item[$mainitem]."',"; ?>],
								label: '<?php echo $mainitem; ?>',
								borderColor: color<?php echo $i; ?>,
								backgroundColor: color<?php echo $i; ?>,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							<?php } ?>
						]
					},
					options: {
						title: { display: true, text: '<?php _e('Inode Usage'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});

			<?php } ?>
		<?php } ?>


		<?php if ($section == "cpu"  && !empty($charts)) { ?>
			<?php $i = 0; foreach($charts['cpu'] as $cpu) { $core = $i-1; ?>

				new Chart(document.getElementById("cjs-cpu-chart-<?php echo $i; ?>"), {
					type: 'line',
					data: {
						labels: [<?php foreach($charts['netspeed'] as $item) echo "'".$item['date']."',"; ?>],
						datasets: [

							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['user']."',"; ?>],
								label: '<?php _e('user'); ?>',
								borderColor: color1,
								backgroundColor: color1,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['nice']."',"; ?>],
								label: '<?php _e('nice'); ?>',
								borderColor: color2,
								backgroundColor: color2,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['system']."',"; ?>],
								label: '<?php _e('system'); ?>',
								borderColor: color3,
								backgroundColor: color3,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['iowait']."',"; ?>],
								label: '<?php _e('iowait'); ?>',
								borderColor: color4,
								backgroundColor: color4,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['irq']."',"; ?>],
								label: '<?php _e('irq'); ?>',
								borderColor: color5,
								backgroundColor: color5,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['softirq']."',"; ?>],
								label: '<?php _e('softirq'); ?>',
								borderColor: color6,
								backgroundColor: color6,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['steal']."',"; ?>],
								label: '<?php _e('steal'); ?>',
								borderColor: color7,
								backgroundColor: color7,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['guest']."',"; ?>],
								label: '<?php _e('guest'); ?>',
								borderColor: color8,
								backgroundColor: color8,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},
							{
								data: [<?php foreach($charts['cpu'][$i] as $item) echo "'".$item['guestnice']."',"; ?>],
								label: '<?php _e('guestnice'); ?>',
								borderColor: color9,
								backgroundColor: color9,
								fill: false,
								pointRadius: 0,
								pointHoverRadius: 4,
							},

						]
					},
					options: {
						//title: { display: true, text: '<?php _e('CPU Chart'); ?>' },
						scales: {
							xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
							yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
						},
						responsive: true,
						tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
						hover: { mode: 'index', intersect: false },
					}
				});


			<?php $i++; } ?>
		<?php } ?>



		<?php if ($section == "ram"  && !empty($charts)) { ?>
			// RAM CHART
			new Chart(document.getElementById("cjs-ram-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['ram'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [{
						data: [<?php foreach($charts['ram'] as $item) echo "'".$item['buffers']."',"; ?>],
						label: '<?php _e('Buffers'); ?>',
						borderColor: color3,
						backgroundColor: "rgba(97,232,67,0.6)",
						fill: true,
						pointRadius: 0,
						pointHoverRadius: 4,
					  }, {
						data: [<?php foreach($charts['ram'] as $item) echo "'".$item['caches']."',"; ?>],
						label: '<?php _e('Caches'); ?>',
						borderColor: color2,
						backgroundColor: "rgba(111,111,111,0.6)",
						fill: true,
						pointRadius: 0,
						pointHoverRadius: 4,
					  }, {
						data: [<?php foreach($charts['ram'] as $item) echo "'".$item['used']."',"; ?>],
						label: '<?php _e('Total Used'); ?>',
						borderColor: color1,
						backgroundColor: "rgba(62,149,205,0.6)",
						fill: true,
						pointRadius: 0,
						pointHoverRadius: 4,
					  },
					]
				},
				options: {
					title: { display: false, text: '<?php _e('RAM Usage'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return value + ' <?php _e('MB'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


			// REAL RAM CHART
			new Chart(document.getElementById("cjs-realram-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['ram'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
					  {
						data: [<?php foreach($charts['ram'] as $item) echo "'".$item['real']."',"; ?>],
						label: '<?php _e('Used'); ?>',
						borderColor: color1,
						backgroundColor: "rgba(62,149,205,0.6)",
						fill: true,
						pointRadius: 0,
						pointHoverRadius: 4,
					  },
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Real Memory'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' <?php _e('MB'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


			// SWAP CHART
			new Chart(document.getElementById("cjs-swap-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['swap'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
					  {
						data: [<?php foreach($charts['swap'] as $item) echo "'".$item['used']."',"; ?>],
						label: '<?php _e('Used'); ?>',
						borderColor: color1,
						backgroundColor: "rgba(62,149,205,0.6)",
						fill: true,
						pointRadius: 0,
						pointHoverRadius: 4,
					  },
					]
				},
				options: {
					title: { display: false, text: '<?php _e('SWAP Usage'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return value + ' <?php _e('MB'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});

		<?php } ?>


		<?php if ($section == "disks"  && !empty($charts)) { ?>
			// DISKS CHART
			new Chart(document.getElementById("cjs-disks-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['disks'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
						<?php $i=0; foreach($charts['disks_keys'] as $mainitem) { $i++; ?>
						{
							data: [<?php foreach($charts['disks'] as $item) echo "'".$item[$mainitem]."',"; ?>],
							label: '<?php echo $mainitem; ?>',
							borderColor: color<?php echo $i; ?>,
							backgroundColor: color<?php echo $i; ?>,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
						<?php } ?>
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Disk Usage'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


			// INODES CHART
			new Chart(document.getElementById("cjs-inodes-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['disks_inodes'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
						<?php $i=0; foreach($charts['disks_inodes_keys'] as $mainitem) { $i++; ?>
						{
							data: [<?php foreach($charts['disks_inodes'] as $item) echo "'".$item[$mainitem]."',"; ?>],
							label: '<?php echo $mainitem; ?>',
							borderColor: color<?php echo $i; ?>,
							backgroundColor: color<?php echo $i; ?>,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
						<?php } ?>
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Inode Usage'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' %'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' %'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});

		<?php } ?>


		<?php if ($section == "network"  && !empty($charts)) { ?>
			// NETSPEED CHART
			new Chart(document.getElementById("cjs-netspeed-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['netspeed'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
						<?php $i=0; foreach($charts['netspeed_keys'] as $mainitem) { $i++; ?>
						{
							data: [<?php foreach($charts['netspeed'] as $item) echo "'".$item[$mainitem]."',"; ?>],
							label: '<?php echo $mainitem; ?>',
							borderColor: color<?php echo $i; ?>,
							backgroundColor: color<?php echo $i; ?>,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
						<?php } ?>
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Network Speed'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(2) + ' <?php _e('MB/s'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('MB/s'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


			// PING CHART
			new Chart(document.getElementById("cjs-ping-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['ping'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
						{
							data: [<?php foreach($charts['ping'] as $item) echo "'".$item['latency']."',"; ?>],
							label: '<?php _e('Latency'); ?>',
							borderColor: color1,
							backgroundColor: color1,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Ping Latency'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' <?php _e('ms'); ?>'; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' <?php _e('ms'); ?>'; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


			// CONNECTIONS CHART
			new Chart(document.getElementById("cjs-connections-chart"), {
				type: 'line',
				data: {
					labels: [<?php foreach($charts['ping'] as $item) echo "'".$item['date']."',"; ?>],
					datasets: [
						{
							data: [<?php foreach($charts['connections'] as $item) echo "'".$item['ssh']."',"; ?>],
							label: '<?php _e('SSH'); ?>',
							borderColor: color1,
							backgroundColor: color1,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
						{
							data: [<?php foreach($charts['connections'] as $item) echo "'".$item['all']."',"; ?>],
							label: '<?php _e('All'); ?>',
							borderColor: color2,
							backgroundColor: color2,
							fill: false,
							pointRadius: 0,
							pointHoverRadius: 4,
						},
					]
				},
				options: {
					title: { display: false, text: '<?php _e('Connections'); ?>' },
					scales: {
						xAxes: [{ type: 'time', time: { tooltipFormat: '<?php echo strtoupper(jsFormat()); ?> HH:mm', displayFormats: { 'second': 'HH:mm', 'minute': 'HH:mm', 'hour': 'DD MMM HH', 'day': 'DD MMM HH:mm', 'week': 'DD MMM', 'month': 'MMM YYYY', 'quarter': 'MMM YYYY', 'year': 'MMM YYYY' } } }],
						yAxes: [{ ticks: { callback: function (value, index, values) { return parseFloat(value).toFixed(0) + ' '; } } }],
					},
					responsive: true,
					tooltips: { position: 'nearest', mode: 'index', intersect: false, callbacks: { label: function(tooltipItem, data) { return data.datasets[tooltipItem.datasetIndex].label +': ' + tooltipItem.yLabel + ' '; } } },
					hover: { mode: 'index', intersect: false },
				}
			});


		<?php } ?>



	});



</script>
