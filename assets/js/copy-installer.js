/**
 * Community Master - Copy to clipboard + instant search filter.
 */

/* Copy to clipboard with visual feedback */
document.addEventListener('click', function (e) {
    var btn = e.target.closest('.cm-copy-btn');
    if (!btn) {
        return;
    }

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

/* Instant search filter */
document.addEventListener('DOMContentLoaded', function () {
    var input = document.querySelector('.cm-search__input');
    if (!input) return;

    var tiles = document.querySelectorAll('.cm-tile');
    var noResults = document.querySelector('.cm-no-results');

    input.addEventListener('input', function () {
        var query = this.value.toLowerCase().trim();
        var visibleCount = 0;

        tiles.forEach(function (tile) {
            var title = tile.getAttribute('data-cm-title') || '';
            var desc = tile.getAttribute('data-cm-desc') || '';
            var match = !query || title.indexOf(query) !== -1 || desc.indexOf(query) !== -1;

            tile.style.display = match ? '' : 'none';
            if (match) visibleCount++;
        });

        if (noResults) {
            noResults.style.display = (visibleCount === 0 && query) ? '' : 'none';
        }
    });
});
