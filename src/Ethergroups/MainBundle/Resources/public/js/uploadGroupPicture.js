function UploadGroupPicture(options) {
	var logo = $('#logo_left'); 
	var img = logo.find('img');
	var custompic, imgupload;
	var leftlogo = $('#logo_left');
	var pathOrigPic = options.pathOrigPic;
	var headerpic = $('#headerpic');
	
	this.init = function(options) {
		this.changePaths(options);
	};
	
	this.changePaths = function(options) {
		custompic.attr('href', options.pathRemove);
		imgupload.find('form').attr('action', options.pathAdd);
	};
	
	this.changePic = function(url) {
        if(url) {
        	headerpic.fadeOut(function() {
        		headerpic.attr('src', url);
            	headerpic.fadeIn();        		
        	});
		    img.addClass('custom');
        }
        else {
        	setOrigPic();
        }
	};
	
	function setOrigPic() {
		headerpic.fadeOut(function() {
    		headerpic.attr('src', pathOrigPic);
        	headerpic.fadeIn();        		
    	});
    	img.removeClass('custom');
	}
	
    // Remove link from logo
    logo.empty().append(img);
    
    // Add "delete image" if necessary
    logo.append('<a id="custompic" href="#"><img src="'+options.pathRemovePic+'"/></a>');
    custompic = logo.find('#custompic');
    custompic.hide();
    
    // prepare upload section
    logo.append('<div id="imgupload"><div>Upload</div><form action="#" method="post" enctype="multipart/form-data"><input name="file" type="file" id="imghover" /></form></div>');
    imgupload = $('#imgupload');
    imgupload.hide();

	// Handle upload
    var imgform = $('#imgupload form');
    imgform.iframePostForm({
        json: true,
        post: function() {
        	leftlogo.block({
        		message: $('#loader-bar'),
    			overlayCSS: { backgroundColor: 'lightgray' },
    			centerX: false,
    			css: { 
    				border: 'none',
    				left: '21%',
    				backgroundColor: 'none',
    			}
        	});
        },
        complete: function (response) {
            var html='';
            leftlogo.unblock();
			if (!response.success)
			{
				$('#flash-messages').slideUp(function ()
				{
					$(this).empty().append('<div class="flash-message"></div>');
					var fm = $(this).children('.flash-message');
					fm.html('There was a problem with the image you uploaded');
					$(this).slideDown();
				});
			}
			else
			{

				if(response.url) {
				    $('#headerpic').attr('src', response.url);
				    img.addClass('custom');
				    
				    changeGroupPicUrl(response.url);
					}
				
				/*
				$('#flash-messages').slideUp(function ()
				{
					$(this).empty().append('<div class="flash-message"></div>');
					var fm = $(this).children('.flash-message');
					fm.html(html);
					$(this).slideDown();
				});
				*/
			}

            }
        });
    
    function changeGroupPicUrl(url) {
	    // TODO: This mapping is not optimal, better filename = groupid
    	$('.group-content.expanded input[name="picUrl"]').val(url);
    }

	// Show upload link and delete img (if necessary)
    logo.hover(function(e) {
        imgupload.toggle();
        if(img.hasClass('custom')) {
            if(imgupload.is(":visible")) {
            	custompic.show();
                }
            else {
            	custompic.hide();
                }
        }
    });
    logo.on('mouseleave', function() {
    	imgupload.hide();
    	custompic.hide();
    });

    // if a file is selected, upload it
    $('#imgupload #imghover').change(function() {
        // Do the upload
        imgform.submit();        
        });
    
    // click handler for "remove picture"
    custompic.click(function(e) {
        e.preventDefault();
        
        leftlogo.block({
    		message: $('#loader-bar'),
			overlayCSS: { backgroundColor: 'lightgray' },
			centerX: false,
			css: { 
				border: 'none',
				left: '21%',
				backgroundColor: 'none',
			}
    	});
        
        $.get($(this).attr('href'), function(data) {
        	custompic.hide();
        	setOrigPic();
        	changeGroupPicUrl(null);
        	leftlogo.unblock();
        });
    });
}