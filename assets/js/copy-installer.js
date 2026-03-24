/**
 * Community Master - Copy to clipboard, search filter, sort.
 */

/* Copy to clipboard */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.cm-copy-btn');
    if (!btn) return;

    var text = btn.getAttribute('data-copy');
    var originalLabel = btn.textContent;

    function showFeedback() {
        btn.textContent = 'Copied!';
        btn.classList.add('cm-copy-btn--copied');
        setTimeout(function () {
            btn.textContent = originalLabel;
            btn.classList.remove('cm-copy-btn--copied');
        }, 2000);
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(showFeedback);
    } else {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showFeedback();
    }
});

/* Search + Sort */
(function init() {
    var input = document.querySelector('.cm-search__input');
    var sortBtn = document.querySelector('.cm-sort__btn');
    var grid = document.querySelector('.cm-grid');
    if (!input || !grid) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
        }
        return;
    }

    var noResults = document.querySelector('.cm-no-results');

    function getTiles() {
        return Array.prototype.slice.call(grid.querySelectorAll('.cm-tile'));
    }

    function filterAndSort() {
        var query = (input.value || '').toLowerCase().trim();
        var sort = sortBtn ? sortBtn.getAttribute('data-sort') : 'newest';
        var tiles = getTiles();
        var visibleCount = 0;

        // Filter
        tiles.forEach(function (tile) {
            var title = tile.getAttribute('data-cm-title') || '';
            var desc = tile.getAttribute('data-cm-desc') || '';
            var match = !query || title.indexOf(query) !== -1 || desc.indexOf(query) !== -1;
            tile.style.display = match ? '' : 'none';
            tile._visible = match;
            if (match) visibleCount++;
        });

        // Sort visible tiles
        var visible = tiles.filter(function (t) { return t._visible; });
        if (sort === 'name') {
            visible.sort(function (a, b) {
                return (a.getAttribute('data-cm-title') || '').localeCompare(b.getAttribute('data-cm-title') || '');
            });
        } else {
            visible.sort(function (a, b) {
                return (b.getAttribute('data-cm-date') || '').localeCompare(a.getAttribute('data-cm-date') || '');
            });
        }

        // Re-append in order
        visible.forEach(function (tile) { grid.appendChild(tile); });
        // Append hidden ones at end
        tiles.filter(function (t) { return !t._visible; }).forEach(function (tile) { grid.appendChild(tile); });

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && query) ? '' : 'none';
        }
    }

    input.addEventListener('input', filterAndSort);
    if (sortBtn) {
        var sortModes = [
            { key: 'newest', label: 'Neueste zuerst ↕' },
            { key: 'name', label: 'Name (A–Z) ↕' }
        ];
        sortBtn.addEventListener('click', function () {
            var current = sortBtn.getAttribute('data-sort');
            var nextIndex = current === 'newest' ? 1 : 0;
            sortBtn.setAttribute('data-sort', sortModes[nextIndex].key);
            sortBtn.textContent = sortModes[nextIndex].label;
            filterAndSort();
        });
    }
})();
