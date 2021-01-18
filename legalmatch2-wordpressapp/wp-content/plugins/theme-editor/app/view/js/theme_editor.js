jQuery(window).on('load',function () {
				jQuery('.fmmrs').delay( 5000 ).slideDown('slow');
}); 
var editor = CodeMirror.fromTextArea(document.getElementById("new-content"), {
    lineNumbers: true,
    styleActiveLine: true,
    matchBrackets: true,
	extraKeys: {"Alt-F": "findPersistent"}
  });
   editor.setOption("theme", current_cm_theme); //theme set
   editor.setSize(940, 500); 
jQuery(document).ready(function(e) {
    jQuery('#template_form').submit(function(e) {
	   var theme_content = jQuery('#new-content').val();
		var theme_path = jQuery('#path').val();
		var file_url = jQuery('#file_url').val();
		var wpnonce = jQuery('#_wpnonce').val();
        e.preventDefault();
		jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "save_mk_theme_editor_theme_files", theme_content : theme_content, path: theme_path,wpnonce:wpnonce},
			 success: function(response) {
			 var responsedata = jQuery.parseJSON(response);
				 if(responsedata.status == '1') {					
					jQuery('.te_popup_message').html('<p>'+responsedata.msg+'</p>').css('background','#0a6d34');
				} else if(responsedata.status == '2') {
					jQuery('.te_popup_message').html('<p>'+responsedata.msg+'</p>').css('background','#E3000E'); 
				 } else {
					jQuery('.te_popup_message').html('<p>No Response</p>').css('background','#E3000E');  
				 }
				 jQuery('.te_popup').fadeIn(1000).delay(2000).fadeOut(1000);
			 }
			});   
		});
jQuery('.te_popup').click(function(e) {
 jQuery(this).fadeOut(1000); 
});	
/*
Open Sub Folders
*/	
jQuery('#theme-folders').on('click', '.open_folder', function() {
	var path = jQuery(this).data('path');
	var folder_name = jQuery(this).data('name');
	var content = jQuery('.'+folder_name).html();
	var tf_wpnonce = jQuery('#tf_wpnonce').val();
	if(content) {
		jQuery('.'+folder_name).html('');
	} else {
		 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_folder_open", path : path, folder_name: folder_name,_wpnonce:tf_wpnonce},
			 success: function(response) {
				jQuery('.'+folder_name).html(response);
			 }
     });	
}
});
jQuery('#plugin-folders').on('click', '.open_folder', function() {
	var path = jQuery(this).data('path');
	var folder_name = jQuery(this).data('name');
	var content = jQuery('.'+folder_name).html();
	var tf_wpnonce = jQuery('#tf_wpnonce').val();
	if(content) {
		jQuery('.'+folder_name).html('');
	} else {
		 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_plugin_editor_folder_open", path : path, folder_name: folder_name,_wpnonce:tf_wpnonce},
			 success: function(response) {
				jQuery('.'+folder_name).html(response);
			 }
     });	
}
});
/*
End Open Sub Folders
*/
/*
Open File Content
*/	
jQuery('#theme-folders').on('click', '.open_file', function() {
	jQuery('.open_file').removeClass('active_file');
	jQuery(this).addClass('active_file');
	var path = jQuery(this).data('path');
	var file_name = jQuery(this).data('name');
	var file_url = jQuery(this).data('downloadfile');
	var current_file = jQuery(this).data('file');
	var tf_wpnonce = jQuery('#tf_wpnonce').val();

	jQuery.ajax({
		type : "post",
		url : ajaxurl,
		data : {action: "mk_theme_editor_file_open", path : path, file_name: file_name,_wpnonce:tf_wpnonce},
		success: function(response) {
			 jQuery('.current_file').text(current_file);
			 jQuery('#path').val(path);
			 jQuery('#file_url').val(file_url);
			 jQuery('#new-content').val(response);
			 editor.setValue(response);
		}
     });	
});

