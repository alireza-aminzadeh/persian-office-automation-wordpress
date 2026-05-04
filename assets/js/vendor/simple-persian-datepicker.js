/**
 * Simple Persian (Jalali) Date Picker
 * Pure JavaScript - Zero Dependencies
 * By: Alireza Aminzadeh - ariacoder.ir
 */

(function(window) {
    'use strict';
    
    // Jalali Calendar Helper
    var JalaliCalendar = {
        monthNames: ['فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'],
        dayNames: ['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'],
        
        getDaysInMonth: function(year, month) {
            if (month <= 6) return 31;
            if (month <= 11) return 30;
            return this.isLeapYear(year) ? 30 : 29;
        },
        
        isLeapYear: function(year) {
            var a = year - 474;
            var b = a % 128;
            return ((b * 25) + 38) % 33 < 25;
        },
        
        getFirstDayOfMonth: function(jYear, jMonth) {
            var gregorian = this.toGregorian(jYear, jMonth, 1);
            var date = new Date(gregorian[0], gregorian[1] - 1, gregorian[2]);
            return (date.getDay() + 1) % 7;
        },
        
        toGregorian: function(jy, jm, jd) {
            var gy, gm, gd, days;
            jy += 1595;
            days = -355668 + (365 * jy) + (~~(jy / 33) * 8) + ~~(((jy % 33) + 3) / 4) + jd + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30 + 186));
            gy = 400 * ~~(days / 146097);
            days %= 146097;
            if (days > 36524) {
                days--;
                gy += 100 * ~~(days / 36524);
                days %= 36524;
                if (days >= 365) days++;
            }
            gy += 4 * ~~(days / 1461);
            days %= 1461;
            if (days > 365) {
                gy += ~~((days - 1) / 365);
                days = (days - 1) % 365;
            }
            gd = days + 1;
            var sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            for (gm = 0; gm < 13 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
            return [gy, gm, gd];
        },
        
        toJalali: function(gy, gm, gd) {
            var g_d_m, jy, jm, jd, gy2, days;
            g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
            gy2 = (gm > 2) ? (gy + 1) : gy;
            days = 355666 + (365 * gy) + ~~((gy2 + 3) / 4) - ~~((gy2 + 99) / 100) + ~~((gy2 + 399) / 400) + gd + g_d_m[gm - 1];
            jy = -1595 + (33 * ~~(days / 12053));
            days %= 12053;
            jy += 4 * ~~(days / 1461);
            days %= 1461;
            if (days > 365) {
                jy += ~~((days - 1) / 365);
                days = (days - 1) % 365;
            }
            if (days < 186) {
                jm = 1 + ~~(days / 31);
                jd = 1 + (days % 31);
            } else {
                jm = 7 + ~~((days - 186) / 30);
                jd = 1 + ((days - 186) % 30);
            }
            return [jy, jm, jd];
        },
        
        format: function(y, m, d) {
            return y + '/' + (m < 10 ? '0' + m : m) + '/' + (d < 10 ? '0' + d : d);
        }
    };
    
    // Simple Date Picker Constructor
    window.SimplePersianDatePicker = function(inputElement, hiddenElement, options) {
        this.input = inputElement;
        this.hidden = hiddenElement;
        this.options = options || {};
        
        var today = new Date();
        var jalaliToday = JalaliCalendar.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
        this.currentYear = jalaliToday[0];
        this.currentMonth = jalaliToday[1];
        this.selectedDate = null;
        
        this.init();
    };
    
    SimplePersianDatePicker.prototype = {
        init: function() {
            var self = this;
            this.input.readOnly = true;
            this.input.style.cursor = 'pointer';
            this.createCalendar();
            
            this.input.addEventListener('click', function(e) {
                e.stopPropagation();
                self.show();
            });
            
            document.addEventListener('click', function(e) {
                if (!self.calendar.contains(e.target) && e.target !== self.input) {
                    self.hide();
                }
            });
            
            // Helper to set today
            var setToday = function() {
                var today = new Date();
                var jalali = JalaliCalendar.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
                self.input.value = JalaliCalendar.format(jalali[0], jalali[1], jalali[2]);
                var gregorian = JalaliCalendar.toGregorian(jalali[0], jalali[1], jalali[2]);
                self.hidden.value = gregorian[0] + '-' + (gregorian[1] < 10 ? '0' + gregorian[1] : gregorian[1]) + '-' + (gregorian[2] < 10 ? '0' + gregorian[2] : gregorian[2]);
                self.selectedDate = { year: jalali[0], month: jalali[1], day: jalali[2] };
            };

            // Set initial value if exists
            if (this.hidden.value) {
                var parts = this.hidden.value.split('-');
                if (parts.length === 3) {
                    var y = parseInt(parts[0]);
                    var m = parseInt(parts[1]);
                    var d = parseInt(parts[2]);
                    
                    if (isNaN(y) || isNaN(m) || isNaN(d) || y <= 0) {
                        if (this.options.defaultToday) setToday();
                        else { this.input.value = ''; this.selectedDate = null; }
                    } else if (y < 1800) {
                        // Jalali: Check validity (1300-1500)
                        if (y < 1300 || y > 1500) {
                            if (this.options.defaultToday) setToday();
                            else { this.input.value = ''; this.selectedDate = null; }
                        } else {
                            this.input.value = JalaliCalendar.format(y, m, d);
                            this.selectedDate = { year: y, month: m, day: d };
                            // Fix hidden value to be Gregorian for consistency
                            var g = JalaliCalendar.toGregorian(y, m, d);
                            this.hidden.value = g[0] + '-' + (g[1] < 10 ? '0' + g[1] : g[1]) + '-' + (g[2] < 10 ? '0' + g[2] : g[2]);
                        }
                    } else {
                        // Gregorian
                        var jalali = JalaliCalendar.toJalali(y, m, d);
                        // Check if converted Jalali year is reasonable
                         if (jalali[0] < 1300 || jalali[0] > 1500) {
                             if (this.options.defaultToday) setToday();
                             else { this.input.value = ''; this.selectedDate = null; }
                        } else {
                            this.input.value = JalaliCalendar.format(jalali[0], jalali[1], jalali[2]);
                            this.selectedDate = { year: jalali[0], month: jalali[1], day: jalali[2] };
                        }
                    }
                } else if (this.options.defaultToday) {
                    setToday();
                }
            } else if (this.options.defaultToday) {
                setToday();
            }
        },
        
        createCalendar: function() {
            this.calendar = document.createElement('div');
            this.calendar.className = 'simple-persian-calendar';
            this.calendar.style.cssText = 'position:absolute;display:none;background:#fff;border:1px solid #ddd;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.15);z-index:99999;padding:16px;font-family:Vazirmatn,Tahoma,sans-serif;direction:rtl;min-width:320px;';
            document.body.appendChild(this.calendar);
        },
        
        render: function() {
            var html = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;padding-bottom:12px;border-bottom:2px solid #eee;">';
            html += '<button type="button" class="prev-btn" style="background:#6366f1;color:#fff;border:none;border-radius:8px;width:36px;height:36px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;">❮</button>';
            html += '<div style="font-weight:700;font-size:16px;color:#1f2937;">' + JalaliCalendar.monthNames[this.currentMonth - 1] + ' ' + this.currentYear + '</div>';
            html += '<button type="button" class="next-btn" style="background:#6366f1;color:#fff;border:none;border-radius:8px;width:36px;height:36px;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;">❯</button>';
            html += '</div>';
            
            html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:8px;">';
            for (var i = 0; i < 7; i++) {
                html += '<div style="text-align:center;font-weight:600;color:#6b7280;font-size:13px;padding:8px 0;">' + JalaliCalendar.dayNames[i] + '</div>';
            }
            html += '</div>';
            
            html += '<div style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;">';
            var daysInMonth = JalaliCalendar.getDaysInMonth(this.currentYear, this.currentMonth);
            var firstDay = JalaliCalendar.getFirstDayOfMonth(this.currentYear, this.currentMonth);
            var today = new Date();
            var todayJalali = JalaliCalendar.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
            
            for (var i = 0; i < firstDay; i++) html += '<div></div>';
            
            for (var day = 1; day <= daysInMonth; day++) {
                var isToday = (this.currentYear === todayJalali[0] && this.currentMonth === todayJalali[1] && day === todayJalali[2]);
                var isSelected = this.selectedDate && (this.currentYear === this.selectedDate.year && this.currentMonth === this.selectedDate.month && day === this.selectedDate.day);
                var bgColor = isSelected ? '#6366f1' : (isToday ? '#dbeafe' : '#f9fafb');
                var textColor = isSelected ? '#fff' : (isToday ? '#1e40af' : '#374151');
                html += '<button type="button" class="day-btn" data-day="' + day + '" style="background:' + bgColor + ';color:' + textColor + ';border:none;border-radius:8px;padding:10px 4px;cursor:pointer;font-size:14px;font-weight:' + (isSelected || isToday ? '700' : '400') + ';transition:all 0.2s;">' + day + '</button>';
            }
            html += '</div>';
            
            html += '<div style="margin-top:16px;padding-top:12px;border-top:1px solid #eee;text-align:center;">';
            html += '<button type="button" class="today-btn" style="background:#10b981;color:#fff;border:none;border-radius:8px;padding:10px 20px;cursor:pointer;font-weight:600;font-size:14px;width:100%;">امروز</button>';
            html += '</div>';
            
            this.calendar.innerHTML = html;
            this.attachEvents();
        },
        
        attachEvents: function() {
            var self = this;
            this.calendar.querySelector('.prev-btn').onclick = function(e) { 
                if(e) e.stopPropagation();
                self.prevMonth(); 
            };
            this.calendar.querySelector('.next-btn').onclick = function(e) { 
                if(e) e.stopPropagation();
                self.nextMonth(); 
            };
            this.calendar.querySelector('.today-btn').onclick = function() { self.selectToday(); };
            
            var dayBtns = this.calendar.querySelectorAll('.day-btn');
            dayBtns.forEach(function(btn) {
                btn.onmouseover = function() {
                    if (this.style.background !== 'rgb(99, 102, 241)') {
                        this.style.background = '#6366f1'; this.style.color = '#fff'; this.style.transform = 'scale(1.05)';
                    }
                };
                btn.onmouseout = function() {
                    var day = parseInt(this.getAttribute('data-day'));
                    var isSelected = self.selectedDate && (self.currentYear === self.selectedDate.year && self.currentMonth === self.selectedDate.month && day === self.selectedDate.day);
                    if (!isSelected) {
                        var today = new Date();
                        var todayJ = JalaliCalendar.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
                        var isToday = (self.currentYear === todayJ[0] && self.currentMonth === todayJ[1] && day === todayJ[2]);
                        this.style.background = isToday ? '#dbeafe' : '#f9fafb';
                        this.style.color = isToday ? '#1e40af' : '#374151';
                        this.style.transform = 'scale(1)';
                    }
                };
                btn.onclick = function() {
                    var day = parseInt(this.getAttribute('data-day'));
                    self.selectDate(self.currentYear, self.currentMonth, day);
                };
            });
        },
        
        selectDate: function(year, month, day) {
            this.selectedDate = { year: year, month: month, day: day };
            this.input.value = JalaliCalendar.format(year, month, day);
            var gregorian = JalaliCalendar.toGregorian(year, month, day);
            this.hidden.value = gregorian[0] + '-' + (gregorian[1] < 10 ? '0' + gregorian[1] : gregorian[1]) + '-' + (gregorian[2] < 10 ? '0' + gregorian[2] : gregorian[2]);
            this.hide();
            if (this.options.onSelect) this.options.onSelect(this.input.value, this.hidden.value);
        },
        
        selectToday: function() {
            var today = new Date();
            var jalali = JalaliCalendar.toJalali(today.getFullYear(), today.getMonth() + 1, today.getDate());
            this.currentYear = jalali[0];
            this.currentMonth = jalali[1];
            this.selectDate(jalali[0], jalali[1], jalali[2]);
        },
        
        prevMonth: function() {
            this.currentMonth--;
            if (this.currentMonth < 1) { this.currentMonth = 12; this.currentYear--; }
            this.render();
        },
        
        nextMonth: function() {
            this.currentMonth++;
            if (this.currentMonth > 12) { this.currentMonth = 1; this.currentYear++; }
            this.render();
        },
        
        show: function() {
            this.render();
            
            // Prepare for measurement
            this.calendar.style.visibility = 'hidden';
            this.calendar.style.display = 'block';
            
            var rect = this.input.getBoundingClientRect();
            var calHeight = this.calendar.offsetHeight || 400;
            var spaceBelow = window.innerHeight - rect.bottom;
            var spaceAbove = rect.top;
            var scrollY = window.scrollY || window.pageYOffset || 0;
            var scrollX = window.scrollX || window.pageXOffset || 0;
            
            // Reset positioning
            this.calendar.style.top = '';
            this.calendar.style.bottom = '';
            this.calendar.style.left = '';
            
            // Calculate Top
            var top = rect.bottom + scrollY + 5; // Default downwards
            
            // Check if should open upwards
            if (spaceBelow < calHeight && spaceAbove > calHeight) {
                 top = rect.top + scrollY - calHeight - 5;
            }
            
            this.calendar.style.top = top + 'px';
            this.calendar.style.left = (rect.left + scrollX) + 'px';
            
            this.calendar.style.visibility = 'visible';
        },
        
        hide: function() {
            this.calendar.style.display = 'none';
        }
    };
    
})(window);

