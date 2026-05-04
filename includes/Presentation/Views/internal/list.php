<?php
/**
 * Internal Letters - List View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only list; GET params sanitized.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

$currentUser = wp_get_current_user();
// $message set in InternalLetterController::renderInbox() after nonce verification.
$message = isset( $message ) ? (string) $message : '';
?>

<div class="persian-oa-wrap">
    <?php if ($message === 'success'): ?>
        <div class="persian-oa-alert persian-oa-alert-success" style="margin-bottom: 20px;">
            نامه با موفقیت ارسال شد.
        </div>
    <?php elseif ($message === 'draft_saved'): ?>
        <div class="persian-oa-alert persian-oa-alert-info" style="margin-bottom: 20px;">
            پیش‌نویس با موفقیت ذخیره شد. از تب «ارسال شده» می‌توانید بعداً آن را ارسال کنید.
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '📝' ) ); ?></span>
                    مکاتبات داخلی
                </h1>
                <p class="persian-oa-subtitle">
                    لیست پیام‌ها و نامه‌های داخلی سازمان
                </p>
            </div>
            <div style="display: flex; gap: 12px;">
                <button class="persian-oa-btn persian-oa-btn-primary" onclick="location.href='?page=persian-oa-internal&action=new'">
                    ➕ نامه جدید
                </button>
                <button class="persian-oa-btn persian-oa-btn-outline" onclick="location.reload()">
                    🔄 بروزرسانی
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <div class="persian-oa-internal-tabs">
        <a href="?page=persian-oa-internal&tab=inbox" class="persian-oa-internal-tab <?php echo esc_attr( ( $activeTab === 'inbox' ) ? 'active' : '' ); ?>">
            <span class="persian-oa-internal-tab-icon">📥</span>
            <span class="persian-oa-internal-tab-label">صندوق ورودی</span>
            <?php if ($activeTab === 'inbox'): ?>
                <span class="persian-oa-internal-tab-indicator"></span>
            <?php endif; ?>
        </a>
        <a href="?page=persian-oa-internal&tab=sent" class="persian-oa-internal-tab <?php echo esc_attr( ( $activeTab === 'sent' ) ? 'active' : '' ); ?>">
            <span class="persian-oa-internal-tab-icon">📤</span>
            <span class="persian-oa-internal-tab-label">ارسال شده</span>
            <?php if ($activeTab === 'sent'): ?>
                <span class="persian-oa-internal-tab-indicator"></span>
            <?php endif; ?>
        </a>
    </div>

    <!-- Filters -->
    <div class="persian-oa-card" style="margin-bottom: 24px; border-top-left-radius: 0;">
        <div style="padding: 20px;">
            <form method="get" action="" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <input type="hidden" name="page" value="persian-oa-internal">
                <input type="hidden" name="tab" value="<?php echo esc_attr($activeTab); ?>">
				<?php wp_nonce_field( 'persian_oa_internal_list', 'persian_oa_list_nonce', false ); ?>
                
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" name="s" class="persian-oa-input" placeholder="🔍 جستجو در موضوع یا متن..." 
                           value="<?php echo esc_attr($search); ?>" style="width: 100%;">
                </div>
                
                <button type="submit" class="persian-oa-btn persian-oa-btn-primary">
                    جستجو
                </button>
            </form>
        </div>
    </div>

    <!-- List -->
    <div class="persian-oa-card">
        <?php if (empty($letters)): ?>
            <div style="text-align: center; padding: 80px 20px;">
                <div style="font-size: 64px; margin-bottom: 20px;">📭</div>
                <h3 style="margin: 0 0 10px 0; color: var(--persian-oa-gray-700);">لیست خالی است</h3>
                <p style="color: var(--persian-oa-gray-500);">هیچ نامه‌ای یافت نشد.</p>
            </div>
        <?php else: ?>
            <div class="persian-oa-list">
                <?php foreach ($letters as $letter): 
                    $sender = get_userdata($letter->getCreatedBy());
                    $senderName = $sender ? $sender->display_name : 'کاربر ناشناس';
                    
                    $recipient = get_userdata($letter->getPrimaryRecipient());
                    $recipientName = $recipient ? $recipient->display_name : 'کاربر ناشناس';
                    
                    $targetName = ($activeTab === 'sent') ? $recipientName : $senderName;
                    $targetLabel = ($activeTab === 'sent') ? 'گیرنده:' : 'فرستنده:';
                    
                    $priorityIcons = [
                        'low' => '🟢',
                        'medium' => '🟡',
                        'high' => '🟠',
                        'urgent' => '🔴'
                    ];
                    $priorityIcon = $priorityIcons[$letter->getPriority()] ?? '⚪';
                ?>
                    <div class="persian-oa-list-item" onclick="location.href='<?php echo esc_js( esc_url( wp_nonce_url( admin_url( 'admin.php?page=persian-oa-internal&action=view&id=' . $letter->getId() ), 'persian_oa_internal_view' ) ) ); ?>'">
                        <div class="persian-oa-list-item-content">
                            <div class="persian-oa-list-item-header">
                                <h3 class="persian-oa-list-item-title">
                                    <?php echo esc_html( $priorityIcon ); ?>
                                    <?php echo esc_html( $letter->getSubject() ); ?>
                                </h3>
                                <div class="persian-oa-list-item-meta">
                                    <?php if ( $letter->getStatus() === 'draft' ): ?>
                                        <span class="persian-oa-badge persian-oa-badge-warning">پیش‌نویس</span>
                                    <?php else: ?>
                                        <span class="persian-oa-badge persian-oa-badge-<?php echo esc_attr( ( $letter->getStatus() === 'read' ) ? 'success' : 'warning' ); ?>">
                                            <?php echo esc_html( ( $letter->getStatus() === 'read' ) ? 'خوانده شده' : 'خوانده نشده' ); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span><?php echo esc_html( JalaliDate::timeAgo( $letter->getCreatedAt() ) ); ?></span>
                                </div>
                            </div>
                            <div class="persian-oa-list-item-details">
                                <span><strong>شماره:</strong> <?php echo esc_html( $letter->getNumber() ); ?></span>
                                <span><strong><?php echo esc_html( $targetLabel ); ?></strong> <?php echo esc_html( $targetName ); ?></span>
                                <span>📅 <?php echo esc_html( JalaliDate::format( $letter->getLetterDate() ) ); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
wp_add_inline_style('persian-oa-admin', '
/* Internal letters tabs - modern pill/segmented style */
.persian-oa-internal-tabs {
    display: inline-flex;
    gap: 0;
    padding: 6px;
    background: rgba(255, 255, 255, 0.8);
    border-radius: var(--persian-oa-radius-lg);
    box-shadow: var(--persian-oa-shadow-sm);
    border: 1px solid var(--persian-oa-gray-200);
    margin-bottom: 24px;
    position: relative;
}

