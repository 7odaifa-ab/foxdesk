            <?php
            $can_drag = is_agent() || is_admin();
            $main_column_count = max(1, count($kanban_main_statuses));
            $center_wide_board = $main_column_count <= 2;
            $fill_wide_board = $main_column_count >= 3 && $main_column_count <= 4;
            $main_board_classes = 'kanban-board';
            if ($center_wide_board) {
                $main_board_classes .= ' kanban-board--centered';
            }
            if ($fill_wide_board) {
                $main_board_classes .= ' kanban-board--fill';
            }
            $main_board_style = ($center_wide_board || $fill_wide_board)
                ? '--kanban-column-count: ' . $main_column_count . ';'
                : '';
            ?>
            <div class="<?php echo e($main_board_classes); ?>"<?php echo $main_board_style !== '' ? ' style="' . e($main_board_style) . '"' : ''; ?> data-kanban-scope="main">
                <?php foreach ($kanban_main_statuses as $status): ?>
                    <?php
                    $status_key = (int) ($status['id'] ?? 0);
                    $status_tickets = $kanban_main_tickets_by_status[$status_key] ?? [];
                    ?>
                    <div class="kanban-column"
                         data-status-id="<?php echo $status_key; ?>"
                         data-is-closed="<?php echo !empty($status['is_closed']) ? '1' : '0'; ?>"
                         data-kanban-scope="main">
                        <div class="kanban-column-header">
                            <span class="kanban-dot" style="background: <?php echo e($status['color']); ?>;"></span>
                            <span class="kanban-status-name"><?php echo e($status['name']); ?></span>
                            <span class="kanban-count"><?php echo count($status_tickets); ?></span>
                        </div>
                        <div class="kanban-cards"
                             data-status-id="<?php echo $status_key; ?>"
                             data-kanban-scope="main">
                            <?php foreach ($status_tickets as $ticket):
                                $priority_color = $ticket['priority_color'] ?? '#94a3b8';
                                $is_overdue = is_due_date_overdue($ticket['due_date'] ?? null, !empty($ticket['is_closed']));
                                $assignee_label = '';
                                if (!empty($ticket['assignee_first_name'])) {
                                    $assignee_label = $ticket['assignee_first_name'] . ' ' . mb_substr($ticket['assignee_last_name'] ?? '', 0, 1) . '.';
                                }
                            ?>
                                <div class="kanban-card"
                                     <?php if ($can_drag): ?>draggable="true"<?php endif; ?>
                                     data-ticket-id="<?php echo (int) $ticket['id']; ?>"
                                     data-status-id="<?php echo (int) $ticket['status_id']; ?>"
                                     data-kanban-scope="main">
                                    <a href="<?php echo ticket_url($ticket); ?>" class="kanban-card-link">
                                        <div class="kanban-card-top">
                                            <span class="kanban-card-code"><?php echo e(get_ticket_code($ticket['id'])); ?></span>
                                            <?php if (!empty($ticket['due_date'])): ?>
                                                <span class="kanban-card-due<?php echo $is_overdue ? ' overdue' : ''; ?>">
                                                    <?php echo date('d.m.', strtotime($ticket['due_date'])); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="kanban-card-title" dir="auto"><?php echo e($ticket['title']); ?></div>
                                        <div class="kanban-card-meta">
                                            <?php if (!empty($ticket['priority_name'])): ?>
                                                <span class="kanban-card-priority" style="background: <?php echo e($priority_color); ?>20; color: <?php echo e($priority_color); ?>;">
                                                    <?php echo e($ticket['priority_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($ticket['attachment_count']) && $ticket['attachment_count'] > 0): ?>
                                                <span class="kanban-card-icon" title="<?php echo e(t('Attachments')); ?>">
                                                    <?php echo get_icon('paperclip', 'w-3 h-3'); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($assignee_label): ?>
                                                <span class="kanban-card-assignee"><?php echo e($assignee_label); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </a>
                                    <?php if ($can_drag): ?>
                                        <select class="kanban-mobile-status" data-ticket-id="<?php echo (int) $ticket['id']; ?>" aria-label="<?php echo e(t('Move to')); ?>">
                                            <?php foreach ($statuses as $opt_status): ?>
                                                <option value="<?php echo (int) $opt_status['id']; ?>"
                                                        data-is-closed="<?php echo !empty($opt_status['is_closed']) ? '1' : '0'; ?>"
                                                        <?php echo (int) $opt_status['id'] === (int) $ticket['status_id'] ? 'selected' : ''; ?>>
                                                    <?php echo e($opt_status['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($kanban_archived_closed_count > 0): ?>
                <button type="button"
                        class="kanban-closed-toggle"
                        aria-expanded="false"
                        onclick="var closedBoard = document.getElementById('closed-kanban-board'); closedBoard.classList.toggle('hidden'); this.setAttribute('aria-expanded', closedBoard.classList.contains('hidden') ? 'false' : 'true');">
                    <span><?php echo e(t('Closed')); ?> (<span id="kanban-closed-count"><?php echo (int) $kanban_archived_closed_count; ?></span>)</span>
                    <span><?php echo get_icon('chevron-down', 'w-4 h-4'); ?></span>
                </button>
                <div id="closed-kanban-board" class="hidden">
                    <div class="kanban-board kanban-board--closed" data-kanban-scope="archived">
                        <?php foreach ($kanban_archived_closed_statuses as $status): ?>
                            <?php
                            $status_key = (int) ($status['id'] ?? 0);
                            $status_tickets = $kanban_archived_closed_tickets_by_status[$status_key] ?? [];
                            ?>
                            <div class="kanban-column"
                                 data-status-id="<?php echo $status_key; ?>"
                                 data-is-closed="1"
                                 data-kanban-scope="archived">
                                <div class="kanban-column-header">
                                    <span class="kanban-dot" style="background: <?php echo e($status['color']); ?>;"></span>
                                    <span class="kanban-status-name"><?php echo e($status['name']); ?></span>
                                    <span class="kanban-count"><?php echo count($status_tickets); ?></span>
                                </div>
                                <div class="kanban-cards"
                                     data-status-id="<?php echo $status_key; ?>"
                                     data-kanban-scope="archived">
                                    <?php foreach ($status_tickets as $ticket):
                                        $priority_color = $ticket['priority_color'] ?? '#94a3b8';
                                        $is_overdue = is_due_date_overdue($ticket['due_date'] ?? null, !empty($ticket['is_closed']));
                                        $assignee_label = '';
                                        if (!empty($ticket['assignee_first_name'])) {
                                            $assignee_label = $ticket['assignee_first_name'] . ' ' . mb_substr($ticket['assignee_last_name'] ?? '', 0, 1) . '.';
                                        }
                                    ?>
                                        <div class="kanban-card"
                                             <?php if ($can_drag): ?>draggable="true"<?php endif; ?>
                                             data-ticket-id="<?php echo (int) $ticket['id']; ?>"
                                             data-status-id="<?php echo (int) $ticket['status_id']; ?>"
                                             data-kanban-scope="archived">
                                            <a href="<?php echo ticket_url($ticket); ?>" class="kanban-card-link">
                                                <div class="kanban-card-top">
                                                    <span class="kanban-card-code"><?php echo e(get_ticket_code($ticket['id'])); ?></span>
                                                    <?php if (!empty($ticket['due_date'])): ?>
                                                        <span class="kanban-card-due<?php echo $is_overdue ? ' overdue' : ''; ?>">
                                                            <?php echo date('d.m.', strtotime($ticket['due_date'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="kanban-card-title" dir="auto"><?php echo e($ticket['title']); ?></div>
                                                <div class="kanban-card-meta">
                                                    <?php if (!empty($ticket['priority_name'])): ?>
                                                        <span class="kanban-card-priority" style="background: <?php echo e($priority_color); ?>20; color: <?php echo e($priority_color); ?>;">
                                                            <?php echo e($ticket['priority_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($ticket['attachment_count']) && $ticket['attachment_count'] > 0): ?>
                                                        <span class="kanban-card-icon" title="<?php echo e(t('Attachments')); ?>">
                                                            <?php echo get_icon('paperclip', 'w-3 h-3'); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($assignee_label): ?>
                                                        <span class="kanban-card-assignee"><?php echo e($assignee_label); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </a>
                                            <?php if ($can_drag): ?>
                                                <select class="kanban-mobile-status" data-ticket-id="<?php echo (int) $ticket['id']; ?>" aria-label="<?php echo e(t('Move to')); ?>">
                                                    <?php foreach ($statuses as $opt_status): ?>
                                                        <option value="<?php echo (int) $opt_status['id']; ?>"
                                                                data-is-closed="<?php echo !empty($opt_status['is_closed']) ? '1' : '0'; ?>"
                                                                <?php echo (int) $opt_status['id'] === (int) $ticket['status_id'] ? 'selected' : ''; ?>>
                                                            <?php echo e($opt_status['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
