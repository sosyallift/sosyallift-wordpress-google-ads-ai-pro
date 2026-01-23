<?php
namespace SosyalliftAIPro\Modules\Intent;

use SosyalliftAIPro\Core\CacheManager;
use SosyalliftAIPro\Core\Logger;

class IntentDetector {
    private $patterns = [
        'commercial' => [
            'tr' => [
                'satın al', 'fiyat', 'ucuz', 'pahalı', 'indirim', 'kampanya', 'taksit',
                'ödeme', 'sipariş ver', 'sepete ekle', 'alışveriş', 'bedava kargo',
                'stokta', 'fırsat', 'son dakika', 'numara', 'rezervasyon', 'bilet',
                'kayıt ol', 'üyelik', 'abonelik', 'kontenjan', 'kupon', 'hediye',
                'ödeme seçenekleri', 'havale', 'eft', 'kredi kartı', 'kapıda ödeme',
            ],
            'en' => [
                'buy', 'price', 'cheap', 'expensive', 'discount', 'sale', 'deal',
                'payment', 'order now', 'add to cart', 'shopping', 'free shipping',
                'in stock', 'offer', 'last minute', 'phone number', 'reservation',
                'ticket', 'sign up', 'membership', 'subscription', 'quota', 'coupon',
                'gift', 'payment options', 'wire transfer', 'credit card', 'cash on delivery',
            ],
        ],
        'informational' => [
            'tr' => [
                'nasıl', 'nedir', 'ne demek', 'anlamı', 'tanım', 'açıklama',
                'rehber', 'tutorial', 'öğretici', 'eğitim', 'kurs', 'ders',
                'haber', 'güncelleme', 'duyuru', 'blog', 'makale', 'yazı',
                'araştırma', 'inceleme', 'analiz', 'rapor', 'istatistik',
                'sık sorulan sorular', 'sss', 'yardım', 'destek', 'kılavuz',
            ],
            'en' => [
                'how to', 'what is', 'meaning', 'definition', 'explanation',
                'guide', 'tutorial', 'educational', 'training', 'course', 'lesson',
                'news', 'update', 'announcement', 'blog', 'article', 'writing',
                'research', 'review', 'analysis', 'report', 'statistics',
                'faq', 'help', 'support', 'manual',
            ],
        ],
        'navigational' => [
            'tr' => [
                'giriş yap', 'üye ol', 'iletişim', 'adres', 'harita', 'konum',
                'telefon', 'email', 'whatsapp', 'instagram', 'facebook', 'twitter',
                'youtube', 'linkedin', 'web sitesi', 'anasayfa', 'iletişim formu',
                'şube', 'ofis', 'mağaza', 'showroom', 'bayi', 'yetkili servis',
            ],
            'en' => [
                'login', 'sign up', 'contact', 'address', 'map', 'location',
                'phone', 'email', 'whatsapp', 'instagram', 'facebook', 'twitter',
                'youtube', 'linkedin', 'website', 'homepage', 'contact form',
                'branch', 'office', 'store', 'showroom', 'dealer', 'authorized service',
            ],
        ],
        'transactional' => [
            'tr' => [
                'giriş yap', 'kayıt ol', 'üyelik girişi', 'şifremi unuttum',
                'hesabım', 'siparişlerim', 'favorilerim', 'sepetim', 'ödeme',
                'fatura', 'makbuz', 'iptal', 'iade', 'değişim', 'takip',
                'kargo takip', 'kargom nerede', 'teslimat', 'adres güncelle',
            ],
            'en' => [
                'log in', 'register', 'membership login', 'forgot password',
                'my account', 'my orders', 'my favorites', 'my cart', 'payment',
                'invoice', 'receipt', 'cancel', 'return', 'exchange', 'track',
                'track shipment', 'where is my package', 'delivery', 'update address',
            ],
        ],
        'comparison' => [
            'tr' => [
                'vs', 'karşılaştırma', 'kıyas', 'fark', 'avantaj', 'dezavantaj',
                'artıları', 'eksileri', 'en iyi', 'hangisi', 'ne kadar fark',
                'ölçüm', 'test', 'performans', 'sonuç', 'puan', 'derecelendirme',
            ],
            'en' => [
                'vs', 'compare', 'comparison', 'difference', 'advantage', 'disadvantage',
                'pros', 'cons', 'best', 'which one', 'how much difference',
                'measurement', 'test', 'performance', 'result', 'score', 'rating',
            ],
        ],
    ];
    
    private $language = 'tr';
    private $cache;
    
    public function __construct(string $language = 'tr') {
        $this->language = in_array($language, ['tr', 'en']) ? $language : 'tr';
        $this->cache = CacheManager::get_instance();
    }
    
