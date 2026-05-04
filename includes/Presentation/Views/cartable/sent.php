<?php
/**
 * Cartable - Sent Items View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

$pagination = $pagination ?? ['total' => 0, 'total_pages' => 1, 'current_page' => 1, 'per_page' => 15];
$total = (int) $pagination['total'];
$totalPages = (int) $pagination['total_pages'];
$currentPage = (int) $pagination['current_page'];
$perPage = (int) $pagination['per_page'];
$from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
$to = min($currentPage * $perPage, $total);
?>

<div class="persian-oa-wrap">
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📤' ) ); ?></span>
                    ارسالی‌های من
                </h1>
                <p class="persian-oa-subtitle">
                    <?php if ( $total > 0 ) : ?>
                        نمایش <?php echo (int) $from; ?> تا <?php echo (int) $to; ?> از <?php echo (int) $total; ?> نامه
                    <?php else : ?>
                        لیست تمام نامه‌ها و مکاتباتی که توسط شما در سیستم ثبت یا ارسال شده‌اند
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="persian-oa-card">
        <div style="padding: 0;">
            <?php if (empty($items)): ?>
                <div style="text-align: center; padding: 80px 20px;">
                    <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                    <h3 style="margin: 0 0 10px 0; color: var(--persian-oa-gray-700);">هیچ نامه‌ای ارسال نشده</h3>
                </div>
            <?php else: ?>
                <div class="persian-oa-table-wrapper">
                    <table class="persian-oa-table">
                        <thead>
                            <tr>
                                <th>شماره</th>
                                <th>موضوع</th>
                                <th>گیرنده</th>
                                <th>تاریخ ارسال</th>
                                <th>وضعیت</th>
                                <th>خوانده شده</th>
                                <th>عملیات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $statusColors = [
                                    'pending' => 'warning',
                                    'approved' => 'success',
                                    'rejected' => 'danger',
                                    'draft' => 'primary'
                                ];
                                $statusLabels = [
                                    'pending' => 'در انتظار',
                                    'approved' => 'تایید شده',
                                    'rejected' => 'رد شده',
                                    'draft' => 'پیش‌نویس'
                                ];
                                
                                $statusClass = $statusColors[$item->status] ?? 'primary';
                                $statusLabel = $statusLabels[$item->status] ?? 'نامشخص';
                                
                                $recipient = get_userdata($item->primary_recipient);
                                $recipientName = $recipient ? $recipient->display_name : 'نامشخص';
                            ?>
                                <tr>
                                    <td><strong style="color: var(--persian-oa-primary);">#<?php echo esc_html( $item->number ); ?></strong></td>
                                    <td><strong><?php echo esc_html( $item->subject ); ?></strong></td>
                                    <td><?php echo esc_html( $recipientName ); ?></td>
                                    <td><?php echo esc_html( JalaliDate::format( $item->created_at, 'datetime' ) ); ?></td>
                                    <td><span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( $statusClass ); ?>"><?php echo esc_html( $statusLabel ); ?></span></td>
                                    <td>
                                        <?php if ( $item->read_count > 0 ) : ?>
                                            <span style="color: var(--persian-oa-success);">✅ <?php echo esc_html( (string) $item->read_count ); ?> نفر</span>
                                        <?php else : ?>
                                            <span style="color: var(--persian-oa-gray-400);">❌ خوانده نشده</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $viewPage = 'persian-oa-incoming-letters';
                                        if ( ! empty( $item->type ) ) {
                                            if ( $item->type === 'outgoing' ) {
                                                $viewPage = 'persian-oa-outgoing';
                                            } elseif ( $item->type === 'internal' ) {
                                                $viewPage = 'persian-oa-internal';
                                            }
                                        }
                                        $viewUrl = admin_url( 'admin.php?page=' . $viewPage . '&action=view&id=' . absint( $item->id ) );
                                        if ( 'persian-oa-internal' === $viewPage ) {
                                            $viewUrl = wp_nonce_url( $viewUrl, 'persian_oa_internal_view' );
                                        }
                                        ?>
                                        <a href="<?php echo esc_url( $viewUrl ); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 6px 12px; font-size: 13px;">
                                            👁️ مشاهده
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ( $totalPages > 1 ) : ?>
                    <div class="persian-oa-pagination" style="margin-top: 24px; padding: 16px; display: flex; justify-content: center; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <?php
                        $baseUrl = admin_url( 'admin.php' );
                        $queryArgs = [ 'page' => 'persian-oa-cartable-sent' ];
                        ?>
                        <?php if ( $currentPage > 1 ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array_merge( $queryArgs, [ 'paged' => $currentPage - 1 ] ), $baseUrl ) ); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 8px 16px;">
                                قبلی
                            </a>
                        <?php endif; ?>
                        <span class="persian-oa-pagination-info" style="font-size: 14px; color: var(--persian-oa-gray-600); padding: 0 12px;">
                            صفحه <?php echo (int) $currentPage; ?> از <?php echo (int) $totalPages; ?>
                        </span>
                        <?php if ( $currentPage < $totalPages ) : ?>
                            <a href="<?php echo esc_url( add_query_arg( array_merge( $queryArgs, [ 'paged' => $currentPage + 1 ] ), $baseUrl ) ); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 8px 16px;">
                                بعدی
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
