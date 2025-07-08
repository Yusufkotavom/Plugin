<?php
/**
 * Simplified Content Generator View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings for defaults
$current_provider = get_option('ai_content_gen_api_provider', 'google_ai');
$default_tone = get_option('ai_content_gen_default_tone', 'informative');
$default_length = get_option('ai_content_gen_default_length', 'medium');
$default_audience = get_option('ai_content_gen_default_audience', 'general');
$default_content_type = get_option('ai_content_gen_default_content_type', 'article');
$default_language = get_option('ai_content_gen_default_language', 'English');
$default_style = get_option('ai_content_gen_default_style', 'standard');
$default_post_type = get_option('ai_content_gen_default_post_type', 'post');
$default_post_status = get_option('ai_content_gen_default_post_status', 'draft');
?>

<div class="wrap ai-content-generator">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-edit-page"></span>
        <?php _e('AI Content Generator', 'ai-content-gen'); ?>
    </h1>
    
    <!-- Quick Status -->
    <div class="ai-status-bar">
        <div class="status-item">
            <span class="status-label"><?php _e('Current Provider:', 'ai-content-gen'); ?></span>
            <span class="status-value"><?php echo esc_html($providers[$current_provider]['name'] ?? 'Not configured'); ?></span>
        </div>
        <a href="<?php echo admin_url('admin.php?page=ai-content-gen-settings'); ?>" class="button button-secondary button-small">
            <?php _e('Configure API Keys', 'ai-content-gen'); ?>
        </a>
    </div>

    <div class="ai-generator-container">
        <!-- Main Generator Form -->
        <div class="generator-main">
            <div class="generator-card">
                <div class="card-header">
                    <h2><?php _e('Generate New Content', 'ai-content-gen'); ?></h2>
                    <p class="description"><?php _e('Create high-quality AI-generated content with optimized prompting for better results and lower token usage.', 'ai-content-gen'); ?></p>
                </div>
                
                <form id="ai-generator-form" class="generator-form">
                    <?php wp_nonce_field('ai_content_gen_nonce', 'nonce'); ?>
                    
                    <!-- Keyword Input -->
                    <div class="form-group">
                        <label for="keyword" class="form-label">
                            <?php _e('Topic/Keyword', 'ai-content-gen'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="keyword" name="keyword" class="form-control" 
                               placeholder="<?php _e('e.g., WordPress SEO best practices', 'ai-content-gen'); ?>" required>
                        <small class="form-hint"><?php _e('Enter your main topic or keyword. Be specific for better results.', 'ai-content-gen'); ?></small>
                    </div>
                    
                    <!-- Content Parameters Row 1 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="content_type" class="form-label"><?php _e('Content Type', 'ai-content-gen'); ?></label>
                            <select id="content_type" name="content_type" class="form-control">
                                <option value="article" <?php selected($default_content_type, 'article'); ?>><?php _e('Article', 'ai-content-gen'); ?></option>
                                <option value="blog_post" <?php selected($default_content_type, 'blog_post'); ?>><?php _e('Blog Post', 'ai-content-gen'); ?></option>
                                <option value="tutorial" <?php selected($default_content_type, 'tutorial'); ?>><?php _e('Tutorial/Guide', 'ai-content-gen'); ?></option>
                                <option value="review" <?php selected($default_content_type, 'review'); ?>><?php _e('Review', 'ai-content-gen'); ?></option>
                                <option value="news" <?php selected($default_content_type, 'news'); ?>><?php _e('News Article', 'ai-content-gen'); ?></option>
                                <option value="opinion" <?php selected($default_content_type, 'opinion'); ?>><?php _e('Opinion Piece', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="tone" class="form-label"><?php _e('Writing Tone', 'ai-content-gen'); ?></label>
                            <select id="tone" name="tone" class="form-control">
                                <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'ai-content-gen'); ?></option>
                                <option value="informative" <?php selected($default_tone, 'informative'); ?>><?php _e('Informative', 'ai-content-gen'); ?></option>
                                <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'ai-content-gen'); ?></option>
                                <option value="creative" <?php selected($default_tone, 'creative'); ?>><?php _e('Creative', 'ai-content-gen'); ?></option>
                                <option value="persuasive" <?php selected($default_tone, 'persuasive'); ?>><?php _e('Persuasive', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="length" class="form-label"><?php _e('Content Length', 'ai-content-gen'); ?></label>
                            <select id="length" name="length" class="form-control">
                                <option value="short" <?php selected($default_length, 'short'); ?>><?php _e('Short (~300 words)', 'ai-content-gen'); ?></option>
                                <option value="medium" <?php selected($default_length, 'medium'); ?>><?php _e('Medium (~600 words)', 'ai-content-gen'); ?></option>
                                <option value="long" <?php selected($default_length, 'long'); ?>><?php _e('Long (~1000 words)', 'ai-content-gen'); ?></option>
                                <option value="extra_long" <?php selected($default_length, 'extra_long'); ?>><?php _e('Extra Long (~1500 words)', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Content Parameters Row 2 -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="audience" class="form-label"><?php _e('Target Audience', 'ai-content-gen'); ?></label>
                            <input type="text" id="audience" name="audience" class="form-control" 
                                   value="<?php echo esc_attr($default_audience); ?>"
                                   placeholder="<?php _e('e.g., beginners, professionals, small business owners', 'ai-content-gen'); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="language" class="form-label"><?php _e('Language', 'ai-content-gen'); ?></label>
                            <select id="language" name="language" class="form-control">
                                <option value="English" <?php selected($default_language, 'English'); ?>><?php _e('English', 'ai-content-gen'); ?></option>
                                <option value="Spanish" <?php selected($default_language, 'Spanish'); ?>><?php _e('Spanish', 'ai-content-gen'); ?></option>
                                <option value="French" <?php selected($default_language, 'French'); ?>><?php _e('French', 'ai-content-gen'); ?></option>
                                <option value="German" <?php selected($default_language, 'German'); ?>><?php _e('German', 'ai-content-gen'); ?></option>
                                <option value="Italian" <?php selected($default_language, 'Italian'); ?>><?php _e('Italian', 'ai-content-gen'); ?></option>
                                <option value="Portuguese" <?php selected($default_language, 'Portuguese'); ?>><?php _e('Portuguese', 'ai-content-gen'); ?></option>
                                <option value="Chinese" <?php selected($default_language, 'Chinese'); ?>><?php _e('Chinese', 'ai-content-gen'); ?></option>
                                <option value="Japanese" <?php selected($default_language, 'Japanese'); ?>><?php _e('Japanese', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="style" class="form-label"><?php _e('Writing Style', 'ai-content-gen'); ?></label>
                            <select id="style" name="style" class="form-control">
                                <option value="standard" <?php selected($default_style, 'standard'); ?>><?php _e('Standard', 'ai-content-gen'); ?></option>
                                <option value="storytelling" <?php selected($default_style, 'storytelling'); ?>><?php _e('Storytelling', 'ai-content-gen'); ?></option>
                                <option value="listicle" <?php selected($default_style, 'listicle'); ?>><?php _e('List Article', 'ai-content-gen'); ?></option>
                                <option value="howto" <?php selected($default_style, 'howto'); ?>><?php _e('How-to Guide', 'ai-content-gen'); ?></option>
                                <option value="qa" <?php selected($default_style, 'qa'); ?>><?php _e('Q&A Format', 'ai-content-gen'); ?></option>
                                <option value="comparison" <?php selected($default_style, 'comparison'); ?>><?php _e('Comparison', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- WordPress Settings -->
                    <div class="form-row">
                        <div class="form-group">
                            <label for="post_type" class="form-label"><?php _e('Post Type', 'ai-content-gen'); ?></label>
                            <select id="post_type" name="post_type" class="form-control">
                                <?php
                                $post_types = get_post_types(array('public' => true), 'objects');
                                foreach ($post_types as $post_type):
                                ?>
                                <option value="<?php echo esc_attr($post_type->name); ?>" <?php selected($default_post_type, $post_type->name); ?>>
                                    <?php echo esc_html($post_type->label); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="post_status" class="form-label"><?php _e('Post Status', 'ai-content-gen'); ?></label>
                            <select id="post_status" name="post_status" class="form-control">
                                <option value="draft" <?php selected($default_post_status, 'draft'); ?>><?php _e('Draft', 'ai-content-gen'); ?></option>
                                <option value="publish" <?php selected($default_post_status, 'publish'); ?>><?php _e('Published', 'ai-content-gen'); ?></option>
                                <option value="private" <?php selected($default_post_status, 'private'); ?>><?php _e('Private', 'ai-content-gen'); ?></option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Generate Button -->
                    <div class="form-actions">
                        <button type="submit" id="generate-btn" class="button button-primary button-large">
                            <span class="dashicons dashicons-edit-page"></span>
                            <?php _e('Generate Content', 'ai-content-gen'); ?>
                        </button>
                        <div class="loading-indicator" id="loading-indicator" style="display: none;">
                            <span class="spinner is-active"></span>
                            <span class="loading-text"><?php _e('Generating content...', 'ai-content-gen'); ?></span>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Results Panel -->
        <div class="generator-sidebar">
            <div class="results-card">
                <div class="card-header">
                    <h3><?php _e('Generation Results', 'ai-content-gen'); ?></h3>
                </div>
                <div id="generation-results" class="results-content">
                    <div class="no-results">
                        <span class="dashicons dashicons-admin-post"></span>
                        <p><?php _e('Generated content will appear here', 'ai-content-gen'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Quick Tips -->
            <div class="tips-card">
                <div class="card-header">
                    <h3><?php _e('💡 Quick Tips', 'ai-content-gen'); ?></h3>
                </div>
                <div class="tips-content">
                    <ul>
                        <li><?php _e('Use specific, long-tail keywords for better results', 'ai-content-gen'); ?></li>
                        <li><?php _e('Match tone and style to your target audience', 'ai-content-gen'); ?></li>
                        <li><?php _e('Start with medium length and adjust based on needs', 'ai-content-gen'); ?></li>
                        <li><?php _e('Review and edit AI content before publishing', 'ai-content-gen'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ai-content-generator {
    max-width: 100%;
    margin: 0;
}

.ai-status-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 16px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    margin: 20px 0;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-label {
    font-weight: 500;
    color: #666;
}

.status-value {
    font-weight: 600;
    color: #2271b1;
}

.ai-generator-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-top: 20px;
}

.generator-card,
.results-card,
.tips-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.card-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f0f0f1;
}

.card-header h2,
.card-header h3 {
    margin: 0 0 8px 0;
    font-size: 18px;
    font-weight: 600;
}

.card-header .description {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.generator-form {
    padding: 24px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}

.form-label {
    display: block;
    margin-bottom: 6px;
    font-weight: 500;
    color: #1d2327;
}

.required {
    color: #d63638;
}

.form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border-color 0.15s ease;
}

.form-control:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.form-hint {
    display: block;
    margin-top: 4px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    margin-top: 32px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f1;
    display: flex;
    align-items: center;
    gap: 16px;
}

.button-large {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
}

.loading-indicator {
    display: flex;
    align-items: center;
    gap: 8px;
}

.loading-text {
    color: #666;
    font-size: 14px;
}

.results-content,
.tips-content {
    padding: 24px;
}

.no-results {
    text-align: center;
    color: #666;
    padding: 40px 20px;
}

.no-results .dashicons {
    font-size: 48px;
    opacity: 0.3;
    margin-bottom: 16px;
}

.success-result {
    background: #f0f8f0;
    border: 1px solid #4caf50;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 16px;
}

.success-result h4 {
    margin: 0 0 12px 0;
    color: #2e7d32;
}

.result-actions {
    margin-top: 16px;
    display: flex;
    gap: 8px;
}

.error-result {
    background: #fef7f7;
    border: 1px solid #e53e3e;
    border-radius: 6px;
    padding: 16px;
    color: #c53030;
}

.tips-content ul {
    margin: 0;
    padding-left: 20px;
}

.tips-content li {
    margin-bottom: 8px;
    font-size: 14px;
    line-height: 1.5;
}

@media (max-width: 1200px) {
    .ai-generator-container {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    }
}

@media (max-width: 768px) {
    .ai-status-bar {
        flex-direction: column;
        gap: 12px;
        text-align: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Generate content form submission
    $('#ai-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        // Validate keyword
        const keyword = $('#keyword').val().trim();
        if (!keyword) {
            alert(aiContentGen.strings.error);
            $('#keyword').focus();
            return;
        }
        
        // Show loading state
        $('#generate-btn').prop('disabled', true);
        $('#loading-indicator').show();
        
        // Collect form data
        const formData = {
            action: 'ai_generate_content',
            nonce: $('input[name="nonce"]').val(),
            keyword: keyword,
            content_type: $('#content_type').val(),
            tone: $('#tone').val(),
            length: $('#length').val(),
            audience: $('#audience').val(),
            language: $('#language').val(),
            style: $('#style').val(),
            post_type: $('#post_type').val(),
            post_status: $('#post_status').val()
        };
        
        // Send AJAX request
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: formData,
            timeout: 60000, // 60 seconds
            success: function(response) {
                if (response.success) {
                    showSuccessResult(response.data);
                } else {
                    showErrorResult(response.data.message);
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    showErrorResult('<?php _e('Request timed out. Please try again.', 'ai-content-gen'); ?>');
                } else {
                    showErrorResult('<?php _e('Network error. Please check your connection and try again.', 'ai-content-gen'); ?>');
                }
            },
            complete: function() {
                // Hide loading state
                $('#generate-btn').prop('disabled', false);
                $('#loading-indicator').hide();
            }
        });
    });
    
    function showSuccessResult(data) {
        const resultHtml = `
            <div class="success-result">
                <h4>✅ <?php _e('Content Generated Successfully!', 'ai-content-gen'); ?></h4>
                <p><strong><?php _e('Title:', 'ai-content-gen'); ?></strong> ${data.keyword}</p>
                <p><strong><?php _e('Post ID:', 'ai-content-gen'); ?></strong> #${data.post_id}</p>
                <p><strong><?php _e('Preview:', 'ai-content-gen'); ?></strong> ${data.content_preview}...</p>
                <div class="result-actions">
                    <a href="${data.edit_link}" target="_blank" class="button button-primary button-small">
                        <?php _e('Edit Post', 'ai-content-gen'); ?> →
                    </a>
                    <a href="${data.view_link}" target="_blank" class="button button-secondary button-small">
                        <?php _e('View Post', 'ai-content-gen'); ?> →
                    </a>
                </div>
            </div>
        `;
        
        $('#generation-results').html(resultHtml);
    }
    
    function showErrorResult(message) {
        const errorHtml = `
            <div class="error-result">
                <h4>❌ <?php _e('Generation Failed', 'ai-content-gen'); ?></h4>
                <p>${message}</p>
            </div>
        `;
        
        $('#generation-results').html(errorHtml);
    }
});
</script>
