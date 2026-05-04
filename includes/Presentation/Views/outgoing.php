<?php
/**
 * Outgoing letters list template (variables from OutgoingLetterController::renderList()).
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

// $outgoing_message, $letters, $total set by controller.

global $wpdb;
$table = $wpdb->prefix . 'persian_oa_correspondence';

if ( $outgoing_message === 'success' ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no user input.
    echo '<div class="persian-oa-card persian-oa-mb-4" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-right: 4px solid #10b981; animation: fadeInUp 0.6s;">';
    echo '<div style="padding: 20px;"><strong style="color: #065f46;">✅ عملیات موفق:</strong> <span style="color: #047857;">نامه صادره با موفقیت ثبت شد.</span></div>';
    echo '</div>';
} elseif ( $outgoing_message === 'deleted' ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no user input.
    echo '<div class="persian-oa-card persian-oa-mb-4" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-right: 4px solid #ef4444;">';
    echo '<div style="padding: 20px;"><strong style="color: #991b1b;">🗑️ نامه با موفقیت حذف شد.</strong></div>';
    echo '</div>';
}
?>

<div class="persian-oa-wrap">
    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📤' ) ); ?></span>
                    نامه‌های صادره
                </h1>
                <p class="persian-oa-subtitle">
                    مجموع <?php echo esc_html( number_format( $total ) ); ?> نامه صادره • 
                    <?php echo esc_html(JalaliDate::now('l، j F Y')); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <?php if (current_user_can('persian_oa_create_letter') || current_user_can('manage_options')) { ?>
                <a href="?page=persian-oa-outgoing&action=new" class="persian-oa-btn persian-oa-btn-primary">
                    ➕ نامه صادره جدید
                </a>
                <?php } ?>
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="window.print()">
                    🖨️ چاپ لیست
                </button>
            </div>
        </div>
    </div>

    <!-- Workflow Status Cards (table from $wpdb->prefix). -->
    <?php // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter ?>
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 32px;">
        <div style="background: linear-gradient(135deg, #e0e7ff, #c7d2fe); padding: 20px; border-radius: var(--persian-oa-radius-lg); box-shadow: var(--persian-oa-shadow-sm);">
            <div style="font-size: 14px; color: #4f46e5; font-weight: 600; margin-bottom: 8px;">📝 پیش‌نویس</div>
            <div style="font-size: 32px; font-weight: 800; color: #4f46e5;">
                <?php echo esc_html( (string) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type='outgoing' AND status='draft'" ) ); ?>
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #fef3c7, #fde68a); padding: 20px; border-radius: var(--persian-oa-radius-lg); box-shadow: var(--persian-oa-shadow-sm);">
            <div style="font-size: 14px; color: #92400e; font-weight: 600; margin-bottom: 8px;">⏳ در انتظار تایید</div>
            <div style="font-size: 32px; font-weight: 800; color: #92400e;">
                <?php echo esc_html( (string) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type='outgoing' AND status='pending'" ) ); ?>
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #d1fae5, #a7f3d0); padding: 20px; border-radius: var(--persian-oa-radius-lg); box-shadow: var(--persian-oa-shadow-sm);">
            <div style="font-size: 14px; color: #065f46; font-weight: 600; margin-bottom: 8px;">✅ ارسال شده</div>
            <div style="font-size: 32px; font-weight: 800; color: #065f46;">
                <?php echo esc_html( (string) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type='outgoing' AND status='approved'" ) ); ?>
            </div>
        </div>
        <div style="background: linear-gradient(135deg, #fee2e2, #fecaca); padding: 20px; border-radius: var(--persian-oa-radius-lg); box-shadow: var(--persian-oa-shadow-sm);">
            <div style="font-size: 14px; color: #991b1b; font-weight: 600; margin-bottom: 8px;">❌ رد شده</div>
            <div style="font-size: 32px; font-weight: 800; color: #991b1b;">
                <?php echo esc_html( (string) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE type='outgoing' AND status='rejected'" ) ); ?>
            </div>
        </div>
    </div>
    <?php // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter ?>

    <!-- Letters Grid -->
    <?php if (!empty($letters)) { ?>
        <div class="persian-oa-card">
            <div style="padding: 0;">
                <?php foreach ($letters as $letter) { 
                    $statusConfig = [
                        'draft' => ['label' => 'پیش‌نویس', 'class' => 'primary', 'icon' => '📝'],
                        'pending' => ['label' => 'در انتظار تایید', 'class' => 'warning', 'icon' => '⏳'],
                        'approved' => ['label' => 'ارسال شده', 'class' => 'success', 'icon' => '✅'],
                        'rejected' => ['label' => 'رد شده', 'class' => 'danger', 'icon' => '❌']
                    ];
                    $config = $statusConfig[$letter->status] ?? $statusConfig['draft'];
                ?>
                    <div style="padding: 28px; border-bottom: 1px solid var(--persian-oa-gray-200); transition: var(--persian-oa-transition); position: relative;">
                        <div style="position: absolute; right: -1px; top: 0; bottom: 0; width: 4px; background: linear-gradient(180deg,
                            <?php echo esc_attr( $letter->status === 'approved' ? '#10b981' : ( $letter->status === 'rejected' ? '#ef4444' : ( $letter->status === 'pending' ? '#f59e0b' : '#6366f1' ) ) ); ?>, transparent);"></div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: start;">
                            <div style="flex: 1;">
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <span style="font-size: 18px; font-weight: 800; color: var(--persian-oa-primary);">
                                        #<?php echo esc_html(JalaliDate::convertNumbers($letter->number)); ?>
                                    </span>
                                    <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr($config['class']); ?>">
                                        <?php echo esc_html($config['icon']); ?> <?php echo esc_html($config['label']); ?>
                                    </span>
                                </div>
                                
                                <h3 style="font-size: 20px; font-weight: 700; color: var(--persian-oa-gray-900); margin: 0 0 8px 0;">
                                    <?php echo esc_html($letter->subject); ?>
                                </h3>
                                
                                <div style="font-size: 14px; color: var(--persian-oa-gray-600);">
                                    <?php if (!empty($letter->recipient)): ?>
                                        📤 گیرنده: <strong><?php echo esc_html($letter->recipient); ?></strong> • 
                                    <?php endif; ?>
                                    📅 <?php echo esc_html(JalaliDate::format($letter->created_at, 'datetime')); ?> • 
                                    🕐 <?php echo esc_html(JalaliDate::timeAgo($letter->created_at)); ?>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                <a href="<?php echo esc_url(admin_url('admin.php?page=persian-oa-outgoing&action=view&id=' . absint($letter->id))); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 8px 16px; font-size: 13px;">👁️ مشاهده</a>
                                <?php if (($letter->status == 'draft' || $letter->status == 'rejected' || $letter->status == 'pending') && (current_user_can('persian_oa_edit_letter') || current_user_can('manage_options'))) { ?>
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=persian-oa-outgoing&action=edit&id=' . absint($letter->id))); ?>" class="persian-oa-btn persian-oa-btn-primary" style="padding: 8px 16px; font-size: 13px;">✏️ ویرایش</a>
                                <?php } ?>
                                <?php if ($letter->status == 'pending' && current_user_can('manage_options')) { ?>
                                    <button class="persian-oa-btn persian-oa-btn-success" style="padding: 8px 16px; font-size: 13px;">✅ تایید</button>
                                <?php } ?>
                                <?php if (current_user_can('persian_oa_edit_letter') || current_user_can('manage_options')) { ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=persian_oa_delete_outgoing_letter&id=' . absint($letter->id)), 'persian_oa_delete_outgoing_' . absint($letter->id))); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 8px 16px; font-size: 13px; color: #dc2626; border-color: #dc2626;" onclick="return confirm('آیا از حذف این نامه اطمینان دارید؟');">🗑️ حذف</a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } else { ?>
        <div class="persian-oa-card">
            <div style="padding: 80px; text-align: center;">
                <div style="font-size: 72px; margin-bottom: 24px; animation: float 3s ease-in-out infinite;">📤</div>
                <h3 style="font-size: 24px; font-weight: 700; color: var(--persian-oa-gray-900); margin-bottom: 12px;">
                    هیچ نامه صادره‌ای ثبت نشده
                </h3>
                <p style="font-size: 16px; color: var(--persian-oa-gray-600); margin-bottom: 32px;">
                    اولین نامه صادره خود را ثبت کنید
                </p>
                <a href="?page=persian-oa-outgoing&action=new" class="persian-oa-btn persian-oa-btn-primary persian-oa-btn-lg">
                    ➕ ثبت نامه صادره جدید
                </a>
            </div>
        </div>
    <?php } ?>
</div>
