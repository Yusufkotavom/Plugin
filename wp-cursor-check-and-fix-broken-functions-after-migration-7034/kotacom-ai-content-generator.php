<?php
/**
 * Plugin Name: AI Content Generator Pro
 * Plugin URI: https://your-site.com/ai-content-generator
 * Description: Professional AI content generator with smart API key rotation and optimized token usage.
 * Version: 2.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: ai-content-gen
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_CONTENT_GEN_VERSION', '2.0.0');
define('AI_CONTENT_GEN_PLUGIN_FILE', __FILE__);
define('AI_CONTENT_GEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_CONTENT_GEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_CONTENT_GEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Debug mode
if (!defined('AI_CONTENT_GEN_DEBUG')) {
    define('AI_CONTENT_GEN_DEBUG', false);
}

/**
 * Main plugin class
 */
class AI_Content_Generator {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin components
     */
    public $database;
    public $admin;
    public $api_handler;
    public $api_key_rotator;
    public $content_generator;
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize plugin
     */
    private function init() {
        // Load dependencies
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Setup hooks
        $this->setup_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once AI_CONTENT_GEN_PLUGIN_DIR . 'includes/class-database.php';
        require_once AI_CONTENT_GEN_PLUGIN_DIR . 'includes/class-api-key-rotator.php';
        require_once AI_CONTENT_GEN_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once AI_CONTENT_GEN_PLUGIN_DIR . 'includes/class-content-generator.php';
        
        if (is_admin()) {
            require_once AI_CONTENT_GEN_PLUGIN_DIR . 'admin/class-admin.php';
        }
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        $this->database = new AI_Content_Gen_Database();
        $this->api_key_rotator = new AI_Content_Gen_API_Key_Rotator();
        $this->api_handler = new AI_Content_Gen_API_Handler();
        $this->content_generator = new AI_Content_Gen_Content_Generator();
        
        // Migrate legacy API keys on initialization
        $this->api_key_rotator->migrate_legacy_keys();
        
        if (is_admin()) {
            $this->admin = new AI_Content_Gen_Admin();
        }
    }
    
    /**
     * Setup WordPress hooks
     */
    private function setup_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init hook
        add_action('init', array($this, 'init_plugin'));
        
        // Admin notices for fallback usage
        add_action('admin_notices', array($this, 'show_fallback_notices'));
        
