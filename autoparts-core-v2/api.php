<?php
/**
 * AutoParts backend — بدون نیاز به هیچ افزونه/درایور خاص PHP (نه SQLite، نه MySQL).
 * فقط از فایل‌های JSON ساده برای ذخیره‌سازی استفاده می‌کنه، پس روی هر هاستی کار می‌کنه.
 *
 * - دیتا در data/db.json نگه داشته می‌شه.
 * - بعد از هر تغییر، یک نسخه‌ی پشتیبان در data/backups/ ساخته می‌شه (آخرین ۳۰ نسخه نگه داشته می‌شه).
 * - اگه db.json به هر دلیل نبود (مثلاً بعد از نصب دوباره‌ی قالب)، از آخرین بکاپ بازسازی می‌شه.
 * - پوشه‌ی data بیرون از پوشه‌ی قالب (داخل wp-content/uploads) نگه داشته می‌شه تا با حذف/نصب
 *   دوباره‌ی قالب پاک نشه.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit; }

/* =========================================================================
 * محصولات و دسته‌بندیِ درختی از هسته‌ی خودِ وردپرس (نوع‌پست ap_product و
 * تاکسونومی سلسله‌مراتبی ap_cat) خونده می‌شن تا مدیر سایت همه چیز رو از
 * پیشخوان وردپرس (محصولات ← دسته‌بندی‌ها) بسازه. برای این کار، هسته‌ی وردپرس
 * همین‌جا بارگذاری می‌شه. ثبت‌نام/ورود/سبد خرید همچنان با همون فایل JSON سبک
 * زیر کار می‌کنن و به این بخش کاری ندارن.
 * ========================================================================= */
/* هسته‌ی وردپرس فقط وقتی بارگذاری می‌شه که واقعاً لازم باشه.
   ورود/ثبت‌نام/پروفایل هیچ نیازی به وردپرس ندارن و با این کار چند صد میلی‌ثانیه
   (روی هاست‌های کند بیشتر) سریع‌تر جواب می‌دن. */
function ap_boot_wp() {
    static $done = false;
    if ($done || defined('ABSPATH')) { $done = true; return; }
    $wp_load = dirname(__DIR__, 3) . '/wp-load.php';
    if (file_exists($wp_load)) { require_once $wp_load; }
    $done = true;
}

/* اکشن‌های سبک: بدون وردپرس اجرا می‌شن */
$AP_LIGHT_ACTIONS = [
    'register', 'login', 'logout', 'config', 'google_login',
    'update_profile', 'add_address', 'update_address', 'delete_address', 'list_my_orders',
];
$ap_action_early = $_GET['action'] ?? '';
if ($ap_action_early === '' || !in_array($ap_action_early, $AP_LIGHT_ACTIONS, true)) {
    ap_boot_wp();
}

function ap_term_ancestors_slugs($term_id, $taxonomy) {
    $slugs = [];
    $ancestors = get_ancestors($term_id, $taxonomy);
    foreach ($ancestors as $aid) {
        $t = get_term($aid, $taxonomy);
        if ($t && !is_wp_error($t)) { $slugs[] = $t->slug; }
    }
    return $slugs;
}

/* =========================================================================
 * رنگِ کارتِ محصول: محصولاتی که قبلاً (با پوسته/درون‌ریزیِ قدیمی) ساخته شدن،
 * رنگِ بنفشِ پوسته‌ی قبلی رو توی متای خودشون ذخیره دارن و همون بنفش روی کارت
 * دیده می‌شد. این تابع رنگ‌های بنفشِ قدیمی رو به قرمزِ برند تبدیل می‌کنه؛ اگه
 * مدیر عمداً رنگِ دیگه‌ای (مثلاً مشکی یا آبی) گذاشته باشه، دست‌نخورده می‌مونه.
 * ========================================================================= */
if (!function_exists('ap_variant_price_for_key')) {
    /* قیمتِ نهاییِ یک ردیفِ سبد از روی کلیدش (مثل «12::c:مشکی::x:نوع=اورجینال»).
       همون قانونِ سمتِ سایت: هر گزینه‌ای که قیمتِ جدا داشته باشه جایگزینِ قیمتِ پایه
       می‌شه و اگه چند گروه قیمت داشته باشن، گروهِ آخر (خاص‌ترین) برنده‌ست. محاسبه
       سمتِ سرور انجام می‌شه تا کسی نتونه با دستکاریِ مرورگر قیمت رو عوض کنه. */
    function ap_variant_price_for_key($basePrice, $variantPrices, $rawKey) {
        $price = (int) $basePrice;
        if (!is_array($variantPrices) || !$variantPrices) { return $price; }
        $parts = explode('::', (string) $rawKey);
        array_shift($parts);
        foreach ($parts as $part) {
            $label = null; $value = null;
            if (strpos($part, 'c:') === 0)      { $label = 'رنگ';        $value = rawurldecode(substr($part, 2)); }
            elseif (strpos($part, 's:') === 0)  { $label = 'حجم/ظرفیت'; $value = rawurldecode(substr($part, 2)); }
            elseif (strpos($part, 'x:') === 0) {
                $raw = substr($part, 2);
                $eq = strpos($raw, '=');
                if ($eq !== false) {
                    $label = rawurldecode(substr($raw, 0, $eq));
                    $value = rawurldecode(substr($raw, $eq + 1));
                }
            }
            if ($label === null || $value === null || $value === '') { continue; }
            $key = $label . '||' . $value;
            if (isset($variantPrices[$key]) && (int) $variantPrices[$key] > 0) {
                $price = (int) $variantPrices[$key];
            }
        }
        return $price;
    }
}

if (!function_exists('ap_brand_card_color')) {
    function ap_brand_card_color($raw) {
        $red = '#ff3b4e';
        $raw = strtolower(trim((string) $raw));
        if ($raw === '') { return $red; }
        $legacy = ['#6a4dff', '#8b6bff', '#8f6bff', '#a68bff', '#7c5cff', '#5b3df5', '#8b5cf6', '#7c9dff', '#6a4eff'];
        if (in_array($raw, $legacy, true)) { return $red; }
        /* هر بنفشِ دیگه‌ای هم (آبیِ پررنگ‌تر از قرمز و سبز) قرمز می‌شه */
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/', $raw, $m)) {
            $r = hexdec($m[1]); $g = hexdec($m[2]); $b = hexdec($m[3]);
            if ($b > $r + 25 && $b > $g + 20) { return $red; }
        }
        return $raw;
    }
}

