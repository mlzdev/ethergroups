$(function() {
	var langform = $('#langform');
	langform.find('input[type="submit"]').hide();
	
	langform.change(function() {
    	langform.submit();
	});
	
    $('#toggle-header').click(function(e){
        e.preventDefault();
        var obj = $(this);
        if(obj.hasClass('hidden')) {
        	obj.removeClass('hidden');
            $('#header').animate({
                top: '0',
                marginBottom: '0',
            });
        }
        else {
        	obj.addClass('hidden');
            $('#header').animate({
                top: '-110px',
                marginBottom: '-100px'
            });
        }
    });
});