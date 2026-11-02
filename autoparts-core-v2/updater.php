<?php
if (!defined('ABSPATH')) { exit; }

/**
 * به‌روزرسانی خودکار قالب از گیت‌هاب.
 * هر ۵ دقیقه آخرین کامیتِ شاخه‌ی main مخزن رو چک می‌کنه؛ اگه عوض شده بود،
 * پوشه‌ی autoparts-core-v2 رو از گیت‌هاب می‌گیره و روی فایل‌های همین قالب می‌ریزه.
 * چون مخزن خصوصیه، یک توکن گیت‌هاب (فقط خواندنی) لازمه که از
 * پیشخوان ← نمایش ← به‌روزرسانی خودکار وارد می‌شه.
 * داده‌های سایت (پوشه‌ی ap-data داخل uploads) دست نمی‌خوره.
 */

define('AP_UPD_REPO', 'git-nexv/site-stock');
define('AP_UPD_BRANCH', 'main');
define('AP_UPD_SUBDIR', 'autoparts-core-v2');

add_filter('cron_schedules', function ($s) {
    $s['ap_upd_5min'] = ['interval' => 300, 'display' => 'هر ۵ دقیقه'];
    return $s;
});

add_action('init', function () {
    if (!wp_next_scheduled('ap_upd_check')) {
        wp_schedule_event(time() + 60, 'ap_upd_5min', 'ap_upd_check');
    }
});

add_action('ap_upd_check', function () { ap_upd_run(false); });

function ap_upd_status($ok, $msg) {
    update_option('ap_upd_status', ['ok' => $ok, 'msg' => $msg, 'time' => time()], false);
    return $ok;
}

function ap_upd_request($url, $token, $args = []) {
    return wp_remote_get($url, $args + [
        'timeout' => 30,
        'headers' => [
            'Authorization'        => 'Bearer ' . $token,
            'Accept'               => 'application/vnd.github+json',
            'User-Agent'           => 'ap-core-updater',
            'X-GitHub-Api-Version' => '2022-11-28',
        ],
    ]);
}

function ap_upd_run($force) {
    $token = trim((string) get_option('ap_upd_token', ''));
    if ($token === '') return ap_upd_status(false, 'توکن گیت‌هاب وارد نشده.');
    if (!class_exists('ZipArchive')) return ap_upd_status(false, 'افزونه‌ی ZipArchive روی PHP هاست فعال نیست.');

    /* جلوگیری از اجرای همزمان */
    if (get_transient('ap_upd_lock')) return false;
    set_transient('ap_upd_lock', 1, 300);

    try {
        $res = ap_upd_request('https://api.github.com/repos/' . AP_UPD_REPO . '/commits/' . AP_UPD_BRANCH, $token);
        if (is_wp_error($res)) return ap_upd_status(false, 'اتصال به گیت‌هاب نشد: ' . $res->get_error_message());
        $code = wp_remote_retrieve_response_code($res);
        if ($code !== 200) return ap_upd_status(false, 'پاسخ گیت‌هاب: ' . $code . ' (توکن رو چک کن)');
        $body = json_decode(wp_remote_retrieve_body($res), true);
        $sha  = isset($body['sha']) ? (string) $body['sha'] : '';
        if ($sha === '') return ap_upd_status(false, 'شناسه‌ی کامیت از گیت‌هاب دریافت نشد.');

        if (!$force && $sha === get_option('ap_upd_sha', '')) {
            return ap_upd_status(true, 'به‌روزه (' . substr($sha, 0, 7) . ').');
        }

        $tmp     = trailingslashit(get_temp_dir()) . 'ap-upd-' . wp_generate_password(8, false);
        $zipFile = $tmp . '.zip';
        $res = ap_upd_request('https://api.github.com/repos/' . AP_UPD_REPO . '/zipball/' . $sha, $token, [
            'timeout'  => 120,
            'stream'   => true,
            'filename' => $zipFile,
        ]);
        if (is_wp_error($res) || wp_remote_retrieve_response_code($res) !== 200) {
            @unlink($zipFile);
            return ap_upd_status(false, 'دانلود فایل‌ها از گیت‌هاب نشد.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipFile) !== true) { @unlink($zipFile); return ap_upd_status(false, 'فایل zip خراب بود.'); }
        $zip->extractTo($tmp);
        $zip->close();
        @unlink($zipFile);

        $tops = glob($tmp . '/*', GLOB_ONLYDIR);
        $src  = $tops ? $tops[0] . '/' . AP_UPD_SUBDIR : '';
        foreach (['functions.php', 'index.php', 'style.css'] as $f) {
            if (!$src || !is_file($src . '/' . $f)) {
                ap_upd_rmdir($tmp);
                return ap_upd_status(false, 'پوشه‌ی قالب توی مخزن کامل نیست؛ چیزی جایگزین نشد.');
            }
        }

        $err = ap_upd_copy($src, __DIR__);
        ap_upd_rmdir($tmp);
        if ($err) return ap_upd_status(false, $err);

        update_option('ap_upd_sha', $sha, false);
        return ap_upd_status(true, 'به‌روزرسانی شد به ' . substr($sha, 0, 7) . '.');
    } finally {
        delete_transient('ap_upd_lock');
    }
}

/* کپی بازگشتی؛ هر فایل اول کنارش نوشته و بعد جابه‌جا می‌شه تا نیمه‌کاره نمونه. */
function ap_upd_copy($from, $to) {
    if (!is_dir($to) && !@mkdir($to, 0755, true)) return 'ساخت پوشه نشد: ' . $to;
    foreach (scandir($from) as $name) {
        if ($name === '.' || $name === '..') continue;
        $s = $from . '/' . $name;
        $d = $to . '/' . $name;
        if (is_dir($s)) {
            $err = ap_upd_copy($s, $d);
            if ($err) return $err;
            continue;
        }
        if (is_file($d) && md5_file($d) === md5_file($s)) continue;
        $t = $d . '.ap-upd-tmp';
        if (!@copy($s, $t) || !@rename($t, $d)) {
            @unlink($t);
            return 'نوشتن فایل نشد (دسترسی پوشه‌ی قالب رو چک کن): ' . $name;
        }
        if (function_exists('opcache_invalidate')) @opcache_invalidate($d, true);
    }
    return '';
}

function ap_upd_rmdir($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $name) {
        if ($name === '.' || $name === '..') continue;
        $p = $dir . '/' . $name;
        is_dir($p) ? ap_upd_rmdir($p) : @unlink($p);
    }
    @rmdir($dir);
}

/* صفحه‌ی تنظیمات: پیشخوان ← نمایش ← به‌روزرسانی خودکار */
add_action('admin_menu', function () {
    add_theme_page('به‌روزرسانی خودکار', 'به‌روزرسانی خودکار', 'manage_options', 'ap-updater', 'ap_upd_page');
});

add_action('admin_post_ap_upd_save', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی ندارید.');
    check_admin_referer('ap_upd_save');
    $token = isset($_POST['ap_upd_token']) ? trim(sanitize_text_field(wp_unslash($_POST['ap_upd_token']))) : '';
    if ($token !== '') update_option('ap_upd_token', $token, false);
    if (!empty($_POST['ap_upd_now'])) ap_upd_run(true);
    wp_safe_redirect(admin_url('themes.php?page=ap-updater'));
    exit;
});