        // AJAX hooks
        $this->setup_ajax_hooks();
    }
    
    /**
     * Setup AJAX hooks
     */
    private function setup_ajax_hooks() {
        // Content generation
        add_action('wp_ajax_ai_generate_content', array($this, 'ajax_generate_content'));
        add_action('wp_ajax_ai_test_api', array($this, 'ajax_test_api'));
        
        // API Key Rotator management
        add_action('wp_ajax_ai_add_api_key', array($this, 'ajax_add_api_key'));
        add_action('wp_ajax_ai_remove_api_key', array($this, 'ajax_remove_api_key'));
        add_action('wp_ajax_ai_test_all_keys', array($this, 'ajax_test_all_keys'));
        add_action('wp_ajax_ai_get_rotation_stats', array($this, 'ajax_get_rotation_stats'));
        add_action('wp_ajax_ai_get_provider_keys', array($this, 'ajax_get_provider_keys'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $this->database->create_tables();
        
        // Set default options
        $this->set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin after WordPress is loaded
     */
    public function init_plugin() {
        // Load text domain
        load_plugin_textdomain('ai-content-gen', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Show fallback notices
     */
    public function show_fallback_notices() {
        $fallback_notice = get_transient('ai_content_gen_fallback_notice');
        
        if ($fallback_notice) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('AI Content Generator:', 'ai-content-gen') . '</strong> ';
            echo sprintf(
                __('Primary provider %s failed, automatically switched to %s for content generation.', 'ai-content-gen'),
                esc_html($fallback_notice['from']),
                esc_html($fallback_notice['to'])
            );
            echo '</p>';
            echo '</div>';
            
            delete_transient('ai_content_gen_fallback_notice');
        }
    }
    
    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'ai_content_gen_api_provider' => 'google_ai',
            'ai_content_gen_default_tone' => 'informative',
            'ai_content_gen_default_length' => '500',
            'ai_content_gen_default_audience' => 'general',
            'ai_content_gen_default_post_type' => 'post',
            'ai_content_gen_default_post_status' => 'draft'
        );
        
        foreach ($defaults as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * AJAX: Generate content with optimized prompting
     */
    public function ajax_generate_content() {
        try {
            check_ajax_referer('ai_content_gen_nonce', 'nonce');
            
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array('message' => __('Insufficient permissions', 'ai-content-gen')));
            }
            
            // Get and sanitize input parameters
            $keyword = sanitize_text_field($_POST['keyword'] ?? '');
            $content_type = sanitize_text_field($_POST['content_type'] ?? 'article');
            $tone = sanitize_text_field($_POST['tone'] ?? 'informative');
            $length = sanitize_text_field($_POST['length'] ?? 'medium');
            $audience = sanitize_text_field($_POST['audience'] ?? 'general');
            $language = sanitize_text_field($_POST['language'] ?? 'English');
            $style = sanitize_text_field($_POST['style'] ?? 'standard');
            $post_type = sanitize_text_field($_POST['post_type'] ?? 'post');
            $post_status = sanitize_text_field($_POST['post_status'] ?? 'draft');
            
            // Validate required fields
            if (empty($keyword)) {
                wp_send_json_error(array('message' => __('Keyword is required', 'ai-content-gen')));
            }
            
            // Create optimized prompt with variables
            $prompt = $this->create_optimized_prompt($keyword, $content_type, $tone, $length, $audience, $language, $style);
            
            // Generate content
            $generation_result = $this->api_handler->generate_content($prompt, array(
                'max_tokens' => $this->get_token_limit($length),
                'temperature' => $this->get_temperature($tone),
                'top_p' => 0.9
            ));
            
            if (!$generation_result['success']) {
                wp_send_json_error(array('message' => $generation_result['error']));
            }
            
            // Process and clean the generated content
            $content = $this->process_generated_content($generation_result['content'], $keyword);
            
            // Create the post
            $post_data = array(
                'post_title' => $this->generate_title($keyword, $content_type),
                'post_content' => $content,
                'post_status' => $post_status,
                'post_type' => $post_type,
                'post_author' => get_current_user_id(),
                'post_date' => current_time('mysql')
            );
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id && !is_wp_error($post_id)) {
                wp_send_json_success(array(
                    'message' => __('Content generated successfully!', 'ai-content-gen'),
                    'post_id' => $post_id,
                    'edit_link' => get_edit_post_link($post_id),
                    'view_link' => get_permalink($post_id),
                    'keyword' => $keyword,
                    'content_preview' => wp_trim_words($content, 50)
                ));
            } else {
                wp_send_json_error(array('message' => __('Failed to create post', 'ai-content-gen')));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => __('Server error: ', 'ai-content-gen') . $e->getMessage()));
        }
    }
    
    /**
     * Create optimized prompt with lower token usage
     */
    private function create_optimized_prompt($keyword, $content_type, $tone, $length, $audience, $language, $style) {
        // Get word count based on length
        $word_count = $this->get_word_count($length);
        
        // Create efficient, token-optimized prompt
        $prompt = sprintf(
            "Write a %s %s about '%s' for %s audience. Tone: %s. Style: %s. Length: %d words. Language: %s.\n\nRequirements:\n- Focus on '%s'\n- Clear structure\n- Engaging content\n- SEO-friendly\n\nContent:",
            $tone,
            $content_type,
            $keyword,
            $audience,
            $tone,
            $style,
            $word_count,
            $language,
            $keyword
        );
        
        return $prompt;
    }
    
    /**
     * Get word count based on length setting
     */
    private function get_word_count($length) {
        $word_counts = array(
            'short' => 300,
            'medium' => 600,
            'long' => 1000,
            'extra_long' => 1500
        );
        
        return $word_counts[$length] ?? 600;
    }
    
    /**
     * Get token limit based on length
     */
    private function get_token_limit($length) {
        $token_limits = array(
            'short' => 500,
            'medium' => 1000,
            'long' => 1800,
            'extra_long' => 2500
        );
        
        return $token_limits[$length] ?? 1000;
    }
    
    /**
     * Get temperature based on tone
     */
    private function get_temperature($tone) {
        $temperatures = array(
            'professional' => 0.3,
            'informative' => 0.5,
            'casual' => 0.7,
            'creative' => 0.8,
            'persuasive' => 0.6
        );
        
        return $temperatures[$tone] ?? 0.5;
    }
    
    /**
     * Generate title based on keyword and content type
     */
    private function generate_title($keyword, $content_type) {
        $title_templates = array(
            'article' => '%s: Complete Guide',
            'blog_post' => 'Ultimate Guide to %s',
            'review' => '%s Review: Pros, Cons & Verdict',
            'tutorial' => 'How to Master %s: Step-by-Step Guide',
            'news' => 'Latest News About %s',
            'opinion' => 'Why %s Matters: An Expert Opinion'
        );
        
        $template = $title_templates[$content_type] ?? 'Everything About %s';
        return sprintf($template, ucwords($keyword));
    }
    
    /**
     * Process and clean generated content
     */
    private function process_generated_content($content, $keyword) {
        // Remove any potential AI artifacts
        $content = preg_replace('/^(AI:|Assistant:|Content:)\s*/i', '', $content);
        
        // Ensure proper paragraph spacing
        $content = preg_replace('/\n\s*\n/', "\n\n", $content);
        
        // Convert to WordPress paragraphs
        $content = wpautop($content);
        
        // Ensure keyword appears naturally
        if (stripos($content, $keyword) === false) {
            $sentences = explode('.', $content);
            if (count($sentences) > 1) {
                $sentences[0] .= " related to " . $keyword;
                $content = implode('.', $sentences);
            }
        }
        
        return $content;
    }
    
    /**
     * AJAX: Test API connection
     */
    public function ajax_test_api() {
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error(array('message' => 'Provider is required'));
        }
        
        $result = $this->api_handler->test_api_connection($provider, $api_key);
        
        if ($result['success']) {
            wp_send_json_success(array('message' => 'API connection successful!'));
        } else {
            wp_send_json_error(array('message' => $result['message']));
        }
    }
    
    /**
     * AJAX: Add API key for provider
     */
    public function ajax_add_api_key() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(['message' => 'Provider and API key are required']);
        }
        
        $result = $this->api_key_rotator->add_api_key($provider, $api_key);
        
        if ($result) {
            wp_send_json_success(['message' => 'API key added successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to add API key']);
        }
    }
    
    /**
     * AJAX: Remove API key for provider
     */
    public function ajax_remove_api_key() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $index = intval($_POST['index'] ?? -1);
        
        if (empty($provider) || $index < 0) {
            wp_send_json_error(['message' => 'Provider and valid index are required']);
        }
        
        $result = $this->api_key_rotator->remove_api_key($provider, $index);
        
        if ($result) {
            wp_send_json_success(['message' => 'API key removed successfully']);
        } else {
            wp_send_json_error(['message' => 'Failed to remove API key']);
        }
    }
    
    /**
     * AJAX: Test all API keys for provider
     */
    public function ajax_test_all_keys() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error(['message' => 'Provider is required']);
        }
        
        $results = $this->api_key_rotator->test_all_keys($provider);
        wp_send_json_success(['results' => $results]);
    }
    
    /**
     * AJAX: Get rotation statistics
     */
    public function ajax_get_rotation_stats() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        $stats = $this->api_key_rotator->get_rotation_stats($provider ?: null);
        
        wp_send_json_success(['stats' => $stats]);
    }
    
    /**
     * AJAX: Get provider keys
     */
    public function ajax_get_provider_keys() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Permission denied']);
        }
        
        check_ajax_referer('ai_content_gen_nonce', 'nonce');
        
        $provider = sanitize_text_field($_POST['provider'] ?? '');
        
        if (empty($provider)) {
            wp_send_json_error(['message' => 'Provider is required']);
        }
        
        $keys = $this->api_key_rotator->get_provider_keys($provider);
        $active_index = get_option("ai_content_gen_{$provider}_active_key_index", 0);
        
        // Mask keys for security
        $masked_keys = array_map(function($key, $index) use ($active_index, $provider) {
            return [
                'masked' => substr($key, 0, 8) . '...' . substr($key, -4),
                'active' => $index === $active_index,
                'in_cooldown' => $this->api_key_rotator->is_key_in_cooldown($provider, $index)
            ];
        }, $keys, array_keys($keys));
        
        wp_send_json_success(['keys' => $masked_keys]);
    }
}

/**
 * Initialize the plugin
 */
function ai_content_generator() {
    return AI_Content_Generator::get_instance();
}

// Start the plugin
ai_content_generator();
