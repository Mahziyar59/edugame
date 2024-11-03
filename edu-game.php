<?php
/**
 * Plugin Name: EduGame
 * Description: یک افزونه جامع برای ایجاد و مدیریت بازی‌های آموزشی
 * Version: 1.0.0
 * Author: PRABBASI
 * Author URI: 
 */

namespace EduGame;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // خروج در صورت دسترسی مستقیم
}

// تعریف ثابت‌ها برای مسیرهای پلاگین
define( 'EDUGAME_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EDUGAME_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// بارگذاری وابستگی‌ها
require_once EDUGAME_PLUGIN_DIR . 'includes/class-edu-game-db.php';
require_once EDUGAME_PLUGIN_DIR . 'includes/class-edu-game.php';

// ایجاد نمونه‌ای از کلاس اصلی و آغاز به کار پلاگین
$edu_game = new EduGame();

class EduGame {
    private $db; // نمونه‌ای از کلاس پایگاه داده

    public function __construct() {
        $this->db = new EduGame_DB();
        add_action( 'init', array( $this, 'init' ) );
    }

    public function init() {
        // ثبت انواع پست سفارشی، تاکسونومی‌ها، شورتکدها، منوهای مدیریت و ایجاد جداول
        $this->register_post_types();
        $this->register_taxonomies();
        $this->register_shortcodes();
        $this->add_admin_menu();
        $this->create_game_tables();
    }

    private function register_post_types() {
        register_post_type( 'edu_game', array(
            'labels' => array(
                'name' => __('Educational Games', 'edu-game'),
                'singular_name' => __('Educational Game', 'edu-game'),
                // ... سایر عناصر آرایه labels
            ),
            'public' => true,
            'has_archive' => true,
            'menu_icon' => 'dashicons-gamepad',
            'supports' => array( 'title', 'editor', 'thumbnail' ),
        ) );
    }
function edugame_enqueue_scripts() {
    // آرایه‌ای برای ذخیره اطلاعات اسکریپت‌ها و شیوه‌نامه‌ها
    $scripts = array(
        'edu-game-style' => array(
            'src' => plugins_url('assets/css/edu-game.css', __FILE__),
            'deps' => array(), // آرایه‌ای از وابستگی‌ها
            'ver' => '1.0',
        ),
        'edu-game-script' => array(
            'src' => plugins_url('assets/js/edu-game.js', __FILE__),
            'deps' => array('jquery'), // اگر به jQuery نیاز دارد
            'ver' => '1.0',
            'in_footer' => true, // بارگذاری در فوتر
        ),
    );

    // بارگذاری اسکریپت‌ها و شیوه‌نامه‌ها بر اساس شرایط
    if (is_singular('edu_game')) { // فقط در صفحات بازی بارگذاری شود
        foreach ($scripts as $handle => $script) {
            wp_enqueue_script($handle, $script['src'], $script['deps'], $script['ver'], $script['in_footer'] ?? false);
        }
    }
}
function edugame_add_jsonld() {
    if (is_singular('game')) { // فرض کنید نوع پست بازی شما 'game' است
        global $post;

        $jsonld = [
            "@context" => "https://schema.org",
            "@type" => "Game",
            "name" => get_the_title(), // عنوان بازی از عنوان پست
            "description" => get_the_excerpt(), // توضیحات بازی از خلاصه پست
            "image" => get_the_post_thumbnail_url(), // تصویر شاخص بازی
            "genre" => "Educational", // ژانر بازی را مشخص کنید
            "publisher" => [
                "@type" => "Organization",
                "name" => "PRABBASI"
            ],
            "datePublished" => get_the_date('c') // تاریخ انتشار بازی
        ];

        echo '<script type="application/ld+json" class="yoast-schema">'. json_encode($jsonld) . '</script>';
    }
}
add_action('wp_head', 'edugame_add_jsonld');
add_action('wp_enqueue_scripts', 'edugame_enqueue_scripts');

    private function register_taxonomies() {
        register_taxonomy( 'game_type', 'edu_game', array(
            'labels' => array(
                'name' => __('Game Types', 'edu-game'),
                'singular_name' => __('Game Type', 'edu-game'),
            ),
            'hierarchical' => true,
        ) );
    }

    private function register_shortcodes() {
        add_shortcode( 'edu_games_list', array( $this, 'display_games_list' ) );
        add_shortcode( 'edu_game', array( $this, 'display_game' ) );
    }

    public function display_games_list() {
        // ... (کد نمایش لیست بازی‌ها)
    }

    public function display_game( $atts ) {
        // ... (کد نمایش یک بازی خاص)
    }

    private function add_admin_menu() {
        add_menu_page(
            __('EduGame', 'edu-game'),
            __('EduGame', 'edu-game'),
            'manage_options',
            'edu-game',
            array( $this, 'edu_game_settings_page' ),
            'dashicons-gamepad',
            60
        );
    }

    public function edu_game_settings_page() {
        // ... (کد نمایش صفحه تنظیمات)
    }

    private function create_game_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$wpdb->prefix}edu_games (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            type VARCHAR(255) NOT NULL,
            description TEXT,
            // سایر فیلدها مانند تاریخ ایجاد، وضعیت بازی و ...
        ) $charset_collate;";

        $sql .= "CREATE TABLE {$wpdb->prefix}edu_game_scores (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT UNSIGNED NOT NULL,
            game_id INT NOT NULL,
            score INT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            // سایر فیلدها مانند سطح دشواری بازی
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }
}

