<?php
/**
 * Optimized Content Generator class
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Content_Gen_Content_Generator {
    
    private $database;
    private $api_handler;
    
    public function __construct() {
        $this->database = new AI_Content_Gen_Database();
        $this->api_handler = new AI_Content_Gen_API_Handler();
    }
    
    /**
     * Generate optimized content with lower token usage
     */
    public function generate_content($keyword, $parameters = array()) {
        // Set defaults
        $defaults = array(
            'content_type' => 'article',
            'tone' => 'informative',
            'length' => 'medium',
            'audience' => 'general',
            'language' => 'English',
            'style' => 'standard',
            'post_type' => 'post',
            'post_status' => 'draft'
        );
        
        $params = wp_parse_args($parameters, $defaults);
        
        // Build optimized prompt with minimal tokens
        $prompt = $this->build_optimized_prompt($keyword, $params);
        
        // Generate content
        $result = $this->api_handler->generate_content($prompt, $params);
        
        if (!$result['success']) {
            return $result;
        }
        
        // Process the generated content
        $content = $this->process_generated_content($result['content'], $keyword, $params);
        
        // Create WordPress post
        $post_id = $this->create_post($keyword, $content, $params);
        
        if (!$post_id) {
            return array(
                'success' => false,
                'error' => __('Failed to create post', 'ai-content-gen')
            );
        }
        
        return array(
            'success' => true,
            'post_id' => $post_id,
            'keyword' => $keyword,
            'content_preview' => wp_trim_words($content, 20),
            'edit_link' => admin_url('post.php?action=edit&post=' . $post_id),
            'view_link' => get_permalink($post_id)
        );
    }
    
    /**
     * Build optimized prompt with minimal token usage
     */
    private function build_optimized_prompt($keyword, $params) {
        // Get word count target
        $word_count = $this->get_word_count($params['length']);
        
        // Build compact, efficient prompt
        $prompt = sprintf(
            "Write a %s %s about '%s' in %s.\n\nSpecs:\n- %d words\n- %s tone\n- %s style\n- Audience: %s\n- Include: title, intro, 3 main sections, conclusion\n- Use H2/H3 headings\n- Add bullet points where helpful\n\nContent:",
            $params['content_type'],
            $params['language'] !== 'English' ? "in {$params['language']}" : '',
            $keyword,
            $params['language'],
            $word_count,
            $params['tone'],
            $params['style'],
            $params['audience']
        );
        
        return $prompt;
    }
    
    /**
     * Process generated content for better formatting
     */
    private function process_generated_content($content, $keyword, $params) {
        // Clean up content
        $content = trim($content);
        
        // Ensure proper HTML formatting
        $content = $this->format_html_content($content);
        
        // Add proper paragraph breaks
        $content = wpautop($content);
        
        // Ensure content length is appropriate
        $content = $this->adjust_content_length($content, $params['length']);
        
        return $content;
    }
    
    /**
     * Format content with proper HTML structure
     */
    private function format_html_content($content) {
        // Convert markdown-style headers to HTML
        $content = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $content);
        $content = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $content);
        $content = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $content);
        
        // Convert markdown lists to HTML
        $content = preg_replace('/^\- (.+)$/m', '<li>$1</li>', $content);
        $content = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $content);
        
        // Clean up multiple consecutive breaks
        $content = preg_replace('/\n{3,}/', "\n\n", $content);
        
        return $content;
    }
    
    /**
     * Adjust content length if needed
     */
    private function adjust_content_length($content, $length_setting) {
        $word_count = str_word_count(strip_tags($content));
        $target_count = $this->get_word_count($length_setting);
        
        // If content is significantly shorter, add a note
        if ($word_count < ($target_count * 0.7)) {
            $content .= "\n\n<p><em>Note: This content can be expanded with additional details, examples, or related information as needed.</em></p>";
        }
        
        // If content is too long, don't truncate (better to have more content)
        
        return $content;
    }
    
    /**
     * Get target word count for length setting
     */
    private function get_word_count($length) {
        switch ($length) {
            case 'short':
                return 300;
            case 'medium':
                return 600;
            case 'long':
                return 1000;
            case 'extra_long':
                return 1500;
            default:
                return 600;
        }
    }
    
    /**
     * Create WordPress post
     */
    private function create_post($keyword, $content, $params) {
        // Generate SEO-optimized title
        $title = $this->generate_title($keyword, $params);
        
        $post_data = array(
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => $params['post_status'],
            'post_type' => $params['post_type'],
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                'ai_content_gen_keyword' => $keyword,
                'ai_content_gen_generated' => current_time('mysql'),
                'ai_content_gen_params' => json_encode($params)
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            return false;
        }
        
        // Add basic SEO meta if Yoast or RankMath not present
        $this->add_basic_seo_meta($post_id, $keyword, $content);
        
        return $post_id;
    }
    
    /**
     * Generate SEO-optimized title
     */
    private function generate_title($keyword, $params) {
        $templates = array(
            'article' => array(
                'Ultimate Guide to %s',
                'Everything You Need to Know About %s',
                'Complete %s Guide',
                '%s: A Comprehensive Overview'
            ),
            'tutorial' => array(
                'How to %s: Step-by-Step Guide',
                '%s Tutorial for Beginners',
                'Learn %s in Simple Steps',
                'Master %s: Complete Tutorial'
            ),
            'review' => array(
                '%s Review: Pros, Cons & Verdict',
                'Honest %s Review',
                '%s: Complete Review & Analysis',
                'Is %s Worth It? Full Review'
            ),
            'blog_post' => array(
                '%s: What You Need to Know',
                'The Truth About %s',
                '%s Explained Simply',
                'Understanding %s Better'
            ),
            'news' => array(
                'Latest %s News & Updates',
                '%s: Recent Developments',
                'Breaking: %s Updates',
                '%s News Today'
            ),
            'opinion' => array(
                'Why %s Matters',
                'The Case for %s',
                'My Take on %s',
                '%s: A Different Perspective'
            )
        );
        
        $type = $params['content_type'];
        if (!isset($templates[$type])) {
            $type = 'article';
        }
        
        $template = $templates[$type][array_rand($templates[$type])];
        return sprintf($template, ucwords($keyword));
    }
    
    /**
     * Add basic SEO meta data
     */
    private function add_basic_seo_meta($post_id, $keyword, $content) {
        // Check if SEO plugins are active
        if (defined('WPSEO_VERSION') || class_exists('RankMath')) {
            return; // Let the SEO plugin handle it
        }
        
        // Generate meta description
        $meta_description = wp_trim_words(strip_tags($content), 25);
        if (strlen($meta_description) > 155) {
            $meta_description = substr($meta_description, 0, 152) . '...';
        }
        
        update_post_meta($post_id, '_meta_description', $meta_description);
        update_post_meta($post_id, '_meta_keywords', $keyword);
        
        // Add focus keyword
        update_post_meta($post_id, '_focus_keyword', $keyword);
    }
    
    /**
     * Get content generation statistics
     */
    public function get_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total generated posts
        $stats['total_posts'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'ai_content_gen_generated'"
        );
        
        // Posts generated today
        $stats['posts_today'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'ai_content_gen_generated' AND DATE(meta_value) = %s",
                current_time('Y-m-d')
            )
        );
        
        // Posts generated this week
        $stats['posts_week'] = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = 'ai_content_gen_generated' AND meta_value >= %s",
                date('Y-m-d', strtotime('-7 days'))
            )
        );
        
        // Most used keywords
        $stats['top_keywords'] = $wpdb->get_results(
            "SELECT meta_value as keyword, COUNT(*) as count 
             FROM {$wpdb->postmeta} 
             WHERE meta_key = 'ai_content_gen_keyword' 
             GROUP BY meta_value 
             ORDER BY count DESC 
             LIMIT 5",
            ARRAY_A
        );
        
        return $stats;
    }
    
    /**
     * Validate content parameters
     */
    public function validate_parameters($params) {
        $errors = array();
        
        // Required parameters
        if (empty($params['keyword'])) {
            $errors[] = __('Keyword is required', 'ai-content-gen');
        }
        
        // Validate content type
        $valid_types = array('article', 'blog_post', 'tutorial', 'review', 'news', 'opinion');
        if (!in_array($params['content_type'], $valid_types)) {
            $errors[] = __('Invalid content type', 'ai-content-gen');
        }
        
        // Validate tone
        $valid_tones = array('professional', 'informative', 'casual', 'creative', 'persuasive');
        if (!in_array($params['tone'], $valid_tones)) {
            $errors[] = __('Invalid tone', 'ai-content-gen');
        }
        
        // Validate length
        $valid_lengths = array('short', 'medium', 'long', 'extra_long');
        if (!in_array($params['length'], $valid_lengths)) {
            $errors[] = __('Invalid length setting', 'ai-content-gen');
        }
        
        return $errors;
    }
    
    /**
     * Estimate token usage for a request
     */
    public function estimate_tokens($keyword, $params) {
        $prompt = $this->build_optimized_prompt($keyword, $params);
        $word_count = str_word_count($prompt);
        
        // Rough estimation: 1 word ≈ 1.3 tokens
        $input_tokens = ceil($word_count * 1.3);
        
        // Estimated output tokens based on target length
        $output_tokens = ceil($this->get_word_count($params['length']) * 1.3);
        
        return array(
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $input_tokens + $output_tokens
        );
    }
}
