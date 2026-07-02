/**
 * AWAN Password Strength Meter
 * Call initPasswordStrength('inputId', 'meterId') to attach.
 */
(function () {
    function score(pw) {
        var s = 0;
        if (!pw || pw.length === 0) return 0;
        if (pw.length >= 8)  s++;
        if (pw.length >= 12) s++;
        if (/[A-Z]/.test(pw)) s++;
        if (/[a-z]/.test(pw)) s++;
        if (/[0-9]/.test(pw)) s++;
        if (/[^A-Za-z0-9]/.test(pw)) s++;
        return s;
    }

    function label(s) {
        if (s <= 1) return { text: 'Very Weak', color: '#ef4444', width: '12%' };
        if (s === 2) return { text: 'Weak',      color: '#f97316', width: '30%' };
        if (s === 3) return { text: 'Fair',      color: '#eab308', width: '50%' };
        if (s === 4) return { text: 'Good',      color: '#22c55e', width: '72%' };
        return              { text: 'Strong',    color: '#16a34a', width: '100%' };
    }

    window.initPasswordStrength = function (inputId, containerId) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var container = document.getElementById(containerId);
        if (!container) {
            container = document.createElement('div');
            container.id = containerId;
            input.parentNode.insertBefore(container, input.nextSibling);
        }

        container.innerHTML = '<div style="margin-top:6px">'
            + '<div style="height:4px;background:var(--color-border,#e2e8f0);border-radius:2px;overflow:hidden">'
            + '<div id="' + containerId + '_bar" style="height:100%;width:0%;border-radius:2px;transition:width .3s,background .3s"></div>'
            + '</div>'
            + '<div id="' + containerId + '_label" style="font-size:11px;color:var(--color-text-muted,#64748b);margin-top:3px"></div>'
            + '</div>';

        var bar   = document.getElementById(containerId + '_bar');
        var lbl   = document.getElementById(containerId + '_label');

        input.addEventListener('input', function () {
            var s = score(this.value);
            if (this.value.length === 0) {
                bar.style.width = '0%';
                lbl.textContent = '';
                return;
            }
            var info = label(s);
            bar.style.width      = info.width;
            bar.style.background = info.color;
            lbl.textContent      = info.text;
            lbl.style.color      = info.color;
        });
    };
})();
