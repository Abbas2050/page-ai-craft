<?php
/**
 * Post/Page creator for AI PageGen
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen Post Creator Class
 */
class AI_PageGen_Post_Creator {
    
    /**
     * Create a new WordPress post/page
     *
     * @param array $content Generated content array
     * @param array $options Creation options
     * @return int|false Post ID on success, false on failure
     */
    public function create_post($content, $options = array()) {
        AI_PageGen_Logger::info('Starting post creation', array(
            'title' => $content['title'],
            'post_type' => isset($options['post_type']) ? $options['post_type'] : 'post'
        ));
        
        // Process content for Gutenberg or Elementor
        $processed_content = $this->process_content_for_editor($content['content'], $options);
        
        // Prepare post data
        $post_data = array(
            'post_title' => $content['title'],
            'post_content' => $processed_content,
            'post_status' => 'draft', // Create as draft for review
            'post_type' => isset($options['post_type']) ? $options['post_type'] : 'post',
            'post_author' => get_current_user_id(),
            'meta_input' => array(
                'ai_pagegen_generated' => true,
                'ai_pagegen_version' => AI_PAGEGEN_VERSION,
                'ai_pagegen_generated_date' => current_time('mysql')
            )
        );
        
        // Add excerpt if available
        if (!empty($content['excerpt'])) {
            $post_data['post_excerpt'] = $content['excerpt'];
        }
        
        // Insert the post
        $post_id = wp_insert_post($post_data);
        
        if (is_wp_error($post_id)) {
            AI_PageGen_Logger::error('Post creation failed', array(
                'error' => $post_id->get_error_message()
            ));
            return false;
        }
        
        // Add Elementor compatibility if requested
        if (isset($options['elementor_compatible']) && $options['elementor_compatible']) {
            $this->make_elementor_compatible($post_id, $content);
        }
        
        // Add SEO meta data if available
        if (!empty($content['seo_title']) || !empty($content['meta_description'])) {
            $this->add_seo_meta($post_id, $content, $options);
        }
        
        // Add custom styling if color scheme was provided
        if (!empty($options['color_scheme'])) {
            $this->add_custom_styling($post_id, $options['color_scheme']);
        }
        
        AI_PageGen_Logger::info('Post created successfully', array('post_id' => $post_id));
        
        return $post_id;
    }
    
    /**
     * Process content for specific editors (Gutenberg/Elementor)
     *
     * @param string $content Raw HTML content
     * @param array $options Creation options
     * @return string Processed content
     */
    private function process_content_for_editor($content, $options) {
        if (isset($options['elementor_compatible']) && $options['elementor_compatible']) {
            // For Elementor, we'll store the content as raw HTML
            // Elementor will handle the conversion when the page is edited
            return $content;
        }
        
        // Convert HTML to Gutenberg blocks
        return $this->convert_html_to_gutenberg_blocks($content);
    }
    
    /**
     * Convert HTML content to Gutenberg blocks
     *
     * @param string $html HTML content
     * @return string Gutenberg blocks
     */
    private function convert_html_to_gutenberg_blocks($html) {
        // Simple HTML to Gutenberg block conversion
        $blocks = '';
        
        // Split content by common HTML tags and convert to blocks
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        $xpath = new DOMXPath($dom);
        
        // Convert headings
        foreach ($xpath->query('//h1 | //h2 | //h3 | //h4 | //h5 | //h6') as $heading) {
            $level = substr($heading->tagName, 1);
            $text = $heading->textContent;
            $blocks .= "<!-- wp:heading {\"level\":{$level}} -->\n";
            $blocks .= "<h{$level}>{$text}</h{$level}>\n";
            $blocks .= "<!-- /wp:heading -->\n\n";
        }
        
        // Convert paragraphs
        foreach ($xpath->query('//p') as $paragraph) {
            $text = $paragraph->textContent;
            if (trim($text)) {
                $blocks .= "<!-- wp:paragraph -->\n";
                $blocks .= "<p>{$text}</p>\n";
                $blocks .= "<!-- /wp:paragraph -->\n\n";
            }
        }
        
        // Convert lists
        foreach ($xpath->query('//ul') as $list) {
            $blocks .= "<!-- wp:list -->\n";
            $blocks .= $dom->saveHTML($list) . "\n";
            $blocks .= "<!-- /wp:list -->\n\n";
        }
        
        foreach ($xpath->query('//ol') as $list) {
            $blocks .= "<!-- wp:list {\"ordered\":true} -->\n";
            $blocks .= $dom->saveHTML($list) . "\n";
            $blocks .= "<!-- /wp:list -->\n\n";
        }
        
        // If no specific blocks were created, wrap in a single HTML block
        if (empty(trim($blocks))) {
            $blocks = "<!-- wp:html -->\n";
            $blocks .= $html . "\n";
            $blocks .= "<!-- /wp:html -->\n";
        }
        
        return $blocks;
    }
    
