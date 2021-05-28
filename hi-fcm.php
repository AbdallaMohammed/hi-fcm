<?php

/**
 * HI FCM
 *
 * @package   HIFCM
 * @author    AbdallahMohammed
 * @link      https://github.com/AbdallaMohammed/hi-fcm
 * @copyright 2021 by AbdallahMohammed
 *
 * @wordpress-plugin
 * Plugin Name: HI FCM - Firebase Cloud Messaging
 * Plugin URI:  https://github.com/AbdallaMohammed/hi-fcm
 * Description: Advanced notifications using firebase cloud messaging from your WordPress!
 * Version:     1.0.0
 * Author:      AbdallahMohammed
 * Author URI:  https://github.com/AbdallaMohammed
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: hi-fcm
 * Domain Path: /i18n/
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('HIF_Fcm')) {
    /**
     * Main class.
     */
    final class HIF_Fcm {
        /**
         * @var string
         */
        public $version = '1.0.0';

        /**
         * @var string
         */
        public $tokens_table = 'hi_fcm_tokens';

        /**
         * @var HIF_Notifications
         */
        public $notifications = null;

        /**
         * @var HIF_Fcm
         */
        protected static $instance = null;

        /**
         * Main HIF_Fcm Instance.
         * 
         * @static
         * @return HIF_Fcm
         */
        public static function instance() {
            if (is_null(self::$instance)) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * HIF_Fcm Constructor.
         */
        public function __construct() {
            global $wpdb;

            $this->define_constants();
            $this->includes();
            $this->init_hooks();

            $this->tokens_table = $wpdb->prefix . 'hi_fcm_tokens';
            $this->notifications = new HIF_Notifications(
                get_option('hi_fcm_api_key', '')
            );

            do_action('hi_fcm/loaded');
        }

        /**
         * Define HIF_Fcm constants.
         * 
         * @return void.
         */
        public function define_constants() {
            $this->define('HI_FCM_DIR_URL', plugin_dir_url(__FILE__));
            $this->define('HI_FCM_BASENAME', plugin_basename(__FILE__));
            $this->define('HI_FCM_DIR', dirname(__FILE__));
            $this->define('HI_FCM_VERSION', $this->version);
        }

        /**
         * Include required core files.
         * 
         * @return void
         */
        public function includes() {
            require_once 'functions.php';

            if ($this->is_request('admin')) {
                require_once 'includes/class-menu.php';
                require_once 'includes/class-pages.php';
            }

            require_once 'includes/class-notifications.php';
            require_once 'includes/class-rest-endpoints.php';
        }

        /**
         * Init main plugin hooks.
         * 
         * @return void
         */
        public function init_hooks() {
            register_activation_hook(__FILE__, [$this, 'activation_hook']);
            register_deactivation_hook(__FILE__, [$this, 'deactivation_hook']);

            add_action('init', [$this, 'register_taxonomies']);
            add_action('init', [$this, 'register_post_type']);

            if ($this->is_request('admin')) {
                add_filter('manage_hi_fcm_tokens_posts_columns', [$this, 'posts_columns']);

                add_action('manage_hi_fcm_tokens_posts_custom_column', [$this, 'posts_custom_column'], 10, 2);
                add_action('manage_edit-hi_fcm_tokens_sortable_columns', [$this, 'sortable_columns']);

                add_action('admin_init', [$this, 'admin_settings']);
                add_action('admin_init', [$this, 'init_admin_hooks']);
                add_action('add_meta_boxes', [$this, 'add_meta_boxes']);

                add_action('save_post', [$this, 'save_post'], 1, 1);

                add_action('admin_menu', 'HIF_Menu::register_menu');
            }

            add_action('rest_api_init', ['HIF_REST_Endpoints', 'register_endpoints']);
        }

        /**
         * Init admin hooks.
         * 
         * @return void
         */
        public function init_admin_hooks() {
            add_action('delete_post', [$this, 'delete_post']);
        }

        /**
         * Plugin's activation hook.
         * 
         * @return void
         */
        public function activation_hook() {
            global $wpdb;

            $table_name = $this->tokens_table;
            $charset_collate = $wpdb->get_charset_collate();

            if (! function_exists('dbDelta')) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            }

            $sql = "
                CREATE TABLE IF NOT EXISTS `$table_name` (
                    `id` INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `post_ID` INT(6) DEFAULT NULL,
                    `user_ID` INT(6) DEFAULT NULL,
                    `device_token` varchar(255) DEFAULT NULL,
                    `device` varchar(255) DEFAULT NULL,
                    `os_version` varchar(50) DEFAULT NULL,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
                ) $charset_collate
            ";

            dbDelta($sql);
            if ($wpdb->last_error) {
                wp_die(
                    esc_html__('Database error while installing pd Android FCM Plugin: <br><b style="color:red">"' . $wpdb->last_error . '"</b>', 'hi-fcm'),
                    esc_html__('Creating Tables Error', 'hi-fcm'),
                    [
                        'back_link' => true,
                    ]
                );
            }
        }

        /**
         * Plugin's deactivation hook.
         * 
         * @return void
         */
        public function deactivation_hook() {
            
        }

        /**
         * Determine if the request equals to the given type.
         * 
         * @param string $type
         * 
         * @return boolean
         */
        public function is_request($type) {
            switch ($type) {
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined('DOING_AJAX');
                case 'frontend' :
                    return (!is_admin() || defined('DOING_AJAX')) && ! defined('DOING_CRON');
            }
        }

        /**
         * Register admin settings.
         * 
         * @return void
         */
        public function admin_settings() {
            register_setting('hi_fcm_settings_group', 'hi_fcm_api_key');
            register_setting('hi_fcm_settings_group', 'hi_fcm_channel');
            register_setting('hi_fcm_settings_group', 'hi_fcm_sound');

            $post_types = hi_fcm_post_types();
            foreach ($post_types as $post_type) {
                register_setting('hi_fcm_settings_group', 'hi_fcm_post_type_' . $post_type->name);
            }
        }

        /**
         * Register post types.
         * 
         * @return void
         */
        public function register_post_type() {
            if (current_user_can('manage_woocommerce') || current_user_can('activate_plugins')) {
                register_post_type('hi_fcm_tokens', [
                    'labels' => [
                        'name' => esc_html__('FCM Devices', 'hi-fcm'),
                        'singular_name' => esc_html__('Device', 'hi-fcm'),
                        'search_items' => esc_html__('Search For Device', 'hi-fcm'),
                        'menu_name' => esc_html__('FCM Devices', 'hi-fcm'),
                        'all_items' => esc_html__('All Devices', 'hi-fcm'),
                        'not_found' => esc_html__('No Devices Found', 'hi-fcm'),
                        'not_found_in_trash' => esc_html__('No Devices Found In Trash', 'hi-fcm'),
                    ],
                    'show_ui' => true,
                    'show_in_menu' => true,
                    'capability_type' => 'post',
                    'hierarchical' => false,
                    'menu_position' => 27,
                    'public' => false,
                    'has_archive' => false,
                    'publicaly_querable' => false,
                    'query_var' => false,
                    'supports' => false,
                    'menu_icon' => 'dashicons-cloud',
                    'taxonomies' => [
                        'hi_fcm_subscriptions'
                    ],
                    'capabilities' => [
                        'create_posts' => false,
                    ],
                    'map_meta_cap' => true,
                    'show_in_rest' => false,
                ]);
            }
        }

        /**
         * Register taxonomies.
         * 
         * @return void
         */
        public function register_taxonomies() {
            register_taxonomy(
                'hi_fcm_subscriptions',
                [],
                [
                    'labels' => [
                        'name' => esc_html__('Subscriptions', 'hi-fcm'),
                        'singular_name' => esc_html__('Subscription', 'hi-fcm'),
                        'search_items' => esc_html__('Search Subscriptions', 'hi-fcm'),
                        'all_items' => esc_html__('All Subscriptions', 'hi-fcm'),
                        'parent_item' => esc_html__('Parent Subscription', 'hi-fcm'),
                        'parent_item_colon' => esc_html__('Parent Subscription:', 'hi-fcm'),
                        'edit_item' => esc_html__('Edit Subscription', 'hi-fcm'),
                        'update_item' => esc_html__('Update Subscription', 'hi-fcm'),
                        'add_new_item' => esc_html__('Add New Subscription', 'hi-fcm'),
                        'menu_name' => esc_html__('Subscriptions', 'hi-fcm'),
                    ],
                    'hierarchical' => true,
                    'parent_item' => false,
                    'parent_item_colon' => false,
                    'show_ui' => true,
                    'show_admin_column' => true,
                    'show_in_rest' => true,
                    'query_var' => true,
                    'rewrite' => [
                        'slug' => 'hi_fcm_subscriptions'
                    ],
                ]
            );
        }

        /**
         * Edit post columns.
         * 
         * @param array $columns
         * 
         * @return array
         */
        public function posts_columns($columns) {
            $columns = [];
            $columns['cb'] = esc_html__('Multiselect', 'hi-fcm');
            $columns['user_name'] = esc_html__('Username', 'hi-fcm');
            $columns['device_token'] = esc_html__('Device Token', 'hi-fcm');
            $columns['os_version'] = esc_html__('OS Version', 'hi-fcm');
            $columns['taxonomy-hi_fcm_subscriptions'] = esc_html__('Subscribed To', 'hi-fcm');
            $columns['created_at'] = esc_html__('Created at', 'hi-fcm');

            return $columns;
        }

        /**
         * Render data for custom post columns.
         * 
         * @return mixed
         */
        public function posts_custom_column($column, $post_id) {
            if (get_post_type($post_id) !== 'hi_fcm_tokens') {
                return;
            }

            $user = get_user_by('ID', hi_fcm_get_column_data($post_id, 'user_ID'));

            switch ($column) {
                case 'user_name':
                    echo '<a href="' . esc_url(get_edit_profile_url($user->ID, 'admin')) . '">' . esc_attr($user->user_nicename) . '</a>';
                    break;
                
                case 'device_token':
                    $device_token = hi_fcm_get_column_data($post_id, 'device_token');
                    echo '<span title="' . esc_attr($device_token) . '">' . esc_attr(mb_strimwidth($device_token, 0, 40, '...')) . '</span>';
                    break;

                case 'os_version':
                    $os_version = hi_fcm_get_column_data($post_id, 'os_version');
                    echo '<span title="' . esc_attr($os_version) . '">' . esc_attr($os_version) . '</span>';
                    break;

                case 'subscribed':
                    $subscribed = hi_fcm_get_subscribed_term_names($post_id, 'hi_fcm_subscriptions');
                    echo esc_attr($subscribed);
                    break;

                case 'created_at':
                    $created_at = get_the_date(get_option('date_format'), $post_id);
                    echo esc_attr($created_at);
                    break;
            }
        }

        /**
         * Sort cusotm columns.
         * 
         * @param array $columns
         * 
         * @return array
         */
        public function sortable_columns($columns) {
            return wp_parse_args([
                'created_at' => 'orderby',
                'taxonomy-hi_fcm_subscriptions' => 'orderby',
                'os_version' => 'orderby',
                'device' => 'orderby',
                'user_email' => 'orderby',
            ], $columns);
        }

        /**
         * Register metaboxes.
         * 
         * @return void
         */
        public function add_meta_boxes() {
            $post_types = hi_fcm_registered_post_types();

            foreach ($post_types as $post_type) {
                add_meta_box(
                    'hi_fcm_send_fcm_notification',
                    esc_html__('Push Notification', 'hi-fcm'),
                    [$this, 'add_meta_boxes_callback'],
                    $post_type->name,
                    'side',
                    'high',
                    null
                );
            }
        }

        /**
         * Show metaboxes.
         * 
         * @param WP_Post $post
         * 
         * @return void
         */
        public function add_meta_boxes_callback($post) {
            global $pagenow;

            $subscriptions = hi_fcm_get_available_terms();
            
            ob_start();
            require_once HI_FCM_DIR . '/templates/metabox.php';
            echo ob_get_clean();
        }

        /**
         * Save custom metabox.
         * 
         * @param integer $post_id
         * 
         * @return void
         */
        public function save_post($post_id) {
            if (
                wp_is_post_autosave($post_id)
                ||
                wp_is_post_revision($post_id)
                ||
                (
                    isset($_POST['hi_fcm_nonce'])
                    &&
                    wp_verify_nonce($_POST['hi_fcm_nonce'], basename(__FILE__))
                )
            ) {
                return;
            }

            remove_action('save_post', [$this, 'on_post_save'], 10);

            if (isset($_POST['hi_fcm_push_notification'])) {
                update_post_meta($post_id, 'hi_fcm_push_notification', '1');
            } else {
                update_post_meta($post_id, 'hi_fcm_push_notification', '0');
            }

            if (isset($_POST['hi_fcm_use_channel'])) {
                update_post_meta($post_id, 'hi_fcm_use_channel', '1');
            } else {
                update_post_meta($post_id, 'hi_fcm_use_channel', '0');
            }

            if (isset($_POST['hi_fcm_subscription'])) {
                update_post_meta($post_id, 'hi_fcm_subscription', sanitize_text_field($_POST['hi_fcm_subsciption']));
            }

            add_action('save_post', [$this, 'on_post_save'], 10, 3);
        }

        /**
         * Do action on delete post.
         * 
         * @param integer $post_id
         * 
         * @return void
         */
        public function delete_post($post_id) {
            global $wpdb;

            if (get_post_type($post_id) !== 'hi_fcm_tokens') {
                return;
            }

            $row = $wpdb->get_row(
                $wpdb->prepare("SELECT * FROM $table WHERE post_ID = %d", $post_id)
            );

            if ($row) {
                $wpdb->delete($this->tokens_table, [
                    'post_ID' => $post_id,
                ]);
            }
        }

        /**
         * Push notification on insert post.
         * 
         * @param integer $post_id
         * @param WP_Post $post
         * @param boolean $update
         * 
         * @return void
         */
        public function on_post_save($post_id, $post, $update) {
            if (! get_option('hi_fcm_api_key') && get_option('hi_fcm_api_key') == '') {
                return;
            }

            if (isset($post->post_status)) {
                if ($update && ($post->post_status == 'publish')) {
                    $metabox = get_post_meta($post->ID, 'hi_fcm_push_notification', true);

                    if ($metabox && $metabox == '1') {
                        $this->notifications->post($post, []);
                    }

                    update_post_meta($post->ID, 'hi_fcm_push_notification', '0');
                }
            }
        }

        /**
         * Define constant if not exists
         * 
         * @return void
         */
        private function define(string $name, $value) {
            if (! defined($name)) {
                define($name, $value);
            }
        }
    }

    $GLOBALS['hi_fcm'] = HIF_Fcm::instance();
}