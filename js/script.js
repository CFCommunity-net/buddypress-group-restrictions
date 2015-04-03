jQuery( document ).ready( function( $ ) {

	$( '#group-restrictions' ).show();

	$( document ).on( 'click', 'input[value="hidden"]', function() {
		$( '#group-restrictions' ).hide();
	});

	$( document ).on( 'click', 'input[value="public"], input[value="private"]', function() {
		$( '#group-restrictions' ).show();
	});
});