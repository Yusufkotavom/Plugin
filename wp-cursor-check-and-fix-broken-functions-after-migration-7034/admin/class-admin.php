<?php
/**
 * Simplified Admin interface class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Content_Gen_Admin {
    
    private $database;
    
    public function __construct() {
        $this->database = new AI_Content_Gen_Database();
        $this->init();
    }
    
    /**
     * Initialize admin interface
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu
        add_menu_page(
            __('AI Content Generator', 'ai-content-gen'),
            __('AI Content', 'ai-content-gen'),
            'edit_posts',
            'ai-content-gen',
            array($this, 'display_generator_page'),
            'dashicons-edit-page',
            30
        );
        
        // Submenu pages
        add_submenu_page(
            'ai-content-gen',
            __('Content Generator', 'ai-content-gen'),
            __('Generator', 'ai-content-gen'),
            'edit_posts',
            'ai-content-gen',
            array($this, 'display_generator_page')
        );
        
        add_submenu_page(
            'ai-content-gen',
            __('API Keys', 'ai-content-gen'),
            __('API Keys', 'ai-content-gen'),
            'manage_options',
            'ai-content-gen-api-keys',
            array($this, 'display_api_keys_page')
        );
        
        add_submenu_page(
            'ai-content-gen',
            __('Settings', 'ai-content-gen'),
            __('Settings', 'ai-content-gen'),
            'manage_options',
            'ai-content-gen-settings',
            array($this, 'display_settings_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        // Only enqueue for our admin pages
        if (strpos($hook, 'ai-content-gen') === false) {
            return;
        }
        
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'ai-content-gen-admin',
            AI_CONTENT_GEN_PLUGIN_URL . 'admin/js/admin.js',
            array('jquery'),
            AI_CONTENT_GEN_VERSION,
            true
        );
        
        wp_enqueue_style(
            'ai-content-gen-admin',
            AI_CONTENT_GEN_PLUGIN_URL . 'admin/css/admin.css',
            array(),
            AI_CONTENT_GEN_VERSION
        );
        
        // Localize script
        wp_localize_script('ai-content-gen-admin', 'aiContentGen', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_content_gen_nonce'),
            'strings' => array(
                'generating' => __('Generating content...', 'ai-content-gen'),
                'success' => __('Content generated successfully!', 'ai-content-gen'),
                'error' => __('An error occurred. Please try again.', 'ai-content-gen'),
                'confirm_test' => __('Test API connection?', 'ai-content-gen'),
                'testing' => __('Testing connection...', 'ai-content-gen'),
                'connection_success' => __('Connection successful!', 'ai-content-gen'),
                'connection_failed' => __('Connection failed!', 'ai-content-gen')
            )
        ));
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        // API Settings
        register_setting('ai_content_gen_settings', 'ai_content_gen_api_provider');
        
        // API Keys for each provider
        $providers = ['google_ai', 'openai', 'groq', 'anthropic', 'cohere', 'huggingface', 'together', 'replicate', 'openrouter', 'perplexity'];
        foreach ($providers as $provider) {
            register_setting('ai_content_gen_settings', "ai_content_gen_{$provider}_api_keys");
            register_setting('ai_content_gen_settings', "ai_content_gen_{$provider}_model");
        }
        
        // Default Parameters
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_tone');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_length');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_audience');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_content_type');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_language');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_style');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_post_type');
        register_setting('ai_content_gen_settings', 'ai_content_gen_default_post_status');
    }
    
    /**
     * Display content generator page
     */
    public function display_generator_page() {
        $api_handler = ai_content_generator()->api_handler;
        $providers = $api_handler->get_providers();
        include AI_CONTENT_GEN_PLUGIN_DIR . 'admin/views/generator.php';
    }
    
    /**
     * Display API keys management page
     */
    public function display_api_keys_page() {
        $api_handler = ai_content_generator()->api_handler;
        $api_key_rotator = ai_content_generator()->api_key_rotator;
        $providers = $api_handler->get_providers();
        include AI_CONTENT_GEN_PLUGIN_DIR . 'admin/views/api-keys.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings_page() {
        $api_handler = ai_content_generator()->api_handler;
        $providers = $api_handler->get_providers();
        include AI_CONTENT_GEN_PLUGIN_DIR . 'admin/views/settings.php';
    }
}
