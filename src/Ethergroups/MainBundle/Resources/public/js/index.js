/**
 * Created by timowelde on 06.12.13.
 */

function initIndex(pathRemovePic) {
    // Collapse other groups
    $('.group-content').hide();

    // Hide actions
    $('.actions').hide();

    // Hide usernames and enable showUsernamesHandler
    usernamesHandler();
    // Add Submithandler to the newuser form
    newUserHandler();

    // Hide flash messages with a click
    flashmessages.clickHandler();

    // Add Handler for "remove group"
    $('.group_delete').each(function() {
        removeGroupHandler($(this));
    });


    // initialise group picture upload
    var uploadGroupPicture = new UploadGroupPicture({
        pathRemovePic: pathRemovePic,
        pathOrigPic: $('#headerpic').attr('src')
    });

    // Prepare newpadforms
    var newpadform = $('.pads .newpadform');

    // Add submit handler for the newpadforms
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
                }
                else {
                    data = $(data.data);
                    padsloader.hide();
                    flashmessages.show(data.find('#flash-messages'));
                }
            });
    });

    // Ajax call for expanding group, if clicked
    $('.group-link').click(function(e) {
        e.preventDefault();

        var obj = $(this);
        var parent = obj.parent();
        var group = obj.parent().parent();
        var group_content = group.find('.group-content');

        if (group_content.hasClass('expanded-new')) {
            group_content.slideUp();
            group_content.removeClass('expanded-new');
        }
        else if (!group_content.hasClass('expanded')) {

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

                // Hide Removepad icon
                padscontent.find('.actions').hide();

                // Add "new pad" form under the pads
                newpadform.empty().append(data.find('#newpad').html());

                // expand actual group
                group_content.slideDown();
                group_content.addClass('expanded-new');

                // If no pad is open yet, open first one
                if($('#pad-content').hasClass('empty')) {
                    padscontent.find('a:first').click();
                }

            });
        }
    });

    // Trigger click for first group
    $('.group:first .group-link').click();

    // Show group_edit button
    var editgroup = $('.group .editgroup');
    editgroup.show();

    // Edit groupname click handler
    editgroup.click(function(e) {
        e.preventDefault();
        var groupname = $(this).parent().parent();
        var name = groupname.find('.group-link').text();
        groupname.find('.editform input[name="groupname"]').val(name);
        groupname.find('.group-link').toggle();
        groupname.find('.editform').toggle();
        groupname.find('.editform input[name="groupname"]').focus();
    });

    // groupname form submit handler
    $('.group .editform').submit(function(e) {
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

    $('#togglegroups').click(function(e){
        e.preventDefault();
        $('#groups-menu').toggle();
        $('#pad').toggleClass('fullwidth');
        $(this).find('img').toggle();
    });
}