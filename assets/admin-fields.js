( async function() {

	const TomSelect = require( 'tom-select/popular' );

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

		document.querySelectorAll( '[data-use-tom-select]' ).forEach( el => {
			el.tom = new TomSelect( el );
		} );

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

		history.pushState( null, null, `#${tabToggle.dataset.section}` );
		switchTab( tabToggle.dataset.section );
	} );

	const switchTab = ( section ) => {

		if ( section === '' ) {
			const firstSection = form.querySelector( `.rs-util-settings-section[data-section]` );
			section = firstSection.dataset.section;
		}

		const tabToggles = document.querySelectorAll( '.rs-util-settings-page__tab-toggle' );
		tabToggles.forEach( toggle => {
			toggle.setAttribute( 'aria-expanded', toggle.dataset.section === section ? 'true' : 'false' );
			const target = form.querySelector( `.rs-util-settings-section[data-section="${toggle.dataset.section}"]` );
			target.setAttribute( 'aria-hidden', toggle.dataset.section === section ? 'false' : 'true' );
		} );
	}

	document.addEventListener( 'change', e => {
		const toggle = e.target.closest( '[data-controls]' );
		if ( ! toggle ) {
			return;
		}
		e.preventDefault();

		showTogglableControls( toggle );
	} );

	const showTogglableControls = ( toggle ) => {
		const toggled = form.querySelectorAll( `[data-toggled-by="${toggle.id}"]` );
		toggled.forEach( fieldRow => {
			fieldRow.setAttribute( 'aria-hidden', ( toggle.checked ?? ( toggle.value || false ) ) ? 'false' : 'true' );
		} );
		if ( toggled.length ) {
			toggle.setAttribute( 'aria-expanded', ( toggle.checked ?? ( toggle.value || false ) ) ? 'true' : 'false' );
			toggle.setAttribute( 'aria-controls', `[data-toggled-by="${toggle.id}"]` );
			toggle.setAttribute( 'data-controls', `[data-toggled-by="${toggle.id}"]` );
		}
	}

	const allToggledBy = form.querySelectorAll( '[data-toggled-by]' );
	allToggledBy.forEach( toggleTarget => {
		const toggle = form.querySelector( `#${toggleTarget.dataset.toggledBy}` );
		if ( ! toggle ) {
			return;
		}
		showTogglableControls( toggle );
	} );

	const onPopState = () => {
		const url = new URL( window.location.href );
		const section = url.hash.replace( '#', '' );
		switchTab( section );
	}

	window.addEventListener( 'popstate', onPopState );

	onPopState();

} )();