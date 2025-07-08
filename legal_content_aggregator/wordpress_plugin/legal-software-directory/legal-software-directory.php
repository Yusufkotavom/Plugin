<?php
/**
 * Plugin Name: Legal Software Directory
 * Plugin URI: https://github.com/your-repo/legal-software-directory
 * Description: Aggregates and displays legal open source software, educational resources, and development tools.
 * Version: 1.0.0
 * Author: Legal Content Aggregator
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: legal-software-directory
 * Domain Path: /languages
 * 
 * Legal Notice: This plugin only aggregates publicly available, legally distributed software.
 * All content is properly attributed to original sources. Only open source and freely
 * available software is included in the directory.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('LSD_VERSION', '1.0.0');
define('LSD_PLUGIN_FILE', __FILE__);
define('LSD_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LSD_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LSD_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Legal Software Directory Class
 */
class LegalSoftwareDirectory {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        $this->init_hooks();
        $this->load_dependencies();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_lsd_import_software', array($this, 'handle_import_software'));
        add_action('wp_ajax_lsd_search_software', array($this, 'handle_search_software'));
        add_action('wp_ajax_lsd_sync_feeds', array($this, 'handle_sync_feeds'));
        
        // Public AJAX (for frontend search)
        add_action('wp_ajax_nopriv_lsd_search_software', array($this, 'handle_search_software'));
        
        // Shortcodes
        add_shortcode('legal_software_directory', array($this, 'directory_shortcode'));
        add_shortcode('software_search', array($this, 'search_shortcode'));
        add_shortcode('trending_software', array($this, 'trending_shortcode'));
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once LSD_PLUGIN_DIR . 'includes/class-database.php';
        require_once LSD_PLUGIN_DIR . 'includes/class-api-client.php';
        require_once LSD_PLUGIN_DIR . 'includes/class-importer.php';
        require_once LSD_PLUGIN_DIR . 'includes/class-renderer.php';
        require_once LSD_PLUGIN_DIR . 'includes/class-settings.php';
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        $database = new LSD_Database();
        $database->create_tables();
        
        // Set default options
        $default_options = array(
            'api_endpoint' => 'http://localhost:8000/api',
            'auto_sync' => true,
            'sync_interval' => 'hourly',
            'max_items_per_sync' => 100,
            'enable_search' => true,
            'enable_categories' => true,
            'items_per_page' => 20
        );
        
        foreach ($default_options as $key => $value) {
            if (get_option('lsd_' . $key) === false) {
                update_option('lsd_' . $key, $value);
            }
        }
        
        // Schedule cron job for auto-sync
        if (!wp_next_scheduled('lsd_auto_sync')) {
            wp_schedule_event(time(), 'hourly', 'lsd_auto_sync');
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('lsd_auto_sync');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain(
            'legal-software-directory',
            false,
            LSD_PLUGIN_DIR . 'languages'
        );
        
        // Register custom post types
        $this->register_post_types();
        
        // Register taxonomies
        $this->register_taxonomies();
        
        // Add cron job handler
        add_action('lsd_auto_sync', array($this, 'run_auto_sync'));
    }
    
    /**
     * Register custom post types
     */
    private function register_post_types() {
        $labels = array(
            'name' => __('Software', 'legal-software-directory'),
            'singular_name' => __('Software', 'legal-software-directory'),
            'menu_name' => __('Legal Software', 'legal-software-directory'),
            'add_new' => __('Add New', 'legal-software-directory'),
            'add_new_item' => __('Add New Software', 'legal-software-directory'),
            'edit_item' => __('Edit Software', 'legal-software-directory'),
            'new_item' => __('New Software', 'legal-software-directory'),
            'view_item' => __('View Software', 'legal-software-directory'),
            'search_items' => __('Search Software', 'legal-software-directory'),
            'not_found' => __('No software found', 'legal-software-directory'),
            'not_found_in_trash' => __('No software found in trash', 'legal-software-directory'),
        );
        
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'query_var' => true,
            'rewrite' => array('slug' => 'software'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
            'menu_position' => 20,
            'menu_icon' => 'dashicons-download',
            'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest' => true, // Enable Gutenberg editor
        );
        
        register_post_type('lsd_software', $args);
    }
    
    /**
     * Register taxonomies
     */
    private function register_taxonomies() {
        // Category taxonomy
        $category_labels = array(
            'name' => __('Categories', 'legal-software-directory'),
            'singular_name' => __('Category', 'legal-software-directory'),
            'menu_name' => __('Categories', 'legal-software-directory'),
        );
        
        register_taxonomy(
            'lsd_category',
            'lsd_software',
            array(
                'labels' => $category_labels,
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'software-category'),
                'show_in_rest' => true,
            )
        );
        
        // License taxonomy
        $license_labels = array(
            'name' => __('Licenses', 'legal-software-directory'),
            'singular_name' => __('License', 'legal-software-directory'),
            'menu_name' => __('Licenses', 'legal-software-directory'),
        );
        
