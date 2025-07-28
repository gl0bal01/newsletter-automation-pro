<?php
/**
 * AI Description Service - Phase 2
 * Handles AI-powered description generation for newsletter posts
 */

class NAP_AIDescriptionService
{
    private $config;
    private $validation_service;

    public function __construct($config)
    {
        $this->config = $config;
        $this->validation_service = new NAP_ValidationService();
    }

    /**
     * Generate descriptions for multiple posts
     */
    public function generateDescriptions($post_ids)
    {
        $post_search = new NAP_PostSearchService();
        $posts_data = $post_search->getPostsForNewsletter($post_ids);
        $descriptions = [];

        foreach ($posts_data as $post_data) {
            try {
                $description = $this->generateSingleDescription($post_data);
                $descriptions[$post_data['id']] = [
                    'success' => true,
                    'description' => $description,
                    'word_count' => str_word_count($description)
                ];
            } catch (Exception $e) {
                $descriptions[$post_data['id']] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                    'description' => $this->generateFallbackDescription($post_data)
                ];
            }
        }

        return $descriptions;
    }

    /**
     * Generate description for a single post
     */
    public function generateSingleDescription($post_data)
    {
        $ai_service = $this->config['ai_service'];
        
        switch ($ai_service) {
            case 'openai':
                return $this->generateWithOpenAI($post_data);
            case 'claude':
                return $this->generateWithClaude($post_data);
            default:
                throw new Exception(__('Invalid AI service configured', 'newsletter-automation'));
        }
    }

    /**
     * Generate description using OpenAI
     */
    private function generateWithOpenAI($post_data)
    {
        $api_key = $this->config['openai_api_key'];
        
        if (empty($api_key)) {
            throw new Exception(__('OpenAI API key not configured', 'newsletter-automation'));
        }

        $prompt = $this->buildPrompt($post_data);
        
        $data = [
            'model' => 'gpt-4',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional newsletter editor specializing in creating engaging, concise descriptions that drive reader engagement.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'max_tokens' => 100,
            'temperature' => 0.7
        ];

        $response = $this->makeApiRequest(
            'https://api.openai.com/v1/chat/completions',
            $data,
            [
                'Authorization: Bearer ' . $api_key,
                'Content-Type: application/json'
            ]
        );

        if (!$response || !isset($response['choices'][0]['message']['content'])) {
            throw new Exception(__('Failed to get response from OpenAI', 'newsletter-automation'));
        }

        $description = trim($response['choices'][0]['message']['content']);
        return $this->validateAndCleanDescription($description, $post_data['title']);
    }

    /**
     * Generate description using Claude
     */
    private function generateWithClaude($post_data)
    {
        $api_key = $this->config['claude_api_key'];
        
        if (empty($api_key)) {
            throw new Exception(__('Claude API key not configured', 'newsletter-automation'));
        }

        $prompt = $this->buildPrompt($post_data);
        
        $data = [
            'model' => 'claude-3-sonnet-20240229',
            'max_tokens' => 100,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $response = $this->makeApiRequest(
            'https://api.anthropic.com/v1/messages',
            $data,
            [
                'x-api-key: ' . $api_key,
                'Content-Type: application/json',
                'anthropic-version: 2023-06-01'
            ]
        );

        if (!$response || !isset($response['content'][0]['text'])) {
            throw new Exception(__('Failed to get response from Claude', 'newsletter-automation'));
        }

        $description = trim($response['content'][0]['text']);
        return $this->validateAndCleanDescription($description, $post_data['title']);
    }

    /**
     * Build prompt for AI services
     */
    private function buildPrompt($post_data)
    {
        $title = $post_data['title'];
        $content = $post_data['content'];
        $max_words = $this->config['max_description_words'];
        
        // Extract title words for exclusion
        $title_words = $this->extractTitleWords($title);
        $title_words_list = implode(', ', $title_words);

        $prompt = <<<PROMPT
Create a compelling newsletter description for this article:

TITLE: "{$title}"

CONTENT: {$content}

REQUIREMENTS:
1. Maximum {$max_words} words
2. Do NOT use any of these words from the title: {$title_words_list}
3. Create an engaging hook that makes readers want to click
4. Focus on the key benefit or insight for readers
5. Use active voice and compelling language
6. Make it conversational and engaging
7. Avoid generic phrases like "learn more" or "find out"

Return ONLY the description, no quotes, no extra text.
PROMPT;

        return $prompt;
    }

    /**
     * Extract meaningful words from title for exclusion
     */
    private function extractTitleWords($title)
    {
        // Common stop words to ignore
        $stop_words = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'were', 'will', 'with', 'you', 'your', 'this', 'these',
            'those', 'they', 'them', 'their', 'have', 'had', 'do', 'does', 'did',
            'le', 'la', 'de', 'du', 'les', 'des', 'voici', 'et', 'qui', 'ce', 'ces', 'cette',
            'leur'
        ];

        $words = str_word_count(strtolower($title), 1);
        $meaningful_words = [];

        foreach ($words as $word) {
            // Remove punctuation and get clean word
            $clean_word = preg_replace('/[^a-z0-9]/', '', $word);
            
            // Skip if empty, too short, or is stop word
            if (strlen($clean_word) < 3 || in_array($clean_word, $stop_words)) {
                continue;
            }

            $meaningful_words[] = $clean_word;
        }

        return array_unique($meaningful_words);
    }

    /**
     * Validate and clean AI-generated description
     */
    private function validateAndCleanDescription($description, $title)
    {
        // Remove quotes if present
        $description = trim($description, '"\'');
        
        // Clean up any formatting
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);

        // Check word count
        $word_count = str_word_count($description);
        $max_words = $this->config['max_description_words'];

        if ($word_count > $max_words) {
            // Truncate to max words while preserving sentence structure
            $words = explode(' ', $description);
            $truncated = implode(' ', array_slice($words, 0, $max_words));
            
            // Try to end at a sentence boundary
            $last_period = strrpos($truncated, '.');
            if ($last_period !== false && $last_period > strlen($truncated) * 0.7) {
                $description = substr($truncated, 0, $last_period + 1);
            } else {
                $description = $truncated . '...';
            }
        }

        // Validate that no title words are repeated
        $validation_result = $this->validation_service->validateDescription($description, $title);
        
        if (!$validation_result['is_valid']) {
            // Try to fix common issues
            $description = $this->fixDescriptionIssues($description, $title, $validation_result['issues']);
        }

        return $description;
    }

    /**
     * Fix description issues automatically
     */
    private function fixDescriptionIssues($description, $title, $issues)
    {
        $title_words = $this->extractTitleWords($title);
        
        foreach ($title_words as $word) {
            // Replace title words with synonyms or remove them
            $description = $this->replaceTitleWord($description, $word);
        }

        return $description;
    }

    /**
     * Replace title words with alternatives
     */
    private function replaceTitleWord($description, $word)
    {
        // Simple synonym mapping - could be expanded
        $synonyms = [
            'guide' => 'tutorial',
            'tips' => 'advice',
            'best' => 'top',
            'ultimate' => 'complete',
            'complete' => 'comprehensive',
            'how' => 'methods',
            'ways' => 'approaches',
            'create' => 'build',
            'make' => 'develop',
            'build' => 'construct'
        ];

        $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
        
        if (isset($synonyms[strtolower($word)])) {
            $description = preg_replace($pattern, $synonyms[strtolower($word)], $description, 1);
        } else {
            // If no synonym, try to rephrase or remove
            $description = preg_replace($pattern, '', $description, 1);
            $description = preg_replace('/\s+/', ' ', $description);
            $description = trim($description);
        }

        return $description;
    }

    /**
     * Generate fallback description if AI fails
     */
    private function generateFallbackDescription($post_data)
    {
        $content = $post_data['content'];
        $title = $post_data['title'];
        $max_words = $this->config['max_description_words'];

        // Extract first meaningful sentence from content
        $sentences = preg_split('/[.!?]+/', $content);
        $description = '';

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 20) { // Skip very short sentences
                $words = explode(' ', $sentence);
                if (count($words) <= $max_words) {
                    $description = $sentence;
                    break;
                }
            }
        }

        // If no suitable sentence found, create from content
        if (empty($description)) {
            $words = explode(' ', $content);
            $description = implode(' ', array_slice($words, 0, $max_words));
        }

        // Ensure it doesn't repeat title words
        $title_words = $this->extractTitleWords($title);
        foreach ($title_words as $word) {
            $description = $this->replaceTitleWord($description, $word);
        }

        return trim($description) . (strlen($description) > 50 ? '...' : '');
    }

    /**
     * Make API request to AI services
     */
    private function makeApiRequest($url, $data, $headers)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);

        if ($curl_error) {
            throw new Exception(sprintf(__('Curl error: %s', 'newsletter-automation'), $curl_error));
        }

        if ($http_code !== 200) {
            throw new Exception(sprintf(__('API request failed with status: %d', 'newsletter-automation'), $http_code));
        }

        $decoded_response = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(__('Invalid JSON response from API', 'newsletter-automation'));
        }

        return $decoded_response;
    }

    /**
     * Test AI service connection
     */
    public function testConnection()
    {
        $test_post = [
            'title' => 'Test Article for Newsletter',
            'content' => 'This is a test article to verify that the AI service is working correctly for newsletter description generation.'
        ];

        try {
            $description = $this->generateSingleDescription($test_post);
            return [
                'success' => true,
                'description' => $description,
                'message' => __('AI service connection successful', 'newsletter-automation')
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => __('AI service connection failed', 'newsletter-automation')
            ];
        }
    }

    /**
     * Get usage statistics
     */
    public function getUsageStats()
    {
        // This could be expanded to track API usage, costs, etc.
        return [
            'total_descriptions_generated' => get_option('nap_total_descriptions', 0),
            'ai_service' => $this->config['ai_service'],
            'average_word_count' => get_option('nap_avg_description_words', 0)
        ];
    }

    /**
     * Update usage statistics
     */
    private function updateUsageStats($word_count)
    {
        $total = get_option('nap_total_descriptions', 0);
        $avg_words = get_option('nap_avg_description_words', 0);
        
        $new_total = $total + 1;
        $new_avg = (($avg_words * $total) + $word_count) / $new_total;
        
        update_option('nap_total_descriptions', $new_total);
        update_option('nap_avg_description_words', round($new_avg, 1));
    }
}
