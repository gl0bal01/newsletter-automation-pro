<?php
/**
 * Post Search Service - Phase 1
 * Handles WordPress post searching and selection functionality
 */

class NAP_PostSearchService
{
    /**
     * Search posts based on various criteria
     */
    public function searchPosts($args = [])
    {
        $defaults = [
            'search_term' => '',
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [],
            'exclude' => []
        ];

        $args = wp_parse_args($args, $defaults);

        // Build WP_Query arguments
        $query_args = [
            'post_type' => $args['post_type'],
            'post_status' => $args['post_status'],
            'posts_per_page' => $args['posts_per_page'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'meta_query' => $args['meta_query'],
            'post__not_in' => $args['exclude']
        ];

        // Add search term if provided
        if (!empty($args['search_term'])) {
            $query_args['s'] = $args['search_term'];
        }

        // Execute query
        $query = new WP_Query($query_args);
        $posts = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                $posts[] = [
                    'id' => $post_id,
                    'title' => get_the_title(),
                    'excerpt' => $this->getSmartExcerpt($post_id),
                    'featured_image' => $this->getFeaturedImageData($post_id),
                    'date' => get_the_date('Y-m-d H:i:s'),
                    'permalink' => get_permalink(),
                    'author' => get_the_author(),
                    'categories' => $this->getPostCategories($post_id),
                    'word_count' => $this->getWordCount($post_id)
                ];
            }
            wp_reset_postdata();
        }

        return [
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        ];
    }

    /**
     * Get featured posts (most recent, popular, etc.)
     */
    public function getFeaturedPosts($limit = 5)
    {
        $args = [
            'posts_per_page' => $limit,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => [
                [
                    'key' => '_thumbnail_id',
                    'compare' => 'EXISTS'
                ]
            ]
        ];

        return $this->searchPosts($args);
    }

    /**
     * Get posts by category
     */
    public function getPostsByCategory($category_id, $limit = 10)
    {
        $args = [
            'posts_per_page' => $limit,
            'category__in' => [$category_id]
        ];

        return $this->searchPosts($args);
    }

    /**
     * Get posts by date range
     */
    public function getPostsByDateRange($start_date, $end_date, $limit = 10)
    {
        $args = [
            'posts_per_page' => $limit,
            'date_query' => [
                [
                    'after' => $start_date,
                    'before' => $end_date,
                    'inclusive' => true
                ]
            ]
        ];

        return $this->searchPosts($args);
    }

    /**
     * Get smart excerpt from post content
     */
    private function getSmartExcerpt($post_id, $length = 100)
    {
        $post = get_post($post_id);
        
        if (!$post) {
            return '';
        }

        // Use manual excerpt if available
        if (!empty($post->post_excerpt)) {
            return wp_strip_all_tags($post->post_excerpt);
        }

        // Generate excerpt from content
        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        
        if (strlen($content) <= $length) {
            return $content;
        }

        // Find last complete sentence within length
        $excerpt = substr($content, 0, $length);
        $last_sentence = strrpos($excerpt, '.');
        
        if ($last_sentence !== false && $last_sentence > $length * 0.7) {
            return substr($excerpt, 0, $last_sentence + 1);
        }

        // Fallback to word boundary
        $excerpt = substr($content, 0, $length);
        $last_space = strrpos($excerpt, ' ');
        
        if ($last_space !== false) {
            return substr($excerpt, 0, $last_space) . '...';
        }

        return $excerpt . '...';
    }

    /**
     * Get featured image data
     */
    private function getFeaturedImageData($post_id)
    {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        
        if (!$thumbnail_id) {
            return [
                'id' => 0,
                'url' => '',
                'alt' => '',
                'caption' => ''
            ];
        }

        $image_data = wp_get_attachment_image_src($thumbnail_id, 'medium');
        $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
        $caption = wp_get_attachment_caption($thumbnail_id);

        return [
            'id' => $thumbnail_id,
            'url' => $image_data[0] ?? '',
            'width' => $image_data[1] ?? 0,
            'height' => $image_data[2] ?? 0,
            'alt' => $alt_text ?: get_the_title($post_id),
            'caption' => $caption ?: ''
        ];
    }

    /**
     * Get post categories
     */
    private function getPostCategories($post_id)
    {
        $categories = get_the_category($post_id);
        $cat_data = [];

        foreach ($categories as $category) {
            $cat_data[] = [
                'id' => $category->term_id,
                'name' => $category->name,
                'slug' => $category->slug
            ];
        }

        return $cat_data;
    }

    /**
     * Get word count for post
     */
    private function getWordCount($post_id)
    {
        $post = get_post($post_id);
        
        if (!$post) {
            return 0;
        }

        $content = strip_shortcodes($post->post_content);
        $content = wp_strip_all_tags($content);
        
        return str_word_count($content);
    }

    /**
     * Validate post selection
     */
    public function validatePostSelection($post_ids)
    {
        $valid_posts = [];
        $errors = [];

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                $errors[] = sprintf(__('Post with ID %d not found', 'newsletter-automation'), $post_id);
                continue;
            }

            if ($post->post_status !== 'publish') {
                $errors[] = sprintf(__('Post "%s" is not published', 'newsletter-automation'), $post->post_title);
                continue;
            }

            // Check if post has featured image
            if (!has_post_thumbnail($post_id)) {
                $errors[] = sprintf(__('Post "%s" has no featured image', 'newsletter-automation'), $post->post_title);
                continue;
            }

            $valid_posts[] = $post_id;
        }

        return [
            'valid_posts' => $valid_posts,
            'errors' => $errors,
            'is_valid' => empty($errors)
        ];
    }

    /**
     * Get post data for newsletter compilation
     */
    public function getPostsForNewsletter($post_ids)
    {
        $posts_data = [];

        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            
            if (!$post) {
                continue;
            }

            $posts_data[] = [
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'content' => $this->getPostContentForAI($post_id),
                'featured_image' => $this->getFeaturedImageData($post_id),
                'permalink' => get_permalink($post_id),
                'date' => get_the_date('F j, Y', $post_id),
                'author' => get_the_author_meta('display_name', $post->post_author),
                'categories' => $this->getPostCategories($post_id)
            ];
        }

        return $posts_data;
    }

    /**
     * Get post content optimized for AI processing
     */
    private function getPostContentForAI($post_id, $max_words = 300)
    {
        $post = get_post($post_id);
        
        if (!$post) {
            return '';
        }

        // Get clean content
        $content = $post->post_content;
        $content = strip_shortcodes($content);
        $content = wp_strip_all_tags($content);
        
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // Limit word count for AI processing
        $words = explode(' ', $content);
        if (count($words) > $max_words) {
            $content = implode(' ', array_slice($words, 0, $max_words)) . '...';
        }

        return $content;
    }
}
