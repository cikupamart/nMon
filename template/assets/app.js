/* nMon Modern App JavaScript */

// Initialize PACE loader on AJAX calls
$(document).ajaxStart(function() { Pace.restart(); });

$(document).ready(function() {

	// Auto-dismiss alerts after 3 seconds
	window.setTimeout(function() {
		$(".alert-auto").fadeTo(500, 0).slideUp(500, function(){
			$(this).remove();
		});
	}, 3000);

	// Initialize Select2 dropdowns
	if ($.fn.select2) {
		$(".select2").select2({
			theme: "bootstrap",
			width: '100%'
		});

		$(".select2tag").select2({
			theme: "bootstrap",
			tags: true,
			maximumSelectionLength: 1,
			width: '100%'
		});

		$(".select2tags").select2({
			theme: "bootstrap",
			tags: true,
			width: '100%'
		});
	}

	// Initialize Summernote editor
	if ($.fn.summernote) {
		$('.summernoteLarge').summernote({
			height: 400,
			toolbar: [
				['style', ['bold', 'italic', 'underline', 'strikethrough']],
				['para', ['ul', 'ol', 'paragraph']],
				['insert', ['link', 'picture']],
				['view', ['fullscreen', 'codeview']]
			]
		});
		
		$('.summernote').summernote({
			height: 200,
			toolbar: [
				['style', ['bold', 'italic', 'underline', 'strikethrough']],
				['para', ['ul', 'ol', 'paragraph']],
				['insert', ['link', 'picture']],
				['view', ['fullscreen', 'codeview']]
			]
		});
	}

	// Initialize Bootstrap tooltips
	if ($.fn.tooltip) {
		$('[data-toggle="tooltip"]').tooltip();
	}

	// Initialize Bootstrap popovers
	if ($.fn.popover) {
		$('[data-toggle="popover"]').popover();
	}

	// Add confirmation dialog for delete actions
	$('a[href*="delete"], .btn-delete').on('click', function(e) {
		if (!confirm('Are you sure you want to delete this item?')) {
			e.preventDefault();
			return false;
		}
	});

	// Smooth scroll to top
	$('.scroll-to-top').on('click', function(e) {
		e.preventDefault();
		$('html, body').animate({ scrollTop: 0 }, 300);
	});

	// Add loading spinner to form submissions
	$('form').on('submit', function() {
		var $btn = $(this).find('button[type="submit"]');
		if ($btn.length) {
			$btn.prop('disabled', true);
			$btn.html('<i class="fa fa-spinner fa-spin"></i> Processing...');
		}
	});

});

// Auto-refresh functionality
var myRefreshTimeout;

function startAutorefresh(refreshPeriod) {
	myRefreshTimeout = setTimeout("window.location.reload();", refreshPeriod);
}

function stopAutorefresh() {
	clearTimeout(myRefreshTimeout);
	window.location.hash = 'stop';
}


// Modal loading function
function showM(url) {
	$('.modal-content').empty();
	$('.modal-content').load(url);
	$('#myModal').modal('show');
	stopAutorefresh();
}

// Go back function
function goBack() {
	window.history.back();
}


// Countdown timer
function Countdown(options) {
	var timer,
	instance = this,
	seconds = options.seconds || 10,
	updateStatus = options.onUpdateStatus || function () {},
	counterEnd = options.onCounterEnd || function () {};

	function decrementCounter() {
		updateStatus(seconds);
		if (seconds === 0) {
			counterEnd();
			instance.stop();
		}
		seconds--;
	}

	this.start = function () {
		clearInterval(timer);
		timer = 0;
		seconds = options.seconds;
		timer = setInterval(decrementCounter, 1000);
	};

	this.stop = function () {
		clearInterval(timer);
	};
}


// Copy to clipboard function
function copyToClipboard(text) {
	if (navigator.clipboard) {
		navigator.clipboard.writeText(text).then(function() {
			showNotification('Copied to clipboard!', 'success');
		});
	} else {
		// Fallback for older browsers
		var textArea = document.createElement("textarea");
		textArea.value = text;
		document.body.appendChild(textArea);
		textArea.select();
		try {
			document.execCommand('copy');
			showNotification('Copied to clipboard!', 'success');
		} catch (err) {
			showNotification('Failed to copy', 'error');
		}
		document.body.removeChild(textArea);
	}
}


// Show notification
function showNotification(message, type) {
	var $notification = $('<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
		'<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
		'<span aria-hidden="true">&times;</span></button>' +
		message + '</div>');
	
	$('.content-header').after($notification);
	
	setTimeout(function() {
		$notification.fadeOut(500, function() {
			$(this).remove();
		});
	}, 3000);
}


// Format bytes to human readable
function formatBytes(bytes, decimals) {
	if (bytes === 0) return '0 Bytes';
	var k = 1024;
	var dm = decimals || 2;
	var sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
	var i = Math.floor(Math.log(bytes) / Math.log(k));
	return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}


// Format date to relative time
function timeAgo(date) {
	var seconds = Math.floor((new Date() - date) / 1000);
	
	var interval = seconds / 31536000;
	if (interval > 1) return Math.floor(interval) + " years ago";
	
	interval = seconds / 2592000;
	if (interval > 1) return Math.floor(interval) + " months ago";
	
	interval = seconds / 86400;
	if (interval > 1) return Math.floor(interval) + " days ago";
	
	interval = seconds / 3600;
	if (interval > 1) return Math.floor(interval) + " hours ago";
	
	interval = seconds / 60;
	if (interval > 1) return Math.floor(interval) + " minutes ago";
	
	return Math.floor(seconds) + " seconds ago";
}


// Debounce function for search inputs
function debounce(func, wait) {
	var timeout;
	return function() {
		var context = this, args = arguments;
		clearTimeout(timeout);
		timeout = setTimeout(function() {
			func.apply(context, args);
		}, wait);
	};
}


// AJAX helper function
function ajaxRequest(url, method, data, successCallback, errorCallback) {
	$.ajax({
		url: url,
		method: method || 'GET',
		data: data || {},
		dataType: 'json',
		beforeSend: function() {
			// Show loading indicator
			$('.content-wrapper').addClass('loading');
		},
		success: function(response) {
			if (successCallback) successCallback(response);
		},
		error: function(xhr, status, error) {
			if (errorCallback) {
				errorCallback(xhr, status, error);
			} else {
				showNotification('An error occurred: ' + error, 'danger');
			}
		},
		complete: function() {
			// Hide loading indicator
			$('.content-wrapper').removeClass('loading');
		}
	});
}
