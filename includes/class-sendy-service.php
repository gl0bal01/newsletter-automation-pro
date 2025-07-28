<?php
/**
 * Sendy Service - Phase 3
 * Handles integration with Sendy email marketing platform
 */

class NAP_SendyService
{
    private $config;
    private $api_url;
    private $api_key;

    public function __construct($config)
    {
        $this->config = $config;
        $this->api_url = rtrim($config['sendy_url'], '/');
        $this->api_key = $config['sendy_api_key'];
    }

    /**
     * Create and send newsletter via Sendy
     */
    public function createNewsletter($data)
    {
        $required_fields = ['subject', 'html_content', 'list_id'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception(sprintf(__('Missing required field: %s', 'newsletter-automation'), $field));
            }
        }

        // Prepare campaign data
        $campaign_data = [
            'from_name' => $data['from_name'] ?? get_bloginfo('name'),
            'from_email' => $data['from_email'] ?? get_bloginfo('admin_email'),
            'reply_to' => $data['reply_to'] ?? get_bloginfo('admin_email'),
            'subject' => $data['subject'],
            'plain_text' => $data['plain_text'] ?? $this->htmlToPlainText($data['html_content']),
            'html_text' => $data['html_content'],
            'list_ids' => $data['list_id'],
            'send_campaign' => $data['send_immediately'] ?? 0,
            'api_key' => $this->api_key
        ];

        // Add optional fields
        if (!empty($data['brand_id'])) {
            $campaign_data['brand_id'] = $data['brand_id'];
        }

        if (!empty($data['query_string'])) {
            $campaign_data['query_string'] = $data['query_string'];
        }

        // Create campaign
        $response = $this->makeApiRequest('/api/campaigns/create.php', $campaign_data);

        if ($response === 'Campaign created') {
            $result = [
                'success' => true,
                'message' => __('Newsletter created successfully', 'newsletter-automation'),
                'campaign_id' => $this->getLatestCampaignId($data['list_id']),
                'sent_immediately' => !empty($data['send_immediately'])
            ];
        } elseif ($response === 'Campaign created and now sending') {
            $result = [
                'success' => true,
                'message' => __('Newsletter created and sent successfully', 'newsletter-automation'),
                'campaign_id' => $this->getLatestCampaignId($data['list_id']),
                'sent_immediately' => true
            ];
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }

        // Log the newsletter creation
        $this->logNewsletterAction('create', $result['campaign_id'], $data);

