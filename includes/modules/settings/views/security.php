        <!-- Security Settings -->
        <?php
        require_once BASE_PATH . '/includes/totp.php';
        $settings = get_settings();
        $tfa_admin = ($settings['2fa_required_admin'] ?? '0') === '1';
        $tfa_agent = ($settings['2fa_required_agent'] ?? '0') === '1';
        $tfa_user = ($settings['2fa_required_user'] ?? '0') === '1';

        // Count users per role and their 2FA status
        $tfa_counts = [];
        foreach (['admin', 'agent', 'user'] as $_r) {
            $total = (int) (db_fetch_one("SELECT COUNT(*) as c FROM users WHERE role = ? AND deleted_at IS NULL", [$_r])['c'] ?? 0);
            $enabled = (int) (db_fetch_one("SELECT COUNT(*) as c FROM users WHERE role = ? AND totp_enabled = 1 AND deleted_at IS NULL", [$_r])['c'] ?? 0);
            $tfa_counts[$_r] = ['total' => $total, 'enabled' => $enabled, 'without' => $total - $enabled];
        }
        ?>
        <div class="card card-body">
            <h3 class="text-xs font-semibold uppercase tracking-wide mb-2 text-theme-muted">
                <?php echo e(t('Two-factor authentication')); ?>
            </h3>

            <p class="text-sm mb-4 text-theme-secondary">
                <?php echo e(t('Require users to set up an authenticator app (Google Authenticator, Authy, 1Password) before accessing the system.')); ?>
            </p>

            <form method="post" class="space-y-4" id="tfa-settings-form">
                <?php echo csrf_field(); ?>

                <div class="space-y-3">
                    <?php foreach (['admin' => t('Admins'), 'agent' => t('Agents'), 'user' => t('Users (clients)')] as $role_key => $role_label): ?>
                    <?php
                        $is_checked = ${'tfa_' . $role_key};
                        $cnt = $tfa_counts[$role_key];
                        $without = $cnt['without'];
                        $total = $cnt['total'];
                        $enabled = $cnt['enabled'];
                    ?>
                    <div class="rounded-lg p-3 transition-colors" style="border: 1px solid var(--border-light);" data-tfa-role="<?php echo $role_key; ?>">
                        <label class="flex items-center gap-3 text-sm cursor-pointer text-theme-primary">
                            <input type="checkbox" name="2fa_required_<?php echo $role_key; ?>" class="rounded tfa-checkbox"
                                data-role="<?php echo e($role_key); ?>"
                                data-without="<?php echo $without; ?>"
                                data-total="<?php echo $total; ?>"
                                <?php echo $is_checked ? 'checked' : ''; ?>>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium"><?php echo e(t('Require 2FA for')); ?> <?php echo e($role_label); ?></span>
                                    <span class="text-xs px-2 py-0.5 rounded-full" style="background: var(--surface-secondary); color: var(--text-muted);">
                                        <?php echo $enabled; ?>/<?php echo $total; ?> <?php echo e(t('enabled')); ?>
                                    </span>
                                </div>
                            </div>
                        </label>

                        <?php if ($is_checked && $without > 0): ?>
                        <!-- Currently enforced but some users don't have it -->
                        <div class="mt-2 rounded p-2 text-xs flex items-start gap-1.5"
                            style="background: var(--warning-bg, #fef3c7); color: var(--warning-text, #92400e); border: 1px solid var(--warning-border, #fde68a);">
                            <?php echo get_icon('exclamation-triangle', 'w-3.5 h-3.5 flex-shrink-0 mt-0.5'); ?>
                            <span><?php echo $without; ?> <?php echo e($without === 1 ? t('user is') : t('users are')); ?> <?php echo e(t('being forced to set up 2FA before they can use the system.')); ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- JS-driven impact warning (hidden by default, shown when toggling ON) -->
                        <div class="tfa-impact-warning mt-2 rounded p-2 text-xs items-start gap-1.5"
                            style="display: none; background: var(--warning-bg, #fef3c7); color: var(--warning-text, #92400e); border: 1px solid var(--warning-border, #fde68a);">
                            <?php echo get_icon('exclamation-triangle', 'w-3.5 h-3.5 flex-shrink-0 mt-0.5'); ?>
                            <span class="tfa-impact-text"></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- What happens info box -->
                <div class="rounded-lg p-3 text-xs space-y-1.5" style="background: var(--surface-secondary); color: var(--text-muted);">
                    <div class="font-medium mb-1 text-theme-secondary"><?php echo get_icon('info-circle', 'w-3.5 h-3.5 inline mr-1'); ?><?php echo e(t('How this works')); ?></div>
                    <div><?php echo e(t('• Users who haven\'t set up 2FA will be redirected to set it up on their next page load. They can\'t access any other page until setup is complete.')); ?></div>
                    <div><?php echo e(t('• Users need an authenticator app (Google Authenticator, Authy, 1Password) to scan a QR code.')); ?></div>
                    <div><?php echo e(t('• 8 one-time backup codes are provided in case the user loses their device.')); ?></div>
                    <div><?php echo e(t('• Remember-me logins and API tokens skip 2FA (trusted device).')); ?></div>
                    <div><?php echo e(t('• If you disable the requirement later, users who already set up 2FA keep it — only the forced setup is removed.')); ?></div>
                </div>

                <button type="submit" name="save_2fa_settings" class="btn btn-primary btn-sm">
                    <?php echo e(t('Save')); ?>
                </button>
            </form>
        </div>

        <script>
        (function() {
            var checkboxes = document.querySelectorAll('.tfa-checkbox');
            checkboxes.forEach(function(cb) {
                var initialState = cb.checked;
                cb.addEventListener('change', function() {
                    var container = cb.closest('[data-tfa-role]');
                    var warning = container.querySelector('.tfa-impact-warning');
                    var text = container.querySelector('.tfa-impact-text');
                    var without = parseInt(cb.dataset.without, 10);
                    var total = parseInt(cb.dataset.total, 10);

                    if (cb.checked && !initialState) {
                        // Turning ON — show what will happen
                        if (without > 0) {
                            text.textContent = without + ' of ' + total + (without === 1
                                ? <?php echo json_encode(' ' . t('user will be immediately forced to set up 2FA. They won\'t be able to use the system until they scan a QR code with their authenticator app.')); ?>
                                : <?php echo json_encode(' ' . t('users will be immediately forced to set up 2FA. They won\'t be able to use the system until they scan a QR code with their authenticator app.')); ?>);
                        } else {
                            text.textContent = <?php echo json_encode(t('All users in this role already have 2FA enabled. New users will be required to set it up on first login.')); ?>;
                        }
                        warning.style.display = 'flex';
                    } else if (!cb.checked && initialState) {
                        // Turning OFF — show what will happen
                        text.textContent = <?php echo json_encode(t('The forced setup requirement will be removed. Users who already have 2FA will keep it — it won\'t be disabled.')); ?>;
                        warning.style.display = 'flex';
                    } else {
                        // Back to initial state
                        warning.style.display = 'none';
                    }
                });
            });
        })();
        </script>
