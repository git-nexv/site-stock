<?php
if (!defined('ABSPATH')) { exit; }

/**
 * متغیرهای پیش‌فرضِ محصولات (درخواستِ آبان ۱۴۰۵):
 * - قطعه‌هایی که چپ و راست دارن (چراغ‌ها، آینه، گلگیر، در، طبق، کمک فنر جلو و ...) ← گروهِ «سمت»
 * - همه‌ی قطعه‌ها ← گروهِ «کیفیت» (اورجینال / درجه یک)
 * - چندتا قطعه‌ی خاص ← گروهِ «نوع» (لنت، شمع، آینه)
 * روی محصولاتِ موجود یک‌بار و دسته‌دسته اعمال می‌شه؛ گروهی که خودت از پیشخوان گذاشتی دست نمی‌خوره
 * (اگه گروهی با همون برچسب هست، جایگزین نمی‌شه). قیمتِ هر گزینه رو از متاباکسِ محصول بذار؛
 * گزینه‌ای که قیمت نداره، قیمتِ اصلیِ محصول رو می‌گیره.
 */

define('AP_VARIANTS_SEED_VERSION', '1');

function ap_part_side_options() { return ['راست (سمت شاگرد)', 'چپ (سمت راننده)', 'جفت (چپ و راست)']; }
function ap_part_quality_options() { return ['اورجینال', 'درجه یک (آفترمارکت)']; }

/* اسلاگِ قطعه از seed key (مثلاً chery-tiggo-7-front-fender ← front-fender) */
function ap_part_slug_of($post_id) {
    $key = (string) get_post_meta($post_id, '_ap_seed_key', true);
    if ($key === '' || !function_exists('ap_parts_seed_list')) return '';
    foreach (ap_parts_seed_list() as $p) {
        if (substr($key, -strlen('-' . $p[0])) === '-' . $p[0]) return $p[0];
    }
    return '';
}

/* گروه‌های متغیرِ مناسبِ یک محصول بر اساسِ نوعِ قطعه و عنوان */
function ap_part_variant_groups($title, $partSlug = '') {
    $t = mb_strtolower(str_replace(["\u{200C}", '‌'], ' ', (string) $title));
    $groups = [];

    /* اگه عنوان خودش سمت رو مشخص کرده (… چپ / Right …)، گروهِ سمت لازم نیست */
    $hasSide = preg_match('/(^|[\s\-|(])(چپ|راست|left|right|lh|rh)([\s\-|)]|$)/u', $t);
    $sidedSlugs = ['headlight', 'taillight', 'side-mirror', 'front-fender', 'fog-light', 'front-control-arm', 'front-shock-absorbers'];
    $sided = in_array($partSlug, $sidedSlugs, true);
    if (!$sided && $partSlug === '') {
        $notSided = preg_match('/درب? (موتور|صندوق|باک)|کاپوت|hood|trunk|tailgate|fuel (door|cap)|سقف|ترمز سوم|third brake|سنسور|کیت|dashboard|داشبورد/u', $t);
        $sided = !$notSided && preg_match(
            '/چراغ (جلو|عقب|خطر|مه ?شکن|راهنما|گلگیر|کنار|روز)|مه ?شکن|آینه|گلگیر|(^|\s)درب? (جلو|عقب)|(^|\s)درب?\s*(چپ|راست)|شیشه (در|بغل|آینه)|دستگیره (در|بیرونی|داخلی)|لولا(ی)? در|طبق|کمک ?فنر جلو|پلوس|رکاب|قاب آینه|سینی چراغ|ریل شیشه|موتور شیشه|بالابر شیشه'
            . '|headlight|head lamp|taillight|tail lamp|fog (light|lamp)|side mirror|wing mirror|fender|(^|\s)door|control arm|turn signal|front shock|axle|cv joint|window regulator/u', $t);
    }
    if ($sided && !$hasSide) $groups[] = ['label' => 'سمت', 'options' => ap_part_side_options()];

    if ($partSlug === 'side-mirror' || ($partSlug === '' && preg_match('/آینه بغل|side mirror|wing mirror/u', $t))) {
        $groups[] = ['label' => 'نوع', 'options' => ['معمولی (دستی)', 'برقی', 'برقی تاشو']];
    } elseif (in_array($partSlug, ['front-brake-pads', 'rear-brake-pads'], true) || ($partSlug === '' && preg_match('/لنت|brake pad/u', $t))) {
        $groups[] = ['label' => 'نوع', 'options' => ['سرامیکی', 'نیمه فلزی']];
    } elseif ($partSlug === 'spark-plugs' || ($partSlug === '' && preg_match('/شمع|spark plug/u', $t))) {
        $groups[] = ['label' => 'نوع', 'options' => ['معمولی (نیکل)', 'پلاتینیوم', 'ایریدیوم']];
    }

    $groups[] = ['label' => 'کیفیت', 'options' => ap_part_quality_options()];
    return $groups;
}

