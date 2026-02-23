/**
 * Busca Koha - Admin JS v4.0.0
 * Connection test, library management
 */
(function () {
    'use strict';

    var cfg = window.bkAdmin || {};
    var i18n = cfg.i18n || {};

    /* ── Init ─────────────────────────────────────────────────────── */

    function init() {
        bindTestConnection();
        bindLibraryManagement();
        bindClearCache();
        bindAuthTypeToggle();
        bindColorPicker();
    }

    /* ── Connection Test ──────────────────────────────────────────── */

    function bindTestConnection() {
        var btn = document.getElementById('bk-test-connection');
        if (!btn) return;

        btn.addEventListener('click', function () {
            var modal = document.getElementById('bk-test-modal');
            var stepsEl = document.getElementById('bk-test-steps');
            var summaryEl = document.getElementById('bk-test-summary');
            if (!modal || !stepsEl) return;

            modal.classList.add('bk-modal--active');
            stepsEl.innerHTML = '';
            summaryEl.style.display = 'none';

            var stepNames = ['dns', 'http', 'api', 'auth'];
            var stepLabels = {
                dns: i18n.stepDns || 'DNS',
                http: i18n.stepHttp || 'HTTP/TLS',
                api: i18n.stepApi || 'API',
                auth: i18n.stepAuth || 'Auth'
            };

            stepNames.forEach(function (s) {
                stepsEl.innerHTML += '<div class="bk-test-step bk-step--pending" data-step="' + s + '">'
                    + '<span class="bk-step-icon">&#9711;</span>'
                    + '<span class="bk-step-label">' + esc(stepLabels[s]) + '</span>'
                    + '<span class="bk-step-message">...</span>'
                    + '</div>';
            });

            wp.apiFetch({
                path: '/busca-koha/v1/admin/test-connection',
                method: 'POST'
            }).then(function (data) {
                (data.steps || []).forEach(function (s) {
                    var el = stepsEl.querySelector('[data-step="' + s.step + '"]');
                    if (!el) return;
                    el.classList.remove('bk-step--pending');
                    el.classList.add('bk-step--' + s.status);
                    el.querySelector('.bk-step-icon').innerHTML = s.status === 'ok' ? '&#10003;' : '&#10007;';
                    el.querySelector('.bk-step-message').textContent = s.message + (s.time_ms ? ' (' + s.time_ms + 'ms)' : '');
                });

                summaryEl.style.display = 'block';
                if (data.success) {
                    summaryEl.className = 'bk-test-summary bk-summary-success';
                    summaryEl.textContent = i18n.connectionSuccess + ' (' + data.total_time_ms + 'ms)';
                } else {
                    summaryEl.className = 'bk-test-summary bk-summary-error';
                    summaryEl.textContent = i18n.connectionFailed;
                }
            }).catch(function (err) {
                summaryEl.style.display = 'block';
                summaryEl.className = 'bk-test-summary bk-summary-error';
                summaryEl.textContent = (err && err.message) || i18n.connectionFailed;
            });
        });

        // Modal close
        document.querySelectorAll('#bk-test-modal .bk-modal-close, #bk-test-modal .bk-modal-overlay, #bk-test-modal .bk-modal-close-btn').forEach(function (el) {
            el.addEventListener('click', function () {
                document.getElementById('bk-test-modal').classList.remove('bk-modal--active');
            });
        });
    }

    /* ── Library Management ───────────────────────────────────────── */

    function bindLibraryManagement() {
        var table = document.getElementById('bk-libraries-table');
        if (!table) return;

        // Import from Koha
        var importBtn = document.getElementById('bk-import-libraries');
        if (importBtn) {
            importBtn.addEventListener('click', function () {
                if (!confirm(i18n.confirmImport)) return;
                importBtn.disabled = true;
                importBtn.textContent = i18n.importing;

                wp.apiFetch({
                    path: '/busca-koha/v1/libraries/import',
                    method: 'POST'
                }).then(function (data) {
                    if (data.success) {
                        alert(i18n.importSuccess + ' (' + data.imported + ')');
                        location.reload();
                    }
                }).catch(function (err) {
                    alert(i18n.importFailed + ': ' + ((err && err.message) || ''));
                    importBtn.disabled = false;
                    importBtn.textContent = i18n.importing.replace('...', '');
                });
            });
        }

        // Load defaults
        var defaultsBtn = document.getElementById('bk-load-defaults');
        if (defaultsBtn) {
            defaultsBtn.addEventListener('click', function () {
                wp.apiFetch({
                    path: '/busca-koha/v1/libraries/defaults',
                    method: 'POST'
                }).then(function () {
                    location.reload();
                });
            });
        }

        // Add library
        var addBtn = document.getElementById('bk-add-library');
        if (addBtn) {
            addBtn.addEventListener('click', function () {
                var tbody = document.getElementById('bk-libraries-body');
                var row = document.createElement('tr');
                row.className = 'bk-library-row bk-editing';
                row.innerHTML = '<td class="bk-col-code"><input type="text" class="bk-edit-input bk-edit-code" placeholder="CODIGO" maxlength="20"></td>'
                    + '<td class="bk-col-name"><input type="text" class="bk-edit-input bk-edit-name" placeholder="Nome da biblioteca"></td>'
                    + '<td class="bk-col-actions">'
                    + '<button type="button" class="button button-small bk-save-edit" title="Salvar"><span class="dashicons dashicons-saved"></span></button>'
                    + '<button type="button" class="button button-small bk-cancel-edit" title="Cancelar"><span class="dashicons dashicons-no"></span></button>'
                    + '</td>';
                tbody.appendChild(row);
                row.querySelector('.bk-edit-code').focus();
            });
        }

        // Delegate: edit, delete, save, cancel
        table.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;
            var row = btn.closest('.bk-library-row');

            if (btn.classList.contains('bk-edit-library')) {
                editRow(row);
            } else if (btn.classList.contains('bk-delete-library')) {
                if (confirm(i18n.confirmDelete)) {
                    row.remove();
                    updateCount();
                }
            } else if (btn.classList.contains('bk-save-edit')) {
                saveRow(row);
            } else if (btn.classList.contains('bk-cancel-edit')) {
                if (row.dataset.code) {
                    cancelEdit(row);
                } else {
                    row.remove();
                }
            }
        });

        // Save libraries button
        var saveBtn = document.getElementById('bk-save-libraries');
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var libraries = collectLibraries();
                saveBtn.disabled = true;
                saveBtn.textContent = i18n.saving;

                wp.apiFetch({
                    path: '/busca-koha/v1/libraries/save',
                    method: 'POST',
                    data: { libraries: libraries }
                }).then(function () {
                    saveBtn.textContent = i18n.saved;
                    setTimeout(function () {
                        saveBtn.disabled = false;
                        saveBtn.textContent = cfg.i18n.saving ? cfg.i18n.saving.replace('...', '') : 'Salvar Bibliotecas';
                        location.reload();
                    }, 1000);
                }).catch(function () {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Salvar Bibliotecas';
                });
            });
        }
    }

    function editRow(row) {
        var code = row.dataset.code;
        var name = row.dataset.name;
        row.classList.add('bk-editing');
        row.querySelector('.bk-col-code').innerHTML = '<input type="text" class="bk-edit-input bk-edit-code" value="' + esc(code) + '" maxlength="20">';
        row.querySelector('.bk-col-name').innerHTML = '<input type="text" class="bk-edit-input bk-edit-name" value="' + esc(name) + '">';
        row.querySelector('.bk-col-actions').innerHTML = '<button type="button" class="button button-small bk-save-edit"><span class="dashicons dashicons-saved"></span></button>'
            + '<button type="button" class="button button-small bk-cancel-edit"><span class="dashicons dashicons-no"></span></button>';
    }

    function saveRow(row) {
        var codeInput = row.querySelector('.bk-edit-code');
        var nameInput = row.querySelector('.bk-edit-name');
        var code = (codeInput.value || '').trim().toUpperCase();
        var name = (nameInput.value || '').trim();
        if (!code || !name) return;

        row.dataset.code = code;
        row.dataset.name = name;
        row.classList.remove('bk-editing');
        row.querySelector('.bk-col-code').innerHTML = '<code>' + esc(code) + '</code>';
        row.querySelector('.bk-col-name').textContent = name;
        row.querySelector('.bk-col-actions').innerHTML = '<button type="button" class="button button-small bk-edit-library" title="Editar"><span class="dashicons dashicons-edit"></span></button>'
            + '<button type="button" class="button button-small bk-delete-library" title="Remover"><span class="dashicons dashicons-trash"></span></button>';
        updateCount();
    }

    function cancelEdit(row) {
        row.classList.remove('bk-editing');
        row.querySelector('.bk-col-code').innerHTML = '<code>' + esc(row.dataset.code) + '</code>';
        row.querySelector('.bk-col-name').textContent = row.dataset.name;
        row.querySelector('.bk-col-actions').innerHTML = '<button type="button" class="button button-small bk-edit-library" title="Editar"><span class="dashicons dashicons-edit"></span></button>'
            + '<button type="button" class="button button-small bk-delete-library" title="Remover"><span class="dashicons dashicons-trash"></span></button>';
    }

    function collectLibraries() {
        var rows = document.querySelectorAll('#bk-libraries-body .bk-library-row');
        var libs = [];
        rows.forEach(function (row) {
            var code = row.dataset.code || '';
            var name = row.dataset.name || '';
            if (code && name) libs.push({ code: code, name: name });
        });
        return libs;
    }

    function updateCount() {
        var count = document.querySelectorAll('#bk-libraries-body .bk-library-row').length;
        var el = document.querySelector('.bk-libraries-count');
        if (el) el.textContent = count + ' biblioteca' + (count !== 1 ? 's' : '') + ' cadastrada' + (count !== 1 ? 's' : '');
    }

    /* ── Clear Cache ──────────────────────────────────────────────── */

    function bindClearCache() {
        var btn = document.getElementById('bk-clear-cache');
        if (!btn) return;

        btn.addEventListener('click', function () {
            if (!confirm(i18n.confirmFlush)) return;
            btn.disabled = true;

            wp.apiFetch({
                path: '/busca-koha/v1/admin/clear-cache',
                method: 'POST'
            }).then(function () {
                var status = document.getElementById('bk-cache-status');
                if (status) {
                    status.textContent = i18n.cacheCleared;
                    status.className = 'bk-inline-status bk-status-success';
                }
                btn.disabled = false;
            }).catch(function () {
                btn.disabled = false;
            });
        });
    }

    /* ── Auth Type Toggle ─────────────────────────────────────────── */

    function bindAuthTypeToggle() {
        var radios = document.querySelectorAll('input[name="bk_auth_type"]');
        if (!radios.length) return;

        function updateVisibility() {
            var val = document.querySelector('input[name="bk_auth_type"]:checked');
            var form = document.getElementById('bk-connection-form');
            if (form && val) {
                form.setAttribute('data-auth-type', val.value);
            }
        }

        radios.forEach(function (r) {
            r.addEventListener('change', updateVisibility);
        });
        updateVisibility();
    }

    /* ── Color Picker ─────────────────────────────────────────────── */

    function bindColorPicker() {
        if (typeof jQuery !== 'undefined' && jQuery.fn.wpColorPicker) {
            jQuery('.bk-color-picker').wpColorPicker();
        }
    }

    /* ── Helpers ───────────────────────────────────────────────────── */

    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str || ''));
        return d.innerHTML;
    }

    /* ── Boot ─────────────────────────────────────────────────────── */

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
