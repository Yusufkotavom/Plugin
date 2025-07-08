<?php
/**
 * API Keys Management View
 */

if (!defined('ABSPATH')) {
    exit;
}

$current_provider = get_option('ai_content_gen_api_provider', 'google_ai');
?>

<div class="wrap ai-api-keys">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-admin-network"></span>
        <?php _e('API Keys Management', 'ai-content-gen'); ?>
    </h1>
    
    <!-- Info Panel -->
    <div class="info-panel">
        <div class="info-card">
            <h3><?php _e('🔄 API Key Rotation', 'ai-content-gen'); ?></h3>
            <p><?php _e('Add multiple API keys per provider for automatic rotation when rate limits are hit. This ensures continuous operation and maximizes your API usage efficiency.', 'ai-content-gen'); ?></p>
            <ul>
                <li><?php _e('Automatic rotation on rate limits', 'ai-content-gen'); ?></li>
                <li><?php _e('Cooldown protection prevents immediate reuse', 'ai-content-gen'); ?></li>
                <li><?php _e('Works with all supported providers', 'ai-content-gen'); ?></li>
            </ul>
        </div>
    </div>

    <!-- Provider Management -->
    <div class="providers-grid">
        <?php foreach ($providers as $provider_key => $provider_data): ?>
        <div class="provider-card" data-provider="<?php echo esc_attr($provider_key); ?>">
            <div class="provider-header">
                <h3><?php echo esc_html($provider_data['name']); ?></h3>
                <div class="provider-status">
                    <?php if ($provider_key === $current_provider): ?>
                        <span class="status-badge active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    <?php endif; ?>
                    <span class="connection-status" id="status-<?php echo esc_attr($provider_key); ?>">
                        <span class="status-dot unknown"></span>
                        <span class="status-text"><?php _e('Not tested', 'ai-content-gen'); ?></span>
                    </span>
                </div>
            </div>
            
            <div class="provider-content">
                <!-- Add New API Key -->
                <div class="add-key-section">
                    <label for="new-key-<?php echo esc_attr($provider_key); ?>" class="form-label">
                        <?php _e('Add New API Key:', 'ai-content-gen'); ?>
                    </label>
                    <div class="add-key-form">
                        <input type="password" 
                               id="new-key-<?php echo esc_attr($provider_key); ?>" 
                               class="form-control new-api-key" 
                               placeholder="<?php _e('Enter API key...', 'ai-content-gen'); ?>">
                        <button type="button" 
                                class="button button-primary add-key-btn" 
                                data-provider="<?php echo esc_attr($provider_key); ?>">
                            <?php _e('Add Key', 'ai-content-gen'); ?>
                        </button>
                    </div>
                </div>
                
                <!-- Existing Keys List -->
                <div class="keys-list" id="keys-<?php echo esc_attr($provider_key); ?>">
                    <div class="loading-keys">
                        <span class="spinner is-active"></span>
                        <span><?php _e('Loading keys...', 'ai-content-gen'); ?></span>
                    </div>
                </div>
                
                <!-- Provider Actions -->
                <div class="provider-actions">
                    <button type="button" 
                            class="button test-all-keys" 
                            data-provider="<?php echo esc_attr($provider_key); ?>">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Test All Keys', 'ai-content-gen'); ?>
                    </button>
                    <button type="button" 
                            class="button get-stats" 
                            data-provider="<?php echo esc_attr($provider_key); ?>">
                        <span class="dashicons dashicons-chart-line"></span>
                        <?php _e('View Stats', 'ai-content-gen'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    
    <!-- Global Statistics -->
    <div class="stats-panel">
        <div class="stats-card">
            <div class="card-header">
                <h3><?php _e('📊 Rotation Statistics', 'ai-content-gen'); ?></h3>
                <button type="button" id="refresh-global-stats" class="button button-small">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh', 'ai-content-gen'); ?>
                </button>
            </div>
            <div class="stats-content" id="global-stats">
                <div class="loading-stats">
                    <span class="spinner is-active"></span>
                    <span><?php _e('Loading statistics...', 'ai-content-gen'); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ai-api-keys {
    max-width: 100%;
}

.info-panel {
    margin: 20px 0;
}

.info-card {
    background: #e7f3ff;
    border: 1px solid #b3d7ff;
    border-radius: 8px;
    padding: 20px;
}

.info-card h3 {
    margin: 0 0 12px 0;
    color: #1e40af;
}

.info-card p {
    margin: 0 0 12px 0;
    color: #1e40af;
}

.info-card ul {
    margin: 0;
    padding-left: 20px;
    color: #1e40af;
}

.info-card li {
    margin-bottom: 4px;
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.provider-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.provider-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.provider-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.provider-status {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.status-badge.active {
    background: #d1fae5;
    color: #065f46;
}

.connection-status {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
}

.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-dot.unknown {
    background: #9ca3af;
}

.status-dot.connected {
    background: #10b981;
}

.status-dot.error {
    background: #ef4444;
}

.status-dot.testing {
    background: #f59e0b;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.provider-content {
    padding: 24px;
}

.add-key-section {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #374151;
}

.add-key-form {
    display: flex;
    gap: 8px;
}

.form-control {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #d1d5db;
    border-radius: 4px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 1px #2563eb;
    outline: none;
}

.add-key-btn {
    white-space: nowrap;
}

.keys-list {
    margin-bottom: 20px;
    min-height: 60px;
}

.loading-keys,
.loading-stats {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #6b7280;
    font-size: 14px;
    padding: 20px 0;
}

.key-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    margin-bottom: 8px;
}

.key-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.key-masked {
    font-family: monospace;
    font-size: 13px;
    color: #374151;
}

.key-status {
    font-size: 12px;
    padding: 2px 6px;
    border-radius: 3px;
    text-transform: uppercase;
    font-weight: 500;
}

.key-status.active {
    background: #dcfce7;
    color: #166534;
}

.key-status.cooldown {
    background: #fef3c7;
    color: #92400e;
}

.key-actions {
    display: flex;
    gap: 4px;
}

.button-small {
    padding: 4px 8px;
    font-size: 12px;
    line-height: 1.2;
}

.provider-actions {
    display: flex;
    gap: 8px;
    padding-top: 16px;
    border-top: 1px solid #f0f0f1;
}

.stats-panel {
    margin-top: 32px;
}

.stats-card {
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stats-card .card-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #f0f0f1;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.stats-card .card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.stats-content {
    padding: 24px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
}

.stat-item {
    text-align: center;
    padding: 16px;
    background: #f8fafc;
    border-radius: 6px;
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #6b7280;
    text-transform: uppercase;
    font-weight: 500;
}

.no-keys {
    text-align: center;
    color: #6b7280;
    font-style: italic;
    padding: 20px;
}

@media (max-width: 768px) {
    .providers-grid {
        grid-template-columns: 1fr;
    }
    
    .add-key-form {
        flex-direction: column;
    }
    
    .provider-actions {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Load keys for all providers on page load
    $('.provider-card').each(function() {
        const provider = $(this).data('provider');
        loadProviderKeys(provider);
    });
    
    // Load global stats
    loadGlobalStats();
    
    // Add API key
    $('.add-key-btn').on('click', function() {
        const provider = $(this).data('provider');
        const keyInput = $('#new-key-' + provider);
        const apiKey = keyInput.val().trim();
        
        if (!apiKey) {
            alert('<?php _e('Please enter an API key', 'ai-content-gen'); ?>');
            keyInput.focus();
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('<?php _e('Adding...', 'ai-content-gen'); ?>');
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_add_api_key',
                nonce: aiContentGen.nonce,
                provider: provider,
                api_key: apiKey
            },
            success: function(response) {
                if (response.success) {
                    keyInput.val('');
                    loadProviderKeys(provider);
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', '<?php _e('Network error occurred', 'ai-content-gen'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php _e('Add Key', 'ai-content-gen'); ?>');
            }
        });
    });
    
    // Remove API key
    $(document).on('click', '.remove-key', function() {
        const provider = $(this).data('provider');
        const index = $(this).data('index');
        
        if (!confirm('<?php _e('Are you sure you want to remove this API key?', 'ai-content-gen'); ?>')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_remove_api_key',
                nonce: aiContentGen.nonce,
                provider: provider,
                index: index
            },
            success: function(response) {
                if (response.success) {
                    loadProviderKeys(provider);
                    showNotice('success', response.data.message);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showNotice('error', '<?php _e('Network error occurred', 'ai-content-gen'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Test all keys for provider
    $('.test-all-keys').on('click', function() {
        const provider = $(this).data('provider');
        const $btn = $(this);
        const $status = $('#status-' + provider);
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $status.find('.status-dot').removeClass().addClass('status-dot testing');
        $status.find('.status-text').text('<?php _e('Testing...', 'ai-content-gen'); ?>');
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_test_all_keys',
                nonce: aiContentGen.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success) {
                    const results = response.data.results;
                    const successCount = results.filter(r => r.success).length;
                    const totalCount = results.length;
                    
                    if (successCount > 0) {
                        $status.find('.status-dot').removeClass().addClass('status-dot connected');
                        $status.find('.status-text').text(successCount + '/' + totalCount + ' <?php _e('keys working', 'ai-content-gen'); ?>');
                    } else {
                        $status.find('.status-dot').removeClass().addClass('status-dot error');
                        $status.find('.status-text').text('<?php _e('No working keys', 'ai-content-gen'); ?>');
                    }
                    
                    loadProviderKeys(provider);
                } else {
                    $status.find('.status-dot').removeClass().addClass('status-dot error');
                    $status.find('.status-text').text('<?php _e('Test failed', 'ai-content-gen'); ?>');
                }
            },
            error: function() {
                $status.find('.status-dot').removeClass().addClass('status-dot error');
                $status.find('.status-text').text('<?php _e('Test error', 'ai-content-gen'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
            }
        });
    });
    
    // Get provider stats
    $('.get-stats').on('click', function() {
        const provider = $(this).data('provider');
        // Implement stats modal or inline display
        alert('<?php _e('Stats feature coming soon', 'ai-content-gen'); ?>');
    });
    
    // Refresh global stats
    $('#refresh-global-stats').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        loadGlobalStats();
        setTimeout(() => {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        }, 1000);
    });
    
    function loadProviderKeys(provider) {
        const $container = $('#keys-' + provider);
        
        $container.html('<div class="loading-keys"><span class="spinner is-active"></span><span><?php _e('Loading keys...', 'ai-content-gen'); ?></span></div>');
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_get_provider_keys',
                nonce: aiContentGen.nonce,
                provider: provider
            },
            success: function(response) {
                if (response.success && response.data.keys.length > 0) {
                    let html = '';
                    response.data.keys.forEach((key, index) => {
                        html += '<div class="key-item">';
                        html += '<div class="key-info">';
                        html += '<span class="key-masked">' + key.masked + '</span>';
                        
                        if (key.active) {
                            html += '<span class="key-status active"><?php _e('Active', 'ai-content-gen'); ?></span>';
                        }
                        
                        if (key.in_cooldown) {
                            html += '<span class="key-status cooldown"><?php _e('Cooldown', 'ai-content-gen'); ?></span>';
                        }
                        
                        html += '</div>';
                        html += '<div class="key-actions">';
                        html += '<button type="button" class="button button-small remove-key" data-provider="' + provider + '" data-index="' + index + '">';
                        html += '<span class="dashicons dashicons-trash"></span>';
                        html += '</button>';
                        html += '</div>';
                        html += '</div>';
                    });
                    
                    $container.html(html);
                } else {
                    $container.html('<div class="no-keys"><?php _e('No API keys configured for this provider', 'ai-content-gen'); ?></div>');
                }
            },
            error: function() {
                $container.html('<div class="no-keys" style="color: #ef4444;"><?php _e('Error loading keys', 'ai-content-gen'); ?></div>');
            }
        });
    }
    
    function loadGlobalStats() {
        const $container = $('#global-stats');
        
        $container.html('<div class="loading-stats"><span class="spinner is-active"></span><span><?php _e('Loading statistics...', 'ai-content-gen'); ?></span></div>');
        
        $.ajax({
            url: aiContentGen.ajaxurl,
            type: 'POST',
            data: {
                action: 'ai_get_rotation_stats',
                nonce: aiContentGen.nonce
            },
            success: function(response) {
                if (response.success) {
                    const stats = response.data.stats;
                    let html = '<div class="stats-grid">';
                    
                    html += '<div class="stat-item">';
                    html += '<div class="stat-value">' + (stats.total_requests || 0) + '</div>';
                    html += '<div class="stat-label"><?php _e('Total Requests', 'ai-content-gen'); ?></div>';
                    html += '</div>';
                    
                    html += '<div class="stat-item">';
                    html += '<div class="stat-value">' + (stats.total_rotations || 0) + '</div>';
                    html += '<div class="stat-label"><?php _e('Key Rotations', 'ai-content-gen'); ?></div>';
                    html += '</div>';
                    
                    html += '<div class="stat-item">';
                    html += '<div class="stat-value">' + (stats.providers_configured || 0) + '</div>';
                    html += '<div class="stat-label"><?php _e('Providers', 'ai-content-gen'); ?></div>';
                    html += '</div>';
                    
                    html += '<div class="stat-item">';
                    html += '<div class="stat-value">' + (stats.total_keys || 0) + '</div>';
                    html += '<div class="stat-label"><?php _e('Total Keys', 'ai-content-gen'); ?></div>';
                    html += '</div>';
                    
                    html += '</div>';
                    
                    $container.html(html);
                } else {
                    $container.html('<div class="no-keys"><?php _e('Unable to load statistics', 'ai-content-gen'); ?></div>');
                }
            },
            error: function() {
                $container.html('<div class="no-keys" style="color: #ef4444;"><?php _e('Error loading statistics', 'ai-content-gen'); ?></div>');
            }
        });
    }
    
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap').prepend($notice);
        
        setTimeout(() => {
            $notice.fadeOut(() => {
                $notice.remove();
            });
        }, 5000);
    }
});
</script>