/* گروه‌های پیش‌فرض با گروه‌های فعلیِ محصول ادغام می‌شن؛ برچسب‌های موجود (و هم‌معنی‌هاشون) دست نمی‌خورن */
function ap_part_apply_variants($post_id, $title = null, $partSlug = null) {
    $title = $title ?? get_the_title($post_id);
    $partSlug = $partSlug ?? ap_part_slug_of($post_id);
    $cur = json_decode((string) get_post_meta($post_id, '_ap_variant_groups', true), true);
    $cur = is_array($cur) ? array_values(array_filter($cur, function ($g) { return !empty($g['label']) && !empty($g['options']); })) : [];
    $syn = ['سمت' => ['سمت', 'طرف', 'جهت', 'side'], 'کیفیت' => ['کیفیت', 'برند', 'اصالت', 'نوع قطعه', 'quality'], 'نوع' => ['نوع', 'مدل', 'type']];
    $have = function ($label) use ($cur, $syn) {
        foreach ($cur as $g) {
            $l = mb_strtolower(trim($g['label']));
            foreach ($syn[$label] ?? [$label] as $s) if ($l === $s || mb_strpos($l, $s) !== false) return true;
        }
        return false;
    };
    $add = [];
    foreach (ap_part_variant_groups($title, $partSlug) as $g) if (!$have($g['label'])) $add[] = $g;
    if (!$add) return false;
    /* «سمت» اولِ لیست (مهم‌ترین انتخاب)، بقیه بعد از گروه‌های خودت */
    $front = array_values(array_filter($add, function ($g) { return $g['label'] === 'سمت'; }));
    $back  = array_values(array_filter($add, function ($g) { return $g['label'] !== 'سمت'; }));
    update_post_meta($post_id, '_ap_variant_groups', wp_json_encode(array_merge($front, $cur, $back), JSON_UNESCAPED_UNICODE));
    return true;
}

/* یک‌بار روی همه‌ی محصولاتِ موجود؛ هر بار ۱۵۰ محصول (با قفل) تا سایت کند نشه */
add_action('init', function () {
    $state = get_option('ap_variants_seed', ['v' => '', 'last' => 0]);
    if (($state['v'] ?? '') === AP_VARIANTS_SEED_VERSION . '-done') return;
    if (($state['v'] ?? '') !== AP_VARIANTS_SEED_VERSION) $state = ['v' => AP_VARIANTS_SEED_VERSION, 'last' => 0];
    if (get_transient('ap_variants_seed_lock')) return;
    set_transient('ap_variants_seed_lock', 1, 60);
    global $wpdb;
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN ('publish','draft','pending','private','future') AND ID > %d ORDER BY ID ASC LIMIT 150",
        (int) $state['last']));
    $changed = 0;
    foreach ($rows as $r) {
        if (ap_part_apply_variants((int) $r->ID, $r->post_title)) $changed++;
        $state['last'] = (int) $r->ID;
    }
    if (count($rows) < 150) $state['v'] = AP_VARIANTS_SEED_VERSION . '-done';
    update_option('ap_variants_seed', $state, false);
    if ($changed) delete_transient('ap_products_cache_v3');
    delete_transient('ap_variants_seed_lock');
}, 40);