    /**
     * Make post compatible with Elementor
     *
     * @param int $post_id Post ID
     * @param array $content Content data
     */
    private function make_elementor_compatible($post_id, $content) {
        AI_PageGen_Logger::info('Making post Elementor compatible', array('post_id' => $post_id));
        
        // Enable Elementor for this post
        update_post_meta($post_id, '_elementor_edit_mode', 'builder');
        update_post_meta($post_id, '_elementor_template_type', 'wp-post');
        update_post_meta($post_id, '_elementor_version', '3.0.0');
        
        // Create basic Elementor structure
        $elementor_data = $this->create_elementor_structure($content);
        update_post_meta($post_id, '_elementor_data', $elementor_data);
        
        // Set page template to Elementor Canvas (if available)
        if (get_post_type($post_id) === 'page') {
            update_post_meta($post_id, '_wp_page_template', 'elementor_canvas');
        }
    }
    
    /**
     * Create basic Elementor structure from content
     *
     * @param array $content Content data
     * @return string JSON encoded Elementor data
     */
    private function create_elementor_structure($content) {
        $elementor_data = array(
            array(
                'id' => wp_generate_uuid4(),
                'elType' => 'section',
                'elements' => array(
                    array(
                        'id' => wp_generate_uuid4(),
                        'elType' => 'column',
                        'elements' => array(
                            array(
                                'id' => wp_generate_uuid4(),
                                'elType' => 'widget',
                                'widgetType' => 'text-editor',
                                'settings' => array(
                                    'editor' => $content['content']
                                )
                            )
                        ),
                        'settings' => array(
                            '_column_size' => 100
                        )
                    )
                ),
                'settings' => array()
            )
        );
        
        return json_encode($elementor_data);
    }
    
    /**
     * Add SEO meta data to post
     *
     * @param int $post_id Post ID
     * @param array $content Generated content
     * @param array $options Creation options
     */
    private function add_seo_meta($post_id, $content, $options) {
        // Custom SEO meta fields
        if (!empty($content['seo_title'])) {
            update_post_meta($post_id, '_ai_pagegen_seo_title', $content['seo_title']);
        }
        
        if (!empty($content['meta_description'])) {
            update_post_meta($post_id, '_ai_pagegen_meta_description', $content['meta_description']);
        }
        
        if (!empty($options['seo_keywords'])) {
            update_post_meta($post_id, '_ai_pagegen_seo_keywords', $options['seo_keywords']);
        }
        
        // Yoast SEO compatibility
        if (defined('WPSEO_VERSION')) {
            if (!empty($content['seo_title'])) {
                update_post_meta($post_id, '_yoast_wpseo_title', $content['seo_title']);
            }
            
            if (!empty($content['meta_description'])) {
                update_post_meta($post_id, '_yoast_wpseo_metadesc', $content['meta_description']);
            }
            
            if (!empty($options['seo_keywords'])) {
                update_post_meta($post_id, '_yoast_wpseo_focuskw', $options['seo_keywords']);
            }
        }
        
        // RankMath SEO compatibility
        if (defined('RANK_MATH_VERSION')) {
            if (!empty($content['seo_title'])) {
                update_post_meta($post_id, 'rank_math_title', $content['seo_title']);
            }
            
            if (!empty($content['meta_description'])) {
                update_post_meta($post_id, 'rank_math_description', $content['meta_description']);
            }
            
            if (!empty($options['seo_keywords'])) {
                update_post_meta($post_id, 'rank_math_focus_keyword', $options['seo_keywords']);
            }
        }
    }
    
