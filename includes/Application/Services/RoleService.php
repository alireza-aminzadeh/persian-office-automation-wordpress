<?php
/**
 * Role Service - Create and manage roles
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Application\Services;

class RoleService {
    
    /**
     * Create default roles and capabilities
     */
    public static function createDefaultRoles() {
        // Remove default subscriber capabilities from all users
        $subscriber = get_role('subscriber');
        
        // Office Manager Role
        $manager = add_role('persian_oa_manager', 'مدیر دبیرخانه', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'persian_oa_manage_all_letters' => true,
            'persian_oa_create_letter' => true,
            'persian_oa_edit_letter' => true,
            'persian_oa_delete_letter' => true,
            'persian_oa_view_letter' => true,
            'persian_oa_manage_settings' => true,
            'persian_oa_manage_users' => true,
        ]);
        
        // If role already exists, update capabilities
        if (!$manager) {
            $manager = get_role('persian_oa_manager');
            if ($manager) {
                $manager->add_cap('persian_oa_manage_all_letters');
                $manager->add_cap('persian_oa_create_letter');
                $manager->add_cap('persian_oa_edit_letter');
                $manager->add_cap('persian_oa_delete_letter');
                $manager->add_cap('persian_oa_view_letter');
                $manager->add_cap('persian_oa_manage_settings');
                $manager->add_cap('persian_oa_manage_users');
            }
        }
        
        // Office Staff Role
        $staff = add_role('persian_oa_staff', 'کارشناس دبیرخانه', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'persian_oa_create_letter' => true,
            'persian_oa_edit_letter' => true,
            'persian_oa_view_letter' => true,
        ]);
        
        // If role already exists, update capabilities
        if (!$staff) {
            $staff = get_role('persian_oa_staff');
            if ($staff) {
                $staff->add_cap('persian_oa_create_letter');
                $staff->add_cap('persian_oa_edit_letter');
                $staff->add_cap('persian_oa_view_letter');
            }
        }
        
        // Office User Role (Read Only)
        $user = add_role('persian_oa_user', 'کاربر دبیرخانه', [
            'read' => true,
            'edit_posts' => false,
            'delete_posts' => false,
            'persian_oa_view_letter' => true,
        ]);
        
        // If role already exists, update capabilities
        if (!$user) {
            $user = get_role('persian_oa_user');
            if ($user) {
                $user->add_cap('persian_oa_view_letter');
            }
        }
        
        // Add capabilities to administrator (ALWAYS update, even if exists)
        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap('persian_oa_manage_all_letters');
            $admin->add_cap('persian_oa_create_letter');
            $admin->add_cap('persian_oa_edit_letter');
            $admin->add_cap('persian_oa_delete_letter');
            $admin->add_cap('persian_oa_view_letter');
            $admin->add_cap('persian_oa_manage_settings');
            $admin->add_cap('persian_oa_manage_users');
        }
    }
    
    /**
     * Remove custom roles
     */
    public static function removeRoles() {
        remove_role('persian_oa_manager');
        remove_role('persian_oa_staff');
        remove_role('persian_oa_user');
    }
}





