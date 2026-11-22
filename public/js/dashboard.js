/*
 * ByteStock real-time dashboard.
 *
 * Polls the JSON API endpoints every 30 seconds (configurable through the
 * data-poll-interval attribute) and refreshes the stats, low-stock list and
 * recent transaction table without a page reload.
 */
(function () {
    'use strict';

    const root = document.getElementById('dashboard-root');
    if (!root) {
        return;
    }

    const endpoints = {
        stats: root.dataset.statsUrl,
        lowStock: root.dataset.lowStockUrl,
        recent: root.dataset.recentUrl,
    };
    const interval = Number(root.dataset.pollInterval) || 30000;

    const numberFormat = new Intl.NumberFormat('en-US');
    const moneyFormat = new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'PHP',
        minimumFractionDigits: 2,
    });

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    async function getJson(url) {
        const response = await fetch(url, {
            method: 'GET',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            throw new Error('Request failed: ' + response.status);
        }

        return response.json();
    }

    function setNumber(id, value, formatted) {
        const element = document.getElementById(id);
        if (!element) {
            return;
        }
        const next = formatted ?? numberFormat.format(value ?? 0);
        if (element.textContent.trim() === next) {
            return;
        }
        element.textContent = next;
        element.classList.remove('value-updated');
        void element.offsetWidth; // restart the CSS animation
        element.classList.add('value-updated');
    }

    function renderStats(payload) {
        const stats = payload.stats || {};
        setNumber('stat-products', stats.total_products);
        setNumber('stat-low', stats.low_stock);
        setNumber('stat-out', stats.out_of_stock);
        setNumber('stat-value', stats.inventory_value, moneyFormat.format(stats.inventory_value ?? 0));
        setNumber('stat-transactions', stats.total_transactions);
        setNumber('stat-today', stats.today_transactions);
    }

    function renderLowStock(products) {
        const list = document.getElementById('lowStockList');
        if (!list) {
            return;
        }

        if (!products.length) {
            list.innerHTML = '<p class="text-muted px-0 py-3 mb-0">No low stock items. Everything is well stocked.</p>';
            return;
        }

        list.innerHTML = products.map((product) => {
            const badge = product.status_color === 'danger' ? 'badge-out' : 'badge-low';
            return `
                <a href="${escapeHtml(product.url)}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center px-0">
                    <span>
                        <span class="fw-semibold d-block">${escapeHtml(product.name)}</span>
                        <span class="small text-muted">${escapeHtml(product.sku)} &middot; ${product.quantity} / ${product.reorder_level}</span>
                    </span>
                    <span class="stock-badge ${badge}">${escapeHtml(product.status)}</span>
                </a>`;
        }).join('');
    }

    function renderTransactions(transactions) {
        const body = document.getElementById('recentTransactionsBody');
        if (!body) {
            return;
        }

        if (!transactions.length) {
            body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No stock movements recorded yet.</td></tr>';
            return;
        }

        body.innerHTML = transactions.map((transaction) => {
            const product = transaction.product || {};
            const signed = Number(transaction.signed_quantity) || 0;
            const sign = signed > 0 ? '+' : '';
            return `
                <tr>
                    <td class="text-nowrap">${escapeHtml(transaction.created_at_human)}</td>
                    <td><a href="${escapeHtml(product.url || '#')}" class="text-decoration-none fw-semibold">${escapeHtml(product.name || '—')}</a></td>
                    <td><span class="stock-badge badge-${escapeHtml(transaction.type_color)}">${escapeHtml(transaction.type_label)}</span></td>
                    <td class="text-end fw-semibold text-${escapeHtml(transaction.type_color)}">${sign}${numberFormat.format(signed)}</td>
                    <td><code>${escapeHtml(transaction.reference)}</code></td>
                    <td>${escapeHtml(transaction.user || 'System')}</td>
                </tr>`;
        }).join('');
    }

    function markUpdated(success) {
        const indicator = document.getElementById('liveIndicator');
        const timestamp = document.getElementById('lastUpdated');
        if (timestamp) {
            timestamp.textContent = new Date().toLocaleTimeString();
        }
        if (indicator) {
            indicator.classList.toggle('border-danger', !success);
            indicator.classList.toggle('text-danger', !success);
            indicator.classList.toggle('text-success', success);
        }
    }

    async function refresh() {
        try {
            const [stats, lowStock, recent] = await Promise.all([
                getJson(endpoints.stats),
                getJson(endpoints.lowStock),
                getJson(endpoints.recent),
            ]);

            renderStats(stats);
            renderLowStock(lowStock.data || []);
            renderTransactions(recent.data || []);
            markUpdated(true);
        } catch (error) {
            console.error('Dashboard refresh failed:', error);
            markUpdated(false);
        }
    }

    refresh();
    window.setInterval(refresh, interval);
})();
