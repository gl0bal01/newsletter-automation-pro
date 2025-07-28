<?php
/**
 * Newsletter Builder Service - Phase 3
 * Handles HTML newsletter generation and formatting
 */

class NAP_NewsletterBuilder
{
    private $template_engine;
    private $default_template;

    public function __construct($template_engine)
    {
        $this->template_engine = $template_engine;
        $this->default_template = $this->getDefaultTemplate();
    }

    /**
     * Build HTML newsletter from selected posts
     */
    public function buildNewsletter($posts_data, $options = [])
    {
        $defaults = [
            'template' => 'default',
            'header_text' => get_bloginfo('name') . ' Newsletter',
            'footer_text' => 'Thanks for reading!',
            'brand_color' => '#2271b1',
            'background_color' => '#f0f0f1',
            'include_social_links' => true,
            'include_unsubscribe' => true
        ];

        $options = wp_parse_args($options, $defaults);

        // Get post search service to fetch complete post data
        $post_search = new NAP_PostSearchService();
        $complete_posts = [];

        foreach ($posts_data as $post_item) {
            $post_id = $post_item['id'];
            $custom_description = $post_item['description'] ?? '';
            
            $post_data = $post_search->getPostsForNewsletter([$post_id])[0];
            $post_data['custom_description'] = $custom_description;
            $complete_posts[] = $post_data;
        }

        // Build newsletter HTML
        $newsletter_html = $this->template_engine->render($options['template'], [
            'posts' => $complete_posts,
            'options' => $options,
            'site_info' => $this->getSiteInfo()
        ]);

        return $newsletter_html;
    }

    /**
     * Get site information for newsletter
     */
    private function getSiteInfo()
    {
        return [
            'name' => get_bloginfo('name'),
            'url' => get_bloginfo('url'),
            'description' => get_bloginfo('description'),
            'admin_email' => get_bloginfo('admin_email'),
            'logo_url' => $this->getSiteLogo()
        ];
    }

