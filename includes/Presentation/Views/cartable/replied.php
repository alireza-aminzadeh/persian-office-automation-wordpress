<?php
/**
 * Cartable - Replied Items View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
?>

<div class="persian-oa-wrap">
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '✅' ) ); ?></span>
                    پاسخ داده شده
                </h1>
                <p class="persian-oa-subtitle">
                    نامه‌هایی که به آن‌ها پاسخ داده‌اید
                </p>
            </div>
        </div>
    </div>

    <div class="persian-oa-card">
        <div style="padding: 0;">
            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">✉️</div>
                    <h3 style="margin: 0 0 10px 0; color: var(--persian-oa-gray-700);">هیچ پاسخی ثبت نشده</h3>
                </div>
            <?php else: ?>
                <div class="persian-oa-table-wrapper">
                    <table class="persian-oa-table">
                        <thead>
                            <tr>
                                <th>شماره</th>
                                <th>موضوع</th>
                                <th>فرستنده</th>
                                <th>تاریخ پاسخ</th>
                                <th>شماره پاسخ</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><strong style="color: var(--persian-oa-primary);">#<?php echo esc_html( $item->number ); ?></strong></td>
                                    <td><strong><?php echo esc_html( $item->subject ); ?></strong></td>
                                    <td><?php echo esc_html( $item->sender ); ?></td>
                                    <td><?php echo esc_html( JalaliDate::format( $item->replied_at, 'datetime' ) ); ?></td>
                                    <td><strong style="color: var(--persian-oa-success);">#<?php echo esc_html( $item->reply_number ?: '-' ); ?></strong></td>
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

