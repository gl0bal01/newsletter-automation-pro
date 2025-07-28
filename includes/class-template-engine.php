<?php
/**
 * Template Engine Service
 * Handles newsletter template rendering with Context7 principles
 */

class NAP_TemplateEngine
{
    private $templates_cache = [];

    /**
     * Render template with data
     */
    public function render($template_name, $data)
    {
        $template_content = $this->getTemplate($template_name);
        
        if (!$template_content) {
            throw new Exception(sprintf(__('Template "%s" not found', 'newsletter-automation'), $template_name));
        }

        return $this->processTemplate($template_content, $data);
    }

    /**
     * Get template content
     */
    private function getTemplate($template_name)
    {
        // Check cache first
        if (isset($this->templates_cache[$template_name])) {
            return $this->templates_cache[$template_name];
        }

        // Load template file
        $template_file = NAP_PLUGIN_DIR . "templates/newsletters/{$template_name}.html";
        
        if (file_exists($template_file)) {
            $content = file_get_contents($template_file);
            $this->templates_cache[$template_name] = $content;
            return $content;
        }

        // Fallback to default template
        if ($template_name !== 'default') {
            return $this->getDefaultTemplate();
        }

        return false;
    }

    /**
     * Process template with handlebars-like syntax
     */
    private function processTemplate($template, $data)
    {
        // Simple variable replacement {{variable}}
        $template = preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($data) {
            $key = trim($matches[1]);
            return $this->getNestedValue($data, $key);
        }, $template);

        // Handle {{#if condition}} blocks
        $template = preg_replace_callback('/\{\{#if\s+([^}]+)\}\}(.*?)\{\{\/if\}\}/s', function($matches) use ($data) {
            $condition = trim($matches[1]);
            $content = $matches[2];
            
            if ($this->evaluateCondition($data, $condition)) {
                return $this->processTemplate($content, $data);
            }
            
            return '';
        }, $template);

        // Handle {{#each array}} blocks
        $template = preg_replace_callback('/\{\{#each\s+([^}]+)\}\}(.*?)\{\{\/each\}\}/s', function($matches) use ($data) {
            $array_key = trim($matches[1]);
            $item_template = $matches[2];
            $array = $this->getNestedValue($data, $array_key);
            
            if (!is_array($array)) {
                return '';
            }
            
            $output = '';
            foreach ($array as $index => $item) {
                $item_data = array_merge($data, [
                    'this' => $item,
                    '@index' => $index,
                    '@first' => $index === 0,
                    '@last' => $index === count($array) - 1
                ]);
                
                // Process nested template
                $processed_item = $this->processTemplate($item_template, $item_data);
                $output .= $processed_item;
            }
            
            return $output;
        }, $template);

        return $template;
    }

    /**
     * Get nested value from data array
     */
    private function getNestedValue($data, $key)
    {
        // Handle direct access
        if (isset($data[$key])) {
            return $data[$key];
        }

        // Handle 'this' reference
        if ($key === 'this') {
            return $data;
        }

        // Handle nested access like 'user.name'
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $value = $data;
            
            foreach ($parts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } elseif (is_object($value) && isset($value->$part)) {
                    $value = $value->$part;
                } else {
                    return '';
                }
            }
            
            return $value;
        }

        return '';
    }

    /**
     * Evaluate condition for {{#if}} blocks
     */
    private function evaluateCondition($data, $condition)
    {
        $value = $this->getNestedValue($data, $condition);
        
        // Handle different truthiness checks
        if (is_array($value)) {
            return !empty($value);
        }
        
        if (is_string($value)) {
            return trim($value) !== '';
        }
        
        return !empty($value);
    }

    /**
     * Get default template
     */
    private function getDefaultTemplate()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{options.header_text}}</title>
    <style>
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: {{options.background_color}}; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background-color: {{options.brand_color}}; color: white; padding: 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; }
        .content { padding: 20px; }
        .post-item { margin-bottom: 30px; border-bottom: 1px solid #eee; padding-bottom: 20px; }
        .post-item:last-child { border-bottom: none; }
        .post-image { width: 100%; height: 200px; object-fit: cover; border-radius: 5px; margin-bottom: 15px; }
        .post-title { font-size: 20px; font-weight: bold; margin: 0 0 10px 0; color: #333; }
        .post-title a { color: {{options.brand_color}}; text-decoration: none; }
        .post-description { color: #666; font-size: 14px; margin-bottom: 10px; }
        .post-meta { font-size: 12px; color: #999; margin-bottom: 15px; }
        .read-more { display: inline-block; background-color: {{options.brand_color}}; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; font-size: 14px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{#if site_info.logo_url}}
            <img src="{{site_info.logo_url}}" alt="{{site_info.name}}" style="max-height: 40px; margin-bottom: 10px;">
            {{/if}}
            <h1>{{options.header_text}}</h1>
        </div>
        
        <div class="content">
            {{#each posts}}
            <div class="post-item">
                {{#if featured_image.url}}
                <img src="{{featured_image.url}}" alt="{{featured_image.alt}}" class="post-image">
                {{/if}}
                
                <h2 class="post-title">
                    <a href="{{permalink}}">{{title}}</a>
                </h2>
                
                <div class="post-meta">
                    By {{author}} • {{date}}
                </div>
                
                {{#if custom_description}}
                <div class="post-description">{{custom_description}}</div>
                {{/if}}
                
                <a href="{{permalink}}" class="read-more">Read More</a>
            </div>
            {{/each}}
        </div>
        
        <div class="footer">
            <p>{{options.footer_text}}</p>
            {{#if options.include_unsubscribe}}
            <p><a href="[unsubscribe]">Unsubscribe</a> | <a href="[webversion]">View in Browser</a></p>
            {{/if}}
            <p><small>{{site_info.name}} • {{site_info.url}}</small></p>
        </div>
    </div>
</body>
</html>
HTML;
    }
}

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
