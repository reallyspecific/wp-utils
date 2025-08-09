import Sortable from "sortablejs";
import dialog from "./class.dialog";
import TomSelect from "tom-select";

export default class settingsPage {

	constructor(selector = '.rs-util-settings-form') {
		if (typeof selector === 'object') {
			this.form = selector;
		} else {
			this.form = document.querySelector(selector);
		}
		this.form.settingsPage = this;

		settingsPage.listen('change', '.rs-util-settings-form', this.enableSaveButton.bind(this) );
		settingsPage.listen('change', '[data-controls]', field => this.showTogglableControls(field) );
		settingsPage.listen('click', '[data-action="save-rs-util-page"]', () => this.save());
		settingsPage.listen('click', '.rs-util-settings-page__tab-toggle', button => this.clickTab(button));
		settingsPage.listen('click', '.rs-util-settings-field-row-ordering', button => this.clickReorderButton(button));
		settingsPage.listen('click', '.rs-util-settings-page [data-action]', button => this.handleAction(button));
		settingsPage.listen( 'submit', '.rs-util-settings-form', this.onSubmit.bind(this) );
		settingsPage.listen( 'click', '.rs-util-settings-form [data-on-click-event]', this.onClick.bind(this) );

		window.addEventListener('popstate', this.onPopState );

		this.init();
	}

	init() {

		const allToggledBy = this.form.querySelectorAll('[data-toggled-by]');
		allToggledBy.forEach(toggleTarget => {
			const toggle = this.form.querySelector(`#${toggleTarget.dataset.toggledBy}`);
			if (!toggle) {
				return;
			}
			this.showTogglableControls(toggle);
		});

		const groupToggles = this.form.querySelectorAll('[data-toggles-group]');
		groupToggles.forEach(toggle => {

			let group;

			if (toggle.dataset.togglesGroup === 'self') {

				group = toggle.closest('.rs-util-settings-field-group');

				const field = toggle.closest('.rs-util-settings-field-row');
				const mainLabel = group.children[0];
				const content = group.children[1];
				content.setAttribute('aria-hidden', toggle.checked ? 'false' : 'true');
				content.setAttribute('data-toggled-by', toggle.id);

				mainLabel.append(field);

			} else {

				group = this.form.querySelector(`#${toggle.dataset.togglesGroup}`);
				if ( group ) {
					group.setAttribute('aria-hidden', toggle.checked ? 'false' : 'true');
					group.setAttribute('data-toggled-by', toggle.id);
				}

			}

			toggle.setAttribute('aria-expanded', toggle.checked ? 'true' : 'false');
			toggle.setAttribute('aria-controls', group?.id ?? '');
			toggle.setAttribute('data-controls', `#${group?.id ?? ''}`);

		});

		const orderedFields = this.form.querySelectorAll('[data-ordered]');
		orderedFields.forEach((field, index) => {
			const hiddenOrder = field.querySelector(`input[data-ordering-field]`);
			if (!(hiddenOrder?.value ?? false)) {
				return;
			}
			field.currentOrder = hiddenOrder.value || (index + 1);
			const rowControls = document.createElement('div');
			rowControls.classList.add('rs-util-settings-field-row-order-controls');
			field.append(rowControls);

			const upButton = document.createElement('button');
			upButton.type = 'button';
			upButton.textContent = 'Move field up';
			upButton.setAttribute('data-action', 'move-up');
			upButton.classList.add('button', 'rs-util-settings-field-row-ordering', 'rs-util-settings-field-row-ordering--up');
			rowControls.append(upButton);

			const downButton = document.createElement('button');
			downButton.type = 'button';
			downButton.textContent = 'Move field down';
			downButton.setAttribute('data-action', 'move-down');
			downButton.classList.add('button', 'rs-util-settings-field-row-ordering', 'rs-util-settings-field-row-ordering--down');
			rowControls.append(downButton);

			const grabButton = document.createElement('button');
			grabButton.type = 'button';
			grabButton.textContent = 'Drag to reorder';
			grabButton.classList.add('button', 'rs-util-settings-field-row-ordering', 'rs-util-settings-field-row-ordering--grabber');
			rowControls.append(grabButton);

			field.parentElement.dataset.orderingGroup = field.dataset.ordered;

		});

		const orderingGroups = this.form.querySelectorAll('[data-ordered]');
		orderingGroups.forEach(group => {
			[...group.children]
				.sort((a, b) => (a.currentOrder - b.currentOrder))
				.forEach(node => group.appendChild(node));

			this.makeSortable( group, {
				handle: '.rs-util-settings-field-row-ordering--grabber',
				onSort: () => this.updateSortingIds(group),
			});
		});

		const sortableFields = this.form.querySelectorAll('[data-sortable="list"]');
		sortableFields.forEach( field => {
			const parent = field.parentElement;
			this.makeSortable( parent, {
				canCreate: true,
				onSort: () => this.updateListValues(parent),
			});
		});

		document.querySelectorAll('[data-use-tom-select]').forEach( this.makeSelectable.bind(this) );

		const thisPage = new URL( window.location.href );
		if ( thisPage.hash ) {
			settingsPage.switchTab( thisPage.hash.substring(1), this.form );
		}

		document.dispatchEvent( new CustomEvent( 'rs-util-settings-page-ready', { detail: this }) );
	}

