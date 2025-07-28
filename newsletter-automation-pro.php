<?php
/**
 * Plugin Name: Newsletter Automation Pro
 * Description: Automated newsletter creation using WordPress, AI, and Sendy integration
 * Version: 1.0.0
 * Author: gl0bal01
 * Text Domain: newsletter-automation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NAP_VERSION', '1.0.0');
define('NAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NAP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NAP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Newsletter Automation Plugin Class
 * Following Context7 architecture principles
 */
class NewsletterAutomationPro
{
    private static $instance = null;
    private $services = [];
    private $config;

    /**
     * Singleton pattern implementation
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    private function __construct()
    {
        $this->loadConfig();
        $this->initHooks();
        $this->loadDependencies();
        $this->initServices();
    }

    /**
     * Load configuration
     */
    private function loadConfig()
    {
        $this->config = [
            'ai_service' => get_option('nap_ai_service', 'openai'),
            'openai_api_key' => get_option('nap_openai_api_key', ''),
            'claude_api_key' => get_option('nap_claude_api_key', ''),
            'sendy_url' => get_option('nap_sendy_url', ''),
            'sendy_api_key' => get_option('nap_sendy_api_key', ''),
            'default_list_id' => get_option('nap_default_list_id', ''),
            'max_description_words' => 14,
            'newsletter_template' => get_option('nap_newsletter_template', 'default')
        ];
    }

