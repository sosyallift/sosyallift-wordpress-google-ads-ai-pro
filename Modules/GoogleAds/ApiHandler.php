<?php
namespace SosyalliftAIPro\Modules\GoogleAds;

use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\V16\GoogleAdsClientBuilder;
use Google\Ads\GoogleAds\Lib\V16\GoogleAdsException;
use Google\Ads\GoogleAds\V16\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V16\Services\SearchGoogleAdsRequest;
use Google\Ads\GoogleAds\V16\Services\ListAccessibleCustomersRequest;
use Google\ApiCore\ApiException;
use SosyalliftAIPro\Core\Logger;
use SosyalliftAIPro\Core\CacheManager;

class ApiHandler {
    private $client = null;
    private $customer_id = '';
    private $config = [];
    
    public function __construct(array $config = []) {
        $this->config = wp_parse_args($config, [
            'developer_token'  => get_option('sl_ai_pro_google_dev_token'),
            'client_id'        => get_option('sl_ai_pro_google_client_id'),
            'client_secret'    => get_option('sl_ai_pro_google_client_secret'),
            'refresh_token'    => get_option('sl_ai_pro_google_refresh_token'),
            'login_customer_id' => get_option('sl_ai_pro_google_customer_id'),
            'use_proxy'        => false,
            'proxy_url'        => '',
            'timeout'          => 30,
        ]);
    }
    