	handleAction(button) {
		switch (button.dataset.action) {
			case 'remove-item':
				this.removeListItem( button.closest('.rs-util-settings-sortable-list-item') );
				this.enableSaveButton();
				break;
		}
	}

	save() {
		this.onSubmit();
	}



	addMessage( message, level = 'info' ) {
		const messageContainer = this.form.querySelector('.rs-util-settings-form-messages');
		const messageElement = document.createElement('p');
		messageElement.classList.add(`is-state-${level}`);
		messageElement.textContent = message;
		messageContainer.append(messageElement);
	}

	clearMessages() {
		const messageContainer = this.form.querySelector('.rs-util-settings-form-messages');
		messageContainer.innerHTML = '';
	}

	onClick( self ) {
		document.dispatchEvent( new CustomEvent( self.dataset.onClickEvent, { detail: self.closest('[data-on-click-event]') } ) );
	}

	onSubmit() {

		this.setState('saving');

		const formData = new FormData( this.form );
		const data = new URLSearchParams( formData );

		fetch( this.form.action, {
			method: 'POST',
			body: data,
		} )
		.then( response => response.text() )
		.then( response => {

			const currentTab = ( new URL(window.location.href) ).hash;

			const newDocument = document.createElement('html');
			newDocument.innerHTML = response;
			const newForm = newDocument.querySelector('form.rs-util-settings-form');
			this.form.replaceWith( newForm );
			this.form = newForm;
			this.init();

			this.setState('saved');
			this.addMessage("Settings saved successfully.", 'success');

			//if ( currentTab ) {
			//	settingsPage.switchTab( currentTab.substring(1), this.form );
			//}

		})
		.catch( error => {
			console.error( error );
			this.setState('error');
			this.openDialog( {
				title: 'Error on save',
				body: 'There was an error saving your settings. More details are in the developer console.',
			});
		})
		.finally( () => {
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		} );
	}

	setState( state = null ) {
		this.form.classList.entries().forEach( value => {
			if ( value[1].substring( 0, 8 ) === 'is-state' ) {
				this.form.classList.remove( value[1] );
			}
		} );
		if ( state ) {
			this.form.classList.add( `is-state-${state}` );
		}
	}

	clickTab(toggle) {
		history.pushState(null, null, `#${toggle.dataset.section}`);
		settingsPage.switchTab(toggle.dataset.section, this.form);
	}

	clickReorderButton(orderingButton) {

		const field = orderingButton.closest('.rs-util-settings-field-row');
		const hiddenOrder = field.querySelector(`input[data-ordering-field]`);
		if (!hiddenOrder) {
			return;
		}

		const swapWith = orderingButton.dataset.action === 'move-up'
			? field.previousElementSibling
			: field.nextElementSibling;

		if (swapWith) {
			if (orderingButton.dataset.action === 'move-up') {
				field.insertAdjacentElement('afterend', swapWith);
			} else {
				field.insertAdjacentElement('beforebegin', swapWith);
			}

			this.enableSaveButton();
			this.updateSortingIds(field.closest('[data-sortable="list"]'));

		}

	}

	updateSortingIds(group) {
		const fields = group.querySelectorAll('.rs-util-settings-field-row');
		fields.forEach((field, index) => {
			field.querySelector(`input[data-ordering-field]`).value = index + 1;
			field.currentOrder = index + 1;
		});
	}