function ap_wp_products() {
    if (!function_exists('get_posts')) { return null; }

    /* کش: ساختن این لیست برای صدها محصول (چون هر کدوم چندین متا و ترم و
       گالری داره) سنگین‌ترین Query سایته. نتیجه‌ی نهایی رو ۱۰ دقیقه کش
       می‌کنیم و با هر ذخیره/ویرایش محصول (نگاه کن به save_post_ap_product
       و پاک‌شدن این ترنزینت) بلافاصله باطل می‌شه، پس دیتای بات نمی‌مونه. */
    $cacheKey = 'ap_products_cache_v3';
    $cached = get_transient($cacheKey);
    if (is_array($cached)) return $cached;

    $posts = get_posts([
        'post_type'              => 'ap_product',
        'posts_per_page'         => -1,
        'post_status'            => 'publish',
        'orderby'                => 'menu_order date',
        'order'                  => 'ASC',
        'update_post_term_cache' => true,
        'update_post_meta_cache' => true,
    ]);

    /* پیش‌بارگذاریِ دسته‌بندیِ همه‌ی محصولات با یک Query (به‌جای یک Query
       جدا برای هر محصول) تا مشکل N+1 Query رفع بشه. */
    $postIds = wp_list_pluck($posts, 'ID');
    if ($postIds) { update_object_term_cache($postIds, 'ap_product'); }

    $out = [];
    foreach ($posts as $p) {
        $slugs   = [];
        $primary = '';
        $terms   = function_exists('wp_get_post_terms') ? wp_get_post_terms($p->ID, 'ap_cat') : [];
        if (!is_wp_error($terms)) {
            foreach ($terms as $t) {
                if ($primary === '') { $primary = $t->slug; }
                $slugs[] = $t->slug;
                foreach (ap_term_ancestors_slugs($t->term_id, 'ap_cat') as $s) { $slugs[] = $s; }
            }
        }
        $slugs = array_values(array_unique($slugs));

        /* خودرو (برند ← مدل ← سال): مثل دسته‌بندی، اسلاگِ خودِ ترم و همه‌ی والدهاش
           فرستاده می‌شه تا فیلترِ «انتخاب خودرو» روی سایت با هر سطحی کار کنه. */
        $vslugs = [];
        $vterms = function_exists('wp_get_post_terms') ? wp_get_post_terms($p->ID, 'ap_vehicle') : [];
        if (!is_wp_error($vterms)) {
            foreach ($vterms as $vt) {
                $vslugs[] = $vt->slug;
                foreach (ap_term_ancestors_slugs($vt->term_id, 'ap_vehicle') as $vs) { $vslugs[] = $vs; }
                /* محصولی که به یک مدل (بدون سال) وصل شده، برای همه‌ی سال‌های اون مدل هم نمایش داده می‌شه. */
                $kids = get_term_children($vt->term_id, 'ap_vehicle');
                if (!is_wp_error($kids)) {
                    foreach ($kids as $kid) { $kt = get_term($kid, 'ap_vehicle'); if ($kt && !is_wp_error($kt)) $vslugs[] = $kt->slug; }
                }
            }
        }
        $vslugs = array_values(array_unique($vslugs));

        $badge = get_post_meta($p->ID, '_ap_badge', true);
        $img = get_the_post_thumbnail_url($p->ID, 'large');
        $gallery = ap_gallery_urls($p->ID, $img);
        $out[] = [
            'id'    => (string) $p->ID,
            'title' => get_the_title($p),
            'spec'  => (string) get_post_meta($p->ID, '_ap_spec', true),
            'price' => (int) get_post_meta($p->ID, '_ap_price', true),
            /* تاریخِ شمسیِ آخرین ویرایشِ قیمت؛ فقط توی صفحه‌ی خودِ محصول نشون داده می‌شه */
            'priceUpdated' => function_exists('ap_price_updated_label') ? ap_price_updated_label($p->ID) : '',
            'color' => ap_brand_card_color(get_post_meta($p->ID, '_ap_color', true)),
            'ic'    => get_post_meta($p->ID, '_ap_icon', true) ?: 'disc',
            'badge' => $badge !== '' ? explode('|', $badge)[0] : null,   // سازگاری با نسخه‌های قبلی
            'badges' => $badge !== '' ? array_values(array_filter(array_map('trim', explode('|', $badge)), 'strlen')) : [],
            'cat'   => $primary,
            'cats'  => $slugs,
            'vehicles' => $vslugs,
            'oem'   => (string) get_post_meta($p->ID, '_ap_oem_code', true),
            'url'   => get_permalink($p->ID),
            'image' => $gallery[0] ?? ($img ?: null),
            'images' => $gallery,
            'shortDesc' => (string) get_post_meta($p->ID, '_ap_short_desc', true),
            'colors' => array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($p->ID, '_ap_colors', true))))),
            'storages' => array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($p->ID, '_ap_storages', true))))),
            /* قیمتِ جداگانه‌ی هر متغیر: {"گروه||گزینه": قیمت} */
            'variantPrices' => (function () use ($p) {
                $raw = get_post_meta($p->ID, '_ap_variant_prices', true);
                if (!$raw) return (object) [];
                $decoded = json_decode($raw, true);
                return is_array($decoded) && $decoded ? $decoded : (object) [];
            })(),
            'variantGroups' => (function () use ($p) {
                $raw = get_post_meta($p->ID, '_ap_variant_groups', true);
                if (!$raw) return [];
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            })(),
            /* توضیحاتِ کامل (با اجرای فیلترهای سنگینِ the_content) فقط توی
               صفحه‌ی خودِ محصول لازمه؛ توی لیست فقط یه خلاصه‌ی ساده کافیه،
               همین یک مورد صدها بار اجرای the_content رو حذف کرد. */
            'description' => wp_trim_words(wp_strip_all_tags($p->post_content), 40),
        ];
    }

    set_transient($cacheKey, $out, 10 * MINUTE_IN_SECONDS);
    return $out;
}

/* با هر تغییر در یه محصول، کشِ لیست محصولات فوراً باطل می‌شه تا کاربر
   همیشه آخرین قیمت/موجودی/تغییرات رو ببینه، نه نسخه‌ی ۱۰ دقیقه‌ی قبل. */
/* (این هوک‌ها فقط وقتی وردپرس لود شده معنی دارن؛ در اکشن‌های سبک مثل ورود،
   وردپرس اصلاً لود نمی‌شه. هوک‌های اصلی در functions.php هم ثبت شدن.) */
if (function_exists('add_action')) {
    add_action('save_post_ap_product', function () { delete_transient('ap_products_cache_v3'); });
    add_action('delete_post', function ($post_id) {
        if (get_post_type($post_id) === 'ap_product') delete_transient('ap_products_cache_v3');
    });
    add_action('trashed_post', function ($post_id) {
        if (get_post_type($post_id) === 'ap_product') delete_transient('ap_products_cache_v3');
    });
}

function ap_wp_articles() {
    if (!function_exists('get_posts')) { return null; }
    $posts = get_posts([
        'post_type'      => 'ap_article',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);
    $out = [];
    foreach ($posts as $p) {
        $img = get_the_post_thumbnail_url($p->ID, 'large');
        $out[] = [
            'id'      => (string) $p->ID,
            'title'   => get_the_title($p),
            'excerpt' => (string) ($p->post_excerpt ?: wp_trim_words(wp_strip_all_tags($p->post_content), 30)),
            'content' => apply_filters('the_content', $p->post_content),
            'image'   => $img ?: null,
            'url'     => get_permalink($p->ID),
            'date'    => get_the_date('Y-m-d', $p),
        ];
    }
    return $out;
}

function ap_wp_category_tree() {
    if (!function_exists('get_terms')) { return null; }
    $cacheKey = 'ap_categories_cache_v1';
    $cached = get_transient($cacheKey);
    if (is_array($cached)) return $cached;

    $terms = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false]);
    if (is_wp_error($terms)) { return []; }
    $out = [];
    foreach ($terms as $t) {
        $link = get_term_link($t);
        $orderMeta = get_term_meta($t->term_id, 'ap_cat_order', true);
        $out[] = [
            'id'     => $t->term_id,
            'parent' => (int) $t->parent,
            'name'   => $t->name,
            'slug'   => $t->slug,
            'count'  => (int) $t->count,
            'url'    => (!is_wp_error($link)) ? $link : null,
            // اگه ترم از راه درون‌ریزیِ CSV ساخته شده باشه، ترتیبِ ردیف‌های فایل رو داره؛
            // ترم‌های قدیمی‌تر که این متا رو ندارن، بعد از همه (بر اساس شناسه) میان.
            '_order' => ($orderMeta === '' || $orderMeta === false) ? (1000000 + (int) $t->term_id) : (int) $orderMeta,
        ];
    }
    // خروجی رو بر اساسِ همین ترتیب مرتب کن؛ چیدمانِ فیلتر پیشرفته و منوها روی سایت هم از
    // همین آرایه ساخته می‌شه، پس اینجا مرتب بشه دیگه نیازی به مرتب‌سازی توی جاوااسکریپت نیست.
    usort($out, function ($a, $b) { return $a['_order'] <=> $b['_order']; });
    foreach ($out as &$row) { unset($row['_order']); }
    set_transient($cacheKey, $out, 30 * MINUTE_IN_SECONDS);
    return $out;
}
if (function_exists('add_action')) {
    add_action('edited_ap_cat', function () { delete_transient('ap_categories_cache_v1'); });
    add_action('created_ap_cat', function () { delete_transient('ap_categories_cache_v1'); });
    add_action('delete_ap_cat', function () { delete_transient('ap_categories_cache_v1'); });
}


