<?php
/**
 * Admin Settings Page Template
 */
?>

<div class="wrap nap-settings-wrap">
    <h1><?php _e('Newsletter Automation Settings', 'newsletter-automation'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('nap_settings_nonce'); ?>
        
        <div class="nap-settings-tabs">
            <nav class="nav-tab-wrapper">
                <a href="#ai-settings" class="nav-tab nav-tab-active"><?php _e('AI Configuration', 'newsletter-automation'); ?></a>
                <a href="#sendy-settings" class="nav-tab"><?php _e('Sendy Integration', 'newsletter-automation'); ?></a>
                <a href="#template-settings" class="nav-tab"><?php _e('Templates', 'newsletter-automation'); ?></a>
                <a href="#general-settings" class="nav-tab"><?php _e('General', 'newsletter-automation'); ?></a>
            </nav>

            <!-- AI Configuration Tab -->
            <div id="ai-settings" class="tab-content active">
                <h2><?php _e('AI Service Configuration', 'newsletter-automation'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('AI Service', 'newsletter-automation'); ?></th>
                        <td>
                            <fieldset>
                                <label>
                                    <input type="radio" name="ai_service" value="openai" <?php checked(get_option('nap_ai_service', 'openai'), 'openai'); ?>>
                                    OpenAI (GPT-4)
                                </label><br>
                                <label>
                                    <input type="radio" name="ai_service" value="claude" <?php checked(get_option('nap_ai_service'), 'claude'); ?>>
                                    Anthropic Claude
                                </label>
                            </fieldset>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php _e('OpenAI API Key', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="openai_api_key" name="openai_api_key" value="<?php echo esc_attr(get_option('nap_openai_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Get your API key from OpenAI dashboard', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="claude_api_key"><?php _e('Claude API Key', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="claude_api_key" name="claude_api_key" value="<?php echo esc_attr(get_option('nap_claude_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Get your API key from Anthropic console', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="nap-test-section">
                    <h3><?php _e('Test AI Connection', 'newsletter-automation'); ?></h3>
                    <button type="button" id="test-ai-connection" class="button"><?php _e('Test Connection', 'newsletter-automation'); ?></button>
                    <div id="ai-test-result"></div>
                </div>
            </div>

            <!-- Sendy Integration Tab -->
            <div id="sendy-settings" class="tab-content">
                <h2><?php _e('Sendy Configuration', 'newsletter-automation'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="sendy_url"><?php _e('Sendy Installation URL', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="sendy_url" name="sendy_url" value="<?php echo esc_attr(get_option('nap_sendy_url')); ?>" class="regular-text">
                            <p class="description"><?php _e('Your Sendy installation URL (e.g., https://newsletter.yoursite.com)', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="sendy_api_key"><?php _e('Sendy API Key', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="sendy_api_key" name="sendy_api_key" value="<?php echo esc_attr(get_option('nap_sendy_api_key')); ?>" class="regular-text">
                            <p class="description"><?php _e('Found in your Sendy settings', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="default_list_id"><?php _e('Default List ID', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="default_list_id" name="default_list_id" value="<?php echo esc_attr(get_option('nap_default_list_id')); ?>" class="regular-text">
                            <p class="description"><?php _e('Default subscriber list for newsletters', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                </table>

                <div class="nap-test-section">
                    <h3><?php _e('Test Sendy Connection', 'newsletter-automation'); ?></h3>
                    <button type="button" id="test-sendy-connection" class="button"><?php _e('Test Connection', 'newsletter-automation'); ?></button>
                    <div id="sendy-test-result"></div>
                </div>
            </div>

            <!-- Templates Tab -->
            <div id="template-settings" class="tab-content">
                <h2><?php _e('Newsletter Templates', 'newsletter-automation'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Default Template', 'newsletter-automation'); ?></th>
                        <td>
                            <select name="newsletter_template" class="regular-text">
                                <option value="default" <?php selected(get_option('nap_newsletter_template', 'default'), 'default'); ?>>
                                    <?php _e('Default Template', 'newsletter-automation'); ?>
                                </option>
                                <option value="minimal" <?php selected(get_option('nap_newsletter_template'), 'minimal'); ?>>
                                    <?php _e('Minimal Template', 'newsletter-automation'); ?>
                                </option>
                                <option value="magazine" <?php selected(get_option('nap_newsletter_template'), 'magazine'); ?>>
                                    <?php _e('Magazine Template', 'newsletter-automation'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>

                <div class="nap-template-preview">
                    <h3><?php _e('Template Preview', 'newsletter-automation'); ?></h3>
                    <div id="template-preview-area">
                        <p class="description"><?php _e('Select a template to see preview', 'newsletter-automation'); ?></p>
                    </div>
                </div>
            </div>

            <!-- General Settings Tab -->
            <div id="general-settings" class="tab-content">
                <h2><?php _e('General Settings', 'newsletter-automation'); ?></h2>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="max_description_words"><?php _e('Max Description Words', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="max_description_words" name="max_description_words" value="<?php echo esc_attr(get_option('nap_max_description_words', 14)); ?>" min="5" max="50" class="small-text">
                            <p class="description"><?php _e('Maximum words for AI-generated descriptions', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="brand_color"><?php _e('Brand Color', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="color" id="brand_color" name="brand_color" value="<?php echo esc_attr(get_option('nap_brand_color', '#2271b1')); ?>">
                            <p class="description"><?php _e('Primary color for newsletter templates', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Debug Mode', 'newsletter-automation'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="debug_mode" value="1" <?php checked(get_option('nap_debug_mode', 0), 1); ?>>
                                <?php _e('Enable debug logging', 'newsletter-automation'); ?>
                            </label>
                            <p class="description"><?php _e('Log detailed information for troubleshooting', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        
        $('.nav-tab').removeClass('nav-tab-active');
        $('.tab-content').removeClass('active');
        
        $(this).addClass('nav-tab-active');
        $($(this).attr('href')).addClass('active');
    });

    // Test AI connection
    $('#test-ai-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#ai-test-result');
        
        $button.prop('disabled', true).text('<?php _e('Testing...', 'newsletter-automation'); ?>');
        
        $.post(ajaxurl, {
            action: 'nap_test_ai_connection',
            nonce: '<?php echo wp_create_nonce('nap_test_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Test Connection', 'newsletter-automation'); ?>');
        });
    });

    // Test Sendy connection
    $('#test-sendy-connection').on('click', function() {
        var $button = $(this);
        var $result = $('#sendy-test-result');
        
        $button.prop('disabled', true).text('<?php _e('Testing...', 'newsletter-automation'); ?>');
        
        $.post(ajaxurl, {
            action: 'nap_test_sendy_connection',
            nonce: '<?php echo wp_create_nonce('nap_test_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                $result.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            } else {
                $result.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
        }).always(function() {
            $button.prop('disabled', false).text('<?php _e('Test Connection', 'newsletter-automation'); ?>');
        });
    });
});
</script>
