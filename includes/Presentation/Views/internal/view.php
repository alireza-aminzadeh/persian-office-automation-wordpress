<?php
/**
 * Internal Letters - View Single
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view; letter from controller.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

if ( ! $letter ) {
    echo '<div class="persian-oa-wrap"><div class="persian-oa-alert persian-oa-alert-error">' . esc_html( 'نامه یافت نشد.' ) . '</div></div>';
    return;
}

$sender = get_userdata($letter->getCreatedBy());
$primary = get_userdata($letter->getPrimaryRecipient());
$recipient_names = [];
if ($primary) {
    $recipient_names[] = $primary->display_name;
}
global $wpdb;
$table_cc = $wpdb->prefix . 'persian_oa_cc_recipients';
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
$cc_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT user_id FROM $table_cc WHERE correspondence_id = %d",
    $letter->getId()
));
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
$cc_ids = $cc_ids ?: [];
$cc_names = [];
foreach ($cc_ids as $uid) {
    $u = get_userdata((int) $uid);
    if ($u) {
        $recipient_names[] = $u->display_name;
        $cc_names[] = $u->display_name;
    }
}
$recipient_label = count($recipient_names) > 1 ? 'گیرندگان' : 'گیرنده';
$recipient_display = $recipient_names ? implode('، ', array_map('esc_html', $recipient_names)) : 'نامشخص';
$confidentiality_labels = ['normal' => 'عادی', 'confidential' => 'محرمانه', 'highly_confidential' => 'خیلی محرمانه'];
$confidentiality_text = $confidentiality_labels[$letter->getConfidentiality()] ?? 'عادی';
if (!isset($attachments)) {
    $attachments = [];
}

$status_labels = ['draft' => 'پیش‌نویس', 'sent' => 'ارسال شده', 'pending' => 'در انتظار'];
$status_class = $letter->getStatus() === 'draft' ? 'warning' : 'success';
$status_label = $status_labels[$letter->getStatus()] ?? 'نامشخص';
?>

<div class="persian-oa-wrap persian-oa-internal-view">
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '✉️' ) ); ?></span>
                    <a href="?page=persian-oa-internal" class="persian-oa-title-link">مکاتبات داخلی</a>
                    <span class="persian-oa-breadcrumb-sep">/</span>
                    <span>مشاهده نامه</span>
                    <?php if ($letter->getStatus() === 'draft'): ?>
                        <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( $status_class ); ?> persian-oa-badge-sm">
                            <?php echo esc_html( $status_label ); ?>
                        </span>
                    <?php endif; ?>
                </h1>
                <p class="persian-oa-subtitle">
                    <?php echo esc_html( $letter->getSubject() ); ?>
                </p>
            </div>
            <div class="persian-oa-header-actions">
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="window.print()">🖨️ چاپ</button>
                <a href="javascript:history.back()" class="persian-oa-btn persian-oa-btn-outline">← بازگشت</a>
            </div>
        </div>
    </div>

    <div class="persian-oa-view-container">
        <!-- Letter Content (Paper Style) -->
        <div class="persian-oa-paper" id="persian-oa-internal-paper">
            <div class="persian-oa-paper-header">
                <div class="persian-oa-paper-meta-row">
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">شماره:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html( JalaliDate::convertNumbers( $letter->getNumber() ) ); ?></span>
                    </div>
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">تاریخ:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html( JalaliDate::format( $letter->getCreatedAt() ) ); ?></span>
                    </div>
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">محرمانگی:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html( $confidentiality_text ); ?></span>
                    </div>
                </div>
                <div class="persian-oa-paper-subject">
                    <span class="persian-oa-meta-label">موضوع:</span>
                    <strong><?php echo esc_html( $letter->getSubject() ); ?></strong>
                </div>
            </div>

            <div class="persian-oa-paper-body">
                <?php echo wp_kses_post( $letter->getContent() ); ?>
            </div>

            <?php if (!empty($attachments)): ?>
                <div class="persian-oa-attachments-section">
                    <h3 class="persian-oa-attachments-title">📎 پیوست‌ها</h3>
                    <div class="persian-oa-attachment-list">
                        <?php
                        $upload_dir = wp_upload_dir();
                        foreach ($attachments as $att):
                            $att_url = isset($att->file_path) ? $att->file_path : '#';
                            if ($att_url !== '#' && !empty($upload_dir['basedir']) && strpos($att_url, $upload_dir['basedir']) === 0) {
                                $att_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $att_url);
                            }
                        ?>
                            <a href="<?php echo esc_url($att_url); ?>" target="_blank" rel="noopener" class="persian-oa-attachment-item">
                                <span class="persian-oa-attachment-icon">📄</span>
                                <span class="persian-oa-attachment-name"><?php echo esc_html( isset($att->file_name) ? $att->file_name : '' ); ?></span>
                                <?php if (!empty($att->file_size)): ?>
                                    <span class="persian-oa-attachment-size"><?php echo esc_html( size_format((int) $att->file_size) ); ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $current_user_id = get_current_user_id();
            $is_cc = $letter->getId() && in_array($current_user_id, array_map('intval', $cc_ids), true);
            if (($letter->getPrimaryRecipient() == $current_user_id || $is_cc)):
            ?>
                <div class="persian-oa-paper-actions">
                    <button class="persian-oa-btn persian-oa-btn-primary" onclick="alert('قابلیت پاسخ به زودی فعال می‌شود.')">↩️ پاسخ به نامه</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="persian-oa-sidebar">
            <div class="persian-oa-card persian-oa-participants-card">
                <div class="persian-oa-card-header">
                    <h3>👤 شرکت‌کنندگان</h3>
                </div>
                <div class="persian-oa-card-body">
                    <div class="persian-oa-participant-row">
                        <span class="persian-oa-participant-label">فرستنده</span>
                        <span class="persian-oa-participant-value"><?php echo $sender ? esc_html( $sender->display_name ) : esc_html( 'نامشخص' ); ?></span>
                    </div>
                    <div class="persian-oa-participant-row">
                        <span class="persian-oa-participant-label"><?php echo esc_html( $recipient_label ); ?></span>
                        <span class="persian-oa-participant-value"><?php echo esc_html( $recipient_display ); ?></span>
                    </div>
                    <?php if (!empty($cc_names)): ?>
                        <div class="persian-oa-participant-row">
                            <span class="persian-oa-participant-label">رونوشت (CC)</span>
                            <span class="persian-oa-participant-value"><?php echo esc_html( implode('، ', $cc_names) ); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="persian-oa-card persian-oa-meta-card">
                <div class="persian-oa-card-header">
                    <h3>📋 اطلاعات نامه</h3>
                </div>
                <div class="persian-oa-card-body">
                    <div class="persian-oa-meta-list">
                        <div class="persian-oa-meta-list-item">
                            <span>شماره نامه</span>
                            <strong><?php echo esc_html( JalaliDate::convertNumbers( $letter->getNumber() ) ); ?></strong>
                        </div>
                        <div class="persian-oa-meta-list-item">
                            <span>تاریخ ارسال</span>
                            <strong><?php echo esc_html( JalaliDate::format( $letter->getCreatedAt(), 'datetime' ) ); ?></strong>
                        </div>
                        <div class="persian-oa-meta-list-item">
                            <span>سطح محرمانگی</span>
                            <strong><?php echo esc_html( $confidentiality_text ); ?></strong>
                        </div>
                        <div class="persian-oa-meta-list-item">
                            <span>وضعیت</span>
                            <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status_label ); ?></span>
                        </div>
                        <?php if (!empty($attachments)): ?>
                        <div class="persian-oa-meta-list-item">
                            <span>تعداد پیوست</span>
                            <strong><?php echo esc_html( (string) count( $attachments ) ); ?> فایل</strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
wp_add_inline_style('persian-oa-admin', "
/* Internal View - Paper & Sidebar Layout */
.persian-oa-internal-view .persian-oa-title-link {
    color: inherit;
    text-decoration: none;
    transition: opacity 0.2s;
}
.persian-oa-internal-view .persian-oa-title-link:hover {
    opacity: 0.8;
}
.persian-oa-internal-view .persian-oa-breadcrumb-sep {
    color: var(--persian-oa-gray-400);
    margin: 0 12px;
}
.persian-oa-internal-view .persian-oa-badge-sm { font-size: 13px; padding: 6px 12px; margin-right: 12px; }
.persian-oa-internal-view .persian-oa-header-actions { display: flex; gap: 12px; flex-wrap: wrap; }

