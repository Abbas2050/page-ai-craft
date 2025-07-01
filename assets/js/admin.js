
/**
 * AI PageGen Admin JavaScript
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        initializeAIPageGen();
    });
    
    /**
     * Initialize AI PageGen functionality
     */
    function initializeAIPageGen() {
        bindFormSubmission();
        bindSEOToggle();
        bindProTooltips();
        bindFormValidation();
    }
    
    /**
     * Bind form submission
     */
    function bindFormSubmission() {
        $('#ai-pagegen-form').on('submit', function(e) {
            e.preventDefault();
            
            if (!validateForm()) {
                return;
            }
            
            const formData = new FormData(this);
            formData.append('action', 'ai_pagegen_generate');
            formData.append('nonce', aiPageGen.nonce);
            
            generateContent(formData);
        });
    }
    
    /**
     * Bind SEO optimization toggle
     */
    function bindSEOToggle() {
        $('#seo_optimization').on('change', function() {
            const $seoFields = $('#seo_fields');
            
            if (this.checked && aiPageGen.is_pro) {
                $seoFields.slideDown();
            } else {
                $seoFields.slideUp();
            }
        });
    }
    
    /**
     * Bind pro tooltips
     */
    function bindProTooltips() {
        $('.pro-disabled input, .pro-disabled select, .pro-disabled textarea').on('click', function(e) {
            if (!aiPageGen.is_pro) {
                e.preventDefault();
                showProMessage();
            }
        });
    }
    
    /**
     * Bind form validation
     */
    function bindFormValidation() {
        $('#ai_prompt').on('blur', function() {
            validateField($(this), 'prompt');
        });
    }
    
    /**
     * Validate entire form
     */
    function validateForm() {
        let isValid = true;
        const $prompt = $('#ai_prompt');
        
        // Validate prompt
        if (!validateField($prompt, 'prompt')) {
            isValid = false;
        }
        
        // Check pro features if not pro user
        if (!aiPageGen.is_pro && isUsingProFeatures()) {
            showProMessage();
            isValid = false;
        }
        
        return isValid;
    }
    
    /**
     * Validate individual field
     */
    function validateField($field, type) {
        const value = $field.val().trim();
        const $group = $field.closest('.form-group');
        
        // Remove existing error states
        $group.removeClass('error');
        $group.find('.error-message').remove();
        
        let isValid = true;
        let errorMessage = '';
        
        switch (type) {
            case 'prompt':
                if (value.length < 10) {
                    isValid = false;
                    errorMessage = 'Please enter a more detailed prompt (at least 10 characters).';
                }
                break;
        }
        
        if (!isValid) {
            $group.addClass('error');
            $group.append('<div class="error-message">' + errorMessage + '</div>');
        }
        
        return isValid;
    }
    
    /**
     * Check if user is trying to use pro features
     */
    function isUsingProFeatures() {
        const postType = $('#post_type').val();
        const headerFooter = $('#header_footer').val();
        const seoOptimization = $('#seo_optimization').is(':checked');
        const colorScheme = $('#color_scheme').val();
        const pageSections = $('#page_sections').val();
        
        return (postType !== 'post') || 
               (headerFooter !== 'theme') || 
               seoOptimization || 
               (colorScheme && colorScheme.trim() !== '') || 
               (pageSections && pageSections.trim() !== '');
    }
    
    /**
     * Generate content via AJAX
     */
    function generateContent(formData) {
        const $form = $('#ai-pagegen-form');
        const $button = $('#generate-btn');
        const $preview = $('#content-preview');
        
        // Set loading state
        $form.addClass('generating');
        $button.prop('disabled', true);
        $preview.html('<div class="placeholder">' + aiPageGen.strings.generating + '</div>');
        
        $.ajax({
            url: aiPageGen.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 60000, // 60 seconds
            success: function(response) {
                if (response.success) {
                    displayGeneratedContent(response.data);
                    showMessage('Content generated successfully!', 'success');
                } else {
                    showMessage(response.data || aiPageGen.strings.error, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {xhr, status, error});
                
                let errorMessage = aiPageGen.strings.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again with a shorter prompt.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showMessage(errorMessage, 'error');
            },
            complete: function() {
                // Remove loading state
                $form.removeClass('generating');
                $button.prop('disabled', false);
            }
        });
    }
    
    /**
     * Display generated content in preview
     */
    function displayGeneratedContent(data) {
        const $preview = $('#content-preview');
        
        let html = '<div class="generated-content">';
        
        if (data.content) {
            html += data.content;
        }
        
        if (data.post_id && data.edit_link) {
            html += '<div class="post-actions" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">';
            html += '<p><strong>Post created successfully!</strong></p>';
            html += '<a href="' + data.edit_link + '" class="button button-primary" target="_blank">Edit Post</a>';
            html += '</div>';
        }
        
        html += '</div>';
        
        $preview.html(html);
        
        // Smooth scroll to preview
        $preview[0].scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
    
    /**
     * Show success/error message
     */
    function showMessage(message, type) {
        const $container = $('.ai-pagegen-wrap');
        const $existingMessage = $container.find('.ai-pagegen-message');
        
        // Remove existing messages
        $existingMessage.remove();
        
        // Add new message
        const $message = $('<div class="ai-pagegen-message ' + type + '">' + message + '</div>');
        $container.prepend($message);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $message.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to message
        $message[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }
    
    /**
     * Show pro upgrade message
     */
    function showProMessage() {
        showMessage(aiPageGen.strings.pro_required + ' <a href="admin.php?page=ai-pagegen-license">Upgrade now</a>', 'error');
    }
    
    /**
     * Debug function
     */
    function debug(message, data) {
        if (window.console && console.log) {
            console.log('[AI PageGen] ' + message, data || '');
        }
    }
    
})(jQuery);