/* =========================================================================
 * درختِ خودرو برای فیلترِ «انتخاب خودرو»: سطح ۱ برند (با لوگو)، سطح ۲ مدل،
 * سطح ۳ سال ساخت. ساختارش عیناً مثل خروجیِ دسته‌بندی‌هاست (id/parent/slug)
 * تا سمتِ سایت با همون منطق رندر بشه.
 * ========================================================================= */
function ap_wp_vehicle_tree() {
    if (!function_exists('get_terms')) { return null; }
    $cacheKey = 'ap_vehicles_cache_v1';
    $cached = get_transient($cacheKey);
    if (is_array($cached)) return $cached;

    $terms = get_terms(['taxonomy' => 'ap_vehicle', 'hide_empty' => false]);
    if (is_wp_error($terms)) { return []; }
    $out = [];
    foreach ($terms as $t) {
        $link    = get_term_link($t);
        $logo_id = get_term_meta($t->term_id, 'ap_brand_logo_id', true);
        $out[] = [
            'id'     => $t->term_id,
            'parent' => (int) $t->parent,
            'name'   => $t->name,
            'slug'   => $t->slug,
            'count'  => (int) $t->count,
            'logo'   => $logo_id ? (wp_get_attachment_image_url($logo_id, 'thumbnail') ?: '') : '',
            'url'    => (!is_wp_error($link)) ? $link : null,
        ];
    }
    /* سال‌ها از تازه به قدیم، و برند/مدل‌ها بر اساسِ نام مرتب می‌شن. */
    usort($out, function ($a, $b) {
        $an = (int) preg_replace('/[^0-9]/', '', $a['name']);
        $bn = (int) preg_replace('/[^0-9]/', '', $b['name']);
        if ($an && $bn && $a['parent'] === $b['parent']) { return $bn <=> $an; }
        return strcmp($a['name'], $b['name']);
    });
    set_transient($cacheKey, $out, 30 * MINUTE_IN_SECONDS);
    return $out;
}
if (function_exists('add_action')) {
    add_action('edited_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v1'); });
    add_action('created_ap_vehicle', function () { delete_transient('ap_vehicles_cache_v1'); });
    add_action('delete_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v1'); });
}

/* =========================================================================
 * پیکربندی ورود با گوگل — Client ID رو که از Google Cloud Console گرفتی اینجا بذار.
 * ========================================================================= */
define('GOOGLE_CLIENT_ID', '532662910524-npfu3mq694j19m3o777vc92ai4tik3bh.apps.googleusercontent.com');

/* =========================================================================
 * مسیر ذخیره‌سازی
 * ========================================================================= */
$DATA_DIR = null;
$DATA_URL = null;
if (function_exists('wp_upload_dir')) {
    $u = wp_upload_dir();
    if (!empty($u['basedir']) && is_writable($u['basedir'])) {
        $DATA_DIR = $u['basedir'] . '/ap-data';
        $DATA_URL = rtrim($u['baseurl'], '/') . '/ap-data';
    }
}
if (!$DATA_DIR) {
    $uploadsGuess = dirname(__DIR__, 2) . '/uploads';
    if (is_dir($uploadsGuess) && is_writable($uploadsGuess)) {
        $DATA_DIR = $uploadsGuess . '/ap-data';
    }
}
if (!$DATA_DIR) {
    $DATA_DIR = __DIR__ . '/data';
}
if (!$DATA_URL) {
    // فایل‌های داخل پوشه‌ی قالب هم به‌صورت پیش‌فرض از وب قابل دسترسن.
    $DATA_URL = rtrim(function_exists('get_template_directory_uri') ? get_template_directory_uri() : '', '/') . '/data';
}
$BACKUP_DIR   = $DATA_DIR . '/backups';
$RECEIPTS_DIR = $DATA_DIR . '/receipts';
$RECEIPTS_URL = $DATA_URL . '/receipts';
$DB_FILE      = $DATA_DIR . '/db.json';
$LATEST_JSON  = $BACKUP_DIR . '/latest.json';
$MAX_BACKUPS  = 30;

if (!is_dir($DATA_DIR))     { @mkdir($DATA_DIR, 0775, true); }
if (!is_dir($BACKUP_DIR))   { @mkdir($BACKUP_DIR, 0775, true); }
if (!is_dir($RECEIPTS_DIR)) { @mkdir($RECEIPTS_DIR, 0775, true); }

/* جلوگیری از دسترسیِ مستقیمِ وب به دیتابیس و بکاپ‌ها (که شاملِ رمزها و آدرس‌ها هستن).
   فایل‌های رسید عکس (png/jpg) هستن و همچنان برای مدیر قابل نمایش می‌مونن. روی هاست‌های
   Apache/LiteSpeed این .htaccess جلوی باز شدنِ db.json/بکاپ‌ها از مرورگر رو می‌گیره.
   (روی Nginx باید همین محدودیت در کانفیگ سرور اعمال بشه.) */
$dataHtaccess = $DATA_DIR . '/.htaccess';
if (!file_exists($dataHtaccess)) {
    @file_put_contents($dataHtaccess,
        "Options -Indexes\n" .
        "<FilesMatch \"\\.(json|bak.*)$\">\n" .
        "  Require all denied\n" .
        "  <IfModule !mod_authz_core.c>\n    Order allow,deny\n    Deny from all\n  </IfModule>\n" .
        "</FilesMatch>\n"
    );
}

/* تبدیل تاریخ میلادی به شمسی (جلالی) برای نمایش درستِ تاریخ روی فاکتور. */
function ap_gregorian_to_jalali($gy, $gm, $gd) {
    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2 = ($gm > 2) ? ($gy + 1) : $gy;
    $days = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100)
        + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy += 4 * intdiv($days, 1461);
    $days %= 1461;
    if ($days > 365) { $jy += intdiv($days - 1, 365); $days = ($days - 1) % 365; }
    if ($days < 186) { $jm = 1 + intdiv($days, 31); $jd = 1 + ($days % 31); }
    else { $jm = 7 + intdiv($days - 186, 30); $jd = 1 + (($days - 186) % 30); }
    return [$jy, $jm, $jd];
}

function json_fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

function default_products() {
    // قبلاً اینجا چندتا محصول نمونه به‌عنوان Placeholder بود.
    // طبق دستور صریح، دیگه هیچ محصول Demo/پیش‌فرضی نباید وجود داشته باشه؛ اگه محصولات
    // واقعی هنوز از پیشخوان وردپرس لود نشده باشن، باید آرایه‌ی خالی برگرده، نه چندتا
    // محصول ساختگی که مشتری فکر کنه این‌ها محصولات واقعی سایت هستن.
    return [];
}

function default_db() {
    return [
        'products' => default_products(),
        'users' => [
            [
                'id' => 1,
                'firstName' => 'مدیر',
                'lastName' => 'سایت',
                'phone' => '09109973428',
                'email' => null,
                'google_id' => null,
                'password_hash' => null,
                'is_admin' => true,
                'addresses' => [],
                'created_at' => date('c'),
            ],
        ],
        'next_user_id' => 2,
        'orders' => [],
        'next_order_id' => 1,
        'next_address_id' => 1,
    ];
}

/**
 * خواندن دیتابیس؛ اگه فایل نبود از آخرین بکاپ بازسازی می‌شه، وگرنه از صفر ساخته می‌شه.
 */
