<?php
/**
 * Jalali Date Helper
 * Uses gmdate() for timezone-safe output.
 *
 * @package PersianOfficeAutomation\Common
 * @phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date -- Use gmdate() throughout.
 */

namespace PersianOfficeAutomation\Common;

/**
 * Persian (Jalali) Date Converter
 */
class JalaliDate {
    
    /**
     * Convert Gregorian to Jalali
     * 
     * @param string $date Date in any format
     * @param string $format Output format (default: Y/m/d)
     * @return string Jalali date
     */
    public static function toJalali($date, $format = 'Y/m/d') {
        if (empty($date)) {
            return '-';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        return self::jdate($format, $timestamp);
    }
    
    /**
     * Convert Gregorian to Jalali with time
     * 
     * @param string $datetime
     * @return string Jalali datetime
     */
    public static function toJalaliDateTime($datetime) {
        if (empty($datetime)) {
            return '-';
        }
        
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        
        return self::jdate('Y/m/d', $timestamp) . ' ساعت ' . self::jdate('H:i', $timestamp);
    }
    
    /**
     * Get relative time in Persian
     * 
     * @param string $date
     * @return string
     */
    public static function timeAgo($date) {
        if (empty($date)) {
            return '-';
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        $diff = current_time('timestamp') - $timestamp;
        
        if ($diff < 60) {
            return 'همین الان';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' دقیقه پیش';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' ساعت پیش';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' روز پیش';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' هفته پیش';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' ماه پیش';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' سال پیش';
        }
    }
    
    /**
     * Core Jalali date function
     * 
     * @param string $format
     * @param int $timestamp
     * @return string
     */
    public static function jdate($format, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        list($jy, $jm, $jd) = self::gregorianToJalali(
            (int) gmdate( 'Y', $timestamp ),
            (int) gmdate( 'm', $timestamp ),
            (int) gmdate( 'd', $timestamp )
        );
        
        $monthNames = [
            1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد',
            4 => 'تیر', 5 => 'مرداد', 6 => 'شهریور',
            7 => 'مهر', 8 => 'آبان', 9 => 'آذر',
            10 => 'دی', 11 => 'بهمن', 12 => 'اسفند'
        ];
        
        $weekDays = [
            0 => 'یکشنبه', 1 => 'دوشنبه', 2 => 'سه‌شنبه',
            3 => 'چهارشنبه', 4 => 'پنج‌شنبه', 5 => 'جمعه', 6 => 'شنبه'
        ];
        
        $output = '';
        $len = strlen($format);
        
        for ($i = 0; $i < $len; $i++) {
            switch ($format[$i]) {
                case 'Y':
                    $output .= $jy;
                    break;
                case 'y':
                    $output .= substr($jy, 2);
                    break;
                case 'm':
                    $output .= str_pad($jm, 2, '0', STR_PAD_LEFT);
                    break;
                case 'n':
                    $output .= $jm;
                    break;
                case 'd':
                    $output .= str_pad($jd, 2, '0', STR_PAD_LEFT);
                    break;
                case 'j':
                    $output .= $jd;
                    break;
                case 'F':
                    $output .= $monthNames[$jm];
                    break;
                case 'M':
                    $output .= substr($monthNames[$jm], 0, 3);
                    break;
                case 'l':
                    $output .= $weekDays[ (int) gmdate( 'w', $timestamp ) ];
                    break;
                case 'D':
                    $dayName = $weekDays[ (int) gmdate( 'w', $timestamp ) ];
                    $output .= mb_substr( $dayName, 0, 2 );
                    break;
                case 'H':
                    $output .= gmdate( 'H', $timestamp );
                    break;
                case 'h':
                    $output .= gmdate( 'h', $timestamp );
                    break;
                case 'i':
                    $output .= gmdate( 'i', $timestamp );
                    break;
                case 's':
                    $output .= gmdate( 's', $timestamp );
                    break;
                case 'A':
                    $output .= ( gmdate( 'A', $timestamp ) === 'AM' ) ? 'قبل از ظهر' : 'بعد از ظهر';
                    break;
                case 'a':
                    $output .= ( gmdate( 'a', $timestamp ) === 'am' ) ? 'ق.ظ' : 'ب.ظ';
                    break;
                default:
                    $output .= $format[$i];
            }
        }
        
        return self::convertNumbers($output);
    }
    
    /**
     * Convert Gregorian to Jalali
     * 
     * @param int $g_y Gregorian year
     * @param int $g_m Gregorian month
     * @param int $g_d Gregorian day
     * @return array [year, month, day]
     */
    public static function gregorianToJalali($g_y, $g_m, $g_d) {
        $g_y = (int)$g_y; $g_m = (int)$g_m; $g_d = (int)$g_d;
        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        $j_days_in_month = [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30, 29];

        $gy = $g_y - 1600;
        $gm = $g_m - 1;
        $gd = $g_d - 1;

        $g_day_no = 365 * $gy + (int)(($gy + 3) / 4) - (int)(($gy + 99) / 100) + (int)(($gy + 399) / 400);

        for ($i = 0; $i < $gm; ++$i)
            $g_day_no += $g_days_in_month[$i] + ($i == 1 && (($g_y % 4 == 0 && $g_y % 100 != 0) || ($g_y % 400 == 0)));

        $g_day_no += $gd;

        $j_day_no = $g_day_no - 79;

        $j_np = (int)($j_day_no / 12053);
        $j_day_no = $j_day_no % 12053;

        $jy = 979 + 33 * $j_np + 4 * (int)($j_day_no / 1461);

        $j_day_no %= 1461;

        if ($j_day_no >= 366) {
            $jy += (int)(($j_day_no - 1) / 365);
            $j_day_no = ($j_day_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_day_no >= $j_days_in_month[$i]; ++$i)
            $j_day_no -= $j_days_in_month[$i];

        return [$jy, $i + 1, $j_day_no + 1];
    }
    
    /**
     * Convert English numbers to Persian
     * 
     * @param string $string
     * @return string
     */
    public static function convertNumbers($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($english, $persian, $string);
    }
    
    /**
     * Convert Persian numbers to English
     * 
     * @param string $string
     * @return string
     */
    public static function toEnglishNumbers($string) {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        return str_replace($persian, $english, $string);
    }
    
    /**
     * Get current Jalali date (date only)
     *
     * @param string $format Output format (default: Y/m/d)
     * @return string
     */
    public static function today($format = 'Y/m/d') {
        return self::jdate($format, current_time('timestamp'));
    }

    /**
     * Get current Jalali date and time
     *
     * @param string $format
     * @return string
     */
    public static function now($format = 'Y/m/d H:i') {
        return self::jdate($format, current_time('timestamp'));
    }
    
    /**
     * Convert Jalali to Gregorian
     * 
     * @param int $j_y Jalali year
     * @param int $j_m Jalali month
     * @param int $j_d Jalali day
     * @return array [year, month, day]
     */
    public static function jalaliToGregorian($j_y, $j_m, $j_d) {
        $j_y = (int)$j_y; $j_m = (int)$j_m; $j_d = (int)$j_d;
        $jy = $j_y - 979;
        $jm = $j_m - 1;
        $jd = $j_d - 1;

        $j_day_no = 365 * $jy + (int)($jy / 33) * 8 + (int)(($jy % 33 + 3) / 4);
        for ($i = 0; $i < $jm; ++$i)
            $j_day_no += ($i < 6) ? 31 : 30;

        $j_day_no += $jd;

        $g_day_no = $j_day_no + 79;

        $gy = 1600 + 400 * (int)($g_day_no / 146097);
        $g_day_no = $g_day_no % 146097;

        $leap = true;
        if ($g_day_no >= 36525) {
            $g_day_no--;
            $gy += 100 * (int)($g_day_no / 36524);
            $g_day_no = $g_day_no % 36524;

            if ($g_day_no >= 365)
                $g_day_no++;
            else
                $leap = false;
        }

        $gy += 4 * (int)($g_day_no / 1461);
        $g_day_no %= 1461;

        if ($g_day_no >= 366) {
            $leap = false;
            $g_day_no--;
            $gy += (int)($g_day_no / 365);
            $g_day_no = $g_day_no % 365;
        }

        $g_days_in_month = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        for ($i = 0; $g_day_no >= ($g_days_in_month[$i] + ($i == 1 && $leap)); $i++)
            $g_day_no -= $g_days_in_month[$i] + ($i == 1 && $leap);

        return [$gy, $i + 1, $g_day_no + 1];
    }
    
    /**
     * Convert Jalali date string to Gregorian date string
     * 
     * @param string $jalaliDate Date in format YYYY/MM/DD or YYYY-MM-DD
     * @return string Gregorian date in YYYY-MM-DD format
     */
    public static function jalaliToGregorianString($jalaliDate) {
        // Parse Jalali date
        $jalaliDate = str_replace(['/', '-'], ' ', $jalaliDate);
        $parts = array_filter(explode(' ', $jalaliDate));
        
        if ( count( $parts ) !== 3 ) {
            return gmdate( 'Y-m-d' );
        }
        
        // Convert Persian numbers to English
        $persianNumbers = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $englishNumbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        
        $jy = (int)str_replace($persianNumbers, $englishNumbers, $parts[0]);
        $jm = (int)str_replace($persianNumbers, $englishNumbers, $parts[1]);
        $jd = (int)str_replace($persianNumbers, $englishNumbers, $parts[2]);
        
        list($gy, $gm, $gd) = self::jalaliToGregorian($jy, $jm, $jd);
        
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
    
    /**
     * Format date for display
     * 
     * @param string $date
     * @param string $type 'full', 'date', 'time', 'datetime'
     * @return string
     */
    public static function format($date, $type = 'date') {
        if (empty($date) || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        
        // Check if date is already Jalali (starts with 13 or 14)
        // This handles cases where Jalali date was stored directly in DB
        if (is_string($date) && preg_match('/^1[34][0-9]{2}[\/\-]/', $date)) {
            if ($type === 'date') {
                return str_replace('-', '/', substr($date, 0, 10));
            }
            return $date;
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        
        if ($timestamp === false) {
            return '-';
        }
        
        switch ($type) {
            case 'full':
                return self::jdate('l، j F Y ساعت H:i', $timestamp);
            case 'date':
                return self::jdate('Y/m/d', $timestamp);
            case 'time':
            case 'H:i':
                return self::jdate('H:i', $timestamp);
            case 'datetime':
                return self::jdate('Y/m/d H:i', $timestamp);
            case 'monthyear':
                return self::jdate('F Y', $timestamp);
            case 'd':
                return self::jdate('d', $timestamp);
            case 'F':
                return self::jdate('F', $timestamp);
            default:
                return self::jdate('Y/m/d', $timestamp);
        }
    }

    /**
     * Get days in a Jalali month
     * 
     * @param int $y Jalali year
     * @param int $m Jalali month
     * @return int
     */
    public static function getDaysInJalaliMonth($y, $m) {
        if ($m <= 6) return 31;
        if ($m <= 11) return 30;
        // Check leap year for Esfand (12)
        return self::isJalaliLeapYear($y) ? 30 : 29;
    }

    /**
     * Check if a Jalali year is leap
     * 
     * @param int $y
     * @return bool
     */
    public static function isJalaliLeapYear($y) {
        list($gy, $gm, $gd) = self::jalaliToGregorian($y, 12, 30);
        list($jy, $jm, $jd) = self::gregorianToJalali($gy, $gm, $gd);
        return ($jy == $y && $jm == 12 && $jd == 30);
    }
    
    /**
     * Get start day of week for a Jalali month (0=Sat, ..., 6=Fri)
     * 
     * @param int $y
     * @param int $m
     * @return int
     */
    public static function getFirstDayOfWeek($y, $m) {
        list($gy, $gm, $gd) = self::jalaliToGregorian($y, $m, 1);
        $timestamp = strtotime("$gy-$gm-$gd");
        $w = (int) gmdate( 'w', $timestamp ); // 0=Sun, 6=Sat
        // Map to 0=Sat, 1=Sun, ..., 6=Fri
        // Sat(6)->0, Sun(0)->1, Mon(1)->2, ... Fri(5)->6
        return ($w + 1) % 7;
    }
}

