$(function() {
	var langform = $('#langform');
	langform.find('input[type="submit"]').hide();
	
	langform.change(function() {
    	langform.submit();
	});
	
});