function read_db($DB_FILE, $LATEST_JSON) {
    if (file_exists($DB_FILE)) {
        $raw = file_get_contents($DB_FILE);
        $data = json_decode($raw, true);
        if (is_array($data)) return $data;
    }
    if (file_exists($LATEST_JSON)) {
        $raw = file_get_contents($LATEST_JSON);
        $data = json_decode($raw, true);
        if (is_array($data)) {
            write_db_raw($DB_FILE, $data);
            return $data;
        }
    }
    $fresh = default_db();
    write_db_raw($DB_FILE, $fresh);
    return $fresh;
}

function write_db_raw($DB_FILE, $data, $json = null) {
    if ($json === null) { $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); }
    $fp = fopen($DB_FILE, 'c');
    if ($fp) {
        flock($fp, LOCK_EX);
        ftruncate($fp, 0);
        fwrite($fp, $json);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

/* ذخیره‌ی دیتابیس.
   قبلاً هر بار که چیزی ذخیره می‌شد (حتی ورود ساده که فقط یک مهاجرتِ کوچک روی
   دیتابیس انجام می‌داد)، سه‌بار کلِ دیتابیس روی دیسک نوشته می‌شد و دوبار هم
   JSON کامل کدگذاری می‌شد؛ روی دیتابیسِ چندهزار مشتری این یعنی چند ثانیه تأخیر
   روی همون درخواستِ ورود. حالا:
   - JSON فقط یک‌بار ساخته می‌شه و بین هر سه نوشتن مشترکه،
   - از نسخه‌ی پشتیبانِ زمان‌دار حداکثر هر ۱۰ دقیقه یکی ساخته می‌شه (نه هر درخواست)،
   - و لیستِ بکاپ‌ها فقط وقتی مرتب/هرس می‌شه که واقعاً بکاپِ جدیدی ساخته شده باشه.
   $light = true یعنی این ذخیره فقط یک تعمیرِ داخلیه (نه تغییرِ واقعیِ داده‌ی کاربر)،
   پس اصلاً بکاپِ زمان‌دار نمی‌خواد. */
function save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $data, $light = false) {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    write_db_raw($DB_FILE, $data, $json);
    file_put_contents($LATEST_JSON, $json);
    if ($light) { return; }

    $lastBackup = 0;
    $stamp = $BACKUP_DIR . '/.last-backup';
    if (file_exists($stamp)) { $lastBackup = (int) file_get_contents($stamp); }
    if (time() - $lastBackup < 600) { return; }   // حداکثر هر ۱۰ دقیقه یک بکاپ

    file_put_contents($BACKUP_DIR . '/backup-' . date('Ymd-His') . '.json', $json);
    file_put_contents($stamp, (string) time());

    $files = glob($BACKUP_DIR . '/backup-*.json');
    if ($files && count($files) > $MAX_BACKUPS) {
        usort($files, function ($a, $b) { return filemtime($a) <=> filemtime($b); });
        $toDelete = array_slice($files, 0, count($files) - $MAX_BACKUPS);
        foreach ($toDelete as $f) { @unlink($f); }
    }
}

function public_user($u) {
    unset($u['password_hash']);
    unset($u['password_plain']);
    return $u;
}

/* بررسی دسترسیِ مدیر: فقط سشنِ واقعیِ مدیرِ وردپرس (که هنگام ورودِ مالک از فرم
   سایت به‌صورت خودکار ساخته می‌شه) اجازه‌ی اکشن‌های مدیریتی رو داره. قبلاً این
   بررسی فقط با requesterId ای بود که از سمت مرورگر فرستاده می‌شد و هرکسی می‌تونست
   جعلش کنه؛ حالا سمتِ سرور و غیرقابل‌جعله. */
function ap_require_admin($cap = 'manage_options') {
    ap_boot_wp();
    if (!(function_exists('current_user_can') && (current_user_can('manage_options') || current_user_can($cap)))) {
        json_fail('دسترسی غیرمجاز.', 403);
    }
}

$db = read_db($DB_FILE, $LATEST_JSON);

/* ---------- migrate/repair older db.json files ---------- */
$db_dirty = false;
if (!isset($db['orders']) || !is_array($db['orders'])) { $db['orders'] = []; $db_dirty = true; }
if (!isset($db['next_order_id'])) {
    $maxId = 0;
    foreach ($db['orders'] as $o) { $maxId = max($maxId, (int) ($o['id'] ?? 0)); }
    $db['next_order_id'] = $maxId + 1;
    $db_dirty = true;
}
if (!isset($db['next_address_id'])) { $db['next_address_id'] = 1; $db_dirty = true; }

foreach ($db['users'] as &$u) {
    if (!isset($u['addresses']) || !is_array($u['addresses'])) {
        $u['addresses'] = [];
        // اگه از نسخه‌ی قبلی، آدرس تخت (province/city/...) روی خودِ کاربر ذخیره شده، به یک آدرس توی لیست جدید تبدیلش کن.
        $legacyAddr = trim((string) ($u['fullAddress'] ?? ''));
        if ($legacyAddr !== '') {
            $u['addresses'][] = [
                'id' => $db['next_address_id']++,
                'label' => 'آدرس اصلی',
                'province' => $u['province'] ?? '', 'city' => $u['city'] ?? '',
                'district' => $u['district'] ?? '', 'plaque' => $u['plaque'] ?? '',
                'unit' => $u['unit'] ?? '', 'postalCode' => $u['postalCode'] ?? '',
                'fullAddress' => $legacyAddr,
            ];
        }
        $db_dirty = true;
    }
}
unset($u);

/* ادمین‌ها (مالک و زیرمجموعه‌ها) از پیشخوان ← مدیریت ادمین‌ها تعیین می‌شن و staff.php
   فهرستشون رو با فیلد wp_user_id توی همین دیتابیس همگام نگه می‌داره. */
