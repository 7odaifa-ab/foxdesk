(function (window, document) {
    'use strict';

    var config = window.FoxDeskTicketDetailConfig || {};
    var labels = config.labels || {};
    var icons = config.icons || {};
    var ticketId = config.ticketId || null;
    var csrfToken = config.csrfToken || window.csrfToken || '';

    var fileIconPaths = {
        times: '<line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line>',
        file: '<path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline>',
        'file-image': '<rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline>',
        'file-pdf': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
        'file-word': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
        'file-excel': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>',
        'file-archive': '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline>'
    };

    function t(key, fallback) {
        return Object.prototype.hasOwnProperty.call(labels, key) ? labels[key] : fallback;
    }

    function getIcon(name, classes) {
        var path = fileIconPaths[name] || fileIconPaths.file;
        return '<svg xmlns="http://www.w3.org/2000/svg" class="' + (classes || '') + '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + path + '</svg>';
    }
    window.getIcon = window.getIcon || getIcon;

    function escapeHtml(value) {
        var div = document.createElement('div');
        div.textContent = value == null ? '' : String(value);
        return div.innerHTML;
    }

    function showToast(message, type, options) {
        type = type || 'success';
        if (typeof window.showAppToast === 'function') {
            if (window.showAppToast(message, type, options || {})) return;
        }
        if (window.appNotificationPrefs && window.appNotificationPrefs.inAppEnabled === false) return;

        var toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg text-sm font-medium z-50 transition-opacity duration-300 ' + (type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white');
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function () {
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 300);
        }, 3000);
    }
    window.showToastGlobal = showToast;

    function restoreDeletedItem(action, undoToken) {
        if (!action || !undoToken) return;
        var formData = new FormData();
        formData.append('undo_token', undoToken);
        formData.append('csrf_token', csrfToken);
        fetch('index.php?page=api&action=' + encodeURIComponent(action), { method: 'POST', body: formData })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    showToast(data.message || t('restored', 'Restored.'), 'success');
                    window.setTimeout(function () { window.location.reload(); }, 350);
                } else {
                    showToast(data.error || t('undoFailed', 'Undo is no longer available.'), 'error', { force: true });
                }
            })
            .catch(function () {
                showToast(t('genericError', 'An error occurred.'), 'error', { force: true });
            });
    }

    function showUndoToast(message, data) {
        showToast(message, 'success', {
            force: true,
            duration: Math.max(1000, Number(data.undo_seconds || 10) * 1000),
            actionLabel: data.undo_label || t('undo', 'Undo'),
            onAction: function () { restoreDeletedItem(data.undo_action, data.undo_token); }
        });
    }

    function fadeRemove(node) {
        if (!node) return;
        node.style.opacity = '0';
        node.style.transition = 'opacity 0.2s';
        setTimeout(function () { node.remove(); }, 220);
    }

    window.quickEditField = function (action, data) {
        var body = new FormData();
        body.append('ticket_id', ticketId);
        Object.keys(data || {}).forEach(function (key) {
            body.append(key, data[key]);
        });

        fetch(window.appConfig.apiUrl + '&action=' + action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken },
            body: body
        })
            .then(function (response) { return response.json(); })
            .then(function (result) {
                if (result.success) {
                    showToast(result.message || t('saved', 'Saved'), 'success');
                } else {
                    showToast(result.error || t('error', 'Error'), 'error');
                }
            })
            .catch(function () {
                showToast(t('error', 'Error'), 'error');
            });
    };

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function fillTemplate(template, replacements) {
        var output = String(template || '');
        Object.keys(replacements || {}).forEach(function (key) {
            output = output.split('{' + key + '}').join(replacements[key]);
        });
        return output;
    }

    function formatFileSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(2) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(2) + ' KB';
        return bytes + ' B';
    }

    function fileIconName(mimeType) {
        mimeType = String(mimeType || '');
        if (mimeType.indexOf('image/') === 0) return 'file-image';
        if (mimeType === 'application/pdf') return 'file-pdf';
        if (mimeType.indexOf('word') !== -1) return 'file-word';
        if (mimeType.indexOf('excel') !== -1 || mimeType.indexOf('spreadsheet') !== -1) return 'file-excel';
        if (mimeType.indexOf('zip') !== -1 || mimeType.indexOf('rar') !== -1) return 'file-archive';
        return 'file';
    }

    function fallbackUploadPreview(options) {
        var input = document.getElementById(options.inputId);
        var preview = document.getElementById(options.previewId);
        var limit = options.limit || {};
        var removeLabel = options.removeLabel || t('remove', 'Remove');
        if (!input || !preview) {
            return { enforceLimits: function () { return { changed: false, hadErrors: false }; } };
        }

        function showLimit(message) {
            if (!message) return;
            showToast(message, 'error');
        }

        function enforceLimits() {
            if (typeof DataTransfer === 'undefined') return { changed: false, hadErrors: false };
            var dt = new DataTransfer();
            var originalCount = input.files.length;
            var totalSize = 0;
            var hadErrors = false;
            var totalErrorShown = false;

            for (var i = 0; i < input.files.length; i++) {
                var file = input.files[i];
                if (limit.single > 0 && file.size > limit.single) {
                    hadErrors = true;
                    showLimit(fillTemplate(limit.singleTemplate, { name: file.name, size: formatFileSize(limit.single) }));
                    continue;
                }
                if (limit.total > 0 && totalSize + file.size > limit.total) {
                    hadErrors = true;
                    if (!totalErrorShown) {
                        showLimit(fillTemplate(limit.totalTemplate, { size: formatFileSize(limit.total) }));
                        totalErrorShown = true;
                    }
                    continue;
                }
                totalSize += file.size;
                dt.items.add(file);
            }

            if (originalCount !== dt.files.length) {
                input.files = dt.files;
                return { changed: true, hadErrors: hadErrors };
            }
            return { changed: false, hadErrors: hadErrors };
        }

        function removeFile(index) {
            var dt = new DataTransfer();
            for (var i = 0; i < input.files.length; i++) {
                if (i !== index) dt.items.add(input.files[i]);
            }
            input.files = dt.files;
            updatePreview();
        }
        window.removeCommentFile = removeFile;

        function updatePreview() {
            var validation = enforceLimits();
            preview.innerHTML = '';
            if (input.files.length === 0) {
                preview.classList.add('hidden');
                return validation;
            }

            preview.classList.remove('hidden');
            for (var i = 0; i < input.files.length; i++) {
                var file = input.files[i];
                var row = document.createElement('div');
                row.className = 'flex items-center justify-between rounded-lg px-4 py-2';
                row.style.background = 'var(--surface-secondary)';
                row.innerHTML = '<div class="flex items-center space-x-3 min-w-0">' +
                    getIcon(fileIconName(file.type), 'td-text-muted flex-shrink-0 w-4 h-4') +
                    '<span class="text-sm truncate" style="color: var(--text-secondary)"></span>' +
                    '<span class="text-xs flex-shrink-0" style="color: var(--text-muted)">' + escapeHtml(formatFileSize(file.size)) + '</span>' +
                    '</div>';
                row.querySelector('.truncate').textContent = file.name;

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'text-red-400 hover:text-red-500 ml-2 flex-shrink-0';
                button.title = removeLabel;
                button.setAttribute('aria-label', removeLabel);
                button.innerHTML = getIcon('times', 'w-4 h-4');
                button.addEventListener('click', removeFile.bind(null, i));
                row.appendChild(button);
                preview.appendChild(row);
            }
            return validation;
        }

        if (window.initFileDropzone && options.zoneId) {
            window.initFileDropzone({ zoneId: options.zoneId, inputId: options.inputId, onFilesChanged: updatePreview });
        } else {
            input.addEventListener('change', updatePreview);
        }
        return { enforceLimits: enforceLimits, updatePreview: updatePreview };
    }

    function initUploadPreview() {
        var uploadConfig = config.upload || {};
        var options = {
            zoneId: 'comment-upload-zone',
            inputId: 'comment-file-input',
            previewId: 'comment-file-preview',
            removeLabel: t('remove', 'Remove'),
            limit: {
                single: uploadConfig.single || 0,
                total: uploadConfig.total || 0,
                singleTemplate: uploadConfig.singleTemplate || '',
                totalTemplate: uploadConfig.totalTemplate || ''
            },
            rowClass: uploadConfig.rowClass || 'flex items-center justify-between rounded-lg px-4 py-2',
            iconClass: uploadConfig.iconClass || 'td-text-muted flex-shrink-0 w-4 h-4',
            metaClass: uploadConfig.metaClass || 'flex items-center gap-3 min-w-0',
            nameClass: uploadConfig.nameClass || 'text-sm truncate',
            sizeClass: uploadConfig.sizeClass || 'text-xs flex-shrink-0',
            removeButtonClass: uploadConfig.removeButtonClass || 'text-red-400 hover:text-red-500 ml-2 flex-shrink-0',
            removeIconClass: uploadConfig.removeIconClass || 'w-4 h-4',
            sizeDecimals: uploadConfig.sizeDecimals || 2
        };

        var instance = window.FoxDeskUploadPreview && window.FoxDeskUploadPreview.init
            ? window.FoxDeskUploadPreview.init(options)
            : fallbackUploadPreview(options);

        if (window.FoxDeskAttachmentPasteDrop) {
            window.FoxDeskAttachmentPasteDrop.bind({
                inputId: options.inputId,
                targetSelectors: ['#comment-form', '#comment-upload-zone'],
                namePrefix: 'ticket-attachment',
                onFilesChanged: function () {
                    if (instance && instance.updatePreview) instance.updatePreview();
                }
            });
        }

        window.enforceCommentUploadLimits = function () {
            if (instance && instance.enforceLimits) return instance.enforceLimits();
            return { changed: false, hadErrors: false };
        };
    }

    function initShareCopy() {
        var button = document.getElementById('share-copy-btn');
        var input = document.getElementById('share-link-input');
        if (!button || !input) return;

        button.addEventListener('click', function () {
            var value = input.value;
            var reset = function () {
                setTimeout(function () { button.textContent = t('copy', 'Copy'); }, 1500);
            };
            var copied = function () {
                button.textContent = t('copied', 'Copied');
                reset();
            };
            if (navigator.clipboard) {
                navigator.clipboard.writeText(value).then(copied).catch(function () {
                    button.textContent = t('error', 'Error');
                    reset();
                });
            } else {
                input.select();
                document.execCommand('copy');
                copied();
            }
        });
    }

    function pad2(value) {
        return String(value).padStart(2, '0');
    }

    function formatDateInput(date) {
        return date.getFullYear() + '-' + pad2(date.getMonth() + 1) + '-' + pad2(date.getDate());
    }

    function formatTimeInput(date) {
        return pad2(date.getHours()) + ':' + pad2(date.getMinutes());
    }

    function formatDateTimeLocal(date) {
        return formatDateInput(date) + 'T' + formatTimeInput(date);
    }

    window.FoxDeskTicketDetailRuntime = {
        config: config,
        ticketId: ticketId,
        csrfToken: csrfToken,
        t: t,
        escapeHtml: escapeHtml,
        showToast: showToast,
        showUndoToast: showUndoToast,
        fadeRemove: fadeRemove,
        ready: ready,
        fillTemplate: fillTemplate,
        getIcon: getIcon,
        formatFileSize: formatFileSize,
        fileIconName: fileIconName,
        pad2: pad2,
        formatDateInput: formatDateInput,
        formatTimeInput: formatTimeInput,
        formatDateTimeLocal: formatDateTimeLocal,
        initUploadPreview: initUploadPreview,
        initShareCopy: initShareCopy
    };
})(window, document);
