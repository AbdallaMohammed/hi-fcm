<?php

class HIF_Pages {
    /**
     * Dashboard main page.
     * 
     * @return void
     */
    public static function dashboard() {
        global $hi_fcm;
        
        $post_types = hi_fcm_post_types();

        ob_start();
        require_once HI_FCM_DIR . '/templates/page-dashboard.php';
        echo ob_get_clean();
    }
}