if ($db_dirty) { save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db, true); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($action) {

    case 'products': {
        $wpProducts = ap_wp_products();
        if (is_array($wpProducts) && count($wpProducts) > 0) {
            echo json_encode(['success' => true, 'products' => $wpProducts, 'source' => 'wp'], JSON_UNESCAPED_UNICODE);
        } else {
            // دیگه هیچ‌وقت محصولات Demo/پیش‌فرض جایگزین نمی‌شن؛ اگه محصولی از پیشخوان
            // وردپرس برنگشت، صادقانه یه آرایه‌ی خالی برمی‌گردونیم تا سمتِ سایت بفهمه
            // واقعاً محصولی نیست (یا مشکلی پیش اومده)، نه اینکه محصولاتِ ساختگی نشون بده.
            echo json_encode(['success' => true, 'products' => [], 'source' => 'empty'], JSON_UNESCAPED_UNICODE);
        }
        break;
    }

    case 'categories': {
        $tree = ap_wp_category_tree();
        echo json_encode(['success' => true, 'categories' => $tree ?: []], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'vehicles': {
        $tree = ap_wp_vehicle_tree();
        echo json_encode(['success' => true, 'vehicles' => $tree ?: []], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'articles': {
        $articles = ap_wp_articles();
        echo json_encode(['success' => true, 'articles' => $articles ?: []], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'add_product': {
        ap_require_admin();
        $p = $input;
        if (empty($p['title']) || !isset($p['price'])) json_fail('عنوان و قیمت الزامی است.');
        $id = $p['id'] ?? ('p' . substr(md5(uniqid('', true)), 0, 8));
        $db['products'][] = [
            'id' => $id, 'cat' => $p['cat'] ?? 'acc', 'color' => $p['color'] ?? '#ff3b4e',
            'ic' => $p['ic'] ?? 'disc', 'title' => $p['title'], 'spec' => $p['spec'] ?? '',
            'price' => (int) $p['price'], 'badge' => $p['badge'] ?? null,
        ];
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'update_product': {
        ap_require_admin();
        $p = $input;
        if (empty($p['id'])) json_fail('شناسه‌ی محصول الزامی است.');
        foreach ($db['products'] as &$row) {
            if ($row['id'] === $p['id']) {
                foreach (['cat','color','ic','title','spec','badge'] as $f) {
                    if (isset($p[$f])) $row[$f] = $p[$f];
                }
                if (isset($p['price'])) $row['price'] = (int) $p['price'];
            }
        }
        unset($row);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'delete_product': {
        ap_require_admin();
        $id = $input['id'] ?? $_GET['id'] ?? '';
        if (!$id) json_fail('شناسه‌ی محصول الزامی است.');
        $db['products'] = array_values(array_filter($db['products'], function ($row) use ($id) { return $row['id'] !== $id; }));
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'register': {
        $phone = preg_replace('/[^0-9]/', '', $input['phone'] ?? '');
        $password = (string) ($input['password'] ?? '');
        $firstName = trim($input['firstName'] ?? '');
        $lastName  = trim($input['lastName'] ?? '');

        if (!preg_match('/^09\d{9}$/', $phone)) json_fail('شماره موبایل معتبر نیست.');
        if ($firstName === '' || $lastName === '') json_fail('نام و نام خانوادگی الزامی است.');
        if (strlen($password) < 6) json_fail('رمز عبور باید حداقل ۶ کاراکتر باشد.');

        foreach ($db['users'] as $u) {
            if ($u['phone'] === $phone) json_fail('کاربری با این شماره موبایل قبلاً ثبت‌نام کرده است. لطفاً وارد شوید.');
        }

        $newUser = [
            'id' => $db['next_user_id'],
            'firstName' => $firstName,
            'lastName' => $lastName,
            'phone' => $phone,
            'email' => null,
            'google_id' => null,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'password_plain' => $password,
            'is_admin' => false,
            'addresses' => [],
            'created_at' => date('c'),
        ];
        $db['users'][] = $newUser;
        $db['next_user_id']++;
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($newUser)], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'login': {
        $identifier = trim((string) ($input['identifier'] ?? $input['phone'] ?? ''));
        $password = (string) ($input['password'] ?? '');
        $found = null;
        if (strpos($identifier, '@') !== false) {
            $emailLower = strtolower($identifier);
            foreach ($db['users'] as $u) {
                if (strtolower((string) ($u['email'] ?? '')) === $emailLower) { $found = $u; break; }
            }
        } else {
            $phoneDigits = preg_replace('/[^0-9]/', '', $identifier);
            foreach ($db['users'] as $u) {
                if ($u['phone'] === $phoneDigits) { $found = $u; break; }
            }
        }
        /* ادمین‌ها: رمز با حساب وردپرسِ خودشون چک می‌شه و سشن وردپرس هم براشون باز می‌شه.
           کاربرِ عادی اصلاً وردپرس رو لود نمی‌کنه. */
        $staffId = $found ? (int) ($found['wp_user_id'] ?? 0) : 0;
        if ($staffId) {
            ap_boot_wp();
            $wpUser = function_exists('get_user_by') ? get_user_by('id', $staffId) : null;
            $ok = $wpUser && wp_check_password($password, $wpUser->user_pass, $wpUser->ID)
                && (user_can($wpUser, 'manage_options') || in_array('ap_staff', (array) $wpUser->roles, true));
            if (!$ok) json_fail('شماره موبایل/ایمیل یا رمز عبور اشتباه است.', 401);
            wp_clear_auth_cookie();
            wp_set_current_user($wpUser->ID);
            wp_set_auth_cookie($wpUser->ID, true);
        } elseif (!$found || empty($found['password_hash']) || !password_verify($password, $found['password_hash'])) {
            json_fail('شماره موبایل/ایمیل یا رمز عبور اشتباه است.', 401);
        }
        echo json_encode(['success' => true, 'user' => public_user($found)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- خروج: هم حساب فروشگاه (سمت کاربر با جاوااسکریپت) هم سشن واقعی وردپرس (اگه بریج شده بود) پاک بشه ---------- */
    case 'logout': {
        // فقط اگه واقعاً سشنِ وردپرسی وجود داره (یعنی مدیر بوده) وردپرس لود می‌شه
        $hasWpCookie = false;
        foreach (array_keys($_COOKIE) as $ck) { if (strpos($ck, 'wordpress_logged_in') === 0) { $hasWpCookie = true; break; } }
        if ($hasWpCookie) { ap_boot_wp(); }
        if (function_exists('wp_logout')) { wp_logout(); }
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- ورود/ثبت‌نام با گوگل ---------- */
    case 'google_login': {
        $credential = $input['credential'] ?? '';
        if (!$credential) json_fail('توکن گوگل ارسال نشده است.');

        $verifyUrl = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
        $ctx = stream_context_create(['http' => ['timeout' => 15]]);
        $response = @file_get_contents($verifyUrl, false, $ctx);
        if ($response === false) json_fail('اعتبارسنجی توکن گوگل ممکن نشد.', 500);

        $payload = json_decode($response, true);
        if (empty($payload['email']) || empty($payload['sub'])) {
            json_fail('توکن گوگل معتبر نیست.', 400);
        }
        if (GOOGLE_CLIENT_ID !== '' && ($payload['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
            json_fail('توکن گوگل برای این سایت صادر نشده است.', 400);
        }

        $email = strtolower($payload['email']);
        $googleId = $payload['sub'];
        $firstName = $payload['given_name'] ?? ($payload['name'] ?? 'کاربر');
        $lastName  = $payload['family_name'] ?? '';

        $found = null;
        foreach ($db['users'] as &$u) {
            if (($u['email'] ?? null) === $email || ($u['google_id'] ?? null) === $googleId) {
                $u['google_id'] = $googleId;
                $found = $u;
                break;
            }
        }
        unset($u);

        if (!$found) {
            $newUser = [
                'id' => $db['next_user_id'],
                'firstName' => $firstName,
                'lastName' => $lastName,
                'phone' => null,
                'email' => $email,
                'google_id' => $googleId,
                'password_hash' => null,
                'is_admin' => false,
                'created_at' => date('c'),
            ];
            $db['users'][] = $newUser;
            $db['next_user_id']++;
            $found = $newUser;
        }

        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($found)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- ویرایش اطلاعات شخصی (نام، نام‌خانوادگی، تماس، ایمیل) ---------- */
    case 'update_profile': {
        $id = (int) ($input['id'] ?? 0);
        if (!$id) json_fail('ابتدا وارد حساب کاربری خود شوید.');

        $firstName = trim((string) ($input['firstName'] ?? ''));
        $lastName  = trim((string) ($input['lastName'] ?? ''));
        $phone     = trim((string) ($input['phone'] ?? ''));
        $email     = trim((string) ($input['email'] ?? ''));
        if ($firstName === '' || $lastName === '' || $phone === '') {
            json_fail('نام، نام‌خانوادگی و شماره تماس الزامی است.');
        }
        if (!preg_match('/^09\d{9}$/', $phone)) json_fail('شماره موبایل باید ۱۱ رقم و به‌صورت 09xxxxxxxxx باشد.');

        $updated = null;
        foreach ($db['users'] as &$u) {
            if ((int) $u['id'] === $id) {
                $u['firstName'] = $firstName;
                $u['lastName']  = $lastName;
                $u['phone']     = $phone;
                if ($email !== '') $u['email'] = $email;
                $updated = $u;
                break;
            }
        }
        unset($u);

        if (!$updated) json_fail('کاربر پیدا نشد.', 404);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($updated)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- افزودن یک آدرس جدید ---------- */
    case 'add_address': {
        $id = (int) ($input['id'] ?? 0);
        if (!$id) json_fail('ابتدا وارد حساب کاربری خود شوید.');

        $addr = [
            'id'          => $db['next_address_id'],
            'label'       => trim((string) ($input['label'] ?? '')) ?: 'آدرس',
            'province'    => trim((string) ($input['province'] ?? '')),
            'city'        => trim((string) ($input['city'] ?? '')),
            'district'    => trim((string) ($input['district'] ?? '')),
            'plaque'      => trim((string) ($input['plaque'] ?? '')),
            'unit'        => trim((string) ($input['unit'] ?? '')),
            'postalCode'  => trim((string) ($input['postalCode'] ?? '')),
            'fullAddress' => trim((string) ($input['fullAddress'] ?? '')),
        ];
        foreach (['province', 'city', 'district', 'plaque', 'unit', 'postalCode', 'fullAddress'] as $f) {
            if ($addr[$f] === '') json_fail('لطفاً همه‌ی فیلدهای آدرس را کامل کنید.');
        }
        if (!preg_match('/^\d{10}$/', $addr['postalCode'])) json_fail('کد پستی باید دقیقاً ۱۰ رقم باشد.');

        $updated = null;
        foreach ($db['users'] as &$u) {
            if ((int) $u['id'] === $id) {
                if (!isset($u['addresses']) || !is_array($u['addresses'])) $u['addresses'] = [];
                $u['addresses'][] = $addr;
                $updated = $u;
                break;
            }
        }
        unset($u);
        if (!$updated) json_fail('کاربر پیدا نشد.', 404);
        $db['next_address_id']++;
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($updated)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- ویرایش یک آدرس موجود ---------- */
    case 'update_address': {
        $id = (int) ($input['id'] ?? 0);
        $addressId = (int) ($input['addressId'] ?? 0);
        if (!$id || !$addressId) json_fail('اطلاعات ناقص است.');
        if (isset($input['postalCode']) && !preg_match('/^\d{10}$/', trim((string) $input['postalCode']))) {
            json_fail('کد پستی باید دقیقاً ۱۰ رقم باشد.');
        }

        $updated = null;
        foreach ($db['users'] as &$u) {
            if ((int) $u['id'] === $id) {
                foreach ($u['addresses'] as &$a) {
                    if ((int) $a['id'] === $addressId) {
                        foreach (['label', 'province', 'city', 'district', 'plaque', 'unit', 'postalCode', 'fullAddress'] as $f) {
                            if (isset($input[$f])) $a[$f] = trim((string) $input[$f]);
                        }
                        break;
                    }
                }
                unset($a);
                $updated = $u;
                break;
            }
        }
        unset($u);
        if (!$updated) json_fail('کاربر یا آدرس پیدا نشد.', 404);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($updated)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- حذف یک آدرس ---------- */
    case 'delete_address': {
        $id = (int) ($input['id'] ?? 0);
        $addressId = (int) ($input['addressId'] ?? 0);
        if (!$id || !$addressId) json_fail('اطلاعات ناقص است.');

        $updated = null;
        foreach ($db['users'] as &$u) {
            if ((int) $u['id'] === $id) {
                $u['addresses'] = array_values(array_filter($u['addresses'], function ($a) use ($addressId) { return (int) $a['id'] !== $addressId; }));
                $updated = $u;
                break;
            }
        }
        unset($u);
        if (!$updated) json_fail('کاربر پیدا نشد.', 404);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'user' => public_user($updated)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- ثبت سفارش (کارت‌به‌کارت) + بارگذاری تصویر رسید ---------- */
    case 'create_order': {
        $userId = (int) ($input['userId'] ?? 0);
        $addressId = (int) ($input['addressId'] ?? 0);
        $items  = $input['items'] ?? [];
        $shippingMethod = (string) ($input['shippingMethod'] ?? 'post');
        /* هزینه‌ی ارسال هرگز از سمت مرورگر پذیرفته نمی‌شه؛ سمتِ سرور و بر اساسِ
           همون روش‌های ارسالِ تعریف‌شده‌ی سایت محاسبه می‌شه (فقط پست هزینه داره،
           بقیه پس‌کرایه‌ان). مقدارِ پست با همونی که در سایت به مشتری نشون داده می‌شه یکیه. */
        $AP_SHIPPING_FEES = ['post' => 190000, 'tipax' => 0, 'mahex' => 0, 'peyk' => 0];
        $shippingFee = $AP_SHIPPING_FEES[$shippingMethod] ?? 190000;
        $itemsTotal = 0; // پایین‌تر از روی قیمتِ واقعیِ محصولات (نه ورودیِ کاربر) محاسبه می‌شه
        $receiptData = (string) ($input['receiptImage'] ?? '');

        if (!$userId) json_fail('ابتدا وارد حساب کاربری خود شوید.');
        if (!$addressId) json_fail('لطفاً یک آدرس برای ارسال انتخاب کنید.');
        if (empty($items) || !is_array($items)) json_fail('سبد خرید خالی است.');
        if ($receiptData === '') json_fail('لطفاً تصویر رسید واریزی را ارسال کنید.');

        $user = null;
        foreach ($db['users'] as $u) { if ((int) $u['id'] === $userId) { $user = $u; break; } }
        if (!$user) json_fail('کاربر پیدا نشد.', 404);

        $address = null;
        foreach (($user['addresses'] ?? []) as $a) { if ((int) $a['id'] === $addressId) { $address = $a; break; } }
        if (!$address) json_fail('آدرس انتخاب‌شده پیدا نشد.');

        if (!preg_match('/^data:image\/(png|jpe?g|webp);base64,(.+)$/i', $receiptData, $m)) {
            json_fail('فرمت تصویر رسید نامعتبر است.');
        }
        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $bin = base64_decode($m[2]);
        if ($bin === false || strlen($bin) < 100) json_fail('تصویر رسید قابل خواندن نیست.');
        if (strlen($bin) > 6 * 1024 * 1024) json_fail('حجم تصویر رسید زیاد است (حداکثر ۶ مگابایت).');

        $orderId  = $db['next_order_id'];
        $filename = 'receipt_' . $orderId . '_' . time() . '_' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;
        if (@file_put_contents($RECEIPTS_DIR . '/' . $filename, $bin) === false) {
            json_fail('ذخیره‌ی تصویر رسید ممکن نشد.', 500);
        }

        /* قیمتِ هر کالا از قیمتِ واقعیِ همون محصول در پیشخوان وردپرس خونده می‌شه،
           نه از مقداری که مرورگر فرستاده؛ این‌طوری کسی نمی‌تونه با دستکاریِ درخواست،
           محصول رو ارزون‌تر ثبت کنه. کلیدِ سبد ممکنه شاملِ متغیر (رنگ/حجم) باشه، پس
           شناسه‌ی پایه‌ی محصول از قبل از «::» جدا می‌شه. */
        $priceMap = [];
        $variantPriceMap = [];
        $wpProductsForOrder = function_exists('ap_wp_products') ? ap_wp_products() : [];
        if (is_array($wpProductsForOrder)) {
            foreach ($wpProductsForOrder as $wpp) {
                $priceMap[(string) $wpp['id']] = (int) $wpp['price'];
                $vp = $wpp['variantPrices'] ?? null;
                $variantPriceMap[(string) $wpp['id']] = is_array($vp) ? $vp : (array) $vp;
            }
        }
        $cleanItems = [];
        $computedTotal = 0;
        foreach ($items as $it) {
            $rawId  = (string) ($it['id'] ?? '');
            $baseId = explode('::', $rawId)[0];
            $qty    = max(1, (int) ($it['qty'] ?? 1));
            $price  = isset($priceMap[$baseId]) ? $priceMap[$baseId] : (int) ($it['price'] ?? 0);
            /* اگه متغیرِ انتخاب‌شده قیمتِ جدا داشته باشه، همون ملاکه */
            $price  = ap_variant_price_for_key($price, $variantPriceMap[$baseId] ?? [], $rawId);
            $computedTotal += $price * $qty;
            $cleanItems[] = [
                'id'    => $rawId,
                'title' => (string) ($it['title'] ?? ''),
                'qty'   => $qty,
                'price' => $price,
            ];
        }
        $itemsTotal = $computedTotal;

        $order = [
            'id' => $orderId,
            'userId' => $userId,
            'customerName' => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')),
            'phone' => $user['phone'] ?? '',
            'address' => $address,
            'items' => $cleanItems,
            'itemsTotal' => $itemsTotal,
            'shippingFee' => $shippingFee,
            'shippingMethod' => $shippingMethod,
            'grandTotal' => $itemsTotal + $shippingFee,
            'receiptUrl' => $RECEIPTS_URL . '/' . $filename,
            'status' => 'pending',
            'created_at' => date('c'),
        ];
        $db['orders'][] = $order;
        $db['next_order_id']++;
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'order' => $order], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- سفارش‌های خودِ مشتری (قسمت «سفارشات من») ---------- */
    case 'list_my_orders': {
        $userId = (int) ($_GET['userId'] ?? $input['userId'] ?? 0);
        if (!$userId) json_fail('ابتدا وارد حساب کاربری خود شوید.');
        $mine = array_values(array_filter($db['orders'], function ($o) use ($userId) { return (int) $o['userId'] === $userId; }));
        $mine = array_reverse($mine);
        echo json_encode(['success' => true, 'orders' => $mine], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- پیشخوان مدیریت: فهرست سفارش‌ها (فقط ادمین) ---------- */
    case 'list_orders': {
        ap_require_admin('ap_manage_orders');
        $orders = array_reverse($db['orders']);
        echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- پیشخوان مدیریت: صفحه‌ی قابل‌چاپِ فاکتور برای یک سفارش (فقط ادمین) ----------
       این اکشن به‌جای JSON، یک صفحه‌ی HTML مستقل و آماده‌ی چاپ (قواره‌ی A5) برمی‌گردونه؛
       ادمین از پیشخوان روش کلیک می‌کنه، توی تب جدید باز می‌شه، و از همون‌جا Ctrl+P یا
       دکمه‌ی چاپِ خودِ صفحه رو می‌زنه (خروجی چاپگر یا PDF، برای چسبوندن روی کارتن). */
    case 'invoice': {
        if (!(function_exists('current_user_can') && (current_user_can('manage_options') || current_user_can('ap_manage_orders')))) { http_response_code(403); header('Content-Type: text/plain; charset=utf-8'); echo 'دسترسی غیرمجاز.'; break; }

        $orderId = (int) ($_GET['order_id'] ?? 0);
        $order = null;
        foreach ($db['orders'] as $o) { if ((int) $o['id'] === $orderId) { $order = $o; break; } }
        if (!$order) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'سفارش پیدا نشد.'; break; }

        header('Content-Type: text/html; charset=utf-8');

        $siteNameEn = 'AUTOPARTS';
        $shipLabels = [
            'post'  => 'پست پیشتاز',
            'tipax' => 'تیپاکس (پس‌کرایه)',
            'mahex' => 'ماهکس (پس‌کرایه)',
            'peyk'  => 'پیک - فقط تهران (پس‌کرایه)',
        ];
        $shipMethod = $order['shippingMethod'] ?? 'post';
        $shipLabel = $shipLabels[$shipMethod] ?? 'پست پیشتاز';
        $isCod = $shipMethod !== 'post';

        /* شماره‌ی سفارش: پیشوندِ فروشگاه + عددِ یکتای همون سفارش (که مخصوص به همون مشتریه و
           تکراری نمی‌شه)، به‌فرمتِ ۶ رقمی برای یکدست بودنِ ظاهرِ فاکتورها. */
        $orderNumber = 'AP-' . str_pad((string) $order['id'], 6, '0', STR_PAD_LEFT);
        $fa_digits = ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'];
        $faNum = function ($s) use ($fa_digits) { return strtr((string) $s, $fa_digits); };
        $fmt = function ($n) { return number_format((float) $n); };

        $createdAt = $order['created_at'] ?? date('c');
        $dateObj = date_create($createdAt) ?: new DateTime();
        [$jY, $jM, $jD] = ap_gregorian_to_jalali((int) date_format($dateObj, 'Y'), (int) date_format($dateObj, 'n'), (int) date_format($dateObj, 'j'));
        $jDate = sprintf('%04d/%02d/%02d', $jY, $jM, $jD); // تاریخ شمسی
        $timeStr = date_format($dateObj, 'H:i');

        $address = $order['address'] ?? [];
        $addrProvince = $address['province'] ?? '';
        $addrCity = $address['city'] ?? '';
        $addrDetail = trim(
            (($address['district'] ?? '') !== '' ? ($address['district'] . '، ') : '')
            . (($address['fullAddress'] ?? ($address['line'] ?? ($address['full'] ?? ($address['text'] ?? '')))) ?? '')
            . (($address['plaque'] ?? '') !== '' ? ('، پلاک ' . $address['plaque']) : '')
            . (($address['unit'] ?? '') !== '' ? ('، واحد ' . $address['unit']) : '')
        );
        $postalCode = $address['postalCode'] ?? ($address['postal_code'] ?? '');

        $itemsHtml = '';
        foreach (($order['items'] ?? []) as $it) {
            $lineTotal = (int) $it['price'] * (int) $it['qty'];
            $itemsHtml .= '<tr>'
                . '<td class="c-title">' . htmlspecialchars($it['title'], ENT_QUOTES, 'UTF-8') . '</td>'
                . '<td class="c-num">' . $faNum($it['qty']) . '</td>'
                . '<td class="c-num">' . $faNum($fmt($it['price'])) . '</td>'
                . '<td class="c-num">' . $faNum($fmt($lineTotal)) . '</td>'
                . '</tr>';
        }

        $shippingCell = $isCod ? 'پس‌کرایه (دریافت توسط باربری)' : ($faNum($fmt($order['shippingFee'])) . ' تومان');
        $grandTotalCell = $isCod
            ? ($faNum($fmt($order['itemsTotal'])) . ' تومان + پس‌کرایه')
            : ($faNum($fmt($order['grandTotal'])) . ' تومان');
        ?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<title>فاکتور سفارش <?php echo htmlspecialchars($orderNumber); ?></title>
<link rel="preconnect" href="https://cdn.jsdelivr.net">
<link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet" type="text/css">
<style>
  @page { size: A5; margin: 10mm; }
  * { box-sizing: border-box; }
  body {
    font-family: 'Vazirmatn', Tahoma, sans-serif; margin: 0; padding: 14px; color: #201f2b; font-size: 12.5px;
    -webkit-print-color-adjust: exact; print-color-adjust: exact;
  }
  .head { display: flex; align-items: center; gap: 10px; justify-content: flex-start; border-bottom: 3px solid #ff3b4e; padding-bottom: 12px; margin-bottom: 16px; }
  .head .logo-dot { width: 30px; height: 30px; border-radius: 9px; background: linear-gradient(135deg,#ff3b4e,#ff7b86); flex-shrink: 0; }
  .head .brand-en { font-family: 'Vazirmatn', Tahoma, sans-serif; font-size: 24px; font-weight: 800; letter-spacing: .3px; color: #201f2b; direction: ltr; line-height: 1.1; }
  .head .brand-en small { display: block; font-size: 10.5px; font-weight: 500; color: #7a7788; margin-top: 3px; direction: rtl; }
  h1 { font-size: 16px; margin: 0 0 12px; text-align: center; font-weight: 800; letter-spacing: .3px; }
  .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 7px 18px; background: #f7f6fb; border: 1px solid #e3e0f5; border-radius: 10px; padding: 12px 14px; margin-bottom: 14px; font-size: 11.5px; }
  .meta-grid div span.k { color: #7a7788; }
  .meta-grid div span.v { font-weight: 700; }
  .addr-block { grid-column: 1 / -1; display: flex; flex-direction: column; gap: 2px; }
  .addr-block .k { color: #7a7788; }
  .addr-block .addr-line { font-weight: 700; }
  .addr-block .addr-line.detail { font-weight: 500; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 11.5px; }
  th { background: #ff3b4e; color: #fff; padding: 8px 6px; text-align: center; font-weight: 700; }
  th:first-child { border-radius: 6px 0 0 0; }
  th:last-child { border-radius: 0 6px 0 0; }
  td { padding: 7px 6px; border-bottom: 1px solid #eee; text-align: center; }
  td.c-title { text-align: right; font-weight: 600; }
  td.c-num, th.c-num { text-align: center; }
  tfoot td { font-weight: 700; border-top: 2px solid #ff3b4e; border-bottom: none; }
  .totals { margin-top: -4px; margin-bottom: 14px; }
  .totals div { display: flex; justify-content: space-between; padding: 4px 4px; font-size: 12px; }
  .totals div.grand { font-weight: 800; font-size: 14.5px; color: #ff3b4e; border-top: 1px dashed #ccc; padding-top: 8px; margin-top: 4px; }
  .foot-note { font-size: 10.5px; color: #7a7788; text-align: center; margin-top: 18px; border-top: 1px dashed #ccc; padding-top: 10px; }
  .print-btn { display: block; width: 100%; margin-bottom: 14px; padding: 10px; background: #ff3b4e; color: #fff; border: none; border-radius: 8px; font-size: 13px; font-family: inherit; font-weight: 700; cursor: pointer; }
  @media print { .print-btn { display: none; } body { padding: 0; } }
</style>
</head>
<body>
  <button class="print-btn" onclick="window.print()">🖨 چاپ فاکتور / ذخیره PDF</button>

  <div class="head">
    <div class="logo-dot"></div>
    <div class="brand-en"><?php echo htmlspecialchars($siteNameEn); ?><small>فروشگاه قطعات یدکی خودرو</small></div>
  </div>

  <h1>فاکتور فروش</h1>

  <div class="meta-grid">
    <div><span class="k">شماره سفارش: </span><span class="v"><?php echo htmlspecialchars($orderNumber); ?></span></div>
    <div><span class="k">تاریخ: </span><span class="v"><?php echo $faNum($jDate) . ' - ' . $faNum($timeStr); ?></span></div>
    <div><span class="k">نام مشتری: </span><span class="v"><?php echo htmlspecialchars($order['customerName'] ?? ''); ?></span></div>
    <div><span class="k">شماره تماس: </span><span class="v" style="direction:ltr; display:inline-block;"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></span></div>
    <div class="addr-block">
      <span class="k">آدرس:</span>
      <?php if ($addrProvince): ?><span class="addr-line"><?php echo htmlspecialchars($addrProvince); ?></span><?php endif; ?>
      <?php if ($addrCity): ?><span class="addr-line"><?php echo htmlspecialchars($addrCity); ?></span><?php endif; ?>
      <?php if ($addrDetail): ?><span class="addr-line detail"><?php echo htmlspecialchars($addrDetail); ?></span><?php endif; ?>
    </div>
    <?php if ($postalCode): ?><div><span class="k">کد پستی: </span><span class="v" style="direction:ltr; display:inline-block;"><?php echo htmlspecialchars($postalCode); ?></span></div><?php endif; ?>
    <div><span class="k">روش ارسال: </span><span class="v"><?php echo htmlspecialchars($shipLabel); ?></span></div>
  </div>

  <table>
    <thead>
      <tr><th>شرح کالا</th><th class="c-num">تعداد</th><th class="c-num">قیمت واحد (تومان)</th><th class="c-num">جمع (تومان)</th></tr>
    </thead>
    <tbody><?php echo $itemsHtml; ?></tbody>
  </table>

  <div class="totals">
    <div><span>جمع کالاها</span><span><?php echo $faNum($fmt($order['itemsTotal'])); ?> تومان</span></div>
    <div><span>هزینه ارسال</span><span><?php echo $shippingCell; ?></span></div>
    <div class="grand"><span>مبلغ نهایی</span><span><?php echo $grandTotalCell; ?></span></div>
  </div>

  <div class="foot-note">
    این فاکتور به‌صورت خودکار از فروشگاه <?php echo htmlspecialchars($siteNameEn); ?> صادر شده است. شماره سفارش را برای پیگیری نزد خود نگه دارید.
  </div>
</body>
</html>
<?php
        break;
    }

    /* ---------- پیشخوان مدیریت: فهرست مشتریان (فقط ادمین) ----------
       شامل رمز عبورِ خواناست (فقط برای این‌که در صورت فراموشی، مدیر بتونه
       به مشتری بگه رمزش چیه)؛ توجه: این یعنی رمزها اینجا به‌صورت متن ساده
       هم نگه داشته می‌شن، پس این فهرست باید فقط دست ادمین باشه. */
    case 'list_customers': {
        ap_require_admin();
        $customers = [];
        foreach ($db['users'] as $u) {
            if (!empty($u['is_admin'])) continue;
            $customers[] = [
                'id' => $u['id'],
                'firstName' => $u['firstName'] ?? '',
                'lastName' => $u['lastName'] ?? '',
                'phone' => $u['phone'] ?? '',
                'email' => $u['email'] ?? '',
                'password' => $u['password_plain'] ?? '',
                'addresses' => $u['addresses'] ?? [],
                'created_at' => $u['created_at'] ?? '',
            ];
        }
        echo json_encode(['success' => true, 'customers' => array_reverse($customers)], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- پیشخوان مدیریت: تغییر وضعیت سفارش (فقط ادمین) ---------- */
    case 'update_order_status': {
        ap_require_admin('ap_manage_orders');

        $orderId = (int) ($input['orderId'] ?? 0);
        $status  = (string) ($input['status'] ?? '');
        $validStatuses = ['pending', 'confirmed', 'rejected', 'packing', 'courier', 'shipped'];
        if (!in_array($status, $validStatuses, true)) json_fail('وضعیت نامعتبر است.');

        $found = false;
        foreach ($db['orders'] as &$o) {
            if ((int) $o['id'] === $orderId) { $o['status'] = $status; $found = true; break; }
        }
        unset($o);
        if (!$found) json_fail('سفارش پیدا نشد.', 404);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- حذفِ کاملِ یک سفارش (فقط مدیر) ---------- */
    case 'delete_order': {
        ap_require_admin('ap_manage_orders');
        $orderId = (int) ($input['orderId'] ?? 0);
        $before = count($db['orders']);
        $removedReceipt = null;
        $db['orders'] = array_values(array_filter($db['orders'], function ($o) use ($orderId, &$removedReceipt) {
            if ((int) $o['id'] === $orderId) { $removedReceipt = $o['receiptUrl'] ?? null; return false; }
            return true;
        }));
        if (count($db['orders']) === $before) json_fail('سفارش پیدا نشد.', 404);
        // فایلِ رسیدِ همون سفارش هم پاک می‌شه تا روی هاست تلنبار نشه
        if ($removedReceipt) { @unlink($RECEIPTS_DIR . '/' . basename($removedReceipt)); }
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'config':
        echo json_encode(['success' => true, 'googleClientId' => GOOGLE_CLIENT_ID], JSON_UNESCAPED_UNICODE);
        break;

    case 'backup_now':
        ap_require_admin();
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'message' => 'بکاپ گرفته شد.'], JSON_UNESCAPED_UNICODE);
        break;

    default:
        json_fail('اکشن نامعتبر است.', 404);
}
