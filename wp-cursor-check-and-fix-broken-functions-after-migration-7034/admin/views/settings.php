<?php
/**
 * Simplified Settings View
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['submit']) && wp_verify_nonce($_POST['ai_content_gen_nonce'], 'ai_content_gen_settings')) {
    // API Provider
    update_option('ai_content_gen_api_provider', sanitize_text_field($_POST['api_provider']));
    
    // Default Parameters
    update_option('ai_content_gen_default_tone', sanitize_text_field($_POST['default_tone']));
    update_option('ai_content_gen_default_length', sanitize_text_field($_POST['default_length']));
    update_option('ai_content_gen_default_audience', sanitize_text_field($_POST['default_audience']));
    update_option('ai_content_gen_default_content_type', sanitize_text_field($_POST['default_content_type']));
    update_option('ai_content_gen_default_language', sanitize_text_field($_POST['default_language']));
    update_option('ai_content_gen_default_style', sanitize_text_field($_POST['default_style']));
    update_option('ai_content_gen_default_post_type', sanitize_text_field($_POST['default_post_type']));
    update_option('ai_content_gen_default_post_status', sanitize_text_field($_POST['default_post_status']));
    
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'ai-content-gen') . '</p></div>';
}

// Get current values
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

<div class="wrap ai-settings">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-settings"></span>
        <?php _e('AI Content Generator Settings', 'ai-content-gen'); ?>
    </h1>
    
    <form method="post" action="" class="settings-form">
        <?php wp_nonce_field('ai_content_gen_settings', 'ai_content_gen_nonce'); ?>
        
        <!-- Provider Settings -->
        <div class="settings-section">
            <div class="section-header">
                <h2><?php _e('🤖 AI Provider Settings', 'ai-content-gen'); ?></h2>
                <p class="description"><?php _e('Choose your primary AI provider and configure API access.', 'ai-content-gen'); ?></p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-item">
                    <label for="api_provider" class="setting-label">
                        <?php _e('Primary AI Provider', 'ai-content-gen'); ?>
                        <span class="required">*</span>
                    </label>
                    <select id="api_provider" name="api_provider" class="form-control" required>
                        <?php foreach ($providers as $key => $provider): ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($current_provider, $key); ?>>
                            <?php echo esc_html($provider['name']); ?>
                            <?php if ($api_handler->is_free_tier($key)): ?>
                                <span class="provider-badge">(<?php _e('Free Tier', 'ai-content-gen'); ?>)</span>
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="setting-help"><?php _e('Select your primary AI provider. You can add multiple API keys for rotation in the API Keys section.', 'ai-content-gen'); ?></p>
                </div>
            </div>
            
            <!-- Provider Info Panel -->
            <div id="provider-info" class="provider-info-panel">
                <div class="provider-details">
                    <h4 id="provider-name-display"></h4>
                    <p id="provider-description"></p>
                    <div class="provider-features">
                        <div class="feature-badge" id="provider-pricing"></div>
                        <div class="feature-badge" id="provider-speed"></div>
                        <div class="feature-badge" id="provider-quality"></div>
                    </div>
                </div>
                <div class="provider-actions">
                    <a href="<?php echo admin_url('admin.php?page=ai-content-gen-api-keys'); ?>" class="button button-primary">
                        <?php _e('Manage API Keys', 'ai-content-gen'); ?> →
                    </a>
                    <button type="button" id="test-provider" class="button button-secondary">
                        <?php _e('Test Connection', 'ai-content-gen'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Default Generation Parameters -->
        <div class="settings-section">
            <div class="section-header">
                <h2><?php _e('📝 Default Generation Parameters', 'ai-content-gen'); ?></h2>
                <p class="description"><?php _e('Set default values for content generation. These can be overridden when generating individual content.', 'ai-content-gen'); ?></p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-item">
                    <label for="default_content_type" class="setting-label"><?php _e('Default Content Type', 'ai-content-gen'); ?></label>
                    <select id="default_content_type" name="default_content_type" class="form-control">
                        <option value="article" <?php selected($default_content_type, 'article'); ?>><?php _e('Article', 'ai-content-gen'); ?></option>
                        <option value="blog_post" <?php selected($default_content_type, 'blog_post'); ?>><?php _e('Blog Post', 'ai-content-gen'); ?></option>
                        <option value="tutorial" <?php selected($default_content_type, 'tutorial'); ?>><?php _e('Tutorial/Guide', 'ai-content-gen'); ?></option>
                        <option value="review" <?php selected($default_content_type, 'review'); ?>><?php _e('Review', 'ai-content-gen'); ?></option>
                        <option value="news" <?php selected($default_content_type, 'news'); ?>><?php _e('News Article', 'ai-content-gen'); ?></option>
                        <option value="opinion" <?php selected($default_content_type, 'opinion'); ?>><?php _e('Opinion Piece', 'ai-content-gen'); ?></option>
                    </select>
                </div>
                
                <div class="setting-item">
                    <label for="default_tone" class="setting-label"><?php _e('Default Writing Tone', 'ai-content-gen'); ?></label>
                    <select id="default_tone" name="default_tone" class="form-control">
                        <option value="professional" <?php selected($default_tone, 'professional'); ?>><?php _e('Professional', 'ai-content-gen'); ?></option>
                        <option value="informative" <?php selected($default_tone, 'informative'); ?>><?php _e('Informative', 'ai-content-gen'); ?></option>
                        <option value="casual" <?php selected($default_tone, 'casual'); ?>><?php _e('Casual', 'ai-content-gen'); ?></option>
                        <option value="creative" <?php selected($default_tone, 'creative'); ?>><?php _e('Creative', 'ai-content-gen'); ?></option>
                        <option value="persuasive" <?php selected($default_tone, 'persuasive'); ?>><?php _e('Persuasive', 'ai-content-gen'); ?></option>
                    </select>
                </div>
                
                <div class="setting-item">
                    <label for="default_length" class="setting-label"><?php _e('Default Content Length', 'ai-content-gen'); ?></label>
                    <select id="default_length" name="default_length" class="form-control">
                        <option value="short" <?php selected($default_length, 'short'); ?>><?php _e('Short (~300 words)', 'ai-content-gen'); ?></option>
                        <option value="medium" <?php selected($default_length, 'medium'); ?>><?php _e('Medium (~600 words)', 'ai-content-gen'); ?></option>
                        <option value="long" <?php selected($default_length, 'long'); ?>><?php _e('Long (~1000 words)', 'ai-content-gen'); ?></option>
                        <option value="extra_long" <?php selected($default_length, 'extra_long'); ?>><?php _e('Extra Long (~1500 words)', 'ai-content-gen'); ?></option>
                    </select>
                </div>
                
                <div class="setting-item">
                    <label for="default_audience" class="setting-label"><?php _e('Default Target Audience', 'ai-content-gen'); ?></label>
                    <input type="text" id="default_audience" name="default_audience" class="form-control" 
                           value="<?php echo esc_attr($default_audience); ?>"
                           placeholder="<?php _e('e.g., beginners, professionals, general', 'ai-content-gen'); ?>">
                </div>
                
                <div class="setting-item">
                    <label for="default_language" class="setting-label"><?php _e('Default Language', 'ai-content-gen'); ?></label>
                    <select id="default_language" name="default_language" class="form-control">
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
                
                <div class="setting-item">
                    <label for="default_style" class="setting-label"><?php _e('Default Writing Style', 'ai-content-gen'); ?></label>
                    <select id="default_style" name="default_style" class="form-control">
                        <option value="standard" <?php selected($default_style, 'standard'); ?>><?php _e('Standard', 'ai-content-gen'); ?></option>
                        <option value="storytelling" <?php selected($default_style, 'storytelling'); ?>><?php _e('Storytelling', 'ai-content-gen'); ?></option>
                        <option value="listicle" <?php selected($default_style, 'listicle'); ?>><?php _e('List Article', 'ai-content-gen'); ?></option>
                        <option value="howto" <?php selected($default_style, 'howto'); ?>><?php _e('How-to Guide', 'ai-content-gen'); ?></option>
                        <option value="qa" <?php selected($default_style, 'qa'); ?>><?php _e('Q&A Format', 'ai-content-gen'); ?></option>
                        <option value="comparison" <?php selected($default_style, 'comparison'); ?>><?php _e('Comparison', 'ai-content-gen'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <!-- WordPress Integration Settings -->
        <div class="settings-section">
            <div class="section-header">
                <h2><?php _e('🔗 WordPress Integration', 'ai-content-gen'); ?></h2>
                <p class="description"><?php _e('Configure how generated content is saved in your WordPress site.', 'ai-content-gen'); ?></p>
            </div>
            
            <div class="settings-grid">
                <div class="setting-item">
                    <label for="default_post_type" class="setting-label"><?php _e('Default Post Type', 'ai-content-gen'); ?></label>
                    <select id="default_post_type" name="default_post_type" class="form-control">
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
                
                <div class="setting-item">
                    <label for="default_post_status" class="setting-label"><?php _e('Default Post Status', 'ai-content-gen'); ?></label>
                    <select id="default_post_status" name="default_post_status" class="form-control">
                        <option value="draft" <?php selected($default_post_status, 'draft'); ?>><?php _e('Draft (Recommended)', 'ai-content-gen'); ?></option>
                        <option value="publish" <?php selected($default_post_status, 'publish'); ?>><?php _e('Published', 'ai-content-gen'); ?></option>
                        <option value="private" <?php selected($default_post_status, 'private'); ?>><?php _e('Private', 'ai-content-gen'); ?></option>
                    </select>
                    <p class="setting-help"><?php _e('Draft is recommended so you can review AI-generated content before publishing.', 'ai-content-gen'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Save Button -->
        <div class="submit-section">
            <button type="submit" name="submit" class="button button-primary button-large">
                <span class="dashicons dashicons-yes"></span>
                <?php _e('Save Settings', 'ai-content-gen'); ?>
            </button>
        </div>
    </form>
</div>

<style>
.ai-settings {
    max-width: 100%;
}

.settings-form {
    max-width: 1000px;
}

.settings-section {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    margin-bottom: 32px;
}

.section-header {
    padding: 24px 32px 20px;
    border-bottom: 1px solid #f0f0f1;
}

.section-header h2 {
    margin: 0 0 8px 0;
    font-size: 20px;
    font-weight: 600;
    color: #1d2327;
}

.section-header .description {
    margin: 0;
    color: #646970;
    font-size: 14px;
}

.settings-grid {
    padding: 32px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 24px;
}

.setting-item {
    display: flex;
    flex-direction: column;
}

.setting-label {
    margin-bottom: 8px;
    font-weight: 500;
    color: #1d2327;
    font-size: 14px;
}

.required {
    color: #d63638;
}

.form-control {
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

.setting-help {
    margin: 6px 0 0 0;
    font-size: 12px;
    color: #646970;
    font-style: italic;
}

.provider-info-panel {
    margin: 20px 32px 0;
    padding: 20px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 6px;
    display: none;
}

.provider-info-panel.active {
    display: block;
}

.provider-details h4 {
    margin: 0 0 8px 0;
    color: #1d2327;
}

.provider-details p {
    margin: 0 0 16px 0;
    color: #646970;
    font-size: 14px;
}

.provider-features {
    display: flex;
    gap: 8px;
    margin-bottom: 16px;
    flex-wrap: wrap;
}

.feature-badge {
    padding: 4px 8px;
    background: #e7f3ff;
    color: #1e40af;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.provider-actions {
    display: flex;
    gap: 8px;
}

.submit-section {
    padding: 24px 32px;
    border-top: 1px solid #f0f0f1;
    background: #f8f9fa;
    border-radius: 0 0 8px 8px;
}

.button-large {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 500;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
        padding: 24px;
    }
    
    .section-header {
        padding: 20px 24px 16px;
    }
    
    .submit-section {
        padding: 20px 24px;
    }
    
    .provider-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Provider information
    const providerInfo = {
        'google_ai': {
            name: 'Google AI (Gemini)',
            description: 'Google\'s advanced AI models with excellent performance and generous free tier.',
            pricing: 'Free Tier Available',
            speed: 'Very Fast',
            quality: 'Excellent'
        },
        'openai': {
            name: 'OpenAI',
            description: 'Industry-leading AI models including GPT-4 with premium quality.',
            pricing: 'Paid Service',
            speed: 'Fast',
            quality: 'Excellent'
        },
        'groq': {
            name: 'Groq',
            description: 'Ultra-fast inference with competitive quality and free tier.',
            pricing: 'Free Tier Available',
            speed: 'Ultra Fast',
            quality: 'Very Good'
        },
        'anthropic': {
            name: 'Anthropic Claude',
            description: 'Advanced AI with strong reasoning capabilities and safety features.',
            pricing: 'Free Credits Available',
            speed: 'Fast',
            quality: 'Excellent'
        },
        'cohere': {
            name: 'Cohere',
            description: 'Enterprise-focused AI platform with good performance.',
            pricing: 'Free Tier Available',
            speed: 'Fast',
            quality: 'Very Good'
        },
        'huggingface': {
            name: 'Hugging Face',
            description: 'Open-source models with free tier access.',
            pricing: 'Free Tier Available',
            speed: 'Moderate',
            quality: 'Good'
        },
        'together': {
            name: 'Together AI',
            description: 'Optimized open-source models with competitive pricing.',
            pricing: 'Free Credits Available',
            speed: 'Fast',
            quality: 'Very Good'
        },
        'replicate': {
            name: 'Replicate',
            description: 'Easy access to various open-source models.',
            pricing: 'Free Credits Available',
            speed: 'Moderate',
            quality: 'Good'
        },
        'openrouter': {
            name: 'OpenRouter',
            description: 'Access to many models from various providers through a single API.',
            pricing: 'Paid Service',
            speed: 'Very Fast',
            quality: 'Excellent (Varies by model)'
        },
        'perplexity': {
            name: 'Perplexity AI',
            description: 'Conversational AI focused on accuracy and real-time information.',
            pricing: 'Paid Service',
            speed: 'Very Fast',
            quality: 'Excellent'
        }
    };
    
    // Show provider info when selection changes
    $('#api_provider').on('change', function() {
        const provider = $(this).val();
        showProviderInfo(provider);
    });
    
    // Test provider connection
    $('#test-provider').on('click', function() {
        const provider = $('#api_provider').val();
        const $btn = $(this);
        
        $btn.prop('disabled', true).text('<?php _e('Testing...', 'ai-content-gen'); ?>');
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_test_api',
                nonce: aiContentGen.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    alert('✅ ' + response.data.message);
                } else {
                    alert('❌ ' + response.data.message);
                }
            },
            error: function() {
                alert('❌ <?php _e('Network error occurred', 'ai-content-gen'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Test Connection', 'ai-content-gen'); ?>');
            }
        });
    });
    
    function showProviderInfo(provider) {
        const info = providerInfo[provider];
        if (!info) {
            $('#provider-info').removeClass('active');
            return;
        }
        
        $('#provider-name-display').text(info.name);
        $('#provider-description').text(info.description);
        $('#provider-pricing').text(info.pricing);
        $('#provider-speed').text(info.speed);
        $('#provider-quality').text(info.quality);
        
        $('#provider-info').addClass('active');
    }
    
    // Show initial provider info
    showProviderInfo($('#api_provider').val());
});
</script>