.persian-oa-internal-view .persian-oa-view-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-top: 24px;
}
@media (max-width: 1000px) {
    .persian-oa-internal-view .persian-oa-view-container { grid-template-columns: 1fr; }
}

/* Paper Style */
.persian-oa-internal-view .persian-oa-paper {
    background: #fff;
    padding: 48px;
    border-radius: var(--persian-oa-radius-lg);
    box-shadow: var(--persian-oa-shadow-lg);
    border: 1px solid var(--persian-oa-gray-200);
    min-height: 500px;
}
.persian-oa-internal-view .persian-oa-paper-header {
    border-bottom: 2px solid var(--persian-oa-gray-800);
    padding-bottom: 20px;
    margin-bottom: 32px;
}
.persian-oa-internal-view .persian-oa-paper-meta-row {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 14px;
}
.persian-oa-internal-view .persian-oa-paper-meta-item {
    display: flex;
    gap: 8px;
}
.persian-oa-internal-view .persian-oa-meta-label { font-weight: 600; color: var(--persian-oa-gray-600); }
.persian-oa-internal-view .persian-oa-meta-value { color: var(--persian-oa-gray-900); }
.persian-oa-internal-view .persian-oa-paper-subject {
    margin-top: 20px;
    font-size: 18px;
}
.persian-oa-internal-view .persian-oa-paper-subject .persian-oa-meta-label { margin-left: 8px; }
.persian-oa-internal-view .persian-oa-paper-body {
    font-size: 16px;
    line-height: 2;
    text-align: justify;
    color: var(--persian-oa-gray-800);
    min-height: 150px;
}
.persian-oa-internal-view .persian-oa-paper-body p { margin-bottom: 1em; }
.persian-oa-internal-view .persian-oa-paper-body p:last-child { margin-bottom: 0; }

