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

/* سرویس‌ورکرِ اعلان‌ها: از همین فایل (که همیشه مستقیم به PHP می‌رسه) سرو می‌شه؛ فایلِ
   .js در ریشه‌ی سایت روی بعضی هاست‌ها/CDNها اصلاً به وردپرس نمی‌رسید و ثبت نمی‌شد */
if (($_GET['action'] ?? '') === 'sw' && file_exists(__DIR__ . '/push-lib.php')) {
    require_once __DIR__ . '/push-lib.php';
    ap_pl_serve_sw();
}

header('Content-Type: application/json; charset=utf-8');
/* مسیرِ سریع (آذر ۱۴۰۵): لیستِ عمومیِ محصولات/دسته‌ها/... از فایلِ آماده (و فشرده) خونده می‌شه، بدونِ
   بارگذاریِ وردپرس؛ با ETag، بازدیدِ دوباره فقط «۳۰۴ تغییری نکرده» می‌گیره. با هر تغییرِ محصول/دسته/مقاله
   یا آپدیتِ قالب فایل پاک و دوباره ساخته می‌شه (functions.php ← ap_pub_cache_clear). */
define('AP_PUB_CACHE_DIR', dirname(__DIR__, 2) . '/uploads/ap-data/cache');
function ap_pub_cache_store($action, $json) {
    $dir = AP_PUB_CACHE_DIR;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return;
    $f = $dir . '/' . $action . '.json';
    $tmp = $f . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, $json) !== false) @rename($tmp, $f);
    if (function_exists('gzencode') && ($gz = gzencode($json, 6)) !== false && @file_put_contents($tmp, $gz) !== false) @rename($tmp, $f . '.gz');
}
$ap_pub_ttl = ['products' => 900, 'categories' => 1800, 'vehicles' => 1800, 'articles' => 1800];
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'GET' && count($_GET) === 1 && isset($ap_pub_ttl[$_GET['action'] ?? ''])) {
    $f = AP_PUB_CACHE_DIR . '/' . $_GET['action'] . '.json';
    $mt = @filemtime($f);
    if ($mt && time() - $mt < $ap_pub_ttl[$_GET['action']] && $mt > (int) @filemtime(__FILE__)) {
        $etag = '"' . $mt . '-' . (int) @filesize($f) . '"';
        header('ETag: ' . $etag);
        header('Cache-Control: no-cache');
        header('Vary: Accept-Encoding');
        if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === $etag) { http_response_code(304); exit; }
        while (ob_get_level() > 0) @ob_end_clean();
        if (stripos((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false && is_file($f . '.gz') && !ini_get('zlib.output_compression')) {
            header('Content-Encoding: gzip'); header('Content-Length: ' . filesize($f . '.gz')); readfile($f . '.gz'); exit;
        }
        header('Content-Length: ' . filesize($f)); readfile($f); exit;
    }
}
/* فشرده‌سازیِ پاسخ‌ها (لیست محصولات چند صد کیلوبایته؛ با gzip چند برابر کوچیک‌تر و سریع‌تر روی موبایل) */
if (!ini_get('zlib.output_compression') && extension_loaded('zlib') && stripos((string) ($_SERVER['HTTP_ACCEPT_ENCODING'] ?? ''), 'gzip') !== false
    && in_array($_GET['action'] ?? '', ['products', 'categories', 'articles', 'list_orders', 'list_customers', 'list_my_orders'], true)) {
    ob_start('ob_gzhandler');
}
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

/* هشِ رمز با هزینه‌ی صریحِ ۱۰ (سریع ولی امن)؛ رمزهای قدیمی بعد از اولین ورود بازنویسی می‌شن */
define('AP_PW_COST', 10);
function ap_hash_password($password) {
    return password_hash((string) $password, PASSWORD_BCRYPT, ['cost' => AP_PW_COST]);
}
function ap_password_needs_rehash($hash) {
    return password_needs_rehash((string) $hash, PASSWORD_BCRYPT, ['cost' => AP_PW_COST]);
}

/* اکشن‌های سبک: بدون وردپرس اجرا می‌شن */
$AP_LIGHT_ACTIONS = [
    'register', 'login', 'logout', 'config', 'google_login',
    'update_profile', 'add_address', 'update_address', 'delete_address', 'list_my_orders',
    'push_subscribe', 'push_unsubscribe', 'push_pull',
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
        $red = '#b3122e';   /* پیش‌فرض: زرشکی */
        $raw = strtolower(trim((string) $raw));
        if ($raw === '') { return $red; }
        $legacy = ['#6a4dff', '#8b6bff', '#8f6bff', '#a68bff', '#7c5cff', '#5b3df5', '#8b5cf6', '#7c9dff', '#6a4eff'];
        if (in_array($raw, $legacy, true) || in_array($raw, ['#ff3b4e', '#ff5563', '#2b2d31'], true)) { return $red; }   /* رنگ‌های قبلی هم زرشکی */
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
            /* نسخه‌ی کوچیک‌ترِ همون عکس برای کارت‌ها (۷۶۸px به‌جای ۱۰۲۴px)؛ روی گوشی سریع‌تر لود می‌شه */
            'thumb' => function_exists('ap_card_thumb_url') ? (ap_card_thumb_url($p->ID) ?: ($gallery[0] ?? ($img ?: null))) : null,
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
    $cacheKey = 'ap_vehicles_cache_v2';
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
            'order'  => (int) get_term_meta($t->term_id, 'ap_order', true),
        ];
    }
    /* سال‌ها از تازه به قدیم؛ بقیه به ترتیبِ دلخواه (ap_order) و بعد مرتب‌سازیِ طبیعی (X22 قبل از X33). */
    usort($out, function ($a, $b) {
        $ay = preg_match('/^(13|14|19|20)\d\d$/', $a['name']);
        $by = preg_match('/^(13|14|19|20)\d\d$/', $b['name']);
        if ($ay && $by) { return (int) $b['name'] <=> (int) $a['name']; }
        if ($a['order'] && $b['order'] && $a['order'] !== $b['order']) { return $a['order'] <=> $b['order']; }
        return strnatcasecmp($a['name'], $b['name']);
    });
    set_transient($cacheKey, $out, 30 * MINUTE_IN_SECONDS);
    return $out;
}
if (function_exists('add_action')) {
    add_action('edited_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v2'); });
    add_action('created_ap_vehicle', function () { delete_transient('ap_vehicles_cache_v2'); });
    add_action('delete_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v2'); });
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
if (file_exists(__DIR__ . '/push-lib.php')) require_once __DIR__ . '/push-lib.php';
if (!is_dir($BACKUP_DIR))   { @mkdir($BACKUP_DIR, 0775, true); }
if (!is_dir($RECEIPTS_DIR)) { @mkdir($RECEIPTS_DIR, 0775, true); }

