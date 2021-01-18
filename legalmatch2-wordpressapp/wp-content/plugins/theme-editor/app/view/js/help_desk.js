jQuery(document).ready(function($){ 	   
jQuery('.close_te_help').on('click', function(e) {
					var what_to_do = jQuery(this).data('ct');
					 jQuery.ajax({
						 type : "post",
						 url : ajaxurl,
						 data : {action: "mk_te_close_te_help", what_to_do : what_to_do},
						 success: function(response) {
							jQuery('.wters').slideUp('slow');
						 }
						});	
});		   
});
jQuery(window).on('load',function () {
				jQuery('.wters').delay( 5000 ).slideDown('slow');
			});		