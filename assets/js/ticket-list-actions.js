(function() {
    'use strict';

    var core = window.FoxDeskTicketListCore || {};
    var label = core.label;
    var csrfToken = core.csrfToken;
    var toast = core.toast;
    var errorToast = core.errorToast;
    var apiCall = core.apiCall;

    function bindNewTicketRow() {
        var row = document.getElementById('new-ticket-row');
        var button = document.getElementById('new-ticket-submit-btn');
        var subject = document.getElementById('new-ticket-subject');
        if (!row || !button || !subject) return;

        function value(id) {
            var element = document.getElementById(id);
            return element ? element.value : '';
        }

        function isHidden() {
            return row.classList.contains('hidden') || row.style.display === 'none';
        }

        window.toggleNewTicketRow = function(force) {
            var show = typeof force === 'boolean' ? force : isHidden();
            row.classList.toggle('hidden', !show);
            row.style.display = show ? '' : 'none';
            var toggleButton = document.getElementById('quick-add-toggle-btn');
            if (toggleButton) {
                toggleButton.classList.toggle('is-active', show);
            }
            if (show) {
                setTimeout(function() {
                    subject.focus();
                }, 50);
            }
        };

        var submitting = false;
        function submit() {
            if (submitting) return;
            var title = subject.value.trim();
            if (!title) {
                subject.focus();
                return;
            }

            var minutes = parseInt(value('new-ticket-minutes'), 10);
            if (!isNaN(minutes) && (minutes < 0 || minutes > 1440)) {
                errorToast(label('durationBounds', 'Duration must be between 1 and 1440 minutes.'));
                return;
            }

            submitting = true;
            button.disabled = true;
            apiCall('quick-create-ticket', null, {
                title: title,
                status_id: value('new-ticket-status'),
                priority_id: value('new-ticket-priority'),
                due_date: value('new-ticket-due'),
                organization_id: value('new-ticket-company'),
                assignee_id: value('new-ticket-assignee'),
                type: value('new-ticket-type')
            }).then(function(result) {
                if (!result.success) {
                    submitting = false;
                    button.disabled = false;
                    errorToast(result.error);
                    return;
                }

                var createdMessage = result.message || label('ticketCreated', 'Ticket created.');
                if (!isNaN(minutes) && minutes > 0 && result.ticket_id) {
                    apiCall('quick-log-time', result.ticket_id, { duration_minutes: minutes })
                        .then(function() {
                            toast(createdMessage, 'success');
                            location.reload();
                        })
                        .catch(function() {
                            toast(createdMessage, 'success');
                            location.reload();
                        });
                    return;
                }

                toast(createdMessage, 'success');
                location.reload();
            }).catch(function() {
                submitting = false;
                button.disabled = false;
                errorToast();
            });
        }

        button.addEventListener('click', submit);
        [subject, document.getElementById('new-ticket-minutes')].forEach(function(element) {
            if (!element) return;
            element.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    submit();
                } else if (event.key === 'Escape') {
                    window.toggleNewTicketRow(false);
                }
            });
        });
    }

    function bindInlineLogTime() {
        var activeChips = null;
        var activeButton = null;
        var customPopover = null;

        function closeAll() {
            if (activeChips) {
                if (activeChips._reposition) {
                    window.removeEventListener('resize', activeChips._reposition);
                    window.removeEventListener('scroll', activeChips._reposition, true);
                }
                activeChips.remove();
                activeChips = null;
            }
            if (customPopover) {
                customPopover.remove();
                customPopover = null;
            }
            activeButton = null;
            document.removeEventListener('click', onOutsideClick, true);
            document.removeEventListener('keydown', onEscape);
        }

        function positionChips(chips, button) {
            var rect = button.getBoundingClientRect();
            var viewportWidth = document.documentElement.clientWidth;
            var viewportHeight = document.documentElement.clientHeight;
            var chipsWidth = chips.offsetWidth || 240;
            var chipsHeight = chips.offsetHeight || 28;
            var left = rect.left - chipsWidth - 8;
            if (left < 8) left = 8;
            if (left + chipsWidth > viewportWidth - 8) left = viewportWidth - chipsWidth - 8;
            var top = rect.top + (rect.height - chipsHeight) / 2;
            if (top < 8) top = 8;
            if (top + chipsHeight > viewportHeight - 8) top = viewportHeight - chipsHeight - 8;
            chips.style.left = left + 'px';
            chips.style.top = top + 'px';
        }

        function onOutsideClick(event) {
            if (event.target.closest('.js-inline-log-time') ||
                event.target.closest('.ilt-chips') ||
                event.target.closest('.ilt-custom')) {
                return;
            }
            closeAll();
        }

        function onEscape(event) {
            if (event.key === 'Escape') closeAll();
        }

        function saveDuration(ticketId, minutes, note) {
            var body = new URLSearchParams();
            body.append('ticket_id', ticketId);
            body.append('duration_minutes', String(minutes));
            if (note) body.append('note', note);

            return fetch('index.php?page=api&action=quick-log-time', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken(),
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: body.toString()
            }).then(function(response) {
                return response.json();
            });
        }

        function openCustom(button) {
            if (activeChips) {
                activeChips.remove();
            }
            activeChips = null;

            var template = document.getElementById('inline-log-time-custom-tpl');
            if (!template) return;
            var fragment = template.content.cloneNode(true);
            var popover = fragment.querySelector('.ilt-custom');
            document.body.appendChild(popover);
            customPopover = popover;

            var rect = button.getBoundingClientRect();
            var viewportWidth = document.documentElement.clientWidth;
            var viewportHeight = document.documentElement.clientHeight;
            var popoverWidth = 240;
            var popoverHeight = 140;
            var left = Math.min(rect.right - popoverWidth, viewportWidth - popoverWidth - 8);
            if (left < 8) left = 8;
            var top = rect.bottom + 6;
            if (top + popoverHeight > viewportHeight - 8) top = rect.top - popoverHeight - 6;
            if (top < 8) top = 8;
            popover.style.left = left + 'px';
            popover.style.top = top + 'px';

            var duration = popover.querySelector('.ilt-duration');
            var note = popover.querySelector('.ilt-note');
            var save = popover.querySelector('.ilt-save');

            setTimeout(function() {
                duration.focus();
            }, 30);

            popover.querySelector('.ilt-cancel').addEventListener('click', function(event) {
                event.stopPropagation();
                closeAll();
            });

            save.addEventListener('click', function(event) {
                event.stopPropagation();
                var minutes = parseInt(duration.value, 10) || 0;
                if (minutes <= 0) {
                    duration.focus();
                    return;
                }
                save.disabled = true;
                save.textContent = '...';
                saveDuration(button.dataset.ticketId, minutes, note.value.trim())
                    .then(function(result) {
                        if (result.success) {
                            toast(result.message || label('saved', 'Saved'), 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 300);
                            return;
                        }
                        save.disabled = false;
                        save.textContent = label('save', 'Save');
                        errorToast(result.error);
                    })
                    .catch(function() {
                        save.disabled = false;
                        save.textContent = label('save', 'Save');
                        errorToast();
                    });
            });

            duration.addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    save.click();
                }
            });
        }

        function openChips(button) {
            closeAll();
            var template = document.getElementById('inline-log-time-chips-tpl');
            if (!template) return;
            var fragment = template.content.cloneNode(true);
            var chips = fragment.querySelector('.ilt-chips');
            chips.classList.add('ilt-chips--floating');
            document.body.appendChild(chips);
            positionChips(chips, button);
            activeChips = chips;
            activeButton = button;

            var onResize = function() {
                if (activeChips) {
                    positionChips(activeChips, button);
                }
            };
            window.addEventListener('resize', onResize);
            window.addEventListener('scroll', onResize, true);
            chips._reposition = onResize;

            chips.querySelectorAll('.ilt-chip[data-mins]').forEach(function(chip) {
                chip.addEventListener('click', function(event) {
                    event.stopPropagation();
                    var minutes = parseInt(chip.dataset.mins, 10) || 0;
                    if (!minutes) return;
                    chip.disabled = true;
                    chip.textContent = '...';
                    saveDuration(button.dataset.ticketId, minutes, '')
                        .then(function(result) {
                            if (result.success) {
                                toast(result.message || label('saved', 'Saved'), 'success');
                                setTimeout(function() {
                                    window.location.reload();
                                }, 300);
                                return;
                            }
                            chip.disabled = false;
                            chip.textContent = '+' + minutes;
                            errorToast(result.error);
                        })
                        .catch(function() {
                            chip.disabled = false;
                            chip.textContent = '+' + minutes;
                            errorToast();
                        });
                });
            });

            var customButton = chips.querySelector('.ilt-chip--custom');
            if (customButton) {
                customButton.addEventListener('click', function(event) {
                    event.stopPropagation();
                    openCustom(button);
                });
            }

            setTimeout(function() {
                document.addEventListener('click', onOutsideClick, true);
                document.addEventListener('keydown', onEscape);
            }, 0);
        }

        document.addEventListener('click', function(event) {
            var button = event.target.closest('.js-inline-log-time');
            if (!button) return;
            event.preventDefault();
            event.stopPropagation();
            if (activeButton === button) {
                closeAll();
                return;
            }
            openChips(button);
        });
    }


    document.addEventListener('DOMContentLoaded', function() {
        bindNewTicketRow();
        bindInlineLogTime();
    });
})();
