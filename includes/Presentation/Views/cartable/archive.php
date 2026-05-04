<?php
/**
 * Cartable - Archive View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Common\Constants;
?>

<div class="persian-oa-wrap">
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '🗄️' ) ); ?></span>
                    آرشیو
                </h1>
                <p class="persian-oa-subtitle">
                    نامه‌های بایگانی شده
                </p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="persian-oa-card" style="margin-bottom: 24px;">
        <div style="padding: 20px;">
            <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="page" value="persian-oa-cartable-archive">
                <?php wp_nonce_field('persian_oa_filter_archive', 'persian_oa_filter_nonce', false); ?>
                
                <input type="text" name="s" class="persian-oa-input" placeholder="🔍 جستجو..." 
                       value="<?php echo esc_attr($filters['search'] ?? ''); ?>" style="flex: 1; min-width: 200px;">
                
                <input type="text" name="date_from" class="persian-oa-input jalali-datepicker" placeholder="از تاریخ" 
                       value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>" style="width: 150px;">
                
                <input type="text" name="date_to" class="persian-oa-input jalali-datepicker" placeholder="تا تاریخ" 
                       value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>" style="width: 150px;">
                
                <select name="category" class="persian-oa-input" style="width: 150px;">
                    <option value="">همه دسته‌ها</option>
                    <?php 
                    $categories = get_option('persian_oa_incoming_categories', Constants::LETTER_TYPES);
                    foreach ($categories as $key => $label): 
                    ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($filters['category'] ?? '', $key); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <button type="submit" class="persian-oa-btn persian-oa-btn-primary">جستجو</button>
                <a href="?page=persian-oa-cartable-archive" class="persian-oa-btn persian-oa-btn-outline">پاک کردن</a>
            </form>
        </div>
    </div>

    <div class="persian-oa-card">
        <div style="padding: 0;">
            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">🗄️</div>
                    <h3 style="margin: 0 0 10px 0; color: var(--persian-oa-gray-700);">آرشیو خالی است</h3>
                </div>
            <?php else: ?>
                <div class="persian-oa-table-wrapper">
                    <table class="persian-oa-table">
                        <thead>
                            <tr>
                                <th>شماره</th>
                                <th>موضوع</th>
                                <th>دسته</th>
                                <th>کد بایگانی</th>
                                <th>تاریخ نامه</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong style="color: var(--persian-oa-primary);">#<?php echo esc_html( $item->number ); ?></strong></td>
                                    <td><strong><?php echo esc_html( $item->subject ); ?></strong></td>
                                    <td>
                                        <?php 
                                        $categories = get_option('persian_oa_incoming_categories', Constants::LETTER_TYPES);
                                        $catLabel = $categories[$item->category] ?? $item->category;
                                        if (!$catLabel) $catLabel = 'نامشخص';
                                        ?>
                                        <span class="persian-oa-badge persian-oa-badge-primary"><?php echo esc_html($catLabel); ?></span>
                                    </td>
                                    <td><?php echo esc_html($item->archive_code ?: '-'); ?></td>
                                    <td><?php echo $item->letter_date ? esc_html( JalaliDate::format( $item->letter_date, 'date' ) ) : esc_html( '-' ); ?></td>
                                    <td>
                                        <?php
                                        $viewPage = ( ! empty( $item->type ) && $item->type === 'outgoing' ) ? 'persian-oa-outgoing' : ( ( ! empty( $item->type ) && $item->type === 'internal' ) ? 'persian-oa-internal' : 'persian-oa-incoming-letters' );
                                        $viewUrl = admin_url( 'admin.php?page=' . $viewPage . '&action=view&id=' . absint( $item->id ) );
                                        if ( 'persian-oa-internal' === $viewPage ) {
                                            $viewUrl = wp_nonce_url( $viewUrl, 'persian_oa_internal_view' );
                                        }
                                        ?>
                                        <a href="<?php echo esc_url( $viewUrl ); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 6px 12px; font-size: 13px;">👁️ مشاهده</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

