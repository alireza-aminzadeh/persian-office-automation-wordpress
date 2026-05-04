<?php
/**
 * Outgoing Letter Form View
 * phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 * @package OfficeAutomation
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
use PersianOfficeAutomation\Common\JalaliDate;
use PersianOfficeAutomation\Common\UIHelper;
use PersianOfficeAutomation\Common\Constants;

$is_edit = isset($letter) && $letter;
$page_title = $is_edit ? 'ویرایش نامه صادره' : 'ثبت نامه صادره جدید';
$page_icon = $is_edit ? '✏️' : '➕';
?>

<div class="persian-oa-wrap">
    <!-- Header -->
    <div class="persian-oa-header">
        <div class="persian-oa-header-content">
            <div>
                <h1 class="persian-oa-title">
                    <span class="persian-oa-title-icon"><?php echo wp_kses_post( UIHelper::getTitleIcon( esc_html( $page_icon ) ) ); ?></span>
                    <?php echo esc_html( $page_title ); ?>
                </h1>
                <p class="persian-oa-subtitle">
                    تاریخ: <?php echo esc_html( JalaliDate::now( 'l، j F Y' ) ); ?>
                </p>
            </div>
            <a href="?page=persian-oa-outgoing" class="persian-oa-btn persian-oa-btn-outline">
                ← بازگشت به لیست
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="persian-oa-card persian-oa-mb-4" style="background: #fee; border-right: 4px solid #c33;">
        <div style="padding: 20px;">
            <h3 style="color: #c33; margin: 0 0 12px 0;">❌ خطاهای فرم:</h3>
            <ul style="margin: 0; padding-right: 20px; color: #c33;">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo esc_html($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data" class="persian-oa-letter-form">
        <input type="hidden" name="action" value="persian_oa_save_outgoing_letter">
        <?php wp_nonce_field('persian_oa_save_outgoing_letter', 'persian_oa_outgoing_nonce'); ?>
        <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr($letter->getId()); ?>">
        <?php endif; ?>

        <!-- Tabs -->
        <div class="persian-oa-tabs-container">
            <div class="persian-oa-tabs">
                <button type="button" class="persian-oa-tab active" data-tab="basic">
                    📋 اطلاعات اولیه
                </button>
                <button type="button" class="persian-oa-tab" data-tab="content">
                    📝 محتوا و متن
                </button>
                <button type="button" class="persian-oa-tab" data-tab="workflow">
                    🔄 امضا و گردش کار
                </button>
            </div>
        </div>

        <!-- Tab 1: Basic Information -->
        <div class="persian-oa-card persian-oa-tab-content active" data-tab-content="basic">
            <div style="padding: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--persian-oa-gray-900); margin: 0 0 24px 0; padding-bottom: 16px; border-bottom: 2px solid var(--persian-oa-gray-200);">
                    📋 اطلاعات اولیه نامه
                </h3>

                <div class="persian-oa-form-grid">
                    <!-- شماره نامه صادره (شمارنده خودکار) -->
                    <?php
                    $outgoing_number = $is_edit ? $letter->getNumber() : (isset($next_number) ? $next_number : '');
                    $outgoing_number_display = $outgoing_number !== '' ? JalaliDate::convertNumbers($outgoing_number) : '';
                    ?>
                    <div class="persian-oa-form-group">
                        <label class="persian-oa-label required">شماره نامه صادره</label>
                        <input type="text" name="letter_number" class="persian-oa-input" 
                               value="<?php echo esc_attr($outgoing_number_display); ?>" 
                               <?php echo $is_edit ? '' : esc_attr( 'readonly' ); ?>
                               required placeholder="مثال: OUT-1403/0001">
                        <small class="persian-oa-help-text"><?php echo esc_html( $is_edit ? 'شماره یکتای نامه در سیستم' : 'شمارنده خودکار — بر اساس سال شمسی و ترتیب ثبت ارتقا می‌یابد' ); ?></small>
                    </div>

                    <!-- تاریخ نامه -->
                    <div class="persian-oa-form-group">
                        <label class="persian-oa-label required">تاریخ نامه</label>
                        <input type="text" id="letter-date-jalali" name="letter_date" class="persian-oa-input jalali-datepicker" 
                               value="<?php echo esc_attr( $is_edit && $letter->getLetterDate() ? JalaliDate::format( $letter->getLetterDate(), 'date' ) : JalaliDate::now( 'Y/m/d' ) ); ?>" 
                               required readonly placeholder="انتخاب تاریخ" style="cursor: pointer; background-color: #ffffff;">
                        <input type="hidden" id="letter-date-gregorian" name="letter_date_gregorian" 
                               value="<?php echo esc_attr( $is_edit && $letter->getLetterDate() ? gmdate( 'Y-m-d', strtotime( $letter->getLetterDate() ) ) : gmdate( 'Y-m-d' ) ); ?>">
                    </div>

                    <!-- اولویت -->
                    <div class="persian-oa-form-group">
                        <label class="persian-oa-label required">اولویت</label>
                        <select name="priority" class="persian-oa-select" required>
                            <option value="normal" <?php echo esc_attr( ( ! $is_edit || $letter->getPriority() === 'normal' ) ? 'selected' : '' ); ?>>عادی</option>
                            <option value="high" <?php echo esc_attr( ( $is_edit && $letter->getPriority() === 'high' ) ? 'selected' : '' ); ?>>فوری</option>
                            <option value="urgent" <?php echo esc_attr( ( $is_edit && $letter->getPriority() === 'urgent' ) ? 'selected' : '' ); ?>>بسیار فوری</option>
                        </select>
                    </div>
                </div>

                <!-- موضوع -->
                <div class="persian-oa-form-group persian-oa-mt-4">
                    <label class="persian-oa-label required">موضوع نامه</label>
                    <input type="text" name="subject" class="persian-oa-input" 
                           value="<?php echo $is_edit ? esc_attr( $letter->getSubject() ) : ''; ?>" 
                           required placeholder="خلاصه موضوع نامه را وارد کنید">
                </div>

                <!-- گیرنده -->
                <div class="persian-oa-form-group persian-oa-mt-4">
                    <label class="persian-oa-label required">گیرنده (سازمان / شخص)</label>
                    <input type="text" name="recipient" class="persian-oa-input" 
                           value="<?php echo $is_edit ? esc_attr( $letter->getRecipient() ) : ''; ?>" 
                           required placeholder="نام کامل گیرنده نامه">
                </div>
            </div>
        </div>

        <!-- Tab 2: Content -->
        <div class="persian-oa-card persian-oa-tab-content" data-tab-content="content">
            <div style="padding: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--persian-oa-gray-900); margin: 0 0 24px 0; padding-bottom: 16px; border-bottom: 2px solid var(--persian-oa-gray-200);">
                    📝 متن نامه
                </h3>

                <!-- محتوای کامل -->
                <div class="persian-oa-form-group">
                    <label class="persian-oa-label required">متن نامه</label>
                    <?php 
                    $content = $is_edit ? $letter->getContent() : '';
                    ?>
                    <textarea name="content" id="editor-content" class="persian-oa-textarea" style="visibility:hidden; height:0;"><?php echo esc_textarea($content); ?></textarea>
                </div>

                <!-- پیوست‌ها -->
                <div class="persian-oa-form-group persian-oa-mt-4">
                    <label class="persian-oa-label">پیوست فایل</label>
                    <div class="persian-oa-file-upload">
                        <input type="file" name="attachment" id="attachments" class="persian-oa-file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                        <label for="attachments" class="persian-oa-file-label">
                            📎 انتخاب فایل
                            <span style="font-size: 13px; color: var(--persian-oa-gray-600);">حداکثر <?php echo esc_html(get_option('persian_oa_max_upload_size', 10)); ?>MB</span>
                        </label>
                    </div>
                    <div id="file-list" class="persian-oa-file-list"></div>
                </div>
            </div>
        </div>

        <!-- Tab 3: Workflow -->
        <div class="persian-oa-card persian-oa-tab-content" data-tab-content="workflow">
            <div style="padding: 32px;">
                <h3 style="font-size: 18px; font-weight: 700; color: var(--persian-oa-gray-900); margin: 0 0 24px 0; padding-bottom: 16px; border-bottom: 2px solid var(--persian-oa-gray-200);">
                    🔄 امضا و گردش کار
                </h3>

                <div class="persian-oa-form-grid">
                    <!-- امضا کننده -->
                    <div class="persian-oa-form-group">
                        <label class="persian-oa-label required">امضا کننده (فرستنده)</label>
                        <select name="signer" class="persian-oa-select" required>
                            <option value="">انتخاب کنید</option>
                            <?php 
                            $users = get_users(['role__in' => ['administrator', 'persian_oa_manager', 'persian_oa_staff']]);
                            $current_user_id = get_current_user_id();
                            foreach ($users as $user): 
                            ?>
                                <option value="<?php echo esc_attr( (string) $user->ID ); ?>"
                                        <?php echo esc_attr( ( ( $is_edit && (int) $letter->getPrimaryRecipient() === (int) $user->ID ) || ( ! $is_edit && (int) $user->ID === (int) $current_user_id ) ) ? 'selected' : '' ); ?>>
                                    <?php echo esc_html( $user->display_name ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="persian-oa-help-text">فردی که نامه از طرف او ارسال می‌شود</small>
                    </div>
                </div>

                <!-- یادداشت‌های داخلی -->
                <div class="persian-oa-form-group persian-oa-mt-4">
                    <label class="persian-oa-label">یادداشت‌های داخلی</label>
                    <textarea name="notes" class="persian-oa-textarea" rows="3" placeholder="یادداشت‌های داخلی (اختیاری)"><?php echo $is_edit ? esc_textarea($letter->getNotes()) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="persian-oa-card">
            <div style="padding: 24px; display: flex; gap: 12px; justify-content: flex-end; background: var(--persian-oa-gray-50); border-top: 2px solid var(--persian-oa-gray-200);">
                <a href="?page=persian-oa-outgoing" class="persian-oa-btn persian-oa-btn-outline persian-oa-btn-lg">
                    ❌ انصراف
                </a>
                <button type="submit" name="save_draft" class="persian-oa-btn persian-oa-btn-outline persian-oa-btn-lg">
                    💾 ذخیره پیش‌نویس
                </button>
                <button type="submit" name="submit_approval" class="persian-oa-btn persian-oa-btn-primary persian-oa-btn-lg">
                    📨 ارسال برای تایید
                </button>
            </div>
        </div>
    </form>
</div>

<?php
wp_add_inline_script('persian-oa-admin', "
jQuery(document).ready(function($) {
    // Tab switching
    $('.persian-oa-tab').on('click', function() {
        var tab = $(this).data('tab');
        $('.persian-oa-tab').removeClass('active');
        $(this).addClass('active');
        $('.persian-oa-tab-content').removeClass('active');
        $('.persian-oa-tab-content[data-tab-content=\"' + tab + '\"]').addClass('active');
    });
    
    // File upload preview
    $('#attachments').on('change', function() {
        var file = this.files[0];
        var fileList = $('#file-list');
        fileList.html('');
        
        if (file) {
            var size = (file.size / 1024 / 1024).toFixed(2);
            fileList.append('<div class=\"persian-oa-file-item\">📎 ' + file.name + ' <span class=\"persian-oa-file-size\">(' + size + ' MB)</span></div>');
        }
    });
    
    // Initialize Date Picker
    if (typeof SimplePersianDatePicker !== 'undefined') {
        new SimplePersianDatePicker(
            document.getElementById('letter-date-jalali'),
            document.getElementById('letter-date-gregorian'),
            {
                defaultToday: true
            }
        );
    }

    // Initialize CKEditor 5
    if (typeof ClassicEditor !== 'undefined') {
        ClassicEditor
            .create(document.querySelector('#editor-content'), {
                language: 'fa',
                toolbar: [ 'heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', '|', 'undo', 'redo' ]
            })
            .catch(error => {
                console.error(error);
            });
    }
});
");
?>