    public function analyze(string $query, array $context = []): array {
        $cache_key = 'intent_' . md5($query . $this->language . json_encode($context));
        
        if ($cached = $this->cache->get($cache_key)) {
            return $cached;
        }
        
        $query_lower = mb_strtolower($query, 'UTF-8');
        
        // Calculate intent scores
        $scores = [
            'commercial'    => $this->calculate_commercial_score($query_lower, $context),
            'informational' => $this->calculate_informational_score($query_lower, $context),
            'navigational'  => $this->calculate_navigational_score($query_lower, $context),
            'transactional' => $this->calculate_transactional_score($query_lower, $context),
            'comparison'    => $this->calculate_comparison_score($query_lower, $context),
        ];
        
        // Adjust scores based on context
        if (!empty($context)) {
            $scores = $this->apply_context_adjustments($scores, $context);
        }
        
        // Add AI-based analysis for complex queries
        if ($this->is_complex_query($query_lower)) {
            $scores = $this->apply_ai_analysis($scores, $query_lower);
        }
        
        // Normalize scores
        $total = array_sum($scores);
        if ($total > 0) {
            foreach ($scores as &$score) {
                $score = round(($score / $total) * 100, 2);
            }
        }
        
        // Determine primary intent
        arsort($scores);
        $primary_intent = array_key_first($scores);
        $confidence = $scores[$primary_intent];
        
        // Check for mixed intent
        $mixed = $this->detect_mixed_intent($scores);
        
        $result = [
            'query'         => $query,
            'language'      => $this->language,
            'primary'       => $primary_intent,
            'confidence'    => $confidence,
            'scores'        => $scores,
            'mixed'         => $mixed,
            'suggestions'   => $this->generate_suggestions($query_lower, $primary_intent, $scores),
            'commercial_potential' => $this->calculate_commercial_potential($scores),
            'keywords'      => $this->extract_keywords($query_lower),
            'entities'      => $this->extract_entities($query_lower),
        ];
        
        // Cache for 1 hour
        $this->cache->set($cache_key, $result, 3600);
        
        Logger::debug('Intent analysis completed', [
            'query' => $query,
            'result' => $result,
        ]);
        
        return $result;
    }
    
    public function analyze_bulk(array $queries, array $options = []): array {
        $options = wp_parse_args($options, [
            'parallel'      => true,
            'batch_size'    => 50,
            'timeout'       => 30,
        ]);
        
        $results = [];
        
        if ($options['parallel'] && function_exists('curl_multi_init')) {
            $results = $this->analyze_parallel($queries, $options);
        } else {
            foreach (array_chunk($queries, $options['batch_size']) as $batch) {
                foreach ($batch as $query) {
                    $results[$query] = $this->analyze($query);
                }
            }
        }
        
        // Generate aggregated insights
        $insights = $this->generate_bulk_insights($results);
        
        return [
            'results'   => $results,
            'insights'  => $insights,
            'summary'   => $this->generate_summary($insights),
        ];
    }
    
    public function predict_conversion(string $query, array $user_data = []): array {
        $intent_analysis = $this->analyze($query);
        $commercial_score = $intent_analysis['scores']['commercial'] ?? 0;
        
        // Base conversion probability
        $base_probability = $commercial_score / 100;
        
        // Adjust based on user data if available
        if (!empty($user_data)) {
            $base_probability = $this->adjust_by_user_data($base_probability, $user_data);
        }
        
        // Adjust based on query characteristics
        $query_factors = $this->analyze_query_factors($query);
        $adjusted_probability = $base_probability * $query_factors['conversion_factor'];
        
        // Calculate expected value
        $expected_value = $this->calculate_expected_value($adjusted_probability, $intent_analysis);
        
        return [
            'query'                     => $query,
            'conversion_probability'    => round($adjusted_probability * 100, 2),
            'expected_value'            => round($expected_value, 2),
            'confidence_interval'       => [
                'low'   => round(max(0, $adjusted_probability - 0.1) * 100, 2),
                'high'  => round(min(1, $adjusted_probability + 0.1) * 100, 2),
            ],
            'factors'                   => [
                'intent_strength'       => $commercial_score,
                'query_complexity'      => $query_factors['complexity'],
                'urgency_indicators'    => $query_factors['urgency'],
                'specificity'           => $query_factors['specificity'],
                'competition_level'     => $this->estimate_competition($query),
            ],
            'recommendations'           => $this->generate_conversion_recommendations(
                $adjusted_probability,
                $intent_analysis,
                $user_data
            ),
        ];
    }
    
