function UploadGroupPicture(options) {
	var logo = $('#logo_left'); 
	var img = logo.find('img');
	var custompic, imgupload;
	this.pathOrigPic = options.pathOrigPic;
	
	this.init = function(options) {
		this.changePaths(options);
	};
	
	this.changePaths = function(options) {
		custompic.attr('href', options.pathRemove);
		imgupload.find('form').attr('action', options.pathAdd);
	};
	
	this.changePic = function(url) {
		var headerpic = $('#headerpic'); 
        if(url) {
        	headerpic.fadeOut(function() {
        		headerpic.attr('src', url);
            	headerpic.fadeIn();        		
        	});
		    img.addClass('custom');
        }
        else {
        	var pathOrigPic = this.pathOrigPic;
        	headerpic.fadeOut(function() {
        		headerpic.attr('src', pathOrigPic);
            	headerpic.fadeIn();        		
        	});
        	img.removeClass('custom');
        }
	};
	
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
        complete: function (response) {
            var html='';
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
				    
				    // TODO: This mapping is not optimal, better filename = groupid
				    $('.group-content.expanded input[name="picUrl"]').val(response.url);
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

    // if a file is selected, upload it
    $('#imgupload #imghover').change(function() {
        // Do the upload
        imgform.submit();        
        });
    
    // click handler for "remove picture"
    custompic.click(function(e) {
        e.preventDefault();
        });
	    
}