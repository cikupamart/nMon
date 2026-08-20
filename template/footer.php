
			  <footer class="main-footer no-print">
					<div class="pull-right hidden-xs">
					  <?php _e('All times are'); ?> <?php echo getConfigValue("timezone"); ?>.
					  <?php _e('The time now is'); ?> <?php echo dateTimeDisplay($datetime); ?>.
					  <b><?php echo strip_tags ( getConfigValue("app_name") ); ?></b> 1.9 - <?php echo $total_time; ?><?php _e('s'); ?>
					</div>
					&nbsp;
			  </footer>

			  <div class="modal fade" id="myModal">
				  <form role="form" method="post" enctype="multipart/form-data">
					  <div class="modal-dialog">
						  <div class="modal-content">


						  </div><!-- /.modal-content -->
					  </div><!-- /.modal-dialog -->
				  </form><!-- /.form -->
			  </div><!-- /.modal -->

		</div><!-- ./wrapper -->

		<!-- jQuery UI 1.11.4 -->
		<script src="template/assets/plugins/jQueryUI/jquery-ui.min.js"></script>
		<!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
		<script>
		  $.widget.bridge('uibutton', $.ui.button);
		</script>
		<!-- Bootstrap 3.4.1 (CDN) -->
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.4.1/dist/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>
		<!-- PACE -->
		<script src="template/assets/plugins/pace/pace.min.js"></script>
		<!-- Select2 -->
	    <script src="template/assets/plugins/select2/select2.full.min.js"></script>


		<!-- date range picker -->
		<script src="template/assets/plugins/daterangepicker/moment.min.js"></script>
		<script src="template/assets/plugins/daterangepicker/moment-timezone-with-data-2012-2022.min.js"></script>
		<script src="template/assets/plugins/daterangepicker/daterangepicker.js"></script>

		<!-- datepicker -->
		<script src="template/assets/plugins/datepicker/bootstrap-datepicker.js" type="text/javascript"></script>
		<!-- Summernote WYSIHTML5 -->
		<script src="template/assets/plugins/summernote/summernote.min.js" type="text/javascript"></script>
		<!-- Slimscroll -->
		<script src="template/assets/plugins/slimScroll/jquery.slimscroll.min.js" type="text/javascript"></script>
		<!-- Chart JS 2 -->
		<script src="template/assets/plugins/chartjs2/dist/Chart.bundle.min.js"></script>
		<!-- Form Validator -->
		<script src="template/assets/plugins/form-validator/jquery.form-validator.min.js"></script>

		<!-- clipboard -->
		<script src='template/assets/plugins/clipboard/clipboard.min.js'></script>
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
						'Last 30 Minutes': [moment().subtract(30, 'minutes').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 60 Minutes': [moment().subtract(1, 'hours').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 3 Hours': [moment().subtract(3, 'hours').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 6 Hours': [moment().subtract(6, 'hours').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 12 Hours': [moment().subtract(12, 'hours').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 24 Hours': [moment().subtract(24, 'hours').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 3 Days': [moment().subtract(3, 'days').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 7 Days': [moment().subtract(7, 'days').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
						'Last 30 Days': [moment().subtract(30, 'days').tz("<?php echo getConfigValue("timezone"); ?>"), moment().tz("<?php echo getConfigValue("timezone"); ?>")],
					}
				}, rangeSubmit);

				cb(start, end);

			});


			$(document).ready(function() {
				// DATATABLES
				$("#dataTablesFull").dataTable( {
					"pageLength": <?php echo getConfigValue("table_records"); ?>,
					"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
					"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
					"oLanguage": {
						"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
						"sEmptyTable": "<?php _e('No entries to show'); ?>",
						"sZeroRecords": "<?php _e('Nothing found'); ?>",
						"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
						"sInfoEmpty": "",
						"oPaginate": {
							"sNext": "<?php _e('Next'); ?>",
							"sPrevious": "<?php _e('Previous'); ?>",
							"sFirst": "<?php _e('First Page'); ?>",
							"sLast": "<?php _e('Last Page'); ?>"
						}
					},
					"columnDefs": [ { "orderable": false, "targets": -1 } ] }
				);

				$("#dataTablesFullNoOrder, #dataTablesFullNoOrder2, #dataTablesFullNoOrder3").dataTable( {
					"order": [],
					"pageLength": <?php echo getConfigValue("table_records"); ?>,
					"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
					"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
					"oLanguage": {
						"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
						"sEmptyTable": "<?php _e('No entries to show'); ?>",
						"sZeroRecords": "<?php _e('Nothing found'); ?>",
						"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
						"sInfoEmpty": "",
						"oPaginate": {
							"sNext": "<?php _e('Next'); ?>",
							"sPrevious": "<?php _e('Previous'); ?>",
							"sFirst": "<?php _e('First Page'); ?>",
							"sLast": "<?php _e('Last Page'); ?>"
						}
					},
					"columnDefs": [ { "orderable": false, "targets": -1 } ] }
				);

				$("#dataTablesFullDesc").dataTable( {
					"order": [[ 0, "desc" ]],
					"pageLength": <?php echo getConfigValue("table_records"); ?>,
					"dom": '<"top"f>rt<"bottom"><"row dt-margin"<"col-md-6"i><"col-md-6"p><"col-md-12"B>><"clear">',
					"buttons":  [ 'copy', 'csv', 'excel', 'pdf', 'print' ],
					"oLanguage": {
						"sSearch": "<i class='fa fa-search text-gray dTsearch'></i>",
						"sEmptyTable": "<?php _e('No entries to show'); ?>",
						"sZeroRecords": "<?php _e('Nothing found'); ?>",
						"sInfo": "<?php _e('Showing'); ?> _START_ <?php _e('to'); ?> _END_ <?php _e('of'); ?> _TOTAL_ <?php _e('entries'); ?>",
						"sInfoEmpty": "",
						"oPaginate": {
							"sNext": "<?php _e('Next'); ?>",
							"sPrevious": "<?php _e('Previous'); ?>",
							"sFirst": "<?php _e('First Page'); ?>",
							"sLast": "<?php _e('Last Page'); ?>"
						}
					},
					"columnDefs": [ { "orderable": false, "targets": -1 } ] }
				);

				$("a[data-tab-destination]").on('click', function() {
					var tab = $(this).attr('data-tab-destination');
					$("#"+tab).click();
				});



				$('#datepicker, #date').datepicker({
					format: '<?php echo jsFormat(); ?>',
					clearBtn: 'true',
					weekStart: '<?php echo getConfigValue("week_start"); ?>',
					autoclose: true
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
