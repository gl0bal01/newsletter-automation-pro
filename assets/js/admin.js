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
