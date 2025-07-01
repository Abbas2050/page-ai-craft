
<?php
/**
 * OpenAI API integration for AI PageGen
 *
 * @package AI_PageGen
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI PageGen OpenAI Class
 */
class AI_PageGen_OpenAI {
    
    /**
     * OpenAI API endpoint
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';
    
    /**
     * API Key
     */
    private $api_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $options = get_option('ai_pagegen_settings');
        $this->api_key = isset($options['openai_api_key']) ? $options['openai_api_key'] : '';
    }
    
    /**
     * Generate content using OpenAI API
     *
     * @param string $prompt User prompt
     * @param array $options Generation options
     * @return array|false Generated content or false on failure
     */
    public function generate_content($prompt, $options = array()) {
        if (empty($this->api_key)) {
            throw new Exception(__('OpenAI API key is not configured', 'ai-pagegen'));
        }
        
        // Build the system prompt based on options
        $system_prompt = $this->build_system_prompt($options);
        
        // Prepare the request
        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt
            ),
            array(
                'role' => 'user',
                'content' => $prompt
            )
        );
        
        $body = array(
            'model' => 'gpt-4',
            'messages' => $messages,
            'max_tokens' => 3000,
            'temperature' => 0.7
        );
        
        $headers = array(
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json'
        );
        
        $args = array(
            'headers' => $headers,
            'body' => wp_json_encode($body),
            'timeout' => 60
        );
        
        // Make the API request
        $response = wp_remote_post($this->api_endpoint, $args);
        
        if (is_wp_error($response)) {
            throw new Exception(__('Failed to connect to OpenAI API', 'ai-pagegen'));
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : __('Unknown API error', 'ai-pagegen');
            throw new Exception($error_message);
        }
        
        $data = json_decode($response_body, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception(__('Invalid API response', 'ai-pagegen'));
        }
        
        $generated_content = $data['choices'][0]['message']['content'];
        
        // Parse the generated content
        return $this->parse_generated_content($generated_content, $options);
    }
    
    /**
     * Build system prompt based on options
     *
     * @param array $options Generation options
     * @return string System prompt
     */
    private function build_system_prompt($options) {
        $prompt = "You are a professional WordPress content generator. Generate high-quality, engaging content based on the user's request.";
        
        $prompt .= "\n\nRequirements:";
        $prompt .= "\n- Return content in HTML format suitable for WordPress";
        $prompt .= "\n- Use semantic HTML5 elements";
        $prompt .= "\n- Include proper heading hierarchy (H1, H2, H3, etc.)";
        $prompt .= "\n- Make content engaging and well-structured";
        
        // Post type specific instructions
        if (isset($options['post_type'])) {
            if ($options['post_type'] === 'page') {
                $prompt .= "\n- Generate content suitable for a WordPress page (more formal, comprehensive)";
            } else {
                $prompt .= "\n- Generate content suitable for a blog post (engaging, conversational)";
            }
        }
        
        // SEO optimization
        if (!empty($options['seo_optimization']) && !empty($options['seo_keywords'])) {
            $prompt .= "\n- Optimize for SEO with keywords: " . $options['seo_keywords'];
            $prompt .= "\n- Include keywords naturally in headings and content";
            $prompt .= "\n- Suggest SEO title and meta description";
        }
        
        // Color scheme
        if (!empty($options['color_scheme'])) {
            $prompt .= "\n- Apply color scheme: " . $options['color_scheme'];
            $prompt .= "\n- Use inline styles or suggest CSS for color implementation";
        }
        
        // Page sections
        if (!empty($options['page_sections'])) {
            $prompt .= "\n- Structure content with these sections: " . $options['page_sections'];
            $prompt .= "\n- Each section should be distinct and well-organized";
        }
        
        // Header/Footer
        if (!empty($options['header_footer']) && $options['header_footer'] === 'custom') {
            $prompt .= "\n- Include suggestions for custom header and footer content";
        }
        
        $prompt .= "\n\nFormat your response as JSON with the following structure:";
        $prompt .= "\n{";
        $prompt .= "\n  \"title\": \"Generated title\",";
        $prompt .= "\n  \"content\": \"HTML content here\",";
        $prompt .= "\n  \"seo_title\": \"SEO optimized title (if SEO enabled)\",";
        $prompt .= "\n  \"meta_description\": \"Meta description (if SEO enabled)\",";
        $prompt .= "\n  \"excerpt\": \"Brief excerpt\"";
        $prompt .= "\n}";
        
        return $prompt;
    }
    
    /**
     * Parse generated content
     *
     * @param string $content Generated content from OpenAI
     * @param array $options Generation options
     * @return array Parsed content array
     */
    private function parse_generated_content($content, $options) {
        // Try to parse as JSON first
        $json_data = json_decode($content, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($json_data)) {
            // Valid JSON response
            return array(
                'title' => isset($json_data['title']) ? sanitize_text_field($json_data['title']) : __('Generated Content', 'ai-pagegen'),
                'content' => isset($json_data['content']) ? wp_kses_post($json_data['content']) : $content,
                'seo_title' => isset($json_data['seo_title']) ? sanitize_text_field($json_data['seo_title']) : '',
                'meta_description' => isset($json_data['meta_description']) ? sanitize_text_field($json_data['meta_description']) : '',
                'excerpt' => isset($json_data['excerpt']) ? sanitize_text_field($json_data['excerpt']) : ''
            );
        } else {
            // Fallback for non-JSON response
            return array(
                'title' => __('Generated Content', 'ai-pagegen'),
                'content' => wp_kses_post($content),
                'seo_title' => '',
                'meta_description' => '',
                'excerpt' => wp_trim_words(strip_tags($content), 20)
            );
        }
    }
}
