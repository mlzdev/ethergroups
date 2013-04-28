function switchPublic(url) {
	$.get(url, function(data) {
		data = $(data);
		var status = data.find('#publicStatus .text').html();
	    $('#publicStatus .text').append(status);
	    $('#publicStatus .loader').hide();
		});
}

function removePassword(url) {
	$.get(url, function(data) {
	    data = $(data);
	    flashmessages.show(data.find('#flash-messages'));
	    $('#isPasswordProtected .link').empty().append(data.find('#isPasswordProtected .link').html());
	    $('#pass #addPass').empty().append(data.find('#pass #addPass').html());
	    $('#isPasswordProtected .loader').hide();
		});
}

function initPad() {
	// Make the etherpad iframe resizable
    $("#eplitewrap").resizable({ 
    	handles: { s: '#eplitehandle' },
    	start: function(){
    		ifr = $('#etherpadiframe');
	        var d = $('<div></div>');
	
	        $('#eplitewrap').append(d[0]);
	        d[0].id = 'temp_div';
	        d.css({position:'absolute'});
	        d.css({top: ifr.position().top, left:0});
	        d.height(ifr.height());
	        d.width('100%');
	    },
	    stop: function(){
	    	$('#temp_div').remove();
	    }
      });

	// Send the pass form via ajax
    $("#passForm").submit(function(event){
    	event.preventDefault();

    	$("#passForm .loader").show();

    	var $form = $(this), 
            fpass = $form.find('input[name="form[pass]"]').val(), 
            ftoken = $form.find('input[name="form[_token]"]').val(), 
            url = $form.attr('action');

        var posting = $.post (url, {'form[pass]':fpass, 'form[_token]':ftoken })
        .done(function (data) {
            data = $(data);
            flashmessages.show(data.find('#flash-messages'));
            $('#isPasswordProtected .link').empty().append(data.find('#isPasswordProtected .link').html());
            $form.find('input[name="form[pass]"]').val('');
            $("#passForm .loader").hide();
            $('#pass #addPass').empty().append(data.find('#pass #addPass').html());
            $('#pass #passForm').slideUp();
            });
    });

	// switchpublic if clicked
    $('#switchPublic').click(function(e) {
        e.preventDefault();
        $('#publicStatus .text').empty();
        $('#publicStatus .loader').show();
        switchPublic(this.href);
        });

	// remove password if clicked
    $('#isPasswordProtected .link').click(function(e) {
        e.preventDefault();
        $('#isPasswordProtected .loader').show();
        removePassword(this.href);
        });

    // Hide/Show Passform
    $('#pass #passForm').hide();
    var addPass = $('#pass #addPass');
    addPass.show();
    addPass.addClass("hiddenform");
    addPass.click(function(e) {
        e.preventDefault();
    	var obj = $(this);
    	if(obj.hasClass('hiddenform')) {
    		$('#pass #passForm').slideDown();
    		obj.removeClass('hiddenform');
    		//obj.empty().append('minus');
        }
    	else {
    		$('#pass #passForm').slideUp();
    		obj.addClass('hiddenform');
    		//obj.empty().append('plus');
        }
        });
    
}