( async function() {

	const form = document.querySelector( '.rs-util-settings-form' );
	if ( ! form ) {
		return;
	}

	const onReady = async () => {

		const settings = rsUtil_settingsPageENV();

		if ( settings.svg_iconset && ! document.querySelector( '#rs-util-svg-iconset' ) ) {
			const svgIcons = document.createElement( 'div' );
			try {
				const svg = await fetch( settings.svg_iconset );
				const svgText = await svg.text();
				svgIcons.innerHTML = svgText;
			} catch ( e ) {
				console.error( e );
			}
			document.body.appendChild( svgIcons.firstElementChild );
		}

	}

	if ( typeof rsUtil_settingsPageENV === 'function' ) {
		onReady();
	} else {
		document.addEventListener( 'rsUtil_settingsPageENV|ready', onReady );
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
		const saveButton = e.target.closest( '[data-action="save-rs-util-page"]' );
		if ( ! saveButton ) {
			return;
		}
		e.preventDefault();
		form.classList.add('is-state-saving');
		form.submit();
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
			const section = form.querySelector( `.rs-util-settings-section[data-section="${toggle.dataset.section}"]` );
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

		showTogglableControls( toggle );
	} );

	const showTogglableControls = ( toggle ) => {
		const toggled = document.querySelectorAll( `.rs-util-settings-field-row[data-toggled-by="${toggle.id}"]` );
		toggled.forEach( fieldRow => {
			fieldRow.setAttribute( 'aria-hidden', toggle.checked ? 'false' : 'true' );
		} );
		if ( toggled.length ) {
			toggle.setAttribute( 'aria-expanded', toggle.checked ? 'true' : 'false' );
			toggle.setAttribute( 'aria-controls', `[data-toggled-by="${toggle.id}"]` );
		}
	}

	const allToggles = document.querySelectorAll( '.rs-util-settings-field--checkbox' );
	allToggles.forEach( toggle => {
		showTogglableControls( toggle );
	} );

} )();