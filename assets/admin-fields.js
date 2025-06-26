import TomSelect from 'tom-select/popular';
import Sortable from 'sortablejs';

class SettingsPage {

	constructor( selector = '.rs-util-settings-form' ) {
		if ( typeof selector === 'object' ) {
			this.form = selector;
		} else {
			this.form = document.querySelector( selector );
		}
		this.form.settingsPage = this;
		this.init();
	}

	init() {

		const allToggledBy = this.form.querySelectorAll( '[data-toggled-by]' );
		allToggledBy.forEach( toggleTarget => {
			const toggle = thisForm().querySelector( `#${toggleTarget.dataset.toggledBy}` );
			if ( ! toggle ) {
				return;
			}
			showTogglableControls( toggle );
		} );
	
		const groupToggles = this.form.querySelectorAll( '[data-toggles-group]' );
		groupToggles.forEach( toggle => {
	
			let group;
	
			if ( toggle.dataset.togglesGroup === 'self' ) {
	
				group = toggle.closest( '.rs-util-settings-field-group' );
	
				const field = toggle.closest( '.rs-util-settings-field-row' );
				const mainLabel = group.children[0];
				const content = group.children[1];
				content.setAttribute( 'aria-hidden', toggle.checked ? 'false' : 'true' );
				content.setAttribute( 'data-toggled-by', toggle.id );
	
				mainLabel.append( field );
	
			} else {
	
				group = thisForm().querySelector( `#${toggle.dataset.togglesGroup}` );
				group.setAttribute( 'aria-hidden', toggle.checked ? 'false' : 'true' );
				group.setAttribute( 'data-toggled-by', toggle.id );
	
			}
	
			toggle.setAttribute( 'aria-expanded', toggle.checked ? 'true' : 'false' );
			toggle.setAttribute( 'aria-controls', `[data-toggled-by="${toggle.id}"]` );
			toggle.setAttribute( 'data-controls', `[data-toggled-by="${toggle.id}"]` );
	
		} );
	
		const orderedFields = this.form.querySelectorAll( '[data-ordered]' );
		orderedFields.forEach( ( field, index ) => {
			const hiddenOrder = field.querySelector( `input[data-ordering-field]` );
			if ( ! ( hiddenOrder?.value ?? false ) ) {
				return;
			}
			field.currentOrder = hiddenOrder.value || ( index + 1 );
			const label = field.querySelector( '.rs-util-settings-field-row__label' );
	
			const upButton = document.createElement( 'button' );
			upButton.type = 'button';
			upButton.textContent = 'Move field up';
			upButton.setAttribute( 'data-action', 'move-up' );
			upButton.classList.add( 'button', 'rs-util-settings-field-row-ordering', 'rs-util-settings-field-row-ordering--up' );
			label.append( upButton );
	
			const downButton = document.createElement( 'button' );
			downButton.type = 'button';
			downButton.textContent = 'Move field down';
			downButton.setAttribute( 'data-action', 'move-down' );
			downButton.classList.add( 'button', 'rs-util-settings-field-row-ordering', 'rs-util-settings-field-row-ordering--down' );
			label.append( downButton );
	
			field.parentElement.dataset.orderingGroup = field.dataset.ordered;
	
		} );
	
		const orderingGroups = this.form.querySelectorAll( '[data-ordering-group]' );
		orderingGroups.forEach( group => {
			[ ...group.children ]
				.sort( ( a, b ) => ( a.currentOrder - b.currentOrder ) )
				.forEach( node => group.appendChild( node ) );
		} );

		this.listen( 'change', '.rs-util-settings-form', this.enableSaveButton );
		this.listen( 'click', '[data-action="save-rs-util-page"]', this.save );
		this.listen( 'click', '.rs-util-settings-page__tab-toggle', this.clickTab );
		this.listen( 'change', '[data-controls]', this.showTogglableControls );
		this.listen( 'click', '.rs-util-settings-field-row-ordering', this.clickReorderButton );

		window.addEventListener( 'popstate', this.onPopState );
	}

	ready() {

		/*const settings = rsUtil_settingsPageENV();

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
		}*/

		document.querySelectorAll( '[data-use-tom-select]' ).forEach( makeSelectable );

	}

	save() {
		this.form.classList.add('is-state-saving');
		this.form.submit();
	}

	clickTab( toggle ) {
		history.pushState( null, null, `#${toggle.dataset.section}` );
		this.switchTab( toggle.dataset.section );
	}

