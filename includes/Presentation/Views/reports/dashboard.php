<?php
/**
 * Reports Dashboard View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\UIHelper;
?>

<div class="persian-oa-wrap persian-oa-reports-page">
    <!-- Header -->
    <div class="persian-oa-reports-header">
        <div class="persian-oa-reports-header__content">
            <div class="persian-oa-reports-header__title-section">
                <div class="persian-oa-reports-header__icon-wrap">
                    <span class="dashicons dashicons-chart-pie"></span>
                    <div class="persian-oa-reports-header__icon-glow"></div>
                </div>
                <div class="persian-oa-reports-header__text">
                    <h1 class="persian-oa-reports-header__title">گزارشات مدیریتی</h1>
                    <p class="persian-oa-reports-header__subtitle">نمای کلی از وضعیت مکاتبات و فعالیت‌های سازمان</p>
                </div>
                <div class="persian-oa-reports-header__accent"></div>
            </div>
            <div class="persian-oa-reports-header__actions">
                <button class="persian-oa-reports-header__print-btn" onclick="window.print()">
                    <span class="dashicons dashicons-printer"></span>
                    چاپ گزارش
                </button>
            </div>
        </div>
        <div class="persian-oa-reports-header__bg-pattern"></div>
    </div>

    <!-- Stats Grid -->
    <div class="persian-oa-stats-grid">
        <!-- Incoming -->
        <div class="persian-oa-stat-card">
            <div class="persian-oa-stat-icon persian-oa-stat-icon--incoming" style="--stat-gradient: linear-gradient(135deg, #3b82f6, #2563eb)">
                <span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
            </div>
            <div class="persian-oa-stat-label">نامه‌های وارده</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $summary['total_incoming'] ) ); ?></div>
            <div class="persian-oa-stat-change up">
                کل ورودی‌ها
            </div>
        </div>

        <!-- Outgoing -->
        <div class="persian-oa-stat-card">
            <div class="persian-oa-stat-icon persian-oa-stat-icon--outgoing" style="--stat-gradient: linear-gradient(135deg, #10b981, #059669)">
                <span class="dashicons dashicons-upload" aria-hidden="true"></span>
            </div>
            <div class="persian-oa-stat-label">نامه‌های صادره</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $summary['total_outgoing'] ) ); ?></div>
            <div class="persian-oa-stat-change up">
                کل خروجی‌ها
            </div>
        </div>

        <!-- Internal -->
        <div class="persian-oa-stat-card">
            <div class="persian-oa-stat-icon persian-oa-stat-icon--internal" style="--stat-gradient: linear-gradient(135deg, #8b5cf6, #7c3aed)">
                <span class="dashicons dashicons-media-document" aria-hidden="true"></span>
            </div>
            <div class="persian-oa-stat-label">مکاتبات داخلی</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $summary['total_internal'] ) ); ?></div>
            <div class="persian-oa-stat-change persian-oa-stat-change-neutral">
                کل داخلی‌ها
            </div>
        </div>

        <!-- Tasks -->
        <div class="persian-oa-stat-card">
            <div class="persian-oa-stat-icon persian-oa-stat-icon--tasks" style="--stat-gradient: linear-gradient(135deg, #f59e0b, #d97706)">
                <span class="dashicons dashicons-clipboard" aria-hidden="true"></span>
            </div>
            <div class="persian-oa-stat-label">وظایف در جریان</div>
            <div class="persian-oa-stat-value"><?php echo esc_html( number_format( $taskStats['in_progress'] + $taskStats['todo'] ) ); ?></div>
            <div class="persian-oa-stat-change down">
                <?php echo esc_html( number_format( $taskStats['done'] ) ); ?> انجام شده
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="persian-oa-grid-2-1">
        <div class="persian-oa-card persian-oa-chart-card">
            <div class="persian-oa-chart-header">
                <span class="dashicons dashicons-chart-bar" style="color: var(--persian-oa-primary); font-size: 22px; width: 22px; height: 22px;"></span>
                <h3>روند مکاتبات (۶ ماه اخیر)</h3>
            </div>
            <div class="persian-oa-chart-body trend-chart">
                <canvas id="trendChart"></canvas>
            </div>
        </div>

        <div class="persian-oa-card persian-oa-chart-card">
            <div class="persian-oa-chart-header">
                <span class="dashicons dashicons-chart-pie" style="color: var(--persian-oa-primary); font-size: 22px; width: 22px; height: 22px;"></span>
                <h3>توزیع اولویت‌ها</h3>
            </div>
            <div class="persian-oa-chart-body">
                <canvas id="priorityChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Bottom Row: Meetings & Status Breakdown -->
    <div class="persian-oa-grid-2-1">
        <div class="persian-oa-card persian-oa-meetings-card">
            <div class="persian-oa-card-header">
                <h3>
                    <span class="dashicons dashicons-groups" style="color: var(--persian-oa-primary); font-size: 20px; width: 20px; height: 20px; margin-left: 8px;"></span>
                    جلسات آینده
                </h3>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=persian-oa-meetings' ) ); ?>" class="persian-oa-btn persian-oa-btn-outline persian-oa-btn-view-all">مشاهده همه</a>
            </div>
            <div class="persian-oa-table-wrapper">
                <table class="persian-oa-table">
                    <thead>
                        <tr>
                            <th>عنوان جلسه</th>
                            <th>تاریخ و زمان</th>
                            <th>مکان</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($meetings)): ?>
                            <tr>
                                <td colspan="4">
                                    <div class="persian-oa-reports-empty-state">
                                        <span class="dashicons dashicons-calendar-alt"></span>
                                        <p>هیچ جلسه آینده‌ای یافت نشد.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($meetings as $meeting): ?>
                                <tr>
                                    <td class="persian-oa-meeting-title"><?php echo esc_html($meeting['title']); ?></td>
                                    <td class="persian-oa-meeting-date"><?php echo esc_html($meeting['formatted_date']); ?></td>
                                    <td><?php echo esc_html($meeting['location'] ?: '-'); ?></td>
                                    <td>
                                        <?php 
                                        $statusClass = 'persian-oa-badge-primary';
                                        if ($meeting['status'] === 'held') $statusClass = 'persian-oa-badge-success';
                                        if ($meeting['status'] === 'cancelled') $statusClass = 'persian-oa-badge-danger';
                                        ?>
                                        <span class="persian-oa-badge <?php echo esc_attr( $statusClass ); ?>">
                                            <?php echo esc_html( UIHelper::getMeetingStatusLabel( $meeting['status'] ) ); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="persian-oa-card persian-oa-status-breakdown-card">
            <div class="persian-oa-card-header persian-oa-status-breakdown-header">
                <div class="persian-oa-status-breakdown-title-wrap">
                    <h3>
                        <span class="dashicons dashicons-chart-area" style="color: var(--persian-oa-primary); font-size: 20px; width: 20px; height: 20px; margin-left: 8px;"></span>
                        وضعیت نامه‌ها
                    </h3>
                    <?php
                    $totalLetters = array_sum( $summary['status_breakdown'] );
                    ?>
                    <p class="persian-oa-status-total">کل نامه‌ها: <strong><?php echo esc_html( number_format_i18n( $totalLetters ) ); ?></strong></p>
                </div>
                <p class="persian-oa-status-hint">برای مشاهده هر دسته روی آن کلیک کنید.</p>
            </div>
            <div class="persian-oa-status-list">
                <?php
                $statuses = [
                    'draft'    => [ 'label' => 'پیش‌نویس',       'color' => '#9ca3af', 'bg' => '#f3f4f6', 'url' => admin_url( 'admin.php?page=persian-oa-outgoing&status=draft' ) ],
                    'pending'  => [ 'label' => 'در جریان',       'color' => '#f59e0b', 'bg' => '#fef3c7', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-pending' ) ],
                    'replied'  => [ 'label' => 'پاسخ داده شده',  'color' => '#10b981', 'bg' => '#d1fae5', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-replied' ) ],
                    'archived' => [ 'label' => 'بایگانی شده',   'color' => '#3b82f6', 'bg' => '#e0e7ff', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-archive' ) ],
                    'rejected' => [ 'label' => 'رد شده',        'color' => '#ef4444', 'bg' => '#fee2e2', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-inbox&status=rejected' ) ],
                    'approved' => [ 'label' => 'تأیید شده',     'color' => '#059669', 'bg' => '#ccfbf1', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-inbox&status=approved' ) ],
                    'sent'     => [ 'label' => 'ارسال شده',     'color' => '#6366f1', 'bg' => '#e0e7ff', 'url' => admin_url( 'admin.php?page=persian-oa-cartable-sent' ) ],
                ];
                $totalLettersForPercent = $totalLetters > 0 ? $totalLetters : 1;
                foreach ($statuses as $status => $meta):
                    $count = isset( $summary['status_breakdown'][ $status ] ) ? (int) $summary['status_breakdown'][ $status ] : 0;
                    if ( $count === 0 ) continue;
                    $percent = round( ( $count / $totalLettersForPercent ) * 100 );
                    $url = $meta['url'];
                ?>
                <a href="<?php echo esc_url( $url ); ?>" class="persian-oa-status-item persian-oa-status-item-link">
                    <div class="persian-oa-status-item-header">
                        <span><?php echo esc_html( $meta['label'] ); ?></span>
                        <span class="persian-oa-status-count"><?php echo esc_html( number_format_i18n( $count ) ); ?> <small>(<?php echo esc_html( (string) $percent ); ?>٪)</small></span>
                    </div>
                    <div class="persian-oa-status-bar">
                        <div class="persian-oa-status-bar-fill" style="width: <?php echo esc_attr( (string) min( 100, $percent ) ); ?>%; background: <?php echo esc_attr( $meta['color'] ); ?>;"></div>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php if ( $totalLetters === 0 ): ?>
                <div class="persian-oa-status-empty">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p>هنوز نامه‌ای ثبت نشده است.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
wp_add_inline_script('persian-oa-admin', "
(function() {
    var chartDataTrendsLabels = " . wp_json_encode($chartData['trends']['labels']) . ";
    var chartDataTrendsIncoming = " . wp_json_encode($chartData['trends']['incoming']) . ";
    var chartDataTrendsOutgoing = " . wp_json_encode($chartData['trends']['outgoing']) . ";
    var chartDataTrendsInternal = " . wp_json_encode($chartData['trends']['internal']) . ";
    var vazirFont = { family: 'Vazirmatn', size: 13 };

    function initCharts() {
        // Trend Chart – با فونت فارسی برای محورها و افسانه
        var trendCtx = document.getElementById('trendChart');
        if (trendCtx) {
            trendCtx = trendCtx.getContext('2d');
            new Chart(trendCtx, {
                type: 'bar',
                data: {
                    labels: chartDataTrendsLabels,
                    datasets: [
                        { label: 'وارده', data: chartDataTrendsIncoming, backgroundColor: '#3b82f6', borderRadius: 6 },
                        { label: 'صادره', data: chartDataTrendsOutgoing, backgroundColor: '#10b981', borderRadius: 6 },
                        { label: 'داخلی', data: chartDataTrendsInternal, backgroundColor: '#8b5cf6', borderRadius: 6 }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                font: vazirFont,
                                usePointStyle: true,
                                padding: 20
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [2, 4] },
                            ticks: { font: vazirFont }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: vazirFont, maxRotation: 45, minRotation: 0 }
                        }
                    }
                }
            });
        }

        // Priority Chart – توزیع اولویت‌ها
        var priorityLabels = " . wp_json_encode($chartData['priority']['labels']) . ";
        var priorityData = " . wp_json_encode($chartData['priority']['data']) . ";
        if (!priorityLabels.length || !priorityData.length) {
            priorityLabels = ['بدون داده'];
            priorityData = [1];
        }
        var priorityColors = ['#22c55e', '#eab308', '#f97316', '#ef4444', '#10b981', '#f59e0b', '#b91c1c'];
        var priorityBg = (priorityLabels[0] === 'بدون داده') ? ['#e5e7eb'] : priorityColors.slice(0, Math.max(priorityLabels.length, 1));
        var priorityEl = document.getElementById('priorityChart');
        if (priorityEl) {
            new Chart(priorityEl.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: priorityLabels,
                    datasets: [{
                        data: priorityData,
                        backgroundColor: priorityBg,
                        borderColor: '#ffffff',
                        borderWidth: 2.5,
                        hoverBorderWidth: 3,
                        hoverOffset: 6,
                        pointStyle: 'circle'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    spacing: 3,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            rtl: true,
                            labels: {
                                font: { family: 'Vazirmatn', size: 14 },
                                usePointStyle: true,
                                pointStyle: 'circle',
                                padding: 18,
                                pointStyleWidth: 14,
                                boxWidth: 14,
                                boxHeight: 14
                            }
                        },
                        tooltip: {
                            rtl: true,
                            textDirection: 'rtl',
                            titleFont: { family: 'Vazirmatn', size: 14 },
                            bodyFont: { family: 'Vazirmatn', size: 13 },
                            padding: 14,
                            cornerRadius: 10,
                            callbacks: {
                                label: function(context) {
                                    var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                    var pct = total ? Math.round((context.raw / total) * 100) : 0;
                                    return [
                                        'اولویت: ' + context.label,
                                        'تعداد: ' + context.raw.toLocaleString('fa-IR'),
                                        'درصد: ' + pct + '٪'
                                    ];
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // رسم نمودارها بعد از لود شدن فونت Vazirmatn تا متن‌های فارسی در canvas درست نمایش داده شوند
    function runWhenReady() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', runWhenReady);
            return;
        }
        function drawCharts() {
            if (document.fonts && document.fonts.load) {
                document.fonts.load('400 16px Vazirmatn').then(initCharts).catch(function() { initCharts(); });
            } else if (document.fonts && document.fonts.ready) {
                document.fonts.ready.then(initCharts);
            } else {
                initCharts();
            }
        }
        drawCharts();
    }
    runWhenReady();
})();
");
?>