    public function authenticate(): bool {
        try {
            $client = $this->build_client();
            $request = new ListAccessibleCustomersRequest();
            $response = $client->getCustomerServiceClient()->listAccessibleCustomers($request);
            
            $this->customer_id = $response->getResourceNames()[0] ?? '';
            
            if (empty($this->customer_id)) {
                throw new \Exception('No accessible customers found');
            }
            
            update_option('sl_ai_pro_google_last_auth', time());
            Logger::info('Google Ads authentication successful', [
                'customer_id' => $this->customer_id
            ]);
            
            return true;
            
        } catch (GoogleAdsException $e) {
            Logger::error('Google Ads API error', [
                'error' => $e->getMessage(),
                'details' => $e->getMetadata()
            ]);
            return false;
        } catch (ApiException $e) {
            Logger::error('Google API exception', [
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ]);
            return false;
        } catch (\Exception $e) {
            Logger::error('Authentication failed', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    public function get_campaign_performance(string $date_range = 'LAST_30_DAYS'): array {
        $cache_key = 'google_ads_campaigns_' . $date_range . '_' . md5($this->customer_id);
        $cache = CacheManager::get_instance();
        
        if ($cached = $cache->get($cache_key)) {
            return $cached;
        }
        
        try {
            $client = $this->get_client();
            $query = "
                SELECT 
                    campaign.id,
                    campaign.name,
                    campaign.status,
                    campaign.advertising_channel_type,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.conversions,
                    metrics.conversions_value,
                    metrics.average_cpc,
                    metrics.ctr,
                    metrics.all_conversions,
                    campaign_budget.amount_micros,
                    campaign_budget.total_amount_micros
                FROM campaign
                WHERE segments.date DURING $date_range
                ORDER BY metrics.clicks DESC
                LIMIT 1000
            ";
            
            $request = new SearchGoogleAdsRequest();
            $request->setCustomerId(str_replace('customers/', '', $this->customer_id));
            $request->setQuery($query);
            
            $response = $client->getGoogleAdsServiceClient()->search($request);
            $results = [];
            
            foreach ($response->iterateAllElements() as $row) {
                /** @var GoogleAdsRow $row */
                $campaign = $row->getCampaign();
                $metrics = $row->getMetrics();
                $budget = $row->getCampaignBudget();
                
                $results[] = [
                    'id'            => $campaign->getId(),
                    'name'          => $campaign->getName(),
                    'status'        => $campaign->getStatus(),
                    'channel'       => $campaign->getAdvertisingChannelType(),
                    'impressions'   => $metrics->getImpressions(),
                    'clicks'        => $metrics->getClicks(),
                    'cost'          => $metrics->getCostMicros() / 1000000,
                    'conversions'   => $metrics->getConversions(),
                    'conversion_value' => $metrics->getConversionsValue(),
                    'cpc'           => $metrics->getAverageCpc() / 1000000,
                    'ctr'           => $metrics->getCtr(),
                    'all_conversions' => $metrics->getAllConversions(),
                    'budget'        => $budget ? $budget->getAmountMicros() / 1000000 : 0,
                    'total_budget'  => $budget ? $budget->getTotalAmountMicros() / 1000000 : 0,
                ];
            }
            
            $cache->set($cache_key, $results, 1800); // Cache for 30 minutes
            
            return $results;
            
        } catch (\Exception $e) {
            Logger::error('Failed to fetch campaign performance', [
                'error' => $e->getMessage(),
                'date_range' => $date_range
            ]);
            
            return [];
        }
    }
    
    public function get_search_terms(string $date_range = 'LAST_7_DAYS', int $limit = 1000): array {
        $cache_key = 'google_ads_search_terms_' . $date_range . '_' . $limit . '_' . md5($this->customer_id);
        $cache = CacheManager::get_instance();
        
        if ($cached = $cache->get($cache_key)) {
            return $cached;
        }
        
        try {
            $client = $this->get_client();
            $query = "
                SELECT 
                    search_term_view.search_term,
                    metrics.impressions,
                    metrics.clicks,
                    metrics.cost_micros,
                    metrics.conversions,
                    metrics.conversions_value,
                    metrics.average_cpc,
                    metrics.ctr,
                    campaign.advertising_channel_type,
                    ad_group.name
                FROM search_term_view
                WHERE segments.date DURING $date_range
                AND campaign.advertising_channel_type = 'SEARCH'
                AND metrics.clicks > 0
                ORDER BY metrics.clicks DESC
                LIMIT $limit
            ";
            
            $request = new SearchGoogleAdsRequest();
            $request->setCustomerId(str_replace('customers/', '', $this->customer_id));
            $request->setQuery($query);
            
            $response = $client->getGoogleAdsServiceClient()->search($request);
            $results = [];
            
            foreach ($response->iterateAllElements() as $row) {
                $search_term_view = $row->getSearchTermView();
                $metrics = $row->getMetrics();
                $campaign = $row->getCampaign();
                $ad_group = $row->getAdGroup();
                
                $results[] = [
                    'search_term'   => $search_term_view->getSearchTerm(),
                    'impressions'   => $metrics->getImpressions(),
                    'clicks'        => $metrics->getClicks(),
                    'cost'          => $metrics->getCostMicros() / 1000000,
                    'conversions'   => $metrics->getConversions(),
                    'conversion_value' => $metrics->getConversionsValue(),
                    'cpc'           => $metrics->getAverageCpc() / 1000000,
                    'ctr'           => $metrics->getCtr(),
                    'channel'       => $campaign->getAdvertisingChannelType(),
                    'ad_group'      => $ad_group ? $ad_group->getName() : '',
                    'roas'          => $metrics->getConversionsValue() > 0 ? 
                        ($metrics->getConversionsValue() / ($metrics->getCostMicros() / 1000000)) * 100 : 0,
                ];
            }
            
            $cache->set($cache_key, $results, 3600); // Cache for 1 hour
            
            return $results;
            
        } catch (\Exception $e) {
            Logger::error('Failed to fetch search terms', [
                'error' => $e->getMessage(),
                'date_range' => $date_range
            ]);
            
            return [];
        }
    }
    
    public function generate_negative_keywords(array $search_terms, array $options = []): array {
        $options = wp_parse_args($options, [
            'min_clicks'        => 5,
            'max_ctr'           => 0.5,
            'no_conversion'     => true,
            'exclude_brand'     => true,
            'brand_terms'       => $this->get_brand_terms(),
            'threshold'         => 0.7,
        ]);
        
        $negatives = [];
        $analyzer = new NegativeGenerator();
        
        foreach ($search_terms as $term_data) {
            $search_term = strtolower(trim($term_data['search_term']));
            
            // Skip if below minimum clicks
            if ($term_data['clicks'] < $options['min_clicks']) {
                continue;
            }
            
            // Skip if CTR is acceptable
            if ($term_data['ctr'] > $options['max_ctr']) {
                continue;
            }
            
            // Skip if has conversions
            if ($options['no_conversion'] && $term_data['conversions'] > 0) {
                continue;
            }
            
            // Skip brand terms
            if ($options['exclude_brand'] && $this->is_brand_term($search_term, $options['brand_terms'])) {
                continue;
            }
            
            // Analyze for negative patterns
            $analysis = $analyzer->analyze($search_term);
            
            if ($analysis['score'] >= $options['threshold']) {
                $negatives[] = [
                    'keyword'       => $search_term,
                    'match_type'    => $analysis['recommended_match_type'],
                    'clicks'        => $term_data['clicks'],
                    'cost'          => $term_data['cost'],
                    'ctr'           => $term_data['ctr'],
                    'conversions'   => $term_data['conversions'],
                    'reason'        => $analysis['reasons'],
                    'campaigns'     => $term_data['campaigns'] ?? [],
                    'ad_groups'     => $term_data['ad_groups'] ?? [],
                ];
            }
        }
        
        // Group similar negatives
        $negatives = $this->group_similar_negatives($negatives);
        
        // Sort by cost savings potential
        usort($negatives, function($a, $b) {
            $a_potential = $a['cost'] * (1 - $a['ctr']);
            $b_potential = $b['cost'] * (1 - $b['ctr']);
            return $b_potential <=> $a_potential;
        });
        
        return $negatives;
    }
    
    public function add_negative_keywords(array $negatives, string $campaign_id = '', string $ad_group_id = ''): array {
        $results = [
            'success' => [],
            'failed'  => [],
            'total'   => count($negatives),
        ];
        
        try {
            $client = $this->get_client();
            $customer_id = str_replace('customers/', '', $this->customer_id);
            
            foreach ($negatives as $index => $negative) {
                try {
                    // Build the operation
                    $operation = new \Google\Ads\GoogleAds\V16\Services\CampaignCriterionOperation();
                    
                    $criterion = new \Google\Ads\GoogleAds\V16\Resources\CampaignCriterion();
                    $criterion->setCampaign("customers/{$customer_id}/campaigns/{$campaign_id}");
                    $criterion->setNegative(true);
                    
                    $keyword_info = new \Google\Ads\GoogleAds\V16\Common\KeywordInfo();
                    $keyword_info->setText($negative['keyword']);
                    $keyword_info->setMatchType(
                        $this->get_match_type_enum($negative['match_type'])
                    );
                    
                    $criterion->setKeyword($keyword_info);
                    $operation->setCreate($criterion);
                    
                    // Execute the mutation
                    $response = $client->getCampaignCriterionServiceClient()->mutateCampaignCriteria(
                        $customer_id,
                        [$operation]
                    );
                    
                    $result = $response->getResults()[0];
                    $results['success'][] = [
                        'keyword'   => $negative['keyword'],
                        'resource'  => $result->getResourceName(),
                        'index'     => $index,
                    ];
                    
                    Logger::info('Added negative keyword', [
                        'keyword'   => $negative['keyword'],
                        'match_type'=> $negative['match_type'],
                        'campaign'  => $campaign_id,
                    ]);
                    
                    // Rate limiting
                    if (($index + 1) % 10 === 0) {
                        sleep(1);
                    }
                    
                } catch (\Exception $e) {
                    $results['failed'][] = [
                        'keyword'   => $negative['keyword'],
                        'error'     => $e->getMessage(),
                        'index'     => $index,
                    ];
                    
                    Logger::warning('Failed to add negative keyword', [
                        'keyword'   => $negative['keyword'],
                        'error'     => $e->getMessage(),
                    ]);
                }
            }
            
            return $results;
            
        } catch (\Exception $e) {
            Logger::error('Batch negative keyword addition failed', [
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }
    
    public function get_keyword_recommendations(array $seed_keywords, array $options = []): array {
        $options = wp_parse_args($options, [
            'language'          => 'tr',
            'location_ids'      => [1023223], // Turkey
            'include_adult'     => false,
            'page_size'         => 100,
            'get_monthly_searches'=> true,
        ]);
        
        try {
            $client = $this->get_client();
            $customer_id = str_replace('customers/', '', $this->customer_id);
            
            // Generate ideas
            $request = new \Google\Ads\GoogleAds\V16\Services\GenerateKeywordIdeasRequest();
            $request->setCustomerId($customer_id);
            $request->setLanguage($this->get_language_constant($options['language']));
            $request->setGeoTargetConstants(array_map(
                fn($id) => "geoTargetConstants/{$id}",
                $options['location_ids']
            ));
            $request->setIncludeAdultKeywords($options['include_adult']);
            $request->setPageSize($options['page_size']);
            
            // Add seed keywords
            foreach ($seed_keywords as $keyword) {
                $keyword_seed = new \Google\Ads\GoogleAds\V16\Common\KeywordSeed();
                $keyword_seed->setKeywords([$keyword]);
                $request->setKeywordAndUrlSeed($keyword_seed);
                break; // For simplicity, use first keyword
            }
            
            // Optional: Use URL seed
            if (!empty($options['url'])) {
                $url_seed = new \Google\Ads\GoogleAds\V16\Common\UrlSeed();
                $url_seed->setUrl($options['url']);
                $request->setUrlSeed($url_seed);
            }
            
            // Execute request
            $response = $client->getKeywordPlanIdeaServiceClient()->generateKeywordIdeas($request);
            $results = [];
            
            foreach ($response->iterateAllElements() as $idea) {
                $keyword = $idea->getText();
                $metrics = $idea->getKeywordIdeaMetrics();
                
                if (!$metrics) {
                    continue;
                }
                
                $results[] = [
                    'keyword'               => $keyword,
                    'avg_monthly_searches'  => $metrics->getAvgMonthlySearches(),
                    'competition'           => $metrics->getCompetition()->name,
                    'competition_index'     => $metrics->getCompetitionIndex(),
                    'low_top_of_page_bid_micros' => $metrics->getLowTopOfPageBidMicros(),
                    'high_top_of_page_bid_micros' => $metrics->getHighTopOfPageBidMicros(),
                    'search_volume'         => [
                        'monthly'   => $metrics->getAvgMonthlySearches(),
                        'trend'     => iterator_to_array($metrics->getMonthlySearchVolumes()),
                    ],
                ];
            }
            
            // Analyze intent and commercial value
            $intent_analyzer = new IntentAnalyzer();
            $scorer = new KeywordScorer();
            
            foreach ($results as &$result) {
                $result['intent'] = $intent_analyzer->analyze($result['keyword']);
                $result['score'] = $scorer->calculate(
                    $result['avg_monthly_searches'],
                    $result['competition_index'],
                    $result['intent']['score'],
                    $result['low_top_of_page_bid_micros'] / 1000000
                );
                
                $result['recommendation'] = $this->get_keyword_recommendation($result);
            }
            
            // Sort by score
            usort($results, function($a, $b) {
                return $b['score'] <=> $a['score'];
            });
            
            return array_slice($results, 0, $options['page_size']);
            
        } catch (\Exception $e) {
            Logger::error('Failed to get keyword recommendations', [
                'error' => $e->getMessage(),
                'seed_keywords' => $seed_keywords,
            ]);
            
            return [];
        }
    }
    
    private function build_client(): GoogleAdsClient {
        if ($this->client === null) {
            $oauth2 = new \Google\Auth\OAuth2([
                'clientId'      => $this->config['client_id'],
                'clientSecret'  => $this->config['client_secret'],
                'refresh_token' => $this->config['refresh_token'],
                'tokenCredentialUri' => 'https://oauth2.googleapis.com/token',
            ]);
            
            $builder = new GoogleAdsClientBuilder();
            $builder->withDeveloperToken($this->config['developer_token'])
                    ->withLoginCustomerId($this->config['login_customer_id'])
                    ->withOAuth2Credential($oauth2)
                    ->withLogger(new class extends \Psr\Log\AbstractLogger {
                        public function log($level, $message, array $context = []): void {
                            if ($level === 'error' || $level === 'critical') {
                                Logger::error($message, $context);
                            }
                        }
                    });
            
            if ($this->config['use_proxy'] && !empty($this->config['proxy_url'])) {
                $builder->withTransport('rest')
                        ->withProxyUri($this->config['proxy_url']);
            }
            
            $this->client = $builder->build();
        }
        
        return $this->client;
    }
    
    private function get_client(): GoogleAdsClient {
        if ($this->client === null) {
            $this->authenticate();
        }
        
        return $this->client;
    }
    
    private function get_match_type_enum(string $match_type): int {
        $types = [
            'EXACT'     => \Google\Ads\GoogleAds\V16\Enums\KeywordMatchTypeEnum\KeywordMatchType::EXACT,
            'PHRASE'    => \Google\Ads\GoogleAds\V16\Enums\KeywordMatchTypeEnum\KeywordMatchType::PHRASE,
            'BROAD'     => \Google\Ads\GoogleAds\V16\Enums\KeywordMatchTypeEnum\KeywordMatchType::BROAD,
        ];
        
        return $types[strtoupper($match_type)] ?? $types['BROAD'];
    }
    
    private function get_language_constant(string $language): string {
        $languages = [
            'tr'    => 'languageConstants/1017', // Turkish
            'en'    => 'languageConstants/1000', // English
            'de'    => 'languageConstants/1011', // German
            'fr'    => 'languageConstants/1002', // French
            'es'    => 'languageConstants/1003', // Spanish
            'ru'    => 'languageConstants/1027', // Russian
            'ar'    => 'languageConstants/1018', // Arabic
        ];
        
        return $languages[$language] ?? $languages['en'];
    }
    
    private function get_brand_terms(): array {
        $terms = get_option('sl_ai_pro_brand_terms', []);
        
        if (empty($terms)) {
            $site_name = get_bloginfo('name');
            $domain = parse_url(site_url(), PHP_URL_HOST);
            $domain = str_replace(['www.', '.com', '.com.tr', '.net', '.org'], '', $domain);
            
            $terms = array_filter(array_unique([
                strtolower($site_name),
                strtolower($domain),
                preg_replace('/[^a-z0-9]/', '', strtolower($site_name)),
                preg_replace('/[^a-z0-9]/', '', strtolower($domain)),
            ]));
            
            update_option('sl_ai_pro_brand_terms', $terms);
        }
        
        return $terms;
    }
    
    private function is_brand_term(string $keyword, array $brand_terms): bool {
        foreach ($brand_terms as $brand) {
            if (stripos($keyword, $brand) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function group_similar_negatives(array $negatives): array {
        $groups = [];
        
        foreach ($negatives as $negative) {
            $added = false;
            
            foreach ($groups as &$group) {
                $similarity = $this->calculate_similarity(
                    $group['keywords'][0]['keyword'],
                    $negative['keyword']
                );
                
                if ($similarity >= 0.8) { // 80% similarity threshold
                    $group['keywords'][] = $negative;
                    $group['total_clicks'] += $negative['clicks'];
                    $group['total_cost'] += $negative['cost'];
                    $added = true;
                    break;
                }
            }
            
            if (!$added) {
                $groups[] = [
                    'keywords'      => [$negative],
                    'total_clicks'  => $negative['clicks'],
                    'total_cost'    => $negative['cost'],
                    'representative'=> $negative['keyword'],
                ];
            }
        }
        
        // For each group, select the most representative keyword
        $result = [];
        foreach ($groups as $group) {
            if (count($group['keywords']) === 1) {
                $result[] = $group['keywords'][0];
            } else {
                // Find the keyword that appears most in the group
                $word_freq = [];
                foreach ($group['keywords'] as $kw) {
                    $words = explode(' ', $kw['keyword']);
                    foreach ($words as $word) {
                        if (strlen($word) > 2) {
                            $word_freq[$word] = ($word_freq[$word] ?? 0) + 1;
                        }
                    }
                }
                
                arsort($word_freq);
                $common_words = array_slice(array_keys($word_freq), 0, 3);
                $representative = implode(' ', $common_words);
                
                $result[] = array_merge($group['keywords'][0], [
                    'keyword'       => $representative,
                    'group_size'    => count($group['keywords']),
                    'group_clicks'  => $group['total_clicks'],
                    'group_cost'    => $group['total_cost'],
                ]);
            }
        }
        
        return $result;
    }
    
    private function calculate_similarity(string $str1, string $str2): float {
        $words1 = explode(' ', strtolower($str1));
        $words2 = explode(' ', strtolower($str2));
        
        $intersection = array_intersect($words1, $words2);
        $union = array_unique(array_merge($words1, $words2));
        
        if (empty($union)) {
            return 0;
        }
        
        return count($intersection) / count($union);
    }
    
    private function get_keyword_recommendation(array $keyword_data): array {
        $score = $keyword_data['score'];
        $intent = $keyword_data['intent']['type'];
        $volume = $keyword_data['avg_monthly_searches'];
        $competition = $keyword_data['competition_index'];
        
        if ($score >= 80) {
            $action = 'high_priority';
            $bid_multiplier = 1.2;
            $match_type = 'EXACT';
        } elseif ($score >= 60) {
            $action = 'medium_priority';
            $bid_multiplier = 1.0;
            $match_type = 'PHRASE';
        } elseif ($score >= 40) {
            $action = 'low_priority';
            $bid_multiplier = 0.8;
            $match_type = 'BROAD';
        } else {
            $action = 'monitor_only';
            $bid_multiplier = 0.5;
            $match_type = 'BROAD';
        }
        
        return [
            'action'            => $action,
            'bid_multiplier'    => $bid_multiplier,
            'match_type'        => $match_type,
            'campaign_type'     => $intent === 'commercial' ? 'SEARCH' : 'DISPLAY',
            'ad_group_suggestions' => $this->suggest_ad_groups($keyword_data['keyword'], $intent),
            'landing_page_suggestions' => $this->suggest_landing_pages($keyword_data['keyword'], $intent),
            'estimated_traffic' => $this->estimate_traffic($volume, $score),
            'estimated_conversions' => $this->estimate_conversions($volume, $intent, $score),
        ];
    }
    
    private function suggest_ad_groups(string $keyword, string $intent): array {
        $words = explode(' ', strtolower($keyword));
        $root_word = $words[0] ?? '';
        
        $suggestions = [
            'root'      => $root_word . '_' . $intent,
            'exact'     => 'exact_' . implode('_', $words),
            'category'  => $this->categorize_keyword($keyword) . '_' . $intent,
        ];
        
        return array_unique(array_filter($suggestions));
    }
    
    private function categorize_keyword(string $keyword): string {
        $categories = [
            'price'     => ['fiyat', 'ucuz', 'pahalı', 'indirim', 'kampanya'],
            'buy'       => ['satın al', 'satın', 'alışveriş', 'sipariş', 'ödeme'],
            'info'      => ['nasıl', 'nedir', 'ne kadar', 'kaç', 'hangi'],
            'compare'   => ['karşılaştırma', 'vs', 'fark', 'en iyi', 'hangisi'],
            'review'    => ['yorum', 'değerlendirme', 'inceleme', 'puan', 'deneyim'],
            'problem'   => ['sorun', 'problem', 'arıza', 'tamir', 'yardım'],
        ];
        
        $keyword_lower = strtolower($keyword);
        
        foreach ($categories as $category => $terms) {
            foreach ($terms as $term) {
                if (strpos($keyword_lower, $term) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
    
    private function suggest_landing_pages(string $keyword, string $intent): array {
        $pages = [];
        
        // Search for existing pages
        $query = new \WP_Query([
            's'              => $keyword,
            'post_type'      => ['page', 'post', 'product'],
            'posts_per_page' => 3,
            'post_status'    => 'publish',
        ]);
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $pages[] = [
                    'title' => get_the_title(),
                    'url'   => get_permalink(),
                    'type'  => get_post_type(),
                    'relevance' => $this->calculate_relevance($keyword, get_the_content()),
                ];
            }
            wp_reset_postdata();
        }
        
        // Sort by relevance
        usort($pages, function($a, $b) {
            return $b['relevance'] <=> $a['relevance'];
        });
        
        return array_slice($pages, 0, 3);
    }
    
    private function calculate_relevance(string $keyword, string $content): float {
        $keyword_words = explode(' ', strtolower($keyword));
        $content_lower = strtolower(strip_tags($content));
        
        $matches = 0;
        foreach ($keyword_words as $word) {
            if (strlen($word) > 2 && strpos($content_lower, $word) !== false) {
                $matches++;
            }
        }
        
        return $matches / max(1, count($keyword_words));
    }
    
    private function estimate_traffic(int $monthly_searches, float $score): array {
        $ctr = min(0.1 + ($score / 200), 0.5); // CTR between 10-50%
        $daily_clicks = ($monthly_searches * $ctr) / 30;
        
        return [
            'monthly_searches'  => $monthly_searches,
            'estimated_ctr'     => round($ctr * 100, 2),
            'daily_clicks'      => round($daily_clicks),
            'monthly_clicks'    => round($daily_clicks * 30),
        ];
    }
    
    private function estimate_conversions(int $monthly_searches, string $intent, float $score): array {
        $conversion_rate = 0.01; // Base 1%
        
        // Adjust based on intent
        if ($intent === 'commercial') {
            $conversion_rate *= 3; // 3% for commercial
        } elseif ($intent === 'informational') {
            $conversion_rate *= 0.3; // 0.3% for informational
        }
        
        // Adjust based on score
        $conversion_rate *= ($score / 100);
        
        $monthly_clicks = $monthly_searches * min(0.1 + ($score / 200), 0.5);
        $monthly_conversions = $monthly_clicks * $conversion_rate;
        
        return [
            'conversion_rate'   => round($conversion_rate * 100, 2),
            'monthly_conversions' => round($monthly_conversions),
            'value_per_conversion' => $intent === 'commercial' ? 100 : 10, // Example values
            'estimated_monthly_value' => round($monthly_conversions * ($intent === 'commercial' ? 100 : 10)),
        ];
    }
}