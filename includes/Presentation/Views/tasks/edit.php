<?php
/**
 * Edit Task View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Infrastructure\Repository\TaskRepository;

$taskRepo = new TaskRepository();
$allTasks = $taskRepo->findAll(100);

// $task object is passed from Controller
?>

<div class="persian-oa-wrap">
    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '✏️' ) ); ?></span>
                    ویرایش وظیفه: <?php echo esc_html($task->getTitle()); ?>
                </h1>
            </div>
            <a href="<?php echo esc_url(admin_url('admin.php?page=persian-oa-tasks&action=view&id=' . $task->getId())); ?>" class="persian-oa-btn persian-oa-btn-outline">
                ← بازگشت
            </a>
        </div>
    </div>

    <div class="persian-oa-card">
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="padding: 32px;">
            <input type="hidden" name="action" value="persian_oa_edit_task">
            <input type="hidden" name="id" value="<?php echo esc_attr( (string) $task->getId() ); ?>">
            <?php wp_nonce_field('persian_oa_edit_task', 'persian_oa_task_nonce'); ?>
            
            <div class="persian-oa-form-grid">
                <!-- عنوان -->
                <div class="persian-oa-form-group" style="grid-column: span 2;">
                    <label class="persian-oa-label required">عنوان وظیفه</label>
                    <input type="text" name="title" class="persian-oa-input" required value="<?php echo esc_attr($task->getTitle()); ?>">
                </div>

                <!-- مسئول -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label required">مسئول انجام</label>
                    <select name="assigned_to" class="persian-oa-select" required>
                        <option value="">انتخاب مسئول انجام...</option>
                        <?php 
                        $users = get_users();
                        foreach ($users as $user): 
                        ?>
                            <option value="<?php echo esc_attr( (string) $user->ID ); ?>" <?php selected( $user->ID, $task->getAssignedTo() ); ?>>
                                <?php echo esc_html($user->display_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- وظیفه والد -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">زیرمجموعه وظیفه (والد)</label>
                    <select name="parent_id" class="persian-oa-select">
                        <option value="">بدون والد</option>
                        <?php foreach ($allTasks as $t): ?>
                            <?php if ($t->getId() == $task->getId()) continue; // Prevent self-parenting ?>
                            <option value="<?php echo esc_attr($t->getId()); ?>" <?php selected($t->getId(), $task->getParentId()); ?>>
                                <?php echo esc_html($t->getTitle()); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- تاریخ شروع -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">تاریخ شروع</label>
                    <div class="persian-oa-input-group">
                        <span class="persian-oa-input-icon dashicons dashicons-calendar-alt"></span>
                        <input type="text" id="start-date-jalali" name="start_date" class="persian-oa-input jalali-datepicker" 
                               readonly placeholder="انتخاب تاریخ" style="cursor: pointer; background-color: #ffffff;"
                               value="<?php echo esc_attr( $task->getStartDate() ? JalaliDate::toJalali( $task->getStartDate() ) : '' ); ?>">
                    </div>
                    <input type="hidden" id="start-date-gregorian" name="start_date_gregorian" value="<?php echo esc_attr($task->getStartDate()); ?>">
                </div>

                <!-- مهلت -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">مهلت انجام (ددلاین)</label>
                    <div class="persian-oa-input-group">
                        <span class="persian-oa-input-icon dashicons dashicons-calendar-alt"></span>
                        <input type="text" id="deadline-jalali" name="deadline" class="persian-oa-input jalali-datepicker" 
                               readonly placeholder="انتخاب تاریخ" style="cursor: pointer; background-color: #ffffff;"
                               value="<?php echo esc_attr( $task->getDeadline() ? JalaliDate::toJalali( $task->getDeadline() ) : '' ); ?>">
                    </div>
                    <input type="hidden" id="deadline-gregorian" name="deadline_gregorian" value="<?php echo esc_attr($task->getDeadline()); ?>">
                </div>

                <!-- تخمین زمان -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">تخمین زمان (ساعت)</label>
                    <input type="number" name="estimated_time" class="persian-oa-input" min="0" step="0.5" value="<?php echo esc_attr($task->getEstimatedTime()); ?>">
                </div>

                <!-- دسته‌بندی -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">دسته بندی / پروژه</label>
                    <input type="text" name="category" class="persian-oa-input" list="category-suggestions" value="<?php echo esc_attr($task->getCategory()); ?>">
                    <datalist id="category-suggestions">
                        <option value="عمومی">
                        <option value="توسعه نرم‌افزار">
                        <option value="اداری">
                        <option value="پشتیبانی">
                        <option value="مارکتینگ">
                    </datalist>
                </div>

                <!-- اولویت -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">اولویت</label>
                    <div class="persian-oa-priority-selector">
                        <label class="priority-option low">
                            <input type="radio" name="priority" value="low" <?php checked($task->getPriority(), 'low'); ?>>
                            <span class="badge">کم</span>
                        </label>
                        <label class="priority-option medium">
                            <input type="radio" name="priority" value="medium" <?php checked($task->getPriority(), 'medium'); ?>>
                            <span class="badge">متوسط</span>
                        </label>
                        <label class="priority-option high">
                            <input type="radio" name="priority" value="high" <?php checked($task->getPriority(), 'high'); ?>>
                            <span class="badge">زیاد</span>
                        </label>
                        <label class="priority-option urgent">
                            <input type="radio" name="priority" value="urgent" <?php checked($task->getPriority(), 'urgent'); ?>>
                            <span class="badge">فوری</span>
                        </label>
                    </div>
                </div>

                <!-- وضعیت -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">وضعیت</label>
                    <select name="status" class="persian-oa-select">
                        <option value="todo" <?php selected($task->getStatus(), 'todo'); ?>>برای انجام</option>
                        <option value="in_progress" <?php selected($task->getStatus(), 'in_progress'); ?>>در حال انجام</option>
                        <option value="review" <?php selected($task->getStatus(), 'review'); ?>>در حال بررسی</option>
                        <option value="completed" <?php selected($task->getStatus(), 'completed'); ?>>تکمیل شده</option>
                    </select>
                </div>
            </div>

            <!-- توضیحات -->
            <div class="persian-oa-form-group persian-oa-mt-4">
                <label class="persian-oa-label">توضیحات تکمیلی</label>
                <textarea name="description" class="persian-oa-textarea" rows="5"><?php echo esc_textarea($task->getDescription()); ?></textarea>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 24px;">
                <a href="<?php echo esc_url(admin_url('admin.php?page=persian-oa-tasks&action=view&id=' . $task->getId())); ?>" class="persian-oa-btn persian-oa-btn-outline">
                    انصراف
                </a>
                <button type="submit" class="persian-oa-btn persian-oa-btn-primary" style="min-width: 140px;">
                    💾 ذخیره تغییرات
                </button>
            </div>
        </form>
</div>
<?php
wp_add_inline_style('persian-oa-tasks', '
.persian-oa-input-group { position: relative; display: flex; align-items: center; }
.persian-oa-input-icon { position: absolute; right: 12px; color: #94a3b8; z-index: 1; }
.persian-oa-input-group .persian-oa-input { padding-right: 36px; }
.persian-oa-priority-selector { display: flex; gap: 8px; }
.priority-option input { display: none; }
.priority-option .badge { display: inline-block; padding: 6px 16px; border-radius: 6px; font-size: 13px; cursor: pointer; background: #f1f5f9; color: #64748b; border: 1px solid transparent; transition: all 0.2s; }
.priority-option input:checked + .badge { box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.priority-option.low input:checked + .badge { background: #eff6ff; color: #3b82f6; border-color: #bfdbfe; }
.priority-option.medium input:checked + .badge { background: #f0fdf4; color: #10b981; border-color: #bbf7d0; }
.priority-option.high input:checked + .badge { background: #fff7ed; color: #f59e0b; border-color: #fed7aa; }
.priority-option.urgent input:checked + .badge { background: #fef2f2; color: #ef4444; border-color: #fecaca; }
');

wp_add_inline_script('persian-oa-tasks', '
jQuery(document).ready(function($) {
    if (typeof SimplePersianDatePicker !== "undefined") {
        new SimplePersianDatePicker(
            document.getElementById("deadline-jalali"),
            document.getElementById("deadline-gregorian"),
            { defaultToday: false }
        );
        new SimplePersianDatePicker(
            document.getElementById("start-date-jalali"),
            document.getElementById("start-date-gregorian"),
            { defaultToday: false }
        );
    }
});
');
?>