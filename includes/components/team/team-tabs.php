<?php
/**
 * Team management tab navigation.
 */
?>
<div class="admin-tabs mb-3">
    <a href="<?php echo url('admin', ['section' => 'users']); ?>"
        class="admin-tab <?php echo $tab === 'users' ? 'is-active' : ''; ?>">
        <?php echo get_icon('users', 'w-3.5 h-3.5'); ?><span><?php echo e(t('Users')); ?></span>
    </a>
    <a href="<?php echo url('admin', ['section' => 'users', 'tab' => 'ai_agents']); ?>"
        class="admin-tab <?php echo $tab === 'ai_agents' ? 'is-active' : ''; ?>">
        <?php echo get_icon('magic', 'w-3.5 h-3.5'); ?><span><?php echo e(t('AI agents')); ?></span>
    </a>
</div>