    /**
     * Add custom styling based on color scheme
     *
     * @param int $post_id Post ID
     * @param string $color_scheme Color scheme
     */
    private function add_custom_styling($post_id, $color_scheme) {
        $colors = $this->parse_color_scheme($color_scheme);
        
        if (!empty($colors)) {
            $custom_css = $this->generate_custom_css($colors);
            update_post_meta($post_id, '_ai_pagegen_custom_css', $custom_css);
            
            // Hook to add CSS to frontend
            add_action('wp_head', function() use ($post_id, $custom_css) {
                if (is_singular() && get_the_ID() === $post_id) {
                    echo '<style type="text/css">' . $custom_css . '</style>';
                }
            });
        }
    }
    
    /**
     * Parse color scheme input
     *
     * @param string $color_scheme Color scheme input
     * @return array Array of colors
     */
    private function parse_color_scheme($color_scheme) {
        $colors = array();
        
        // Check if it's hex colors separated by commas
        if (strpos($color_scheme, '#') !== false) {
            $hex_colors = explode(',', $color_scheme);
            foreach ($hex_colors as $color) {
                $color = trim($color);
                if (preg_match('/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/', $color)) {
                    $colors[] = $color;
                }
            }
        } else {
            // Handle color names or themes
            $color_scheme = strtolower(trim($color_scheme));
            
            if (strpos($color_scheme, 'blue') !== false) {
                $colors = array('#2271b1', '#ffffff', '#000000');
            } elseif (strpos($color_scheme, 'dark') !== false) {
                $colors = array('#1a1a1a', '#ffffff', '#333333');
            } elseif (strpos($color_scheme, 'green') !== false) {
                $colors = array('#00a32a', '#ffffff', '#000000');
            } else {
                // Default blue scheme
                $colors = array('#2271b1', '#ffffff', '#000000');
            }
        }
        
        return $colors;
    }
    
    /**
     * Generate custom CSS from colors
     *
     * @param array $colors Array of colors
     * @return string CSS rules
     */
    private function generate_custom_css($colors) {
        $primary = isset($colors[0]) ? $colors[0] : '#2271b1';
        $secondary = isset($colors[1]) ? $colors[1] : '#ffffff';
        $accent = isset($colors[2]) ? $colors[2] : '#000000';
        
        $css = "
        .ai-pagegen-content {
            --primary-color: {$primary};
            --secondary-color: {$secondary};
            --accent-color: {$accent};
        }
        
        .ai-pagegen-content h1,
        .ai-pagegen-content h2,
        .ai-pagegen-content h3 {
            color: {$primary};
        }
        
        .ai-pagegen-content .button,
        .ai-pagegen-content .btn {
            background-color: {$primary};
            color: {$secondary};
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .ai-pagegen-content .button:hover,
        .ai-pagegen-content .btn:hover {
            background-color: {$accent};
        }
        
        .ai-pagegen-content .highlight {
            background-color: {$secondary};
            color: {$accent};
            padding: 2px 4px;
            border-radius: 2px;
        }
        ";
        
        return $css;
    }
    
    /**
     * Get posts created by AI PageGen
     *
     * @param array $args Query arguments
     * @return WP_Query Query object
     */
    public function get_ai_generated_posts($args = array()) {
        $default_args = array(
            'meta_query' => array(
                array(
                    'key' => 'ai_pagegen_generated',
                    'value' => true,
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return new WP_Query($args);
    }
}