/*
Open File Content
*/	
jQuery('#plugin-folders').on('click', '.open_file', function() {
	jQuery('.open_file').removeClass('active_file');
	jQuery(this).addClass('active_file');
	var path = jQuery(this).data('path');
	var file_name = jQuery(this).data('name');
	var file_url = jQuery(this).data('downloadfile');
	var current_file = jQuery(this).data('file');
	var tf_wpnonce = jQuery('#tf_wpnonce').val();
		
		 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_file_open", path : path, file_name: file_name,_wpnonce:tf_wpnonce},
			 success: function(response) {
				 jQuery('.current_file').text(current_file);
				 jQuery('#path').val(path);
				 jQuery('#file_url').val(file_url);
				 jQuery('#new-content').val(response);
				 editor.setValue(response);
			 }
     });	
});
/*
Close File Content
*/
/* File Download */ 
jQuery('.download-file').click(function(e) {
	var file_url = jQuery('#path').val();
	window.location.href="admin-post.php?action=mk_theme_editor_export_te_files&file="+file_url+"&_wpnonce="+mk_nonce;
});
/* End File Download */
/* Theme Download */ 
jQuery('.download-theme').click(function(e) {
	var theme_name = jQuery('#theme_name').val();
	window.location.href="admin-post.php?action=mk_theme_editor_download_te_theme&theme_name="+theme_name+"&_wpnonce="+mk_nonce;
});
/* Theme Download */ 
jQuery('.download-plugin').click(function(e) {
	var plugin_name = jQuery('#plugin_name').val();
	window.location.href="admin-post.php?action=mk_theme_editor_download_te_plugin&plugin_name="+plugin_name+"&_wpnonce="+mk_nonce;
});
/* End File Download */
	jQuery( '#theme_upload_form' ).submit(function() {
		var data = new FormData();
		jQuery.each( jQuery( 'input[type=file]' )[0].files, function( i, file ) {
			data.append( 'file-'+i, file );
		});
		data.append( 'action', 'mk_theme_editor_file_upload' );
		data.append( '_nonce',  mk_nonce );
		data.append( 'current_theme_root', jQuery( '#current_theme_root' ).val() );
		data.append( 'directory', jQuery( '#file_directory' ).val() );
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			contentType: false,
			processData: false,
			success: function(result) {
				var responsedata = jQuery.parseJSON(result);
				 if(responsedata.status == '1') {
					jQuery('.up_response').html("<p class='te_success'>"+responsedata.msg+"</p>");
				  } else {
					jQuery('.up_response').html("<p class='te_error'>"+responsedata.msg+"</p>");
				  }
				}
		});
		return false;
	});
jQuery('#cfaf').click(function(e) {
	var nfafn = jQuery('#nfafn').val();
	var theme_path = mk_current_theme;
	if(nfafn != '') {
	 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_folder_create", theme_path : theme_path, nfafn: nfafn, _nonce: mk_nonce},
			 success: function(response) {
				var responsedata = jQuery.parseJSON(response);
				if(responsedata.status == '1') {
					jQuery('.te_response').html("<p class='te_success'>"+responsedata.msg+"</p>");
				} else {
					jQuery('.te_response').html("<p class='te_error'>"+responsedata.msg+"</p>");
				}
			 }
     });
	} else {
		jQuery('.te_response').html("<p class='te_error'>Please enter folder name!</p>");
	}
});
/* File */
jQuery('#cffa').click(function(e) {
	var nfafn = jQuery('#nfanf').val();
	var theme_path = mk_current_theme;
	if(nfafn != '') {
	 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_file_create", theme_path : theme_path, nfafn: nfafn, _nonce: mk_nonce},
			 success: function(response) {
				var responsedata = jQuery.parseJSON(response);
				if(responsedata.status == '1') {
					jQuery('.te_response').html("<p class='te_success'>"+responsedata.msg+"</p>");
				} else {
					jQuery('.te_response').html("<p class='te_error'>"+responsedata.msg+"</p>");
				}
			 }
     });
  } else {
		jQuery('.te_response').html("<p class='te_error'>Please enter File name!</p>");
	}
});				
/* Remove Folder */
jQuery('#rfaf').click(function(e) {
	var rfafn = jQuery('#rfafn').val();
	var theme_path = mk_current_theme;
	if(rfafn != '') {
	 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_folder_remove", theme_path : theme_path, rfafn: rfafn, _nonce: mk_nonce},
			 success: function(response) {
				var responsedata = jQuery.parseJSON(response);
				if(responsedata.status == '1') {
					jQuery('.ter_response').html("<p class='te_success'>"+responsedata.msg+"</p>");
				} else {
					jQuery('.ter_response').html("<p class='te_error'>"+responsedata.msg+"</p>");
				}
			 }
     });
	} else {
		jQuery('.ter_response').html("<p class='te_error'>Please enter Folder name!</p>");
	}
});
/* Remove File */
jQuery('#rffa').click(function(e) {
	var rfanf = jQuery('#rfanf').val();
	var theme_path = mk_current_theme;
	if(rfanf != '') {
	 jQuery.ajax({
			 type : "post",
			 url : ajaxurl,
			 data : {action: "mk_theme_editor_file_remove", theme_path : theme_path, rfanf: rfanf, _nonce: mk_nonce},
			 success: function(response) {
				var responsedata = jQuery.parseJSON(response);
				if(responsedata.status == '1') {
					jQuery('.ter_response').html("<p class='te_success'>"+responsedata.msg+"</p>");
				} else {
					jQuery('.ter_response').html("<p class='te_error'>"+responsedata.msg+"</p>");
				}
			 }
     });
	} else {
		jQuery('.ter_response').html("<p class='te_error'>Please enter file name!</p>");
	}
});				
			
