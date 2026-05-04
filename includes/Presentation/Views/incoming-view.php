<?php
/**
 * Incoming Letter Read-Only View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, PluginCheck.Security.DirectDB.UnescapedDBParameter
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view; GET params sanitized in controller.
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Common\Constants;

// Ensure we have a letter object
if (!isset($letter) || !$letter) {
    wp_die('نامه مورد نظر یافت نشد.');
}

// Get helper data
$categories = get_option('persian_oa_incoming_categories', Constants::LETTER_TYPES);
$catLabel = $categories[$letter->getCategory()] ?? $letter->getCategory();

// Status labels
$statusColors = [
    'pending' => 'warning',
    'approved' => 'success',
    'rejected' => 'danger',
    'draft' => 'primary',
    'archived' => 'secondary'
];
$statusLabels = [
    'pending' => 'در انتظار پاسخ',
    'approved' => 'تایید شده',
    'rejected' => 'رد شده',
    'draft' => 'پیش‌نویس',
    'archived' => 'بایگانی شده'
];

$statusClass = $statusColors[$letter->getStatus()] ?? 'primary';
$statusLabel = $statusLabels[$letter->getStatus()] ?? 'نامشخص';

// Fetch workflow history (referrals)
global $wpdb;
$referrals_table = $wpdb->prefix . 'persian_oa_referrals';
$users_table = $wpdb->users;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table names from $wpdb->prefix.
$referrals = $wpdb->get_results( $wpdb->prepare(
    "SELECT r.*, u_from.display_name as from_name, u_to.display_name as to_name " .
    "FROM {$referrals_table} r " .
    "LEFT JOIN {$users_table} u_from ON r.from_user = u_from.ID " .
    "LEFT JOIN {$users_table} u_to ON r.to_user = u_to.ID " .
    "WHERE r.correspondence_id = %d ORDER BY r.created_at ASC",
    $letter->getId()
) );
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

// Helper function to get user display name safely
if ( ! function_exists( 'persian_oa_get_user_name' ) ) {
    function persian_oa_get_user_name( $user_id ) {
        $user = get_userdata($user_id);
        return $user ? $user->display_name : 'کاربر حذف شده';
    }
}
    // Fallback for letter date if invalid
    $letterDate = $letter->getLetterDate();
    if (empty($letterDate) || $letterDate === '0000-00-00' || $letterDate === '0000-00-00 00:00:00') {
        $letterDate = $letter->getCreatedAt();
    }
    
    // Logo for letterhead
    $logo_id = get_option('persian_oa_title_icon_attachment_id');
    $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'full') : '';
    $org_name = get_bloginfo('name');
?>

<div class="persian-oa-wrap">
    <!-- Header Actions -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📄' ) ); ?></span>
                    مشاهده نامه وارده
                    <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( $statusClass ); ?>" style="font-size: 14px; margin-right: 12px; vertical-align: middle;">
                        <?php echo esc_html( $statusLabel ); ?>
                    </span>
                </h1>
                <p class="persian-oa-subtitle">
                    شماره: <?php echo esc_html( JalaliDate::convertNumbers( $letter->getNumber() ) ); ?> •
                    تاریخ: <?php echo esc_html( JalaliDate::format( $letterDate, 'date' ) ); ?>
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="persian-oa-btn persian-oa-btn-outline" id="persian-oa-letterhead-toggle" onclick="toggleLetterheadMode()">
                    📝 سربرگ
                </button>
                <a href="javascript:history.back()" class="persian-oa-btn persian-oa-btn-outline">
                    ← بازگشت
                </a>
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="window.print()">
                    🖨️ چاپ
                </button>
                <?php if (current_user_can('persian_oa_create_referral')): ?>
                <button class="persian-oa-btn persian-oa-btn-primary" onclick="showReferralModal()">
                    ↪️ ارجاع نامه
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="persian-oa-view-container">
        <!-- Letter Content (Paper Style) -->
        <div class="persian-oa-paper" id="persian-oa-paper-content">
            <!-- Letterhead Header (Hidden by default) -->
            <div class="persian-oa-letterhead-header" style="display: none;">
                <div class="persian-oa-lh-right">
                    <?php if ($logo_url): ?>
                        <img src="<?php echo esc_url($logo_url); ?>" alt="Logo" class="persian-oa-lh-logo">
                    <?php endif; ?>
                    <div class="persian-oa-lh-org-name"><?php echo esc_html($org_name); ?></div>
                </div>
                <div class="persian-oa-lh-center">
                    <div class="persian-oa-lh-basmala">بسمه تعالی</div>
                </div>
                    <div class="persian-oa-lh-left">
                    <div class="persian-oa-lh-meta-row">
                        <span>شماره:</span>
                        <span><?php echo esc_html( JalaliDate::convertNumbers( $letter->getNumber() ) ); ?></span>
                    </div>
                    <div class="persian-oa-lh-meta-row">
                        <span>تاریخ:</span>
                        <span><?php echo esc_html( JalaliDate::format( $letterDate, 'date' ) ); ?></span>
                    </div>
                    <div class="persian-oa-lh-meta-row">
                        <span>پیوست:</span>
                        <span><?php echo esc_html( ! empty( $attachments ) ? 'دارد' : 'ندارد' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Paper Header -->
            <div class="persian-oa-paper-header">
                <div class="persian-oa-paper-meta-row">
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">شماره نامه:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html(JalaliDate::convertNumbers($letter->getNumber())); ?></span>
                    </div>
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">تاریخ:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html( JalaliDate::format( $letterDate, 'date' ) ); ?></span>
                    </div>
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">پیوست:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html( ! empty( $attachments ) ? 'دارد' : 'ندارد' ); ?></span>
                    </div>
                </div>
                
                <div class="persian-oa-paper-meta-row" style="margin-top: 8px;">
                     <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">فرستنده:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html($letter->getSender()); ?></span>
                    </div>
                    <?php if ($letter->getReferenceNumber()): ?>
                    <div class="persian-oa-paper-meta-item">
                        <span class="persian-oa-meta-label">عطف به:</span>
                        <span class="persian-oa-meta-value"><?php echo esc_html(JalaliDate::convertNumbers($letter->getReferenceNumber())); ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="persian-oa-paper-subject">
                    <span class="persian-oa-meta-label">موضوع:</span>
                    <strong><?php echo esc_html($letter->getSubject()); ?></strong>
                </div>
            </div>

            <!-- Paper Body -->
            <div class="persian-oa-paper-body">
                <?php echo wp_kses_post(wpautop($letter->getContent() ?: $letter->getDescription())); ?>
            </div>

            <!-- Paper Footer (Signatures/Info) -->
            <div class="persian-oa-paper-footer">
                <div class="persian-oa-info-row">
                    <strong>اولویت:</strong>
                    <?php
                        $priorities = array( 'low' => 'عادی', 'medium' => 'متوسط', 'high' => 'زیاد', 'urgent' => 'فوری' );
                        echo esc_html( $priorities[ $letter->getPriority() ] ?? 'عادی' );
                    ?>
                </div>
                <div class="persian-oa-info-row">
                    <strong>محرمانگی:</strong>
                    <?php
                        $confidentiality = array( 'normal' => 'عادی', 'confidential' => 'محرمانه', 'highly_confidential' => 'سری' );
                        echo esc_html( $confidentiality[ $letter->getConfidentiality() ] ?? 'عادی' );
                    ?>
                </div>
            </div>

            <!-- Letterhead Footer (Hidden by default) -->
            <div class="persian-oa-letterhead-footer" style="display: none;">
                <div class="persian-oa-lh-footer-content">
                    <?php echo esc_html(get_bloginfo('description')); ?>
                </div>
            </div>

            <!-- Attachments Section -->
            <?php if (!empty($attachments)): ?>
            <div class="persian-oa-attachments-section">
                <h3>📎 پیوست‌ها</h3>
                <div class="persian-oa-attachment-list">
                    <?php foreach ($attachments as $attachment): ?>
                        <a href="<?php echo esc_url($attachment->file_path); ?>" target="_blank" class="persian-oa-attachment-item">
                            <span class="persian-oa-attachment-icon">📄</span>
                            <span class="persian-oa-attachment-name"><?php echo esc_html($attachment->file_name); ?></span>
                            <span class="persian-oa-attachment-size">(<?php echo esc_html( size_format( $attachment->file_size ) ); ?>)</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar / Workflow Info -->
        <div class="persian-oa-sidebar">
            <!-- Workflow Timeline -->
            <div class="persian-oa-card">
                <div class="persian-oa-card-header">
                    <h3>🔄 گردش کار</h3>
                </div>
                <div class="persian-oa-workflow-timeline">
                    <!-- Initial Creation -->
                    <div class="persian-oa-timeline-item">
                        <div class="persian-oa-timeline-marker start"></div>
                        <div class="persian-oa-timeline-content">
                            <div class="persian-oa-timeline-header">
                                <strong>ثبت در سیستم</strong>
                                <span class="persian-oa-timeline-date"><?php echo esc_html( JalaliDate::format( $letter->getCreatedAt(), 'datetime' ) ); ?></span>
                            </div>
                            <div class="persian-oa-timeline-user">
                                توسط: <?php echo esc_html( persian_oa_get_user_name( $letter->getCreatedBy() ) ); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Referrals -->
                    <?php if (!empty($referrals)): ?>
                        <?php foreach ($referrals as $referral): ?>
                        <div class="persian-oa-timeline-item">
                            <div class="persian-oa-timeline-marker"></div>
                            <div class="persian-oa-timeline-content">
                                <div class="persian-oa-timeline-header">
                                    <strong>ارجاع به <?php echo esc_html($referral->to_name); ?></strong>
                                    <span class="persian-oa-timeline-date"><?php echo esc_html( JalaliDate::format( $referral->created_at, 'datetime' ) ); ?></span>
                                </div>
                                <div class="persian-oa-timeline-user">
                                    از طرف: <?php echo esc_html( $referral->from_name ); ?>
                                </div>
                                <?php if ($referral->comments): ?>
                                    <div class="persian-oa-timeline-message">
                                        "<?php echo esc_html($referral->comments); ?>"
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CC Recipients -->
            <?php if (!empty($cc_recipients)): ?>
            <div class="persian-oa-card persian-oa-mt-4">
                <div class="persian-oa-card-header">
                    <h3>👥 رونوشت‌ها</h3>
                </div>
                <div class="persian-oa-card-body">
                    <ul class="persian-oa-list">
                        <?php foreach ($cc_recipients as $uid): ?>
                            <li><?php echo esc_html( persian_oa_get_user_name( $uid ) ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
wp_add_inline_style('persian-oa-admin', '
/* View Specific Styles */
.persian-oa-view-container { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; margin-top: 24px; }
@media (max-width: 1000px) { .persian-oa-view-container { grid-template-columns: 1fr; } }
/* Paper Style */
.persian-oa-paper { background: #fff; padding: 60px; border-radius: 2px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); min-height: 800px; position: relative; }
.persian-oa-paper-header { border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 40px; }
.persian-oa-paper-meta-row { display: flex; justify-content: space-between; font-size: 14px; }
.persian-oa-paper-meta-item { display: flex; gap: 8px; }
.persian-oa-meta-label { font-weight: bold; }
.persian-oa-paper-subject { margin-top: 20px; font-size: 16px; }
.persian-oa-paper-body { font-size: 16px; line-height: 2; text-align: justify; margin-bottom: 60px; white-space: pre-wrap; }
.persian-oa-paper-footer { border-top: 1px solid #eee; padding-top: 20px; margin-top: auto; display: flex; gap: 24px; font-size: 13px; color: #666; }
/* Attachments */
.persian-oa-attachments-section { margin-top: 40px; padding-top: 20px; border-top: 1px dashed #ccc; }
.persian-oa-attachment-item { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 6px; margin-right: 8px; margin-bottom: 8px; text-decoration: none; color: #333; transition: all 0.2s; }
.persian-oa-attachment-item:hover { background: #e9ecef; transform: translateY(-2px); }
/* Timeline */
.persian-oa-workflow-timeline { position: relative; padding: 20px 0; }
.persian-oa-workflow-timeline::before { content: ""; position: absolute; right: 20px; top: 0; bottom: 0; width: 2px; background: #e9ecef; }
.persian-oa-timeline-item { position: relative; padding-right: 40px; margin-bottom: 24px; }
.persian-oa-timeline-marker { position: absolute; right: 14px; top: 6px; width: 14px; height: 14px; background: #fff; border: 3px solid var(--persian-oa-primary); border-radius: 50%; z-index: 2; }
.persian-oa-timeline-marker.start { border-color: var(--persian-oa-success); }
.persian-oa-timeline-content { background: #f8f9fa; padding: 12px; border-radius: 8px; border: 1px solid #e9ecef; }
.persian-oa-timeline-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; font-size: 13px; }
.persian-oa-timeline-date { font-size: 11px; color: #999; }
.persian-oa-timeline-user { font-size: 12px; color: #666; }
.persian-oa-timeline-message { margin-top: 8px; font-size: 13px; font-style: italic; color: #555; background: #fff; padding: 8px; border-radius: 4px; border-right: 3px solid var(--persian-oa-primary); }
/* Print Styles */
@media print {
    .persian-oa-header, .persian-oa-sidebar, #adminmenuback, #adminmenuwrap, #wpadminbar, #wpfooter { display: none !important; }
    #wpcontent, #wpbody-content { margin-left: 0 !important; padding: 0 !important; }
    .persian-oa-wrap { padding: 0 !important; margin: 0 !important; background: white !important; }
    .persian-oa-view-container { display: block !important; }
    .persian-oa-paper { box-shadow: none !important; padding: 0 !important; border: none !important; }
}

/* Letterhead Mode Styles */
.persian-oa-letterhead-mode { padding-top: 40px !important; }
.persian-oa-letterhead-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding-bottom: 20px; border-bottom: 2px solid #000; }
.persian-oa-lh-right { display: flex; flex-direction: column; align-items: center; width: 200px; }
.persian-oa-lh-logo { max-width: 80px; max-height: 80px; margin-bottom: 10px; }
.persian-oa-lh-org-name { font-weight: bold; font-size: 16px; text-align: center; }
.persian-oa-lh-center { flex: 1; display: flex; justify-content: center; padding-top: 20px; }
.persian-oa-lh-basmala { font-family: "Nastaliq", "IranNastaliq", serif; font-size: 24px; }
.persian-oa-lh-left { width: 200px; font-size: 14px; }
.persian-oa-lh-meta-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
.persian-oa-letterhead-footer { border-top: 2px solid #000; margin-top: 60px; padding-top: 10px; text-align: center; font-size: 12px; color: #333; }
.persian-oa-letterhead-mode .persian-oa-paper-header { border-bottom: none; margin-bottom: 20px; padding-bottom: 0; }
.persian-oa-letterhead-mode .persian-oa-paper-meta-row:first-child { display: none; }
.persian-oa-letterhead-mode .persian-oa-paper-footer { display: none; }

/* Simple Modal Styles */
.persian-oa-modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); }
.persian-oa-modal-content { background-color: #fefefe; margin: 10% auto; padding: 30px; border: 1px solid #888; width: 500px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.persian-oa-close { color: #aaa; float: left; font-size: 28px; font-weight: bold; cursor: pointer; }
.persian-oa-close:hover, .persian-oa-close:focus { color: black; text-decoration: none; cursor: pointer; }
');

wp_add_inline_script('persian-oa-admin', "
function showReferralModal() {
    document.getElementById('referral-modal').style.display = 'block';
}
function closeReferralModal() {
    document.getElementById('referral-modal').style.display = 'none';
}
window.onclick = function(event) {
    if (event.target == document.getElementById('referral-modal')) {
        closeReferralModal();
    }
}
function toggleLetterheadMode() {
    const paper = document.getElementById('persian-oa-paper-content');
    const btn = document.getElementById('persian-oa-letterhead-toggle');
    const header = document.querySelector('.persian-oa-letterhead-header');
    
    paper.classList.toggle('persian-oa-letterhead-mode');
    
    if (paper.classList.contains('persian-oa-letterhead-mode')) {
        btn.innerHTML = '📝 حالت عادی';
        btn.classList.add('persian-oa-btn-primary');
        btn.classList.remove('persian-oa-btn-outline');
        header.style.display = 'flex';
    } else {
        btn.innerHTML = '📝 سربرگ';
        btn.classList.remove('persian-oa-btn-primary');
        btn.classList.add('persian-oa-btn-outline');
        header.style.display = 'none';
    }
}
");
?>

