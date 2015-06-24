$(function() {
	var langform = $('#langform');
	langform.find('input[type="submit"]').hide();
	
	langform.change(function() {
    	langform.submit();
	});
	
    $('#toggle-header').click(function(e){
        e.preventDefault();
        var obj = $(this);
        toggleHeader(obj);
    });

    // show/hide groups
    $('#togglegroups').click(function(e){
        e.preventDefault();
        toggleGroups($(this))
    });

    $('#toggle-fullscreen').click(function(e) {
        e.preventDefault();
        toggleHeader($('#toggle-header'));
        toggleGroups($('#togglegroups'));
    })

    function toggleHeader(obj) {
        if(obj.hasClass('hidden')) {
            obj.removeClass('hidden');
            $('#header').animate({
                top: '0',
                marginBottom: '0',
            }, function() {switchToggleHeaderPic();});
        }
        else {
            obj.addClass('hidden');
            $('#header').animate({
                top: '-110px',
                marginBottom: '-100px'
            }, function() {switchToggleHeaderPic();});
        }
    }

    function toggleGroups(obj) {
        $('#groups-menu').toggle();
        $('#pad').toggleClass('fullwidth');
        obj.find('img').toggle();
    }
});

function switchToggleHeaderPic() {
	$('#toggle-header img').toggle();
}