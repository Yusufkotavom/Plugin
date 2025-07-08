<?php
/**
 * Keyword Research Admin Page
 * Advanced keyword research with multiple free sources
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['research_keywords']) && wp_verify_nonce($_POST['_wpnonce'], 'keyword_research')) {
        $researcher = new AI_Content_Gen_Keyword_Researcher();
        $seed_keyword = sanitize_text_field($_POST['seed_keyword']);
        
        $options = array(
            'language' => sanitize_text_field($_POST['language']),
            'country' => sanitize_text_field($_POST['country']),
            'min_search_volume' => intval($_POST['min_search_volume']),
            'max_difficulty' => intval($_POST['max_difficulty']),
            'limit' => intval($_POST['limit']),
            'include_questions' => isset($_POST['include_questions']),
            'include_related' => isset($_POST['include_related']),
            'include_competitors' => isset($_POST['include_competitors'])
        );
        
        $research_results = $researcher->research_keywords($seed_keyword, $options);
    }
}

// Handle keyword file upload
if (isset($_FILES['keyword_file']) && $_FILES['keyword_file']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . sanitize_file_name($_FILES['keyword_file']['name']);
    
    if (move_uploaded_file($_FILES['keyword_file']['tmp_name'], $file_path)) {
        $upload_success = true;
        $uploaded_file_path = $file_path;
    }
}
?>

<div class="wrap">
    <h1><?php _e('🔍 Keyword Research', 'ai-content-gen'); ?></h1>
    
    <div class="ai-content-gen-admin">
        <!-- Research Form -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('🎯 Keyword Discovery', 'ai-content-gen'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field('keyword_research'); ?>
                    
                    <div class="form-grid">
                        <!-- Main Search -->
                        <div class="form-section">
                            <h3><?php _e('🔎 Primary Research', 'ai-content-gen'); ?></h3>
                            
                            <div class="form-row">
                                <label for="seed_keyword"><?php _e('Seed Keyword', 'ai-content-gen'); ?></label>
                                <input type="text" id="seed_keyword" name="seed_keyword" 
                                       value="<?php echo esc_attr($_POST['seed_keyword'] ?? ''); ?>" 
                                       placeholder="e.g., digital marketing" required>
                                <small><?php _e('Enter your main keyword to research related terms', 'ai-content-gen'); ?></small>
                            </div>
                            
                            <div class="form-row-group">
                                <div class="form-row">
                                    <label for="language"><?php _e('Language', 'ai-content-gen'); ?></label>
                                    <select id="language" name="language">
                                        <option value="en" <?php selected($_POST['language'] ?? 'en', 'en'); ?>>English</option>
                                        <option value="es" <?php selected($_POST['language'] ?? '', 'es'); ?>>Spanish</option>
                                        <option value="fr" <?php selected($_POST['language'] ?? '', 'fr'); ?>>French</option>
                                        <option value="de" <?php selected($_POST['language'] ?? '', 'de'); ?>>German</option>
                                        <option value="it" <?php selected($_POST['language'] ?? '', 'it'); ?>>Italian</option>
                                        <option value="pt" <?php selected($_POST['language'] ?? '', 'pt'); ?>>Portuguese</option>
                                        <option value="zh" <?php selected($_POST['language'] ?? '', 'zh'); ?>>Chinese</option>
                                        <option value="ja" <?php selected($_POST['language'] ?? '', 'ja'); ?>>Japanese</option>
                                        <option value="ar" <?php selected($_POST['language'] ?? '', 'ar'); ?>>Arabic</option>
                                        <option value="hi" <?php selected($_POST['language'] ?? '', 'hi'); ?>>Hindi</option>
                                    </select>
                                </div>
                                
                                <div class="form-row">
                                    <label for="country"><?php _e('Target Country', 'ai-content-gen'); ?></label>
                                    <select id="country" name="country">
                                        <option value="US" <?php selected($_POST['country'] ?? 'US', 'US'); ?>>United States</option>
                                        <option value="GB" <?php selected($_POST['country'] ?? '', 'GB'); ?>>United Kingdom</option>
                                        <option value="CA" <?php selected($_POST['country'] ?? '', 'CA'); ?>>Canada</option>
                                        <option value="AU" <?php selected($_POST['country'] ?? '', 'AU'); ?>>Australia</option>
                                        <option value="DE" <?php selected($_POST['country'] ?? '', 'DE'); ?>>Germany</option>
                                        <option value="FR" <?php selected($_POST['country'] ?? '', 'FR'); ?>>France</option>
                                        <option value="ES" <?php selected($_POST['country'] ?? '', 'ES'); ?>>Spain</option>
                                        <option value="IN" <?php selected($_POST['country'] ?? '', 'IN'); ?>>India</option>
                                        <option value="BR" <?php selected($_POST['country'] ?? '', 'BR'); ?>>Brazil</option>
                                        <option value="JP" <?php selected($_POST['country'] ?? '', 'JP'); ?>>Japan</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Filters -->
                        <div class="form-section">
                            <h3><?php _e('🎛️ Search Filters', 'ai-content-gen'); ?></h3>
                            
                            <div class="form-row-group">
                                <div class="form-row">
                                    <label for="min_search_volume"><?php _e('Min Search Volume', 'ai-content-gen'); ?></label>
                                    <input type="number" id="min_search_volume" name="min_search_volume" 
                                           value="<?php echo esc_attr($_POST['min_search_volume'] ?? '100'); ?>" 
                                           min="0" max="100000">
                                </div>
                                
                                <div class="form-row">
                                    <label for="max_difficulty"><?php _e('Max Difficulty', 'ai-content-gen'); ?></label>
                                    <input type="number" id="max_difficulty" name="max_difficulty" 
                                           value="<?php echo esc_attr($_POST['max_difficulty'] ?? '70'); ?>" 
                                           min="1" max="100">
                                </div>
                                
                                <div class="form-row">
                                    <label for="limit"><?php _e('Max Results', 'ai-content-gen'); ?></label>
                                    <select id="limit" name="limit">
                                        <option value="50" <?php selected($_POST['limit'] ?? '100', '50'); ?>>50 keywords</option>
                                        <option value="100" <?php selected($_POST['limit'] ?? '100', '100'); ?>>100 keywords</option>
                                        <option value="250" <?php selected($_POST['limit'] ?? '100', '250'); ?>>250 keywords</option>
                                        <option value="500" <?php selected($_POST['limit'] ?? '100', '500'); ?>>500 keywords</option>
                                        <option value="1000" <?php selected($_POST['limit'] ?? '100', '1000'); ?>>1000 keywords</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="checkbox-group">
                                <label>
                                    <input type="checkbox" name="include_questions" <?php checked(isset($_POST['include_questions']) ? $_POST['include_questions'] : true); ?>>
                                    <?php _e('Include Question Keywords', 'ai-content-gen'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="include_related" <?php checked(isset($_POST['include_related']) ? $_POST['include_related'] : true); ?>>
                                    <?php _e('Include Related Searches', 'ai-content-gen'); ?>
                                </label>
                                <label>
                                    <input type="checkbox" name="include_competitors" <?php checked(isset($_POST['include_competitors']) ? $_POST['include_competitors'] : true); ?>>
                                    <?php _e('Include Competitor Analysis', 'ai-content-gen'); ?>
                                </label>
                            </div>
                        </div>
                        
                        <!-- File Upload -->
                        <div class="form-section">
                            <h3><?php _e('📁 Bulk Keyword Upload', 'ai-content-gen'); ?></h3>
                            
                            <div class="form-row">
                                <label for="keyword_file"><?php _e('Upload Keywords File', 'ai-content-gen'); ?></label>
                                <input type="file" id="keyword_file" name="keyword_file" accept=".txt,.csv">
                                <small><?php _e('Upload a .txt or .csv file with one keyword per line', 'ai-content-gen'); ?></small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="submit-section">
                        <button type="submit" name="research_keywords" class="button button-primary button-hero">
                            <span class="dashicons dashicons-search"></span>
                            <?php _e('Research Keywords', 'ai-content-gen'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Free Tools Integration -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('🛠️ Free Research Tools', 'ai-content-gen'); ?></h2>
            </div>
            <div class="inside">
                <div class="tools-grid">
                    <div class="tool-card">
                        <h4><?php _e('🔄 Google Autocomplete', 'ai-content-gen'); ?></h4>
                        <p><?php _e('Real-time suggestions from Google search autocomplete', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                    
                    <div class="tool-card">
                        <h4><?php _e('❓ People Also Ask', 'ai-content-gen'); ?></h4>
                        <p><?php _e('Question-based keywords from Google SERP features', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                    
                    <div class="tool-card">
                        <h4><?php _e('🔗 Related Searches', 'ai-content-gen'); ?></h4>
                        <p><?php _e('Related search terms from Google bottom suggestions', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                    
                    <div class="tool-card">
                        <h4><?php _e('📊 SERP Analysis', 'ai-content-gen'); ?></h4>
                        <p><?php _e('Competitor keyword extraction from search results', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                    
                    <div class="tool-card">
                        <h4><?php _e('🎯 Answer The Public', 'ai-content-gen'); ?></h4>
                        <p><?php _e('Question and preposition-based keyword generation', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                    
                    <div class="tool-card">
                        <h4><?php _e('🔤 Alphabet Soup', 'ai-content-gen'); ?></h4>
                        <p><?php _e('A-Z keyword variations for comprehensive coverage', 'ai-content-gen'); ?></p>
                        <span class="status active"><?php _e('Active', 'ai-content-gen'); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (isset($research_results) && !empty($research_results)): ?>
        <!-- Research Results -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('📈 Research Results', 'ai-content-gen'); ?></h2>
                <div class="postbox-actions">
                    <button id="export-csv" class="button"><?php _e('Export CSV', 'ai-content-gen'); ?></button>
                    <button id="export-txt" class="button"><?php _e('Export TXT', 'ai-content-gen'); ?></button>
                    <button id="bulk-generate" class="button button-primary"><?php _e('Generate Content', 'ai-content-gen'); ?></button>
                </div>
            </div>
            <div class="inside">
                <!-- Summary Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h4><?php echo number_format($research_results['total_found']); ?></h4>
                        <p><?php _e('Total Keywords Found', 'ai-content-gen'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo number_format($research_results['filtered_count']); ?></h4>
                        <p><?php _e('After Filtering', 'ai-content-gen'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo count($research_results['clusters']); ?></h4>
                        <p><?php _e('Keyword Clusters', 'ai-content-gen'); ?></p>
                    </div>
                    <div class="stat-card">
                        <h4><?php echo number_format(array_sum(array_column($research_results['keywords'], 'search_volume'))); ?></h4>
                        <p><?php _e('Total Search Volume', 'ai-content-gen'); ?></p>
                    </div>
                </div>
                
                <!-- Filter Controls -->
                <div class="results-filters">
                    <input type="text" id="keyword-filter" placeholder="<?php _e('Filter keywords...', 'ai-content-gen'); ?>">
                    <select id="intent-filter">
                        <option value=""><?php _e('All Intents', 'ai-content-gen'); ?></option>
                        <option value="commercial"><?php _e('Commercial', 'ai-content-gen'); ?></option>
                        <option value="informational"><?php _e('Informational', 'ai-content-gen'); ?></option>
                        <option value="transactional"><?php _e('Transactional', 'ai-content-gen'); ?></option>
                        <option value="navigational"><?php _e('Navigational', 'ai-content-gen'); ?></option>
                    </select>
                    <select id="sort-by">
                        <option value="commercial_value"><?php _e('Commercial Value', 'ai-content-gen'); ?></option>
                        <option value="search_volume"><?php _e('Search Volume', 'ai-content-gen'); ?></option>
                        <option value="difficulty"><?php _e('Difficulty (Low to High)', 'ai-content-gen'); ?></option>
                        <option value="cpc"><?php _e('CPC (High to Low)', 'ai-content-gen'); ?></option>
                    </select>
                </div>
                
                <!-- Keywords Table -->
                <div class="keywords-table-container">
                    <table class="wp-list-table widefat fixed striped keywords-table">
                        <thead>
                            <tr>
                                <th class="manage-column column-cb check-column">
                                    <input type="checkbox" id="select-all-keywords">
                                </th>
                                <th class="manage-column"><?php _e('Keyword', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Volume', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Difficulty', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('CPC', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Intent', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Value', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Cluster', 'ai-content-gen'); ?></th>
                                <th class="manage-column"><?php _e('Actions', 'ai-content-gen'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="keywords-table-body">
                            <?php foreach ($research_results['keywords'] as $index => $keyword_data): ?>
                            <tr data-keyword="<?php echo esc_attr($keyword_data['keyword']); ?>" 
                                data-intent="<?php echo esc_attr($keyword_data['intent']); ?>"
                                data-volume="<?php echo esc_attr($keyword_data['search_volume']); ?>"
                                data-difficulty="<?php echo esc_attr($keyword_data['difficulty']); ?>"
                                data-cpc="<?php echo esc_attr($keyword_data['cpc']); ?>"
                                data-value="<?php echo esc_attr($keyword_data['commercial_value']); ?>">
                                <td class="check-column">
                                    <input type="checkbox" name="selected_keywords[]" value="<?php echo esc_attr($keyword_data['keyword']); ?>">
                                </td>
                                <td class="keyword-column">
                                    <strong><?php echo esc_html($keyword_data['keyword']); ?></strong>
                                    <div class="row-actions">
                                        <span class="source"><?php _e('Source:', 'ai-content-gen'); ?> <?php echo esc_html($keyword_data['source'] ?? 'Multiple'); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="volume-badge volume-<?php echo $keyword_data['search_volume'] > 1000 ? 'high' : ($keyword_data['search_volume'] > 500 ? 'medium' : 'low'); ?>">
                                        <?php echo number_format($keyword_data['search_volume']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="difficulty-bar">
                                        <div class="difficulty-fill difficulty-<?php echo $keyword_data['difficulty'] < 30 ? 'easy' : ($keyword_data['difficulty'] < 70 ? 'medium' : 'hard'); ?>" 
                                             style="width: <?php echo $keyword_data['difficulty']; ?>%"></div>
                                        <span class="difficulty-text"><?php echo $keyword_data['difficulty']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="cpc-value"><?php echo '$' . number_format($keyword_data['cpc'], 2); ?></span>
                                </td>
                                <td>
                                    <span class="intent-badge intent-<?php echo $keyword_data['intent']; ?>">
                                        <?php echo ucfirst($keyword_data['intent']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="value-score">
                                        <div class="value-bar">
                                            <div class="value-fill" style="width: <?php echo $keyword_data['commercial_value']; ?>%"></div>
                                        </div>
                                        <span><?php echo $keyword_data['commercial_value']; ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="cluster-tag"><?php echo esc_html($keyword_data['cluster'] ?? 'unclustered'); ?></span>
                                </td>
                                <td>
                                    <div class="row-actions">
                                        <a href="#" class="generate-single" data-keyword="<?php echo esc_attr($keyword_data['keyword']); ?>">
                                            <?php _e('Generate', 'ai-content-gen'); ?>
                                        </a>
                                        <span>|</span>
                                        <a href="https://www.google.com/search?q=<?php echo urlencode($keyword_data['keyword']); ?>" target="_blank">
                                            <?php _e('SERP', 'ai-content-gen'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Keyword Clusters -->
                <div class="clusters-section">
                    <h3><?php _e('🗂️ Keyword Clusters', 'ai-content-gen'); ?></h3>
                    <div class="clusters-grid">
                        <?php foreach ($research_results['clusters'] as $cluster_name => $cluster_data): ?>
                        <div class="cluster-card">
                            <h4><?php echo esc_html(str_replace('_', ' ', $cluster_name)); ?></h4>
                            <div class="cluster-stats">
                                <span><?php echo $cluster_data['count']; ?> keywords</span>
                                <span><?php echo number_format($cluster_data['avg_volume']); ?> avg volume</span>
                                <span><?php echo $cluster_data['avg_difficulty']; ?>% avg difficulty</span>
                            </div>
                            <div class="cluster-actions">
                                <button class="button button-small cluster-generate" data-cluster="<?php echo esc_attr($cluster_name); ?>">
                                    <?php _e('Generate All', 'ai-content-gen'); ?>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Trending Keywords -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('📈 Trending Keywords', 'ai-content-gen'); ?></h2>
            </div>
            <div class="inside">
                <?php 
                $researcher = new AI_Content_Gen_Keyword_Researcher();
                $trending = $researcher->get_trending_keywords();
                ?>
                
                <div class="trending-grid">
                    <?php foreach ($trending as $trend): ?>
                    <div class="trending-card">
                        <h4><?php echo esc_html($trend['keyword']); ?></h4>
                        <div class="trend-stats">
                            <span class="trend-score"><?php echo $trend['trend_score']; ?>/100</span>
                            <span class="trend-growth">+<?php echo $trend['growth']; ?></span>
                            <span class="trend-volume"><?php echo number_format($trend['search_volume']); ?> searches</span>
                        </div>
                        <button class="button button-small research-trending" data-keyword="<?php echo esc_attr($trend['keyword']); ?>">
                            <?php _e('Research', 'ai-content-gen'); ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.ai-content-gen-admin {
    max-width: 1200px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.form-section h3 {
    margin-top: 0;
    color: #1d2327;
    font-size: 14px;
    font-weight: 600;
}

.form-row {
    margin-bottom: 15px;
}

.form-row-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-row label {
    display: block;
    margin-bottom: 5px;
    font-weight: 500;
    color: #1d2327;
}

.form-row input,
.form-row select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-row small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.checkbox-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: normal;
}

.submit-section {
    text-align: center;
    padding: 20px 0;
    border-top: 1px solid #ddd;
}

.button-hero {
    padding: 12px 24px !important;
    font-size: 16px !important;
    height: auto !important;
}

.tools-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.tool-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    position: relative;
}

.tool-card h4 {
    margin: 0 0 8px 0;
    color: #1d2327;
}

.tool-card p {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 13px;
}

.status {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.status.active {
    background: #00a32a;
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.stat-card {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 20px;
    text-align: center;
}

.stat-card h4 {
    margin: 0 0 5px 0;
    font-size: 24px;
    font-weight: 700;
    color: #2271b1;
}

.stat-card p {
    margin: 0;
    color: #666;
    font-size: 13px;
}

.results-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
}

.results-filters input,
.results-filters select {
    padding: 6px 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.keywords-table-container {
    overflow-x: auto;
}

.keywords-table {
    margin: 0;
}

.keywords-table th,
.keywords-table td {
    padding: 12px 8px;
}

.volume-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
    color: white;
}

.volume-high { background: #00a32a; }
.volume-medium { background: #dba617; }
.volume-low { background: #d63638; }

.difficulty-bar {
    position: relative;
    background: #f0f0f0;
    border-radius: 10px;
    height: 20px;
    overflow: hidden;
}

.difficulty-fill {
    height: 100%;
    border-radius: 10px;
    position: relative;
}

.difficulty-easy { background: #00a32a; }
.difficulty-medium { background: #dba617; }
.difficulty-hard { background: #d63638; }

.difficulty-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 11px;
    font-weight: 500;
    color: #333;
    z-index: 1;
}

.intent-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    text-transform: uppercase;
}

.intent-commercial { background: #2271b1; color: white; }
.intent-informational { background: #00a32a; color: white; }
.intent-transactional { background: #d63638; color: white; }
.intent-navigational { background: #dba617; color: white; }

.value-score {
    display: flex;
    align-items: center;
    gap: 8px;
}

.value-bar {
    flex: 1;
    background: #f0f0f0;
    border-radius: 10px;
    height: 16px;
    overflow: hidden;
}

.value-fill {
    height: 100%;
    background: linear-gradient(90deg, #d63638 0%, #dba617 50%, #00a32a 100%);
    border-radius: 10px;
}

.cluster-tag {
    display: inline-block;
    padding: 2px 6px;
    background: #f0f0f0;
    border-radius: 4px;
    font-size: 11px;
    color: #666;
}

.clusters-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 15px;
}

.cluster-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
}

.cluster-card h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
    text-transform: capitalize;
}

.cluster-stats {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 10px;
}

.cluster-stats span {
    font-size: 12px;
    color: #666;
}

.trending-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.trending-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    position: relative;
}

.trending-card h4 {
    margin: 0 0 10px 0;
    color: #1d2327;
}

.trend-stats {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-bottom: 10px;
}

.trend-score {
    font-weight: 600;
    color: #2271b1;
}

.trend-growth {
    color: #00a32a;
    font-weight: 500;
}

.trend-volume {
    color: #666;
    font-size: 12px;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row-group {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .results-filters {
        flex-direction: column;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Keyword filtering
    $('#keyword-filter').on('input', function() {
        filterTable();
    });
    
    $('#intent-filter, #sort-by').on('change', function() {
        filterTable();
    });
    
    function filterTable() {
        const keywordFilter = $('#keyword-filter').val().toLowerCase();
        const intentFilter = $('#intent-filter').val();
        const sortBy = $('#sort-by').val();
        
        let rows = $('#keywords-table-body tr').get();
        
        // Filter rows
        rows.forEach(function(row) {
            const $row = $(row);
            const keyword = $row.data('keyword').toLowerCase();
            const intent = $row.data('intent');
            
            let show = true;
            
            if (keywordFilter && !keyword.includes(keywordFilter)) {
                show = false;
            }
            
            if (intentFilter && intent !== intentFilter) {
                show = false;
            }
            
            $row.toggle(show);
        });
        
        // Sort visible rows
        const visibleRows = $('#keywords-table-body tr:visible').get();
        visibleRows.sort(function(a, b) {
            const aVal = $(a).data(sortBy.replace('_', ''));
            const bVal = $(b).data(sortBy.replace('_', ''));
            
            if (sortBy === 'difficulty') {
                return aVal - bVal; // Low to high
            } else {
                return bVal - aVal; // High to low
            }
        });
        
        $('#keywords-table-body').append(visibleRows);
    }
    
    // Select all functionality
    $('#select-all-keywords').on('change', function() {
        $('input[name="selected_keywords[]"]:visible').prop('checked', $(this).prop('checked'));
    });
    
    // Export functionality
    $('#export-csv').on('click', function() {
        exportKeywords('csv');
    });
    
    $('#export-txt').on('click', function() {
        exportKeywords('txt');
    });
    
    function exportKeywords(format) {
        const selectedKeywords = [];
        $('input[name="selected_keywords[]"]:checked').each(function() {
            const $row = $(this).closest('tr');
            selectedKeywords.push({
                keyword: $(this).val(),
                volume: $row.data('volume'),
                difficulty: $row.data('difficulty'),
                cpc: $row.data('cpc'),
                intent: $row.data('intent'),
                value: $row.data('value')
            });
        });
        
        if (selectedKeywords.length === 0) {
            alert('<?php _e('Please select keywords to export', 'ai-content-gen'); ?>');
            return;
        }
        
        // Create and download file
        let content = '';
        if (format === 'csv') {
            content = 'Keyword,Search Volume,Difficulty,CPC,Intent,Commercial Value\n';
            selectedKeywords.forEach(function(kw) {
                content += `"${kw.keyword}",${kw.volume},${kw.difficulty},${kw.cpc},"${kw.intent}",${kw.value}\n`;
            });
        } else {
            selectedKeywords.forEach(function(kw) {
                content += kw.keyword + '\n';
            });
        }
        
        const blob = new Blob([content], {type: 'text/plain'});
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `keywords.${format}`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Bulk generate functionality
    $('#bulk-generate').on('click', function() {
        const selectedKeywords = [];
        $('input[name="selected_keywords[]"]:checked').each(function() {
            selectedKeywords.push($(this).val());
        });
        
        if (selectedKeywords.length === 0) {
            alert('<?php _e('Please select keywords for bulk generation', 'ai-content-gen'); ?>');
            return;
        }
        
        // Redirect to bulk generator with selected keywords
        const params = new URLSearchParams();
        params.append('page', 'ai-content-gen-bulk');
        params.append('keywords', selectedKeywords.join(','));
        
        window.location.href = '<?php echo admin_url('admin.php'); ?>?' + params.toString();
    });
    
    // Single generation
    $('.generate-single').on('click', function(e) {
        e.preventDefault();
        const keyword = $(this).data('keyword');
        
        const params = new URLSearchParams();
        params.append('page', 'ai-content-gen');
        params.append('keyword', keyword);
        
        window.location.href = '<?php echo admin_url('admin.php'); ?>?' + params.toString();
    });
    
    // Cluster generation
    $('.cluster-generate').on('click', function() {
        const cluster = $(this).data('cluster');
        const clusterKeywords = [];
        
        $(`#keywords-table-body tr`).each(function() {
            const clusterTag = $(this).find('.cluster-tag').text().trim();
            if (clusterTag.replace(/\s+/g, '_') === cluster) {
                clusterKeywords.push($(this).data('keyword'));
            }
        });
        
        if (clusterKeywords.length === 0) {
            alert('<?php _e('No keywords found in this cluster', 'ai-content-gen'); ?>');
            return;
        }
        
        const params = new URLSearchParams();
        params.append('page', 'ai-content-gen-bulk');
        params.append('keywords', clusterKeywords.join(','));
        params.append('cluster', cluster);
        
        window.location.href = '<?php echo admin_url('admin.php'); ?>?' + params.toString();
    });
    
    // Research trending keywords
    $('.research-trending').on('click', function() {
        const keyword = $(this).data('keyword');
        $('#seed_keyword').val(keyword);
        $('form').submit();
    });
});
</script>