	clickReorderButton( orderingButton ) {

		const field = orderingButton.closest( '.rs-util-settings-field-row' );
		const hiddenOrder = field.querySelector( `input[data-ordering-field]` );
		if ( ! hiddenOrder ) {
			return;
		}
		const currentOrder = hiddenOrder.value + '';

		const swapWith = orderingButton.dataset.action === 'move-up' 
			? field.previousElementSibling
			: field.nextElementSibling;

		if ( swapWith ) {
			const newOrder = swapWith.querySelector( `input[data-ordering-field]` );
			if ( ! newOrder ) {
				return;
			}

			hiddenOrder.value = newOrder.value + '';
			hiddenOrder.currentOrder = newOrder.value + '';
			newOrder.value = currentOrder;
			newOrder.currentOrder = currentOrder;

			if ( orderingButton.dataset.action === 'move-up' ) {
				field.insertAdjacentElement( 'afterend', swapWith );
			} else {
				field.insertAdjacentElement( 'beforebegin', swapWith );
			}

			enableSaveButton();

		}

	}

	switchTab( section ) {

		if ( section === '' ) {
			const firstSection = this.form.querySelector( `.rs-util-settings-section[data-section]` );
			section = firstSection.dataset.section;
		}

		const tabToggles = document.querySelectorAll( '.rs-util-settings-page__tab-toggle' );
		tabToggles.forEach( toggle => {
			toggle.setAttribute( 'aria-expanded', toggle.dataset.section === section ? 'true' : 'false' );
			const target = this.form.querySelector( `.rs-util-settings-section[data-section="${toggle.dataset.section}"]` );
			target.setAttribute( 'aria-hidden', toggle.dataset.section === section ? 'false' : 'true' );
		} );
	}

	showTogglableControls( toggle ) {
		const toggled = this.form.querySelectorAll( `[data-toggled-by="${toggle.id}"]` );
		toggled.forEach( fieldRow => {
			fieldRow.setAttribute( 'aria-hidden', ( toggle.checked ?? ( toggle.value || false ) ) ? 'false' : 'true' );
		} );
		if ( toggled.length ) {
			toggle.setAttribute( 'aria-expanded', ( toggle.checked ?? ( toggle.value || false ) ) ? 'true' : 'false' );
			toggle.setAttribute( 'aria-controls', `[data-toggled-by="${toggle.id}"]` );
			toggle.setAttribute( 'data-controls', `[data-toggled-by="${toggle.id}"]` );
		}
	}

	onPopState() {
		const url = new URL( window.location.href );
		const section = url.hash.replace( '#', '' );
		this.switchTab( section );
	}

	enableSaveButton() {
		document.querySelector( '.rs-util-settings-page .rs-util-settings-page__submit' ).disabled = false;
	}

	static listen( eventName, selector, callback ) {

		document.addEventListener( eventName, e => {
			const self = e.target.closest( selector );
			if ( ! self ) {
				return;
			}
			e.preventDefault();
			callback( self, e );
		} );

	}

	static makeSelectable( el, moreArgs = {} ) {

		const fireTomEvent = ( event, el, ...args ) => {
			document.dispatchEvent( new CustomEvent( `rs-util-select-${event}`, { detail: { el, args } } ) );
			return true;
		}

		const tomArgs = {
			onChange:        ( ...args ) => fireTomEvent( 'change', el, ...args ),
			onItemAdd:       ( ...args ) => fireTomEvent( 'item-add', el, ...args ),
			onItemRemove:    ( ...args ) => fireTomEvent( 'item-remove', el, ...args ),
			onOptionAdd:     ( ...args ) => fireTomEvent( 'option-add', el, ...args ),
			onOptionRemove:  ( ...args ) => fireTomEvent( 'option-remove', el, ...args ),
			onDropdownOpen:  ( ...args ) => fireTomEvent( 'open', el, ...args ),
			onDropdownClose: ( ...args ) => fireTomEvent( 'close', el, ...args ),
			onFocus:         ( ...args ) => fireTomEvent( 'focus', el, ...args ),
			onBlur:          ( ...args ) => fireTomEvent( 'blur', el, ...args ),
			onInitialize:    ( ...args ) => fireTomEvent( 'initialize', el, ...args ),
			...moreArgs,
		};

		if ( el.dataset.source ) {
			tomArgs.load = ( query, callback ) => {
				const url = el.dataset.source.replace( '@query', encodeURIComponent( query ) );
				fetch( url )
					.then( response => response.json() )
					.then( data => {
						const results = [];
						el.currentResults ??= {};
						data.forEach( item => {
							results.push( {
								id: item.id,
								title: item.title.rendered,
							} );
							el.currentResults[item.id] = item.title.rendered;
						} );
						callback( results );
					} )
					.catch( error => console.error( error ) );
			};
			tomArgs.valueField  = 'id';
			tomArgs.labelField  = 'title';
			tomArgs.searchField = 'title';
		}

		el.tom = new TomSelect( el, tomArgs );
	}

	static makeSortable( el, args = {} ) {
		Sortable.create( el, args );
	}

	static install() {
		new SettingsPage();
	}

}

export default SettingsPage;
