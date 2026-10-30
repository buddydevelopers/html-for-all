( function () {
	var form = document.querySelector( '.hfa-panel' );
	if ( ! form ) {
		return;
	}

	var count = form.querySelector( '[data-hfa-count]' );
	var boxes = form.querySelectorAll( 'input[name="hfa_post_types[]"]' );
	if ( ! count || ! boxes.length ) {
		return;
	}

	var render = function () {
		var selected = 0;
		boxes.forEach( function ( box ) {
			if ( box.checked ) {
				selected += 1;
			}
		} );
		var template = selected === 1 ? count.getAttribute( 'data-one' ) : count.getAttribute( 'data-many' );
		if ( template ) {
			count.textContent = template.replace( '%s', String( selected ) );
		}
	};

	boxes.forEach( function ( box ) {
		box.addEventListener( 'change', render );
	} );
}() );
