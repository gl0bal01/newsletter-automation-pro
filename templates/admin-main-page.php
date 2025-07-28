<?php
/**
 * Admin Main Page Template
 * Phase 1 - WordPress Admin Interface
 */
?>

<div class="wrap nap-admin-wrap">
    <h1><?php _e('Newsletter Automation Pro', 'newsletter-automation'); ?></h1>
    <p class="description"><?php _e('Create and send automated newsletters with AI-generated descriptions', 'newsletter-automation'); ?></p>

    <div class="nap-admin-container">
        <!-- Step 1: Post Selection -->
        <div class="nap-step nap-step-active" id="step-1">
            <div class="nap-step-header">
                <h2><span class="step-number">1</span><?php _e('Select Articles', 'newsletter-automation'); ?></h2>
                <p><?php _e('Search and select articles for your newsletter', 'newsletter-automation'); ?></p>
            </div>

            <div class="nap-search-section">
                <div class="nap-search-controls">
                    <input type="text" id="post-search" placeholder="<?php _e('Search articles...', 'newsletter-automation'); ?>" class="regular-text">
                    <select id="post-type-filter">
                        <option value="post"><?php _e('Posts', 'newsletter-automation'); ?></option>
                        <option value="page"><?php _e('Pages', 'newsletter-automation'); ?></option>
                    </select>
                    <button type="button" id="search-posts" class="button"><?php _e('Search', 'newsletter-automation'); ?></button>
                </div>

                <div class="nap-quick-filters">
                    <button type="button" class="button" data-filter="recent"><?php _e('Recent Posts', 'newsletter-automation'); ?></button>
                    <button type="button" class="button" data-filter="featured"><?php _e('Featured', 'newsletter-automation'); ?></button>
                    <button type="button" class="button" data-filter="popular"><?php _e('Popular', 'newsletter-automation'); ?></button>
                </div>
            </div>

            <div class="nap-posts-grid" id="posts-grid">
                <div class="nap-loading" id="posts-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <p><?php _e('Loading posts...', 'newsletter-automation'); ?></p>
                </div>
                <div id="posts-results"></div>
            </div>
        </div>

        <!-- Step 2: Selected Posts & Reordering -->
        <div class="nap-step" id="step-2">
            <div class="nap-step-header">
                <h2><span class="step-number">2</span><?php _e('Arrange & Customize', 'newsletter-automation'); ?></h2>
                <p><?php _e('Drag to reorder your selected articles and customize descriptions', 'newsletter-automation'); ?></p>
            </div>

            <div class="nap-selected-posts-section">
                <div class="nap-section-header">
                    <h3><?php _e('Selected Articles', 'newsletter-automation'); ?> <span id="selected-count">0</span></h3>
                    <button type="button" id="generate-descriptions" class="button button-primary" disabled>
                        <?php _e('Generate AI Descriptions', 'newsletter-automation'); ?>
                    </button>
                </div>

                <div id="selected-posts-list" class="nap-sortable-list">
                    <div class="nap-empty-state" id="empty-selection">
                        <p><?php _e('No articles selected yet. Choose articles from the search results above.', 'newsletter-automation'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 3: Newsletter Configuration -->
        <div class="nap-step" id="step-3">
            <div class="nap-step-header">
                <h2><span class="step-number">3</span><?php _e('Newsletter Setup', 'newsletter-automation'); ?></h2>
                <p><?php _e('Configure your newsletter settings and send', 'newsletter-automation'); ?></p>
            </div>

            <div class="nap-newsletter-config">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="newsletter-subject"><?php _e('Subject Line', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="newsletter-subject" class="regular-text" placeholder="<?php _e('Enter newsletter subject...', 'newsletter-automation'); ?>">
                            <p class="description"><?php _e('Keep it under 60 characters for best results', 'newsletter-automation'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="newsletter-list"><?php _e('Sendy List', 'newsletter-automation'); ?></label>
                        </th>
                        <td>
                            <select id="newsletter-list" class="regular-text">
                                <option value=""><?php _e('Loading lists...', 'newsletter-automation'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Send Options', 'newsletter-automation'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="send-option" value="immediate" checked>
                                <?php _e('Send immediately', 'newsletter-automation'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="send-option" value="schedule">
                                <?php _e('Schedule for later', 'newsletter-automation'); ?>
                            </label>
                            <input type="datetime-local" id="schedule-time" style="margin-left: 10px; display: none;">
                        </td>
                    </tr>
                </table>

                <div class="nap-newsletter-preview">
                    <h4><?php _e('Preview', 'newsletter-automation'); ?></h4>
                    <div id="newsletter-preview-content">
                        <p class="description"><?php _e('Select articles and generate descriptions to see preview', 'newsletter-automation'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="nap-actions">
            <div class="nap-steps-nav">
                <button type="button" id="prev-step" class="button" disabled><?php _e('Previous', 'newsletter-automation'); ?></button>
                <button type="button" id="next-step" class="button button-primary"><?php _e('Next', 'newsletter-automation'); ?></button>
            </div>

            <div class="nap-final-actions" style="display: none;">
                <button type="button" id="save-draft" class="button"><?php _e('Save as Draft', 'newsletter-automation'); ?></button>
                <button type="button" id="send-newsletter" class="button button-primary">
                    <?php _e('Create & Send Newsletter', 'newsletter-automation'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Status Messages -->
    <div id="nap-messages" class="nap-messages"></div>
</div>

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
