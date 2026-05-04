<?php
/**
 * Tasks Kanban View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filter; GET params sanitized.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

// Helper function for user avatar (prefixed for Plugin Check).
if ( ! function_exists( 'persian_oa_get_user_avatar_url' ) ) {
    function persian_oa_get_user_avatar_url( $user_id, $size = 32 ) {
        return get_avatar_url( $user_id, [ 'size' => $size ] );
    }
}

// $task_list_filter set by TaskController::renderList().
if ( ! isset( $task_list_filter ) || ! in_array( $task_list_filter, [ 'my_tasks', 'assigned_by_me' ], true ) ) {
    $task_list_filter = 'my_tasks';
}
?>

<div class="persian-oa-wrap">
    <!-- Header Section with Modern Filters -->
    <div class="persian-oa-header" style="padding: 24px; margin-bottom: 20px;">
        <div class="persian-oa-header-content" style="align-items: center;">
            <div class="persian-oa-title" style="margin: 0; font-size: 28px;">
                <span class="persian-oa-title-icon" style="width: 48px; height: 48px; font-size: 24px;"><?php echo wp_kses_post( UIHelper::getTitleIcon( '☑️' ) ); ?></span>
                مدیریت وظایف
            </div>
            
            <div class="persian-oa-filter-bar">
                <div class="persian-oa-search-box">
                    <input type="text" class="persian-oa-input" placeholder="جستجو در وظایف..." id="persian-oa-task-search">
                    <span class="dashicons dashicons-search"></span>
                </div>
                
                <div class="persian-oa-view-switcher-group">
                    <a href="<?php echo esc_url( add_query_arg( [ 'view' => 'list' ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ); ?>" class="persian-oa-btn-icon" title="نمای لیست">
                        <span class="dashicons dashicons-list-view"></span>
                    </a>
                    <a href="#" class="persian-oa-btn-icon active" title="نمای کانبان">
                        <span class="dashicons dashicons-columns"></span>
                    </a>
                </div>
                
                <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'new' ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ); ?>" class="persian-oa-btn persian-oa-btn-primary">
                    <span class="dashicons dashicons-plus-alt2"></span> وظیفه جدید
                </a>
            </div>
        </div>
        
        <!-- Filter Tabs -->
        <div class="persian-oa-tabs" style="margin-top: 20px; box-shadow: none; border: 1px solid var(--persian-oa-gray-200); padding: 4px;">
            <a href="<?php echo esc_url( add_query_arg( [ 'filter' => 'my_tasks' ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ); ?>" class="persian-oa-tab <?php echo esc_attr( ( $task_list_filter === 'my_tasks' ) ? 'active' : '' ); ?>">
                📥 وظایف من
            </a>
            <a href="<?php echo esc_url( add_query_arg( [ 'filter' => 'assigned_by_me' ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ); ?>" class="persian-oa-tab <?php echo esc_attr( ( $task_list_filter === 'assigned_by_me' ) ? 'active' : '' ); ?>">
                📤 محول شده توسط من
            </a>
        </div>
    </div>

    <!-- Modern Kanban Board -->
    <div class="persian-oa-kanban-board-wrapper">
        <div class="persian-oa-kanban-board">
            <?php
            $statuses = [
                'todo' => ['label' => 'برای انجام', 'color' => 'var(--persian-oa-gray-500)', 'bg' => 'var(--persian-oa-gray-100)', 'border' => 'var(--persian-oa-gray-300)'],
                'in_progress' => ['label' => 'در حال انجام', 'color' => 'var(--persian-oa-info)', 'bg' => '#eff6ff', 'border' => '#bfdbfe'],
                'review' => ['label' => 'در حال بررسی', 'color' => 'var(--persian-oa-warning)', 'bg' => '#fffbeb', 'border' => '#fde68a'],
                'completed' => ['label' => 'تکمیل شده', 'color' => 'var(--persian-oa-success)', 'bg' => '#ecfdf5', 'border' => '#a7f3d0']
            ];
            
            foreach ($statuses as $key => $status): 
                $columnTasks = array_filter($tasks, function($t) use ($key) { return $t->getStatus() === $key; });
            ?>
                <div class="persian-oa-kanban-column" data-status="<?php echo esc_attr( $key ); ?>" style="background: <?php echo esc_attr( $status['bg'] ); ?>; border-top: 4px solid <?php echo esc_attr( $status['color'] ); ?>;">
                    <div class="persian-oa-kanban-header">
                        <h3 style="color: <?php echo esc_attr( $status['color'] ); ?>">
                            <?php echo esc_html( $status['label'] ); ?>
                            <span class="persian-oa-badge" style="background: white; color: <?php echo esc_attr( $status['color'] ); ?>; margin-right: auto; box-shadow: none; border: 1px solid <?php echo esc_attr( $status['border'] ); ?>"><?php echo esc_html( (string) count( $columnTasks ) ); ?></span>
                        </h3>
                    </div>
                    
                    <div class="persian-oa-kanban-body custom-scrollbar">
                        <?php foreach ($columnTasks as $task): 
                            $priorityColors = [
                                'low' => ['bg' => '#def7ec', 'text' => '#03543f', 'label' => 'پایین'],
                                'medium' => ['bg' => '#fef3c7', 'text' => '#92400e', 'label' => 'متوسط'],
                                'high' => ['bg' => '#fee2e2', 'text' => '#991b1b', 'label' => 'بالا'],
                                'urgent' => ['bg' => '#7f1d1d', 'text' => '#ffffff', 'label' => 'فوری']
                            ];
                            $pColor = $priorityColors[$task->getPriority()] ?? $priorityColors['medium'];
                        ?>
                            <div class="persian-oa-kanban-card persian-oa-card" draggable="true" data-id="<?php echo esc_attr( (string) $task->getId() ); ?>">
                                <div class="persian-oa-card-badges">
                                    <span class="persian-oa-badge" style="background: <?php echo esc_attr( $pColor['bg'] ); ?>; color: <?php echo esc_attr( $pColor['text'] ); ?>; font-size: 10px; padding: 2px 8px;">
                                        <?php echo esc_html( $pColor['label'] ); ?>
                                    </span>
                                </div>
                                
                                <h4 class="persian-oa-card-title">
                                    <a href="<?php echo esc_url( add_query_arg( [ 'action' => 'view', 'id' => $task->getId() ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ); ?>">
                                        <?php echo esc_html($task->getTitle()); ?>
                                    </a>
                                </h4>
                                
                                <div class="persian-oa-card-footer">
                                    <div class="persian-oa-card-meta">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html( $task->getDeadline() ? JalaliDate::format( $task->getDeadline(), 'j F' ) : 'بدون مهلت' ); ?>
                                    </div>
                                    
                                    <div class="persian-oa-avatars">
                                        <?php $assignedToUser = get_userdata($task->getAssignedTo()); ?>
                                        <img src="<?php echo esc_url( persian_oa_get_user_avatar_url( $task->getAssignedTo() ) ); ?>" 
                                             title="مسئول: <?php echo esc_attr( $assignedToUser ? $assignedToUser->display_name : 'نامشخص' ); ?>"
                                             alt="Avatar">
                                    </div>
                                </div>
                                
                                <?php if ($task->getProgress() > 0): ?>
                                    <div style="height: 3px; background: #f3f4f6; margin-top: 8px; border-radius: 2px; overflow: hidden;">
                                        <div style="width: <?php echo esc_attr( (string) $task->getProgress() ); ?>%; background: var(--persian-oa-primary); height: 100%;"></div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Quick Add Button -->
                    <div style="margin-top: 12px;">
                        <button class="persian-oa-btn-ghost" onclick="window.location.href='<?php echo esc_js( esc_url( add_query_arg( [ 'action' => 'new', 'status' => $key ], admin_url( 'admin.php?page=persian-oa-tasks' ) ) ) ); ?>'">
                            <span class="dashicons dashicons-plus"></span> افزودن سریع
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php
wp_add_inline_script('persian-oa-admin', "
jQuery(document).ready(function($) {
    // Simple client-side search
    $('#persian-oa-task-search').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.persian-oa-kanban-card').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Drag and Drop Logic
    let draggedItem = null;

    $('.persian-oa-kanban-card').on('dragstart', function(e) {
        draggedItem = this;
        setTimeout(() => {
            $(this).addClass('dragging');
        }, 0);
    });

    $('.persian-oa-kanban-card').on('dragend', function() {
        $(this).removeClass('dragging');
        draggedItem = null;
    });

    $('.persian-oa-kanban-column').on('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    });

    $('.persian-oa-kanban-column').on('dragleave', function(e) {
        $(this).removeClass('drag-over');
    });

    $('.persian-oa-kanban-column').on('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('drag-over');
        
        if (draggedItem) {
            const newStatus = $(this).data('status');
            const taskId = $(draggedItem).data('id');
            const body = $(this).find('.persian-oa-kanban-body');
            
            // Move the card in DOM
            body.append(draggedItem);
            
            // Update status via AJAX
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'persian_oa_task_update_status',
                    task_id: taskId,
                    status: newStatus,
                    nonce: '" . esc_js( wp_create_nonce( 'persian_oa_task_nonce' ) ) . "'
                },
                success: function(response) {
                    if (response.success) {
                        // Optional: Show toast or feedback
                        console.log('Status updated');
                    } else {
                        alert('خطا در بروزرسانی وضعیت: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('خطا در ارتباط با سرور');
                }
            });
        }
    });
});
");
?>
