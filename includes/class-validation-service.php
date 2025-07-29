<?php 
/**
 * Validation Service
 * Handles all validation logic following Context7 principles
 */

class NAP_ValidationService
{
    /**
     * Validate newsletter description
     */
    public function validateDescription($description, $title)
    {
        $issues = [];
        $warnings = [];

        // Check word count
        $word_count = str_word_count($description);
        $max_words = get_option('nap_max_description_words', 14);

        if ($word_count > $max_words) {
            $issues[] = sprintf(__('Description exceeds %d words (%d words)', 'newsletter-automation'), $max_words, $word_count);
        }

        // Check for title word repetition
        $title_words = $this->extractMeaningfulWords($title);
        $description_words = $this->extractMeaningfulWords($description);
        
        $repeated_words = array_intersect($title_words, $description_words);
        
        if (!empty($repeated_words)) {
            $issues[] = sprintf(__('Description repeats title words: %s', 'newsletter-automation'), implode(', ', $repeated_words));
        }

        // Check for empty description
        if (empty(trim($description))) {
            $issues[] = __('Description is empty', 'newsletter-automation');
        }

        // Check for too short description
        if ($word_count < 3) {
            $warnings[] = __('Description is very short', 'newsletter-automation');
        }

        // Check for generic phrases
        $generic_phrases = ['learn more', 'find out', 'click here', 'read on', 'discover'];
        foreach ($generic_phrases as $phrase) {
            if (stripos($description, $phrase) !== false) {
                $warnings[] = sprintf(__('Description contains generic phrase: "%s"', 'newsletter-automation'), $phrase);
            }
        }

        return [
            'is_valid' => empty($issues),
            'issues' => $issues,
            'warnings' => $warnings,
            'word_count' => $word_count
        ];
    }

    /**
     * Validate newsletter data
     */
    public function validateNewsletterData($data)
    {
        $errors = [];
        $warnings = [];

        // Required fields
        $required_fields = ['subject', 'posts'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = sprintf(__('Required field missing: %s', 'newsletter-automation'), $field);
            }
        }

        // Validate subject line
        if (!empty($data['subject'])) {
            $subject_validation = $this->validateSubjectLine($data['subject']);
            if (!$subject_validation['is_valid']) {
                $warnings = array_merge($warnings, $subject_validation['warnings']);
            }
        }

        // Validate posts
        if (!empty($data['posts'])) {
            foreach ($data['posts'] as $index => $post) {
                $post_validation = $this->validateNewsletterPost($post, $index + 1);
                $errors = array_merge($errors, $post_validation['errors']);
                $warnings = array_merge($warnings, $post_validation['warnings']);
            }
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Validate subject line
     */
    public function validateSubjectLine($subject)
    {
        $warnings = [];

        // Length check
        $length = strlen($subject);
        if ($length > 60) {
            $warnings[] = sprintf(__('Subject line is long (%d characters). Consider shortening for better mobile display.', 'newsletter-automation'), $length);
        } elseif ($length < 20) {
            $warnings[] = sprintf(__('Subject line is short (%d characters). Consider adding more detail.', 'newsletter-automation'), $length);
        }

        // Spam trigger words
        $spam_words = ['free', 'urgent', 'limited time', 'act now', 'guaranteed', 'no risk', 'winner', 'congratulations'];
        foreach ($spam_words as $word) {
            if (stripos($subject, $word) !== false) {
                $warnings[] = sprintf(__('Subject contains potential spam trigger: "%s"', 'newsletter-automation'), $word);
            }
        }

        // Excessive punctuation
        if (preg_match('/[!]{2,}/', $subject) || preg_match('/[?]{2,}/', $subject)) {
            $warnings[] = __('Subject contains excessive punctuation', 'newsletter-automation');
        }

        // All caps check
        if ($subject === strtoupper($subject) && strlen($subject) > 10) {
            $warnings[] = __('Subject line is in all caps', 'newsletter-automation');
        }

        return [
            'is_valid' => true, // Subject warnings don't make it invalid
            'warnings' => $warnings
        ];
    }

    /**
     * Validate individual newsletter post
     */
    private function validateNewsletterPost($post, $position)
    {
        $errors = [];
        $warnings = [];

        // Check required fields
        if (empty($post['id'])) {
            $errors[] = sprintf(__('Post #%d: Missing post ID', 'newsletter-automation'), $position);
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // Validate post exists
        $wp_post = get_post($post['id']);
        if (!$wp_post) {
            $errors[] = sprintf(__('Post #%d: Post not found (ID: %d)', 'newsletter-automation'), $position, $post['id']);
            return ['errors' => $errors, 'warnings' => $warnings];
        }

        // Check post status
        if ($wp_post->post_status !== 'publish') {
            $errors[] = sprintf(__('Post #%d: Post is not published ("%s")', 'newsletter-automation'), $position, $wp_post->post_title);
        }

        // Check featured image
        if (!has_post_thumbnail($post['id'])) {
            $warnings[] = sprintf(__('Post #%d: No featured image ("%s")', 'newsletter-automation'), $position, $wp_post->post_title);
        }

        // Validate custom description if provided
        if (!empty($post['description'])) {
            $desc_validation = $this->validateDescription($post['description'], $wp_post->post_title);
            if (!$desc_validation['is_valid']) {
                foreach ($desc_validation['issues'] as $issue) {
                    $warnings[] = sprintf(__('Post #%d description: %s', 'newsletter-automation'), $position, $issue);
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * Extract meaningful words from text
     */
    private function extractMeaningfulWords($text)
    {
        $stop_words = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'were', 'will', 'with', 'you', 'your', 'this', 'these',
            'those', 'they', 'them', 'their', 'have', 'had', 'do', 'does', 'did'
        ];

        $words = str_word_count(strtolower($text), 1);
        $meaningful_words = [];

        foreach ($words as $word) {
            $clean_word = preg_replace('/[^a-z0-9]/', '', $word);
            
            if (strlen($clean_word) >= 3 && !in_array($clean_word, $stop_words)) {
                $meaningful_words[] = $clean_word;
            }
        }

        return array_unique($meaningful_words);
    }

    /**
     * Validate API configuration
     */
    public function validateApiConfig($service, $config)
    {
        $errors = [];

        switch ($service) {
            case 'openai':
                if (empty($config['openai_api_key'])) {
                    $errors[] = __('OpenAI API key is required', 'newsletter-automation');
                } elseif (!preg_match('/^sk-[a-zA-Z0-9]{48}$/', $config['openai_api_key'])) {
                    $errors[] = __('Invalid OpenAI API key format', 'newsletter-automation');
                }
                break;

            case 'claude':
                if (empty($config['claude_api_key'])) {
                    $errors[] = __('Claude API key is required', 'newsletter-automation');
                }
                break;

            default:
                $errors[] = __('Invalid AI service selected', 'newsletter-automation');
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Validate Sendy configuration
     */
    public function validateSendyConfig($config)
    {
        $errors = [];

        if (empty($config['sendy_url'])) {
            $errors[] = __('Sendy URL is required', 'newsletter-automation');
        } elseif (!filter_var($config['sendy_url'], FILTER_VALIDATE_URL)) {
            $errors[] = __('Invalid Sendy URL format', 'newsletter-automation');
        }

        if (empty($config['sendy_api_key'])) {
            $errors[] = __('Sendy API key is required', 'newsletter-automation');
        }

        if (empty($config['default_list_id'])) {
            $errors[] = __('Default list ID is required', 'newsletter-automation');
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