        return $result;
    }

    /**
     * Send existing campaign
     */
    public function sendCampaign($campaign_id, $list_id = null)
    {
        $send_data = [
            'campaign_id' => $campaign_id,
            'api_key' => $this->api_key
        ];

        if ($list_id) {
            $send_data['list_ids'] = $list_id;
        }

        $response = $this->makeApiRequest('/api/campaigns/send.php', $send_data);

        if ($response === 'Campaign sent' || $response === 'Campaign is now sending') {
            $this->logNewsletterAction('send', $campaign_id, ['list_id' => $list_id]);
            
            return [
                'success' => true,
                'message' => __('Newsletter sent successfully', 'newsletter-automation'),
                'campaign_id' => $campaign_id
            ];
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get campaign details
     */
    public function getCampaignDetails($campaign_id)
    {
        $data = [
            'campaign_id' => $campaign_id,
            'api_key' => $this->api_key
        ];

        $response = $this->makeApiRequest('/api/campaigns/summary.php', $data);

        if (is_array($response)) {
            return [
                'success' => true,
                'data' => $response
            ];
        } else {
            throw new Exception($this->parseErrorResponse($response));
        }
    }

    /**
     * Get subscriber lists
     */
    public function getLists($brand_id = null)
    {
        $data = [
            'api_key' => $this->api_key
        ];

        if ($brand_id) {
            $data['brand_id'] = $brand_id;
        }

        $response = $this->makeApiRequest('/api/lists/get-lists.php', $data);

        if (is_array($response)) {
            return [
                'success' => true,
                'lists' => $response
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->parseErrorResponse($response)
            ];
        }
    }

    /**
     * Test Sendy connection
     */
    public function testConnection()
    {
        try {
            $result = $this->getLists();
            
            if ($result['success']) {
                return [
                    'success' => true,
                    'message' => __('Sendy connection successful', 'newsletter-automation'),
                    'lists_count' => count($result['lists'])
                ];
            } else {
                return [
                    'success' => false,
                    'message' => __('Sendy connection failed', 'newsletter-automation'),
                    'error' => $result['error']
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => __('Sendy connection failed', 'newsletter-automation'),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Subscribe email to list
     */
    public function subscribe($email, $list_id, $name = '', $custom_fields = [])
    {
        $data = [
            'email' => $email,
            'list' => $list_id,
            'api_key' => $this->api_key,
            'boolean' => 'true'
        ];

        if (!empty($name)) {
            $data['name'] = $name;
        }

        // Add custom fields
        foreach ($custom_fields as $field => $value) {
            $data[$field] = $value;
        }

        $response = $this->makeApiRequest('/subscribe', $data);

        if ($response === '1') {
            return [
                'success' => true,
                'message' => __('Successfully subscribed', 'newsletter-automation')
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->parseErrorResponse($response)
            ];
        }
    }

    /**
     * Unsubscribe email from list
     */
    public function unsubscribe($email, $list_id)
    {
        $data = [
            'email' => $email,
            'list' => $list_id,
            'api_key' => $this->api_key,
            'boolean' => 'true'
        ];

        $response = $this->makeApiRequest('/unsubscribe', $data);

        if ($response === '1') {
            return [
                'success' => true,
                'message' => __('Successfully unsubscribed', 'newsletter-automation')
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->parseErrorResponse($response)
            ];
        }
    }

    /**
     * Get subscriber count for list
     */
    public function getSubscriberCount($list_id)
    {
        $data = [
            'list_id' => $list_id,
            'api_key' => $this->api_key
        ];

        $response = $this->makeApiRequest('/api/subscribers/active-subscriber-count.php', $data);

        if (is_numeric($response)) {
            return [
                'success' => true,
                'count' => intval($response)
            ];
        } else {
            return [
                'success' => false,
                'error' => $this->parseErrorResponse($response)
            ];
        }
    }

    /**
     * Convert HTML to plain text
     */
    private function htmlToPlainText($html)
    {
        // Remove HTML tags
        $text = wp_strip_all_tags($html);
        
        // Convert HTML entities
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Clean up whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Make API request to Sendy
     */
    private function makeApiRequest($endpoint, $data)
    {
        if (empty($this->api_url) || empty($this->api_key)) {
            throw new Exception(__('Sendy API URL or API key not configured', 'newsletter-automation'));
        }

        $url = $this->api_url . $endpoint;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => true
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        
        curl_close($ch);

        if ($curl_error) {
            throw new Exception(sprintf(__('Curl error: %s', 'newsletter-automation'), $curl_error));
        }

        if ($http_code !== 200) {
            throw new Exception(sprintf(__('HTTP error: %d', 'newsletter-automation'), $http_code));
        }

        // Try to decode JSON response
        $json_response = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $json_response;
        }

        // Return raw response if not JSON
        return trim($response);
    }

    /**
     * Parse error response from Sendy
     */
    private function parseErrorResponse($response)
    {
        $error_messages = [
            'No data passed' => __('No data was sent to Sendy', 'newsletter-automation'),
            'API key not passed' => __('Sendy API key is missing', 'newsletter-automation'),
            'Invalid API key' => __('Invalid Sendy API key', 'newsletter-automation'),
            'Brand ID not passed' => __('Brand ID is required', 'newsletter-automation'),
            'Brand does not exist' => __('The specified brand does not exist', 'newsletter-automation'),
            'List ID not passed' => __('List ID is required', 'newsletter-automation'),
            'List does not exist' => __('The specified list does not exist', 'newsletter-automation'),
            'From name not passed' => __('From name is required', 'newsletter-automation'),
            'From email not passed' => __('From email is required', 'newsletter-automation'),
            'Reply to email not passed' => __('Reply-to email is required', 'newsletter-automation'),
            'Subject not passed' => __('Email subject is required', 'newsletter-automation'),
            'HTML not passed' => __('Email HTML content is required', 'newsletter-automation'),
            'Campaign does not exist' => __('The specified campaign does not exist', 'newsletter-automation'),
            'Campaign not sent' => __('Failed to send campaign', 'newsletter-automation'),
            'Segment does not exist' => __('The specified segment does not exist', 'newsletter-automation')
        ];

        return $error_messages[$response] ?? sprintf(__('Sendy error: %s', 'newsletter-automation'), $response);
    }

    /**
     * Get latest campaign ID for a list
     */
    private function getLatestCampaignId($list_id)
    {
        // This is a simplified approach - in a real implementation,
        // you might want to store campaign IDs or use Sendy's API to retrieve them
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsletter_automation_logs';
        
        $campaign_id = $wpdb->get_var($wpdb->prepare(
            "SELECT newsletter_id FROM $table_name 
             WHERE status = 'created' 
             ORDER BY created_at DESC 
             LIMIT 1"
        ));

        return $campaign_id ?: 'unknown';
    }

    /**
     * Log newsletter action
     */
    private function logNewsletterAction($action, $campaign_id, $data)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsletter_automation_logs';
        
        $log_data = [
            'newsletter_id' => $campaign_id,
            'post_ids' => isset($data['posts']) ? json_encode(wp_list_pluck($data['posts'], 'id')) : '',
            'subject' => $data['subject'] ?? '',
            'status' => $action,
            'created_at' => current_time('mysql')
        ];

        if ($action === 'send') {
            $log_data['sent_at'] = current_time('mysql');
        }

        $wpdb->insert($table_name, $log_data);
    }

    /**
     * Get newsletter statistics
     */
    public function getNewsletterStats()
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsletter_automation_logs';
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_newsletters,
                COUNT(CASE WHEN status = 'sent' THEN 1 END) as sent_newsletters,
                COUNT(CASE WHEN status = 'created' THEN 1 END) as draft_newsletters,
                COUNT(CASE WHEN error_message IS NOT NULL THEN 1 END) as failed_newsletters
            FROM $table_name
        ", ARRAY_A);

        return $stats ?: [
            'total_newsletters' => 0,
            'sent_newsletters' => 0,
            'draft_newsletters' => 0,
            'failed_newsletters' => 0
        ];
    }

    /**
     * Get recent newsletter activity
     */
    public function getRecentActivity($limit = 10)
    {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'newsletter_automation_logs';
        
        $activities = $wpdb->get_results($wpdb->prepare("
            SELECT newsletter_id, subject, status, created_at, sent_at, error_message
            FROM $table_name 
            ORDER BY created_at DESC 
            LIMIT %d
        ", $limit), ARRAY_A);

        return $activities ?: [];
    }

    /**
     * Validate Sendy configuration
     */
    public function validateConfig()
    {
        $errors = [];

        if (empty($this->api_url)) {
            $errors[] = __('Sendy URL is not configured', 'newsletter-automation');
        } elseif (!filter_var($this->api_url, FILTER_VALIDATE_URL)) {
            $errors[] = __('Invalid Sendy URL format', 'newsletter-automation');
        }

        if (empty($this->api_key)) {
            $errors[] = __('Sendy API key is not configured', 'newsletter-automation');
        }

        if (empty($this->config['default_list_id'])) {
            $errors[] = __('Default list ID is not configured', 'newsletter-automation');
        }

        return [
            'is_valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Schedule newsletter for later sending
     */
    public function scheduleNewsletter($data, $send_time)
    {
        // Create the campaign first without sending
        $data['send_immediately'] = false;
        $result = $this->createNewsletter($data);

        if ($result['success']) {
            // Schedule using WordPress cron
            $timestamp = strtotime($send_time);
            
            wp_schedule_single_event($timestamp, 'nap_send_scheduled_newsletter', [
                'campaign_id' => $result['campaign_id'],
                'list_id' => $data['list_id']
            ]);

            return [
                'success' => true,
                'message' => __('Newsletter scheduled successfully', 'newsletter-automation'),
                'campaign_id' => $result['campaign_id'],
                'scheduled_time' => $send_time
            ];
        }

        return $result;
    }
}
