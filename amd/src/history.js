/**
 * History page logic for Web Service Manager.
 *
 * @author     Eduardo Estrada <me@e2rd0.com>
 * @author     Hector Arrechea
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module     local_wsmanager/history
 */

import $ from 'jquery';
import { get_string as getString } from 'core/str';
import Notification from 'core/notification';

/**
 * Initialize the history page logic.
 */
export const init = () => {
    const compareBtn = $('#compare-btn');
    const container = $('.wsmanager-history'); // Main container

    // Use event delegation for checkboxes since they might be inside a dynamic table or late-bound
    container.on('change', '.compare-checkbox', function () {
        let checked = $('.compare-checkbox:checked');

        if (checked.length > 2) {
            $(this).prop('checked', false); // Uncheck the one just clicked
            getString('compare_select_two', 'local_wsmanager').then(s => {
                Notification.alert('', s);
            });
            // Re-calculate checked after unchecking
            checked = $('.compare-checkbox:checked');
        }

        if (checked.length === 2) {
            compareBtn.removeAttr('disabled');
        } else {
            compareBtn.attr('disabled', 'disabled');
        }
    });

    // YAML Content Toggle - delegation
    container.on('click', '.toggle-yaml-btn', function (e) {
        e.preventDefault();
        const targetId = $(this).attr('data-target');
        const targetEl = document.getElementById(targetId);
        if (targetEl) {
            $(targetEl).toggle();
        }
    });

    // Filter form handling
    const filterForm = document.getElementById('history-filter-form');

    if (filterForm) {
        filterForm.addEventListener('submit', () => {
            const datefrom = document.getElementById('filter-datefrom').value;
            const dateto = document.getElementById('filter-dateto').value;

            if (datefrom) {
                const ts = Math.floor(new Date(datefrom).getTime() / 1000);
                document.getElementById('history-datefrom-timestamp').value = ts;
            }
            if (dateto) {
                const ts = Math.floor(new Date(dateto).getTime() / 1000);
                document.getElementById('history-dateto-timestamp').value = ts;
            }

            // Disable original date inputs so they aren't sent in URL (cleaner URL).
            document.getElementById('filter-datefrom').disabled = true;
            document.getElementById('filter-dateto').disabled = true;
        });
    }

    // Keep dropdown open on click inside.
    $('.wsmanager-filter-dropdown').on('click', function (e) {
        e.stopPropagation();
    });
};
