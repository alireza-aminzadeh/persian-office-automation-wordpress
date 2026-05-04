<?php
/**
 * Incoming letters list template (variables from IncomingLetterController::renderList()).
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Common\Constants;

// $incoming_message, $letters, $total, $incoming_status_filter, $incoming_search_value, $incoming_priority_filter set by controller.
if ( ! isset( $incoming_priority_filter ) ) {
	$incoming_priority_filter = '';
}

if ( $incoming_message === 'success' ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static markup, no user input.
	echo '<div class="persian-oa-card persian-oa-mb-4" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-right: 4px solid #10b981; animation: fadeInUp 0.6s;">';
	echo '<div style="padding: 20px;"><strong style="color: #065f46;">✅ عملیات موفق:</strong> <span style="color: #047857;">نامه با موفقیت ذخیره شد.</span></div>';
	echo '</div>';
}
?>

<div class="persian-oa-wrap">
    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📥' ) ); ?></span>
                    نامه‌های وارده
                </h1>
                <p class="persian-oa-subtitle">
                    مجموع <?php echo esc_html( number_format( $total ) ); ?> نامه وارده در سیستم • 
                    تاریخ: <?php echo esc_html(JalaliDate::now('l، j F Y')); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <?php if (current_user_can('persian_oa_create_letter') || current_user_can('manage_options')) { ?>
                <a href="?page=persian-oa-incoming-letters&action=new" class="persian-oa-btn persian-oa-btn-primary">
                    ➕ نامه وارده جدید
                </a>
                <?php } ?>
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="window.print()">
                    🖨️ چاپ لیست
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="persian-oa-card persian-oa-mb-4" style="animation-delay: 0.1s;">
        <div style="padding: 24px;">
            <form method="get" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 16px; align-items: end;">
                <input type="hidden" name="page" value="persian-oa-incoming-letters">
                <?php wp_nonce_field( 'persian_oa_incoming_list', 'persian_oa_list_nonce', false ); ?>
                
                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: var(--persian-oa-gray-700); margin-bottom: 8px;">
                        🔍 جستجو
                    </label>
                    <input type="text" name="s" class="persian-oa-input" placeholder="موضوع یا شماره نامه..." value="<?php echo esc_attr( $incoming_search_value ); ?>">
                </div>

                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: var(--persian-oa-gray-700); margin-bottom: 8px;">
                        📊 وضعیت
                    </label>
                    <select name="status" class="persian-oa-select">
                        <option value="">همه</option>
                        <option value="draft" <?php selected( $incoming_status_filter, 'draft' ); ?>>پیش‌نویس</option>
                        <option value="pending" <?php selected( $incoming_status_filter, 'pending' ); ?>>در انتظار</option>
                        <option value="approved" <?php selected( $incoming_status_filter, 'approved' ); ?>>تایید شده</option>
                        <option value="rejected" <?php selected( $incoming_status_filter, 'rejected' ); ?>>رد شده</option>
                    </select>
                </div>

                <div>
                    <label style="display: block; font-size: 14px; font-weight: 600; color: var(--persian-oa-gray-700); margin-bottom: 8px;">
                        ⚡ اولویت
                    </label>
                    <select name="priority" class="persian-oa-select">
                        <option value="">همه</option>
                        <option value="low" <?php selected( $incoming_priority_filter, 'low' ); ?>>کم</option>
                        <option value="medium" <?php selected( $incoming_priority_filter, 'medium' ); ?>>متوسط</option>
                        <option value="high" <?php selected( $incoming_priority_filter, 'high' ); ?>>زیاد</option>
                        <option value="urgent" <?php selected( $incoming_priority_filter, 'urgent' ); ?>>فوری</option>
                    </select>
                </div>

                <button type="submit" class="persian-oa-btn persian-oa-btn-primary" style="margin-top: 29px;">
                    فیلتر
                </button>
            </form>
        </div>
    </div>

    <!-- Letters Grid -->
    <?php if (!empty($letters)) { ?>
        <div style="display: grid; gap: 20px;">
            <?php foreach ($letters as $letter) { 
                $statusColors = [
                    'pending' => 'warning',
                    'approved' => 'success',
                    'rejected' => 'danger',
                    'draft' => 'primary'
                ];
                $statusLabels = [
                    'pending' => 'در انتظار پاسخ',
                    'approved' => 'تایید و پاسخ داده شده',
                    'rejected' => 'رد شده',
                    'draft' => 'پیش‌نویس'
                ];
                $priorityIcons = [
                    'low' => '🟢',
                    'medium' => '🟡',
                    'high' => '🟠',
                    'urgent' => '🔴'
                ];
                $priorityLabels = [
                    'low' => 'کم',
                    'medium' => 'متوسط',
                    'high' => 'زیاد',
                    'urgent' => 'فوری'
                ];
            ?>
                <div class="persian-oa-card" style="animation-delay: 0.<?php echo esc_attr( (string) array_search( $letter, $letters, true ) ); ?>s;">
                    <div style="padding: 28px; position: relative;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 20px;">
                            <div>
                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                    <span style="font-size: 20px; font-weight: 800; color: var(--persian-oa-primary);">
                                        #<?php echo esc_html( JalaliDate::convertNumbers( $letter->number ) ); ?>
                                    </span>
                                    <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( $statusColors[ $letter->status ] ?? 'primary' ); ?>">
                                        <?php echo esc_html( $statusLabels[ $letter->status ] ?? 'نامشخص' ); ?>
                                    </span>
                                    <?php if (!empty($letter->priority)) { ?>
                                        <span class="persian-oa-badge persian-oa-badge-warning">
                                            <?php echo esc_html($priorityIcons[$letter->priority] ?? ''); ?> <?php echo esc_html($priorityLabels[$letter->priority] ?? $letter->priority); ?>
                                        </span>
                                    <?php } ?>
                                </div>
                                
                                <h3 style="font-size: 22px; font-weight: 700; color: var(--persian-oa-gray-900); margin: 0 0 12px 0;">
                                    <?php echo esc_html($letter->subject); ?>
                                </h3>
                                
                                <div style="display: flex; gap: 24px; color: var(--persian-oa-gray-600); font-size: 14px;">
                                    <?php 
                                        $categories = get_option('persian_oa_incoming_categories', Constants::LETTER_TYPES);
                                        $catLabel = $categories[$letter->category] ?? $letter->category;
                                    ?>
                                    <?php if ($catLabel): ?>
                                        <span>📋 <strong><?php echo esc_html($catLabel); ?></strong></span>
                                    <?php endif; ?>
                                    <span>👤 فرستنده: <strong><?php echo esc_html($letter->sender ?? 'نامشخص'); ?></strong></span>
                                    <span>📅 تاریخ: <strong><?php echo esc_html(JalaliDate::format($letter->created_at, 'date')); ?></strong></span>
                                    <span>🕐 زمان: <strong><?php echo esc_html(JalaliDate::timeAgo($letter->created_at)); ?></strong></span>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <?php if (current_user_can('persian_oa_view_letter') || current_user_can('manage_options')) { ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=persian-oa-incoming-letters&action=view&id=' . absint( $letter->id ) ) ); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 10px 16px; font-size: 14px;">
                                    👁️ مشاهده
                                </a>
                                <?php } ?>
                                <?php if ( current_user_can( 'persian_oa_edit_letter' ) || current_user_can( 'manage_options' ) ) { ?>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=persian-oa-incoming-letters&action=edit&id=' . absint( $letter->id ) ) ); ?>" class="persian-oa-btn persian-oa-btn-primary" style="padding: 10px 16px; font-size: 14px;">
                                    ✏️ ویرایش
                                </a>
                                <?php } ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($letter->description)) { ?>
                            <div style="padding: 16px; background: var(--persian-oa-gray-50); border-radius: 12px; font-size: 15px; color: var(--persian-oa-gray-700); line-height: 1.6;">
                                <?php echo wp_kses_post(substr($letter->description, 0, 200)); ?>
                                <?php if ( strlen( $letter->description ) > 200 ) echo esc_html( '...' ); ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <div class="persian-oa-card">
            <div style="padding: 80px; text-align: center;">
                <div style="font-size: 72px; margin-bottom: 24px; animation: float 3s ease-in-out infinite;">📥</div>
                <h3 style="font-size: 24px; font-weight: 700; color: var(--persian-oa-gray-900); margin-bottom: 12px;">
                    هیچ نامه وارده‌ای یافت نشد
                </h3>
                <p style="font-size: 16px; color: var(--persian-oa-gray-600); margin-bottom: 32px;">
                    با کلیک روی دکمه بالا، اولین نامه وارده را ثبت کنید
                </p>
                <?php if (current_user_can('persian_oa_create_letter') || current_user_can('manage_options')) { ?>
                <a href="?page=persian-oa-incoming-letters&action=new" class="persian-oa-btn persian-oa-btn-primary persian-oa-btn-lg">
                    ➕ ثبت نامه وارده جدید
                </a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</div>


