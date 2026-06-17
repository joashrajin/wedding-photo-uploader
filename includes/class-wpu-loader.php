<?php
/**
 * Register all actions and filters for the plugin
 */
class WPU_Loader {
    /**
     * The array of actions registered with WordPress.
     */
    protected $actions;

    /**
     * The array of filters registered with WordPress.
     */
    protected $filters;

    /**
     * Initialize the collections used to maintain the actions and filters.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Add a new action to the collection to be registered with WordPress.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Add a new filter to the collection to be registered with WordPress.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * A utility function that is used to register the actions and hooks into a single
     * collection.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Register the filters and actions with WordPress.
     */
    public function run() {
        // Initialize components
        $this->init_components();

        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }

    /**
     * Initialize plugin components and register hooks
     */
    private function init_components() {
        // Initialize admin
        $plugin_admin = new WPU_Admin('wedding-photo-uploader', WPU_VERSION);
        $this->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->add_action('admin_init', $plugin_admin, 'register_settings');

        // Initialize uploader block
        $uploader = new WPU_Uploader();
        $this->add_action('init', $uploader, 'register_block');
        $this->add_action('wp_ajax_wpu_upload_photos', $uploader, 'handle_upload');
        $this->add_action('wp_ajax_nopriv_wpu_upload_photos', $uploader, 'handle_upload');

        // Initialize gallery block
        $gallery = new WPU_Gallery();
        $this->add_action('init', $gallery, 'register_block');
        $this->add_action('enqueue_block_editor_assets', $gallery, 'enqueue_editor_assets');
        $this->add_action('wp_enqueue_scripts', $gallery, 'enqueue_frontend_assets');

        // Initialize internationalization
        $i18n = new WPU_i18n();
        $this->add_action('plugins_loaded', $i18n, 'load_plugin_textdomain');
    }
} 