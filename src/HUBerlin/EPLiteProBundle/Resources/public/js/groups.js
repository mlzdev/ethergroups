function fmessagesClickHandler() {
	// possibility to hide flash-messages with a click
    $('#flash-messages').click(function(e) {
        $(this).slideUp();
        });
}

function removeGroupHandler(obj) {
	obj.click(function(e) {
		e.preventDefault();
		
		var dialog;
		if(obj.hasClass('last')) {
			dialog = $('#removeGroupLastDialog');
		}
		else {
			dialog = $('#removeGroupDialog');
		}
		
		handleDialog(dialog, function(pageUnblock) {
			$.get(obj.attr('href'), function(data) {
				obj.parent().parent().hide('drop', function() {
	    		    $(this).remove();
	        	});
				pageUnblock();
			});
		});
	});
}

function handleDialog(obj, yesFunction) {
	
	obj.show();

	var dialogs = $('#removeDialogs');
	var page = $('#page'); 
	page.block({
		message: dialogs,
		onUnblock: function() {
			hideDialogs();
			yes.off();
			no.off();
		}
	});
	
	var yes = dialogs.find('.yes');
	var no = dialogs.find('.no');
	
	yes.click(function() {
		yesFunction(function() {
			page.unblock();
		});
	});
	
	no.click(function() {
		page.unblock();
	});
}

function hideDialogs() {
	$('#removeDialogs').find('div').hide();
}

function removePadHandler(obj) {
	obj.find('.padremovelink').off();
	obj.find('.padremovelink').click(function(e) {
        e.preventDefault();
        var obj = $(this);
        var padloader = obj.parent().find('.loader');
        padloader.show();
        $.get(obj.attr('href'), function(data) {
    		padloader.hide();
    		obj.parent().hide('drop', function() {
    		    $(this).remove();
        		});
    		});
        });
}

function openPadHandler(obj) {
	obj.find('.padname').off();
	obj.find('.padname').click(function(e) {
		e.preventDefault();
		
		var obj = $(this);
		
		var pad = $('#pad');
		pad.block({
			message: $('#loader-bar'),
			overlayCSS: { backgroundColor: 'lightgray' },
			css: { 
				border: 'none',
				backgroundColor: 'none' },
			onBlock: function() {
				$.get(obj.attr('href'), function(data) {
				    data = $(data);
				    var content = data.find('.page-content').html();
				    var padcontent = $('#pad-content');
				    padcontent.empty().append(content);
				    padcontent.removeClass('empty');
				    pad.unblock();
					});				
				},
			onUnblock: function() {
				initPad();
				}
			});
		});
}

function usernamesHandler() {
	var usernames = $('.usernames').hide();
	var userinfo = $('.userinfo');

	userinfo.click(function(e) {
	    e.preventDefault();
	    usernames.slideToggle();
		});
	
	userinfo.show();
}