function ap_upd_page() {
    $st    = get_option('ap_upd_status', []);
    $sha   = (string) get_option('ap_upd_sha', '');
    $has   = get_option('ap_upd_token', '') !== '';
    $next  = wp_next_scheduled('ap_upd_check');
    ?>
    <div class="wrap">
        <h1>به‌روزرسانی خودکار قالب</h1>
        <p>هر ۵ دقیقه شاخه‌ی <code><?php echo esc_html(AP_UPD_BRANCH); ?></code> مخزن
            <code><?php echo esc_html(AP_UPD_REPO); ?></code> چک می‌شه و اگه تغییری بود، روی سایت ریخته می‌شه.</p>
        <table class="form-table">
            <tr><th>آخرین نسخه‌ی نصب‌شده</th><td><code><?php echo $sha ? esc_html(substr($sha, 0, 7)) : '—'; ?></code></td></tr>
            <tr><th>آخرین وضعیت</th><td><?php
                if ($st) {
                    echo '<span style="color:' . ($st['ok'] ? '#008a20' : '#d63638') . '">' . esc_html($st['msg']) . '</span> ';
                    echo '<small>(' . esc_html(human_time_diff($st['time'])) . ' پیش)</small>';
                } else { echo '—'; }
            ?></td></tr>
            <tr><th>بررسی بعدی</th><td><?php echo $next ? esc_html(human_time_diff($next)) . ' دیگه' : '—'; ?></td></tr>
        </table>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ap_upd_save'); ?>
            <input type="hidden" name="action" value="ap_upd_save">
            <table class="form-table">
                <tr>
                    <th><label for="ap_upd_token">توکن گیت‌هاب</label></th>
                    <td>
                        <input type="password" id="ap_upd_token" name="ap_upd_token" class="regular-text" dir="ltr" autocomplete="off"
                               placeholder="<?php echo $has ? 'ذخیره شده — برای تغییر، توکن جدید بزن' : 'github_pat_...'; ?>">
                        <p class="description">توکن Fine-grained با دسترسی فقط به همین مخزن و مجوز Contents: Read-only.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('ذخیره', 'primary', 'submit', false); ?>
            <?php submit_button('ذخیره و به‌روزرسانی همین الان', 'secondary', 'ap_upd_now', false); ?>
        </form>
    </div>
    <?php
}

/* دکمه‌ی «به‌روزرسانی از گیت‌هاب»: توی منوی قطعات یدکی و نوار بالای پیشخوان؛
   با یک کلیک همین الان آخرین نسخه از گیت‌هاب گرفته و روی سایت ریخته می‌شه. */
add_action('admin_menu', function () {
    add_submenu_page('ap-dashboard', 'به‌روزرسانی سایت', 'به‌روزرسانی سایت', 'manage_options', 'ap-updater', 'ap_upd_page');
}, 11);

add_action('admin_bar_menu', function ($bar) {
    if (!current_user_can('manage_options')) return;
    $bar->add_node([
        'id'    => 'ap-update-now',
        'title' => '⟳ به‌روزرسانی سایت',
        'href'  => wp_nonce_url(admin_url('admin-post.php?action=ap_upd_now'), 'ap_upd_now'),
    ]);
}, 90);

add_action('admin_post_ap_upd_now', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی ندارید.');
    check_admin_referer('ap_upd_now');
    ap_upd_run(true);
    wp_safe_redirect(admin_url('admin.php?page=ap-updater'));
    exit;
});
