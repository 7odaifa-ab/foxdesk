        <!-- Workflow Tab - Statuses, Priorities, Ticket Types -->
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

        <div class="workflow-grid">
            <?php foreach (admin_workflow_cards() as $workflow_card): ?>
                <?php render_admin_workflow_card($workflow_card); ?>
            <?php endforeach; ?>
        </div>