class EduGame_DB {
    public function create_game($title, $type, $description) {
        global $wpdb;
        // اعتبارسنجی ورودی‌ها
        $title = sanitize_text_field($title);
        $type = sanitize_text_field($type);
        $description = sanitize_textarea_field($description);

        if (empty($title) || empty($type)) {
            return false;
        }

        $data = array(
            'title' => $title,
            'type' => $type,
            'description' => $description,
        );

        $result = $wpdb->insert($wpdb->prefix . 'edu_games', $data);
        return $result;
    }

    public function get_game_by_id($game_id) {
        global $wpdb;
        $game_id = intval($game_id);

        $game = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}edu_games WHERE ID = %d", $game_id));
        return $game;
    }

    // ... سایر متدها برای مدیریت کاربران، امتیازات و ...

    public function register_user($username, $email, $password) {
        // ... اعتبارسنجی ورودی‌ها و ثبت کاربر در وردپرس
        $user_id = wp_create_user($username, $password, $email);
        // ... سایر عملیات پس از ثبت کاربر
        return $user_id;
    }
}

class EduGame_Score {
    public function save_score($user_id, $game_id, $score) {
        // ... ذخیره امتیاز در پایگاه داده
    }
}

class EduGame_Leaderboard {
    public function display_leaderboard($game_id) {
        // ... نمایش جدول امتیازات برای یک بازی خاص
    }
}

class EduGame_Ajax {
    private $cache; // برای ذخیره نتایج کوئری‌ها در حافظه پنهان

    public function __construct() {
        global $wp_object_cache;
        $this->cache = &$wp_object_cache;

        add_action('wp_ajax_edugame_handle_ajax_request', array($this, 'handle_ajax_request'));
        add_action('wp_ajax_nopriv_edugame_handle_ajax_request', array($this, 'handle_ajax_request'));
    }

    public function handle_ajax_request() {
        check_ajax_referer('edu_game_nonce', 'nonce');

        try {
            // پاکسازی داده‌های ورودی
            $action = sanitize_text_field($_POST['action']);

            if ($action === 'create_game') {
                $title = sanitize_text_field($_POST['title']);
                $type = sanitize_text_field($_POST['type']);
                $description = sanitize_textarea_field($_POST['description']);

                // اعتبارسنجی ورودی‌ها
                if (empty($title) || empty($type)) {
                    wp_send_json_error('لطفا عنوان و نوع بازی را وارد کنید.');
                }

                $this->db->create_game($title, $type, $description);
                wp_send_json_success('بازی با موفقیت ایجاد شد.');
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
}

new EduGame_Ajax();
