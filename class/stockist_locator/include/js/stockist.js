function stockist_admin_tab( _this ){
	if (jQuery(_this).is(':checked')) {
		jQuery('.show_if_stockists').show();
		jQuery('.hide_if_stockists').hide();
	} else {
		jQuery('.show_if_stockists').hide();
		jQuery('.hide_if_stockists').show();
	}
}
jQuery(document).on('change', '#_stockists', function(){
	stockist_admin_tab( jQuery(this) );
});
jQuery(document).ready(function (){
	stockist_admin_tab( jQuery('#_stockists') );
	setTimeout( function (){ stockist_admin_tab( jQuery('#_stockists') ); }, 500 );
	if( typeof woocommerce_admin_meta_boxes != 'undefined' )
		woocommerce_admin_meta_boxes.product_types[woocommerce_admin_meta_boxes.product_types.length] = 'stockists';
});

jQuery(document).ready(function (){
	jQuery('body').append(	'<div id="category_add_modal">'+
								'<div>'+
									'<div id="category_add_modal_close">close</div>'+
									'<h3>Add new category</h3>'+
									'<input type="text" id="new_category_name" value="" placeholder="Category name" required /> '+
									'<input type="submit" id="new_category_submit" value="Add" />'+
								'</div>'+
							'</div>');
	
	jQuery(document).on("click", "#add_new_category_action", function (event){
		event.preventDefault();
		jQuery('#category_add_modal').show();
	});
	
	jQuery(document).on("click", "#category_add_modal_close", function (event){
		event.preventDefault();
		jQuery('#category_add_modal').hide();
	});
	
	jQuery(document).on("click", "#new_category_submit", function (event){
		event.preventDefault();
		name = jQuery("#new_category_name").val();
		jQuery.post(stockist_ajaxurl, {action: 'stockist_add_category', category_name: name}, function (html){
			if( typeof html != 'undefined' && html != '' ){
				jQuery('select[name="category[]"]').html( html );
				jQuery('#_stockists_category').html( html );
			}
		})
		jQuery('#category_add_modal').hide();
	});
	
	var custom_uploader, custom_subuploader;
 
 
    jQuery('#upload_image_button').click(function(e) {
 
        e.preventDefault();
 
        //If the uploader object has already been created, reopen the dialog
        if (custom_uploader) {
            custom_uploader.open();
            return;
        }
 
        //Extend the wp.media object
        custom_uploader = wp.media.frames.file_frame = wp.media({
            title: 'Choose Image',
            button: {
                text: 'Choose Image'
            },
            multiple: true
        });
 
        //When a file is selected, grab the URL and set it as the text field's value
        custom_uploader.on('select', function() {
            attachment = custom_uploader.state().get('selection').first().toJSON();
            jQuery('#upload_image').val(attachment.url);
        });
 
        //Open the uploader dialog
        custom_uploader.open();
 
    });
    
    jQuery(document).on("click", "#gallery_upload_image_button", function (event){
		
		event.preventDefault();
		if( !stockist_frame ){
			stockist_frame = wp.media({
				title : 'Stockist Gallery',
				frame: 'post',
				multiple : true, // set to false if you want only one image
				library : { type : 'image'},
				button : { text : 'Add Images' },
			});
			stockist_frame.on('close',function(data) {
				var imageArrayUrls = [];
				imageArray = [];
				stockist_images = stockist_frame.state().get('selection');
				stockist_images.each(function(image) {
					imageArray.push(image.attributes.id);
					imageArrayUrls.push(image.attributes.url);
				});

				jQuery("#imageurls").val( JSON.stringify({ids:imageArray, urls:imageArrayUrls}) ); // Adds all image URL's comma seperated to a text input
			});
			stockist_frame.on('open',function() {
				if( imageArray.length > 0 ){
					var selection = stockist_frame.state().get('selection');
					ids = imageArray;
					ids.forEach(function(id) {
						attachment = wp.media.attachment(id);
						attachment.fetch();
						selection.add( attachment ? [ attachment ] : [] );
					});
				}
			});
		}else
			stockist_frame.state().set('selection', stockist_images);
		stockist_frame.open()
		
		
		//~ wp.media.gallery.link = 'stockist-2';
		//~ wp.media.gallery.id = 2;
		//~ wp.media.gallery.edit('[gallery ids="2"]').on('update',function(n,a) {console.log(n); console.log(a)});
	});
});

if( typeof imageArray == 'undefined' )
	var imageArray = [];
var stockist_frame, stockist_images;