.persian-oa-internal-tab {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 28px;
    color: var(--persian-oa-gray-600);
    text-decoration: none;
    border-radius: var(--persian-oa-radius-md);
    font-weight: 500;
    font-size: 15px;
    transition: var(--persian-oa-transition-fast);
    position: relative;
}

.persian-oa-internal-tab:hover {
    color: var(--persian-oa-primary-dark);
    background: var(--persian-oa-gray-50);
}

.persian-oa-internal-tab.active {
    background: linear-gradient(135deg, var(--persian-oa-primary) 0%, var(--persian-oa-primary-dark) 100%);
    color: #fff;
    font-weight: 600;
    box-shadow: var(--persian-oa-shadow-md);
}

.persian-oa-internal-tab.active:hover {
    background: linear-gradient(135deg, var(--persian-oa-primary-dark) 0%, #047857 100%);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 6px 12px rgba(16, 185, 129, 0.35);
}

.persian-oa-internal-tab-icon {
    font-size: 1.2em;
    line-height: 1;
    opacity: 0.95;
}

.persian-oa-internal-tab.active .persian-oa-internal-tab-icon {
    opacity: 1;
}

.persian-oa-internal-tab-label {
    letter-spacing: -0.01em;
}

.persian-oa-internal-tab-indicator {
    position: absolute;
    bottom: 8px;
    left: 50%;
    transform: translateX(-50%);
    width: 24px;
    height: 3px;
    background: rgba(255, 255, 255, 0.6);
    border-radius: var(--persian-oa-radius-full);
}

.persian-oa-list {
    display: flex;
    flex-direction: column;
}

.persian-oa-list-item {
    padding: 20px;
    border-bottom: 1px solid var(--persian-oa-gray-200);
    cursor: pointer;
    transition: background 0.2s;
}

.persian-oa-list-item:hover {
    background: var(--persian-oa-gray-50);
}

.persian-oa-list-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 10px;
}

.persian-oa-list-item-title {
    margin: 0;
    font-size: 16px;
    color: var(--persian-oa-gray-900);
}

.persian-oa-list-item-details {
    display: flex;
    gap: 20px;
    font-size: 13px;
    color: var(--persian-oa-gray-600);
}

.persian-oa-list-item-meta {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: var(--persian-oa-gray-500);
}
');
?>

