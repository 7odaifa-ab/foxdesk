<?php
// Self-hosted report partial. Data is prepared by report_admin_page_context().
?>
            <!-- Work Log Tab - Simple inline edit UI -->
            <?php
            // Group entries by day
            $entries_by_day = [];
            $day_totals = [];
            foreach ($entries as $entry) {
                $day_key = date('Y-m-d', strtotime($entry['started_at']));
                if (!isset($entries_by_day[$day_key])) {
                    $entries_by_day[$day_key] = [];
                    $day_totals[$day_key] = 0;
                }
                $entries_by_day[$day_key][] = $entry;
                $day_totals[$day_key] += $entry['actual_minutes'];
            }

            // Helper to get day label
            function get_day_label($date_str) {
                $date = new DateTime($date_str);
                $today = new DateTime('today');
                $yesterday = new DateTime('yesterday');

                if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
                    return t('Today');
                } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
                    return t('Yesterday');
                } else {
                    return $date->format('d.m.Y');
                }
            }
            ?>
            <?php if (empty($entries)): ?>
                <!-- Empty State -->
                <div class="worklog worklog--empty">
                    <?php echo get_icon('clock', 'worklog__empty-icon'); ?>
                    <p class="worklog__empty-text"><?php echo e(t('No time entries yet.')); ?></p>
                </div>
            <?php else: ?>
                <div class="worklog">
                    <!-- Sticky Column Headers -->
                    <div class="worklog__header">
                        <div><?php echo e(t('Ticket')); ?></div>
                        <div><?php echo e(t('Subject')); ?></div>
                        <div><?php echo e(t('Company')); ?></div>
                        <div><?php echo e(t('User')); ?></div>
                        <?php if (is_admin()): ?><div class="text-center">$</div><?php endif; ?>
                        <div class="text-center"><?php echo e(t('Time')); ?></div>
                        <div class="text-right"><?php echo e(t('Duration')); ?></div>
                        <?php if (is_admin()): ?><div></div><?php endif; ?>
                    </div>

                    <?php foreach ($entries_by_day as $day_key => $day_entries): ?>
                        <div class="worklog__day-group">
                            <!-- Day Header -->
                            <div class="worklog__day-header">
                                <span><?php echo get_day_label($day_key); ?></span>
                                <span class="worklog__day-total">
                                    <?php echo e(t('Total')); ?>: <strong><?php echo e(format_duration_minutes($day_totals[$day_key])); ?></strong>
                                </span>
                            </div>

                            <!-- Day Entries -->
                            <div class="worklog__entries">
                                <?php foreach ($day_entries as $entry): ?>
                                    <?php $is_running = empty($entry['ended_at']); ?>
                                    <div class="worklog__row <?php echo $is_running ? 'worklog__row--running' : ''; ?>" data-entry-id="<?php echo $entry['id']; ?>">
                                        <!-- Ticket ID -->
                                        <div class="worklog__cell worklog__cell--ticket">
                                            <a href="<?php echo url('ticket', ['id' => $entry['ticket_id']]); ?>">
                                                <?php echo e(get_ticket_code($entry['ticket_id'])); ?>
                                            </a>
                                        </div>

                                        <!-- Title -->
                                        <div class="worklog__cell worklog__cell--title" title="<?php echo e($entry['ticket_title']); ?>">
                                            <a href="<?php echo url('ticket', ['id' => $entry['ticket_id']]); ?>">
                                                <?php echo e($entry['ticket_title']); ?>
                                            </a>
                                            <?php if ($tags_supported): ?>
                                                <?php $entry_tags = function_exists('get_ticket_tags_array') ? get_ticket_tags_array($entry['ticket_tags'] ?? '') : []; ?>
                                                <?php if (!empty($entry_tags)): ?>
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        <?php foreach (array_slice($entry_tags, 0, 4) as $tag): ?>
                                                            <span class="inline-flex items-center px-1 py-0.5 rounded tag-badge text-xs">#<?php echo e($tag); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Client -->
                                        <div class="worklog__cell worklog__cell--client" title="<?php echo e($entry['organization_name'] ?: '-'); ?>">
                                            <?php if ($entry['organization_name']): ?>
                                                <span class="worklog__client-dot"></span><?php echo e($entry['organization_name']); ?>
                                            <?php else: ?>
                                                <span class="report-empty-value">—</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- User -->
                                        <div class="worklog__cell worklog__cell--user" title="<?php echo e(trim($entry['first_name'] . ' ' . $entry['last_name'])); ?>">
                                            <?php echo e(trim($entry['first_name'] . ' ' . $entry['last_name'])); ?>
                                        </div>

                                        <!-- Billable -->
                                        <div class="worklog__cell worklog__cell--billable">
                                            <?php if (is_admin()): ?>
                                            <form method="post" class="inline">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="entry_id" value="<?php echo $entry['id']; ?>">
                                                <input type="hidden" name="is_billable" value="<?php echo $entry['is_billable'] ? '0' : '1'; ?>">
                                                <button type="submit" name="set_billable"
                                                    class="worklog__badge <?php echo $entry['is_billable'] ? 'worklog__badge--billable' : 'worklog__badge--non-billable'; ?>"
                                                    title="<?php echo $entry['is_billable'] ? t('Billable') : t('Non-billable'); ?>">
                                                    <?php echo get_icon('dollar-sign', 'w-4 h-4'); ?>
                                                </button>
                                            </form>
                                            <?php else: ?>
                                            <span class="worklog__badge <?php echo $entry['is_billable'] ? 'worklog__badge--billable' : 'worklog__badge--non-billable'; ?>"
                                                title="<?php echo $entry['is_billable'] ? t('Billable') : t('Non-billable'); ?>">
                                                <?php echo get_icon('dollar-sign', 'w-4 h-4'); ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Time Range -->
                                        <div class="worklog__cell worklog__cell--time">
                                            <?php if (!$is_running): ?>
                                                <?php if (is_admin()): ?>
                                                <div class="worklog__time-form"
                                                     data-entry-id="<?php echo $entry['id']; ?>"
                                                     data-entry-date="<?php echo date('Y-m-d', strtotime($entry['started_at'])); ?>">
                                                    <input type="time" name="start_time"
                                                        value="<?php echo date('H:i', strtotime($entry['started_at'])); ?>"
                                                        class="worklog__time-input"
                                                        onchange="updateTimeInline(this)">
                                                    <span class="worklog__time-separator">–</span>
                                                    <input type="time" name="end_time"
                                                        value="<?php echo date('H:i', strtotime($entry['ended_at'])); ?>"
                                                        class="worklog__time-input"
                                                        onchange="updateTimeInline(this)">
                                                </div>
                                                <?php else: ?>
                                                <span class="text-sm">
                                                    <?php echo date('H:i', strtotime($entry['started_at'])); ?> – <?php echo date('H:i', strtotime($entry['ended_at'])); ?>
                                                </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="worklog__time-running">
                                                    <?php echo date('H:i', strtotime($entry['started_at'])); ?> – ...
                                                </span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Duration -->
                                        <div class="worklog__cell worklog__cell--duration <?php echo $is_running ? 'text-green-600' : ''; ?>">
                                            <?php echo e(format_duration_minutes($entry['actual_minutes'])); ?>
                                        </div>

                                        <!-- Actions -->
                                        <?php if (is_admin()): ?>
                                        <div class="worklog__cell worklog__cell--actions">
                                            <button type="button" class="worklog__delete-btn"
                                                data-report-delete-time data-entry-id="<?php echo (int) $entry['id']; ?>"
                                                title="<?php echo e(t('Delete')); ?>">
                                                <?php echo get_icon('trash', 'w-4 h-4'); ?>
                                            </button>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
