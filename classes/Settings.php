<?php

namespace ReallySpecific\WP_Util;

use ReallySpecific\WP_Util as Util;

class Settings {

	private $settings = null;

	private $hook = null;

	private $sections = [];

	private $slug = null;

	/**
	 * Constructor for the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 * @param string $menu_title The title of the menu.
	 * @param array $props Additional properties for the settings.
	 */
	public function __construct( Plugin $plugin, string $menu_title, array $props = [] ) {
		$this->settings = wp_parse_args( $props, [
			'parent'     => false,
			'slug'       => sanitize_title( $menu_title ) . '-settings',
			'capability' => 'manage_options',
			'page_title' => $menu_title . ' ' . __( 'Settings', $plugin->i18n_domain ),
			'form_title' => $menu_title . ' ' . __( 'Settings', $plugin->i18n_domain ),
			'menu_title' => $menu_title,
		] );
		$this->slug = $this->settings['slug'];
		if ( ! isset( $this->settings['option_name'] ) ) {
			$this->settings['option_name'] = $this->slug;
		}
		add_action( 'admin_menu', [ $this, 'install' ] );
	}

	/**
	 * Installs the menu page or submenu page based on the settings.
	 *
	 * @return void
	 */
	public function install() {
		if ( $this->settings['parent'] === false ) {
			$this->hook = add_menu_page(
				$this->settings['page_title'],
				$this->settings['menu_title'],
				$this->settings['capability'],
				$this->slug,
				[ $this, 'render' ],
				$this->settings['icon_url'] ?? null,
				$this->settings['position'] ?? null
			);
		} else {
			add_submenu_page(
				$this->settings['parent'],
				$this->settings['page_title'],
				$this->settings['menu_title'],
				$this->settings['capability'],
				$this->slug,
				[ $this, 'render' ],
				$this->settings['position'] ?? null
			);
		}
	}

	/**
	 * Adds a section to the sections array with the given ID and properties.
	 *
	 * @param string $id The ID of the section.
	 * @param array $props The properties of the section. Default is an empty array.
	 * @return void
	 */
	public function add_section( string $id, array $props = [] ) {
		$this->sections[ $id ] = wp_parse_args( $props, [
			'title'       => null,
			'description' => null,
			'order'       => ( count( $this->sections ) + 1 ) * 10,
			'fields'      => [],
		] );
	}

	/**
	 * Sets the title for a section in the settings object.
	 *
	 * @param string $id The ID of the section.
	 * @param string $title The title to set for the section.
	 * @return void
	 */
	public function set_section_title( string $id, string $title ) {
		if ( ! isset( $this->sections[ $id ] ) ) {
			$this->add_section( $id );
		}
		$this->sections[ $id ]['title'] = $title;
	}

	/**
	 * Sets the description for a section in the settings object.
	 *
	 * @param string $id The ID of the section.
	 * @param string $title The description to set for the section.
	 * @return void
	 */
	public function set_section_description( string $id, string $title ) {
		if ( ! isset( $this->sections[ $id ] ) ) {
			$this->add_section( $id );
		}
		$this->sections[ $id ]['description'] = $title;
	}

	/**
	 * Adds a field to a section in the settings.
	 *
	 * @param array $props The properties of the field.
	 * @param string|null $section_id The ID of the section to add the field to. If not provided, the field will be added to the default section.
	 * @throws \Exception If the field does not have a name.
	 * @return void
	 */
	public function add_field( array $props, string $section_id = null ) {
		if ( $section_id === null ) {
			$section_id = 'default';
			if ( ! isset( $this->sections['default'] ) ) {
				$this->add_section( 'default', [ 'order' => 0 ] );
			}
		}
		if ( ! isset( $this->sections[ $section_id ] ) ) {
			$this->add_section( $section_id );
		}
		if ( ! isset( $props['name'] ) ) {
			throw new \Exception( 'Cannot attach fields without a name.' );
		}
		$this->sections[ $section_id ]['fields'][] = wp_parse_args( $props, [
			'type'        => 'text',
			'order'       => ( count( $this->sections[ $section_id ]['fields'] ) + 1 ) * 10,
			'label'       => $props['name'],
			'id'          => $section_id . '__' . $props['name'],
			'default'     => null,
			'placeholder' => null,
			'description' => null,
		] );
	}

	public function render() {

		$current_values = get_option( $this->slug, [] );
		
		?>
		<div class="wrap">
			<h2><?php echo $this->settings['form_title']; ?></h2>
			<?php do_action( $this->slug . '_rs_util_settings_render_form_beforestart', $this ); ?>
			<?php do_action( 'rs_util_settings_render_form_beforestart', $this ); ?>
			<form method="post" action="<?php echo $this->settings['form_url'] ?? $_SERVER['REQUEST_URI']; ?>">
				<?php do_action( $this->slug . '_rs_util_settings_render_form_afterstart', $this ); ?>
				<?php do_action( 'rs_util_settings_render_form_afterstart', $this ); ?>
				<?php wp_nonce_field( $this->slug ); ?>
				<?php foreach( $this->sections as $section ) : ?>
					
					<?php if ( isset( $section['title'] ) ) : ?>
					<h3><?php echo $section['title']; ?></h3>
					<?php endif; ?>
					<?php if ( isset( $section['description'] ) ) : ?>
					<p class="description"><?php echo $section['description']; ?></p>
					<?php endif; ?>

					<?php if ( isset( $section['fields'] ) ) : ?>
					<?php do_action( $this->slug . '_rs_util_settings_render_section_beforestart', $section, $this ); ?>
					<?php do_action( 'rs_util_settings_render_section_beforestart', $section, $this ); ?>
					<table class="form-table">
						<?php foreach( $section['fields'] as $field ) : ?>
							<?php do_action( $this->slug . '_rs_util_settings_render_field_row_beforestart', $field, $section, $this ); ?>
							<?php do_action( 'rs_util_settings_render_fieldrow_beforestart', $field, $section, $this ); ?>
							<?php $this->render_field_row( $field, $current_values[ $field['name'] ] ?? null ); ?>
							<?php do_action( 'rs_util_settings_render_field_row_afterend', $field, $section, $this ); ?>
							<?php do_action( $this->slug . '_rs_util_settings_render_field_row_afterend', $field, $section, $this ); ?>
						<?php endforeach; ?>
					</table>
					<?php do_action( $this->slug . '_rs_util_settings_render_section_afterend', $section, $this ); ?>
					<?php do_action( 'rs_util_settings_render_section_afterend', $section, $this ); ?>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php submit_button(); ?>
				<?php do_action( 'rs_util_settings_render_form_beforeend', $this ); ?>
				<?php do_action( $this->slug . '_rs_util_settings_render_form_beforeend', $this ); ?>
			</form>
			<?php do_action( 'rs_util_settings_render_form_afterend', $this ); ?>
			<?php do_action( $this->slug . '_rs_util_settings_render_form_afterend', $this ); ?>
		</div>
		<?php

	}

