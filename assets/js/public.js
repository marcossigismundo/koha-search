/**
 * Busca Koha - Public JS v4.0.0
 * Redirect search to Koha OPAC (opens in new window)
 */
(function () {
    'use strict';

    var C = window.buscaKohaConfig || {};
    var els = {};
    var preconnected = false;

    /* ── Utilities ────────────────────────────────────────────────── */

    function preconnect() {
        if (preconnected) return;
        preconnected = true;
        if (!C.opacUrl) return;
        var link = document.createElement('link');
        link.rel = 'preconnect';
        link.href = C.opacUrl.replace(/\/cgi-bin.*/, '');
        link.crossOrigin = 'anonymous';
        document.head.appendChild(link);
    }

    /* ── Modal ────────────────────────────────────────────────────── */

    function initModal() {
        var trigger = document.getElementById('bk-modal-trigger');
        var overlay = document.getElementById('bk-modal-overlay');
        var closeBtn = document.getElementById('bk-modal-close');

        if (!trigger || !overlay) return;

        trigger.addEventListener('click', function () {
            overlay.classList.add('bk-modal--open');
            overlay.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
            var input = overlay.querySelector('#busca-koha-input');
            if (input) input.focus();
        });

        function closeModal() {
            overlay.classList.remove('bk-modal--open');
            overlay.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            trigger.focus();
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closeModal);
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeModal();
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && overlay.classList.contains('bk-modal--open')) {
                closeModal();
            }
        });
    }

    /* ── Init ─────────────────────────────────────────────────────── */

    function init() {
        els.input = document.getElementById('busca-koha-input');
        els.submit = document.getElementById('busca-koha-submit');
        els.select = document.getElementById('busca-biblioteca-select');
        els.authority = document.getElementById('bk-authority-btn');

        if (!els.input) return;

        // Performance: preconnect on first interaction
        els.input.addEventListener('focus', preconnect, { once: true });
        els.input.addEventListener('input', preconnect, { once: true });

        // Search on Enter
        els.input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                executarBusca('geral');
            }
        });

        // Search button click
        if (els.submit) {
            els.submit.addEventListener('click', function () {
                executarBusca('geral');
            });
        }

        // Authority search
        if (els.authority) {
            els.authority.addEventListener('click', function () {
                executarBusca('autoridade');
            });
        }

        // Preconnect on library select focus
        if (els.select) {
            els.select.addEventListener('focus', preconnect, { once: true });
        }

        // Init modal if present
        initModal();
    }

    /* ── Search Redirect ─────────────────────────────────────────── */

    function executarBusca(tipo) {
        var term = (els.input.value || '').trim();

        // Empty input validation with shake animation
        if (!term && tipo !== 'autoridade') {
            els.input.focus();
            els.input.classList.add('busca-input--vazio');
            setTimeout(function () { els.input.classList.remove('busca-input--vazio'); }, 600);
            return;
        }

        var base = C.opacUrl || '';
        var code = els.select ? els.select.value : '';
        var url;

        switch (tipo) {
            case 'autoridade':
                url = term
                    ? base + '/opac-authorities-home.pl?op=do_search&type=opac&operator=contains&value=' + encodeURIComponent(term)
                    : base + '/opac-authorities-home.pl';
                break;
            default:
                url = base + '/opac-search.pl?idx=kw%2Cwrdl&q=' + encodeURIComponent(term) + '&weight_search=1';
                if (code && term) {
                    url += '&limit=branch:' + encodeURIComponent(code);
                }
                break;
        }

        window.open(url, '_blank', 'noopener,noreferrer');
    }

    /* ── Bootstrap ─────────────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Public API for backwards compatibility
    window.buscaKoha = {
        executar: function (tipo) {
            executarBusca(tipo || 'geral');
        }
    };

})();
