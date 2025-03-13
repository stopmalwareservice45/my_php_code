<?php  
/*  
Plugin Name: Фаервол  
Description: Блокировка подозрительных User-Agent и URL, добавление IP в черный список, защита от XSS через куки, скрытие версии WordPress.  
Version: 2.2  
Author: Кирилл  
*/  
  
if (!defined('ABSPATH')) {  
    exit;  
}  
  
// Список подозрительных User-Agent  
$bot_agent = [  
    'curl', 'Chrome-Lighthouse', 'FBSN', 'Trident', 'Crios', 'PhantomJS', 'wget', 'SM-G892A', 'Default',  
    'Build/NRD90M', 'GuzzleHttp', 'Go-http-client', 'urllib', 'aiohttp', 'Java', 'Scrapy', 'PycURL',  
    'mechanize', 'node-fetch', 'aiohttp', 'python-requests', 'python-urllib', 'Yowser'  
];  
  
// Список агентов автоматических атак  
$attack_bots = [  
    'sqlmap', 'Nikto', 'ZAP', 'Wget', 'libwww-perl', 'Nmap', 'python-httpx', 'Metasploit',  
    'BurpSuite', 'dirb', 'wpscan', 'fierce', 'httprobe', 'masscan'  
];  
  
// Список запрещенных URL  
$blocked_urls = [  
    '/wp-includes/blocks/navigation/style.min.css',  
    '/wp-includes/js/dist/script-modules/interactivity/index.min.js',  
    '/wp-includes/js/dist/script-modules/block-library/navigation/view.min.js',  
    '/wp-includes/js/wp-emoji-release.min.js',  
    '/wp-includes/css/dist/block-library/common.min.css',  
    '/wp-includes/js/comment-reply.min.js',  
    '/wp-admin/load-scripts.php',  
    '/wp-includes/css/editor.min.css',  
    '/wp-admin/load-scripts.php',  
    '/wp-admin/js/common.min.js',  
    '/wp-includes/js/hoverintent-js.min.js',  
    '/wp-includes/js/admin-bar.min.js',  
    '/wp-includes/js/clipboard.min.js',  
    '/wp-includes/js/api-request.min.js',  
    '/wp-includes/js/dist/url.min.js',  
    '/wp-includes/js/dist/vendor/wp-polyfill.min.js',  
    '/wp-admin/js/site-health.min.js',  
    '/wp-includes/js/wp-ajax-response.min.js',  
    '/wp-includes/js/jquery/jquery.color.min.js',  
    '/wp-includes/js/wp-lists.min.js',  
    '/wp-includes/js/quicktags.min.js',  
    '/wp-includes/js/jquery/jquery.query.js',  
    '/wp-admin/js/edit-comments.min.js',  
    '/wp-includes/js/jquery/ui/core.min.js',  
    '/wp-includes/js/jquery/ui/mouse.min.js',  
    '/wp-includes/js/jquery/ui/sortable.min.js',  
    '/wp-admin/js/postbox.min.js',  
    '/wp-includes/js/dist/vendor/moment.min.js',  
    '/wp-includes/js/dist/deprecated.min.js',  
    '/wp-includes/js/dist/date.min.js',  
    '/wp-admin/js/dashboard.min.js',  
    '/wp-includes/js/wp-sanitize.min.js',  
    '/wp-admin/js/plugin-install.min.js',  
    '/wp-includes/js/thickbox/thickbox.js',  
    '/wp-admin/js/media-upload.min.js',  
    '/wp-includes/js/shortcode.min.js',  
    '/wp-admin/js/updates.min.js',  
    '/wp-includes/js/jquery/jquery.ui.touch-punch.js',  
    '/wp-admin/js/svg-painter.js',  
    '/wp-includes/js/heartbeat.min.js',  
    '/wp-includes/js/jquery/ui/menu.min.js',  
    '/wp-includes/js/wp-auth-check.min.js',  
    '/wp-includes/js/wplink.min.js',  
    '/wp-includes/js/jquery/ui/autocomplete.min.js',  
    '/wp-includes/js/underscore.min.js',  
    '/wp-json/wp/v2/posts/1',  
    '/wp-json/wp/v2/pages/2',  
    '/wp-json/oembed/1.0/embed',  
    '/xmlrpc.php',
    '/wp-json/wp/v2/users',
    '/wp-json/wp/v2/users/*',
    '/?author=',
    '/wp-content/plugins/',
    '/wp-content/themes/'
];  
function block_version_information() {  
    if (isset($_SERVER['QUERY_STRING']) && preg_match('/ver=/i', $_SERVER['QUERY_STRING'])) {  
        wp_die('Заблокирован запрос с параметром ?ver');  
    }  
  
    if (strpos($_SERVER['REQUEST_URI'], 'wp-json/wp/v2') !== false) {  
        wp_die('Доступ к REST API заблокирован');  
    }  
  
    // Убираем версии в HTML-выводе  
    remove_action('wp_head', 'wp_generator');  
}  
add_action('init', 'block_version_information');  
  
// Функция для скрытия мета-тега generator  
function remove_wp_version_meta_tag() {  
    remove_action('wp_head', 'wp_generator'); // Удаляет мета-тег generator  
}  
add_action('init', 'remove_wp_version_meta_tag');  
  
// Функция проверки запросов  
function check_suspicious_request() {  
    global $bot_agent, $attack_bots, $blocked_urls;  
  
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';  
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';  
    $request_url = $_SERVER['REQUEST_URI'] ?? 'UNKNOWN';  
  
    foreach (array_merge($bot_agent, $attack_bots) as $artifact) {  
        if (stripos($userAgent, $artifact) !== false) {  
            log_and_block($ip, $userAgent, $request_url);  
        }  
    }  
  
    foreach ($blocked_urls as $bad_url) {  
        if (stripos($request_url, $bad_url) !== false) {  
            log_and_block($ip, $userAgent, $request_url);  
        }  
    }  
}  
add_action('init', 'check_suspicious_request');  
  
// Функция логирования и блокировки  
function log_and_block($ip, $userAgent, $request_url) {  
    $log_file = plugin_dir_path(__FILE__) . 'firewall_log.txt';  
    file_put_contents($log_file, "[$ip] $userAgent | URL: $request_url\n", FILE_APPEND);  
    send_telegram_alert($ip, $userAgent, $request_url);  
      
    if (!headers_sent()) {  
        header('Content-Type: application/json');  
    }  
      
    wp_die('Заблокирована попытка атаки [Xblock]');  
}  
  
// Функция отправки уведомлений в Telegram  
function send_telegram_alert($ip, $userAgent, $request_url) {  
    $telegram_token = '@BOTFATHER TOKEN';  
    $chat_id = 'CHAT ID';  
    $message = "🚨 Подозрительная активность!\nIP: $ip\nUser-Agent: $userAgent\nURL: $request_url";  
    $telegram_url = "https://api.telegram.org/bot$telegram_token/sendMessage?chat_id=$chat_id&text=" . urlencode($message);  
    file_get_contents($telegram_url);  
}  
  
// Функция для отображения логов в админке  
function firewall_admin_menu() {  
    add_menu_page('Логи Фаервола', 'Фаервол', 'manage_options', 'firewall-logs', 'firewall_logs_page');  
}  
add_action('admin_menu', 'firewall_admin_menu');  
  
// Страница логов  
function firewall_logs_page() {  
    $log_file = plugin_dir_path(__FILE__) . 'firewall_log.txt';  
    echo '<div class="wrap"><h2>Логи Фаервола</h2><pre>' . (file_exists($log_file) ? file_get_contents($log_file) : 'Логов нет.') . '</pre></div>';  
}