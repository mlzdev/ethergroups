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

    removeUserHandler($('.remove-user'));

    // Hide flash messages with a click
    flashmessages.clickHandler();

    // Add Handler for "remove group"
    removeGroupHandler($('.group_delete'));

    // initialise group picture upload
    var uploadGroupPicture = new UploadGroupPicture({
        pathRemovePic: pathRemovePic,
        pathOrigPic: $('#headerpic').attr('src')
    });

    // Prepare newpadforms
    var newpadform = $('.pads .newpadform');
    // Add submit handler for the newpadforms
    newPadFormSubmitHandler(newpadform, uploadGroupPicture);
    // Ajax call for expanding group, if clicked
    expandGroupHandler($('#groups .group-name'), newpadform, uploadGroupPicture);

    // show action icons when mouseover groupname
    showActionsHandler($('#groups .group-name'))

    var search = location.search.substring(1).split('=')[1];
    if(!search) {
        // Trigger click for first group
        $('.group:first .group-link').click();
    }
    else {
        search = decodeURIComponent(search)
        openFromSearch(search)
        history.replaceState(true, '', null);
    }

    window.onpopstate = function(event) {
        if(event.state) {
            var search = location.search.substring(1).split('=')[1];
            search = decodeURIComponent(search)
            openFromSearch(search)
        }
    }

    function openFromSearch(search) {
        // Try to open pad and group from hash
        search = search.split('/')
        var groupid = search[0];
        var group = $('#group-'+groupid);
        expandGroup(group, newpadform, uploadGroupPicture, function(padscontent) {
            group.find('[data-name="'+search[1]+'"]').click();

        })
    }

    // Show group_edit button (This functionality is hidden, when js is disabled)
    var editgroup = $('.group .editgroup').show();
    // Edit groupname click handler
    renameGroupClickHandler(editgroup);

    // groupname form submit handler
    renameGroupFormHandler($('.group .editform'));

    // show/hide groups
    $('#togglegroups').click(function(e){
        e.preventDefault();
        $('#groups-menu').toggle();
        $('#pad').toggleClass('fullwidth');
        $(this).find('img').toggle();
    });

    // renew cookie every x seconds
    renewCookieHandler()
}

function renewCookieHandler() {
    var renewObj = $('#renewCookie')
    var url = renewObj.attr('href')

    var delay = parseInt(renewObj.data('expires'))*1000

    timeoutFunc = function(delay) {
        renewCookieTimeout = setTimeout(function(){
            $.get(url, function(data, textStatus, jqXHR) {
                var delay = parseInt(data)*1000
                timeoutFunc(delay)
            })
        }, delay)
    }

    timeoutFunc(delay)

}