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
