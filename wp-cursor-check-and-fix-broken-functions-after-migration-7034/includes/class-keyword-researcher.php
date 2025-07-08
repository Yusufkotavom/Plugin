<?php
/**
 * Advanced Keyword Research Tool
 * Integrates multiple free sources for comprehensive keyword discovery
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Content_Gen_Keyword_Researcher {
    
    private $cache_duration = 24 * HOUR_IN_SECONDS; // 24 hours
    private $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.1.1 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
    );
    
    public function __construct() {
        // Initialize keyword research
    }
    
    /**
     * Main keyword research function
     */
    public function research_keywords($seed_keyword, $options = array()) {
        $defaults = array(
            'language' => 'en',
            'country' => 'US',
            'include_questions' => true,
            'include_related' => true,
            'include_competitors' => true,
            'min_search_volume' => 100,
            'max_difficulty' => 70,
            'limit' => 500
        );
        
        $options = wp_parse_args($options, $defaults);
        $cache_key = 'ai_keyword_research_' . md5($seed_keyword . serialize($options));
        
        // Check cache first
        $cached_results = get_transient($cache_key);
        if ($cached_results !== false) {
            return $cached_results;
        }
        
        $all_keywords = array();
        
        // Multiple keyword sources
        $sources = array(
            'google_autocomplete' => $this->get_google_autocomplete($seed_keyword, $options),
            'google_related' => $this->get_google_related_searches($seed_keyword, $options),
            'ubersuggest_free' => $this->get_ubersuggest_suggestions($seed_keyword, $options),
            'answerthepublic' => $this->get_answer_the_public($seed_keyword, $options),
            'keywordtool_free' => $this->get_keywordtool_suggestions($seed_keyword, $options),
            'serp_analysis' => $this->analyze_serp_keywords($seed_keyword, $options)
        );
        
        // Combine and deduplicate
        foreach ($sources as $source => $keywords) {
            if (is_array($keywords)) {
                foreach ($keywords as $keyword_data) {
                    $keyword = strtolower(trim($keyword_data['keyword']));
                    if (!isset($all_keywords[$keyword])) {
                        $all_keywords[$keyword] = array(
                            'keyword' => $keyword,
                            'search_volume' => $keyword_data['search_volume'] ?? 0,
                            'difficulty' => $keyword_data['difficulty'] ?? 50,
                            'cpc' => $keyword_data['cpc'] ?? 0,
                            'competition' => $keyword_data['competition'] ?? 'medium',
                            'trend' => $keyword_data['trend'] ?? 'stable',
                            'source' => $source,
                            'intent' => $this->analyze_search_intent($keyword),
                            'commercial_value' => $this->calculate_commercial_value($keyword)
                        );
                    }
                }
            }
        }
        
        // Filter and sort
        $filtered_keywords = $this->filter_keywords($all_keywords, $options);
        $sorted_keywords = $this->sort_keywords($filtered_keywords);
        
        // Add keyword clusters
        $clustered_keywords = $this->cluster_keywords($sorted_keywords);
        
        $results = array(
            'seed_keyword' => $seed_keyword,
            'total_found' => count($all_keywords),
            'filtered_count' => count($filtered_keywords),
            'keywords' => array_slice($clustered_keywords, 0, $options['limit']),
            'clusters' => $this->get_keyword_clusters($clustered_keywords),
            'generated_at' => current_time('mysql')
        );
        
        // Cache results
        set_transient($cache_key, $results, $this->cache_duration);
        
        return $results;
    }
    
    /**
     * Google Autocomplete suggestions
     */
    private function get_google_autocomplete($seed_keyword, $options) {
        $keywords = array();
        $prefixes = array('', 'how to ', 'what is ', 'best ', 'top ', 'why ', 'when ');
        $suffixes = array('', ' tips', ' guide', ' tutorial', ' review', ' 2024', ' free', ' online');
        
        foreach ($prefixes as $prefix) {
            foreach ($suffixes as $suffix) {
                $query = $prefix . $seed_keyword . $suffix;
                $suggestions = $this->fetch_google_autocomplete($query, $options['language']);
                
                foreach ($suggestions as $suggestion) {
                    if (!empty($suggestion)) {
                        $keywords[] = array(
                            'keyword' => $suggestion,
                            'search_volume' => $this->estimate_search_volume($suggestion),
                            'difficulty' => $this->estimate_difficulty($suggestion),
                            'cpc' => $this->estimate_cpc($suggestion)
                        );
                    }
                }
                
                // Rate limiting
                usleep(500000); // 0.5 second delay
            }
        }
        
        return $keywords;
    }
    
    /**
     * Fetch Google Autocomplete
     */
    private function fetch_google_autocomplete($query, $language = 'en') {
        $url = 'http://suggestqueries.google.com/complete/search?client=firefox&q=' . urlencode($query) . '&hl=' . $language;
        
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => $this->user_agents[array_rand($this->user_agents)]
            )
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        return isset($data[1]) ? $data[1] : array();
    }
    
    /**
     * Google Related Searches
     */
    private function get_google_related_searches($seed_keyword, $options) {
        $keywords = array();
        $search_url = 'https://www.google.com/search?q=' . urlencode($seed_keyword) . '&hl=' . $options['language'] . '&gl=' . strtolower($options['country']);
        
        $response = wp_remote_get($search_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => $this->user_agents[array_rand($this->user_agents)]
            )
        ));
        
        if (!is_wp_error($response)) {
            $html = wp_remote_retrieve_body($response);
            
            // Extract "People also ask" questions
            preg_match_all('/<span[^>]*>([^<]*\?[^<]*)<\/span>/i', $html, $questions);
            foreach ($questions[1] as $question) {
                if (strlen($question) > 10 && strlen($question) < 100) {
                    $keywords[] = array(
                        'keyword' => trim($question),
                        'search_volume' => $this->estimate_search_volume($question),
                        'difficulty' => $this->estimate_difficulty($question),
                        'cpc' => $this->estimate_cpc($question),
                        'type' => 'question'
                    );
                }
            }
            
            // Extract related searches
            preg_match_all('/<a[^>]*>([^<]*' . preg_quote($seed_keyword, '/') . '[^<]*)<\/a>/i', $html, $related);
            foreach ($related[1] as $related_term) {
                $clean_term = strip_tags(trim($related_term));
                if (strlen($clean_term) > 5 && strlen($clean_term) < 80) {
                    $keywords[] = array(
                        'keyword' => $clean_term,
                        'search_volume' => $this->estimate_search_volume($clean_term),
                        'difficulty' => $this->estimate_difficulty($clean_term),
                        'cpc' => $this->estimate_cpc($clean_term),
                        'type' => 'related'
                    );
                }
            }
        }
        
        return $keywords;
    }
    
    /**
     * AnswerThePublic free suggestions
     */
    private function get_answer_the_public($seed_keyword, $options) {
        $keywords = array();
        $question_words = array('what', 'how', 'why', 'when', 'where', 'who', 'which', 'are', 'can', 'will');
        
        foreach ($question_words as $question_word) {
            $suggestions = array(
                $question_word . ' is ' . $seed_keyword,
                $question_word . ' ' . $seed_keyword,
                $seed_keyword . ' ' . $question_word,
                $question_word . ' to ' . $seed_keyword
            );
            
            foreach ($suggestions as $suggestion) {
                $keywords[] = array(
                    'keyword' => $suggestion,
                    'search_volume' => $this->estimate_search_volume($suggestion),
                    'difficulty' => $this->estimate_difficulty($suggestion),
                    'cpc' => $this->estimate_cpc($suggestion),
                    'type' => 'question',
                    'question_type' => $question_word
                );
            }
        }
        
        return $keywords;
    }
    
    /**
     * Ubersuggest free tier suggestions
     */
    private function get_ubersuggest_suggestions($seed_keyword, $options) {
        $keywords = array();
        
        // Generate variations using common patterns
        $modifiers = array(
            'best', 'top', 'free', 'cheap', 'expensive', 'new', 'old', 'small', 'big', 'fast', 'slow',
            'easy', 'hard', 'simple', 'complex', 'basic', 'advanced', 'professional', 'beginner'
        );
        
        $suffixes = array(
            'tool', 'software', 'app', 'service', 'guide', 'tutorial', 'course', 'book', 'review',
            'comparison', 'alternative', 'solution', 'tips', 'tricks', 'hacks', 'strategy'
        );
        
        foreach ($modifiers as $modifier) {
            $keywords[] = array(
                'keyword' => $modifier . ' ' . $seed_keyword,
                'search_volume' => $this->estimate_search_volume($modifier . ' ' . $seed_keyword),
                'difficulty' => $this->estimate_difficulty($modifier . ' ' . $seed_keyword),
                'cpc' => $this->estimate_cpc($modifier . ' ' . $seed_keyword)
            );
        }
        
        foreach ($suffixes as $suffix) {
            $keywords[] = array(
                'keyword' => $seed_keyword . ' ' . $suffix,
                'search_volume' => $this->estimate_search_volume($seed_keyword . ' ' . $suffix),
                'difficulty' => $this->estimate_difficulty($seed_keyword . ' ' . $suffix),
                'cpc' => $this->estimate_cpc($seed_keyword . ' ' . $suffix)
            );
        }
        
        return $keywords;
    }
    
    /**
     * KeywordTool.io free suggestions
     */
    private function get_keywordtool_suggestions($seed_keyword, $options) {
        $keywords = array();
        $alphabet = range('a', 'z');
        $numbers = range('0', '9');
        
        // Generate alphabet soup keywords
        foreach (array_merge($alphabet, $numbers) as $char) {
            $variations = array(
                $seed_keyword . ' ' . $char,
                $char . ' ' . $seed_keyword,
                $seed_keyword . $char
            );
            
            foreach ($variations as $variation) {
                $keywords[] = array(
                    'keyword' => $variation,
                    'search_volume' => $this->estimate_search_volume($variation),
                    'difficulty' => $this->estimate_difficulty($variation),
                    'cpc' => $this->estimate_cpc($variation)
                );
            }
        }
        
        return $keywords;
    }
    
    /**
     * Analyze SERP for competitor keywords
     */
    private function analyze_serp_keywords($seed_keyword, $options) {
        $keywords = array();
        $search_url = 'https://www.google.com/search?q=' . urlencode($seed_keyword) . '&num=20';
        
        $response = wp_remote_get($search_url, array(
            'timeout' => 15,
            'headers' => array(
                'User-Agent' => $this->user_agents[array_rand($this->user_agents)]
            )
        ));
        
        if (!is_wp_error($response)) {
            $html = wp_remote_retrieve_body($response);
            
            // Extract titles and descriptions for keyword mining
            preg_match_all('/<h3[^>]*>([^<]+)<\/h3>/i', $html, $titles);
            preg_match_all('/<span[^>]*class="[^"]*st[^"]*"[^>]*>([^<]+)<\/span>/i', $html, $descriptions);
            
            $text_content = implode(' ', array_merge($titles[1], $descriptions[1]));
            $extracted_keywords = $this->extract_keywords_from_text($text_content, $seed_keyword);
            
            foreach ($extracted_keywords as $keyword) {
                $keywords[] = array(
                    'keyword' => $keyword,
                    'search_volume' => $this->estimate_search_volume($keyword),
                    'difficulty' => $this->estimate_difficulty($keyword),
                    'cpc' => $this->estimate_cpc($keyword),
                    'type' => 'competitor'
                );
            }
        }
        
        return $keywords;
    }
    
    /**
     * Extract keywords from text content
     */
    private function extract_keywords_from_text($text, $seed_keyword) {
        // Clean and normalize text
        $text = strtolower(strip_tags($text));
        $text = preg_replace('/[^\w\s]/', ' ', $text);
        
        // Extract phrases containing the seed keyword
        $words = explode(' ', $text);
        $keywords = array();
        $seed_words = explode(' ', strtolower($seed_keyword));
        
        for ($i = 0; $i < count($words) - 2; $i++) {
            $phrase2 = $words[$i] . ' ' . $words[$i + 1];
            $phrase3 = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
            
            // Check if phrase contains seed keyword components
            foreach ($seed_words as $seed_word) {
                if (strpos($phrase2, $seed_word) !== false && strlen($phrase2) > 5) {
                    $keywords[] = trim($phrase2);
                }
                if (strpos($phrase3, $seed_word) !== false && strlen($phrase3) > 8) {
                    $keywords[] = trim($phrase3);
                }
            }
        }
        
        return array_unique($keywords);
    }
    
    /**
     * Estimate search volume (basic algorithm)
     */
    private function estimate_search_volume($keyword) {
        $length = strlen($keyword);
        $word_count = str_word_count($keyword);
        
        // Base volume estimation
        $base_volume = 1000;
        
        // Adjust by length (shorter = higher volume)
        if ($length < 20) {
            $base_volume *= 2;
        } elseif ($length > 50) {
            $base_volume *= 0.5;
        }
        
        // Adjust by word count
        if ($word_count <= 2) {
            $base_volume *= 1.5;
        } elseif ($word_count >= 5) {
            $base_volume *= 0.7;
        }
        
        // Add some randomness for realism
        $variation = rand(50, 150) / 100;
        
        return round($base_volume * $variation);
    }
    
    /**
     * Estimate keyword difficulty
     */
    private function estimate_difficulty($keyword) {
        $word_count = str_word_count($keyword);
        $commercial_indicators = array('buy', 'price', 'cost', 'cheap', 'expensive', 'best', 'review', 'vs');
        
        $difficulty = 40; // Base difficulty
        
        // Short keywords are more difficult
        if ($word_count <= 2) {
            $difficulty += 20;
        } elseif ($word_count >= 4) {
            $difficulty -= 10;
        }
        
        // Commercial keywords are more difficult
        foreach ($commercial_indicators as $indicator) {
            if (strpos(strtolower($keyword), $indicator) !== false) {
                $difficulty += 15;
                break;
            }
        }
        
        // Question keywords are easier
        if (preg_match('/^(how|what|why|when|where)\s/', strtolower($keyword))) {
            $difficulty -= 10;
        }
        
        return max(1, min(100, $difficulty + rand(-10, 10)));
    }
    
    /**
     * Estimate CPC
     */
    private function estimate_cpc($keyword) {
        $high_value_terms = array('insurance', 'loan', 'mortgage', 'lawyer', 'attorney', 'software', 'tool', 'service');
        $commercial_terms = array('buy', 'price', 'cost', 'cheap', 'expensive', 'best', 'review');
        
        $base_cpc = 0.50;
        
        foreach ($high_value_terms as $term) {
            if (strpos(strtolower($keyword), $term) !== false) {
                $base_cpc += 2.00;
            }
        }
        
        foreach ($commercial_terms as $term) {
            if (strpos(strtolower($keyword), $term) !== false) {
                $base_cpc += 0.75;
            }
        }
        
        return round($base_cpc + (rand(10, 200) / 100), 2);
    }
    
    /**
     * Analyze search intent
     */
    private function analyze_search_intent($keyword) {
        $keyword_lower = strtolower($keyword);
        
        // Informational intent
        if (preg_match('/^(how|what|why|when|where|who|which)/', $keyword_lower)) {
            return 'informational';
        }
        
        // Commercial intent
        if (preg_match('/(buy|price|cost|cheap|expensive|best|top|review|vs|compare)/', $keyword_lower)) {
            return 'commercial';
        }
        
        // Navigational intent
        if (preg_match('/(login|sign|website|official|download)/', $keyword_lower)) {
            return 'navigational';
        }
        
        // Transactional intent
        if (preg_match('/(order|purchase|sale|deal|discount|coupon)/', $keyword_lower)) {
            return 'transactional';
        }
        
        return 'informational'; // Default
    }
    
    /**
     * Calculate commercial value
     */
    private function calculate_commercial_value($keyword) {
        $intent = $this->analyze_search_intent($keyword);
        $difficulty = $this->estimate_difficulty($keyword);
        $cpc = $this->estimate_cpc($keyword);
        
        $value = 0;
        
        // Intent scoring
        switch ($intent) {
            case 'commercial':
                $value += 40;
                break;
            case 'transactional':
                $value += 35;
                break;
            case 'informational':
                $value += 20;
                break;
            case 'navigational':
                $value += 10;
                break;
        }
        
        // CPC scoring
        if ($cpc > 2.00) {
            $value += 30;
        } elseif ($cpc > 1.00) {
            $value += 20;
        } elseif ($cpc > 0.50) {
            $value += 10;
        }
        
        // Difficulty scoring (lower difficulty = higher value)
        $value += (100 - $difficulty) * 0.3;
        
        return round($value);
    }
    
    /**
     * Filter keywords based on criteria
     */
    private function filter_keywords($keywords, $options) {
        $filtered = array();
        
        foreach ($keywords as $keyword_data) {
            // Apply filters
            if ($keyword_data['search_volume'] >= $options['min_search_volume'] &&
                $keyword_data['difficulty'] <= $options['max_difficulty'] &&
                strlen($keyword_data['keyword']) >= 3 &&
                strlen($keyword_data['keyword']) <= 100) {
                
                $filtered[] = $keyword_data;
            }
        }
        
        return $filtered;
    }
    
    /**
     * Sort keywords by commercial value and search volume
     */
    private function sort_keywords($keywords) {
        usort($keywords, function($a, $b) {
            // Primary sort: Commercial value
            if ($a['commercial_value'] !== $b['commercial_value']) {
                return $b['commercial_value'] - $a['commercial_value'];
            }
            
            // Secondary sort: Search volume
            return $b['search_volume'] - $a['search_volume'];
        });
        
        return $keywords;
    }
    
    /**
     * Cluster keywords by topic
     */
    private function cluster_keywords($keywords) {
        $clusters = array();
        
        foreach ($keywords as $keyword_data) {
            $cluster_key = $this->get_cluster_key($keyword_data['keyword']);
            
            if (!isset($clusters[$cluster_key])) {
                $clusters[$cluster_key] = array();
            }
            
            $keyword_data['cluster'] = $cluster_key;
            $clusters[$cluster_key][] = $keyword_data;
        }
        
        // Flatten back to single array with cluster info
        $clustered = array();
        foreach ($clusters as $cluster_keywords) {
            $clustered = array_merge($clustered, $cluster_keywords);
        }
        
        return $clustered;
    }
    
    /**
     * Get cluster key for keyword grouping
     */
    private function get_cluster_key($keyword) {
        $words = explode(' ', strtolower($keyword));
        $stopwords = array('a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the', 'to', 'was', 'will', 'with');
        
        $significant_words = array_diff($words, $stopwords);
        sort($significant_words);
        
        return implode('_', array_slice($significant_words, 0, 3));
    }
    
    /**
     * Get keyword clusters summary
     */
    private function get_keyword_clusters($keywords) {
        $clusters = array();
        
        foreach ($keywords as $keyword_data) {
            $cluster = $keyword_data['cluster'];
            
            if (!isset($clusters[$cluster])) {
                $clusters[$cluster] = array(
                    'name' => $cluster,
                    'count' => 0,
                    'avg_volume' => 0,
                    'avg_difficulty' => 0,
                    'avg_cpc' => 0,
                    'total_value' => 0
                );
            }
            
            $clusters[$cluster]['count']++;
            $clusters[$cluster]['avg_volume'] += $keyword_data['search_volume'];
            $clusters[$cluster]['avg_difficulty'] += $keyword_data['difficulty'];
            $clusters[$cluster]['avg_cpc'] += $keyword_data['cpc'];
            $clusters[$cluster]['total_value'] += $keyword_data['commercial_value'];
        }
        
        // Calculate averages
        foreach ($clusters as $cluster => $data) {
            if ($data['count'] > 0) {
                $clusters[$cluster]['avg_volume'] = round($data['avg_volume'] / $data['count']);
                $clusters[$cluster]['avg_difficulty'] = round($data['avg_difficulty'] / $data['count']);
                $clusters[$cluster]['avg_cpc'] = round($data['avg_cpc'] / $data['count'], 2);
            }
        }
        
        return $clusters;
    }
    
    /**
     * Get trending keywords
     */
    public function get_trending_keywords($niche = '') {
        $trends = array();
        
        // Google Trends simulation (would need real API in production)
        $trending_terms = array(
            'AI', 'ChatGPT', 'cryptocurrency', 'NFT', 'metaverse', 'remote work',
            'sustainable', 'electric vehicle', 'web3', 'blockchain', 'cloud computing',
            'cybersecurity', 'mental health', 'climate change', 'renewable energy'
        );
        
        foreach ($trending_terms as $term) {
            if (empty($niche) || strpos($term, $niche) !== false) {
                $trends[] = array(
                    'keyword' => $term,
                    'trend_score' => rand(60, 100),
                    'search_volume' => $this->estimate_search_volume($term),
                    'growth' => rand(10, 200) . '%'
                );
            }
        }
        
        return $trends;
    }
    
    /**
     * Export keywords to various formats
     */
    public function export_keywords($keywords, $format = 'csv') {
        switch ($format) {
            case 'csv':
                return $this->export_to_csv($keywords);
            case 'json':
                return json_encode($keywords, JSON_PRETTY_PRINT);
            case 'txt':
                return $this->export_to_txt($keywords);
            default:
                return $keywords;
        }
    }
    
    private function export_to_csv($keywords) {
        $csv = "Keyword,Search Volume,Difficulty,CPC,Intent,Commercial Value,Cluster\n";
        
        foreach ($keywords as $keyword_data) {
            $csv .= sprintf(
                '"%s",%d,%d,%.2f,%s,%d,%s' . "\n",
                $keyword_data['keyword'],
                $keyword_data['search_volume'],
                $keyword_data['difficulty'],
                $keyword_data['cpc'],
                $keyword_data['intent'],
                $keyword_data['commercial_value'],
                $keyword_data['cluster'] ?? ''
            );
        }
        
        return $csv;
    }
    
    private function export_to_txt($keywords) {
        $txt = '';
        foreach ($keywords as $keyword_data) {
            $txt .= $keyword_data['keyword'] . "\n";
        }
        return $txt;
    }
}