	showTogglableControls(toggle) {
		const toggled = this.form.querySelectorAll(`[data-toggled-by="${toggle.id}"]`);
		toggled.forEach(fieldRow => {
			fieldRow.setAttribute('aria-hidden', (toggle.checked ?? (toggle.value || false)) ? 'false' : 'true');
		});
		if (toggled.length) {
			toggle.setAttribute('aria-expanded', (toggle.checked ?? (toggle.value || false)) ? 'true' : 'false');
			toggle.setAttribute('aria-controls', `[data-toggled-by="${toggle.id}"]`);
			toggle.setAttribute('data-controls', `[data-toggled-by="${toggle.id}"]`);
		}
	}

	onPopState() {
		const url = new URL(window.location.href);
		const section = url.hash.replace('#', '');
		settingsPage.switchTab(section, this.form);
	}

	enableSaveButton() {
		this.setState(null);
		document.querySelector('.rs-util-settings-page .rs-util-settings-page__submit').disabled = false;
	}

	static switchTab(section, form) {

		if (!section) {
			const firstSection = form.querySelector(`.rs-util-settings-section[data-section]`);
			section = firstSection.dataset.section;
		}

		const tabToggles = document.querySelectorAll('.rs-util-settings-page__tab-toggle');
		tabToggles.forEach(toggle => {
			toggle.setAttribute('aria-expanded', toggle.dataset.section === section ? 'true' : 'false');
			const target = form.querySelector(`.rs-util-settings-section[data-section="${toggle.dataset.section}"]`);
			target.setAttribute('aria-hidden', toggle.dataset.section === section ? 'false' : 'true');
		});
	}

	static listen(eventName, selector, callback) {

		document.addEventListener(eventName, e => {
			const self = e.target.closest(selector);
			if (!self) {
				return;
			}
			e.preventDefault();
			callback(self, e);
		});

	}

	makeSelectable(el, moreArgs = {}) {

		const fireTomEvent = (event, el, ...args) => {
			document.dispatchEvent(new CustomEvent(`rs-util-select-${event}`, {detail: {el, args}}));
			return true;
		}

		const tomArgs = {
			onChange: (...args) => fireTomEvent('change', el, ...args),
			onItemAdd: (...args) => fireTomEvent('item-add', el, ...args),
			onItemRemove: (...args) => fireTomEvent('item-remove', el, ...args),
			onOptionAdd: (...args) => fireTomEvent('option-add', el, ...args),
			onOptionRemove: (...args) => fireTomEvent('option-remove', el, ...args),
			onDropdownOpen: (...args) => fireTomEvent('open', el, ...args),
			onDropdownClose: (...args) => fireTomEvent('close', el, ...args),
			onFocus: (...args) => fireTomEvent('focus', el, ...args),
			onBlur: (...args) => fireTomEvent('blur', el, ...args),
			onInitialize: (...args) => fireTomEvent('initialize', el, ...args),
			...moreArgs,
		};

		if (el.dataset.source) {
			const source = JSON.parse(el.dataset.source);
			const valueField = source.value.split('.');
			const labelField = source.label.split('.');
			tomArgs.load = (query, callback) => {
				const url = source.url.replace('@query', encodeURIComponent(query));
				fetch(url)
					.then(response => response.json())
					.then(data => {
						const results = [];
						el.currentResults ??= {};
						data.forEach(item => {
							let value = item;
							valueField.forEach(field => {
								value = value[field];
							});
							let label = item;
							labelField.forEach(field => {
								label = label[field];
							});
							results.push({value, label});
							el.currentResults[value] = label;
						});
						callback(results);
					})
					.catch(error => console.error(error));
			};
			tomArgs.valueField = 'value';
			tomArgs.labelField = 'label';
			tomArgs.searchField = 'label';
		}

		if ( el.dataset.useTomSelect !== 'true' ) {
			try {
				const additionalArgs = JSON.parse(el.dataset.useTomSelect ?? el.dataset.tomSelectAgs ?? '{}');
				if ( additionalArgs ) {
					Object.keys( additionalArgs ).forEach( key => {
						tomArgs[key] = additionalArgs[key];
					} );
				}
			}
			catch (e) {}

		}

		console.log( el, tomArgs );

		el.tom = new TomSelect(el, tomArgs);

		if ( tomArgs.closeAfterSelect ?? false ) {
			el.tom.on( 'item_select', () => {
				el.tom.blur();
			} );
			el.tom.on( 'item_add', () => {
				el.tom.blur();
			} );
		}
	}