	public function render_field_row( array $field, $value = null, $echo = true ) {

		$label = apply_filters( $this->slug . '_rs_util_settings_render_field_row_label', $field['label'] ?? '', $field, $value, $this );
		$label = apply_filters( 'rs_util_settings_render_field_row_label', $label, $field, $value, $this );

		$description = apply_filters( $this->slug . '_rs_util_settings_render_field_row_description', $field['description'] ?? null, $field, $value, $this );
		$description = apply_filters( 'rs_util_settings_render_field_row_description', $description, $field, $value, $this );

		ob_start();
		?>
		<tr>
			<th scope="row">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo $label; ?></label>
			</th>
			<td>
				<?php $this->render_field( $field, $value ); ?>
				<?php if ( ! empty( $description ) ) : ?>
				<label for="<?php echo esc_attr( $field['id'] ); ?>" class="description">
					<?php echo $description; ?>
				</label>
				<?php endif; ?>
			</td>
		</tr>
		<?php
		
		$rendered = ob_get_clean();
		$rendered = apply_filters( $this->slug . '_rs_util_settings_render_field_row', $rendered, $field, $value, $this );
		$rendered = apply_filters( 'rs_util_settings_render_field_row', $rendered, $field, $value, $this );
		
		if ( $echo ) {
			echo $rendered;
		}
		return $rendered;

	}

	public function render_field( array $field, $value = null, $echo = true ) {
		$tag = match( $field['type'] ) {
			'options' => 'select',
			'select'  => 'select',
			default   => 'input',
		};
		$attrs = wp_parse_args( $field['attrs'] ?? [], [
			'id'       => $field['id'],
			'name'     => $field['name'],
			'required' => filter_var( $field['required'], FILTER_VALIDATE_BOOLEAN ) ? 'required' : null,
			'class'    => [],
		] );
		$attrs['class']       = is_array( $attrs['class'] ) ? $attrs['class'] : [ $attrs['class'] ];
		$attrs['class'][]     = match( $field['type'] ) {
			'text'  => 'regular-text',
		};
		$attrs['class'] = trim( implode( ' ', array_unique( $attrs['class'] ) ) );
		switch( $tag ) {
			case 'input':
				$attrs['type']        = $field['type'] ?? 'text';
				$attrs['placeholder'] = $field['placeholder'] ?? null;
				if ( $field['type'] === 'checkbox' || $field['type'] === 'radio' ) {
					$checked = filter_var( $value ?? $field['default'] ?? null, FILTER_VALIDATE_BOOLEAN );
					$attrs['checked'] = $checked ? 'checked' : '';
					$value = null;
				}
				if ( $field['type'] !== 'checked' ) {
					$attrs['value'] = $value ?? $field['default'] ?? null;
				}
				$render_template = '<input %1$s>';
				break;
		}
		$rendered = sprintf( $render_template ?? '', Util\array_to_attr_string( $attrs ) );
		$rendered = apply_filters( $this->slug . '_rs_util_settings_render_field_' . $field['name'], $rendered, $field, $value, $this );
		$rendered = apply_filters( $this->slug . '_rs_util_settings_render_field', $rendered, $field, $value, $this );
		$rendered = apply_filters( 'rs_util_settings_render_field', $rendered, $field, $value, $this );
		if ( ! empty( $rendered ) && $echo ) {
			echo $rendered;
		}
		return $rendered ?? '';
	}

	public function save() {

		if ( ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}

		if ( ( $_GET['page'] ?? '' ) !== $this->slug ){
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			return;
		}

		$new_setting_values = [];

		foreach( $this->sections as $section ) {
			foreach( $section['fields'] as $field ) {
				if ( isset( $_POST[ $field['name'] ] ) ) {
					$new_setting_values[ $field['name'] ] = sanitize_text_field( $_POST[ $field['name'] ] );
				}
			}
		}

		update_option( $this->settings['option_name'], $new_setting_values, false );

	}

	public function get( ?string $key = null ) {
		$options = get_option( $this->settings['option_name'], [] );
		return $key ? $options[ $key ] : $options;
	}

	public function __get( $key ) {
		return $this->get( $key );
	}

	public function add_action( $hook, $callback, $priority = 10, $args = 0 ) {
		add_action( $this->slug . '_' . $hook, $callback, $priority, $args );
	}

	public function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		add_filter( $this->slug . '_' . $hook, $callback, $priority, $args );
	}

}
