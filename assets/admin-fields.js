( function() {

	const form = document.querySelector( '.rs-util-settings-form' );

	function enableSaveButton() {
		form.querySelector( '.rs-util-settings-page__submit' ).disabled = false;
	}

	document.addEventListener( 'change', e => {
		if ( e.target.closest( '.rs-util-settings-form' ) ) {
			enableSaveButton();
		}
	} );

	document.addEventListener( 'click', e => {
		const tabToggle = e.target.closest( '.rs-util-settings-page__tab-toggle' );
		if ( ! tabToggle ) {
			return;
		}
		e.preventDefault();

		const tabToggles = document.querySelectorAll( '.rs-util-settings-page__tab-toggle' );
		tabToggles.forEach( toggle => {
			toggle.setAttribute( 'aria-expanded', toggle === tabToggle ? 'true' : 'false' );
			const section = form.querySelector( `.rs-util-settings-field-section[data-section="${toggle.dataset.section}"]` );
			section.setAttribute( 'aria-hidden', toggle === tabToggle ? 'false' : 'true' );
			if ( toggle === tabToggle ) {
				section.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'start' });
			}
		} );
	} );

} )();