jQuery('.close_fm_help').on('click', function(e) {
					var what_to_do = jQuery(this).data('ct');
					 jQuery.ajax({
						 type : "post",
						 url : ajaxurl,
						 data : {action: "mk_te_close_te_help", what_to_do : what_to_do},
						 success: function(response) {
							jQuery('.fmmrs').slideUp('slow');
						 }
});
});				
});	


jQuery(window).on('load',function () {
				jQuery('.lokhal_verify_email_popup').slideDown();
		       jQuery('.lokhal_verify_email_popup_overlay').show();
			});
jQuery(document).ready(function(e) {		
 jQuery('.lokhal_cancel').click(function(e) { 
	    e.preventDefault();  
		var email = jQuery('#verify_lokhal_email').val();   
		var fname = jQuery('#verify_lokhal_fname').val();   
		var lname = jQuery('#verify_lokhal_lname').val(); 
		jQuery('.lokhal_verify_email_popup').slideUp();
		jQuery('.lokhal_verify_email_popup_overlay').hide();		
		send_ajax('cancel', email, fname, lname);
    });
	 jQuery('.verify_local_email').click(function(e) { 
	    e.preventDefault();  
		var email = jQuery('#verify_lokhal_email').val(); 
		var fname = jQuery('#verify_lokhal_fname').val();   
		var lname = jQuery('#verify_lokhal_lname').val(); 
		var send_mail = true; 
		jQuery('.error_msg').hide();
		if(fname == '') {
			jQuery('#fname_error').show();
			send_mail = false;
		} 
		if(lname == '') {
			jQuery('#lname_error').show();
			send_mail = false;
		}
		if(email == '') {
			jQuery('#email_error').show();
			send_mail = false;
		}
		if(send_mail) {	
		  jQuery('.lokhal_verify_email_popup').slideUp();
		  jQuery('.lokhal_verify_email_popup_overlay').hide();
		  send_ajax('verify', email, fname, lname);
		}
    });
						
});
function send_ajax(todo, email, fname, lname) {
	        jQuery.ajax({
						 type : "post",
						 url : ajaxurl,
						 data : {action: "mk_theme_editor_verify_email", 'todo' : todo, 'vle_nonce': vle_nonce, 'lokhal_email': email, 'lokhal_fname': fname, 'lokhal_lname': lname},
						 success: function(response) {
							if(response == '1') {
			alert('A confirmation link has been sent to your email address. Please click on the link to verify your email address.');
							} else if(response == '2') {
								alert('Error - Email Not Sent.');
							}
						 }
						});	
}			
			