/* =========================================================================
 * آرشیوِ دائمیِ سفارش‌ها (هیچ‌وقت پاک نمی‌شه)
 * هر سفارش، جدا از db.json، در یک فایلِ مستقل (order-<id>.json) و یک دفترِ فقط‌افزودنی
 * (orders-log.jsonl) هم ذخیره می‌شه. اگه db.json به هر دلیلی خراب/خالی بشه، سفارش‌ها
 * در اولین درخواست از همین آرشیو برمی‌گردن. تاریخِ ثبتِ سفارش هیچ‌وقت عوض نمی‌شه.
 * ========================================================================= */
$ARCHIVE_DIR = $DATA_DIR . '/orders-archive';
if (!is_dir($ARCHIVE_DIR)) { @mkdir($ARCHIVE_DIR, 0775, true); }
if (!file_exists($ARCHIVE_DIR . '/.htaccess')) {
    @file_put_contents($ARCHIVE_DIR . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
}
function ap_archive_order($dir, $order, $event = 'save') {
    if (!$dir || !is_dir($dir) || empty($order['id'])) return;
    $file = $dir . '/order-' . (int) $order['id'] . '.json';
    if (file_exists($file)) {
        $prev = json_decode((string) @file_get_contents($file), true);
        /* اگه با همین شماره سفارشِ دیگه‌ای (با تاریخِ ثبتِ متفاوت) آرشیو شده، روش نوشته نمی‌شه */
        if (is_array($prev) && !empty($prev['created_at']) && !empty($order['created_at']) && $prev['created_at'] !== $order['created_at']) {
            $file = $dir . '/order-' . (int) $order['id'] . '-' . preg_replace('/\D/', '', $order['created_at']) . '.json';
        } elseif (is_array($prev) && !empty($prev['created_at'])) {
            $order['created_at'] = $prev['created_at'];   // تاریخِ ثبت همیشه همون اولی می‌مونه
        }
    }
    $json = json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $tmp = $file . '.tmp';
    if (@file_put_contents($tmp, $json, LOCK_EX) !== false) { @rename($tmp, $file); }
    @file_put_contents($dir . '/orders-log.jsonl', json_encode(['event' => $event, 'at' => date('c'), 'order' => $order], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}
function ap_archived_orders($dir) {
    $out = [];
    foreach ((array) glob($dir . '/order-*.json') as $f) {
        $o = json_decode((string) @file_get_contents($f), true);
        if (is_array($o) && !empty($o['id'])) $out[] = $o;
    }
    return $out;
}

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

/* کلیدِ سبد («<id>::c:رنگ::s:حجم::x:برچسب=مقدار») رو می‌خونه و اگه اون ترکیب قیمتِ
   اختصاصی داشته باشه برمی‌گردونه. ترتیبِ مقدارها همونیه که سمتِ مرورگر ساخته می‌شه:
   [رنگ، حجم، مقدارِ گروه‌ها به ترتیب]، و با «|~|» به هم می‌چسبن. */
function ap_variant_price_from_cart_key($cartKey, $variantPrices) {
    if (!is_array($variantPrices) || !$variantPrices) return 0;
    $parts = explode('::', (string) $cartKey);
    array_shift($parts);
    $color = ''; $storage = ''; $extras = [];
    foreach ($parts as $part) {
        if (strpos($part, 'c:') === 0)      { $color   = urldecode(substr($part, 2)); }
        elseif (strpos($part, 's:') === 0)  { $storage = urldecode(substr($part, 2)); }
        elseif (strpos($part, 'x:') === 0) {
            $raw = substr($part, 2);
            $eq  = strpos($raw, '=');
            if ($eq !== false) $extras[] = urldecode(substr($raw, $eq + 1));
        }
    }
    $values = array_merge([$color, $storage], $extras);
    $key = implode('|~|', array_map(function ($v) { return trim((string) $v); }, $values));
    if (isset($variantPrices[$key])) return (int) $variantPrices[$key];
    $n = ap_norm_variant_key($key);
    foreach ($variantPrices as $k => $v) { if (ap_norm_variant_key($k) === $n && (int) $v > 0) return (int) $v; }
    return 0;
}

/* جواب رو همین الان به مرورگر می‌فرسته و اتصال رو می‌بنده؛ اسکریپت بعدش در پس‌زمینه ادامه می‌ده
   (برای کارهای طولانی مثلِ ارسالِ اعلان به همه؛ مدیر لازم نیست صفحه رو باز نگه داره) */
function ap_respond_and_continue($json) {
    @ignore_user_abort(true); @set_time_limit(900);
    while (ob_get_level() > 0) @ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Connection: close');
        header('Content-Encoding: none');
        header('Content-Length: ' . strlen($json));
    }
    echo $json;
    if (function_exists('fastcgi_finish_request')) { fastcgi_finish_request(); return; }
    if (function_exists('litespeed_finish_request')) { litespeed_finish_request(); return; }
    @flush();
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
                'is_owner' => true,
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
    /* یک نسخه‌ی روزانه که هیچ‌وقت پاک نمی‌شه (نسخه‌های بالا فقط ۳۰تای آخر نگه داشته می‌شن) */
    $daily = $BACKUP_DIR . '/daily-' . date('Ymd') . '.json';
    file_put_contents($daily, $json);
    file_put_contents($stamp, (string) time());

    $files = glob($BACKUP_DIR . '/backup-*.json');
    if ($files && count($files) > $MAX_BACKUPS) {
        usort($files, function ($a, $b) { return filemtime($a) <=> filemtime($b); });
        $toDelete = array_slice($files, 0, count($files) - $MAX_BACKUPS);
        foreach ($toDelete as $f) { @unlink($f); }
    }
}

function public_user($u) {
    unset($u['admin_tokens']);
    unset($u['password_hash']);
    unset($u['password_plain']);
    if (!empty($u['is_admin'])) { $u['perms'] = ap_admin_perms($u); $u['is_owner'] = !empty($u['is_owner']); }
    else { unset($u['perms'], $u['is_owner']); }
    return $u;
}

/* ---------------------------------------------------------------------------
   مدیرها: یک «مدیرِ کل» (مالکِ سایت، is_owner) و هر تعداد ادمینِ زیرمجموعه که
   مدیرِ کل برای هرکدوم مشخص می‌کنه به چه بخش‌هایی دسترسی داشته باشه. همه‌ی
   بررسی‌ها سمتِ سرور انجام می‌شه (با توکنِ ۹۰ روزه‌ی هر ادمین).
--------------------------------------------------------------------------- */
function ap_admin_perm_labels() {
    return [
        'orders'       => 'دیدنِ سفارش‌ها، رسیدها و فاکتورها (+ اعلانِ سفارشِ جدید)',
        'order_status' => 'تغییرِ وضعیتِ سفارش‌ها',
        'customers'    => 'دیدنِ لیستِ مشتری‌ها',
        'notify'       => 'ارسالِ اعلان برای مشتری‌ها',
        'products'     => 'مدیریتِ محصولات، دسته‌ها و مقالات (پیشخوانِ وردپرس)',
    ];
}
function ap_admin_perms($u) {
    $all = array_keys(ap_admin_perm_labels());
    if (!empty($u['is_owner'])) return $all;
    if (empty($u['is_admin'])) return [];
    /* ادمین‌های قدیمی (قبل از اضافه‌شدنِ سطحِ دسترسی) همون دسترسیِ کاملِ قبلی رو دارن */
    if (!isset($u['perms']) || !is_array($u['perms'])) return $all;
    return array_values(array_intersect($all, $u['perms']));
}
/* ادمینی که این درخواست رو فرستاده (از روی توکن)، یا null */
function ap_admin_user() {
    global $db;
    $token = (string) ($_SERVER['HTTP_X_AP_ADMIN'] ?? $_GET['t'] ?? '');
    if ($token === '' || !is_array($db) || empty($db['users'])) return null;
    $h = hash('sha256', $token);
    foreach ($db['users'] as $u) {
        if (empty($u['is_admin'])) continue;
        foreach ((array) ($u['admin_tokens'] ?? []) as $t) {
            if (($t['exp'] ?? 0) > time() && hash_equals((string) ($t['h'] ?? ''), $h)) return $u;
        }
    }
    return null;
}
function ap_admin_token_valid() { return ap_admin_user() !== null; }
function ap_owner_user() {
    global $db;
    foreach ((array) ($db['users'] ?? []) as $u) { if (!empty($u['is_owner'])) return $u; }
    return null;
}
function ap_user_index($id) {
    global $db;
    foreach ($db['users'] as $i => $u) { if ((int) $u['id'] === (int) $id) return $i; }
    return null;
}
function ap_phone_taken($phone, $exceptId = 0) {
    global $db;
    foreach ($db['users'] as $u) { if (($u['phone'] ?? '') === $phone && (int) $u['id'] !== (int) $exceptId) return true; }
    return false;
}

/* بررسی دسترسی. $perm: null = هر ادمینی، 'owner' = فقط مدیرِ کل، یا یکی از کلیدهای ap_admin_perm_labels.
   سشنِ واقعیِ مدیرِ وردپرس (manage_options) هم مثلِ مدیرِ کل حساب می‌شه. */
function ap_require_admin($perm = null) {
    $u = ap_admin_user();
    if ($u) {
        if ($perm === null) return $u;
        if ($perm === 'owner') { if (!empty($u['is_owner'])) return $u; json_fail('این بخش فقط برای مدیرِ کلِ سایته.', 403); }
        if (in_array($perm, ap_admin_perms($u), true)) return $u;
        json_fail('شما به این بخش دسترسی ندارید؛ از مدیرِ کلِ سایت بخواهید دسترسی رو براتون فعال کنه.', 403);
    }
    ap_boot_wp();
    if (function_exists('current_user_can') && current_user_can('manage_options')) {
        return ap_owner_user() ?: ['id' => 0, 'is_admin' => true, 'is_owner' => true];
    }
    json_fail('دسترسی غیرمجاز. لطفاً یک بار از حساب خارج و دوباره وارد شوید.', 403);
}

/* ---------------------------------------------------------------------------
   توکنِ مدیر: قبلاً دسترسیِ مدیر فقط به سشنِ وردپرس وابسته بود که بعد از ۱۴ روز
   منقضی می‌شد؛ مدیر هنوز در سایت «وارد» دیده می‌شد ولی سفارش‌ها نمیومدن. حالا
   هنگامِ ورودِ مدیر یک توکنِ تصادفیِ ۹۰ روزه ساخته می‌شه (فقط هش‌اش ذخیره می‌شه)
   و مرورگرِ مدیر اون رو همراهِ درخواست‌های مدیریتی می‌فرسته؛ غیرقابل‌جعله.
--------------------------------------------------------------------------- */
function ap_issue_admin_token(&$db, $userId) {
    $token = bin2hex(random_bytes(24));
    foreach ($db['users'] as &$u) {
        if ((int) $u['id'] !== (int) $userId) continue;
        $list = array_values(array_filter((array) ($u['admin_tokens'] ?? []), function ($t) { return ($t['exp'] ?? 0) > time(); }));
        $list[] = ['h' => hash('sha256', $token), 'exp' => time() + 90 * DAY_SECONDS_AP];
        $u['admin_tokens'] = array_slice($list, -10);
        $u['last_login'] = date('c');
        break;
    }
    unset($u);
    return $token;
}
define('DAY_SECONDS_AP', 86400);
/* رمزِ مدیرِ کل (فقط هشِ bcrypt نگه داشته می‌شه، نه خودِ رمز) */

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

/* بازیابیِ خودکار: هر سفارشی که در آرشیو هست ولی در db.json نیست، برگردونده می‌شه */
$ap_arch_files = (array) glob($ARCHIVE_DIR . '/order-*.json');
if (count($ap_arch_files) > count($db['orders'])) {
    $have = [];
    foreach ($db['orders'] as $o) { $have[(int) $o['id'] . '|' . ($o['created_at'] ?? '')] = true; }
    foreach (ap_archived_orders($ARCHIVE_DIR) as $ao) {
        if (!isset($have[(int) $ao['id'] . '|' . ($ao['created_at'] ?? '')])) { $db['orders'][] = $ao; $db_dirty = true; }
    }
    usort($db['orders'], function ($a, $b) { return strcmp((string) ($a['created_at'] ?? ''), (string) ($b['created_at'] ?? '')) ?: ((int) $a['id'] <=> (int) $b['id']); });
}
$ap_max_order = 0;
foreach ($db['orders'] as $o) { $ap_max_order = max($ap_max_order, (int) ($o['id'] ?? 0)); }
foreach ($ap_arch_files as $f) { if (preg_match('/order-(\d+)/', basename($f), $m)) $ap_max_order = max($ap_max_order, (int) $m[1]); }
if ((int) $db['next_order_id'] <= $ap_max_order) { $db['next_order_id'] = $ap_max_order + 1; $db_dirty = true; }
/* سفارش‌هایی که قبل از اضافه‌شدنِ آرشیو ثبت شدن، یک‌بار آرشیو می‌شن */
if (count((array) glob($ARCHIVE_DIR . '/order-*.json')) < count($db['orders'])) {
    foreach ($db['orders'] as $o) {
        if (!file_exists($ARCHIVE_DIR . '/order-' . (int) $o['id'] . '.json')) ap_archive_order($ARCHIVE_DIR, $o, 'backfill');
    }
}
unset($ap_arch_files, $ap_max_order);

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

/* مدیرِ کل (مالکِ سایت): یک‌بار با شماره‌ی 09109973428 مشخص می‌شه و از اون به بعد با
   علامتِ is_owner شناخته می‌شه؛ پس اگه شماره یا رمزش رو از پنل عوض کنه، دیگه هیچ حسابِ
   پیش‌فرضی با شماره‌ی قبلی ساخته نمی‌شه. اگه مدیرِ کل هنوز رمزِ سایت نداره، اولین ورودش
   با رمزِ حسابِ وردپرسش انجام می‌شه (پایینِ case 'login'). */
$ADMIN_PHONE = '09109973428';
if (!isset($db['migrations']) || !is_array($db['migrations'])) { $db['migrations'] = []; }
$ownerIdx = null;
foreach ($db['users'] as $i => $u) { if (!empty($u['is_owner'])) { $ownerIdx = $i; break; } }
if ($ownerIdx === null) {
    foreach ($db['users'] as $i => $u) { if (($u['phone'] ?? '') === $ADMIN_PHONE && !empty($u['is_admin'])) { $ownerIdx = $i; break; } }
    if ($ownerIdx === null) foreach ($db['users'] as $i => $u) { if (($u['phone'] ?? '') === $ADMIN_PHONE) { $ownerIdx = $i; break; } }
    if ($ownerIdx !== null) { $db['users'][$ownerIdx]['is_owner'] = true; $db_dirty = true; }
}
if ($ownerIdx === null) {
    $db['users'][] = [
        'id' => $db['next_user_id'],
        'firstName' => 'مدیر',
        'lastName' => 'سایت',
        'phone' => $ADMIN_PHONE,
        'email' => null,
        'google_id' => null,
        'password_hash' => null,
        'is_admin' => true,
        'is_owner' => true,
        'addresses' => [],
        'created_at' => date('c'),
    ];
    $db['next_user_id']++;
    $ownerIdx = count($db['users']) - 1;
    $db_dirty = true;
}
if (empty($db['users'][$ownerIdx]['is_admin'])) { $db['users'][$ownerIdx]['is_admin'] = true; $db_dirty = true; }
/* سیستمِ قبلیِ ادمین‌ها (staff.php) روی ادمین‌ها علامتِ is_staff می‌ذاشت؛ دیگه لازم نیست */
foreach ($db['users'] as &$u) {
    if (!empty($u['is_admin']) && isset($u['password_plain'])) { unset($u['password_plain']); $db_dirty = true; }
    if (isset($u['is_staff'])) { unset($u['is_staff']); $db_dirty = true; }
}
unset($u, $ownerIdx);
if ($db_dirty) { save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db, true); }

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$input  = json_decode(file_get_contents('php://input'), true) ?: [];

switch ($action) {

    case 'products': {
        $wpProducts = ap_wp_products();
        if (is_array($wpProducts) && count($wpProducts) > 0) {
            $json = json_encode(['success' => true, 'products' => $wpProducts, 'source' => 'wp'], JSON_UNESCAPED_UNICODE);
            ap_pub_cache_store('products', $json);
            echo $json;
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
        $json = json_encode(['success' => true, 'categories' => $tree ?: []], JSON_UNESCAPED_UNICODE);
        if ($tree) ap_pub_cache_store('categories', $json);
        echo $json;
        break;
    }

    case 'vehicles': {
        $tree = ap_wp_vehicle_tree();
        $json = json_encode(['success' => true, 'vehicles' => $tree ?: []], JSON_UNESCAPED_UNICODE);
        if ($tree) ap_pub_cache_store('vehicles', $json);
        echo $json;
        break;
    }

    case 'articles': {
        $articles = ap_wp_articles();
        $json = json_encode(['success' => true, 'articles' => $articles ?: []], JSON_UNESCAPED_UNICODE);
        if ($articles) ap_pub_cache_store('articles', $json);
        echo $json;
        break;
    }

    case 'add_product': {
        ap_require_admin('products');
        $p = $input;
        if (empty($p['title']) || !isset($p['price'])) json_fail('عنوان و قیمت الزامی است.');
        $id = $p['id'] ?? ('p' . substr(md5(uniqid('', true)), 0, 8));
        $db['products'][] = [
            'id' => $id, 'cat' => $p['cat'] ?? 'acc', 'color' => $p['color'] ?? '#b3122e',
            'ic' => $p['ic'] ?? 'disc', 'title' => $p['title'], 'spec' => $p['spec'] ?? '',
            'price' => (int) $p['price'], 'badge' => $p['badge'] ?? null,
        ];
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        echo json_encode(['success' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
        break;
    }

    case 'update_product': {
        ap_require_admin('products');
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
        ap_require_admin('products');
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
        /* ادمین‌هایی که هنوز رمزِ سایت ندارن (مدیرِ کل یا ادمین‌های منتقل‌شده از سیستمِ قبلیِ
           پیشخوان): رمز با حسابِ وردپرسشون چک می‌شه و همون لحظه به‌عنوان رمزِ سایت ذخیره می‌شه. */
        if ($found && !empty($found['is_admin']) && empty($found['password_hash'])) {
            ap_boot_wp();
            $wpUser = null;
            if (function_exists('get_user_by')) {
                if (!empty($found['wp_user_id'])) $wpUser = get_user_by('id', (int) $found['wp_user_id']);
                if (!$wpUser && !empty($found['is_owner'])) $wpUser = get_user_by('login', get_option('ap_owner_wp_login') ?: $found['phone']);
            }
            if ($wpUser && wp_check_password($password, $wpUser->user_pass, $wpUser->ID)) {
                $found['password_hash'] = ap_hash_password($password);
                foreach ($db['users'] as &$uRef) {
                    if ((int) $uRef['id'] === (int) $found['id']) { $uRef['password_hash'] = $found['password_hash']; break; }
                }
                unset($uRef);
                save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
            }
        }
        if (!$found || empty($found['password_hash']) || !password_verify($password, $found['password_hash'])) {
            json_fail('شماره موبایل/ایمیل یا رمز عبور اشتباه است.', 401);
        }
        /* حساب‌هایی که با هزینه‌ی قبلی (cost=12) ساخته شدن، همون یک‌بار بعد از
           ورودِ موفق با هزینه‌ی جدید بازنویسی می‌شن؛ از ورودِ بعدی به بعد سریعه. */
        if (ap_password_needs_rehash($found['password_hash'])) {
            $newHash = ap_hash_password($password);
            foreach ($db['users'] as &$uRef) {
                if ((int) $uRef['id'] === (int) $found['id']) { $uRef['password_hash'] = $newHash; break; }
            }
            unset($uRef);
            $found['password_hash'] = $newHash;
            save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        }

        // فقط برای مدیر لازمه سشنِ وردپرس ساخته بشه؛ کاربرِ عادی اصلاً وردپرس رو لود نمی‌کنه
        $adminToken = null;
        if (!empty($found['is_admin'])) {
            $adminToken = ap_issue_admin_token($db, $found['id']);
            save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
            ap_boot_wp();
            if (function_exists('ap_bridge_wp_session')) { ap_bridge_wp_session($found['phone'], public_user($found)); }
        }
        echo json_encode(['success' => true, 'user' => public_user($found), 'adminToken' => $adminToken], JSON_UNESCAPED_UNICODE);
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

        /* حسابِ ادمین‌ها فقط با توکنِ خودِ همون ادمین ویرایش می‌شه (کسی نتونه شماره‌ی مدیر رو عوض کنه) */
        $ap_t = ap_user_index($id);
        if ($ap_t !== null && !empty($db['users'][$ap_t]['is_admin'])) {
            $ap_me = ap_admin_user();
            if (!$ap_me || (int) $ap_me['id'] !== $id) json_fail('برای ویرایشِ حسابِ ادمین، یک بار خارج و دوباره وارد شوید.', 403);
        }
        if (ap_phone_taken($phone, $id)) json_fail('این شماره موبایل متعلق به حسابِ دیگری است.');

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
        /* فقط همون محصولاتِ داخلِ سبد خونده می‌شن (قبلاً کلِ کاتالوگِ ۵۰۰+ محصولی ساخته
           می‌شد و ثبت سفارش رو چند ثانیه کند می‌کرد). */
        $priceMap = [];
        $variantPriceMap = [];
        foreach ($items as $it) {
            $bid = (int) explode('::', (string) ($it['id'] ?? ''))[0];
            if (!$bid || isset($priceMap[(string) $bid]) || !function_exists('get_post_type') || get_post_type($bid) !== 'ap_product') continue;
            $priceMap[(string) $bid] = (int) get_post_meta($bid, '_ap_price', true);
            $variantPriceMap[(string) $bid] = function_exists('ap_variant_prices') ? ap_variant_prices($bid) : [];
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
        $order['statusHistory'] = [['status' => 'pending', 'at' => $order['created_at']]];
        $db['orders'][] = $order;
        $db['next_order_id']++;
        ap_archive_order($ARCHIVE_DIR, $order, 'created');   // اول آرشیوِ دائمی
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        if (function_exists('ap_mirror_order_to_wp')) { try { ap_mirror_order_to_wp($order); } catch (\Throwable $e) {} }   // نسخه‌ی سوم در دیتابیسِ وردپرس
        /* اول جوابِ مشتری فرستاده می‌شه؛ اطلاع‌رسانی به مدیر بعدش و بدون معطل‌کردنِ مشتری */
        echo json_encode(['success' => true, 'order' => $order], JSON_UNESCAPED_UNICODE);
        $ap_detached = false;
        if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); $ap_detached = true; }
        elseif (function_exists('litespeed_finish_request')) { @litespeed_finish_request(); $ap_detached = true; }
        if (function_exists('ap_notify_new_order')) { @ap_notify_new_order($order, $ap_detached); }
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
        ap_require_admin('orders');
        $orders = array_reverse($db['orders']);
        echo json_encode(['success' => true, 'orders' => $orders], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- پیشخوان مدیریت: صفحه‌ی قابل‌چاپِ فاکتور برای یک سفارش (فقط ادمین) ----------
       این اکشن به‌جای JSON، یک صفحه‌ی HTML مستقل و آماده‌ی چاپ (قواره‌ی A5) برمی‌گردونه؛
       ادمین از پیشخوان روش کلیک می‌کنه، توی تب جدید باز می‌شه، و از همون‌جا Ctrl+P یا
       دکمه‌ی چاپِ خودِ صفحه رو می‌زنه (خروجی چاپگر یا PDF، برای چسبوندن روی کارتن). */
    case 'invoice': {
        $ap_inv_admin = ap_admin_user();
        if (!($ap_inv_admin && in_array('orders', ap_admin_perms($ap_inv_admin), true)) && !(function_exists('current_user_can') && current_user_can('manage_options'))) { http_response_code(403); header('Content-Type: text/plain; charset=utf-8'); echo 'دسترسی غیرمجاز.'; break; }

        $orderId = (int) ($_GET['order_id'] ?? 0);
        $order = null;
        foreach ($db['orders'] as $o) { if ((int) $o['id'] === $orderId) { $order = $o; break; } }
        if (!$order) { http_response_code(404); header('Content-Type: text/plain; charset=utf-8'); echo 'سفارش پیدا نشد.'; break; }

        /* فاکتورِ A5 سیاه‌وسفید (invoice.php)؛ فرستنده از «درباره ما»، گیرنده از اطلاعاتِ سفارش */
        if (!function_exists('ap_render_invoice') && defined('ABSPATH')) require_once __DIR__ . '/invoice.php';
        ap_render_invoice($order);
        break;
    }

    /* ---------- پیشخوان مدیریت: فهرست مشتریان (فقط ادمین) ----------
       شامل رمز عبورِ خواناست (فقط برای این‌که در صورت فراموشی، مدیر بتونه
       به مشتری بگه رمزش چیه)؛ توجه: این یعنی رمزها اینجا به‌صورت متن ساده
       هم نگه داشته می‌شن، پس این فهرست باید فقط دست ادمین باشه. */
    case 'list_customers': {
        ap_require_admin('customers');
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
        ap_require_admin('order_status');

        $orderId = (int) ($input['orderId'] ?? 0);
        $status  = (string) ($input['status'] ?? '');
        $validStatuses = ['pending', 'confirmed', 'rejected', 'packing', 'courier', 'shipped'];
        if (!in_array($status, $validStatuses, true)) json_fail('وضعیت نامعتبر است.');

        $found = null;
        foreach ($db['orders'] as &$o) {
            if ((int) $o['id'] === $orderId) {
                $o['status'] = $status;
                $o['statusHistory'] = array_merge((array) ($o['statusHistory'] ?? []), [['status' => $status, 'at' => date('c')]]);
                $found = $o;
                break;
            }
        }
        unset($o);
        if (!$found) json_fail('سفارش پیدا نشد.', 404);
        ap_archive_order($ARCHIVE_DIR, $found, 'status:' . $status);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        if (function_exists('ap_mirror_order_to_wp')) { try { ap_mirror_order_to_wp($found); } catch (\Throwable $e) {} }
        echo json_encode(['success' => true, 'order' => $found], JSON_UNESCAPED_UNICODE);
        break;
    }

    /* ---------- حذفِ کاملِ یک سفارش (فقط مدیر) ---------- */
    /* ---------- حذفِ سفارش غیرفعاله: سفارش‌ها هیچ‌وقت پاک نمی‌شن (به درخواستِ مدیر سایت) ---------- */
    case 'delete_order': {
        ap_require_admin('orders');
        json_fail('حذف سفارش غیرفعال است؛ سفارش‌ها برای همیشه نگه داشته می‌شوند. برای کنار گذاشتنِ یک سفارش، وضعیتش را «رد شد» کنید.', 403);
        break;
    }

    /* ---------- اعلان‌ها (Web Push) ---------- */
    /* کلیدِ عمومیِ اعلان (عمومیه و برای ثبتِ هر دستگاه لازمه) */
    case 'push_vapid': {
        ap_boot_wp();
        $v = function_exists('ap_vapid') ? ap_vapid() : null;
        if (!$v) json_fail('ساخت کلید اعلان روی این هاست ممکن نشد (OpenSSL).', 500);
        echo json_encode(['success' => true, 'publicKey' => $v['public']], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* ثبتِ یک دستگاه؛ اگه درخواست با توکنِ معتبرِ مدیر بیاد، دستگاه «مدیر» ثبت می‌شه و اعلانِ سفارشِ جدید هم می‌گیره */
    case 'push_subscribe': {
        $ap_sub_admin = ap_admin_user();
        $isAdmin = $ap_sub_admin && in_array('orders', ap_admin_perms($ap_sub_admin), true);
        if (!empty($input['old']) && is_string($input['old'])) ap_pl_unsubscribe($DATA_DIR, $input['old']);
        $uid = isset($input['userId']) && is_numeric($input['userId']) ? (int) $input['userId'] : null;
        $s = ap_pl_subscribe($DATA_DIR, (array) ($input['subscription'] ?? []), $isAdmin, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), $uid);
        if (!$s) json_fail('ثبتِ این دستگاه ممکن نشد.');
        echo json_encode(['success' => true, 'role' => $s['r']], JSON_UNESCAPED_UNICODE);
        break;
    }
    case 'push_unsubscribe': {
        ap_pl_unsubscribe($DATA_DIR, (string) ($input['endpoint'] ?? ''));
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* سرویس‌ورکر وقتی اعلان بدونِ محتوا رسیده، آخرین پیامِ همون دستگاه رو از اینجا می‌گیره */
    case 'push_pull': {
        $m = ap_pl_pull($DATA_DIR, (string) ($input['endpoint'] ?? ''));
        echo json_encode(['success' => true, 'message' => $m], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* آمارِ دستگاه‌ها برای صفحه‌ی «اعلان‌ها»ی مدیر */
    case 'push_stats': {
        ap_require_admin('notify');
        $st = ap_pl_read($DATA_DIR);
        $c = ap_pl_counts($DATA_DIR);
        echo json_encode(['success' => true, 'counts' => $c, 'last' => $st['last'] ?? null], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* ارسالِ یک اعلان برای همه‌ی دستگاه‌هایی که اجازه‌ی اعلان دادن (فقط مدیر) */
    case 'push_broadcast': {
        ap_require_admin('notify'); ap_boot_wp();
        $title = trim((string) ($input['title'] ?? ''));
        $body  = trim((string) ($input['body'] ?? ''));
        $url   = trim((string) ($input['url'] ?? ''));
        if ($body === '') json_fail('متنِ اعلان رو بنویسید.');
        if ($url !== '' && !preg_match('#^(https?://|/)#i', $url)) $url = '';
        $v = function_exists('ap_vapid') ? ap_vapid() : null;
        if (!$v) json_fail('کلیدِ اعلان روی این هاست ساخته نشد (OpenSSL).', 500);
        $pc = ap_pl_counts($DATA_DIR);
        if (!$pc['total']) json_fail('هنوز هیچ دستگاهی اجازه‌ی اعلان نداده.');
        /* تأییدیه همون لحظه برمی‌گرده؛ ارسال به تک‌تکِ دستگاه‌ها در پس‌زمینه انجام می‌شه و نتیجه‌ش
           در «آخرین ارسال» (push_stats) ثبت می‌شه */
        ap_respond_and_continue(json_encode(['success' => true, 'queued' => true, 'result' => ['sent' => 0, 'total' => (int) $pc['total'], 'removed' => 0]], JSON_UNESCAPED_UNICODE));
        ap_pl_send($DATA_DIR, $v, 'all', ['title' => $title !== '' ? $title : (function_exists('get_bloginfo') ? get_bloginfo('name') : ''), 'body' => $body, 'url' => $url, 'tag' => 'ap-news-' . time()], function_exists('ap_push_subject') ? ap_push_subject() : 'mailto:admin@nexv.ir');
        exit;
    }
    case 'push_test': {
        ap_require_admin('orders'); ap_boot_wp();
        $v = function_exists('ap_vapid') ? ap_vapid() : null;
        if (!$v) json_fail('کلیدِ اعلان روی این هاست ساخته نشد (OpenSSL).', 500);
        $home = function_exists('home_url') ? home_url('/?ap_admin=orders') : '';
        $r = ap_pl_send($DATA_DIR, $v, 'admins', ['title' => '🔔 اعلان آزمایشی ' . (function_exists('get_bloginfo') ? get_bloginfo('name') : ''), 'body' => 'اعلان‌های سفارشِ جدید روی این دستگاه فعاله ✅', 'url' => $home, 'tag' => 'ap-test'], function_exists('ap_push_subject') ? ap_push_subject() : 'mailto:admin@nexv.ir');
        echo json_encode(['success' => $r['sent'] > 0, 'result' => $r, 'error' => $r['sent'] > 0 ? null : ($r['total'] ? 'ارسال به سرورِ اعلان ناموفق بود: ' . implode(' | ', $r['errors']) : 'هنوز هیچ دستگاهی برای مدیر ثبت نشده.')], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* سازگاری با نسخه‌ی قبلیِ سرویس‌ورکر */
    case 'latest_order_brief': {
        ap_require_admin('orders');
        $last = $db['orders'] ? end($db['orders']) : null;
        $brief = $last ? ['id' => $last['id'], 'customerName' => $last['customerName'] ?? '', 'grandTotal' => $last['grandTotal'] ?? 0,
            'items' => implode('، ', array_map(function ($it) { return ($it['title'] ?? '') . ' ×' . (int) ($it['qty'] ?? 1); }, array_slice((array) ($last['items'] ?? []), 0, 3)))] : null;
        echo json_encode(['success' => true, 'test' => false, 'order' => $brief ?: ['id' => 0, 'customerName' => '', 'grandTotal' => 0]], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* manifestِ نصبِ سایت روی صفحه‌ی گوشی (برای اعلان روی آیفون لازمه) */
    case 'manifest': {
        ap_boot_wp();
        header('Content-Type: application/manifest+json; charset=utf-8', true);
        header('Cache-Control: public, max-age=3600');
        echo json_encode(function_exists('ap_manifest_data') ? ap_manifest_data() : ['name' => 'Store', 'short_name' => 'Store', 'start_url' => '/', 'display' => 'standalone'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        break;
    }

    /* =====================================================================
       ادمین‌ها: حسابِ خودِ ادمین (رمز/شماره) + مدیریتِ ادمین‌های زیرمجموعه (فقط مدیرِ کل)
       ===================================================================== */
    case 'admin_me': {
        $me = ap_require_admin();
        if (empty($me['id'])) json_fail('برای این بخش یک بار از سایت خارج و دوباره وارد شوید.', 403);
        echo json_encode(['success' => true, 'user' => public_user($me), 'labels' => ap_admin_perm_labels()], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* تغییرِ رمز و/یا شماره‌ی خودِ ادمین؛ رمزِ فعلی همیشه لازمه */
    case 'admin_update_me': {
        $me = ap_require_admin();
        $i = empty($me['id']) ? null : ap_user_index($me['id']);
        if ($i === null) json_fail('برای این بخش یک بار از سایت خارج و دوباره وارد شوید.', 403);
        $cur = (string) ($input['currentPassword'] ?? '');
        if ($cur === '' || empty($db['users'][$i]['password_hash']) || !password_verify($cur, $db['users'][$i]['password_hash'])) json_fail('رمزِ فعلی درست نیست.');
        $newPhone = preg_replace('/[^0-9]/', '', (string) ($input['newPhone'] ?? ''));
        $newPass = (string) ($input['newPassword'] ?? '');
        if ($newPhone === '' && $newPass === '') json_fail('شماره یا رمزِ جدید رو وارد کنید.');
        if ($newPhone !== '' && $newPhone !== $db['users'][$i]['phone']) {
            if (!preg_match('/^09\d{9}$/', $newPhone)) json_fail('شماره موبایل باید ۱۱ رقم و به‌صورت 09xxxxxxxxx باشد.');
            if (ap_phone_taken($newPhone, $me['id'])) json_fail('این شماره متعلق به حسابِ دیگری است.');
            $db['users'][$i]['phone'] = $newPhone;
        }
        $passChanged = false;
        if ($newPass !== '') {
            if (mb_strlen($newPass, 'UTF-8') < 8) json_fail('رمزِ جدید باید حداقل ۸ کاراکتر باشد.');
            $db['users'][$i]['password_hash'] = ap_hash_password($newPass);
            unset($db['users'][$i]['password_plain']);
            /* بقیه‌ی دستگاه‌هایی که با رمزِ قبلی وارد شده بودن خارج می‌شن؛ همین دستگاه وارد می‌مونه */
            $curH = hash('sha256', (string) ($_SERVER['HTTP_X_AP_ADMIN'] ?? ''));
            $db['users'][$i]['admin_tokens'] = array_values(array_filter((array) ($db['users'][$i]['admin_tokens'] ?? []), function ($t) use ($curH) { return hash_equals((string) ($t['h'] ?? ''), $curH); }));
            $passChanged = true;
        }
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        ap_boot_wp();
        if ($passChanged && function_exists('ap_wp_admin_logout_others')) ap_wp_admin_logout_others(public_user($db['users'][$i]));
        /* حسابِ وردپرسیِ مدیرِ کل: نام کاربری = شماره، رمز = همین رمز */
        if (function_exists('ap_wp_owner_sync')) ap_wp_owner_sync(public_user($db['users'][$i]), $passChanged ? $newPass : '');
        echo json_encode(['success' => true, 'user' => public_user($db['users'][$i])], JSON_UNESCAPED_UNICODE);
        break;
    }
    case 'admin_list': {
        ap_require_admin('owner');
        $out = [];
        foreach ($db['users'] as $u) {
            if (empty($u['is_admin'])) continue;
            $out[] = ['id' => $u['id'], 'firstName' => $u['firstName'] ?? '', 'lastName' => $u['lastName'] ?? '', 'phone' => $u['phone'] ?? '',
                'is_owner' => !empty($u['is_owner']), 'perms' => ap_admin_perms($u), 'last_login' => $u['last_login'] ?? null, 'created_at' => $u['created_at'] ?? null];
        }
        usort($out, function ($a, $b) { return ($b['is_owner'] <=> $a['is_owner']) ?: ((int) $a['id'] <=> (int) $b['id']); });
        echo json_encode(['success' => true, 'admins' => $out, 'labels' => ap_admin_perm_labels()], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* افزودن/ویرایشِ ادمینِ زیرمجموعه. اگه شماره مالِ یک مشتریِ موجود باشه، همون حساب ادمین می‌شه */
    case 'admin_save': {
        ap_require_admin('owner');
        $id = (int) ($input['id'] ?? 0);
        $phone = preg_replace('/[^0-9]/', '', (string) ($input['phone'] ?? ''));
        $firstName = trim((string) ($input['firstName'] ?? ''));
        $lastName = trim((string) ($input['lastName'] ?? ''));
        $pass = (string) ($input['password'] ?? '');
        $perms = array_values(array_intersect(array_keys(ap_admin_perm_labels()), array_map('strval', (array) ($input['perms'] ?? []))));
        if (!$perms) json_fail('حداقل یک دسترسی برای این ادمین انتخاب کنید.');
        if ($pass !== '' && mb_strlen($pass, 'UTF-8') < 8) json_fail('رمزِ ادمین باید حداقل ۸ کاراکتر باشد.');
        if (!preg_match('/^09\d{9}$/', $phone)) json_fail('شماره موبایل باید ۱۱ رقم و به‌صورت 09xxxxxxxxx باشد.');
        if ($id) {
            $i = ap_user_index($id);
            if ($i === null || empty($db['users'][$i]['is_admin'])) json_fail('این ادمین پیدا نشد.', 404);
            if (!empty($db['users'][$i]['is_owner'])) json_fail('دسترسی‌های مدیرِ کل قابلِ تغییر نیست.');
            if (ap_phone_taken($phone, $id)) json_fail('این شماره متعلق به حسابِ دیگری است.');
        } else {
            $i = null;
            foreach ($db['users'] as $k => $u) { if (($u['phone'] ?? '') === $phone) { $i = $k; break; } }
            if ($i !== null) {
                if (!empty($db['users'][$i]['is_owner'])) json_fail('این شماره مالِ مدیرِ کله.');
                if (!empty($db['users'][$i]['is_admin'])) json_fail('این شماره از قبل ادمینه؛ از لیست ویرایشش کنید.');
                if ($pass === '' && empty($db['users'][$i]['password_hash'])) json_fail('برای این حساب یک رمز تعیین کنید.');
            } else {
                if ($pass === '') json_fail('برای ادمینِ جدید یک رمز (حداقل ۸ کاراکتر) تعیین کنید.');
                $db['users'][] = ['id' => $db['next_user_id'], 'firstName' => $firstName ?: 'ادمین', 'lastName' => $lastName, 'phone' => $phone,
                    'email' => null, 'google_id' => null, 'password_hash' => '', 'is_admin' => false, 'addresses' => [], 'created_at' => date('c')];
                $db['next_user_id']++;
                $i = count($db['users']) - 1;
            }
        }
        $u = &$db['users'][$i];
        $u['is_admin'] = true; $u['is_owner'] = false; $u['perms'] = $perms; $u['phone'] = $phone;
        if ($firstName !== '') $u['firstName'] = $firstName;
        if ($lastName !== '' || $id) $u['lastName'] = $lastName;
        unset($u['password_plain']);
        if ($pass !== '') { $u['password_hash'] = ap_hash_password($pass); $u['admin_tokens'] = []; }
        $saved = $u; unset($u);
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        ap_boot_wp();
        if (function_exists('ap_wp_admin_sync')) ap_wp_admin_sync(public_user($saved));
        echo json_encode(['success' => true, 'admin' => public_user($saved)], JSON_UNESCAPED_UNICODE);
        break;
    }
    /* برداشتنِ دسترسیِ ادمین: حساب به یک مشتریِ معمولی برمی‌گرده (سفارش‌ها و اطلاعاتش می‌مونه) */
    case 'admin_remove': {
        ap_require_admin('owner');
        $i = ap_user_index((int) ($input['id'] ?? 0));
        if ($i === null || empty($db['users'][$i]['is_admin'])) json_fail('این ادمین پیدا نشد.', 404);
        if (!empty($db['users'][$i]['is_owner'])) json_fail('مدیرِ کل قابلِ حذف نیست.');
        $db['users'][$i]['is_admin'] = false;
        $db['users'][$i]['admin_tokens'] = [];
        unset($db['users'][$i]['perms'], $db['users'][$i]['is_owner']);
        $removed = $db['users'][$i];
        save_db($DB_FILE, $BACKUP_DIR, $LATEST_JSON, $MAX_BACKUPS, $db);
        ap_boot_wp();
        if (function_exists('ap_wp_admin_sync')) ap_wp_admin_sync(public_user($removed));
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