    /**
     * Initialize WordPress hooks
     */
    private function initHooks()
    {
        add_action('plugins_loaded', [$this, 'loadTextDomain']);
        add_action('admin_menu', [$this, 'addAdminMenus']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminScripts']);
        add_action('wp_ajax_nap_search_posts', [$this, 'handleAjaxSearchPosts']);
        add_action('wp_ajax_nap_generate_descriptions', [$this, 'handleAjaxGenerateDescriptions']);
        add_action('wp_ajax_nap_create_newsletter', [$this, 'handleAjaxCreateNewsletter']);
        
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Load plugin dependencies
     */
    private function loadDependencies()
    {
        require_once NAP_PLUGIN_DIR . 'includes/class-post-search-service.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-ai-description-service.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-newsletter-builder.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-sendy-service.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-template-engine.php';
        require_once NAP_PLUGIN_DIR . 'includes/class-validation-service.php';
    }

    /**
     * Initialize services with dependency injection
     */
    private function initServices()
    {
        $this->services['validation'] = new NAP_ValidationService();
        $this->services['post_search'] = new NAP_PostSearchService();
        $this->services['ai_description'] = new NAP_AIDescriptionService($this->config);
        $this->services['template_engine'] = new NAP_TemplateEngine();
        $this->services['newsletter_builder'] = new NAP_NewsletterBuilder(
            $this->services['template_engine']
        );
        $this->services['sendy'] = new NAP_SendyService($this->config);
    }

    /**
     * Get service instance
     */
    public function getService($name)
    {
        return isset($this->services[$name]) ? $this->services[$name] : null;
    }

    /**
     * Load text domain for translations
     */
    public function loadTextDomain()
    {
        load_plugin_textdomain('newsletter-automation', false, dirname(NAP_PLUGIN_BASENAME) . '/languages/');
    }

    /**
     * Add admin menus
     */
    public function addAdminMenus()
    {
        add_menu_page(
            __('Newsletter Automation', 'newsletter-automation'),
            __('Newsletter', 'newsletter-automation'),
            'manage_options',
            'newsletter-automation',
            [$this, 'renderMainPage'],
            'dashicons-email-alt',
            30
        );

        add_submenu_page(
            'newsletter-automation',
            __('Create Newsletter', 'newsletter-automation'),
            __('Create Newsletter', 'newsletter-automation'),
            'manage_options',
            'newsletter-automation',
            [$this, 'renderMainPage']
        );

        add_submenu_page(
            'newsletter-automation',
            __('Settings', 'newsletter-automation'),
            __('Settings', 'newsletter-automation'),
            'manage_options',
            'newsletter-settings',
            [$this, 'renderSettingsPage']
        );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueueAdminScripts($hook_suffix)
    {
        if (strpos($hook_suffix, 'newsletter') === false) {
            return;
        }

        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script(
            'nap-admin-js',
            NAP_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery', 'jquery-ui-sortable'],
            NAP_VERSION,
            true
        );

        wp_enqueue_style(
            'nap-admin-css',
            NAP_PLUGIN_URL . 'assets/css/admin.css',
            [],
            NAP_VERSION
        );

        // Localize script for AJAX
        wp_localize_script('nap-admin-js', 'napAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nap_ajax_nonce'),
            'strings' => [
                'searching' => __('Searching...', 'newsletter-automation'),
                'generating' => __('Generating descriptions...', 'newsletter-automation'),
                'creating' => __('Creating newsletter...', 'newsletter-automation'),
                'error' => __('An error occurred. Please try again.', 'newsletter-automation')
            ]
        ]);
    }

    /**
     * Handle AJAX search posts
     */
    public function handleAjaxSearchPosts()
    {
        check_ajax_referer('nap_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'newsletter-automation'));
        }

        $search_term = sanitize_text_field($_POST['search_term'] ?? '');
        $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
        $posts_per_page = intval($_POST['posts_per_page'] ?? 10);

        $results = $this->services['post_search']->searchPosts([
            'search_term' => $search_term,
            'post_type' => $post_type,
            'posts_per_page' => $posts_per_page
        ]);

        wp_send_json_success($results);
    }

    /**
     * Handle AJAX generate descriptions
     */
    public function handleAjaxGenerateDescriptions()
    {
        check_ajax_referer('nap_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'newsletter-automation'));
        }

        $post_ids = array_map('intval', $_POST['post_ids'] ?? []);
        
        if (empty($post_ids)) {
            wp_send_json_error(__('No posts selected', 'newsletter-automation'));
        }

        try {
            $descriptions = $this->services['ai_description']->generateDescriptions($post_ids);
            wp_send_json_success($descriptions);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Handle AJAX create newsletter
     */
    public function handleAjaxCreateNewsletter()
    {
        check_ajax_referer('nap_ajax_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'newsletter-automation'));
        }

        $newsletter_data = [
            'posts' => array_map(function($post) {
                return [
                    'id' => intval($post['id']),
                    'description' => sanitize_text_field($post['description'])
                ];
            }, $_POST['posts'] ?? []),
            'subject' => sanitize_text_field($_POST['subject'] ?? ''),
            'list_id' => sanitize_text_field($_POST['list_id'] ?? $this->config['default_list_id']),
            'send_immediately' => filter_var($_POST['send_immediately'] ?? false, FILTER_VALIDATE_BOOLEAN)
        ];

        try {
            // Build newsletter HTML
            $html_content = $this->services['newsletter_builder']->buildNewsletter($newsletter_data['posts']);
            
            // Create newsletter in Sendy
            $result = $this->services['sendy']->createNewsletter([
                'subject' => $newsletter_data['subject'],
                'html_content' => $html_content,
                'list_id' => $newsletter_data['list_id'],
                'send_immediately' => $newsletter_data['send_immediately']
            ]);

            wp_send_json_success($result);
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Render main page
     */
    public function renderMainPage()
    {
        include NAP_PLUGIN_DIR . 'templates/admin-main-page.php';
    }

    /**
     * Render settings page
     */
    public function renderSettingsPage()
    {
        if (isset($_POST['submit'])) {
            $this->saveSettings();
        }
        include NAP_PLUGIN_DIR . 'templates/admin-settings-page.php';
    }

    /**
     * Save settings
     */
    private function saveSettings()
    {
        check_admin_referer('nap_settings_nonce');

        $settings = [
            'nap_ai_service' => sanitize_text_field($_POST['ai_service'] ?? 'openai'),
            'nap_openai_api_key' => sanitize_text_field($_POST['openai_api_key'] ?? ''),
            'nap_claude_api_key' => sanitize_text_field($_POST['claude_api_key'] ?? ''),
            'nap_sendy_url' => esc_url_raw($_POST['sendy_url'] ?? ''),
            'nap_sendy_api_key' => sanitize_text_field($_POST['sendy_api_key'] ?? ''),
            'nap_default_list_id' => sanitize_text_field($_POST['default_list_id'] ?? ''),
            'nap_newsletter_template' => sanitize_text_field($_POST['newsletter_template'] ?? 'default')
        ];

        foreach ($settings as $key => $value) {
            update_option($key, $value);
        }

        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>' . 
                 __('Settings saved successfully!', 'newsletter-automation') . '</p></div>';
        });
    }

    /**
     * Plugin activation
     */
    public function activate()
    {
        // Create necessary database tables if needed
        $this->createTables();
        
        // Set default options
        $defaults = [
            'nap_ai_service' => 'openai',
            'nap_newsletter_template' => 'default',
            'nap_max_description_words' => 14
        ];

        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }

        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate()
    {
        flush_rewrite_rules();
    }

    /**
     * Create necessary database tables
     */
    private function createTables()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'newsletter_automation_logs';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            newsletter_id varchar(100) NOT NULL,
            post_ids text NOT NULL,
            subject varchar(255) NOT NULL,
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            sent_at datetime NULL,
            error_message text NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

// Initialize the plugin
function nap_init()
{
    return NewsletterAutomationPro::getInstance();
}

// Start the plugin
nap_init();
