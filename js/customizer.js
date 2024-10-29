( function( $ ) {
	
	var AIODIE = {

		init: function()
		{
			$( 'input[name=aiodie-export-button]' ).on( 'click', AIODIE._export );
			$( 'input[name=aiodie-import-button]' ).on( 'click', AIODIE._import );
		},

		_export: function()
		{
			var q= "";
			if ($('#aiodie-export-customize').is(':checked')) {
				q += "aiodie-export-customize=yes&";
			}
			if ($('#aiodie-export-content').is(':checked')) {
				//console.log('all content');
				q += "aiodie-export-content=yes&";
			}
			if ($('#content-post').is(':checked')) {

				var cat = $("#cat option:selected").val();
				var post_author = $("#post_author option:selected").val();
				var post_start_date = $("#post-start-date option:selected").val();
				var post_end_date = $("#post-end-date option:selected").val();
				var post_status = $("#post-status option:selected").val();


				q += "aiodie-post-export=yes&postform="+cat+"&post-author="+post_author+"&post_str_date="+post_start_date+"&post_en_date="+post_end_date+"&post_sts="+post_status+"&";
				//q += "aiodie-post-export=yes&postform="+cat+"&post-author="+post_author+"&post_sts="+post_status+"&";

			}
			if ($('#page-content').is(':checked')) {

				var page_author = $("#page_author option:selected").val();
				var page_start_date = $("#page-start-date option:selected").val();
				var page_end_date = $("#page-end-date option:selected").val();
				var page_status = $("#page-status option:selected").val();

				q += "aiodie-page-export=yes&page-author="+page_author+"&page_str_date="+page_start_date+"&page_en_date="+page_end_date+"&page_sts="+page_status+"&";
				//q += "aiodie-page-export=yes&page-author="+page_author+"&page_sts="+page_status+"&";

			}
			if ($('#content-attachment').is(':checked')) {
				
				var attachment_start_date = $("#attachment-start-date option:selected").val();
				var attachment_end_date = $("#attachment-end-date option:selected").val();		
				
				q += "aiodie-attachment-export=yes&attach-start-date="+attachment_start_date+"&attach-end-date="+attachment_end_date+"&";	

			}
			$( '.aiodie-others-export' ).each(function() {
				if ($(this).is(':checked')) {
					var posttype = $( this ).val();
					q += "aiodie-others-export=yes&post_type="+posttype+"&";
				//console.log(q);
			}
		});

			window.location.href = AIODIEConfig.customizerURL + '?'+ q+ 'aiodie-export=' + AIODIEConfig.exportNonce;

		},
		_import: function()
		{
			var win			= $( window ),
			body		= $( 'body' ),
			form		= $( '<form class="aiodie-form" method="POST" enctype="multipart/form-data"></form>' ),
			controls	= $( '.aiodie-import-controls' ),
			file		= $( 'input[name=aiodie-import-file]' ),
			message		= $( '.aiodie-uploading' );

			if ( '' == file.val() ) {
				alert( AIODIEl10n.emptyImport );
			}
			else {
				win.off( 'beforeunload' );
				body.append( form );
				form.append( controls );
				message.show();
				form.submit();
			}
		}
	};

	$( AIODIE.init );

})( jQuery );
