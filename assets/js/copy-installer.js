/**
 * Community Master - Copy to clipboard with visual feedback.
 *
 * Uses event delegation on document for all .cm-copy-btn clicks.
 * Primary: navigator.clipboard API. Fallback: execCommand('copy').
 */
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
        // Fallback for non-HTTPS environments
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
