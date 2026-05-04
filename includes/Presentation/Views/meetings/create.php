<?php
/**
 * Create Meeting View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Form has nonce; GET only for default date/redirect.
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;

$defaultDate = '';
$defaultDateGregorian = '';
$defaultEndDate = '';
$defaultEndDateGregorian = '';
$redirectTo = '';

if ( isset( $_GET['date'] ) || isset( $_GET['redirect_to'] ) ) {
    if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'persian_oa_create_meeting_get' ) ) {
        wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ) );
    }
    
    if ( isset( $_GET['date'] ) ) {
        $defaultDate = sanitize_text_field( wp_unslash( $_GET['date'] ) );
        $defaultDateGregorian = JalaliDate::jalaliToGregorianString($defaultDate);
        $defaultDate .= ' 09:00';
        $defaultDateGregorian .= ' 09:00:00';
        $defaultEndDateGregorian = gmdate('Y-m-d H:i:s', strtotime($defaultDateGregorian) + 3600);
        $defaultEndDate = JalaliDate::toJalali($defaultEndDateGregorian, 'Y/m/d') . ' ' . gmdate('H:i', strtotime($defaultEndDateGregorian));
    }
    
    if ( isset( $_GET['redirect_to'] ) ) {
        $redirectTo = sanitize_text_field( wp_unslash( $_GET['redirect_to'] ) );
    }
}

if ( empty( $defaultEndDateGregorian ) ) {
    $defaultEndDateGregorian = gmdate('Y-m-d H:i:s', strtotime('+1 hour'));
    $defaultEndDate = JalaliDate::toJalali($defaultEndDateGregorian, 'Y/m/d') . ' ' . gmdate('H:i', strtotime($defaultEndDateGregorian));
}
$formErrors = get_transient('persian_oa_meeting_create_errors');
if ($formErrors) {
    delete_transient('persian_oa_meeting_create_errors');
}
?>

<div class="persian-oa-wrap">
    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( '➕' ) ); ?></span>
                    ثبت جلسه جدید
                </h1>
                <p class="persian-oa-subtitle">برنامه‌ریزی و دعوت از همکاران</p>
            </div>
            <a href="<?php echo esc_url( $redirectTo === 'calendar' ? '?page=persian-oa-calendar' : '?page=persian-oa-meetings' ); ?>" class="persian-oa-btn persian-oa-btn-outline">
                ← بازگشت
            </a>
        </div>
    </div>

    <div class="persian-oa-card">
        <?php if (!empty($formErrors)) : ?>
            <div class="persian-oa-notice persian-oa-notice-error" style="margin: 0 32px 24px; padding: 12px 16px; border-radius: 8px; background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;">
                <strong>خطا در ثبت جلسه:</strong>
                <ul style="margin: 8px 0 0 20px;">
                    <?php foreach ($formErrors as $err) : ?>
                        <li><?php echo esc_html($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="padding: 32px;">
            <input type="hidden" name="action" value="persian_oa_create_meeting">
            <?php wp_nonce_field('persian_oa_create_meeting', 'persian_oa_meeting_nonce'); ?>
            
            <?php if($redirectTo): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirectTo); ?>">
            <?php endif; ?>

            <div class="persian-oa-form-grid">
                <!-- عنوان -->
                <div class="persian-oa-form-group" style="grid-column: span 2;">
                    <label class="persian-oa-label required">عنوان جلسه</label>
                    <input type="text" name="title" class="persian-oa-input" required placeholder="مثال: جلسه بررسی عملکرد ماهانه..." autofocus>
                </div>

                <!-- تاریخ و زمان شروع -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label required">تاریخ و زمان شروع</label>
                    <div class="persian-oa-date-time-row">
                        <div class="persian-oa-input-group persian-oa-flex-1">
                            <span class="persian-oa-input-icon dashicons dashicons-calendar-alt"></span>
                            <input type="text" id="meeting-date-jalali" name="meeting_date" class="persian-oa-input jalali-datepicker" 
                                   required readonly placeholder="انتخاب تاریخ" style="cursor: pointer; background-color: #ffffff;"
                                   value="<?php echo esc_attr( $defaultDate ? preg_replace( '/\s+\d{1,2}:\d{2}$/', '', $defaultDate ) : '' ); ?>">
                        </div>
                        <label class="persian-oa-time-label">ساعت شروع</label>
                        <input type="time" id="meeting-time" class="persian-oa-input persian-oa-time-input" value="<?php echo esc_attr( $defaultDateGregorian ? gmdate( 'H:i', strtotime( $defaultDateGregorian ) ) : '09:00' ); ?>" required>
                    </div>
                    <input type="hidden" id="meeting-date-gregorian-date" value="<?php echo esc_attr( $defaultDateGregorian ? gmdate( 'Y-m-d', strtotime( $defaultDateGregorian ) ) : '' ); ?>">
                    <input type="hidden" id="meeting-date-gregorian" name="meeting_date_gregorian" value="<?php echo esc_attr($defaultDateGregorian); ?>">
                </div>

                <!-- تاریخ و زمان پایان -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label required">تاریخ و زمان پایان</label>
                    <div class="persian-oa-date-time-row">
                        <div class="persian-oa-input-group persian-oa-flex-1">
                            <span class="persian-oa-input-icon dashicons dashicons-calendar-alt"></span>
                            <input type="text" id="meeting-end-date-jalali" name="end_date" class="persian-oa-input jalali-datepicker" 
                                   required readonly placeholder="انتخاب تاریخ" style="cursor: pointer; background-color: #ffffff;"
                                   value="<?php echo esc_attr( $defaultEndDate ? preg_replace( '/\s+\d{1,2}:\d{2}$/', '', $defaultEndDate ) : '' ); ?>">
                        </div>
                        <label class="persian-oa-time-label">ساعت پایان</label>
                        <input type="time" id="end-time" class="persian-oa-input persian-oa-time-input" value="<?php echo esc_attr( $defaultEndDateGregorian ? gmdate( 'H:i', strtotime( $defaultEndDateGregorian ) ) : '10:00' ); ?>" required>
                    </div>
                    <input type="hidden" id="meeting-end-date-gregorian-date" value="<?php echo esc_attr( $defaultEndDateGregorian ? gmdate( 'Y-m-d', strtotime( $defaultEndDateGregorian ) ) : '' ); ?>">
                    <input type="hidden" id="meeting-end-date-gregorian" name="end_date_gregorian" value="<?php echo esc_attr($defaultEndDateGregorian); ?>">
                </div>

                <!-- مدت زمان (خودکار از شروع و پایان) -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">مدت زمان</label>
                    <div class="persian-oa-duration-display" id="persian-oa-duration-display" style="padding: 10px 14px; background: #f1f5f9; border-radius: 8px; color: #475569; font-weight: 500;">
                        —
                    </div>
                    <input type="hidden" name="duration" id="persian-oa-duration-minutes" value="60">
                </div>

                <!-- مکان -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label required">مکان برگزاری</label>
                    <div class="persian-oa-input-group">
                        <span class="persian-oa-input-icon dashicons dashicons-location"></span>
                        <input type="text" name="location" class="persian-oa-input" required placeholder="نام اتاق جلسه یا لینک آنلاین">
                    </div>
                </div>

                <!-- تکرار -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label">تکرار جلسه</label>
                    <select name="recurrence" class="persian-oa-select">
                        <option value="none">بدون تکرار</option>
                        <option value="daily">روزانه</option>
                        <option value="weekly">هفتگی</option>
                        <option value="monthly">ماهانه</option>
                    </select>
                </div>

                <!-- رنگ -->
                <div class="persian-oa-form-group" style="grid-column: span 2;">
                    <label class="persian-oa-label">رنگ نمایش در تقویم</label>
                    <div class="persian-oa-color-picker">
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#3b82f6" checked>
                            <span class="swatch" style="background: #3b82f6;"></span>
                            <span class="name">آبی (عادی)</span>
                        </label>
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#ef4444">
                            <span class="swatch" style="background: #ef4444;"></span>
                            <span class="name">قرمز (مهم)</span>
                        </label>
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#10b981">
                            <span class="swatch" style="background: #10b981;"></span>
                            <span class="name">سبز (بازبینی)</span>
                        </label>
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#f59e0b">
                            <span class="swatch" style="background: #f59e0b;"></span>
                            <span class="name">نارنجی (آموزشی)</span>
                        </label>
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#8b5cf6">
                            <span class="swatch" style="background: #8b5cf6;"></span>
                            <span class="name">بنفش (مدیریتی)</span>
                        </label>
                        <label class="persian-oa-color-option">
                            <input type="radio" name="color" value="#6b7280">
                            <span class="swatch" style="background: #6b7280;"></span>
                            <span class="name">سایر</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- شرکت کنندگان -->
            <div class="persian-oa-form-group persian-oa-mt-4">
                <label class="persian-oa-label">شرکت کنندگان (دعوت شدگان)</label>
                <div style="border: 1px solid #cbd5e1; border-radius: 8px; padding: 16px; max-height: 200px; overflow-y: auto;">
                    <?php 
                    $users = get_users();
                    foreach ($users as $user): 
                    ?>
                        <label class="persian-oa-checkbox-item">
                            <input type="checkbox" name="participants[]" value="<?php echo esc_attr( (string) $user->ID ); ?>">
                            <span class="avatar">
                                <?php echo wp_kses_post( get_avatar($user->ID, 24) ); ?>
                            </span>
                            <span class="name"><?php echo esc_html($user->display_name); ?></span>
                            <span class="email"><?php echo esc_html($user->user_email); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <small class="persian-oa-help-text">افراد مورد نظر را از لیست بالا انتخاب کنید.</small>
            </div>

            <!-- توضیحات -->
            <div class="persian-oa-form-group persian-oa-mt-4">
                <label class="persian-oa-label">توضیحات / دستور جلسه</label>
                <div class="persian-oa-description-toolbar">
                    <button type="button" class="persian-oa-toolbar-btn persian-oa-btn-numbered" id="persian-oa-add-numbered-item" title="افزودن مورد شماره‌دار">
                        <span class="dashicons dashicons-editor-ol"></span>
                        <span>افزودن مورد شماره‌دار</span>
                    </button>
                </div>
                <textarea name="description" id="persian-oa-meeting-description" class="persian-oa-textarea" rows="5" placeholder="شرح دستور جلسه و جزئیات دیگر... یا از دکمه بالا برای لیست شماره‌دار استفاده کنید."></textarea>
            </div>

            <!-- Action Buttons -->
            <div style="margin-top: 32px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 24px;">
                <a href="<?php echo esc_url( $redirectTo === 'calendar' ? admin_url( 'admin.php?page=persian-oa-calendar' ) : admin_url( 'admin.php?page=persian-oa-meetings' ) ); ?>" class="persian-oa-btn persian-oa-btn-outline">
                    انصراف
                </a>
                <button type="submit" class="persian-oa-btn persian-oa-btn-primary" style="min-width: 140px;">
                    💾 ثبت جلسه
                </button>
            </div>
        </form>
    </div>
</div>

<?php
wp_add_inline_style('persian-oa-admin', '
.persian-oa-date-time-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.persian-oa-date-time-row .persian-oa-flex-1 { flex: 1; min-width: 140px; }
.persian-oa-time-label { font-size: 13px; color: #64748b; white-space: nowrap; }
.persian-oa-time-input { width: 100px; padding: 10px 12px; }
.persian-oa-input-group { position: relative; display: flex; align-items: center; }
.persian-oa-input-icon { position: absolute; right: 12px; color: #94a3b8; z-index: 1; }
.persian-oa-input-group .persian-oa-input { padding-right: 36px; }
.persian-oa-color-picker { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 8px; }
.persian-oa-color-option { display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 6px 12px; border: 1px solid #e2e8f0; border-radius: 6px; transition: all 0.2s; }
.persian-oa-color-option:hover { background: #f8fafc; border-color: #cbd5e1; }
.persian-oa-color-option input { margin: 0; }
.persian-oa-color-option .swatch { width: 16px; height: 16px; border-radius: 4px; }
.persian-oa-color-option .name { font-size: 13px; color: #475569; }
.persian-oa-checkbox-item { display: flex; align-items: center; gap: 12px; padding: 8px; border-bottom: 1px solid #f1f5f9; cursor: pointer; transition: background 0.1s; }
.persian-oa-checkbox-item:last-child { border-bottom: none; }
.persian-oa-checkbox-item:hover { background: #f8fafc; }
.persian-oa-checkbox-item .avatar img { border-radius: 50%; vertical-align: middle; }
.persian-oa-checkbox-item .name { font-weight: 500; color: #334155; }
.persian-oa-checkbox-item .email { font-size: 12px; color: #94a3b8; margin-right: auto; }
.persian-oa-description-toolbar { display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap; }
.persian-oa-toolbar-btn { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px; background: #fff; color: #475569; cursor: pointer; transition: all 0.2s; }
.persian-oa-toolbar-btn:hover { background: #f1f5f9; border-color: #94a3b8; color: #334155; }
.persian-oa-toolbar-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
');

wp_add_inline_script('persian-oa-admin', '
jQuery(document).ready(function($) {
    function syncStartDateTime() {
        var datePart = $("#meeting-date-gregorian-date").val();
        var timePart = $("#meeting-time").val() || "09:00";
        if (datePart) $("#meeting-date-gregorian").val(datePart + " " + timePart + ":00");
    }
    function syncEndDateTime() {
        var datePart = $("#meeting-end-date-gregorian-date").val();
        var timePart = $("#end-time").val() || "10:00";
        if (datePart) $("#meeting-end-date-gregorian").val(datePart + " " + timePart + ":00");
    }
    function parseGregorian(val) {
        if (!val || !val.trim()) return null;
        var d = new Date(val.replace(/-/g, "/"));
        return isNaN(d.getTime()) ? null : d;
    }
    function updateDuration() {
        syncStartDateTime();
        syncEndDateTime();
        var startVal = $("#meeting-date-gregorian").val();
        var endVal = $("#meeting-end-date-gregorian").val();
        var start = parseGregorian(startVal);
        var end = parseGregorian(endVal);
        var $display = $("#persian-oa-duration-display");
        var $minutes = $("#persian-oa-duration-minutes");
        if (!start || !end) {
            $display.text("—");
            $minutes.val("0");
            return;
        }
        var diffMs = end.getTime() - start.getTime();
        var diffMins = Math.round(diffMs / 60000);
        if (diffMins < 0) {
            $display.text("زمان پایان باید بعد از شروع باشد");
            $minutes.val("0");
            return;
        }
        $minutes.val(String(diffMins));
        if (diffMins < 60) {
            $display.text(diffMins + " دقیقه");
        } else if (diffMins === 60) {
            $display.text("۱ ساعت");
        } else if (diffMins === 90) {
            $display.text("۱ ساعت و نیم");
        } else if (diffMins % 60 === 0) {
            $display.text((diffMins / 60) + " ساعت");
        } else {
            $display.text(Math.floor(diffMins / 60) + " ساعت و " + (diffMins % 60) + " دقیقه");
        }
    }
    if (typeof SimplePersianDatePicker !== "undefined") {
        new SimplePersianDatePicker(
            document.getElementById("meeting-date-jalali"),
            document.getElementById("meeting-date-gregorian-date"),
            { defaultToday: false, onSelect: function() { syncStartDateTime(); updateDuration(); } }
        );
        new SimplePersianDatePicker(
            document.getElementById("meeting-end-date-jalali"),
            document.getElementById("meeting-end-date-gregorian-date"),
            { defaultToday: false, onSelect: function() { syncEndDateTime(); updateDuration(); } }
        );
    }
    $("#meeting-time, #end-time").on("change input", updateDuration);
    setInterval(updateDuration, 800);
    updateDuration();

    function getNextNumberedIndex(text) {
        if (!text || !text.trim()) return 1;
        var lines = text.split(/\r?\n/);
        var maxNum = 0;
        var re = /^\s*(\d+)[\.\)]\s/;
        for (var i = 0; i < lines.length; i++) {
            var m = lines[i].match(re);
            if (m) {
                var n = parseInt(m[1], 10);
                if (n > maxNum) maxNum = n;
            }
        }
        return maxNum + 1;
    }
    function insertNumberedItem() {
        var ta = document.getElementById("persian-oa-meeting-description");
        if (!ta) return;
        var start = ta.selectionStart, end = ta.selectionEnd;
        var text = ta.value;
        var nextNum = getNextNumberedIndex(text);
        var insertion = (start > 0 && text[start - 1] !== "\n" ? "\n" : "") + nextNum + ". ";
        ta.value = text.slice(0, start) + insertion + text.slice(end);
        ta.selectionStart = ta.selectionEnd = start + insertion.length;
        ta.focus();
    }
    $("#persian-oa-add-numbered-item").on("click", insertNumberedItem);
});
');
?>