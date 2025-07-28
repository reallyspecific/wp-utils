import settingsPage from './class.settingsPage';

function load() {
	if ( document.querySelector( '.rs-util-settings-form' ) ) {
		window.rsUtil_SettingsPage = new settingsPage();
	}
}

if ( document.readyState === 'loading' ) {
	document.addEventListener( 'DOMContentLoaded', load );
} else {
	load();
}

