/**
 * Newsletter Automation Pro - Admin JavaScript
 * Handles all frontend interactions for the admin interface
 */

(function($) {
    'use strict';

    // Main application object
    const NewsletterApp = {
        selectedPosts: [],
        currentStep: 1,
        totalSteps: 3,

        init: function() {
            this.bindEvents();
            this.loadInitialData();
            this.initSortable();
        },

        bindEvents: function() {
            // Step navigation
            $('#next-step').on('click', this.nextStep.bind(this));
            $('#prev-step').on('click', this.prevStep.bind(this));

            // Post search
            $('#search-posts').on('click', this.searchPosts.bind(this));
            $('#post-search').on('keypress', function(e) {
                if (e.which === 13) {
                    NewsletterApp.searchPosts();
                }
            });

            // Quick filters
            $('.nap-quick-filters button').on('click', this.handleQuickFilter.bind(this));

            // Description generation
            $('#generate-descriptions').on('click', this.generateDescriptions.bind(this));

            // Newsletter actions
            $('#send-newsletter').on('click', this.sendNewsletter.bind(this));
            $('#save-draft').on('click', this.saveDraft.bind(this));

            // Send options
            $('input[name="send-option"]').on('change', this.toggleScheduleTime.bind(this));

            // Subject line validation
            $('#newsletter-subject').on('input', this.validateSubject.bind(this));
        },

        loadInitialData: function() {
            this.searchPosts(); // Load recent posts
            this.loadSendyLists();
        },

        initSortable: function() {
            $('#selected-posts-list').sortable({
                items: '.nap-selected-post',
                handle: '.nap-drag-handle',
                placeholder: 'nap-sort-placeholder',
                update: this.updatePostOrder.bind(this)
            });
        },

        // Step Navigation
        nextStep: function() {
            if (this.currentStep < this.totalSteps) {
                if (this.validateCurrentStep()) {
                    this.currentStep++;
                    this.updateStepDisplay();
                }
            }
        },

        prevStep: function() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateStepDisplay();
            }
        },

        updateStepDisplay: function() {
            // Hide all steps
            $('.nap-step').removeClass('nap-step-active');
            
            // Show current step
            $('#step-' + this.currentStep).addClass('nap-step-active');

            // Update navigation buttons
            $('#prev-step').prop('disabled', this.currentStep === 1);
            
            if (this.currentStep === this.totalSteps) {
                $('#next-step').hide();
                $('.nap-final-actions').show();
            } else {
                $('#next-step').show().prop('disabled', false);
                $('.nap-final-actions').hide();
            }

            // Update step indicators
            $('.step-number').removeClass('active completed');
            for (let i = 1; i <= this.totalSteps; i++) {
                if (i < this.currentStep) {
                    $('#step-' + i + ' .step-number').addClass('completed');
                } else if (i === this.currentStep) {
                    $('#step-' + i + ' .step-number').addClass('active');
                }
            }

            // Generate preview for step 3
            if (this.currentStep === 3) {
                this.generatePreview();
            }
        },

        validateCurrentStep: function() {
            switch (this.currentStep) {
                case 1:
                    return this.selectedPosts.length > 0;
                case 2:
                    return this.selectedPosts.every(post => post.description && post.description.trim());
                default:
                    return true;
            }
        },

        // Post Search & Selection
        searchPosts: function() {
            const searchTerm = $('#post-search').val();
            const postType = $('#post-type-filter').val();
            
            $('#posts-loading').show();
            $('#posts-results').empty();

            $.post(napAjax.ajaxurl, {
                action: 'nap_search_posts',
                nonce: napAjax.nonce,
                search_term: searchTerm,
                post_type: postType,
                posts_per_page: 20
            }, this.handleSearchResults.bind(this))
            .fail(this.handleAjaxError.bind(this))
            .always(() => $('#posts-loading').hide());
        },

        handleQuickFilter: function(e) {
            const filter = $(e.target).data('filter');
            
            $('#post-search').val('');
            $('#posts-loading').show();
            $('#posts-results').empty();

            $.post(napAjax.ajaxurl, {
                action: 'nap_search_posts',
                nonce: napAjax.nonce,
                filter: filter,
                posts_per_page: 20
            }, this.handleSearchResults.bind(this))
            .fail(this.handleAjaxError.bind(this))
            .always(() => $('#posts-loading').hide());
        },

        handleSearchResults: function(response) {
            if (response.success) {
                this.renderSearchResults(response.data.posts);
            } else {
                this.showMessage('error', response.data || napAjax.strings.error);
            }
        },

        renderSearchResults: function(posts) {
            const $container = $('#posts-results');
            $container.empty();

            if (posts.length === 0) {
                $container.html('<p class="nap-no-results">' + napAjax.strings.no_results + '</p>');
                return;
            }

            posts.forEach(post => {
                const isSelected = this.selectedPosts.some(p => p.id === post.id);
                const $postCard = $(`
                    <div class="nap-post-card ${isSelected ? 'selected' : ''}" data-post-id="${post.id}">
                        <div class="nap-post-image">
                            ${post.featured_image.url ? 
                                `<img src="${post.featured_image.url}" alt="${post.featured_image.alt}">` : 
                                '<div class="nap-no-image">No Image</div>'
                            }
                        </div>
                        <div class="nap-post-content">
                            <h4 class="nap-post-title">${post.title}</h4>
                            <p class="nap-post-excerpt">${post.excerpt}</p>
                            <div class="nap-post-meta">
                                <span class="author">By ${post.author}</span>
                                <span class="date">${post.date}</span>
                                <span class="words">${post.word_count} words</span>
                            </div>
                            <button type="button" class="button nap-select-post" ${isSelected ? 'disabled' : ''}>
                                ${isSelected ? 'Selected' : 'Select'}
                            </button>
                        </div>
                    </div>
                `);

                $postCard.find('.nap-select-post').on('click', this.selectPost.bind(this, post));
                $container.append($postCard);
            });
        },

        selectPost: function(post) {
            // Check if already selected
            if (this.selectedPosts.some(p => p.id === post.id)) {
                return;
            }

            // Add to selected posts
            this.selectedPosts.push({
                id: post.id,
                title: post.title,
                featured_image: post.featured_image,
                excerpt: post.excerpt,
                author: post.author,
                date: post.date,
                permalink: post.permalink,
                description: '' // Will be generated later
            });

            this.updateSelectedPostsDisplay();
            this.updatePostCard(post.id, true);
        },

        unselectPost: function(postId) {
            this.selectedPosts = this.selectedPosts.filter(p => p.id !== postId);
            this.updateSelectedPostsDisplay();
            this.updatePostCard(postId, false);
        },

        updatePostCard: function(postId, selected) {
            const $card = $(`.nap-post-card[data-post-id="${postId}"]`);
            const $button = $card.find('.nap-select-post');
            
            if (selected) {
                $card.addClass('selected');
                $button.prop('disabled', true).text('Selected');
            } else {
                $card.removeClass('selected');
                $button.prop('disabled', false).text('Select');
            }
        },

        updateSelectedPostsDisplay: function() {
            const $container = $('#selected-posts-list');
            const $emptyState = $('#empty-selection');
            const $counter = $('#selected-count');

            $counter.text(this.selectedPosts.length);
            $('#generate-descriptions').prop('disabled', this.selectedPosts.length === 0);

            if (this.selectedPosts.length === 0) {
                $emptyState.show();
                return;
            }

            $emptyState.hide();
            $container.children('.nap-selected-post').remove();

            this.selectedPosts.forEach((post, index) => {
                const $selectedPost = $(`
                    <div class="nap-selected-post" data-post-id="${post.id}">
                        <div class="nap-drag-handle">
                            <span class="dashicons dashicons-menu"></span>
                        </div>
                        <div class="nap-selected-content">
                            <div class="nap-selected-image">
                                ${post.featured_image.url ? 
                                    `<img src="${post.featured_image.url}" alt="${post.featured_image.alt}">` : 
                                    '<div class="nap-no-image">No Image</div>'
                                }
                            </div>
                            <div class="nap-selected-details">
                                <h4>${post.title}</h4>
                                <p class="meta">By ${post.author} • ${post.date}</p>
                                <div class="nap-description-section">
                                    <label>AI Description:</label>
                                    <textarea class="nap-description-input" placeholder="AI description will appear here..." rows="2">${post.description}</textarea>
                                    <div class="nap-description-status">
                                        <span class="word-count">0 words</span>
                                        <span class="validation-status"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="nap-selected-actions">
                                <button type="button" class="button-link nap-remove-post" title="Remove">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                `);

                // Bind events
                $selectedPost.find('.nap-remove-post').on('click', () => this.unselectPost(post.id));
                $selectedPost.find('.nap-description-input').on('input', this.updateDescription.bind(this, post.id));

                $container.append($selectedPost);
            });

            this.updateDescriptionWordCounts();
        },

        updateDescription: function(postId, e) {
            const description = $(e.target).val();
            const post = this.selectedPosts.find(p => p.id === postId);
            
            if (post) {
                post.description = description;
                this.updateDescriptionWordCount($(e.target));
            }
        },

        updateDescriptionWordCounts: function() {
            $('.nap-description-input').each((index, element) => {
                this.updateDescriptionWordCount($(element));
            });
        },

        updateDescriptionWordCount: function($textarea) {
            const text = $textarea.val();
            const words = text.trim() ? text.trim().split(/\s+/).length : 0;
            const $status = $textarea.siblings('.nap-description-status');
            const $wordCount = $status.find('.word-count');
            const $validation = $status.find('.validation-status');

            $wordCount.text(words + ' words');

            // Validation
            if (words > 14) {
                $validation.text('Too long').addClass('error').removeClass('warning success');
            } else if (words < 5) {
                $validation.text('Too short').addClass('warning').removeClass('error success');
            } else {
                $validation.text('Good').addClass('success').removeClass('error warning');
            }
        },

        updatePostOrder: function() {
            const newOrder = [];
            $('#selected-posts-list .nap-selected-post').each(function() {
                const postId = parseInt($(this).data('post-id'));
                const post = NewsletterApp.selectedPosts.find(p => p.id === postId);
                if (post) {
                    newOrder.push(post);
                }
            });
            this.selectedPosts = newOrder;
        },

        // AI Description Generation
        generateDescriptions: function() {
            if (this.selectedPosts.length === 0) {
                return;
            }

            const $button = $('#generate-descriptions');
            $button.prop('disabled', true).text(napAjax.strings.generating);

            const postIds = this.selectedPosts.map(p => p.id);

            $.post(napAjax.ajaxurl, {
                action: 'nap_generate_descriptions',
                nonce: napAjax.nonce,
                post_ids: postIds
            }, this.handleDescriptionResults.bind(this))
            .fail(this.handleAjaxError.bind(this))
            .always(() => {
                $button.prop('disabled', false).text('Generate AI Descriptions');
            });
        },

        handleDescriptionResults: function(response) {
            if (response.success) {
                const descriptions = response.data;
                
                this.selectedPosts.forEach(post => {
                    if (descriptions[post.id] && descriptions[post.id].success) {
                        post.description = descriptions[post.id].description;
                    }
                });

                this.updateSelectedPostsDisplay();
                this.showMessage('success', 'AI descriptions generated successfully!');
            } else {
                this.showMessage('error', response.data || napAjax.strings.error);
            }
        },

        // Newsletter Creation & Sending
        generatePreview: function() {
            if (this.selectedPosts.length === 0) {
                return;
            }

            const posts = this.selectedPosts.map(post => ({
                id: post.id,
                description: post.description
            }));

            $.post(napAjax.ajaxurl, {
                action: 'nap_generate_preview',
                nonce: napAjax.nonce,
                posts: posts
            }, function(response) {
                if (response.success) {
                    $('#newsletter-preview-content').html(response.data.html);
                }
            });
        },

        sendNewsletter: function() {
            if (!this.validateNewsletterForm()) {
                return;
            }

            const $button = $('#send-newsletter');
            $button.prop('disabled', true).text(napAjax.strings.creating);

            const data = this.getNewsletterData();

            $.post(napAjax.ajaxurl, {
                action: 'nap_create_newsletter',
                nonce: napAjax.nonce,
                ...data
            }, this.handleNewsletterResult.bind(this))
            .fail(this.handleAjaxError.bind(this))
            .always(() => {
                $button.prop('disabled', false).text('Create & Send Newsletter');
            });
        },

        saveDraft: function() {
            // Similar to sendNewsletter but with send_immediately: false
            const data = this.getNewsletterData();
            data.send_immediately = false;

            const $button = $('#save-draft');
            $button.prop('disabled', true).text('Saving...');

            $.post(napAjax.ajaxurl, {
                action: 'nap_create_newsletter',
                nonce: napAjax.nonce,
                ...data
            }, this.handleNewsletterResult.bind(this))
            .fail(this.handleAjaxError.bind(this))
            .always(() => {
                $button.prop('disabled', false).text('Save as Draft');
            });
        },

        getNewsletterData: function() {
            const posts = this.selectedPosts.map(post => ({
                id: post.id,
                description: post.description
            }));

            const sendOption = $('input[name="send-option"]:checked').val();
            
            return {
                posts: posts,
                subject: $('#newsletter-subject').val(),
                list_id: $('#newsletter-list').val(),
                send_immediately: sendOption === 'immediate',
                schedule_time: sendOption === 'schedule' ? $('#schedule-time').val() : null
            };
        },

        validateNewsletterForm: function() {
            const subject = $('#newsletter-subject').val().trim();
            const listId = $('#newsletter-list').val();

            if (!subject) {
                this.showMessage('error', 'Please enter a subject line');
                return false;
            }

            if (!listId) {
                this.showMessage('error', 'Please select a Sendy list');
                return false;
            }

            if (this.selectedPosts.length === 0) {
                this.showMessage('error', 'Please select at least one article');
                return false;
            }

            // Check if all posts have descriptions
            const missingDescriptions = this.selectedPosts.filter(p => !p.description || !p.description.trim());
            if (missingDescriptions.length > 0) {
                this.showMessage('error', 'Please generate descriptions for all selected articles');
                return false;
            }

            return true;
        },

        handleNewsletterResult: function(response) {
            if (response.success) {
                this.showMessage('success', response.data.message);
                // Optionally redirect or reset form
            } else {
                this.showMessage('error', response.data || napAjax.strings.error);
            }
        },

        // Utility Functions
        loadSendyLists: function() {
            $.post(napAjax.ajaxurl, {
                action: 'nap_get_sendy_lists',
                nonce: napAjax.nonce
            }, function(response) {
                const $select = $('#newsletter-list');
                $select.empty();

                if (response.success && response.data.lists) {
                    response.data.lists.forEach(list => {
                        $select.append(`<option value="${list.id}">${list.name}</option>`);
                    });
                } else {
                    $select.append('<option value="">No lists available</option>');
                }
            });
        },

        toggleScheduleTime: function() {
            const sendOption = $('input[name="send-option"]:checked').val();
            if (sendOption === 'schedule') {
                $('#schedule-time').show();
            } else {
                $('#schedule-time').hide();
            }
        },

        validateSubject: function() {
            const subject = $('#newsletter-subject').val();
            const length = subject.length;
            
            // Simple validation feedback
            if (length > 60) {
                $('#newsletter-subject').addClass('warning');
            } else {
                $('#newsletter-subject').removeClass('warning');
            }
        },

        showMessage: function(type, message) {
            const $container = $('#nap-messages');
            const $message = $(`
                <div class="notice notice-${type} is-dismissible">
                    <p>${message}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);

            $container.append($message);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                $message.fadeOut(() => $message.remove());
            }, 5000);

            // Manual dismiss
            $message.find('.notice-dismiss').on('click', () => {
                $message.fadeOut(() => $message.remove());
            });
        },

        handleAjaxError: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            this.showMessage('error', napAjax.strings.error);
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        NewsletterApp.init();
    });

})(jQuery);

/* CSS Styles for Newsletter Automation Pro */
.nap-admin-wrap {
    max-width: 1200px;
    margin: 20px 0;
}

.nap-admin-container {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin: 20px 0;
}

.nap-step {
    display: none;
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.nap-step.nap-step-active {
    display: block;
}

.nap-step-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f1;
}

.nap-step-header h2 {
    margin: 0 0 10px 0;
    display: flex;
    align-items: center;
    gap: 15px;
}

.step-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #f0f0f1;
    color: #666;
    font-weight: bold;
    font-size: 14px;
}

.step-number.active {
    background: #2271b1;
    color: white;
}

.step-number.completed {
    background: #00a32a;
    color: white;
}

.nap-search-section {
    margin-bottom: 20px;
}

.nap-search-controls {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    align-items: center;
}

.nap-search-controls input[type="text"] {
    flex: 1;
}

.nap-quick-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.nap-posts-grid {
    margin-top: 20px;
}

.nap-loading {
    text-align: center;
    padding: 40px;
}

#posts-results {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.nap-post-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    background: #fff;
}

.nap-post-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.nap-post-card.selected {
    border-color: #2271b1;
    background: #f6f9fc;
}

.nap-post-image {
    height: 150px;
    overflow: hidden;
    background: #f0f0f1;
}

.nap-post-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nap-no-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #666;
    font-size: 14px;
}

.nap-post-content {
    padding: 15px;
}

.nap-post-title {
    margin: 0 0 8px 0;
    font-size: 16px;
    line-height: 1.4;
}

.nap-post-excerpt {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 14px;
    line-height: 1.4;
}

.nap-post-meta {
    font-size: 12px;
    color: #999;
    margin-bottom: 15px;
}

.nap-post-meta span {
    margin-right: 10px;
}

.nap-selected-posts-section {
    margin-top: 20px;
}

.nap-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

#selected-count {
    background: #2271b1;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 12px;
    margin-left: 5px;
}

.nap-sortable-list {
    min-height: 100px;
}

.nap-empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
    background: #f9f9f9;
    border: 2px dashed #ddd;
    border-radius: 8px;
}

.nap-selected-post {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 15px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.nap-selected-post:hover {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nap-drag-handle {
    cursor: move;
    color: #666;
    padding: 5px;
}

.nap-drag-handle:hover {
    color: #2271b1;
}

.nap-selected-content {
    display: flex;
    gap: 15px;
    flex: 1;
}

.nap-selected-image {
    width: 80px;
    height: 60px;
    border-radius: 4px;
    overflow: hidden;
    background: #f0f0f1;
    flex-shrink: 0;
}

.nap-selected-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.nap-selected-details {
    flex: 1;
}

.nap-selected-details h4 {
    margin: 0 0 5px 0;
    font-size: 16px;
}

.nap-selected-details .meta {
    margin: 0 0 10px 0;
    font-size: 12px;
    color: #666;
}

.nap-description-section label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    font-size: 13px;
}

.nap-description-input {
    width: 100%;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 8px;
    font-size: 13px;
    resize: vertical;
}

.nap-description-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 5px;
    font-size: 11px;
}

.validation-status.error {
    color: #d63638;
}

.validation-status.warning {
    color: #dba617;
}

.validation-status.success {
    color: #00a32a;
}

.nap-selected-actions {
    flex-shrink: 0;
}

.nap-remove-post {
    color: #d63638;
    text-decoration: none;
    padding: 5px;
}

.nap-remove-post:hover {
    color: #b32d2e;
}

.nap-newsletter-config {
    max-width: 800px;
}

.nap-newsletter-preview {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

#newsletter-preview-content {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    background: white;
}

.nap-actions {
    padding: 20px;
    background: #f9f9f9;
    border-top: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.nap-steps-nav {
    display: flex;
    gap: 10px;
}

.nap-final-actions {
    display: flex;
    gap: 10px;
}

.nap-messages {
    margin: 20px 0;
}

#newsletter-subject.warning {
    border-left: 4px solid #dba617;
}

.nap-sort-placeholder {
    height: 60px;
    background: #f0f0f1;
    border: 2px dashed #2271b1;
    border-radius: 8px;
    margin-bottom: 15px;
}

/* Settings Page Styles */
.nap-settings-wrap .nav-tab-wrapper {
    margin-bottom: 0;
}

.tab-content {
    display: none;
    padding: 20px;
    background: white;
    border: 1px solid #ccd0d4;
    border-top: none;
}

.tab-content.active {
    display: block;
}

.nap-test-section {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.nap-test-section h3 {
    margin-top: 0;
}

#ai-test-result,
#sendy-test-result {
    margin-top: 15px;
}

.nap-template-preview {
    margin-top: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

#template-preview-area {
    height: 300px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: white;
    overflow: auto;
}

/* Responsive Design */
@media (max-width: 768px) {
    #posts-results {
        grid-template-columns: 1fr;
    }
    
    .nap-search-controls {
        flex-direction: column;
    }
    
    .nap-search-controls input[type="text"] {
        width: 100%;
    }
    
    .nap-selected-content {
        flex-direction: column;
    }
    
    .nap-selected-image {
        width: 100%;
        height: 120px;
    }
    
    .nap-actions {
        flex-direction: column;
        gap: 15px;
    }
}
