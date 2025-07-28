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
