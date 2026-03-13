(function () {
    'use strict';

    const ajaxUrl = sgkb_writebot_args?.ajaxUrl || '';
    const nonce = sgkb_writebot_args?.nonce || '';

    const buttonText = sgkb_writebot_args?.text?.button_text || '';
    const generatingButtonText = sgkb_writebot_args?.text?.generating_button_text || '';
    const generatedButtonText = sgkb_writebot_args?.text?.generated_button_text || '';
    const fieldsRequiredMessage = sgkb_writebot_args?.text?.fields_required_message || '';
    const errorMessage = sgkb_writebot_args?.text?.error_message || '';

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('.block-editor-page')) {
            const aiButton = document.createElement('div');
            aiButton.className = 'sgkb-writebot-button-container';
            aiButton.innerHTML = `
                <button class="sgkb-writebot-button">
                    <span class="dashicons-logo-icon"></span>
                    <span>${buttonText}</span>
                </button>
            `;

            // Add button to editor header
            wp.data.subscribe(function () {
                setTimeout(() => {
                    const editorSettings = document.querySelector('.edit-post-header__settings') ||
                        document.querySelector('.editor-header__settings');

                    if (editorSettings && !document.querySelector('.sgkb-writebot-button-container')) {
                        editorSettings.prepend(aiButton);
                    }
                }, 1);
            });

            // Event delegation for dynamically added elements
            document.addEventListener('click', function (event) {
                // Handle AI button click
                if (event.target.closest('.sgkb-writebot-button')) {
                    document.querySelector('.sgkb-writebot-modal-wrapper').classList.remove('sgkb-writebot-hidden');
                    updatePrompt();
                }

                // Handle close button and overlay clicks
                if (event.target.closest('.sgkb-writebot-close-button') ||
                    event.target.classList.contains('sgkb-writebot-modal-overlay')) {
                    document.querySelector('.sgkb-writebot-modal-wrapper').classList.add('sgkb-writebot-hidden');
                }

                // Handle generate button click
                if (event.target.closest('.sgkb-writebot-generate-button')) {
                    event.preventDefault();
                    clearErrorMessage();
                    generateContent();
                }
            });

            // Listen for title changes in Gutenberg
            let previousTitle = '';
            wp.data.subscribe(() => {
                const currentTitle = wp.data.select('core/editor').getEditedPostAttribute('title');

                if (currentTitle !== previousTitle) {
                    const titleInput = document.getElementById('sgkb-writebot-title');
                    if (titleInput) {
                        titleInput.value = currentTitle;
                        previousTitle = currentTitle;
                        updatePrompt();
                    }
                }
            });

            // Update prompt when inputs change
            ['sgkb-writebot-title', 'sgkb-writebot-keywords'].forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.addEventListener('input', updatePrompt);
                }
            });

            function updatePrompt() {
                const title = document.getElementById('sgkb-writebot-title').value || '{Title}';
                const keywords = document.getElementById('sgkb-writebot-keywords').value || '{Keywords}';

                let prompt = '';

                prompt += `Generate comprehensive documentation for '${title}' that is SEO-optimized and reads naturally. Structure the content with proper HTML heading tags (h1, h2, h3) for hierarchy and scanability.\n\n`;
                prompt += `Include these components:\n`;
                prompt += `1. A clear introduction explaining what '${title}' is and why it matters\n`;
                prompt += `2. Step-by-step instructions with numbered lists where appropriate\n`;
                prompt += `3. Practical examples demonstrating key concepts\n`;
                prompt += `4. Common questions or troubleshooting tips\n`;
                prompt += `5. Best practices and recommendations\n\n`;
                prompt += `Focus on these keywords naturally throughout the text: '${keywords}'. Don't keyword stuff - integrate them contextually where they make sense.\n\n`;
                prompt += `Format guidelines:\n`;
                prompt += `- Wrap paragraphs in <p> tags\n`;
                prompt += `- Use <ul> and <li> tags for unordered lists\n`;
                prompt += `- Use <ol> and <li> tags for ordered/numbered instructions\n`;
                prompt += `- Add <span class="highlight"> for important terms or concepts\n`;
                prompt += `- Include a "Quick Summary" section at the beginning\n`;
                prompt += `- End with a brief conclusion and next steps\n\n`;
                prompt += `The content should be conversational yet authoritative, with a Flesch-Kincaid readability score between 60-70. Use clear language, avoid jargon unless necessary, and explain technical terms when they first appear.\n\n`;
                prompt += `Make the content actionable with specific examples that readers can implement immediately. Focus on solving real problems users might have with '${title}'.`;

                document.getElementById('sgkb-writebot-prompt').value = prompt;
            }

            function generateContent() {
                const tool = document.getElementById('sgkb-writebot-tool').value;
                const title = document.getElementById('sgkb-writebot-title').value;
                const keywords = document.getElementById('sgkb-writebot-keywords').value;
                const prompt = document.getElementById('sgkb-writebot-prompt').value;
                const isOverwrite = document.getElementById('sgkb-writebot-overwrite').checked;

                if (!tool || !title || !keywords || !prompt) {
                    insertErrorMessage(fieldsRequiredMessage);
                    return;
                }

                const generateBtn = document.querySelector('.sgkb-writebot-generate-button');
                const generateBtnText = generateBtn.querySelector('span');
                const originalText = generateBtnText.textContent;
                generateBtnText.textContent = generatingButtonText;
                generateBtn.disabled = true;

                // Create XMLHttpRequest
                const xhr = new XMLHttpRequest();
                xhr.open('POST', ajaxUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.responseType = 'json';

                // Handle response
                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        const response = xhr.response;

                        if (response.status) {
                            generateBtnText.textContent = generatedButtonText;
                            setTimeout(() => {
                                insertContentToEditor(title, response.data.content, isOverwrite);
                                generateBtnText.textContent = originalText;
                                generateBtn.disabled = false;
                                document.querySelector('.sgkb-writebot-modal-wrapper').classList.add('sgkb-writebot-hidden');
                            }, 1000);
                        } else {
                            insertErrorMessage(errorMessage);
                            generateBtnText.textContent = originalText;
                            generateBtn.disabled = false;
                        }
                    } else {
                        console.error('XHR Error:', xhr.statusText);
                        insertErrorMessage(errorMessage);
                        generateBtnText.textContent = originalText;
                        generateBtn.disabled = false;
                    }
                };

                // Handle network errors
                xhr.onerror = function () {
                    console.error('Network Error');
                    insertErrorMessage(errorMessage);
                    generateBtnText.textContent = originalText;
                    generateBtn.disabled = false;
                };

                // Prepare and send data
                const formData = new URLSearchParams();
                formData.append('_ajax_nonce', nonce);
                formData.append('action', 'support-genix_AJ_Apbd_wps_knowledge_base_writebot_generate');
                formData.append('tool', tool);
                formData.append('prompt', prompt);
                formData.append('keywords', keywords);

                xhr.send(formData.toString());
            }

            function insertContentToEditor(title, htmlContent, isOverwrite) {
                const { dispatch, select } = wp.data;

                // Process HTML content
                htmlContent = preprocessHtml(htmlContent);

                // Convert HTML to blocks
                const blocks = wp.blocks.rawHandler({
                    HTML: htmlContent
                });

                // Set title
                dispatch('core/editor').editPost({
                    title: title,
                });

                if (isOverwrite) {
                    // Remove all existing blocks
                    const allBlocks = select('core/block-editor').getBlocks();
                    allBlocks.forEach(block => {
                        dispatch('core/block-editor').removeBlock(block.clientId);
                    });
                    // Insert new blocks
                    dispatch('core/block-editor').insertBlocks(blocks);
                } else {
                    // Insert at cursor position or end
                    const selectedBlockClientId = select('core/block-editor').getSelectedBlockClientId();
                    if (selectedBlockClientId) {
                        const selectedIndex = select('core/block-editor').getBlockIndex(selectedBlockClientId);
                        dispatch('core/block-editor').insertBlocks(blocks, selectedIndex + 1);
                    } else {
                        dispatch('core/block-editor').insertBlocks(blocks);
                    }
                }
            }

            function preprocessHtml(html) {
                // Extract body content if full HTML is returned
                const bodyMatch = html.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
                if (bodyMatch) {
                    html = bodyMatch[1];
                }

                // Apply highlighting to headings
                html = html.replace(/<(h\d)(.*?)>(.*?)<\/\1>/g, '<$1$2><span class="highlight">$3</span></$1>');

                // Clean up extra whitespace
                html = html.replace(/<p>\s+/g, '<p>');

                return html;
            }

            function insertErrorMessage(message) {
                const errorElement = document.querySelector('.sgkb-writebot-error');

                if (errorElement) {
                    errorElement.innerHTML = message;
                    errorElement.classList.remove('sgkb-writebot-hidden');
                }

                scrollToTop();
            }

            function clearErrorMessage(message) {
                const errorElement = document.querySelector('.sgkb-writebot-error');

                if (errorElement) {
                    errorElement.innerHTML = '';
                    errorElement.classList.add('sgkb-writebot-hidden');
                }
            }

            function scrollToTop() {
                const modalContent = document.querySelector('.sgkb-writebot-modal-content');
                if (!modalContent) return;

                modalContent.scrollTop = 0;
            }
        }
    });
})();