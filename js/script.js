( function( $ ) {
	$( document ).ready( function() {

		$( '.et-bfb-optin-cta' ).remove();

		if ( $( 'select[name="cstmdmnpg_parent"]' ).length > 0 ) {
			$( 'select[name="cstmdmnpg_parent"]' ).change( function() {
				if ( '' == $( this ).children( 'option:selected' ).val() ) {
					$( '#cstmdmnpg_icon_to_page' ).show();
				} else {
					$( '#cstmdmnpg_icon_to_page' ).hide();
				}
			} );
		}

		$( 'input[name="cstmdmnpg_icon_image"]' ).on('change', function() {
			var icon = $( this ).filter( ':checked' ).val();

			switch( icon ) {
				case 'none':
					$( '.cstmdmnpg_to_image_input, .cstmdmnpg_to_svg_input, .cstmdmnpg_to_dashicon_input' ).hide();
					break;
				case 'svg':
					$( '.cstmdmnpg_to_svg_input' ).show();
					$( '.cstmdmnpg_to_dashicon_input, .cstmdmnpg_to_image_input' ).hide();
					break;
				case 'image':
					$( '.cstmdmnpg_to_svg_input, .cstmdmnpg_to_dashicon_input' ).hide();
					$( '.cstmdmnpg_to_image_input' ).show();
					break;
				case 'dashicons':
					$( '.cstmdmnpg_to_image_input, .cstmdmnpg_to_svg_input' ).hide();
					$( '.cstmdmnpg_to_dashicon_input' ).show();
					break;
			}
		}).trigger('change');

		if ( $( '.cstmdmnpg-upload-image' ).length > 0 ) {

			/**
			 * include WordPress media uploader for images
			 */
			var file_frame,
				wp_media_post_id = wp.media.model.settings.post.id, /* Store the old id */
				set_to_post_id   = 0; /* Set this */
			$( '.cstmdmnpg-upload-image' ).on( 'click', function( event ) {
				var buttons= $( this );
				var imageUrl = $( this ).parent().find( 'input.cstmdmnpg-image-url' );

				event.preventDefault();

				/* If the media frame already exists, reopen it. */
				if ( file_frame ) {
					/* Set the post ID to what we want */
					file_frame.uploader.uploader.param( 'post_id', set_to_post_id );
					/* Open frame */
					file_frame.open();
					return;
				} else {
					/* Set the wp.media post id so the uploader grabs the ID we want when initialised */
					wp.media.model.settings.post.id = set_to_post_id;
				}

				/* Create the media frame. */
				file_frame = wp.media.frames.file_frame = wp.media( {
					title:    $( this ).data( 'uploader_title' ),
					library:  {
						type: 'image'
					},
					button:   {
						text: $( this ).data( 'uploader_button_text' )
					},
					multiple: false /* Set to true to allow multiple files to be selected */
				} );

				/* When an image is selected, run a callback. */
				file_frame.on( 'select', function() {
					/* We set multiple to false so only get one image from the uploader */
					var attachment = file_frame.state().get( 'selection' ).first().toJSON();

					/* Do something with attachment.id and/or attachment.url here */
					buttons.val( cstmdmnpgScriptVars['changeImageLabel'] );
					imageUrl.val( attachment.url ).trigger( 'change' );

					/* Restore the main post ID */
					wp.media.model.settings.post.id = wp_media_post_id;
				} );

				/* Finally, open the modal */
				file_frame.open();
			} );
		}
	} );
} )( jQuery );