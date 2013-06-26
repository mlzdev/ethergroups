function switchPublic(url, callback) {
	$.get(url, function(data) {
		data = $(data);
		var status = data.find('#publicStatus .text').html();
		var isPublic = data.find('#publicIndicator').hasClass('on');
	    if(isPublic) {
	    	$('#publicIndicator').addClass('on');
	    	$('#publicStatus .text').slideDown();
	    	$('#pass').slideDown();
	    }
	    else {
	    	removePassword($('#removePass').attr('href'), false);
	    	$('#publicIndicator').removeClass('on');
	    	$('#publicStatus .text').slideUp();
	    	$('#pass').slideUp();
	    }
	    $('#publicStatus .loader').hide();
	    callback();
		});
}

function removePassword(url, showFlash) {
	if(showFlash === undefined) {
		showFlash = true;
	}
	$.get(url, function(data) {
	    data = $(data);
	    if(showFlash) flashmessages.show(data.find('#flash-messages'));
	    $('#removePass').empty().append(data.find('#removePass').html());
	    $('#pass #switchPass').empty().append(data.find('#pass #switchPass').html());
	    $('#pass .loader').hide();
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

    	$("#pass .loader").show();

    	var $form = $(this), 
            fpass = $form.find('input[name="form[pass]"]').val(), 
            ftoken = $form.find('input[name="form[_token]"]').val(), 
            url = $form.attr('action');

        var posting = $.post (url, {'form[pass]':fpass, 'form[_token]':ftoken })
        .done(function (data) {
            data = $(data);
            flashmessages.show(data.find('#flash-messages'));
            $('#removePass').empty().append(data.find('#removePass').html());
            $form.find('input[name="form[pass]"]').val('');
            $("#pass .loader").hide();
            $('#pass #switchPass').empty().append(data.find('#pass #switchPass').html());
            $('#pass #passForm').slideUp();
            });
    });

	// switchpublic if clicked
    $('#switchPublic').click(function(e) {
        e.preventDefault();
        var $this = $(this);
    	if($this.data('disabled')) return;
  	  	$this.data('disabled',true);
        $('#publicStatus .loader').show();
        switchPublic(this.href, function() {
        		$this.removeData('disabled');
        	});
        });

    // Hide/Show Passform
    $('#pass #passForm').hide();
    $('#removePass').hide();
    var switchPass = $('#pass #switchPass');
    switchPass.addClass("hiddenform");
    switchPass.click(function(e) {
        e.preventDefault();
    	var obj = $(this);
    	if($('#passIndicator').hasClass('on')) {
    		$('#pass .loader').show();
    		removePassword($('#removePass').attr('href'));
    	}
    	else {
    		if(obj.hasClass('hiddenform')) {
        		$('#pass #passForm').slideDown();
        		obj.removeClass('hiddenform');
            }
        	else {
        		$('#pass #passForm').slideUp();
        		obj.addClass('hiddenform');
            }
    	}
        });
    
}