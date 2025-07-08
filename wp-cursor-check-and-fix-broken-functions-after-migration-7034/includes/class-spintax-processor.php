<?php
/**
 * Advanced Spintax Processor
 * Handles content spinning and variation generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class AI_Content_Gen_Spintax_Processor {
    
    private $synonym_database;
    private $phrase_variations;
    
    public function __construct() {
        $this->synonym_database = $this->build_synonym_database();
        $this->phrase_variations = $this->build_phrase_variations();
    }
    
    /**
     * Apply spintax to content
     */
    public function apply_spintax($content, $params = array()) {
        $defaults = array(
            'spin_intensity' => 'medium', // low, medium, high
            'preserve_structure' => true,
            'spin_sentences' => true,
            'spin_paragraphs' => false,
            'minimum_variations' => 3,
            'maximum_variations' => 8
        );
        
        $params = wp_parse_args($params, $defaults);
        
        // Step 1: Parse existing spintax
        $content = $this->parse_spintax($content);
        
        // Step 2: Apply word-level spinning
        $content = $this->apply_word_spinning($content, $params);
        
        // Step 3: Apply phrase-level spinning
        $content = $this->apply_phrase_spinning($content, $params);
        
        // Step 4: Apply sentence-level spinning if enabled
        if ($params['spin_sentences']) {
            $content = $this->apply_sentence_spinning($content, $params);
        }
        
        // Step 5: Apply paragraph-level spinning if enabled
        if ($params['spin_paragraphs']) {
            $content = $this->apply_paragraph_spinning($content, $params);
        }
        
        return $content;
    }
    
    /**
     * Parse existing spintax in content
     */
    private function parse_spintax($content) {
        // Find and process existing spintax {option1|option2|option3}
        return preg_replace_callback('/\{([^}]+)\}/', function($matches) {
            $options = explode('|', $matches[1]);
            return $options[array_rand($options)];
        }, $content);
    }
    
    /**
     * Apply word-level spinning using synonyms
     */
    private function apply_word_spinning($content, $params) {
        $intensity_levels = array(
            'low' => 0.1,    // 10% of words
            'medium' => 0.25, // 25% of words
            'high' => 0.4     // 40% of words
        );
        
        $spin_ratio = $intensity_levels[$params['spin_intensity']] ?? 0.25;
        
        // Split content into words while preserving HTML
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        $textNodes = $xpath->query('//text()[not(ancestor::script) and not(ancestor::style)]');
        
        foreach ($textNodes as $textNode) {
            $text = $textNode->nodeValue;
            $words = explode(' ', $text);
            $modified = false;
            
            foreach ($words as $index => $word) {
                if (rand(0, 100) / 100 <= $spin_ratio) {
                    $clean_word = strtolower(preg_replace('/[^\w]/', '', $word));
                    
                    if (isset($this->synonym_database[$clean_word])) {
                        $synonyms = $this->synonym_database[$clean_word];
                        $variations = array_slice($synonyms, 0, rand($params['minimum_variations'], $params['maximum_variations']));
                        
                        if (count($variations) >= 2) {
                            // Preserve word case
                            $variations = $this->preserve_word_case($word, $variations);
                            $spintax = '{' . implode('|', $variations) . '}';
                            $words[$index] = str_replace($word, $spintax, $words[$index]);
                            $modified = true;
                        }
                    }
                }
            }
            
            if ($modified) {
                $textNode->nodeValue = implode(' ', $words);
            }
        }
        
        return $dom->saveHTML();
    }
    
    /**
     * Apply phrase-level spinning
     */
    private function apply_phrase_spinning($content, $params) {
        foreach ($this->phrase_variations as $original => $variations) {
            if (stripos($content, $original) !== false) {
                $all_options = array_merge(array($original), $variations);
                $spintax = '{' . implode('|', $all_options) . '}';
                $content = str_ireplace($original, $spintax, $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Apply sentence-level spinning
     */
    private function apply_sentence_spinning($content, $params) {
        // Split into sentences
        $sentences = preg_split('/([.!?]+)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        for ($i = 0; $i < count($sentences) - 1; $i += 2) {
            $sentence = trim($sentences[$i]);
            
            if (strlen($sentence) > 20 && rand(0, 100) < 30) { // 30% chance
                $variations = $this->generate_sentence_variations($sentence);
                
                if (count($variations) >= 2) {
                    $spintax = '{' . implode('|', $variations) . '}';
                    $sentences[$i] = $spintax;
                }
            }
        }
        
        return implode('', $sentences);
    }
    
    /**
     * Apply paragraph-level spinning
     */
    private function apply_paragraph_spinning($content, $params) {
        $paragraphs = explode("\n\n", $content);
        
        foreach ($paragraphs as $index => $paragraph) {
            if (strlen(trim($paragraph)) > 100 && rand(0, 100) < 20) { // 20% chance
                $variations = $this->generate_paragraph_variations($paragraph);
                
                if (count($variations) >= 2) {
                    $spintax = '{' . implode('|', $variations) . '}';
                    $paragraphs[$index] = $spintax;
                }
            }
        }
        
        return implode("\n\n", $paragraphs);
    }
    
    /**
     * Generate sentence variations
     */
    private function generate_sentence_variations($sentence) {
        $variations = array($sentence);
        
        // Variation 1: Passive to active voice conversion
        $passive_variation = $this->convert_passive_to_active($sentence);
        if ($passive_variation !== $sentence) {
            $variations[] = $passive_variation;
        }
        
        // Variation 2: Rearrange clause order
        $rearranged = $this->rearrange_clauses($sentence);
        if ($rearranged !== $sentence) {
            $variations[] = $rearranged;
        }
        
        // Variation 3: Add transitional phrases
        $with_transition = $this->add_transitional_phrases($sentence);
        if ($with_transition !== $sentence) {
            $variations[] = $with_transition;
        }
        
        return array_unique($variations);
    }
    
    /**
     * Generate paragraph variations
     */
    private function generate_paragraph_variations($paragraph) {
        $variations = array($paragraph);
        
        // Variation 1: Reverse sentence order
        $sentences = preg_split('/([.!?]+)/', $paragraph, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (count($sentences) > 4) {
            $reversed_sentences = array();
            for ($i = count($sentences) - 2; $i >= 0; $i -= 2) {
                $reversed_sentences[] = $sentences[$i] . ($sentences[$i + 1] ?? '');
            }
            $variations[] = implode(' ', $reversed_sentences);
        }
        
        // Variation 2: Add connecting words
        $with_connectors = $this->add_paragraph_connectors($paragraph);
        if ($with_connectors !== $paragraph) {
            $variations[] = $with_connectors;
        }
        
        return array_unique($variations);
    }
    
    /**
     * Preserve word case when applying synonyms
     */
    private function preserve_word_case($original_word, $variations) {
        $preserved = array();
        
        foreach ($variations as $variation) {
            if (ctype_upper($original_word)) {
                $preserved[] = strtoupper($variation);
            } elseif (ctype_upper(substr($original_word, 0, 1))) {
                $preserved[] = ucfirst(strtolower($variation));
            } else {
                $preserved[] = strtolower($variation);
            }
        }
        
        return $preserved;
    }
    
    /**
     * Convert passive voice to active voice
     */
    private function convert_passive_to_active($sentence) {
        // Simple passive to active conversions
        $passive_patterns = array(
            '/is ([\w\s]+) by/' => 'actively involves',
            '/was ([\w\s]+) by/' => 'previously involved',
            '/are ([\w\s]+) by/' => 'actively involve',
            '/were ([\w\s]+) by/' => 'previously involved'
        );
        
        foreach ($passive_patterns as $pattern => $replacement) {
            $sentence = preg_replace($pattern, $replacement, $sentence);
        }
        
        return $sentence;
    }
    
    /**
     * Rearrange clauses in sentence
     */
    private function rearrange_clauses($sentence) {
        // Simple clause rearrangement for sentences with commas
        if (substr_count($sentence, ',') >= 1) {
            $parts = explode(',', $sentence);
            if (count($parts) >= 2) {
                // Move first clause to end
                $first_clause = array_shift($parts);
                $parts[] = trim($first_clause);
                return implode(', ', $parts);
            }
        }
        
        return $sentence;
    }
    
    /**
     * Add transitional phrases to sentences
     */
    private function add_transitional_phrases($sentence) {
        $transitions = array(
            'Furthermore, ',
            'Additionally, ',
            'Moreover, ',
            'In addition, ',
            'Importantly, ',
            'Notably, ',
            'Specifically, '
        );
        
        // Add transition to beginning of sentence (randomly)
        if (rand(0, 1) && !preg_match('/^(Furthermore|Additionally|Moreover|In addition|Importantly|Notably|Specifically),/', $sentence)) {
            return $transitions[array_rand($transitions)] . lcfirst($sentence);
        }
        
        return $sentence;
    }
    
    /**
     * Add paragraph connectors
     */
    private function add_paragraph_connectors($paragraph) {
        $connectors = array(
            'As a result, ',
            'Consequently, ',
            'Therefore, ',
            'Hence, ',
            'Thus, ',
            'For this reason, '
        );
        
        $sentences = explode('. ', $paragraph);
        if (count($sentences) > 1) {
            // Add connector to second sentence
            $sentences[1] = $connectors[array_rand($connectors)] . lcfirst($sentences[1]);
            return implode('. ', $sentences);
        }
        
        return $paragraph;
    }
    
    /**
     * Build comprehensive synonym database
     */
    private function build_synonym_database() {
        return array(
            // Action words
            'create' => array('create', 'build', 'develop', 'generate', 'produce', 'establish', 'form', 'construct'),
            'make' => array('make', 'create', 'produce', 'generate', 'craft', 'build', 'form'),
            'use' => array('use', 'utilize', 'employ', 'apply', 'implement', 'leverage', 'adopt'),
            'get' => array('get', 'obtain', 'acquire', 'secure', 'gain', 'receive', 'attain'),
            'find' => array('find', 'discover', 'locate', 'identify', 'uncover', 'detect', 'spot'),
            'show' => array('show', 'display', 'demonstrate', 'reveal', 'present', 'exhibit', 'illustrate'),
            'help' => array('help', 'assist', 'support', 'aid', 'guide', 'facilitate', 'enable'),
            'improve' => array('improve', 'enhance', 'upgrade', 'optimize', 'refine', 'boost', 'elevate'),
            'increase' => array('increase', 'boost', 'enhance', 'expand', 'grow', 'amplify', 'escalate'),
            'reduce' => array('reduce', 'decrease', 'minimize', 'lower', 'cut', 'diminish', 'lessen'),
            
            // Descriptive words
            'good' => array('good', 'excellent', 'great', 'outstanding', 'superior', 'quality', 'effective'),
            'bad' => array('bad', 'poor', 'inferior', 'substandard', 'inadequate', 'unsatisfactory'),
            'big' => array('big', 'large', 'huge', 'massive', 'enormous', 'substantial', 'significant'),
            'small' => array('small', 'tiny', 'compact', 'minor', 'little', 'minimal', 'modest'),
            'fast' => array('fast', 'quick', 'rapid', 'swift', 'speedy', 'efficient', 'prompt'),
            'slow' => array('slow', 'gradual', 'steady', 'deliberate', 'measured', 'unhurried'),
            'easy' => array('easy', 'simple', 'straightforward', 'effortless', 'uncomplicated', 'basic'),
            'hard' => array('hard', 'difficult', 'challenging', 'complex', 'demanding', 'tough'),
            'new' => array('new', 'fresh', 'recent', 'latest', 'modern', 'contemporary', 'current'),
            'old' => array('old', 'traditional', 'established', 'conventional', 'classic', 'legacy'),
            
            // Business terms
            'strategy' => array('strategy', 'approach', 'method', 'plan', 'technique', 'system', 'framework'),
            'solution' => array('solution', 'answer', 'resolution', 'fix', 'remedy', 'approach', 'method'),
            'business' => array('business', 'company', 'organization', 'enterprise', 'firm', 'corporation'),
            'customer' => array('customer', 'client', 'consumer', 'user', 'buyer', 'patron', 'end-user'),
            'product' => array('product', 'item', 'offering', 'solution', 'service', 'tool', 'resource'),
            'service' => array('service', 'offering', 'solution', 'support', 'assistance', 'help'),
            'market' => array('market', 'industry', 'sector', 'field', 'marketplace', 'domain'),
            'growth' => array('growth', 'expansion', 'development', 'progress', 'advancement', 'improvement'),
            
            // Technology terms
            'software' => array('software', 'application', 'program', 'tool', 'platform', 'system'),
            'website' => array('website', 'site', 'platform', 'portal', 'web application', 'online presence'),
            'data' => array('data', 'information', 'statistics', 'metrics', 'analytics', 'insights'),
            'online' => array('online', 'digital', 'web-based', 'internet-based', 'virtual', 'cloud-based'),
            'technology' => array('technology', 'tech', 'innovation', 'advancement', 'system', 'solution'),
            
            // Common adjectives
            'important' => array('important', 'crucial', 'vital', 'essential', 'critical', 'significant', 'key'),
            'popular' => array('popular', 'trending', 'favored', 'preferred', 'sought-after', 'in-demand'),
            'effective' => array('effective', 'successful', 'efficient', 'productive', 'powerful', 'impactful'),
            'simple' => array('simple', 'easy', 'straightforward', 'basic', 'uncomplicated', 'clear'),
            'complex' => array('complex', 'complicated', 'intricate', 'sophisticated', 'advanced', 'detailed'),
            'free' => array('free', 'complimentary', 'no-cost', 'gratis', 'without charge', 'at no cost'),
            'cheap' => array('cheap', 'affordable', 'budget-friendly', 'low-cost', 'economical', 'inexpensive'),
            'expensive' => array('expensive', 'costly', 'premium', 'high-end', 'luxury', 'pricey'),
            
            // Process words
            'process' => array('process', 'procedure', 'method', 'approach', 'technique', 'system', 'workflow'),
            'step' => array('step', 'stage', 'phase', 'procedure', 'action', 'measure', 'operation'),
            'guide' => array('guide', 'tutorial', 'manual', 'handbook', 'instructions', 'roadmap'),
            'method' => array('method', 'approach', 'technique', 'strategy', 'way', 'procedure', 'system'),
            'tool' => array('tool', 'instrument', 'resource', 'utility', 'application', 'solution'),
            
            // Time-related
            'now' => array('now', 'currently', 'presently', 'today', 'at present', 'right now'),
            'future' => array('future', 'upcoming', 'forthcoming', 'ahead', 'down the line', 'later'),
            'past' => array('past', 'previous', 'former', 'earlier', 'prior', 'before'),
            'quickly' => array('quickly', 'rapidly', 'swiftly', 'promptly', 'speedily', 'efficiently'),
            'recently' => array('recently', 'lately', 'newly', 'just', 'not long ago', 'fresh')
        );
    }
    
    /**
     * Build phrase variations database
     */
    private function build_phrase_variations() {
        return array(
            // Common phrases
            'in order to' => array('to', 'so as to', 'with the aim of', 'for the purpose of'),
            'due to the fact that' => array('because', 'since', 'as', 'given that'),
            'at this point in time' => array('now', 'currently', 'at present', 'today'),
            'in the event that' => array('if', 'should', 'in case', 'when'),
            'for the reason that' => array('because', 'since', 'as', 'due to'),
            'in spite of the fact that' => array('although', 'despite', 'even though', 'regardless of'),
            
            // Business phrases
            'it is important to note that' => array('notably', 'importantly', 'it should be noted', 'worth mentioning'),
            'as a result of' => array('because of', 'due to', 'owing to', 'thanks to'),
            'in addition to' => array('besides', 'along with', 'as well as', 'together with'),
            'on the other hand' => array('however', 'conversely', 'alternatively', 'in contrast'),
            'for example' => array('for instance', 'such as', 'like', 'including'),
            
            // Technical phrases
            'in order to achieve' => array('to achieve', 'to accomplish', 'to reach', 'to attain'),
            'it is possible to' => array('you can', 'one can', 'it\'s feasible to', 'you may'),
            'there are many ways to' => array('various methods exist to', 'multiple approaches can', 'several techniques help'),
            'it is necessary to' => array('you must', 'you need to', 'it\'s essential to', 'you should'),
            
            // Transition phrases
            'first and foremost' => array('primarily', 'first of all', 'most importantly', 'initially'),
            'last but not least' => array('finally', 'lastly', 'in conclusion', 'ultimately'),
            'as previously mentioned' => array('as noted earlier', 'as discussed', 'as stated before'),
            'in conclusion' => array('to summarize', 'in summary', 'finally', 'ultimately', 'to conclude')
        );
    }
    
    /**
     * Generate unique content variations
     */
    public function generate_variations($content, $count = 3) {
        $variations = array();
        
        for ($i = 0; $i < $count; $i++) {
            $variation = $this->apply_spintax($content, array(
                'spin_intensity' => 'medium',
                'spin_sentences' => true,
                'minimum_variations' => 2,
                'maximum_variations' => 5
            ));
            
            // Parse the spintax to get unique version
            $variation = $this->parse_spintax($variation);
            $variations[] = $variation;
        }
        
        return $variations;
    }
    
    /**
     * Calculate content uniqueness percentage
     */
    public function calculate_uniqueness($original, $spun) {
        $original_words = str_word_count(strtolower($original), 1);
        $spun_words = str_word_count(strtolower($spun), 1);
        
        $common_words = array_intersect($original_words, $spun_words);
        $total_words = count($original_words);
        
        if ($total_words === 0) return 0;
        
        $uniqueness = (($total_words - count($common_words)) / $total_words) * 100;
        return round($uniqueness, 2);
    }
    
    /**
     * Clean spintax format for final output
     */
    public function clean_spintax($content) {
        // Remove any remaining spintax brackets
        return preg_replace('/\{[^}]+\}/', '', $content);
    }
}