    private function calculate_commercial_score(string $query, array $context): float {
        $score = 0;
        $patterns = $this->patterns['commercial'][$this->language];
        
        foreach ($patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                $score += 10;
                
                // Bonus for exact matches at beginning
                if (strpos($query, $pattern) === 0) {
                    $score += 5;
                }
                
                // Bonus for price indicators
                if (preg_match('/\d+(\s*(tl|try|usd|eur|£|€|\$))/iu', $query)) {
                    $score += 15;
                }
            }
        }
        
        // Check for urgency indicators
        $urgency_terms = ['acil', 'hemen', 'bugün', 'şimdi', 'derhal', 'today', 'now', 'immediately'];
        foreach ($urgency_terms as $term) {
            if (strpos($query, $term) !== false) {
                $score += 8;
            }
        }
        
        // Check for location specificity
        if (preg_match('/\b(istanbul|ankara|izmir|bursa|antalya|adana)\b/iu', $query)) {
            $score += 5;
        }
        
        return min($score, 100);
    }
    
    private function calculate_informational_score(string $query, array $context): float {
        $score = 0;
        $patterns = $this->patterns['informational'][$this->language];
        
        foreach ($patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                $score += 10;
                
                // Bonus for question words at beginning
                $question_words = ['nasıl', 'neden', 'niçin', 'ne zaman', 'nere', 'kim', 'what', 'why', 'when', 'where', 'who'];
                foreach ($question_words as $word) {
                    if (strpos($query, $word) === 0) {
                        $score += 5;
                        break;
                    }
                }
            }
        }
        
        // Check for length (informational queries tend to be longer)
        $word_count = str_word_count($query);
        if ($word_count > 4) {
            $score += min(($word_count - 4) * 2, 20);
        }
        
        return min($score, 100);
    }
    
    private function calculate_navigational_score(string $query, array $context): float {
        $score = 0;
        $patterns = $this->patterns['navigational'][$this->language];
        
        foreach ($patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                $score += 12;
                
                // Bonus for brand names
                if ($this->contains_brand_name($query)) {
                    $score += 10;
                }
            }
        }
        
        // Check for exact domain matches
        $domain = parse_url(site_url(), PHP_URL_HOST);
        $domain_no_www = str_replace('www.', '', $domain);
        
        if (strpos($query, $domain_no_www) !== false || strpos($query, str_replace('.', ' ', $domain_no_www)) !== false) {
            $score += 20;
        }
        
        return min($score, 100);
    }
    
    private function calculate_transactional_score(string $query, array $context): float {
        $score = 0;
        $patterns = $this->patterns['transactional'][$this->language];
        
        foreach ($patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                $score += 10;
            }
        }
        
        // Check for account-related terms
        $account_terms = ['hesabım', 'siparişlerim', 'favorilerim', 'my account', 'my orders', 'my favorites'];
        foreach ($account_terms as $term) {
            if (strpos($query, $term) !== false) {
                $score += 15;
            }
        }
        
        return min($score, 100);
    }
    
    private function calculate_comparison_score(string $query, array $context): float {
        $score = 0;
        $patterns = $this->patterns['comparison'][$this->language];
        
        foreach ($patterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                $score += 10;
                
                // Bonus for "vs" pattern
                if (strpos($query, ' vs ') !== false || strpos($query, ' karşı ') !== false) {
                    $score += 10;
                }
            }
        }
        
        // Check for multiple product mentions
        $product_indicators = ['iphone', 'samsung', 'xiaomi', 'huawei', 'lg', 'sony'];
        $mentioned_products = 0;
        
        foreach ($product_indicators as $product) {
            if (stripos($query, $product) !== false) {
                $mentioned_products++;
            }
        }
        
        if ($mentioned_products >= 2) {
            $score += $mentioned_products * 5;
        }
        
        return min($score, 100);
    }
    
    private function contains_brand_name(string $query): bool {
        $brands = get_option('sl_ai_pro_known_brands', []);
        
        if (empty($brands)) {
            // Extract brands from site content
            $brands = $this->extract_brands_from_site();
            update_option('sl_ai_pro_known_brands', $brands);
        }
        
        foreach ($brands as $brand) {
            if (stripos($query, $brand) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function extract_brands_from_site(): array {
        $brands = [];
        
        // Extract from site title and tagline
        $site_title = get_bloginfo('name');
        $tagline = get_bloginfo('description');
        
        if (!empty($site_title)) {
            $brands[] = strtolower($site_title);
            $words = explode(' ', $site_title);
            if (count($words) > 1) {
                $brands[] = strtolower($words[0]); // First word as potential brand
            }
        }
        
        // Extract from product categories (if WooCommerce is active)
        if (function_exists('wc_get_product_category_list')) {
            $categories = get_terms([
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'number'     => 20,
            ]);
            
            foreach ($categories as $category) {
                $brands[] = strtolower($category->name);
            }
        }
        
        // Extract from post tags
        $tags = get_tags(['number' => 50]);
        foreach ($tags as $tag) {
            $brands[] = strtolower($tag->name);
        }
        
        return array_unique(array_filter($brands));
    }
    
    private function apply_context_adjustments(array $scores, array $context): array {
        // Adjust based on source
        if (isset($context['source'])) {
            switch ($context['source']) {
                case 'google_ads':
                    $scores['commercial'] *= 1.2;
                    break;
                case 'organic_search':
                    $scores['informational'] *= 1.1;
                    break;
                case 'direct':
                    $scores['navigational'] *= 1.3;
                    break;
            }
        }
        
        // Adjust based on device
        if (isset($context['device'])) {
            if ($context['device'] === 'mobile') {
                $scores['transactional'] *= 1.15;
                $scores['navigational'] *= 1.1;
            }
        }
        
        // Adjust based on time of day
        if (isset($context['hour'])) {
            $hour = (int) $context['hour'];
            if ($hour >= 9 && $hour <= 17) {
                $scores['commercial'] *= 1.1; // Business hours
            } elseif ($hour >= 20 || $hour <= 6) {
                $scores['informational'] *= 1.15; // Evening/night
            }
        }
        
        // Ensure scores don't exceed reasonable limits
        foreach ($scores as &$score) {
            $score = min($score, 100);
        }
        
        return $scores;
    }
    
    private function is_complex_query(string $query): bool {
        $word_count = str_word_count($query);
        $has_question = preg_match('/\?$/u', $query);
        $has_multiple_entities = preg_match_all('/\b(\w+)\b/u', $query) > 3;
        
        return $word_count > 5 || $has_question || $has_multiple_entities;
    }
    
    private function apply_ai_analysis(array $scores, string $query): array {
        // This is a simplified version. In production, you'd integrate with an AI service
        // like OpenAI GPT, Google Natural Language API, or a custom ML model
        
        // For now, use rule-based enhancements
        $enhancements = $this->rule_based_enhancements($query);
        
        foreach ($enhancements as $intent => $boost) {
            if (isset($scores[$intent])) {
                $scores[$intent] = min($scores[$intent] + $boost, 100);
            }
        }
        
        return $scores;
    }
    
    private function rule_based_enhancements(string $query): array {
        $enhancements = [];
        
        // Question analysis
        if (preg_match('/^(nasıl|neden|niçin|ne zaman|nere|kim|hangi|kaç)\b/iu', $query)) {
            $enhancements['informational'] = 15;
        }
        
        // Urgency detection
        if (preg_match('/\b(acil|hemen|bugün|şimdi|derhal|today|now|asap)\b/iu', $query)) {
            $enhancements['commercial'] = 10;
            $enhancements['transactional'] = 5;
        }
        
        // Location specificity
        if (preg_match('/\b(istanbul|ankara|izmir|bursa|antalya|adana|konya|mersin)\b/iu', $query)) {
            $enhancements['commercial'] = 8;
            $enhancements['navigational'] = 5;
        }
        
        // Price mention
        if (preg_match('/\d+\s*(tl|try|usd|eur|£|€|\$)/iu', $query)) {
            $enhancements['commercial'] = 20;
        }
        
        // Comparison indicators
        if (preg_match('/\b(vs|veya|ya da|karşı|kıyas|comparison|versus)\b/iu', $query)) {
            $enhancements['comparison'] = 15;
        }
        
        return $enhancements;
    }
    
    private function detect_mixed_intent(array $scores): array {
        $mixed = [];
        $threshold = 25; // Minimum score to be considered mixed
        
        foreach ($scores as $intent => $score) {
            if ($score >= $threshold) {
                $mixed[$intent] = $score;
            }
        }
        
        // If more than one intent meets threshold, it's mixed
        if (count($mixed) > 1) {
            arsort($mixed);
            return [
                'is_mixed'  => true,
                'intents'   => $mixed,
                'dominant'  => array_key_first($mixed),
                'secondary' => array_key_slice(array_keys($mixed), 1, 1)[0] ?? null,
            ];
        }
        
        return ['is_mixed' => false];
    }
    
    private function generate_suggestions(string $query, string $primary_intent, array $scores): array {
        $suggestions = [];
        
        switch ($primary_intent) {
            case 'commercial':
                $suggestions = [
                    'bid_adjustment'    => '+20%',
                    'ad_copy_focus'     => 'price, urgency, benefits',
                    'landing_page'      => 'product page with clear CTA',
                    'negative_keywords' => $this->suggest_negative_keywords($query, 'commercial'),
                    'match_type'        => 'exact or phrase',
                    'extension_use'     => ['price', 'location', 'callout'],
                ];
                break;
                
            case 'informational':
                $suggestions = [
                    'bid_adjustment'    => '-10%',
                    'ad_copy_focus'     => 'information, guides, solutions',
                    'landing_page'      => 'blog post or guide page',
                    'negative_keywords' => $this->suggest_negative_keywords($query, 'informational'),
                    'match_type'        => 'broad modified',
                    'extension_use'     => ['sitelink', 'callout', 'structured_snippet'],
                ];
                break;
                
            case 'navigational':
                $suggestions = [
                    'bid_adjustment'    => '+15%',
                    'ad_copy_focus'     => 'brand, trust, direct navigation',
                    'landing_page'      => 'homepage or specific brand page',
                    'negative_keywords' => [],
                    'match_type'        => 'exact',
                    'extension_use'     => ['sitelink', 'call', 'location'],
                ];
                break;
        }
        
        // Add general suggestions based on scores
        if ($scores['transactional'] > 30) {
            $suggestions['transactional_focus'] = 'Simplify conversion path';
        }
        
        if ($scores['comparison'] > 30) {
            $suggestions['comparison_content'] = 'Add comparison charts or tables';
        }
        
        return $suggestions;
    }
    
    private function suggest_negative_keywords(string $query, string $intent): array {
        $negatives = [];
        $words = explode(' ', strtolower($query));
        
        switch ($intent) {
            case 'commercial':
                // For commercial intent, exclude informational terms
                $info_terms = ['nasıl', 'nedir', 'ne demek', 'anlamı', 'how', 'what', 'meaning'];
                foreach ($info_terms as $term) {
                    if (!in_array($term, $words)) {
                        $negatives[] = $term;
                    }
                }
                break;
                
            case 'informational':
                // For informational intent, exclude commercial terms
                $commercial_terms = ['fiyat', 'satın al', 'ucuz', 'price', 'buy', 'cheap'];
                foreach ($commercial_terms as $term) {
                    if (!in_array($term, $words)) {
                        $negatives[] = $term;
                    }
                }
                break;
        }
        
        return array_unique($negatives);
    }
    
    private function calculate_commercial_potential(array $scores): array {
        $commercial_score = $scores['commercial'] ?? 0;
        $transactional_score = $scores['transactional'] ?? 0;
        
        $total_potential = ($commercial_score * 0.7) + ($transactional_score * 0.3);
        
        if ($total_potential >= 70) {
            $level = 'high';
            $color = 'success';
        } elseif ($total_potential >= 40) {
            $level = 'medium';
            $color = 'warning';
        } else {
            $level = 'low';
            $color = 'error';
        }
        
        return [
            'score'     => round($total_potential, 2),
            'level'     => $level,
            'color'     => $color,
            'recommended_bid_multiplier' => $this->calculate_bid_multiplier($total_potential),
        ];
    }
    
    private function calculate_bid_multiplier(float $potential): float {
        if ($potential >= 80) return 1.5;
        if ($potential >= 60) return 1.3;
        if ($potential >= 40) return 1.1;
        if ($potential >= 20) return 1.0;
        return 0.8;
    }
    
    private function extract_keywords(string $query): array {
        $stop_words = $this->get_stop_words();
        $words = preg_split('/\s+/', strtolower($query));
        
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        // Add bigrams and trigrams
        $ngrams = $this->extract_ngrams($keywords);
        
        return [
            'unigrams'  => array_values($keywords),
            'bigrams'   => $ngrams['bigrams'],
            'trigrams'  => $ngrams['trigrams'],
            'entities'  => $this->identify_entities($keywords),
        ];
    }
    
    private function get_stop_words(): array {
        $stop_words = [
            'tr' => [
                'bir', 've', 'ile', 'için', 'bu', 'şu', 'o', 'de', 'da', 'mi', 'mı', 'mu', 'mü',
                'ama', 'fakat', 'ancak', 'lakin', 'çünkü', 'eğer', 'ise', 'ki', 'ne', 'nasıl',
                'neden', 'niçin', 'nerede', 'nereye', 'ne zaman', 'kim', 'kime', 'kimi',
                'hangisi', 'kaç', 'nasıl', 'kadar', 'gibi', 'kadar', 'sanki', 'üzere',
                'rağmen', 'karşın', 'doğru', 'göre', 'yönelik', 'ait', 'ilişkin',
            ],
            'en' => [
                'a', 'an', 'the', 'and', 'or', 'but', 'if', 'because', 'as', 'what',
                'who', 'which', 'where', 'when', 'how', 'why', 'can', 'could', 'may',
                'might', 'must', 'shall', 'should', 'will', 'would', 'this', 'that',
                'these', 'those', 'then', 'than', 'there', 'their', 'they', 'them',
                'such', 'from', 'with', 'about', 'into', 'over', 'under', 'again',
                'further', 'then', 'once', 'here', 'there', 'when', 'where', 'why',
                'how', 'all', 'any', 'both', 'each', 'few', 'more', 'most', 'other',
                'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so',
                'than', 'too', 'very', 's', 't', 'can', 'will', 'just', 'don',
            ],
        ];
        
        return $stop_words[$this->language] ?? $stop_words['en'];
    }
    
    private function extract_ngrams(array $words): array {
        $bigrams = [];
        $trigrams = [];
        
        // Extract bigrams
        for ($i = 0; $i < count($words) - 1; $i++) {
            $bigrams[] = $words[$i] . ' ' . $words[$i + 1];
        }
        
        // Extract trigrams
        for ($i = 0; $i < count($words) - 2; $i++) {
            $trigrams[] = $words[$i] . ' ' . $words[$i + 1] . ' ' . $words[$i + 2];
        }
        
        return [
            'bigrams'   => $bigrams,
            'trigrams'  => $trigrams,
        ];
    }
    
    private function identify_entities(array $words): array {
        $entities = [
            'products'  => [],
            'brands'    => [],
            'locations' => [],
            'numbers'   => [],
            'dates'     => [],
        ];
        
        $product_terms = ['telefon', 'bilgisayar', 'tv', 'laptop', 'tablet', 'phone', 'computer', 'laptop', 'tablet'];
        $location_terms = ['istanbul', 'ankara', 'izmir', 'bursa', 'antalya', 'adana'];
        
        foreach ($words as $word) {
            // Check for product terms
            if (in_array($word, $product_terms)) {
                $entities['products'][] = $word;
            }
            
            // Check for location terms
            if (in_array($word, $location_terms)) {
                $entities['locations'][] = $word;
            }
            
            // Check for numbers
            if (is_numeric($word)) {
                $entities['numbers'][] = $word;
            }
            
            // Check for brands (simplified)
            if ($this->is_likely_brand($word)) {
                $entities['brands'][] = $word;
            }
        }
        
        return $entities;
    }
    
    private function is_likely_brand(string $word): bool {
        // Simple heuristic: capitalized words that aren't common
        $common_words = ['telefon', 'bilgisayar', 'fiyat', 'nasıl', 'nedir'];
        
        return !in_array(strtolower($word), $common_words) &&
               preg_match('/^[A-Z][a-z]+$/', $word);
    }
    
    private function extract_entities(string $query): array {
        // This is a simplified entity extraction
        // In production, use NLP services or more sophisticated algorithms
        
        $entities = [
            'products'  => [],
            'brands'    => [],
            'locations' => [],
            'numbers'   => [],
            'dates'     => [],
            'prices'    => [],
        ];
        
        // Extract prices
        if (preg_match_all('/(\d+[\d.,]*)\s*(tl|try|usd|eur|£|€|\$)/iu', $query, $matches)) {
            $entities['prices'] = $matches[0];
        }
        
        // Extract numbers
        if (preg_match_all('/\b\d+\b/', $query, $matches)) {
            $entities['numbers'] = $matches[0];
        }
        
        return $entities;
    }
    
    private function analyze_parallel(array $queries, array $options): array {
        $results = [];
        $mh = curl_multi_init();
        $handles = [];
        
        // Create handles for each query
        foreach ($queries as $index => $query) {
            $handles[$index] = curl_init();
            
            // This would be an internal API endpoint in production
            $url = add_query_arg([
                'action'    => 'sl_ai_pro_analyze_intent',
                'query'     => urlencode($query),
                'language'  => $this->language,
                'nonce'     => wp_create_nonce('sl_ai_pro_parallel'),
            ], admin_url('admin-ajax.php'));
            
            curl_setopt_array($handles[$index], [
                CURLOPT_URL             => $url,
                CURLOPT_RETURNTRANSFER  => true,
                CURLOPT_TIMEOUT         => $options['timeout'],
                CURLOPT_SSL_VERIFYPEER  => false,
                CURLOPT_SSL_VERIFYHOST  => false,
            ]);
            
            curl_multi_add_handle($mh, $handles[$index]);
        }
        
        // Execute parallel requests
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        // Process results
        foreach ($handles as $index => $handle) {
            $response = curl_multi_getcontent($handle);
            $data = json_decode($response, true);
            
            if ($data && $data['success']) {
                $results[$queries[$index]] = $data['data'];
            }
            
            curl_multi_remove_handle($mh, $handle);
            curl_close($handle);
        }
        
        curl_multi_close($mh);
        
        return $results;
    }
    
    private function generate_bulk_insights(array $results): array {
        $insights = [
            'intent_distribution' => [],
            'avg_confidence'      => [],
            'commercial_potential'=> 0,
            'top_keywords'        => [],
            'common_entities'     => [],
        ];
        
        $intent_counts = [];
        $total_commercial = 0;
        $all_keywords = [];
        $all_entities = [];
        
        foreach ($results as $result) {
            $primary = $result['primary'];
            $intent_counts[$primary] = ($intent_counts[$primary] ?? 0) + 1;
            
            $total_commercial += $result['scores']['commercial'];
            
            // Collect keywords
            foreach ($result['keywords']['unigrams'] as $keyword) {
                $all_keywords[$keyword] = ($all_keywords[$keyword] ?? 0) + 1;
            }
            
            // Collect entities
            foreach ($result['entities'] as $type => $entities) {
                foreach ($entities as $entity) {
                    $all_entities[$type][$entity] = ($all_entities[$type][$entity] ?? 0) + 1;
                }
            }
        }
        
        // Calculate distribution
        $total = count($results);
        foreach ($intent_counts as $intent => $count) {
            $insights['intent_distribution'][$intent] = [
                'count' => $count,
                'percentage' => round(($count / $total) * 100, 2),
            ];
        }
        
        // Calculate average commercial potential
        $insights['commercial_potential'] = round($total_commercial / $total, 2);
        
        // Get top keywords
        arsort($all_keywords);
        $insights['top_keywords'] = array_slice($all_keywords, 0, 20, true);
        
        // Get common entities
        foreach ($all_entities as $type => $entities) {
            arsort($entities);
            $insights['common_entities'][$type] = array_slice($entities, 0, 10, true);
        }
        
        return $insights;
    }
    
    private function generate_summary(array $insights): array {
        $primary_intent = array_key_first($insights['intent_distribution']);
        $commercial_score = $insights['commercial_potential'];
        
        $summary = [
            'primary_focus' => $primary_intent,
            'commercial_score' => $commercial_score,
            'keyword_count' => count($insights['top_keywords']),
            'entity_types'  => array_keys($insights['common_entities']),
        ];
        
        // Generate recommendations
        $summary['recommendations'] = $this->generate_bulk_recommendations($insights);
        
        return $summary;
    }
    
    private function generate_bulk_recommendations(array $insights): array {
        $recommendations = [];
        
        $commercial_percentage = $insights['intent_distribution']['commercial']['percentage'] ?? 0;
        
        if ($commercial_percentage > 50) {
            $recommendations[] = "High commercial intent detected. Consider increasing bids for commercial keywords.";
            $recommendations[] = "Focus on conversion-optimized landing pages for commercial queries.";
        }
        
        if (($insights['intent_distribution']['informational']['percentage'] ?? 0) > 30) {
            $recommendations[] = "Significant informational intent. Create educational content and consider display campaigns.";
        }
        
        if (!empty($insights['common_entities']['locations'])) {
            $top_location = array_key_first($insights['common_entities']['locations']);
            $recommendations[] = "Location-specific queries detected. Consider geo-targeted campaigns for: " . $top_location;
        }
        
        if (!empty($insights['common_entities']['products'])) {
            $top_product = array_key_first($insights['common_entities']['products']);
            $recommendations[] = "Product-focused queries. Ensure product pages are optimized for: " . $top_product;
        }
        
        return $recommendations;
    }
    
    private function adjust_by_user_data(float $probability, array $user_data): float {
        $adjustment = 1.0;
        
        // Adjust based on user history
        if (isset($user_data['conversion_rate'])) {
            $adjustment *= ($user_data['conversion_rate'] / 0.03); // Normalize to 3% baseline
        }
        
        // Adjust based on device
        if (isset($user_data['device']) && $user_data['device'] === 'mobile') {
            $adjustment *= 1.1; // Mobile users convert 10% more
        }
        
        // Adjust based on time on site
        if (isset($user_data['avg_time_on_site'])) {
            if ($user_data['avg_time_on_site'] > 180) { // More than 3 minutes
                $adjustment *= 1.2;
            } elseif ($user_data['avg_time_on_site'] < 30) { // Less than 30 seconds
                $adjustment *= 0.8;
            }
        }
        
        // Adjust based for returning visitors
        if (isset($user_data['is_returning']) && $user_data['is_returning']) {
            $adjustment *= 1.3; // Returning visitors convert 30% more
        }
        
        return $probability * $adjustment;
    }
    
    private function analyze_query_factors(string $query): array {
        $factors = [
            'complexity'        => 0,
            'urgency'           => 0,
            'specificity'       => 0,
            'conversion_factor' => 1.0,
        ];
        
        // Complexity: longer queries with specific terms
        $word_count = str_word_count($query);
        $factors['complexity'] = min($word_count / 10, 1.0);
        
        // Urgency: presence of time-sensitive words
        $urgency_terms = ['acil', 'hemen', 'bugün', 'şimdi', 'today', 'now', 'asap', 'immediately'];
        foreach ($urgency_terms as $term) {
            if (stripos($query, $term) !== false) {
                $factors['urgency'] = 1;
                break;
            }
        }
        
        // Specificity: presence of numbers, brands, locations
        $specific_indicators = [
            'numbers'   => preg_match_all('/\d+/', $query),
            'brands'    => $this->contains_brand_name($query) ? 1 : 0,
            'locations' => preg_match('/\b(istanbul|ankara|izmir|bursa|antalya)\b/i', $query) ? 1 : 0,
        ];
        
        $factors['specificity'] = min(array_sum($specific_indicators) / 3, 1.0);
        
        // Calculate conversion factor
        $factors['conversion_factor'] = 
            0.5 + // Base
            ($factors['urgency'] * 0.2) +
            ($factors['specificity'] * 0.3) -
            ($factors['complexity'] * 0.1);
        
        return $factors;
    }
    
    private function estimate_competition(string $query): string {
        // Simple competition estimation based on query characteristics
        $word_count = str_word_count($query);
        $has_brand = $this->contains_brand_name($query);
        $has_location = preg_match('/\b(istanbul|ankara|izmir|bursa|antalya)\b/i', $query);
        
        if ($has_brand) {
            return 'low'; // Brand queries typically have lower competition
        }
        
        if ($word_count == 1) {
            return 'high'; // Single-word generic queries are competitive
        }
        
        if ($word_count >= 3) {
            return 'medium'; // Longer tail queries
        }
        
        if ($has_location) {
            return 'medium'; // Location-specific queries
        }
        
        return 'high';
    }
    
    private function calculate_expected_value(float $probability, array $intent_analysis): float {
        $commercial_score = $intent_analysis['scores']['commercial'] / 100;
        $avg_order_value = 100; // Base AOV, should be configurable
        
        // Adjust AOV based on intent and query
        if ($intent_analysis['primary'] === 'commercial') {
            $avg_order_value *= 1.5;
        }
        
        // Adjust based on query specificity
        if (!empty($intent_analysis['entities']['prices'])) {
            // If price is mentioned, use it as indicator
            $price = floatval(preg_replace('/[^0-9.]/', '', $intent_analysis['entities']['prices'][0]));
            if ($price > 0) {
                $avg_order_value = $price * 1.2; // Assume 20% higher than mentioned price
            }
        }
        
        return $probability * $avg_order_value;
    }
    
    private function generate_conversion_recommendations(float $probability, array $intent_analysis, array $user_data): array {
        $recommendations = [];
        
        if ($probability < 0.1) {
            $recommendations[] = "Low conversion probability. Consider focusing on awareness rather than direct conversion.";
            $recommendations[] = "Use this keyword for remarketing campaigns rather than direct response.";
        } elseif ($probability < 0.3) {
            $recommendations[] = "Moderate conversion potential. Optimize landing page for information gathering.";
            $recommendations[] = "Consider using lead magnets or newsletter signups as conversion goals.";
        } elseif ($probability < 0.6) {
            $recommendations[] = "Good conversion potential. Ensure clear CTAs and simplified checkout process.";
            $recommendations[] = "Test different ad copies emphasizing benefits and social proof.";
        } else {
            $recommendations[] = "High conversion probability. Bid aggressively and ensure inventory/supply.";
            $recommendations[] = "Implement urgency tactics (limited time offers, stock counters).";
            $recommendations[] = "Use ad extensions (price, location, call) to increase CTR.";
        }
        
        // Intent-specific recommendations
        switch ($intent_analysis['primary']) {
            case 'commercial':
                $recommendations[] = "Show prices prominently in ads and landing pages.";
                $recommendations[] = "Include trust signals (reviews, guarantees, payment options).";
                break;
                
            case 'informational':
                $recommendations[] = "Create comprehensive guide or comparison content.";
                $recommendations[] = "Consider content upgrades or lead magnets for email capture.";
                break;
                
            case 'comparison':
                $recommendations[] = "Create comparison charts or tables on landing page.";
                $recommendations[] = "Highlight competitive advantages and unique selling points.";
                break;
        }
        
        // User-specific recommendations
        if (!empty($user_data)) {
            if (isset($user_data['device']) && $user_data['device'] === 'mobile') {
                $recommendations[] = "Optimize for mobile: fast loading, large buttons, simplified forms.";
            }
            
            if (isset($user_data['is_returning']) && $user_data['is_returning']) {
                $recommendations[] = "Show personalized offers or loyalty benefits for returning visitors.";
            }
        }
        
        return array_unique($recommendations);
    }
}