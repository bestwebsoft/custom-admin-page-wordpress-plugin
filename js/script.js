( function( $ ) {
	$( document ).ready( function() {

		$( '#cstmdmnpg_page_form #titlediv' ).on( 'click', '.edit-slug', function() {
			var i, slug_value,
				$el, revert_e,
				c = 0,
				real_slug = $( '#post_name' ),
				revert_slug = real_slug.val(),
				permalink = $( '#sample-permalink' ),
				permalinkOrig = permalink.html(),
				permalinkInner = $( '#sample-permalink a' ).html(),
				buttons = $( '#edit-slug-buttons' ),
				buttonsOrig = buttons.html(),
				full = $( '#editable-post-name-full' ),
				page_id = $( 'input[name="cstmdmnpg_page_id"]' ).val();

			// Deal with Twemoji in the post-name
			full.find( 'img' ).replaceWith( function() { return this.alt; } );
			full = full.html();

			permalink.html( permalinkInner );
			$el = $( '#editable-post-name' );
			revert_e = $el.html();

			buttons.html( '<button type="button" class="save button button-small">' + cstmdmnpgScriptVars.ok + '</button> <button type="button" class="cancel button-link">' + cstmdmnpgScriptVars.cancel + '</button>' );
			
			buttons.children( '.save' ).click( function() {
				var new_slug = $el.children( 'input' ).val();

				if ( new_slug == $( '#editable-post-name-full' ).text() ) {
					buttons.children( '.cancel' ).click();
					return;
				}
				$.post( ajaxurl, {
					action: 'cstmdmnpg-sample-permalink',
					page_id: page_id,
					new_slug: new_slug,
					new_title: $( '#title' ).val(),
					parent_slug: $( 'select[name="cstmdmnpg_parent"] option:selected' ).val(),
					nonce: cstmdmnpgScriptVars.ajax_nonce
				}, function( data ) {
					var box = $( '#edit-slug-box' );
					box.html( data );
					if ( box.hasClass( 'hidden' ) ) {
						box.fadeIn( 'fast', function() {
							box.removeClass( 'hidden' );
						});
					}

					buttons.html( buttonsOrig );
					permalink.html( permalinkOrig );
					real_slug.val( new_slug );
					$( '.edit-slug' ).focus();
					wp.a11y.speak( cstmdmnpgScriptVars.permalinkSaved );
				});
			});

			buttons.children( '.cancel' ).click( function() {
				$( '#view-post-btn' ).show();
				$el.html( revert_e );
				buttons.html( buttonsOrig );
				permalink.html( permalinkOrig );
				real_slug.val( revert_slug );
				$( '.edit-slug' ).focus();
			});

			for ( i = 0; i < full.length; ++i ) {
				if ( '%' == full.charAt( i ) )
					c++;
			}

			slug_value = ( c > full.length / 4 ) ? '' : full;
			$el.html( '<input type="text" id="new-post-slug" value="' + slug_value + '" autocomplete="off" />' ).children( 'input' ).keydown( function( e ) {
				var key = e.which;
				// On enter, just save the new slug, don't save the post.
				if ( 13 === key ) {
					e.preventDefault();
					buttons.children( '.save' ).click();
				}
				if ( 27 === key ) {
					buttons.children( '.cancel' ).click();
				}
			} ).keyup( function() {
				real_slug.val( this.value );
			}).focus();
		});
		
		$( 'select[name=cstmdmnpg_parent]' ).change( function() {
			if ( '' == $( 'select[name="cstmdmnpg_parent"] option:selected' ).val() )
				$( '.cstmdmnpg_position, .cstmdmnpg_icon' ).show();
			else
				$( '.cstmdmnpg_position, .cstmdmnpg_icon' ).hide();
		}).trigger( 'change' );

		if ( $( '.cstmdmnpg-upload-image' ).length > 0 ) {

			/**
			 * include WordPress media uploader for images
			 */
			var file_frame,
				wp_media_post_id = wp.media.model.settings.post.id, /* Store the old id */
				set_to_post_id   = 0; /* Set this */
			$( '.cstmdmnpg-upload-image' ).on( 'click', function( event ) {
				var button       = $( this );
				var imageUrl  = $( this ).parent().find( 'input.cstmdmnpg-image-url' );

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
					multiple: false  /* Set to true to allow multiple files to be selected */
				} );

				/* When an image is selected, run a callback. */
				file_frame.on( 'select', function() {
					/* We set multiple to false so only get one image from the uploader */
					var attachment = file_frame.state().get( 'selection' ).first().toJSON();
					if ( ! attachment.mime.match( '^image/' ) ) {
						alert( cstmdmnpgScriptVars[ 'errorInsertImage' ] );
						return false;
					}

					/* Do something with attachment.id and/or attachment.url here */
					button.val( cstmdmnpgScriptVars['changeImageLabel'] );
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