        register_taxonomy(
            'lsd_license',
            'lsd_software',
            array(
                'labels' => $license_labels,
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'license'),
                'show_in_rest' => true,
            )
        );
        
        // Programming Language taxonomy
        $language_labels = array(
            'name' => __('Languages', 'legal-software-directory'),
            'singular_name' => __('Language', 'legal-software-directory'),
            'menu_name' => __('Languages', 'legal-software-directory'),
        );
        
        register_taxonomy(
            'lsd_language',
            'lsd_software',
            array(
                'labels' => $language_labels,
                'hierarchical' => false,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'query_var' => true,
                'rewrite' => array('slug' => 'language'),
                'show_in_rest' => true,
            )
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Legal Software Directory', 'legal-software-directory'),
            __('Software Directory', 'legal-software-directory'),
            'manage_options',
            'legal-software-directory',
            array($this, 'admin_page'),
            'dashicons-download',
            25
        );
        
        add_submenu_page(
            'legal-software-directory',
            __('Import Software', 'legal-software-directory'),
            __('Import', 'legal-software-directory'),
            'manage_options',
            'lsd-import',
            array($this, 'import_page')
        );
        
        add_submenu_page(
            'legal-software-directory',
            __('Settings', 'legal-software-directory'),
            __('Settings', 'legal-software-directory'),
            'manage_options',
            'lsd-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'legal-software-directory',
            __('Analytics', 'legal-software-directory'),
            __('Analytics', 'legal-software-directory'),
            'manage_options',
            'lsd-analytics',
            array($this, 'analytics_page')
        );
    }
    
    /**
     * Enqueue public scripts
     */
    public function enqueue_public_scripts() {
        wp_enqueue_style(
            'lsd-public-style',
            LSD_PLUGIN_URL . 'assets/css/public.css',
            array(),
            LSD_VERSION
        );
        
        wp_enqueue_script(
            'lsd-public-script',
            LSD_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            LSD_VERSION,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script('lsd-public-script', 'lsd_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lsd_ajax_nonce'),
            'strings' => array(
                'searching' => __('Searching...', 'legal-software-directory'),
                'no_results' => __('No results found.', 'legal-software-directory'),
                'error' => __('An error occurred.', 'legal-software-directory'),
            )
        ));
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'legal-software-directory') === false && strpos($hook, 'lsd-') === false) {
            return;
        }
        
        wp_enqueue_style(
            'lsd-admin-style',
            LSD_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            LSD_VERSION
        );
        
        wp_enqueue_script(
            'lsd-admin-script',
            LSD_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-util'),
            LSD_VERSION,
            true
        );
        
        wp_localize_script('lsd-admin-script', 'lsd_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lsd_admin_ajax_nonce'),
            'strings' => array(
                'importing' => __('Importing...', 'legal-software-directory'),
                'import_complete' => __('Import completed successfully!', 'legal-software-directory'),
                'import_error' => __('Import failed. Please try again.', 'legal-software-directory'),
                'confirm_sync' => __('Are you sure you want to sync feeds? This may take a while.', 'legal-software-directory'),
            )
        ));
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        include LSD_PLUGIN_DIR . 'templates/admin-dashboard.php';
    }
    
    /**
     * Import page
     */
    public function import_page() {
        include LSD_PLUGIN_DIR . 'templates/admin-import.php';
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        $settings = new LSD_Settings();
        $settings->render_page();
    }
    
    /**
     * Analytics page
     */
    public function analytics_page() {
        include LSD_PLUGIN_DIR . 'templates/admin-analytics.php';
    }
    
    /**
     * Directory shortcode
     */
    public function directory_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => '',
            'license' => '',
            'language' => '',
            'limit' => 20,
            'search' => true,
            'pagination' => true,
        ), $atts);
        
        $renderer = new LSD_Renderer();
        return $renderer->render_directory($atts);
    }
    
    /**
     * Search shortcode
     */
    public function search_shortcode($atts) {
        $atts = shortcode_atts(array(
            'placeholder' => __('Search software...', 'legal-software-directory'),
            'show_filters' => true,
        ), $atts);
        
        $renderer = new LSD_Renderer();
        return $renderer->render_search_form($atts);
    }
    
    /**
     * Trending shortcode
     */
    public function trending_shortcode($atts) {
        $atts = shortcode_atts(array(
            'limit' => 10,
            'period' => 'week',
            'show_stats' => true,
        ), $atts);
        
        $renderer = new LSD_Renderer();
        return $renderer->render_trending($atts);
    }
    
    /**
     * Handle software import AJAX
     */
    public function handle_import_software() {
        check_ajax_referer('lsd_admin_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $source = sanitize_text_field($_POST['source']);
        $category = sanitize_text_field($_POST['category']);
        $limit = intval($_POST['limit']);
        
        $importer = new LSD_Importer();
        $result = $importer->import_from_source($source, $category, $limit);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Handle software search AJAX
     */
    public function handle_search_software() {
        check_ajax_referer('lsd_ajax_nonce', 'nonce');
        
        $query = sanitize_text_field($_POST['query']);
        $filters = $_POST['filters'] ? $_POST['filters'] : array();
        
        $renderer = new LSD_Renderer();
        $results = $renderer->search_software($query, $filters);
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle feed sync AJAX
     */
    public function handle_sync_feeds() {
        check_ajax_referer('lsd_admin_ajax_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $result = $this->run_sync();
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Run auto sync
     */
    public function run_auto_sync() {
        if (get_option('lsd_auto_sync')) {
            $this->run_sync();
        }
    }
    
    /**
     * Run sync process
     */
    private function run_sync() {
        $api_client = new LSD_API_Client();
        $importer = new LSD_Importer();
        
        try {
            // Get latest data from API
            $sources = array('github', 'sourceforge', 'rss');
            $total_imported = 0;
            
            foreach ($sources as $source) {
                $data = $api_client->fetch_latest($source);
                if ($data) {
                    $result = $importer->import_data($data, $source);
                    $total_imported += $result['imported'];
                }
            }
            
            return array(
                'success' => true,
                'message' => sprintf(__('Successfully imported %d items.', 'legal-software-directory'), $total_imported),
                'imported' => $total_imported
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
}

// Initialize the plugin
LegalSoftwareDirectory::get_instance();

/**
 * Legal notice and compliance functions
 */

/**
 * Add legal notice to plugin footer
 */
function lsd_add_legal_notice() {
    echo '<div class="lsd-legal-notice">';
    echo '<p><strong>' . __('Legal Notice:', 'legal-software-directory') . '</strong> ';
    echo __('This directory contains only legally distributed, open source software. All content is properly attributed to original sources.', 'legal-software-directory');
    echo '</p>';
    echo '</div>';
}
add_action('wp_footer', 'lsd_add_legal_notice');

/**
 * Add meta box for legal information
 */
function lsd_add_legal_meta_box() {
    add_meta_box(
        'lsd_legal_info',
        __('Legal Information', 'legal-software-directory'),
        'lsd_legal_meta_box_callback',
        'lsd_software',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'lsd_add_legal_meta_box');

/**
 * Legal meta box callback
 */
function lsd_legal_meta_box_callback($post) {
    wp_nonce_field('lsd_legal_meta_box', 'lsd_legal_meta_box_nonce');
    
    $license = get_post_meta($post->ID, '_lsd_license', true);
    $source_url = get_post_meta($post->ID, '_lsd_source_url', true);
    $legal_status = get_post_meta($post->ID, '_lsd_legal_status', true);
    
    echo '<table class="form-table">';
    
    echo '<tr>';
    echo '<td><label for="lsd_license">' . __('License:', 'legal-software-directory') . '</label></td>';
    echo '<td><input type="text" id="lsd_license" name="lsd_license" value="' . esc_attr($license) . '" class="widefat" /></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td><label for="lsd_source_url">' . __('Source URL:', 'legal-software-directory') . '</label></td>';
    echo '<td><input type="url" id="lsd_source_url" name="lsd_source_url" value="' . esc_url($source_url) . '" class="widefat" /></td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<td><label for="lsd_legal_status">' . __('Legal Status:', 'legal-software-directory') . '</label></td>';
    echo '<td>';
    echo '<select id="lsd_legal_status" name="lsd_legal_status" class="widefat">';
    echo '<option value="open_source"' . selected($legal_status, 'open_source', false) . '>' . __('Open Source', 'legal-software-directory') . '</option>';
    echo '<option value="freeware"' . selected($legal_status, 'freeware', false) . '>' . __('Freeware', 'legal-software-directory') . '</option>';
    echo '<option value="public_domain"' . selected($legal_status, 'public_domain', false) . '>' . __('Public Domain', 'legal-software-directory') . '</option>';
    echo '</select>';
    echo '</td>';
    echo '</tr>';
    
    echo '</table>';
    
    echo '<div class="lsd-legal-notice">';
    echo '<p><strong>' . __('Important:', 'legal-software-directory') . '</strong> ';
    echo __('Only add software that is legally available for distribution. Verify license compliance before publishing.', 'legal-software-directory');
    echo '</p>';
    echo '</div>';
}

/**
 * Save legal meta box data
 */
function lsd_save_legal_meta_box($post_id) {
    if (!isset($_POST['lsd_legal_meta_box_nonce'])) {
        return;
    }
    
    if (!wp_verify_nonce($_POST['lsd_legal_meta_box_nonce'], 'lsd_legal_meta_box')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['lsd_license'])) {
        update_post_meta($post_id, '_lsd_license', sanitize_text_field($_POST['lsd_license']));
    }
    
    if (isset($_POST['lsd_source_url'])) {
        update_post_meta($post_id, '_lsd_source_url', esc_url_raw($_POST['lsd_source_url']));
    }
    
    if (isset($_POST['lsd_legal_status'])) {
        update_post_meta($post_id, '_lsd_legal_status', sanitize_text_field($_POST['lsd_legal_status']));
    }
}
add_action('save_post', 'lsd_save_legal_meta_box');