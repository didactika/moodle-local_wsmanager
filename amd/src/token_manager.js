/**
 * Token manager AMD module for Web Service Manager
 *
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module     local_servicemanager/token_manager
 */

import { get_string as getString } from 'core/str';
import { addNotification } from 'core/notification';

/**
 * Copy text to clipboard
 *
 * @param {string} text Text to copy
 * @param {Element} button The button element
 * @param {string} targetId Optional input element ID to select
 */
const copyToClipboard = (text, button, targetId) => {
    // Select input for visual feedback
    if (targetId) {
        const input = document.getElementById(targetId);
        if (input) {
            input.select();
            try {
                input.setSelectionRange(0, 99999);
            } catch (e) {
                // Ignore if not supported
            }
        }
    }

    // Try clipboard API first
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(text).then(() => {
            showCopySuccess(button);
        }).catch((err) => {
            // eslint-disable-next-line no-console
            console.log('Clipboard API failed, using fallback', err);
            fallbackCopy(text, button);
        });
    } else {
        fallbackCopy(text, button);
    }
};

/**
 * Fallback copy method using execCommand
 *
 * @param {string} text Text to copy
 * @param {Element} button The button element
 */
const fallbackCopy = (text, button) => {
    const textArea = document.createElement('textarea');
    textArea.value = text;

    // Make textarea out of viewport
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    textArea.style.opacity = '0';

    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    let success = false;
    try {
        success = document.execCommand('copy');
    } catch (err) {
        // eslint-disable-next-line no-console
        console.error('execCommand failed:', err);
    }

    document.body.removeChild(textArea);

    if (success) {
        showCopySuccess(button);
    } else {
        addNotification({
            message: 'Failed to copy token to clipboard',
            type: 'error'
        });
    }
};

/**
 * Show success feedback on button
 *
 * @param {Element} button The button element
 */
const showCopySuccess = (button) => {
    const originalHtml = button.innerHTML;
    const originalClass = button.className;

    button.innerHTML = '<i class="fa fa-check mr-1"></i>Copied!';
    button.className = button.className.replace('btn-success', '').replace('btn-outline-secondary', '') + ' btn-success';

    getString('copied', 'local_servicemanager').then((copiedStr) => {
        button.innerHTML = '<i class="fa fa-check mr-1"></i>' + copiedStr;
    }).catch(() => {
    });

    setTimeout(() => {
        button.innerHTML = originalHtml;
        button.className = originalClass;
    }, 2000);
};

/**
 * Initialize the token manager
 */
export const init = () => {
    // Bind copy button click events using event delegation
    document.addEventListener('click', (e) => {
        const button = e.target.closest('.copy-token');
        if (button) {
            e.preventDefault();
            e.stopPropagation();

            const token = button.getAttribute('data-token');
            const targetId = button.getAttribute('data-target');

            if (token) {
                copyToClipboard(token, button, targetId);
            }
        }
    });
};
