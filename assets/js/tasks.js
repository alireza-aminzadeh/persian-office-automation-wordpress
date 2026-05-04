/**
 * Task Management JavaScript
 * Features: Persistent Timer, Linked Status Actions, Checklist, Comments
 */
jQuery(document).ready(function($) {
    // --- Configuration & Init ---
    if (typeof oaTaskData === 'undefined') {
        // Only run logic if oaTaskData is present (Task View/Edit)
        // Or if we are on list/create page which might not have oaTaskData fully populated or different
        // But we moved general logic here too.
        
        // If not on task view/edit, just run general handlers if elements exist
        
        // Recurring Checkbox
        $('#persian-oa-recurring-check').change(function() {
            if($(this).is(':checked')) {
                $('#persian-oa-recurring-pattern').slideDown();
            } else {
                $('#persian-oa-recurring-pattern').slideUp();
            }
        });

        // Search
        $('#persian-oa-task-search-list').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('.persian-oa-task-item').filter(function() {
                var text = $(this).find('.persian-oa-task-title').text().toLowerCase();
                $(this).toggle(text.indexOf(value) > -1);
            });
        });

        // Datepickers for create/edit
        if (typeof SimplePersianDatePicker !== 'undefined') {
            if (document.getElementById('deadline-jalali')) {
                new SimplePersianDatePicker(
                    document.getElementById('deadline-jalali'),
                    document.getElementById('deadline-gregorian'),
                    { defaultToday: false }
                );
            }
            if (document.getElementById('start-date-jalali')) {
                new SimplePersianDatePicker(
                    document.getElementById('start-date-jalali'),
                    document.getElementById('start-date-gregorian'),
                    { defaultToday: false }
                );
            }
        }

        return; 
    }

    const taskId = oaTaskData.id;
    const taskStatus = oaTaskData.status;
    const nonce = oaTaskData.nonce;
    const ajaxUrl = oaTaskData.ajaxUrl || window.ajaxurl;

    // Initialize Persian Date Pickers if present
    if (typeof SimplePersianDatePicker !== 'undefined') {
        // Create Task Page
        if (document.getElementById('deadline-jalali')) {
            new SimplePersianDatePicker(
                document.getElementById('deadline-jalali'),
                document.getElementById('deadline-gregorian'),
                { defaultToday: false }
            );
        }
        if (document.getElementById('start-date-jalali')) {
            new SimplePersianDatePicker(
                document.getElementById('start-date-jalali'),
                document.getElementById('start-date-gregorian'),
                { defaultToday: false }
            );
        }
    }

    // Recurring Checkbox Logic
    $('#persian-oa-recurring-check').change(function() {
        if($(this).is(':checked')) {
            $('#persian-oa-recurring-pattern').slideDown();
        } else {
            $('#persian-oa-recurring-pattern').slideUp();
        }
    });

    const STORAGE_KEY = `persian_oa_timer_start_${taskId}`;
    let timerInterval;

    // Initialize
    initTimer();

    // --- Timer Logic (Persistent) ---
    
    function initTimer() {
        // Check if task is completed, if so, clear any running timer
        if (taskStatus === 'completed') {
            localStorage.removeItem(STORAGE_KEY);
            return;
        }

        const storedStart = localStorage.getItem(STORAGE_KEY);
        if (storedStart) {
            // Resume timer
            runTimerDisplay(parseInt(storedStart));
            showStopButton();
        }
    }

    function startTimer() {
        const startTime = Date.now();
        localStorage.setItem(STORAGE_KEY, startTime);
        runTimerDisplay(startTime);
        showStopButton();
    }

    function stopTimer() {
        const storedStart = localStorage.getItem(STORAGE_KEY);
        if (!storedStart) return 0;

        clearInterval(timerInterval);
        localStorage.removeItem(STORAGE_KEY);
        
        showPlayButton();
        $('#persian-oa-timer-display').text("00:00:00");
        $('.persian-oa-timer-widget').css('border-color', 'var(--persian-oa-gray-200)');

        // Calculate duration in seconds
        const endTime = Date.now();
        return Math.floor((endTime - parseInt(storedStart)) / 1000);
    }

    function runTimerDisplay(startTime) {
        // Clear existing to avoid duplicates
        if (timerInterval) clearInterval(timerInterval);

        showStopButton(); // Ensure UI is correct
        
        // Update immediately
        updateDisplay();

        timerInterval = setInterval(updateDisplay, 1000);

        function updateDisplay() {
            const now = Date.now();
            const diff = Math.floor((now - startTime) / 1000);
            
            // Format HH:MM:SS
            const date = new Date(0);
            date.setSeconds(diff);
            const timeString = date.toISOString().substr(11, 8);
            $('#persian-oa-timer-display').text(timeString);
        }
    }

    function showStopButton() {
        $('#persian-oa-btn-start-timer').hide();
        $('#persian-oa-btn-stop-timer').show();
        $('.persian-oa-timer-widget').css('border-color', 'var(--persian-oa-success)');
    }

    function showPlayButton() {
        $('#persian-oa-btn-stop-timer').hide();
        $('#persian-oa-btn-start-timer').show();
    }

    // --- UI Event Handlers ---

    // 1. Timer Buttons
    $('#persian-oa-btn-start-timer').click(function() {
        startTimer();
    });

    $('#persian-oa-btn-stop-timer').click(function() {
        const durationSeconds = stopTimer();
        openManualTimeModal(durationSeconds);
    });

    // 2. Status Buttons (Linked to Timer)
    $('.persian-oa-status-btn').click(function(e) {
        e.preventDefault();
        const newStatus = $(this).data('status');
        
        if(!confirm('آیا مطمئن هستید؟')) return;

        // Intelligent Linking
        if (newStatus === 'in_progress') {
            // Auto-start timer if not running
            if (!localStorage.getItem(STORAGE_KEY)) {
                startTimer();
            }
        } else if (newStatus === 'completed') {
            // Auto-stop timer if running (cleanup)
            if (localStorage.getItem(STORAGE_KEY)) {
                // Optional: We could prompt to log time here, 
                // but for now we just stop it to prevent "ghost" timers on completed tasks.
                stopTimer(); 
            }
        }

        updateStatus(newStatus);
    });

    // 3. Status Select
    $('#persian-oa-status-select').change(function() {
        const newStatus = $(this).val();
        if(!confirm('آیا از تغییر وضعیت اطمینان دارید؟')) {
            location.reload();
            return;
        }
        updateStatus(newStatus);
    });

    // --- Core Actions ---

    function updateStatus(status) {
        $.post(ajaxUrl, {
            action: 'persian_oa_task_update_status',
            task_id: taskId,
            status: status,
            nonce: nonce
        }, function(res) {
            if(res.success) {
                location.reload();
            } else {
                alert(res.message || 'خطا در تغییر وضعیت');
            }
        }).fail(function() {
            alert('خطا در ارتباط با سرور');
        });
    }

    function openManualTimeModal(durationSeconds = 0) {
        const now = new Date();
        const endStr = toLocalISOString(now).slice(0, 16);
        const startStr = toLocalISOString(new Date(now.getTime() - durationSeconds * 1000)).slice(0, 16);
        
        $('#persian-oa-time-start').val(startStr);
        $('#persian-oa-time-end').val(endStr);
        $('#persian-oa-manual-time-modal').fadeIn();
    }

    // --- Helper: Date Formatting ---
    function toLocalISOString(date) {
        const offset = date.getTimezoneOffset() * 60000;
        const localISOTime = (new Date(date.getTime() - offset)).toISOString().slice(0, -1);
        return localISOTime;
    }

    // --- Other Modules (Checklist, Comments, Description, etc.) ---
    
    // Tabs
    $('.persian-oa-tab-btn').click(function(e) {
        e.preventDefault();
        $('.persian-oa-tab-btn').removeClass('active');
        $(this).addClass('active');
        $('.persian-oa-tab-content').removeClass('active');
        $('#tab-' + $(this).data('tab')).addClass('active');
    });

    // Description Editing
    $('#persian-oa-edit-desc-btn').click(function() {
        $('#persian-oa-desc-content').hide();
        $('#persian-oa-desc-edit-container').fadeIn();
        $(this).hide();
    });

    $('#persian-oa-cancel-desc-btn').click(function() {
        $('#persian-oa-desc-edit-container').hide();
        $('#persian-oa-desc-content').fadeIn();
        $('#persian-oa-edit-desc-btn').show();
    });

    $('#persian-oa-save-desc-btn').click(function() {
        const newDesc = $('#persian-oa-desc-edit-area').val();
        const $btn = $(this);
        $btn.prop('disabled', true).text('در حال ذخیره...');

        $.post(ajaxUrl, {
            action: 'persian_oa_task_update_description',
            task_id: taskId,
            description: newDesc,
            nonce: nonce
        }, function(res) {
            if(res.success) location.reload();
            else {
                alert(res.message || 'خطا');
                $btn.prop('disabled', false).text('ذخیره');
            }
        });
    });

    // Delete Task
    $('.persian-oa-delete-btn').click(function(e) {
        e.preventDefault();
        if(!confirm('هشدار: حذف غیرقابل بازگشت است.')) return;
        
        $.post(ajaxUrl, {
            action: 'persian_oa_task_delete',
            task_id: taskId,
            nonce: nonce
        }, function(res) {
            if(res.success) window.location.href = 'admin.php?page=persian-oa-tasks';
            else alert(res.message);
        });
    });

    // Checklist Logic
    $(document).on('change', '.persian-oa-checklist-cb', function() {
        const isChecked = $(this).is(':checked');
        $(this).siblings('.persian-oa-text').toggleClass('completed', isChecked);
        updateChecklistProgress();
        saveChecklist();
    });

    $('#persian-oa-add-check-btn').click(addChecklistItem);
    $('#persian-oa-new-checklist-text').keypress(function(e) {
        if(e.which === 13) addChecklistItem();
    });

    function addChecklistItem() {
        const text = $('#persian-oa-new-checklist-text').val().trim();
        if(!text) return;
        
        const html = `
            <li class="persian-oa-checklist-row">
                <label class="persian-oa-checkbox-container">
                    <input type="checkbox" class="persian-oa-checklist-cb">
                    <span class="persian-oa-checkmark"></span>
                    <span class="persian-oa-text">${$('<div>').text(text).html()}</span>
                </label>
            </li>`;
            
        $('#persian-oa-checklist-items').append(html);
        $('#persian-oa-new-checklist-text').val('');
        updateChecklistProgress();
        saveChecklist();
    }
    
    function updateChecklistProgress() {
        const total = $('.persian-oa-checklist-cb').length;
        const checked = $('.persian-oa-checklist-cb:checked').length;
        $('.persian-oa-badge-counter').text(`${checked} / ${total}`);
    }

    function saveChecklist() {
        const items = [];
        $('#persian-oa-checklist-items li').each(function() {
            items.push({
                text: $(this).find('.persian-oa-text').text().trim(),
                checked: $(this).find('input').is(':checked')
            });
        });

        $.post(ajaxUrl, {
            action: 'persian_oa_task_update_checklist',
            task_id: taskId,
            checklist: JSON.stringify(items),
            nonce: nonce
        });
    }

    // Manual Time Log Submit
    $('#persian-oa-submit-time-manual').click(function() {
        const start = $('#persian-oa-time-start').val();
        const end = $('#persian-oa-time-end').val();
        const desc = $('#persian-oa-time-desc').val();
        
        if(!start || !end) return alert('زمان را وارد کنید');
        
        $.post(ajaxUrl, {
            action: 'persian_oa_task_add_time_log',
            task_id: taskId,
            start_time: start,
            end_time: end,
            description: desc,
            nonce: nonce
        }, function(res) {
            if(res.success) location.reload();
            else alert(res.message || 'خطا');
        });
    });
    
    // Simple client-side search for list view
    $('#persian-oa-task-search-list').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.persian-oa-task-item').filter(function() {
            var text = $(this).find('.persian-oa-task-title').text().toLowerCase();
            $(this).toggle(text.indexOf(value) > -1);
        });
    });
    
    // Close Modal
    $('.close-modal').click(function() {
        $(this).closest('.persian-oa-modal-overlay').fadeOut();
    });

    // Comments & Uploads
    $('#persian-oa-upload-trigger').click(function(e) {
        e.preventDefault();
        if (typeof wp === 'undefined' || !wp.media) return alert('آپلودر یافت نشد');

        var mediaUploader = wp.media({
            title: 'انتخاب فایل پیوست',
            button: { text: 'انتخاب فایل' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#persian-oa-comment-attachment-url').val(attachment.url);
            $('#persian-oa-attachment-name').text(attachment.filename);
        });
        mediaUploader.open();
    });

    $('#persian-oa-submit-comment').click(function(e) {
        e.preventDefault();
        const text = $('#persian-oa-new-comment').val();
        const att = $('#persian-oa-comment-attachment-url').val();
        
        if(!text && !att) return;
        
        const $btn = $(this);
        $btn.prop('disabled', true).text('در حال ارسال...');
        
        $.post(ajaxUrl, {
            action: 'persian_oa_task_add_comment',
            task_id: taskId,
            comment: text,
            attachment: att,
            nonce: nonce
        }, function(res) {
             if (res.success) location.reload(); 
             else {
                 alert('خطا');
                 $btn.prop('disabled', false).text('ارسال');
             }
        });
    });
});