    /**
     * Get site logo URL
     */
    private function getSiteLogo()
    {
        $custom_logo_id = get_theme_mod('custom_logo');
        
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'medium');
            return $logo_data[0] ?? '';
        }

        return '';
    }

    /**
     * Generate preview of newsletter
     */
    public function generatePreview($posts_data, $options = [])
    {
        $html = $this->buildNewsletter($posts_data, $options);
        
        return [
            'html' => $html,
            'text_preview' => $this->generateTextPreview($html),
            'estimated_size' => strlen($html),
            'post_count' => count($posts_data)
        ];
    }

    /**
     * Generate text preview from HTML
     */
    private function generateTextPreview($html)
    {
        $text = wp_strip_all_tags($html);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) > 500) {
            $text = substr($text, 0, 500) . '...';
        }

        return $text;
    }

    /**
     * Validate newsletter content
     */
    public function validateNewsletter($posts_data)
    {
        $errors = [];
        $warnings = [];

        // Check if posts are provided
        if (empty($posts_data)) {
            $errors[] = __('No posts selected for newsletter', 'newsletter-automation');
            return ['errors' => $errors, 'warnings' => $warnings, 'is_valid' => false];
        }

        foreach ($posts_data as $index => $post_item) {
            $post_id = $post_item['id'];
            $post = get_post($post_id);

            if (!$post) {
                $errors[] = sprintf(__('Post #%d not found', 'newsletter-automation'), $index + 1);
                continue;
            }

            // Check featured image
            if (!has_post_thumbnail($post_id)) {
                $warnings[] = sprintf(__('Post "%s" has no featured image', 'newsletter-automation'), $post->post_title);
            }

            // Check description
            if (empty($post_item['description'])) {
                $warnings[] = sprintf(__('Post "%s" has no description', 'newsletter-automation'), $post->post_title);
            }

            // Check description length
            if (!empty($post_item['description'])) {
                $word_count = str_word_count($post_item['description']);
                if ($word_count > 20) {
                    $warnings[] = sprintf(__('Description for "%s" is quite long (%d words)', 'newsletter-automation'), $post->post_title, $word_count);
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'is_valid' => empty($errors)
        ];
    }

    /**
     * Get default newsletter template
     */
    private function getDefaultTemplate()
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{header_text}}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: {{background_color}};
            line-height: 1.6;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            background-color: {{brand_color}};
            color: white;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 20px;
        }
        .post-item {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .post-item:last-child {
            border-bottom: none;
        }
        .post-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .post-title {
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 10px 0;
            color: #333;
        }
        .post-title a {
            color: {{brand_color}};
            text-decoration: none;
        }
        .post-title a:hover {
            text-decoration: underline;
        }
        .post-description {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .post-meta {
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
        }
        .read-more {
            display: inline-block;
            background-color: {{brand_color}};
            color: white;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 3px;
            font-size: 14px;
        }
        .read-more:hover {
            background-color: #1e5a8a;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
            border-top: 1px solid #eee;
        }
        .social-links {
            margin: 15px 0;
        }
        .social-links a {
            color: {{brand_color}};
            text-decoration: none;
            margin: 0 10px;
        }
        @media only screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }
            .header, .content, .footer {
                padding: 15px !important;
            }
            .post-title {
                font-size: 18px !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            {{#if site_info.logo_url}}
            <img src="{{site_info.logo_url}}" alt="{{site_info.name}}" style="max-height: 40px; margin-bottom: 10px;">
            {{/if}}
            <h1>{{header_text}}</h1>
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
                    {{#if categories}}
                    • {{#each categories}}{{name}}{{#unless @last}}, {{/unless}}{{/each}}
                    {{/if}}
                </div>
                
                {{#if custom_description}}
                <div class="post-description">{{custom_description}}</div>
                {{/if}}
                
                <a href="{{permalink}}" class="read-more">Read More</a>
            </div>
            {{/each}}
        </div>
        
        <div class="footer">
            <p>{{footer_text}}</p>
            
            {{#if options.include_social_links}}
            <div class="social-links">
                <a href="{{site_info.url}}">Visit Website</a>
            </div>
            {{/if}}
            
            {{#if options.include_unsubscribe}}
            <p>
                <a href="[unsubscribe]" style="color: #999;">Unsubscribe</a> | 
                <a href="[webversion]" style="color: #999;">View in Browser</a>
            </p>
            {{/if}}
            
            <p style="margin-top: 20px;">
                <small>{{site_info.name}} • {{site_info.url}}</small>
            </p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Export newsletter as HTML file
     */
    public function exportAsHtml($posts_data, $options = [], $filename = null)
    {
        $html = $this->buildNewsletter($posts_data, $options);
        
        if (!$filename) {
            $filename = 'newsletter_' . date('Y-m-d_H-i-s') . '.html';
        }

        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        if (file_put_contents($file_path, $html) !== false) {
            return [
                'success' => true,
                'file_path' => $file_path,
                'file_url' => $upload_dir['url'] . '/' . $filename,
                'filename' => $filename
            ];
        }

        return [
            'success' => false,
            'error' => __('Failed to create HTML file', 'newsletter-automation')
        ];
    }

    /**
     * Get available newsletter templates
     */
    public function getAvailableTemplates()
    {
        $templates = [
            'default' => [
                'name' => __('Default Template', 'newsletter-automation'),
                'description' => __('Clean, professional newsletter template', 'newsletter-automation'),
                'preview_image' => NAP_PLUGIN_URL . 'assets/images/template-default.png'
            ],
            'minimal' => [
                'name' => __('Minimal Template', 'newsletter-automation'),
                'description' => __('Simple, text-focused design', 'newsletter-automation'),
                'preview_image' => NAP_PLUGIN_URL . 'assets/images/template-minimal.png'
            ],
            'magazine' => [
                'name' => __('Magazine Template', 'newsletter-automation'),
                'description' => __('Rich, magazine-style layout', 'newsletter-automation'),
                'preview_image' => NAP_PLUGIN_URL . 'assets/images/template-magazine.png'
            ]
        ];

        // Allow themes and other plugins to add templates
        return apply_filters('nap_newsletter_templates', $templates);
    }
}
