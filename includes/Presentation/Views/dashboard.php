<?php
/**
 * Dashboard View - Ultra Modern Design
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.DateTime.RestrictedFunctions.date_date
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only dashboard; no form submission.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository;

$repo = new CorrespondenceRepository();
$counts = $repo->getDashboardCounts();
$total    = $counts['total'];
$incoming = $counts['incoming'];
$outgoing = $counts['outgoing'];
$pending  = $counts['pending'];

// Calculate Monthly Stats (Last 6 Months)
$chartLabels   = [];
$chartIncoming = [];
$chartOutgoing = [];

$monthNames = [
    1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
    7 => 'مهر', 8 => 'آبان', 9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
];

list($currentJY, $currentJM, $currentJD) = JalaliDate::gregorianToJalali( (int) gmdate( 'Y' ), (int) gmdate( 'm' ), (int) gmdate( 'd' ) );

for ( $i = 5; $i >= 0; $i-- ) {
    $m = $currentJM - $i;
    $y = $currentJY;
    if ( $m <= 0 ) {
        $m += 12;
        $y -= 1;
    }

    list($startGY, $startGM, $startGD) = JalaliDate::jalaliToGregorian( $y, $m, 1 );
    $startDate = sprintf( '%04d-%02d-%02d 00:00:00', $startGY, $startGM, $startGD );

    $daysInMonth = JalaliDate::getDaysInJalaliMonth( $y, $m );
    list($endGY, $endGM, $endGD) = JalaliDate::jalaliToGregorian( $y, $m, $daysInMonth );
    $endDate = sprintf( '%04d-%02d-%02d 23:59:59', $endGY, $endGM, $endGD );

    $monthCounts = $repo->getMonthlyCounts( $startDate, $endDate );
    $chartLabels[]   = $monthNames[ $m ] . ' ' . JalaliDate::convertNumbers( (string) $y );
    $chartIncoming[] = $monthCounts['incoming'];
    $chartOutgoing[] = $monthCounts['outgoing'];
}

// Month-over-month percentage change (current month = last index, previous = second-to-last)
$currIncoming = isset($chartIncoming[5]) ? $chartIncoming[5] : 0;
$prevIncoming = isset($chartIncoming[4]) ? $chartIncoming[4] : 0;
$currOutgoing = isset($chartOutgoing[5]) ? $chartOutgoing[5] : 0;
$prevOutgoing = isset($chartOutgoing[4]) ? $chartOutgoing[4] : 0;
$currTotal = $currIncoming + $currOutgoing;
$prevTotal = $prevIncoming + $prevOutgoing;

$pctIncoming = $prevIncoming > 0
    ? round(( $currIncoming - $prevIncoming ) / $prevIncoming * 100)
    : ( $currIncoming > 0 ? 100 : 0 );
$pctOutgoing = $prevOutgoing > 0
    ? round(( $currOutgoing - $prevOutgoing ) / $prevOutgoing * 100)
    : ( $currOutgoing > 0 ? 100 : 0 );
$pctTotal = $prevTotal > 0
    ? round(( $currTotal - $prevTotal ) / $prevTotal * 100)
    : ( $currTotal > 0 ? 100 : 0 );

$statCardStyle = 'text-decoration: none; color: inherit; display: block; cursor: pointer;';
$urlTotal    = admin_url( 'admin.php?page=persian-oa-incoming-letters' );
$urlIncoming = admin_url( 'admin.php?page=persian-oa-incoming-letters' );
$urlOutgoing = admin_url( 'admin.php?page=persian-oa-outgoing' );
$urlPending  = admin_url( 'admin.php?page=persian-oa-cartable-pending' );
?>

<div class="persian-oa-wrap">
    <!-- Header with gradient -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📊' ) ); ?></span>
                    داشبورد مدیریتی
                </h1>
                <p class="persian-oa-subtitle">
                    خوش آمدید، <?php echo esc_html(wp_get_current_user()->display_name); ?> 👋 • 
                    امروز: <?php echo esc_html(JalaliDate::format(current_time('timestamp'), 'full')); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <button class="persian-oa-btn persian-oa-btn-primary" onclick="location.href='?page=persian-oa-incoming'">
                    ➕ نامه وارده جدید
                </button>
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="location.href='?page=persian-oa-outgoing'">
                    📤 نامه صادره جدید
                </button>
            </div>
        </div>
    </div>

    <!-- Beautiful Stats Grid -->
    <div class="persian-oa-stats-grid">
        <!-- Total -->
        <a href="<?php echo esc_url( $urlTotal ); ?>" class="persian-oa-stat-card" style="--stat-gradient: linear-gradient(135deg, #6366f1, #4f46e5); <?php echo esc_attr( $statCardStyle ); ?>">
            <div class="persian-oa-stat-icon">📋</div>
            <div class="persian-oa-stat-label">کل نامه‌ها</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $total ) ); ?></div>
            <span class="persian-oa-stat-change <?php echo esc_attr( $pctTotal >= 0 ? 'up' : 'down' ); ?>">
                <?php
                if ( $pctTotal > 0 ) {
                    echo '⬆ ' . esc_html( JalaliDate::convertNumbers( (string) $pctTotal ) ) . '٪ نسبت به ماه قبل';
                } elseif ( $pctTotal < 0 ) {
                    echo '⬇ ' . esc_html( JalaliDate::convertNumbers( (string) abs( $pctTotal ) ) ) . '٪ نسبت به ماه قبل';
                } else {
                    echo esc_html( 'بدون تغییر نسبت به ماه قبل' );
                }
                ?>
            </span>
        </a>

        <!-- Incoming -->
        <a href="<?php echo esc_url( $urlIncoming ); ?>" class="persian-oa-stat-card" style="--stat-gradient: linear-gradient(135deg, #10b981, #059669); <?php echo esc_attr( $statCardStyle ); ?>">
            <div class="persian-oa-stat-icon">📥</div>
            <div class="persian-oa-stat-label">نامه‌های وارده</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $incoming ) ); ?></div>
            <span class="persian-oa-stat-change <?php echo esc_attr( $pctIncoming >= 0 ? 'up' : 'down' ); ?>">
                <?php
                if ( $pctIncoming > 0 ) {
                    echo '⬆ ' . esc_html( JalaliDate::convertNumbers( (string) $pctIncoming ) ) . '٪ افزایش';
                } elseif ( $pctIncoming < 0 ) {
                    echo '⬇ ' . esc_html( JalaliDate::convertNumbers( (string) abs( $pctIncoming ) ) ) . '٪ کاهش';
                } else {
                    echo esc_html( 'بدون تغییر' );
                }
                ?>
            </span>
        </a>

        <!-- Outgoing -->
        <a href="<?php echo esc_url( $urlOutgoing ); ?>" class="persian-oa-stat-card" style="--stat-gradient: linear-gradient(135deg, #f59e0b, #d97706); <?php echo esc_attr( $statCardStyle ); ?>">
            <div class="persian-oa-stat-icon">📤</div>
            <div class="persian-oa-stat-label">نامه‌های صادره</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $outgoing ) ); ?></div>
            <span class="persian-oa-stat-change <?php echo esc_attr( $pctOutgoing >= 0 ? 'up' : 'down' ); ?>">
                <?php
                if ( $pctOutgoing > 0 ) {
                    echo '⬆ ' . esc_html( JalaliDate::convertNumbers( (string) $pctOutgoing ) ) . '٪ افزایش';
                } elseif ( $pctOutgoing < 0 ) {
                    echo '⬇ ' . esc_html( JalaliDate::convertNumbers( (string) abs( $pctOutgoing ) ) ) . '٪ کاهش';
                } else {
                    echo esc_html( 'بدون تغییر' );
                }
                ?>
            </span>
        </a>

        <!-- Pending -->
        <a href="<?php echo esc_url( $urlPending ); ?>" class="persian-oa-stat-card" style="--stat-gradient: linear-gradient(135deg, #ef4444, #dc2626); <?php echo esc_attr( $statCardStyle ); ?>">
            <div class="persian-oa-stat-icon">⏳</div>
            <div class="persian-oa-stat-label">در انتظار پاسخ</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $pending ) ); ?></div>
            <span class="persian-oa-stat-change down">
                ⚠️ نیاز به اقدام
            </span>
        </a>
    </div>

    <!-- Charts & Activity -->
    <div class="persian-oa-grid-2-1">
        <!-- Chart Card -->
        <div class="persian-oa-card">
            <div style="padding: 24px; border-bottom: 1px solid var(--persian-oa-gray-200);">
                <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--persian-oa-gray-900); display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--persian-oa-primary), var(--persian-oa-primary-dark)); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">📈</span>
                    نمودار نامه‌های ماهانه
                </h3>
                <p style="margin: 8px 0 0 52px; font-size: 13px; color: var(--persian-oa-gray-500);">بر اساس ۶ ماه اخیر (داده واقعی از دیتابیس)</p>
            </div>
            <div style="padding: 32px; position: relative; overflow: hidden;">
                <canvas id="monthlyChart" style="max-height: 300px; width: 100%;"></canvas>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="persian-oa-card">
            <div style="padding: 24px; border-bottom: 1px solid var(--persian-oa-gray-200);">
                <h3 style="margin: 0; font-size: 18px; font-weight: 700; color: var(--persian-oa-gray-900); display: flex; align-items: center; gap: 10px;">
                    <span>⚡</span>
                    اقدامات سریع
                </h3>
            </div>
            <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                <a href="?page=persian-oa-incoming" class="persian-oa-btn persian-oa-btn-primary" style="width: 100%; justify-content: center;">
                    📥 ثبت نامه وارده
                </a>
                <a href="?page=persian-oa-outgoing" class="persian-oa-btn persian-oa-btn-success" style="width: 100%; justify-content: center;">
                    📤 ثبت نامه صادره
                </a>
                <a href="?page=persian-oa-users" class="persian-oa-btn persian-oa-btn-outline" style="width: 100%; justify-content: center;">
                    👥 مدیریت کاربران
                </a>
                <a href="?page=persian-oa-settings" class="persian-oa-btn persian-oa-btn-outline" style="width: 100%; justify-content: center;">
                    ⚙️ تنظیمات
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Letters -->
    <div class="persian-oa-card">
        <div style="padding: 24px; border-bottom: 1px solid var(--persian-oa-gray-200);">
            <h3 style="margin: 0; font-size: 20px; font-weight: 700; color: var(--persian-oa-gray-900); display: flex; align-items: center; gap: 12px;">
                <span style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--persian-oa-success), #059669); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px;">🔔</span>
                آخرین نامه‌ها
            </h3>
        </div>
        <div style="padding: 0;">
            <div class="persian-oa-table-wrapper">
                <table class="persian-oa-table">
                    <thead>
                        <tr>
                            <th>شماره</th>
                            <th>موضوع</th>
                            <th>نوع</th>
                            <th>وضعیت</th>
                            <th>تاریخ</th>
                            <th>عملیات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $repo->getRecent( 10 );
                        if ( ! empty( $recent ) ) {
                            foreach ($recent as $item) {
                                $typeLabel = $item->type == 'incoming' ? '📥 وارده' : '📤 صادره';
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
                                ?>
                                <tr>
                                    <td><strong style="color: var(--persian-oa-primary);">#<?php echo esc_html($item->number); ?></strong></td>
                                    <td><strong><?php echo esc_html($item->subject); ?></strong></td>
                                    <td><?php echo esc_html($typeLabel); ?></td>
                                    <td><span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr($statusClass); ?>"><?php echo esc_html($statusLabel); ?></span></td>
                                    <td><?php echo esc_html(JalaliDate::format($item->created_at, 'date')); ?></td>
                                    <td>
                                        <?php
                                        $view_url = $item->type === 'incoming'
                                            ? admin_url('admin.php?page=persian-oa-incoming-letters&action=view&id=' . absint($item->id))
                                            : admin_url('admin.php?page=persian-oa-outgoing&action=view&id=' . absint($item->id));
                                        ?>
                                        <a href="<?php echo esc_url($view_url); ?>" class="persian-oa-btn persian-oa-btn-outline" style="padding: 6px 12px; font-size: 13px;">
                                            👁️ مشاهده
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 60px; color: var(--persian-oa-gray-500);">
                                    <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                                    <div style="font-size: 18px; font-weight: 600;">هیچ نامه‌ای ثبت نشده است</div>
                                    <div style="font-size: 14px; margin-top: 8px;">با کلیک روی دکمه‌های بالا، اولین نامه را ثبت کنید</div>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$chartYMax = 1;
if ( ! empty( $chartIncoming ) || ! empty( $chartOutgoing ) ) {
    $chartYMax = (int) max(
        $chartIncoming ? max( $chartIncoming ) : 0,
        $chartOutgoing ? max( $chartOutgoing ) : 0,
        1
    );
    $chartYMax = min( 100, max( 5, $chartYMax + (int) ceil( $chartYMax * 0.2 ) ) );
}

wp_add_inline_script('persian-oa-admin', "
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('monthlyChart');
    if (ctx && typeof Chart !== 'undefined') {
        var chartLabels = " . wp_json_encode( $chartLabels ) . ";
        var chartIncoming = " . wp_json_encode( $chartIncoming ) . ";
        var chartOutgoing = " . wp_json_encode( $chartOutgoing ) . ";
        var chartYMax = " . (int) $chartYMax . ";

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'نامه‌های وارده',
                    data: chartIncoming,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }, {
                    label: 'نامه‌های صادره',
                    data: chartOutgoing,
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointRadius: 6,
                    pointHoverRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Vazirmatn', size: 14, weight: '600' },
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { family: 'Vazirmatn', size: 14, weight: '600' },
                        bodyFont: { family: 'Vazirmatn', size: 13 },
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) label += ': ';
                                if (context.parsed.y !== null) {
                                    var num = context.parsed.y.toString().replace(/\d/g, function(d) { return '۰۱۲۳۴۵۶۷۸۹'[d]; });
                                    label += num + ' نامه';
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: chartYMax,
                        grid: { color: '#f3f4f6' },
                        ticks: {
                            font: { family: 'Vazirmatn' },
                            callback: function(value) {
                                return value.toString().replace(/\d/g, d => '۰۱۲۳۴۵۶۷۸۹'[d]);
                            }
                        }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Vazirmatn', size: 13 } }
                    }
                }
            }
        });
    }
});
");
?>