	makeSortable( el, args = {} ) {

		const list = el.querySelector('[data-sortable="list"]') || el;

		Sortable.create( list, {
			animation: 150,
			ghostClass: 'sortable-ghost',
			chosenClass: 'sortable-chosen',
			handle: '.rs-util-settings-draggable-handle',
			...args
		} );

		if ( args.canCreate ?? false ) {
			const fields = el.querySelectorAll('[data-sortable="item-fields"] input, [data-sortable="item-fields"] select');
			const addBtn = el.querySelector('[data-action="add-item"]');
			if ( fields && addBtn ) {
				addBtn.addEventListener('click', e => {
					const newValue = {};
					let passedValidation = true;
					fields.forEach( field => {
						if ( ! ( field.required ?? false ) ) {
							return;
						}
						if ( ! field.checkValidity() ) {
							field.classList.add('is-state-invalid');
							if ( ! field.tom ) {
								field.reportValidity();
							} else {
								field.tom.wrapper.classList.add('is-state-invalid');
							}
							passedValidation = false;
						} else {
							field.classList.remove('is-state-invalid');
							if ( field.tom ) {
								field.tom.wrapper.classList.remove('is-state-invalid');
							}
						}
					} );
					if ( ! passedValidation ) {
						return;
					}
					fields.forEach(field => {
						newValue[field.dataset.key] = ['checkbox','radio'].includes( field.type ) ? field.checked : field.value;
					});
					const label = this.makeListItemLabel( newValue, fields, list.dataset.label );
					this.addListItem( el, newValue, label );

					fields.forEach( field => {
						field.value = '';
						field.checked = false;
						field.classList.remove('is-state-invalid');
						if ( field.tom ) {
							field.tom.wrapper.classList.remove('is-state-invalid');
							field.tom.clear(true);
						}
					})
				});
			}
		}

	}

	makeListItemLabel( valueGroup, fields, pattern = '{value}' ) {
		let label = pattern + '';
		fields.forEach( field => {
			const value = valueGroup[field.dataset.key] ?? '';
			if ( field.tagName === 'SELECT' ) {
				const option = field.options[field.selectedIndex];
				const optionValue = field.options[field.selectedIndex].value;
				label = label.replace( `{${field.dataset.key}.label}`, option.innerHTML );
				label = label.replace( `{${field.dataset.key}.value}`, option.value );
				label = label.replace( `{${field.dataset.key}}`, optionValue );
			}
			label = label.replace( `{${field.dataset.key}}`, value );
		} );
		return label;
	}

	addListItem = (group, value, label) => {

		const values = (group.querySelector('input[type="hidden"]').value
			? JSON.parse(group.querySelector('input[type="hidden"]').value)
			: null) || [];

		values.push(value);
		group.querySelector('input[type="hidden"]').value = JSON.stringify(values);


		const newItem = document.createElement('div');
		newItem.classList.add('rs-util-settings-sortable-list-item');
		newItem.dataset.value = value;
		newItem.innerHTML = `
			<span class="rs-util-settings-draggable-handle"></span>
			<span>${label}</span>
			<button type="button" class="rs-util-settings-trash-btn" data-action="remove-item">Remove Item</button>
		`;
		newItem.dataset.value = JSON.stringify(value);

		const list = group.querySelector('.rs-util-settings-sortable-list');
		list.append(newItem);

		this.enableSaveButton();

	}

	removeListItem = (item) => {

		const group = item.closest('.rs-util-settings-field-value');

		item.remove();

		this.updateListValues(group);
		this.enableSaveButton();
	}

	updateListValues(group) {

		const hiddenValueField = group.querySelector('input[type="hidden"]');

		const list = group.querySelector('.rs-util-settings-sortable-list');
		const values = [];
		list.querySelectorAll('.rs-util-settings-sortable-list-item').forEach( (item, index) => {
			values.push( JSON.parse( item.dataset.value) );
			item.dataset.order = index;
		} );
		hiddenValueField.value = JSON.stringify(values);

		this.enableSaveButton();

	}

	openDialog( props ) {
		const modal = new dialog( props );
		modal.open();
		return;
	}

}