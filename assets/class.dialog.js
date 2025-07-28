
export default class dialog {
	constructor(props) {
		this.props = {
			closeOnClickOutside: true,
			root: document.body,
			...props
		};
		this.dialog = null;
		this.addListener( 'close-dialog', this.close );
	}

	clickListener( e ) {
		if ( ! e.target.closest('.rs-util-settings-dialog__content') && this.props.closeOnClickOutside ) {
			this.close();
		}
		const button = e.target.closest('button');
		if ( button && button.dataset.action ) {
			document.dispatchEvent( new CustomEvent('dialog-action.' + button.dataset.action, { detail: { dialog: this.dialog, button } }) );
		}
	}

	addListener( eventName, callback ) {
		document.addEventListener( 'dialog-action.' + eventName, callback );
	}

	close( { detail } ) {
		const { dialog } = detail || {};
		document.body.classList.remove('rs-util-settings-dialog-open');
		if ( dialog ) {
			dialog.remove();
		}
	}

	open() {
		document.body.classList.add('rs-util-settings-dialog-open');
		this.render();
	}

	render( props = {} ) {
		props = { ...this.props, ...props };

		const dialog = document.createElement('div');
		dialog.classList.add('rs-util-settings-dialog');
		dialog.innerHTML = `
			<div class="rs-util-settings-dialog__content">
				<div class="rs-util-settings-dialog__header">
					<h2>${props.title}</h2>
					<button type="button" class="rs-util-settings-dialog__close" data-action="close-dialog">Close</button>
				</div>
				<div class="rs-util-settings-dialog__body">
					${props.content}
					<div class="rs-util-settings-dialog__actions">
						${
							props.actions.map( action => {
								const el = document.createElement('button');
								el.type = 'button';
								el.classList.add('button','rs-util-settings-dialog__action');
								if ( action.primary ) {
									el.classList.add('button-primary');
								}
								if ( action.classes ) {
									el.classList.add(...action.classes);
								}
								if ( action.action ) {
									el.dataset.action = action.action;
								}
								el.innerHTML = action.label;
								return el.outerHTML;
							} ).join('') 
						}
					</div>
				</div>
			</div>
		`;

		this.dialog = dialog;
		this.props.root.appendChild(dialog);

		dialog.addEventListener('click', this.clickListener.bind(this) );
		return dialog;
	}
}


