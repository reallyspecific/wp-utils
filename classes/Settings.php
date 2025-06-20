<?php

namespace ReallySpecific\Utils;

use function ReallySpecific\Utils\Text\array_to_attr_string;
use function ReallySpecific\Utils\Text\parsedown_line;

class Settings {

	private array $settings = [];

	private ?string $hook = null;

	private array $sections = [];

	private ?string $slug = null;

	private bool $multisite = false;

	private ?int $post_id = null;

	private ?array $cache = null;

	/**
	 * Constructor for the class.
	 *
	 * @param Plugin $plugin The plugin object.
	 * @param string $menu_title The title of the menu.
	 * @param array $props Additional properties for the settings.
	 */
	public function __construct( array $props = [] ) {
		
		$props = wp_parse_args( $props, [
			'slug'        => null,
			'capability'  => 'manage_options',
			'option_name' => null,
			'post_id'     => null,
			'parent'      => false,
		] );

		$this->slug = sanitize_title( $props['slug'] );
		unset( $props['slug'] );

		$this->settings = $props;

		if ( ! isset( $props['option_name'] ) ) {
			$this->settings['option_name'] = $this->slug;
		}

		if ( is_multisite() && $props['capability'] === 'manage_network_options' ) {
			add_action( 'network_admin_menu', [ $this, 'install' ] );
			$this->multisite = true;
		} else {
			add_action( 'admin_menu', [ $this, 'install' ] );
		} // todo: need another option for Post IDs.

		//add_filter( 'rs_util_settings_sanitize_field_value', [ $this, 'sanitize_textarea_field' ], 9, 2 );
	}

	/**
	 * This is called during init to prevent i18n doing_it_wrong issues with translated strings on the labels.
	 * @param mixed $props
	 * @return void
	 */
	public function setup( $props ) {
		$settings = wp_parse_args( $props, [
			'menu_title' => ucwords( $this->slug ),
			'page_title' => ucwords( $this->slug ),
		] );
		$this->settings = [
			...$this->settings,
			...$settings,
		];
		if ( ! has_action( 'admin_enqueue_scripts', [ $this::class, 'enqueue_admin_scripts' ] ) ) {
			add_action( 'admin_enqueue_scripts', [ $this::class, 'enqueue_admin_scripts' ] );
		}
		if ( ! has_action( 'rs_util_settings_sanitize_field_value', [ $this::class, 'sanitize_textarea_field' ], 10, 2 ) ) {
			add_filter( 'rs_util_settings_sanitize_field_value', [ $this::class, 'sanitize_textarea_field' ], 10, 2 );
		}
	}

