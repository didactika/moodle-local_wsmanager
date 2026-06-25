/**
 * Health logs filter for Service Schema view page.
 *
 * @module     local_wsmanager/health_log_filter
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

/**
 * Initialize the filter.
 */
export const init = () => {
    const filter = document.getElementById('healthlog-status-filter');
    const table = document.getElementById('healthlog-table');

    if (!filter || !table) {
        return;
    }

    filter.addEventListener('change', (e) => {
        const selectedStatus = e.target.value;
        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            const status = row.getAttribute('data-status');
            if (!selectedStatus || status === selectedStatus) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
};
