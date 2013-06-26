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
				data = $(data);
				obj.parent().parent().parent().hide('drop', function() {
	    		    $(this).remove();
	        	});
				flashmessages.show(data.find('#flash-messages'));
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
        
        var dialog = $('#removePadDialog');
        var obj = $(this);
        var padloader = obj.parent().parent().find('.loader');
        
        handleDialog(dialog, function(pageUnblock) {
        	pageUnblock();
        	
        	padloader.show();
            $.get(obj.attr('href'), function(data) {
        		padloader.hide();
        		obj.parent().parent().hide('drop', function() {
        		    $(this).remove();
            		});
        		});
            });
        	
        });
}

function openPadAndGroupHandler(obj, group, uploadGroupPicture) {
	obj.find('.padname').off();
	obj.find('.padname').click(function(e) {
		e.preventDefault();
		
		// Collabse other groups and expand actual group
		if(!group.find('.group-content').hasClass('expanded')) {
			$('.group-content.expanded').slideUp();
	        $('.group-content.expanded').removeClass('expanded');
	        
	        var newgroup = $('.group-content.expanded-new');
	        newgroup.removeClass('expanded-new');
	        newgroup.addClass('expanded');
	        
	        $('.group-link.selected').removeClass('selected');
	        group.find('.group-link').addClass('selected');
	        
	        // Only show actions for selected group
			$('.actions').fadeOut();
	        group.find('.group-name .actions').fadeIn();
        
	        // Change the paths for the group picture
	        uploadGroupPicture.changePaths({
	        	pathAdd: newgroup.find('input[name="pathAdd"]').val(),
	    		pathRemove: newgroup.find('input[name="pathRemove"]').val()
	            });
	        // Change the pic
	        uploadGroupPicture.changePic(newgroup.find('input[name="picUrl"]').val());
	        
		}
		
		clickedPadHandler(this);
	});
}

function openPadHandler(obj) {
	obj.find('.padname').off();
	obj.find('.padname').click(function(e) {
		e.preventDefault();
		
		clickedPadHandler(this);
	});
}

function clickedPadHandler (obj) {
	var obj = $(obj);
	
	// only show actions for selected pad
	$('.pads .actions').fadeOut();
	obj.parent().find('.actions').fadeIn();
	
	// deselect other pads & select this
	$('.padname.selected').removeClass('selected');
	obj.addClass('selected');
	
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
}

function usernamesHandler() {
	$('.usernames').hide();
	var userinfo = $('.userinfo');

	userinfo.click(function(e) {
	    e.preventDefault();
	    $(this).parent().find('.usernames').slideToggle();
	    $(this).find('img').toggle();
		});
	
	userinfo.show();
}

function newUserHandler() {
	$('.adduserform').submit(function(e) {
		e.preventDefault();
		
		var $this = $(this);
		
		var $form = $(this), 
        fname = $form.find('input[name="username"]').val(), 
        url = $form.attr('action');
		
		// Show loader
		
		// Clear the form and disable it
    	$form.find('input[name="username"]').val('');
    	$form.children().prop('disabled', true);
    	
    	var groupID = $form.closest('.group').attr('id');
    	
        var posting = $.post (url, {'username':fname })
        .done(function (data) {
            data = $(data);
            // Hide loader
            
            var newUserNames = data.find('#'+groupID+' .usernames div');
            
            $('#'+groupID+' .usernames div').empty().append(newUserNames);
            
            $form.children().prop('disabled', false);
            
            flashmessages.show(data.find('#flash-messages'));
            
        });
    	
	});
}