	public static function enqueue_admin_scripts() {
		wp_register_style( 'rs-util-admin-fields', plugins_url( 'assets/admin-fields.css', __DIR__ ) );
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
			$this->hook = add_submenu_page(
				$this->settings['parent'],
				$this->settings['page_title'],
				$this->settings['menu_title'],
				$this->settings['capability'],
				$this->slug,
				[ $this, 'render' ],
				$this->settings['position'] ?? null
			);
		}
		add_action( $this->hook, [ $this, 'save_form' ], -1 );
	}

	/**
	 * Adds a section to the sections array with the given ID and properties.
	 *
	 * @param string $id The ID of the section.
	 * @param array $props The properties of the section. Default is an empty array.
	 * @return void
	 */
	public function add_section( string $id = 'default', array $props = [] ) {
		if ( ! empty( $props['fields'] ) ) {
			$fields = $props['fields'];
			unset( $props['fields'] );
		}
		$this->sections[ $id ] = wp_parse_args( $props, [
			'title'       => null,
			'description' => null,
			'order'       => ( count( $this->sections ) + 1 ) * 10,
			'fields'      => [],
		] );
		if ( ! empty( $fields ) ) {
			foreach( $fields as $field ) {
				$this->add_field( $field, $id );
			}
		}
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
	public function add_field( array $props, ?string $section_id = null ) {
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
			'id'          => $section_id . '__' . sanitize_title( $props['name'] ),
			'default'     => null,
			'placeholder' => null,
			'description' => null,
		] );
	}

	public function render() {

		wp_enqueue_style( 'rs-util-admin-fields' );

		$current_values = $this->get();
		
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
					<h3 class="rs-util-settings-field-section__title"><?php echo $section['title']; ?></h3>
					<?php endif; ?>
					<?php if ( isset( $section['description'] ) ) : ?>
					<p class="rs-util-settings-field-section__description">
					<?php echo parsedown_line( $section['description'], 'description', 'rs-util-settings' ); ?>
					</p>
					<?php endif; ?>

					<?php if ( isset( $section['fields'] ) ) : ?>
					<?php do_action( $this->slug . '_rs_util_settings_render_section_beforestart', $section, $this ); ?>
					<?php do_action( 'rs_util_settings_render_section_beforestart', $section, $this ); ?>
					<table class="form-table">
						<?php foreach( $section['fields'] as $field ) : ?>
							<?php $field_name = $field['attrs']['name'] ?? $field['name'] ?? ''; ?>
							<?php do_action( $this->slug . '_rs_util_settings_render_field_row_beforestart', $field, $section, $this ); ?>
							<?php do_action( 'rs_util_settings_render_field_row_beforestart', $field, $section, $this ); ?>
							<?php $this->render_field_row( $field, $this->get( $field_name ) ?? null ); ?>
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
		<tr class="rs-util-settings-field-row">
			<th scope="row" class="rs-util-settings-field-row__label">
				<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo $label; ?></label>
			</th>
			<td class="rs-util-settings-field-value rs-util-settings-field-value--<?php echo $field['type']; ?>">
				<?php $this->render_field( $field, $value ); ?>
				<?php if ( ! empty( $description ) ) : ?>
				<p class="rs-util-settings-field__description">
					<?php echo parsedown_line( $description, 'description', 'rs-util-settings' ); ?>
				</p>
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

	private function parse_field_name( string $name, bool $multiple = false ) {

		$name_parts = explode( '.', $name );
		$new_name   = sanitize_title( array_shift( $name_parts ) );
		while ( count( $name_parts ) > 0 ) {
			$new_name .= '[' . sanitize_title( array_shift( $name_parts ) ) . ']';
		}
		if ( ! empty( $multiple ) ) {
			$new_name .= '[]';
		}
		return $new_name;
	}

	public function render_field( array $field, $value = null, $echo = true ) {

		$field_name = $field['name'];
		$tag = match( $field['type'] ) {
			'options'   => 'multicheck',
			'select'    => 'select',
			'textarea'  => 'textarea',
			default     => 'input',
		};
		$attrs = wp_parse_args( $field['attrs'] ?? [], [
			'id'       => $field['id'],
			'name'     => null,
			'required' => filter_var( $field['required'] ?? null, FILTER_VALIDATE_BOOLEAN ) ? 'required' : null,
			'class'    => $field['class'] ?? [],
			'multiple' => filter_var( $field['multiple'] ?? null, FILTER_VALIDATE_BOOLEAN ) ? 'multiple' : null,
		] );
		
		$attrs['name'] = $attrs['name'] ?? $this->parse_field_name( $field_name, ! empty( $attrs['multiple'] ) );

		$attrs['class']       = is_array( $attrs['class'] ) ? $attrs['class'] : [ $attrs['class'] ];
		$attrs['class'][]     = match( $field['type'] ) {
			'input'    => 'regular-text',
			'textarea' => 'large-text',
			default    => '',
		};

		$attrs['class'][] = 'rs-util-settings-field';
		$attrs['class'][] = 'rs-util-settings-field--' . $field['type'];
		$attrs['class'] = trim( implode( ' ', array_unique( $attrs['class'] ) ) );
		
		$value = $value ?? $field['default'] ?? ( empty( $attrs['multiple'] ) ? '' : [] );
		switch( $tag ) {
			case 'input':
				$attrs['size']        = $field['size'] ?? null;
				$attrs['type']        = $field['type'] ?? 'text';
				$attrs['placeholder'] = $field['placeholder'] ?? null;
				if ( $field['type'] === 'checkbox' ) {
					$checked = filter_var( $value ?? $field['default'] ?? null, FILTER_VALIDATE_BOOLEAN );
					$attrs['checked'] = $checked ? 'checked' : null;
					$value = null;
				} else {
					$attrs['value'] = $value ?? $field['default'] ?? null;
				}
				$render_template = '<input %1$s>';
				if ( isset( $field['value_label'] ) ) {
					$render_template .= '<label for="' . $attrs['id'] . '">' . $field['value_label'] . '</label>';
				}
				break;
			case 'textarea':
				$attrs['rows'] = $field['rows'] ?? null;
				$attrs['cols'] = $field['cols'] ?? null;
				$render_template = '<textarea %1$s>' . esc_html( $value ) . '</textarea>';
				break;
			case 'select':
				$render_template = [ $this, 'render_select' ];
				break;
			case 'multicheck':
				$render_template = [ $this, 'render_multicheck' ];
				break;
		}
		if ( is_callable( $render_template ) ) {
			$rendered = call_user_func( $render_template, $field, $value, $attrs );
		} else {
			$rendered = sprintf( $render_template ?? '', array_to_attr_string( $attrs ) );
		}
		$rendered = apply_filters( $this->slug . '_rs_util_settings_render_field_' . $field_name, $rendered, $field, $value, $this );
		$rendered = apply_filters( $this->slug . '_rs_util_settings_render_field', $rendered, $field, $value, $this );
		$rendered = apply_filters( 'rs_util_settings_render_field', $rendered, $field, $value, $this );
		if ( ! empty( $rendered ) && $echo ) {
			echo $rendered;
		}
		return $rendered ?? '';
	}

	public function render_select( array $field, $value, array $attrs ) {
		$buffer = '';

		if ( is_callable( $field['options'] ) ) {
			$field['options'] = call_user_func( $field['options'] );
		}
		
		$buffer .= '<select ' . array_to_attr_string( $attrs ) . '>';
		foreach( $field['options'] as $key => $option ) {
			if ( isset( $option['group'] ) ) {
				$buffer .= sprintf( '<optgroup label="%s">', esc_attr( $option['group'] ) );
				$suboptions = $option['options'] ?? [];
			} else {
				$suboptions = [ $key => $option ];
			}
			foreach( $suboptions as $subkey => $suboption ) {
				$subattrs = [];
				$label = '';
				if ( is_string( $suboption ) ) {
					$label = $suboption;
				} else {
					$label = $suboption['label'] ?? $subkey;
					if ( isset( $suboption['data'] ) ) {
						foreach( $suboption['data'] as $data_key => $data_value ) {
							$subattrs[ "data-{$data_key}" ] = $data_value;
						}
					}
				}
				if ( $subkey === $value || ( is_array( $value ) && in_array( $subkey, $value ) ) ) {
					$subattrs['selected'] = 'selected';
				}
				$buffer .= sprintf( '<option value="%s" %s>%s</option>',
					esc_attr( $subkey ),
					array_to_attr_string( $subattrs ),
					esc_html( $label )
				);
			}
			if ( isset( $option['group'] ) ) {
				$buffer .= '</optgroup>';
			}
		}
		$buffer .= '</select>';

		return $buffer;
	}

	public function render_multicheck( array $field, $value, array $attrs ) {
		
		$buffer = '';
		$buffer .= '<div class="rs-util-settings-field rs-util-settings-field--multicheck">';

		if ( is_callable( $field['options'] ) ) {
			$field['options'] = call_user_func( $field['options'] );
		}

		foreach( $field['options'] as $key => $option ) {
			if ( isset( $option['group'] ) ) {
				$buffer .= sprintf( '<fieldset class="rs-util-settings-field__group"><legend><strong>%s</strong></legend>', esc_attr( $option['group'] ) );
				$suboptions = $option['options'] ?? [];
			} else {
				$suboptions = [ $key => $option ];
			}
			foreach( $suboptions as $subkey => $suboption ) {
				$subattrs = [ 'class' => '' ];
				$label = '';
				if ( is_string( $suboption ) ) {
					$label = $suboption;
				} else {
					$label = $suboption['label'] ?? $subkey;
					if ( isset( $suboption['data'] ) ) {
						foreach( $suboption['data'] as $data_key => $data_value ) {
							$subattrs[ "data-{$data_key}" ] = $data_value;
						}
					}
					if ( isset( $suboption['classes'] ) ) {
						$subattrs['class'] = implode( ' ', $suboption['classes'] );
					}
				}
				$class = trim( $attrs['class'] . ' ' . $subattrs['class'] );
				$class = str_replace( 'rs-util-settings-field--multicheck', 'rs-util-settings-field--input', $class );
				$subattrs['class'] = $class;
				$subattrs['name'] = empty( $attrs['multiple'] ) ? $attrs['name'] : str_replace( '[]', "[{$subkey}]", $attrs['name'] );
				
				if ( $subkey === $value || ( is_array( $value ) && in_array( $subkey, $value ) ) ) {
					$subattrs['checked'] = 'checked';
				}
				$buffer .= sprintf( '<p class="rs-util-settings-field__option"><input type="%1$s" value="%2$s" %3$s><label for="%2$s">%4$s</label></p>',
					empty( $attrs['multiple'] ) ? 'radio' : 'checkbox',
					esc_attr( $subkey ),
					array_to_attr_string( $subattrs ),
					esc_html( $label )
				);
			}
			if ( isset( $option['group'] ) ) {
				$buffer .= '</fieldset>';
			}
		}

		$buffer .= '</div>';

		return $buffer;
	}

	private function get_request_value( string $key, array $request ) {
		$keys = explode( '.', $key );
		foreach( $keys as $key ) {
			if ( ! isset( $request[ $key ] ) ) {
				return null;
			}
			$request = $request[ $key ];
		}
		return $request;
	}

	public function save_form()
    {
        if ( ! current_user_can( $this->settings['capability'] ) ) {
			return;
		}

		if ( ( $_GET['page'] ?? '' ) !== $this->slug ){
			return;
		}

		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], $this->slug ) ) {
			return;
		}

		$this->cache = [];

		foreach( $this->sections as $section ) {
			foreach( $section['fields'] as $field ) {
				$field_name  = $field['attrs']['name'] ?? $field['name'];
				$field_value = $this->get_request_value( $field_name, $_POST );

				$sanitization_function = apply_filters( 'rs_util_settings_sanitize_field_value', $field['sanitization_callback'] ?? null, $field );
				if ( empty( $sanitization_function ) ) {
					$sanitization_function = 'sanitize_text_field';
				}
				if ( is_array( $field_value ) ) {
					$new_value = [];
					foreach( $field_value as $value ) {
						$new_value[] = call_user_func( $sanitization_function, $value, $field );
					}
				} else {
					$new_value = call_user_func( $sanitization_function, $field_value, $field );
				}

				$this->update( $field_name, $new_value, false );

			}
		}

		$this->save();
	}

	public function get( ?string $key = null, $nocache = false ) {
		if ( is_null( $this->cache ) || $nocache ) {
			$this->load();
		}
		if ( ! empty( $key ) ) {
			$parts = explode( '.', $key );
			$value = &$this->cache;
			while( ! empty( $parts ) ) {
				$index = array_shift( $parts );
				if ( ! isset( $value[ $index ] ) ) {
					return null;
				}
				$value = &$value[ $index ] ?? null;
			}
			return $value;
		}
		return $this->cache;
	}

	public function __get( $key ) {
		return $this->get( $key );
	}

	private function load() {
		if ( $this->multisite ) {
			$this->cache = get_site_option( $this->settings['option_name'], [] ) ?: [];
		} else {
			$this->cache = get_option( $this->settings['option_name'], [] ) ?: [];
		}
	}

	public function update( $key, $value, $save = true ) {

		$settings = $this->get();

		$name = explode( '.', $key );

		$setting = &$settings;
		while ( count( $name ) > 1 ) {
			$index = array_shift( $name );
			if ( ! isset( $setting[ $index ] ) ) {
				$setting[ $index ] = [];
			}
			$setting = &$setting[ $index ];
		}
		$setting[ current( $name ) ] = $value;

		$this->cache = $settings;

		if ( ! $save ) {
			return $settings;
		}

		$this->save();

	}

	public function save() {
		if ( $this->post_id ) {
			update_post_meta( $this->post_id, $this->settings['option_name'], $this->cache );
		} elseif ( $this->multisite ) {
			update_site_option( $this->settings['option_name'], $this->cache );
		} else {
			update_option( $this->settings['option_name'], $this->cache );
		}
	}

	public function add_action( $hook, $callback, $priority = 10, $args = 0 ) {
		add_action( $this->slug . '_' . $hook, $callback, $priority, $args );
	}

	public function add_filter( $hook, $callback, $priority = 10, $args = 1 ) {
		add_filter( $this->slug . '_' . $hook, $callback, $priority, $args );
	}

	public static function sanitize_textarea_field( $callback_function, $field = []) {

		if ( ! empty( $callback_function ) ) {
			return $callback_function;
		}

		if ( ( $field['type'] ?? null ) === 'textarea' ) {
			return 'sanitize_textarea_field';
		}

		return $callback_function;
	}

}
