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

define('AP_VARIANTS_SEED_VERSION', '2');   /* ۲: کالاهای مصرفی (لاستیک، باتری، روغن، ضدیخ، روغن ترمز) */

function ap_part_side_options() { return ['راست (سمت شاگرد)', 'چپ (سمت راننده)', 'جفت (چپ و راست)']; }
function ap_part_quality_options() { return ['اورجینال', 'درجه یک (آفترمارکت)']; }

/* اسلاگِ قطعه از seed key (مثلاً chery-tiggo-7-front-fender ← front-fender) */
function ap_part_slug_of($post_id) {
    $key = (string) get_post_meta($post_id, '_ap_seed_key', true);
    if ($key === '' || !function_exists('ap_parts_all_list')) return '';
    foreach (ap_parts_all_list() as $p) {
        if (substr($key, -strlen('-' . $p[0])) === '-' . $p[0]) return $p[0];
    }
    return '';
}

/* گروه‌های متغیرِ مناسبِ یک محصول بر اساسِ نوعِ قطعه و عنوان */
function ap_part_variant_groups($title, $partSlug = '') {
    $t = mb_strtolower(str_replace(["\u{200C}", '‌'], ' ', (string) $title));
    $groups = [];

    /* کالاهای مصرفی: برندهای معتبرِ بازار + مشخصه‌ی فنی (به‌جای «کیفیتِ» اورجینال/درجه یک) */
    if ($partSlug === '') {
        if (preg_match('/لاستیک|(^|\s)tires?(\s|$)/u', $t)) $partSlug = 'tires';
        elseif (preg_match('/باتری|battery/u', $t)) $partSlug = 'car-battery';
        elseif (preg_match('/روغن موتور|engine oil/u', $t)) $partSlug = 'engine-oil';
        elseif (preg_match('/ضدیخ|ضد یخ|coolant|antifreeze/u', $t)) $partSlug = 'coolant';
        elseif (preg_match('/روغن ترمز|brake fluid/u', $t)) $partSlug = 'brake-fluid';
    }
    $cons = ap_part_consumable_groups();
    if (isset($cons[$partSlug])) return $cons[$partSlug];

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

/* برندها و مشخصه‌های کالاهای مصرفی (بهترین‌های بازارِ ایران) */
function ap_part_consumable_groups() {
    return [
        'tires' => [
            ['label' => 'برند', 'options' => ['میشلن (فرانسه)', 'بریجستون (ژاپن)', 'کنتیننتال (آلمان)', 'پیرلی (ایتالیا)', 'گودیر (آمریکا)', 'یوکوهاما (ژاپن)', 'هانکوک (کره)', 'کومهو (کره)', 'نکسن (کره)', 'بارز (ایران)', 'کویر تایر (ایران)', 'یزد تایر (ایران)']],
            ['label' => 'سایز', 'options' => ['165/65R13', '175/70R13', '175/65R14', '185/65R14', '185/60R15', '185/65R15', '195/60R15', '195/65R15', '205/55R16', '205/60R16', '215/55R16', '215/60R16', '215/65R16', '215/55R17', '215/60R17', '225/55R17', '225/60R17', '225/65R17', '225/55R18', '225/60R18', '235/55R18', '235/60R18', '235/55R19', '245/45R19']],
            ['label' => 'تعداد', 'options' => ['تک حلقه', 'جفت (۲ حلقه)', 'دست کامل (۴ حلقه)']],
        ],
        'car-battery' => [
            ['label' => 'برند', 'options' => ['اطلس بی‌ایکس (کره)', 'دلکور (کره)', 'سولیت (کره)', 'روکت (کره)', 'وارتا (آلمان)', 'بوش (آلمان)', 'صبا باتری (ایران)', 'سپاهان باتری (ایران)']],
            ['label' => 'آمپر', 'options' => ['۴۵ آمپر', '۵۰ آمپر', '۵۵ آمپر', '۶۰ آمپر', '۶۶ آمپر', '۷۰ آمپر', '۷۴ آمپر', '۸۰ آمپر', '۹۰ آمپر', '۱۰۰ آمپر']],
        ],
        'engine-oil' => [
            ['label' => 'برند', 'options' => ['موبیل ۱', 'کاسترول', 'شل هلیکس', 'توتال کوارتز', 'لیکومولی (آلمان)', 'ایرانول', 'بهران اسپیدی']],
            ['label' => 'گرانروی', 'options' => ['0W-20', '5W-30', '5W-40', '10W-40', '20W-50']],
            ['label' => 'حجم', 'options' => ['۴ لیتری', '۵ لیتری']],
        ],
        'coolant' => [
            ['label' => 'برند', 'options' => ['پرستون (آمریکا)', 'لیکومولی (آلمان)', 'توتال', 'بهران', 'ایرانول']],
            ['label' => 'نوع', 'options' => ['آماده‌ی مصرف (۵۰٪)', 'غلیظ (کنسانتره)']],
            ['label' => 'حجم', 'options' => ['۱ لیتری', '۴ لیتری']],
        ],
        'brake-fluid' => [
            ['label' => 'برند', 'options' => ['بوش (آلمان)', 'لیکومولی (آلمان)', 'کاسترول', 'بهران']],
            ['label' => 'استاندارد', 'options' => ['DOT 4', 'DOT 3']],
        ],
    ];
}

/* گروه‌های پیش‌فرض با گروه‌های فعلیِ محصول ادغام می‌شن؛ برچسب‌های موجود (و هم‌معنی‌هاشون) دست نمی‌خورن */
function ap_part_apply_variants($post_id, $title = null, $partSlug = null) {
    $title = $title ?? get_the_title($post_id);
    $partSlug = $partSlug ?? ap_part_slug_of($post_id);
    $cur = json_decode((string) get_post_meta($post_id, '_ap_variant_groups', true), true);
    $cur = is_array($cur) ? array_values(array_filter($cur, function ($g) { return !empty($g['label']) && !empty($g['options']); })) : [];
    $cons = ap_part_consumable_groups();
    if ($partSlug === '' && preg_match('/باتری|لاستیک|روغن موتور|ضدیخ|روغن ترمز/u', (string) $title)) {
        foreach (array_keys($cons) as $cs) { $g = ap_part_variant_groups($title, ''); if ($g === $cons[$cs]) { $partSlug = $cs; break; } }
    }
    if (isset($cons[$partSlug])) {
        $q = ap_part_quality_options();
        $cur = array_values(array_filter($cur, function ($g) use ($q) { return !($g['label'] === 'کیفیت' && array_values($g['options']) === $q); }));
    }
    $syn = ['سمت' => ['سمت', 'طرف', 'جهت', 'side'], 'کیفیت' => ['کیفیت', 'اصالت', 'نوع قطعه', 'quality'], 'برند' => ['برند', 'brand'], 'سایز' => ['سایز', 'size'], 'آمپر' => ['آمپر'], 'گرانروی' => ['گرانروی', 'ویسکوزیته'], 'حجم' => ['حجم'], 'تعداد' => ['تعداد'], 'استاندارد' => ['استاندارد'], 'نوع' => ['نوع', 'مدل', 'type']];
    $have = function ($label) use ($cur, $syn) {
        foreach ($cur as $g) {
            $l = mb_strtolower(trim($g['label']));
            foreach ($syn[$label] ?? [$label] as $s) if ($l === $s || mb_strpos($l, $s) !== false) return true;
        }
        return false;
    };
    $add = [];
    foreach (ap_part_variant_groups($title, $partSlug) as $g) if (!$have($g['label'])) $add[] = $g;
    if (!$add) {
        /* فقط گروهِ «کیفیت»ِ باتری حذف شده بود */
        $orig = json_decode((string) get_post_meta($post_id, '_ap_variant_groups', true), true);
        if (is_array($orig) && count($orig) !== count($cur)) { update_post_meta($post_id, '_ap_variant_groups', wp_json_encode($cur, JSON_UNESCAPED_UNICODE)); return true; }
        return false;
    }
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
