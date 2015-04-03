/*!
 * BuddyPress Group Restrictions
 */

;
(function( $ ) {

	if ( typeof _cfbGRTypes === 'undefined' || ! $( '#group-restrictions' ).length ) {
		return;
	}

	var currentStatus      = $( 'input[name=group-status]:checked' );
	var buddypressInfo     = {};
	var currentRestriction = null;

	if ( $( 'input[name=_cfbgr_restriction_status]:checked' ).length )  {
		currentRestriction = $( 'input[name=_cfbgr_restriction_status]:checked' ).val();
	}

	resetUI = function() {
		$( 'input[name=_cfbgr_restriction_status]' ).each( function(){
			$( this ).prop( 'checked', false );
		} );

		$( 'input[name=group-status]' ).each( function() {
			if ( ! $( this ).parent().find( 'li' ).length ) {
				$( this ).parent().next().find( 'li' ).first().html( buddypressInfo[ $( this ).val() ] );
			} else {
				$( this ).parent().find( 'li' ).first().html( buddypressInfo[ $( this ).val() ] );
			}
		} );
	}

	moveRestrictions = function( radio )  {
		$( '#group-restrictions' ).show();

		if ( ! radio.parent().find( 'ul' ).length ) {
			radio.parent().next().after( $( '#group-restrictions' ) );
		} else {
			radio.parent().find( 'ul' ).first().after( $( '#group-restrictions' ) );
		}
	}

	// Populate default restriction info
	$( 'input[name=group-status]' ).each( function() {
		target = $( this ).parent().find( 'li' ).first();

		if ( ! target.length ) {
			target = $( this ).parent().next().find( 'li' ).first();
		}

		if ( 'undefined' !== target.html() ) {
			buddypressInfo[ $( this ).val() ] = target.html();
		}
		console.log( buddypressInfo );
	} );

	if ( 'hidden' === currentStatus.val() ) {
		$( '#group-restrictions' ).hide();
	} else {
		moveRestrictions( currentStatus );

		if ( null !== currentRestriction ) {
			if ( ! currentStatus.parent().find( 'li' ).length ) {
				currentStatus.parent().next().find( 'li' ).first().html(
					_cfbGRTypes[ currentRestriction ][ currentStatus.val() ]
				);
			} else {
				currentStatus.parent().find( 'li' ).first().html(
					_cfbGRTypes[ currentRestriction ][ currentStatus.val() ]
				);
			}
		}
	}

	$( 'input[name=group-status]' ).on( 'click', function( e ) {
		resetUI();

		if ( 'hidden' !== $( this ).val() ) {
			moveRestrictions( $( this ) );
		} else {
			$( '#group-restrictions' ).hide();
		}
	} );

	// Replace the BuddyPress privacy message
	$( 'input[name=_cfbgr_restriction_status]' ).on( 'click', function( e ) {
		target = $( 'input[name=group-status]:checked' ).parent().find( 'li' ).first();

		if ( ! target.length ) {
			target = $( 'input[name=group-status]:checked' ).parent().next().find( 'li' ).first();
		}

		if ( true === $( this ).prop( 'checked' ) ) {

			self = $( this );
			// Make sure any other checkbox are unchecked
			$( 'input[name=_cfbgr_restriction_status]' ).each( function(){
				if ( true === $( this ).prop( 'checked' ) && $( this ).val() !== self.val() ) {
					$( this ).prop( 'checked', false );
				}
			} );


			target.html(
				_cfbGRTypes[ $( this ).val() ][ $( 'input[name=group-status]:checked' ).val() ]
			);
		} else {
			target.html(
				buddypressInfo[ $( 'input[name=group-status]:checked' ).val() ]
			);
		}
	} );

} )( jQuery );
