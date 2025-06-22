( function() {

	const settings = rsUtil_getGlobalVar__settingsPage();

	const form = document.querySelector( '.rs-util-settings-form' );
	if ( ! form ) {
		return;
	}

	if ( settings.svg_iconset && ! document.querySelector( '#rs-util-svg-iconset' ) ) {
		const svgIcons = document.createElement( 'div' );
		svgIcons.innerHTML = settings.svg_iconset;
		document.body.appendChild( svgIcons.firstElementChild );
	}

	function enableSaveButton() {
		document.querySelector( '.rs-util-settings-page .rs-util-settings-page__submit' ).disabled = false;
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

	document.addEventListener( 'change', e => {
		const toggle = e.target.closest( '.rs-util-settings-field--checkbox' );
		if ( ! toggle ) {
			return;
		}
		e.preventDefault();

		const toggled = document.querySelectorAll( `.rs-util-settings-field-row[data-toggled-by="${toggle.id}"]` );
		toggled.forEach( fieldRow => {
			fieldRow.setAttribute( 'aria-hidden', toggle.checked ? 'false' : 'true' );
		} );
	} );

} )();