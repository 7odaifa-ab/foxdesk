<?php
// Self-hosted report partial. Data is prepared by report_admin_page_context().
?>
<?php if ($tab === 'billing' || $tab === 'worklog'): ?>
    <div id="entryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
        <div class="rounded-xl shadow-xl max-w-lg w-full mx-4 p-4" style="background: var(--bg-primary);">
            <h3 class="font-semibold mb-4 text-theme-primary"><?php echo e(t('Edit time entry')); ?></h3>
            <form method="post" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="entry_id" id="edit_entry_id">

                <div>
                    <label class="block text-sm font-medium mb-1 text-theme-secondary"><?php echo e(t('Ticket ID')); ?></label>
                    <input type="text" name="ticket_id" id="edit_ticket_id" class="form-input">
                    <p class="text-xs mt-1 text-theme-muted"><?php echo e(t('Ticket code (e.g., TK-0003)')); ?></p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1 text-theme-secondary"><?php echo e(t('Ticket title')); ?></label>
                    <input type="text" name="ticket_title" id="edit_ticket_title" class="form-input">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label
                            class="block text-sm font-medium" class="mb-1 text-theme-secondary"><?php echo e(t('Start time')); ?></label>
                        <input type="datetime-local" name="started_at" id="edit_started_at" class="form-input" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium" class="mb-1 text-theme-secondary"><?php echo e(t('End time')); ?></label>
                        <input type="datetime-local" name="ended_at" id="edit_ended_at" class="form-input" required>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" name="update_entry" class="btn btn-primary">
                        <?php echo e(t('Save changes')); ?>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeEntryModal()">
                        <?php echo e(t('Cancel')); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