/* Attachments */
.persian-oa-internal-view .persian-oa-attachments-section {
    margin-top: 36px;
    padding-top: 24px;
    border-top: 1px dashed var(--persian-oa-gray-300);
}
.persian-oa-internal-view .persian-oa-attachments-title {
    margin: 0 0 16px 0;
    font-size: 16px;
    color: var(--persian-oa-gray-700);
}
.persian-oa-internal-view .persian-oa-attachment-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
}
.persian-oa-internal-view .persian-oa-attachment-item {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: linear-gradient(135deg, var(--persian-oa-gray-50) 0%, #fff 100%);
    border: 1px solid var(--persian-oa-gray-200);
    border-radius: var(--persian-oa-radius-md);
    text-decoration: none;
    color: var(--persian-oa-gray-800);
    font-size: 14px;
    transition: var(--persian-oa-transition);
}
.persian-oa-internal-view .persian-oa-attachment-item:hover {
    background: linear-gradient(135deg, var(--persian-oa-primary-light) 0%, #fff 100%);
    border-color: var(--persian-oa-primary);
    transform: translateY(-2px);
    box-shadow: var(--persian-oa-shadow-md);
}
.persian-oa-internal-view .persian-oa-attachment-icon { font-size: 18px; }
.persian-oa-internal-view .persian-oa-attachment-name { font-weight: 600; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.persian-oa-internal-view .persian-oa-attachment-size { font-size: 12px; color: var(--persian-oa-gray-500); }

/* Paper Actions */
.persian-oa-internal-view .persian-oa-paper-actions {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--persian-oa-gray-200);
}

/* Sidebar Cards */
.persian-oa-internal-view .persian-oa-participants-card,
.persian-oa-internal-view .persian-oa-meta-card { margin-bottom: 24px; }
.persian-oa-internal-view .persian-oa-participant-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    padding: 12px 0;
    border-bottom: 1px solid var(--persian-oa-gray-100);
}
.persian-oa-internal-view .persian-oa-participant-row:last-child { border-bottom: none; }
.persian-oa-internal-view .persian-oa-participant-label {
    font-size: 12px;
    color: var(--persian-oa-gray-500);
    font-weight: 600;
}
.persian-oa-internal-view .persian-oa-participant-value {
    font-size: 14px;
    color: var(--persian-oa-gray-900);
}
.persian-oa-internal-view .persian-oa-meta-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--persian-oa-gray-100);
    font-size: 14px;
}
.persian-oa-internal-view .persian-oa-meta-list-item:last-child { border-bottom: none; }
.persian-oa-internal-view .persian-oa-meta-list-item span { color: var(--persian-oa-gray-500); }
.persian-oa-internal-view .persian-oa-meta-list-item strong { color: var(--persian-oa-gray-900); }

/* Print */
@media print {
    .persian-oa-internal-view .persian-oa-header,
    .persian-oa-internal-view .persian-oa-sidebar,
    .persian-oa-internal-view .persian-oa-paper-actions,
    #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter { display: none !important; }
    #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; }
    .persian-oa-internal-view { padding: 0 !important; margin: 0 !important; background: white !important; }
    .persian-oa-internal-view .persian-oa-view-container { display: block !important; }
    .persian-oa-internal-view .persian-oa-paper {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
");
?>
