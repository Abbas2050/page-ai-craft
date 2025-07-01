
/**
 * AI PageGen Admin JavaScript - Enhanced with Animations & UX
 */
(function($) {
    'use strict';
    
    // Configuration object
    const AIPageGen = {
        init: function() {
            this.bindEvents();
            this.initializeUI();
            this.setupAnimations();
        },
        
        bindEvents: function() {
            this.bindFormSubmission();
            this.bindSEOToggle();
            this.bindProFeatures();
            this.bindFormValidation();
            this.bindSettingsValidation();
            this.bindPreviewUpdates();
        },
        
        initializeUI: function() {
            this.setupTooltips();
            this.initializeProgressBars();
            this.setupKeyboardShortcuts();
        },
        
        setupAnimations: function() {
            // Animate elements on page load
            $('.ai-pagegen-wrap').addClass('ai-fade-in');
            
            // Stagger form group animations
            $('.form-group').each(function(index) {
                $(this).css('animation-delay', (index * 0.1) + 's');
                $(this).addClass('ai-fade-in');
            });
        }
    };
    
    $(document).ready(function() {
        AIPageGen.init();
    });
    
    /**
     * Enhanced form submission with better UX
     */
    AIPageGen.bindFormSubmission = function() {
        $('#ai-pagegen-form').on('submit', function(e) {
            e.preventDefault();
            
            // Validate form before submission
            if (!AIPageGen.validateForm()) {
                AIPageGen.showValidationErrors();
                return;
            }
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'ai_pagegen_generate');
            formData.append('nonce', aiPageGen.nonce);
            
            // Track analytics
            AIPageGen.trackEvent('content_generation_started');
            
            AIPageGen.generateContent(formData);
        });
    };
    
    /**
     * Enhanced SEO toggle with smooth animations
     */
    AIPageGen.bindSEOToggle = function() {
        $('#seo_optimization').on('change', function() {
            const $seoFields = $('#seo_fields');
            const isChecked = this.checked;
            
            if (isChecked && aiPageGen.is_pro) {
                $seoFields.slideDown({
                    duration: 400,
                    easing: 'easeOutCubic',
                    complete: function() {
                        $(this).find('input, textarea').first().focus();
                    }
                });
                AIPageGen.trackEvent('seo_optimization_enabled');
            } else if (isChecked && !aiPageGen.is_pro) {
                // Show pro upgrade message with animation
                AIPageGen.showProUpgradeModal();
                this.checked = false;
            } else {
                $seoFields.slideUp({
                    duration: 300,
                    easing: 'easeInCubic'
                });
            }
        });
    };
    
    /**
     * Enhanced pro features handling
     */
    AIPageGen.bindProFeatures = function() {
        $('.pro-disabled input, .pro-disabled select, .pro-disabled textarea').on('click focus', function(e) {
            if (!aiPageGen.is_pro) {
                e.preventDefault();
                e.stopPropagation();
                
                const $tooltip = $(this).siblings('.pro-tooltip');
                $tooltip.addClass('pulse-animation');
                
                setTimeout(() => {
                    $tooltip.removeClass('pulse-animation');
                }, 1000);
                
                AIPageGen.showProUpgradeModal();
                AIPageGen.trackEvent('pro_feature_attempted', {
                    feature: $(this).attr('name') || $(this).attr('id')
                });
            }
        });
        
        // Add hover effects to pro tooltips
        $('.pro-tooltip').hover(
            function() {
                $(this).addClass('tooltip-hover');
            },
            function() {
                $(this).removeClass('tooltip-hover');
            }
        );
    };
    
    /**
     * Enhanced form validation with real-time feedback
     */
    AIPageGen.bindFormValidation = function() {
        // Real-time validation for prompt
        $('#ai_prompt').on('input', function() {
            AIPageGen.validateField($(this), 'prompt', true);
            AIPageGen.updateCharacterCount($(this));
        });
        
        // Validation on blur
        $('#ai_prompt').on('blur', function() {
            AIPageGen.validateField($(this), 'prompt');
        });
        
        // API key validation
        $('#openai_api_key').on('blur', function() {
            AIPageGen.validateField($(this), 'api_key');
        });
        
        // Color scheme validation
        $('#color_scheme').on('input', function() {
            AIPageGen.validateField($(this), 'color_scheme', true);
            AIPageGen.previewColorScheme($(this).val());
        });
    };
    
    /**
     * Settings page validation
     */
    AIPageGen.bindSettingsValidation = function() {
        $('#ai-pagegen-settings-form').on('submit', function(e) {
            const $apiKey = $('#openai_api_key');
            
            if (!AIPageGen.validateField($apiKey, 'api_key')) {
                e.preventDefault();
                $apiKey.focus();
                AIPageGen.showMessage('Please enter a valid OpenAI API key.', 'error');
            }
        });
    };
    
    /**
     * Real-time preview updates
     */
    AIPageGen.bindPreviewUpdates = function() {
        // Update preview when prompt changes
        $('#ai_prompt').on('input', AIPageGen.debounce(function() {
            AIPageGen.updatePreviewPrompt($(this).val());
        }, 500));
        
        // Update preview when post type changes
        $('#post_type').on('change', function() {
            AIPageGen.updatePreviewType($(this).val());
        });
    };
    
    /**
     * Enhanced field validation with visual feedback
     */
    AIPageGen.validateField = function($field, type, isRealTime = false) {
        const value = $field.val().trim();
        const $group = $field.closest('.form-group');
        
        // Remove existing error states
        $group.removeClass('error success');
        $group.find('.error-message, .success-message').remove();
        
        let isValid = true;
        let message = '';
        let messageType = 'error';
        
        switch (type) {
            case 'prompt':
                if (value.length < 10) {
                    isValid = false;
                    message = 'Please enter a more detailed prompt (at least 10 characters).';
                } else if (value.length > 2000) {
                    isValid = false;
                    message = 'Prompt is too long. Please keep it under 2000 characters.';
                } else if (!isRealTime) {
                    messageType = 'success';
                    message = 'Prompt looks good!';
                }
                break;
                
            case 'api_key':
                if (!value) {
                    isValid = false;
                    message = 'OpenAI API key is required.';
                } else if (!value.startsWith('sk-')) {
                    isValid = false;
                    message = 'OpenAI API key should start with "sk-".';
                } else if (value.length < 30) {
                    isValid = false;
                    message = 'API key appears to be too short.';
                } else if (!isRealTime) {
                    messageType = 'success';
                    message = 'API key format looks correct.';
                }
                break;
                
            case 'color_scheme':
                if (value && !AIPageGen.isValidColorScheme(value)) {
                    isValid = false;
                    message = 'Please enter valid hex codes (e.g., #FF0000,#00FF00) or color names.';
                } else if (value && !isRealTime) {
                    messageType = 'success';
                    message = 'Color scheme is valid.';
                }
                break;
        }
        
        // Apply validation state
        if (!isValid) {
            $group.addClass('error');
            if (message) {
                $group.append('<div class="error-message">' + message + '</div>');
            }
        } else if (message && messageType === 'success' && !isRealTime) {
            $group.addClass('success');
            $group.append('<div class="success-message">' + message + '</div>');
        }
        
        return isValid;
    };
    
    /**
     * Enhanced content generation with progress tracking
     */
    AIPageGen.generateContent = function(formData) {
        const $form = $('#ai-pagegen-form');
        const $button = $('#generate-btn');
        const $preview = $('#content-preview');
        const $progress = AIPageGen.createProgressBar();
        
        // Set loading state with animations
        $form.addClass('generating');
        $button.prop('disabled', true);
        $preview.html($progress);
        
        // Start progress animation
        AIPageGen.animateProgress();
        
        // Track generation start time
        const startTime = Date.now();
        
        $.ajax({
            url: aiPageGen.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 90000, // 90 seconds
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                
                // Add upload progress if available
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = (evt.loaded / evt.total) * 100;
                        AIPageGen.updateProgress(percentComplete);
                    }
                }, false);
                
                return xhr;
            },
            success: function(response) {
                const duration = Date.now() - startTime;
                
                if (response.success) {
                    AIPageGen.displayGeneratedContent(response.data);
                    AIPageGen.showMessage('Content generated successfully! (' + (duration/1000).toFixed(1) + 's)', 'success');
                    AIPageGen.trackEvent('content_generation_success', {
                        duration: duration,
                        content_length: response.data.content ? response.data.content.length : 0
                    });
                } else {
                    AIPageGen.handleGenerationError(response.data || aiPageGen.strings.error);
                    AIPageGen.trackEvent('content_generation_error', {
                        error: response.data,
                        duration: duration
                    });
                }
            },
            error: function(xhr, status, error) {
                const duration = Date.now() - startTime;
                console.error('AJAX Error:', {xhr, status, error});
                
                let errorMessage = aiPageGen.strings.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. The AI is taking longer than expected. Please try with a shorter prompt.';
                } else if (xhr.status === 429) {
                    errorMessage = 'API rate limit exceeded. Please wait a moment and try again.';
                } else if (xhr.status === 401) {
                    errorMessage = 'Invalid API key. Please check your OpenAI API key in settings.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                AIPageGen.handleGenerationError(errorMessage);
                AIPageGen.trackEvent('content_generation_ajax_error', {
                    error: errorMessage,
                    status: status,
                    xhr_status: xhr.status,
                    duration: duration
                });
            },
            complete: function() {
                // Remove loading state with animation
                setTimeout(() => {
                    $form.removeClass('generating');
                    $button.prop('disabled', false);
                }, 500);
            }
        });
    };
    
    /**
     * Create animated progress bar
     */
    AIPageGen.createProgressBar = function() {
        return `
            <div class="ai-progress-container">
                <div class="ai-progress-header">
                    <span class="ai-robot-icon">🤖</span>
                    <h3>Generating Content...</h3>
                    <p class="ai-progress-message">The AI is crafting your content</p>
                </div>
                <div class="ai-progress-bar">
                    <div class="ai-progress-fill"></div>
                </div>
                <div class="ai-progress-steps">
                    <div class="ai-step active">Analyzing prompt</div>
                    <div class="ai-step">Generating content</div>
                    <div class="ai-step">Formatting output</div>
                </div>
            </div>
        `;
    };
    
    /**
     * Animate progress bar
     */
    AIPageGen.animateProgress = function() {
        let progress = 0;
        let step = 0;
        const steps = ['Analyzing prompt...', 'Generating content...', 'Formatting output...', 'Almost done...'];
        
        const interval = setInterval(() => {
            progress += Math.random() * 15;
            
            if (progress > 90) {
                progress = 90;
            }
            
            $('.ai-progress-fill').css('width', progress + '%');
            
            if (progress > (step + 1) * 25 && step < steps.length - 1) {
                step++;
                $('.ai-progress-message').text(steps[step]);
                $('.ai-step').eq(step).addClass('active');
            }
            
            if (!$('.generating').length) {
                clearInterval(interval);
            }
        }, 800);
    };
    
    /**
     * Enhanced content display with animations
     */
    AIPageGen.displayGeneratedContent = function(data) {
        const $preview = $('#content-preview');
        
        let html = '<div class="generated-content-wrapper">';
        
        if (data.content) {
            html += '<div class="generated-content" style="animation: fadeIn 0.5s ease-out;">';
            html += data.content;
            html += '</div>';
        }
        
        if (data.seo_data && aiPageGen.is_pro) {
            html += AIPageGen.renderSEOPreview(data.seo_data);
        }
        
        if (data.post_id && data.edit_link) {
            html += AIPageGen.renderPostActions(data);
        }
        
        html += '</div>';
        
        // Animate content appearance
        $preview.fadeOut(200, function() {
            $(this).html(html).fadeIn(300);
            
            // Add copy to clipboard functionality
            AIPageGen.addCopyButtons();
            
            // Smooth scroll to preview
            $preview[0].scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
    };
    
    /**
     * Render SEO preview
     */
    AIPageGen.renderSEOPreview = function(seoData) {
        return `
            <div class="seo-preview" style="margin-top: 20px; padding: 16px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #28a745;">
                <h4 style="margin: 0 0 12px 0; color: #28a745;">SEO Preview</h4>
                <div class="seo-title" style="color: #1a0dab; font-size: 18px; margin-bottom: 4px;">${seoData.title}</div>
                <div class="seo-url" style="color: #006621; font-size: 14px; margin-bottom: 8px;">${window.location.origin}/sample-url</div>
                <div class="seo-description" style="color: #545454; font-size: 13px; line-height: 1.4;">${seoData.description}</div>
            </div>
        `;
    };
    
    /**
     * Render post actions
     */
    AIPageGen.renderPostActions = function(data) {
        return `
            <div class="post-actions" style="margin-top: 24px; padding-top: 20px; border-top: 2px solid #e0e0e0; text-align: center;">
                <div class="success-animation" style="margin-bottom: 16px;">✅</div>
                <p style="font-weight: 600; color: #28a745; margin-bottom: 16px;">Post created successfully!</p>
                <div class="action-buttons">
                    <a href="${data.edit_link}" class="button button-primary ai-hover-lift" target="_blank" style="margin-right: 12px;">
                        <span class="dashicons dashicons-edit"></span> Edit Post
                    </a>
                    <a href="${data.view_link || '#'}" class="button button-secondary ai-hover-lift" target="_blank">
                        <span class="dashicons dashicons-visibility"></span> View Post
                    </a>
                </div>
            </div>
        `;
    };
    
    /**
     * Add copy to clipboard functionality
     */
    AIPageGen.addCopyButtons = function() {
        $('.generated-content').each(function() {
            const $content = $(this);
            const $copyBtn = $('<button class="copy-content-btn" title="Copy to clipboard">📋</button>');
            
            $copyBtn.css({
                position: 'absolute',
                top: '10px',
                right: '10px',
                background: '#fff',
                border: '1px solid #ddd',
                borderRadius: '4px',
                padding: '6px 8px',
                cursor: 'pointer',
                fontSize: '14px'
            });
            
            $content.css('position', 'relative').append($copyBtn);
            
            $copyBtn.on('click', function() {
                const text = $content.text();
                navigator.clipboard.writeText(text).then(() => {
                    $(this).text('✅').css('color', '#28a745');
                    setTimeout(() => {
                        $(this).text('📋').css('color', '');
                    }, 2000);
                });
            });
        });
    };
    
    /**
     * Enhanced error handling
     */
    AIPageGen.handleGenerationError = function(errorMessage) {
        const $preview = $('#content-preview');
        
        const errorHtml = `
            <div class="error-state" style="text-align: center; padding: 40px; color: #721c24;">
                <div class="error-icon" style="font-size: 48px; margin-bottom: 16px;">⚠️</div>
                <h3 style="color: #721c24; margin-bottom: 12px;">Generation Failed</h3>
                <p style="margin-bottom: 20px;">${errorMessage}</p>
                <button class="button button-secondary" onclick="location.reload()">Try Again</button>
            </div>
        `;
        
        $preview.html(errorHtml);
        AIPageGen.showMessage(errorMessage, 'error');
    };
    
    /**
     * Enhanced message display with auto-dismiss
     */
    AIPageGen.showMessage = function(message, type, autoDismiss = true) {
        const $container = $('.ai-pagegen-wrap');
        const $existingMessage = $container.find('.ai-pagegen-message');
        
        // Remove existing messages with animation
        $existingMessage.fadeOut(200, function() {
            $(this).remove();
        });
        
        // Create new message with animation
        const $message = $(`
            <div class="ai-pagegen-message ${type}" style="display: none;">
                <span class="message-text">${message}</span>
                <button class="message-close" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer; opacity: 0.7;">&times;</button>
            </div>
        `);
        
        $container.prepend($message);
        $message.slideDown(300);
        
        // Bind close button
        $message.find('.message-close').on('click', function() {
            $message.slideUp(200, function() {
                $(this).remove();
            });
        });
        
        // Auto-hide for success messages
        if (autoDismiss && type === 'success') {
            setTimeout(() => {
                $message.slideUp(200, function() {
                    $(this).remove();
                });
            }, 5000);
        }
        
        // Scroll to message
        $message[0].scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    };
    
    /**
     * Show pro upgrade modal
     */
    AIPageGen.showProUpgradeModal = function() {
        const modalHtml = `
            <div class="ai-pro-modal" style="
                position: fixed; top: 0; left: 0; right: 0; bottom: 0;
                background: rgba(0,0,0,0.8); z-index: 999999;
                display: flex; align-items: center; justify-content: center;
                animation: fadeIn 0.3s ease-out;
            ">
                <div class="ai-pro-modal-content" style="
                    background: white; padding: 40px; border-radius: 12px;
                    max-width: 500px; margin: 20px; text-align: center;
                    animation: scaleIn 0.3s ease-out;
                ">
                    <div style="font-size: 48px; margin-bottom: 20px;">🚀</div>
                    <h2 style="margin-bottom: 16px; color: #2271b1;">Upgrade to Pro</h2>
                    <p style="margin-bottom: 24px; color: #666; line-height: 1.6;">
                        This feature is available in the Pro version. Unlock advanced AI capabilities, 
                        SEO optimization, custom styling, and priority support.
                    </p>
                    <div style="margin-bottom: 30px;">
                        <a href="admin.php?page=ai-pagegen-license" class="button button-primary" style="margin-right: 12px;">
                            Upgrade Now
                        </a>
                        <button class="button button-secondary ai-modal-close">Maybe Later</button>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
        
        // Bind close events
        $('.ai-modal-close, .ai-pro-modal').on('click', function(e) {
            if (e.target === this) {
                $('.ai-pro-modal').fadeOut(200, function() {
                    $(this).remove();
                });
            }
        });
        
        // Close on escape key
        $(document).on('keyup.modal', function(e) {
            if (e.keyCode === 27) { // Escape key
                $('.ai-pro-modal').fadeOut(200, function() {
                    $(this).remove();
                });
                $(document).off('keyup.modal');
            }
        });
    };
    
    /**
     * Utility functions
     */
    AIPageGen.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    AIPageGen.isValidColorScheme = function(value) {
        // Check for hex codes or color names
        const hexPattern = /^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/;
        const colorNames = ['red', 'blue', 'green', 'yellow', 'purple', 'orange', 'pink', 'black', 'white', 'gray', 'grey', 'dark', 'light'];
        
        const colors = value.split(',').map(c => c.trim());
        
        return colors.every(color => {
            return hexPattern.test(color) || colorNames.includes(color.toLowerCase());
        });
    };
    
    AIPageGen.updateCharacterCount = function($field) {
        const maxLength = 2000;
        const currentLength = $field.val().length;
        const $group = $field.closest('.form-group');
        
        let $counter = $group.find('.character-counter');
        if (!$counter.length) {
            $counter = $('<div class="character-counter" style="font-size: 12px; color: #666; margin-top: 4px;"></div>');
            $group.append($counter);
        }
        
        const remaining = maxLength - currentLength;
        const percentage = (currentLength / maxLength) * 100;
        
        let color = '#666';
        if (percentage > 90) color = '#d63638';
        else if (percentage > 75) color = '#dba617';
        
        $counter.css('color', color).text(`${currentLength}/${maxLength} characters`);
    };
    
    AIPageGen.previewColorScheme = function(colorScheme) {
        if (!colorScheme || !AIPageGen.isValidColorScheme(colorScheme)) return;
        
        // Create a small preview
        const colors = colorScheme.split(',').map(c => c.trim());
        const $preview = $('#color-scheme-preview');
        
        if (!$preview.length) {
            $('#color_scheme').after('<div id="color-scheme-preview" style="margin-top: 8px; display: flex; gap: 4px; height: 20px;"></div>');
        }
        
        const previewHtml = colors.map(color => 
            `<div style="flex: 1; background: ${color}; border-radius: 2px; border: 1px solid #ddd;"></div>`
        ).join('');
        
        $('#color-scheme-preview').html(previewHtml);
    };
    
    AIPageGen.trackEvent = function(eventName, properties = {}) {
        // Analytics tracking (implement with your preferred analytics service)
        if (window.gtag) {
            window.gtag('event', eventName, properties);
        }
        
        // Console log for development
        console.log('[AI PageGen Analytics]', eventName, properties);
    };
    
    AIPageGen.setupKeyboardShortcuts = function() {
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + Enter to submit form
            if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
                if ($('#ai-pagegen-form').length) {
                    e.preventDefault();
                    $('#ai-pagegen-form').submit();
                }
            }
        });
    };
    
    AIPageGen.setupTooltips = function() {
        // Enhanced tooltips for better UX
        $('[data-tooltip]').hover(
            function() {
                const tooltip = $(this).data('tooltip');
                $(this).append('<div class="ai-tooltip">' + tooltip + '</div>');
            },
            function() {
                $(this).find('.ai-tooltip').remove();
            }
        );
    };
    
    AIPageGen.initializeProgressBars = function() {
        // Add CSS for progress bars and animations
        const css = `
            <style>
                .ai-progress-container {
                    text-align: center;
                    padding: 40px 20px;
                }
                .ai-progress-header .ai-robot-icon {
                    font-size: 64px;
                    display: block;
                    margin-bottom: 16px;
                    animation: bounce 2s infinite;
                }
                .ai-progress-bar {
                    width: 100%;
                    height: 8px;
                    background: #e0e0e0;
                    border-radius: 4px;
                    margin: 20px 0;
                    overflow: hidden;
                }
                .ai-progress-fill {
                    height: 100%;
                    background: linear-gradient(90deg, #2271b1, #f0b849);
                    width: 0%;
                    transition: width 0.5s ease;
                    border-radius: 4px;
                }
                .ai-progress-steps {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 16px;
                }
                .ai-step {
                    font-size: 12px;
                    color: #999;
                    transition: color 0.3s ease;
                }
                .ai-step.active {
                    color: #2271b1;
                    font-weight: 600;
                }
                @keyframes bounce {
                    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
                    40% { transform: translateY(-10px); }
                    60% { transform: translateY(-5px); }
                }
                .pulse-animation {
                    animation: pulse 0.5s ease-in-out;
                }
                @keyframes pulse {
                    0% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                    100% { transform: scale(1); }
                }
                .tooltip-hover {
                    transform: scale(1.1);
                    transition: transform 0.2s ease;
                }
                @keyframes scaleIn {
                    from { transform: scale(0.9); opacity: 0; }
                    to { transform: scale(1); opacity: 1; }
                }
            </style>
        `;
        
        if (!$('#ai-pagegen-dynamic-styles').length) {
            $('head').append(css);
        }
    };
    
})(jQuery);
