<?php

namespace ReallySpecific\SamplePlugin\Dependencies\RS_Utils;

use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Settings;
use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Service_Host;
use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\Updatable;
use ReallySpecific\SamplePlugin\Dependencies\RS_Utils\MultiArray;
use Exception;
abstract class Plugin
{
    use Service_Host;
    protected $root_path = null;
    protected $root_file = null;
    protected $services = [];
    protected $i18n_domain = null;
    protected $i18n_path = null;
    protected $name = null;
    protected $slug = null;
    protected $settings = [];
    protected $data = [];
    protected $updater = null;
    protected $assets = ['stylesheets' => [], 'scripts' => []];
    public MultiArray $env;
    /**
     * Creates a new instance of the plugin.
     * @return Plugin
     */
    public static function new(array $props = []): Plugin
    {
        return new static($props);
    }
    /**
     * Not necessary to be implemented, executed at the end of the constructor method.
     *
     * @return void
     */
    public function setup(): void
    {
    }
    /**
     * Plugin constructor.
     *
     * @param array $props
     * @throws \Exception
     */
    function __construct(array $props = [])
    {
        if (empty($props['name'])) {
            throw new \Exception('Plugin was constructed without a `name` property.');
        }
        if (empty($props['file'])) {
            throw new Exception('Plugin was constructed without a `file` property.');
        }
        $this->root_file = $props['file'];
        $this->root_path = trailingslashit(dirname($this->root_file));
        $this->i18n_domain = $props['i18n_domain'] ?? null;
        $this->i18n_path = $props['i18n_path'] ?? $this->get_root_path() . 'languages';
        $this->name = $props['name'];
        $this->slug = $props['slug'] ?? sanitize_title(basename($this->root_path));
        $this->attach_assets($props['stylesheets'] ?? [], 'stylesheet');
        $this->attach_assets($props['scripts'] ?? [], 'script');
        add_action('init', [$this, 'get_wp_data']);
        add_action('init', [$this, 'setup_updater']);
        add_action('init', [$this, 'install_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'install_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'install_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'install_editor_assets']);
        add_action('enqueue_block_assets', [$this, 'install_fse_styles']);
        if (did_action('plugins_loaded')) {
            $this->setup();
        } else {
            add_action('plugins_loaded', [$this, 'setup']);
        }
        $this->register_settings();
        $this->env = new MultiArray();
    }
    public function get_version()
    {
        $version = wp_cache_get('version', $this->name);
        if (!$version) {
            $version_path = $this->get_path('assets/dist/version.php');
            $version = include $version_path;
            if (empty($version)) {
                $version = get_plugin_data($this->root_path)->get('Version');
            }
            wp_cache_set('version', $version, $this->name);
        }
        return $version;
    }
    /**
     * Not necessary to be implemented, executed at the end of the constructor method.
     *
     * @return void
     */
    public function register_settings($namespaces = []): void
    {
        foreach ($namespaces as $namespace => $props) {
            $this->settings[$namespace] = new Settings($props);
        }
        add_action('wp_loaded', [$this, 'install_settings'], 10, 0);
    }
    /**
     * Not necessary to be implemented, executed at the end of the constructor method.
     *
     * @return void
     */
    public function install_settings(array $settings = []): void
    {
        foreach ($this->settings as $namespace => $props) {
            $this->settings[$namespace]->setup($settings[$namespace] ?? []);
        }
    }
    public function setup_updater()
    {
        if (empty($this->get_wp_data('UpdateURI'))) {
            return;
        }
        $this->updater = new Updater(['object' => $this, 'update_uri' => $this->get_wp_data('UpdateURI'), 'slug' => $this->slug, 'file' => $this->root_file]);
    }
    protected function load_wp_data()
    {
        if (!function_exists('\get_plugin_data')) {
            include_once \ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugin = \get_plugin_data($this->root_file);
        $this->data = $plugin;
        return $plugin;
    }
    public function get_wp_data($key = null)
    {
        if (empty($this->data) && did_action('init')) {
            $this->load_wp_data();
        }
        if (empty($key)) {
            return $this->data;
        }
        return $this->data[$key] ?? null;
    }
    public function install_textdomain()
    {
        if (!empty($this->domain)) {
            load_plugin_textdomain($this->domain, \false, $this->i18n_path);
        }
    }
    public function __get($key)
    {
        switch ($key) {
            case 'name':
                return $this->get_name();
            case 'domain':
            case 'text_domain':
            case 'i18n_domain':
                return $this->get_domain();
            case 'slug':
                return $this->slug;
            case 'file':
                return $this->get_root_file();
            case 'path':
                return $this->get_root_path();
            case 'update_uri':
                return $this->get_wp_data('UpdateURI');
            case 'data':
                return $this->data;
            default:
                if (isset($this->services[$key])) {
                    return $this->services[$key];
                }
                return null;
        }
    }
    public function get_domain()
    {
        if (empty($this->i18n_domain) && did_action('init')) {
            $this->i18n_domain = $this->get_wp_data('TextDomain');
        }
        return $this->i18n_domain ?? null;
    }
    public function get_name()
    {
        return $this->name;
    }
    public function get_root_path()
    {
        return $this->root_path;
    }
    public function get_root_file()
    {
        return $this->root_file;
    }
    public function get_url($relative_path = null)
    {
        return untrailingslashit(plugins_url($relative_path, $this->get_root_file()));
    }
    public function get_path($relative_path = '')
    {
        return untrailingslashit($this->get_root_path() . $relative_path);
    }
    public function debug_mode()
    {
        return is_debug_mode();
    }
    public function update_check($update, $item, $plugin_file)
    {
        if ($plugin_file !== $this->root_file) {
            return $update;
        }
        // TODO: implement update check
        return $update;
    }
    public function &settings($namespace = 'default')
    {
        return $this->settings[$namespace];
    }
    public function get_setting($namespace = 'default', $key = null)
    {
        $settings = $this->settings[$namespace] ?? null;
        if (empty($settings)) {
            return null;
        }
        return $settings->get($key);
    }
    public function get_template_part(string $slug, ?string $name = null, array $args = [])
    {
        $args = wp_parse_args($args, ['extension_type' => '.php', 'theme_folder' => null]);
        if (!empty($args['theme_folder'])) {
            $path = $args['theme_folder'] . '/' . $slug;
        }
        ob_start();
        $found = get_template_part($path ?? $slug, $name, $args);
        $output = ob_get_clean();
        if ($found) {
            return $output;
        }
        $file_path = $this->get_root_path() . 'templates/' . $slug;
        if ($name && file_exists($file_path . '-' . $name . '.php')) {
            $template = $file_path . '-' . $name . '.php';
        } elseif (file_exists($file_path . '.php')) {
            $template = $file_path . '.php';
        } else {
            return \false;
        }
        $encapsulator = function ($template_file_path) use ($slug, $name, $args) {
            ob_start();
            if (pathinfo($template_file_path, \PATHINFO_EXTENSION) === '.php') {
                include $template_file_path;
            } else {
                return file_get_contents($template_file_path);
            }
            return ob_get_clean();
        };
        return $encapsulator($template);
    }
    protected function attach_assets($assets, $type, $dest = 'public')
    {
        foreach ($assets as $handle => $resource) {
            list($name, $dest) = explode('|', $handle) + [null, $dest];
            if (!is_array($resource)) {
                $resource = ['path' => $resource];
            }
            $asset_path = substr($resource['path'], 0, 1) === '/' ? $resource['path'] : $this->get_path($resource['path']);
            $asset_uri = $this->get_url($resource['path']);
            $dep_path = dirname($asset_path) . '/' . basename($asset_path, '.js') . '.asset.php';
            if (file_exists($dep_path)) {
                $dep = include $dep_path;
                foreach ($dep as $key => $value) {
                    $resource[$key] ??= $value;
                }
            }
            $this->assets[$type . 's'][] = ['name' => $name, 'dest' => $dest, 'url' => $asset_uri, 'path' => $asset_path, 'env' => $resource['env'] ?? null, 'version' => $resource['version'] ?? $this->get_version(), 'dependencies' => $resource['dependencies'] ?? []];
        }
    }
    public function install_public_assets()
    {
        $this->install_scripts('public');
        $this->install_styles('public');
    }
    public function install_admin_assets()
    {
        $this->install_scripts('admin');
        $this->install_styles('admin');
    }
    public function install_editor_assets()
    {
        $this->install_scripts('editor', \false);
        $this->install_styles('editor');
    }
    public function install_fse_styles()
    {
        if (is_admin()) {
            $this->install_styles('fse');
        }
    }
    private function install_scripts($dest, $in_footer = \true)
    {
        foreach ($this->assets['scripts'] as $script) {
            if ($script['dest'] !== $dest) {
                continue;
            }
            wp_register_script($script['name'], $script['url'], $script['dependencies'] ?? [], $script['version'] ?? $this->get_version(), $script['in_footer'] ?? $in_footer);
            if (!empty($script['env'])) {
                wp_add_inline_script($script['name'], sprintf('window.global = { ...window.global, %s: %s }', $script['env'], json_encode($this->get_env($script['env']))), 'before');
            }
            wp_enqueue_script($script['name']);
        }
    }
    private function install_styles($dest)
    {
        foreach ($this->assets['stylesheets'] as $stylesheet) {
            if ($stylesheet['dest'] !== $dest) {
                continue;
            }
            wp_enqueue_style($stylesheet['name'], $stylesheet['url'], $stylesheet['dependencies'] ?? [], $stylesheet['version'] ?? $this->get_version());
        }
    }
    public function add_env($key, $value)
    {
        $this->env[$key] = $value;
    }
    public function get_env($key)
    {
        $env = $this->env[$key];
        if ($env instanceof MultiArray) {
            return $env->to_array();
        }
    }
    public function get_env_vars()
    {
        return $this->env;
    }
}
