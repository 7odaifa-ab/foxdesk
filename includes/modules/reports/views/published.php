<?php
// Self-hosted report partial. Data is prepared by report_admin_page_context().
?>
            <div class="card card-body space-y-4">
                <h3 class="font-semibold text-theme-primary"><?php echo e(t('Share link')); ?></h3>
                <form method="post" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium" class="mb-1 text-theme-secondary"><?php echo e(t('Company')); ?></label>
                            <select name="organization_id" class="form-select">
                                <?php foreach ($organizations as $org): ?>
                                    <option value="<?php echo $org['id']; ?>"><?php echo e($org['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-sm font-medium" class="mb-1 text-theme-secondary"><?php echo e(t('Expiry (optional)')); ?></label>
                            <input type="datetime-local" name="share_expires_at" class="form-input">
                        </div>
                    </div>
                    <button type="submit" name="create_report_share" class="btn btn-primary">
                        <?php echo e(t('Create share link')); ?>
                    </button>
                </form>

                <?php
                $share_org_id = (int) ($_GET['share_org_id'] ?? 0);
                if ($share_org_id <= 0 && !empty($organizations)) {
                    $share_org_id = (int) $organizations[0]['id'];
                }
                $active_share = $share_org_id ? get_active_report_share($share_org_id) : null;
                $share_token = null;
                if (!empty($_SESSION['report_share_token']) && (int) ($_SESSION['report_share_org_id'] ?? 0) === $share_org_id) {
                    $share_token = $_SESSION['report_share_token'];
                    unset($_SESSION['report_share_token'], $_SESSION['report_share_org_id']);
                }
                $share_url = $share_token ? get_report_share_url($share_token) : null;
                ?>

                <?php if ($share_url): ?>
                    <div class="border border-green-200 rounded-lg p-4 bg-theme-secondary">
                        <div class="text-sm text-green-600 mb-2"><?php echo e(t('Share link created.')); ?></div>
                        <input type="text" readonly class="form-input" value="<?php echo e($share_url); ?>" onclick="this.select()">
                    </div>
                <?php elseif ($active_share): ?>
                    <div class="border border-yellow-200 rounded-lg p-4 text-sm text-yellow-600 bg-theme-secondary">
                        <?php echo e(t('An active link exists but is hidden for security. Generate a new link to get a new URL.')); ?>
                    </div>
                    <form method="post">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="organization_id" value="<?php echo $share_org_id; ?>">
                        <button type="submit" name="revoke_report_share" class="btn btn-warning">
                            <?php echo e(t('Revoke share link')); ?>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="text-sm text-theme-muted"><?php echo e(t('No active share link exists yet.')); ?></div>
                <?php endif; ?>
            </div>
