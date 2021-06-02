<?php

class HIF_Menu {
    /**
     * Register admin menu items.
     * 
     * @return void
     */
    public static function register_menu() {
        global $menu, $submenu;

        add_submenu_page(
            'options-general.php',
            __('Firebase Cloud Messaging', 'hi-fcm'),
            __('Firebase Cloud Messaging', 'hi-fcm'),
            'manage_options',
            'hi_fcm',
            'HIF_Pages::dashboard'
        );
    }
}