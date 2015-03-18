function renameGroupClickHandler(obj) {
    obj.click(function(e) {
        e.preventDefault();
        e.stopPropagation();
        var groupname = $(this).parent().parent();
        var name = groupname.find('.group-link').text();
        groupname.find('.editform input[name="groupname"]').val(name);
        groupname.find('.group-link').toggle();
        groupname.find('.editform').toggle();
        groupname.find('.editform input[name="groupname"]').focus();
    });
}

function renameGroupFormHandler(obj) {
    obj.submit(function(e) {
        e.preventDefault();

        var $form = $(this),
            fname = $form.find('input[name="groupname"]').val(),
            url = $form.attr('action');

        var groupname = $form.parent();

        var editloader = $form.find('.editloader');
        editloader.show();

        $.post (url, {'groupname':fname })
            .done(function (data) {
                groupname.find('.group-link').text(data.newname);
                editloader.hide();
                groupname.find('.group-link').toggle();
                groupname.find('.editform').toggle();
            });
    });
}

function removeGroupHandler(obj) {
	obj.click(function(e) {
		e.preventDefault();
        e.stopPropagation();
        var obj = $(this);

        $.getJSON(obj.next().attr('href'), function(data) {
            var dialog;

            if(data.last) {
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
    });
}

function expandGroupHandler(obj, newpadform, uploadGroupPicture) {
    obj.click(function(e) {
        e.preventDefault();
        expandGroup($(this), newpadform, uploadGroupPicture, function(padscontent) {
            // If no pad is open yet, open first one
            if($('#pad-content').hasClass('empty')) {
                padscontent.find('a:first').click();
            }
        });
    });
}

function expandGroup($this, newpadform, uploadGroupPicture, callback) {
    var obj = $this.find('.group-link');
    var parent = obj.parent();
    var group = obj.parent().parent();
    var group_content = group.find('.group-content');

    if (group_content.hasClass('expanded-new')) { // Group has been expanded before, but isn't open group
        group_content.slideUp();
        group_content.removeClass('expanded-new');
    }
    else if (!group_content.hasClass('expanded')) {  // Group isn't open group, but should be expanded

        // Close other new expanded groups
        $('.group-content.expanded-new').slideUp();
        $('.group-content.expanded-new').removeClass('expanded-new');

        // Prepare loading process
        parent.find('.loader').show();
        group_content.slideUp();

        // Get the pads
        $.get(obj.attr('href'), function(data) {
            data = $(data);

            // Add the pads in this page
            var padscontent = group.find('.pads .content');
            padscontent.empty();
            padscontent.append(data.find('#pads').html());

            // Add click handler for openPad
            openPadAndGroupHandler(padscontent, group, uploadGroupPicture);

            // Add click handler for removePad
            removePadHandler(padscontent);
            parent.find('.loader').hide();

            // Show pad actions on hover
            showActionsHandler(padscontent.children());

            // Hide Removepad icon
            padscontent.find('.actions').hide();

            // Add "new pad" form under the pads
            newpadform.empty().append(data.find('#newpad').html());

            // expand actual group
            group_content.slideDown();
            group_content.addClass('expanded-new');

            callback(padscontent);
        });
    }
    else { // Group is open
        callback(group.find('.pads .content'))
    }
}

function newPadFormSubmitHandler(newpadform, uploadGroupPicture) {
    newpadform.submit(function(e){
        e.preventDefault();
        var obj = $(this);
        var padsloader = obj.parent().find('.loader');
        padsloader.show();

        var $form = $(this),
            fname = $form.find('input[name="form[name]"]').val(),
            ftoken = $form.find('input[name="form[_token]"]').val(),
            url = $form.attr('action');

        // Clear the form
        $form.find('input[name="form[name]"]').val('');

        // post form via ajax
        var posting = $.post (url, {'form[name]':fname, 'form[_token]':ftoken })
            .done(function (data) {
                if(data.success) {
                    data = $(data.data);
                    var padscontent = obj.parent().parent().find('.content');
                    // Get the newpad; hide it and its actions
                    var newpad = data.find('.pad').hide();
                    newpad.find('.actions').hide();
                    var newName = newpad.find('.padname').text().toLowerCase();
                    var inserted = false;
                    padscontent.children().each(function(index, element) {
                        element = $(element);
                        var padname = element.find('.padname').text();
                        if(strnatcmp(newName, padname.toLowerCase()) <=0 ) {
                            newpad.insertBefore(element);
                            inserted = true;
                            return false;
                        }
                    });
                    if(!inserted) {
                        padscontent.append(newpad);
                    }
                    padsloader.hide();
                    newpad.show('drop');
                    //newpad.slideDown();
                    removePadHandler(padscontent);
                    openPadAndGroupHandler(padscontent, padscontent.closest('.group'), uploadGroupPicture);
                    showActionsHandler(newpad);
                }
                else {
                    data = $(data.data);
                    padsloader.hide();
                    flashmessages.show(data.find('#flash-messages'));
                }
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
        e.stopPropagation();
        
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
	obj.find('.pad').off('click');
	obj.find('.pad').click(function(e) {
		e.preventDefault();
		openPadAndGroup($(this), group, uploadGroupPicture)
	});
}

function openPadAndGroup($this, group, uploadGroupPicture) {

    // Collabse other groups and expand actual group
    if(!group.find('.group-content').hasClass('expanded')) {
        var otherOpenGroup = $('.group-content.expanded');
        otherOpenGroup.slideUp();
        showActionsHandler(otherOpenGroup.parent().children('.group-name'));
        otherOpenGroup.removeClass('expanded');

        var newgroup = $('.group-content.expanded-new');
        newgroup.removeClass('expanded-new');
        newgroup.addClass('expanded');

        $('.group-link.selected').removeClass('selected');
        group.find('.group-link').addClass('selected');

        // Only show actions for selected group
        $('.actions').fadeOut();
        group.find('.group-name .actions').fadeIn();
        group.find('.group-name').off('hover mouseleave');

        // Change the paths for the group picture
        uploadGroupPicture.changePaths({
            pathAdd: newgroup.find('input[name="pathAdd"]').val(),
            pathRemove: newgroup.find('input[name="pathRemove"]').val()
        });
        // Change the pic
        uploadGroupPicture.changePic(newgroup.find('input[name="picUrl"]').val());

    }

    clickedPadHandler($this.children('.padname'));
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

    var otherOpenPad = $('.padname.selected');

	// only show actions for selected pad
	$('.pads .actions').not(obj.parent().find('.actions')).fadeOut();
    showActionsHandler(otherOpenPad.parent());
	obj.parent().find('.actions').fadeIn();
    obj.parent().off('hover mouseleave');
	
	// deselect other pads & select this
	otherOpenPad.removeClass('selected');
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
            
            $('#'+groupID+' .usernames div').empty().append(newUserNames.html());
            
            var newUserNo = data.find('#'+groupID+' .userinfo .usernumber');
            $('#'+groupID+' .userinfo .usernumber').empty().append(newUserNo.html());
            
            $form.children().prop('disabled', false);
            
            flashmessages.show(data.find('#flash-messages'));
            
        });
    	
	});
}

function showActionsHandler(obj) {
    obj.hover(function(){
        $(this).children('.actions').show();
    });
    obj.mouseleave(function(){
        $(this).children('.actions').hide();
    });
}