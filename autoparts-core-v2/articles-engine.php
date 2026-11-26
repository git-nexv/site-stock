<?php
if (!defined('ABSPATH')) { exit; }

/**
 * مقاله‌ها (آذر ۱۴۰۵):
 * ۱) ایمپورتِ یک‌باره‌ی مقاله‌های دست‌نویس از seed/articles-*.json (فیلدِ rel = محصولاتِ مرتبط).
 * ۲) «محصولاتِ مرتبط» زیرِ هر مقاله: از متای _ap_rel ({"veh":"mvm","cats":["brake-pads"]})، وگرنه
 *    از روی کلمه‌های عنوان (برند + نوعِ قطعه) حدس زده می‌شه.
 * ۳) تولیدِ خودکارِ روزانه (ساعتِ ۱۶ به وقتِ تهران): برای هر «مدل + قطعه» که محصول داره، مقاله‌های
 *    کوتاهِ «راهنمای خرید و قیمت» و «علائم خرابی و زمان تعویض» با داده‌ی واقعیِ همون محصول
 *    (قیمت، سال‌های سازگار، لینک‌ها). هر روز AP_ART_DAILY تا؛ تکراری ساخته نمی‌شه (_ap_gen_key).
 */

define('AP_ART_DAILY', 30);

/* دانشِ هر نوعِ قطعه: [علائمِ خرابی، زمان/دوره‌ی تعویض، نکته‌ی خرید] */
function ap_art_part_knowledge() {
    return [
        'front-brake-pads' => [['صدای جیرجیر یا سوت هنگام ترمز', 'طولانی‌تر شدنِ مسافتِ توقف', 'لرزش پدال یا فرمان هنگام ترمز', 'روشن شدنِ چراغِ هشدارِ لنت (در خودروهای مجهز)'], 'معمولاً هر ۲۰ تا ۴۰ هزار کیلومتر؛ بسته به سبکِ رانندگی و ترافیکِ شهری', 'لنتِ هر دو چرخِ یک محور رو با هم عوض کنید و ضخامتِ دیسک رو هم بررسی کنید.'],
        'rear-brake-pads'  => [['صدای سایش از چرخ‌های عقب', 'ضعیف شدنِ ترمز دستی', 'کشیدنِ خودرو به یک سمت هنگام ترمز', 'گردِ ترمزِ زیاد روی رینگ‌های عقب'], 'معمولاً هر ۴۰ تا ۶۰ هزار کیلومتر', 'بعد از تعویض، چند ترمزِ نرم برای آب‌بندیِ لنت بگیرید.'],
        'front-bumper'     => [['ترک یا شکستگی بعد از تصادف', 'شل شدن یا افتادنِ گیره‌ها', 'تغییرِ فاصله‌ی درز با گلگیر و چراغ'], 'بعد از برخورد یا وقتی ترمیم دیگه به‌صرفه نیست', 'سپر به‌صورتِ خام عرضه می‌شه؛ هزینه‌ی رنگ رو هم در نظر بگیرید و محلِ سنسورها رو چک کنید.'],
        'rear-bumper'      => [['ترک یا فرورفتگی بعد از پارک', 'خراب شدنِ محلِ سنسورِ پارک', 'لق شدنِ سپر'], 'بعد از برخورد یا آسیبِ جدی', 'اگه خودرو سنسور پارک داره، سپرِ سوراخ‌دار رو انتخاب کنید.'],
        'headlight'        => [['زرد و کدر شدنِ طلق', 'بخار یا آب گرفتنِ داخلِ چراغ', 'شکستگیِ پایه‌ها یا طلق', 'کاهشِ نورِ مفید در شب'], 'وقتی طلق ترک خورده، پایه شکسته یا آب‌بندی از بین رفته', 'سمتِ چراغ (راست یا چپ) و نوعِ لامپ (هالوژن، زنون یا LED) رو قبل از سفارش مشخص کنید.'],
        'taillight'        => [['ترک خوردنِ طلق', 'آب گرفتنِ چراغ', 'خاموش شدنِ بخشی از چراغ (به‌خصوص مدل‌های LED)'], 'بعد از ضربه یا خرابیِ مدارِ LED', 'سمتِ چراغ رو مشخص کنید؛ در مدل‌های LED معمولاً کلِ چراغ تعویض می‌شه.'],
        'side-mirror'      => [['شکستنِ شیشه یا قاب', 'کار نکردنِ موتورِ تنظیم یا تاشو', 'لرزشِ آینه در سرعت'], 'بعد از ضربه یا خرابیِ موتورِ برقی', 'سمت و نوعِ آینه (دستی، برقی یا برقیِ تاشو) رو مشخص کنید.'],
        'front-fender'     => [['فرورفتگی یا پارگی بعد از تصادف', 'زنگ‌زدگیِ لبه‌ها', 'به‌هم‌ریختنِ فاصله‌ی درزها'], 'وقتی صافکاری به‌صرفه نیست', 'گلگیر آسترخورده و آماده‌ی رنگه؛ سمتِ گلگیر رو مشخص کنید.'],
        'hood'             => [['فرورفتگی یا تاب برداشتن', 'بسته نشدنِ درست یا لرزش در سرعت', 'زنگ‌زدگی'], 'بعد از تصادفِ جلو', 'محلِ لولا و قفل رو با قطعه‌ی قبلی مقایسه کنید.'],
        'grille'           => [['شکستگیِ پره‌ها', 'افتادنِ آرم یا گیره‌ها', 'رنگ‌پریدگی'], 'بعد از ضربه یا شکستگی', 'مدلِ جلوپنجره (قبل یا بعد از فیس‌لیفت) رو با عکسِ خودرو تطبیق بدید.'],
        'radiator'         => [['بالا رفتنِ آمپرِ دما', 'نشتیِ آب یا ضدیخ زیرِ خودرو', 'کم شدنِ مداومِ آبِ رادیاتور', 'گرفتگی یا خوردگیِ پره‌ها'], 'هنگامِ نشتی یا گرفتگیِ شدید؛ معمولاً بعد از ۸ تا ۱۰ سال', 'بعد از نصب، سیستم رو با ضدیخِ مناسب پر و هواگیری کنید.'],
        'oil-filter'       => [['کثیف شدنِ سریعِ روغن', 'روشن شدنِ چراغِ فشارِ روغن', 'صدای بیشترِ موتور در استارتِ سرد'], 'در هر بار تعویضِ روغن (معمولاً هر ۵ تا ۱۰ هزار کیلومتر)', 'واشرِ فیلتر رو قبل از بستن با کمی روغن چرب کنید.'],
        'air-filter'       => [['افزایشِ مصرفِ سوخت', 'افت قدرت و شتاب', 'کثیفی و گردوغبارِ زیاد روی فیلتر'], 'هر ۱۰ تا ۱۵ هزار کیلومتر؛ در مناطقِ پرگردوغبار زودتر', 'فیلتر رو با فوت کردن تمیز نکنید؛ تعویضش ارزون‌تر و مطمئن‌تره.'],
        'spark-plugs'      => [['سخت روشن شدن', 'لرزشِ موتور در درجا', 'افزایشِ مصرفِ سوخت', 'ریپ زدن هنگامِ شتاب'], 'شمعِ معمولی ۲۰ تا ۳۰ هزار، پلاتینیوم/ایریدیوم ۶۰ تا ۱۰۰ هزار کیلومتر', 'همه‌ی شمع‌ها رو با هم و با گشتاورِ استاندارد عوض کنید.'],
        'front-brake-discs'=> [['لرزشِ فرمان هنگامِ ترمز', 'شیار یا لبه‌ی عمیق روی دیسک', 'کمتر شدنِ ضخامت از حدِ مجاز'], 'معمولاً هر دو بار تعویضِ لنت یکبار', 'دیسک‌های هر محور رو با هم و همراهِ لنتِ نو عوض کنید.'],
        'front-shock-absorbers' => [['تکان خوردنِ زیاد در دست‌انداز', 'روغن‌ریزی از بدنه‌ی کمک', 'شیرجه رفتنِ جلو هنگامِ ترمز', 'سایشِ نامنظمِ لاستیک'], 'معمولاً هر ۶۰ تا ۸۰ هزار کیلومتر', 'کمک‌فنرهای هر محور رو با هم عوض کنید و بعدش تنظیمِ فرمان انجام بدید.'],
        'rear-shock-absorbers'  => [['نشستنِ عقبِ خودرو زیرِ بار', 'تکان‌های ادامه‌دار بعد از دست‌انداز', 'روغن‌ریزی'], 'معمولاً هر ۶۰ تا ۸۰ هزار کیلومتر', 'کمک‌فنرهای عقب رو جفتی عوض کنید.'],
        'front-control-arm'=> [['صدای تق‌تق در دست‌انداز', 'کشیدنِ فرمان به یک سمت', 'لقیِ فرمان', 'سایشِ لبه‌ی لاستیک'], 'وقتی سیبک یا بوش‌ها لق شدن', 'بعد از تعویضِ طبق حتماً تنظیمِ فرمان (تنظیم باد) انجام بدید.'],
        'fog-light'        => [['شکستنِ طلق', 'آب گرفتنِ چراغ', 'کار نکردنِ لامپ با وجودِ سالم بودن'], 'بعد از ضربه از جلو', 'سمتِ چراغ رو مشخص کنید.'],
        'ac-condenser'     => [['خنک نکردنِ کولر', 'نشتیِ گاز (لکه‌ی روغنی روی کندانسور)', 'سوراخ شدن با سنگ‌ریزه'], 'هنگامِ نشتی یا آسیب', 'بعد از نصب، سیستم باید وکیوم و شارژِ گاز بشه.'],
        'cabin-filter'     => [['بوی نامطبوع از دریچه‌ها', 'ضعیف شدنِ بادِ کولر و بخاری', 'بخار گرفتنِ شیشه‌ها'], 'هر ۱۰ تا ۱۵ هزار کیلومتر یا سالی یک بار', 'جهتِ جریانِ هوا (فلشِ روی فیلتر) رو موقعِ نصب رعایت کنید.'],
        'car-battery'      => [['کند استارت خوردن', 'خاموش شدنِ چراغ‌ها هنگامِ استارت', 'سفیدک زدنِ قطب‌ها', 'تمام شدنِ شارژ بعد از چند روز پارک'], 'معمولاً هر ۲ تا ۴ سال', 'آمپر و جهتِ قطب‌ها باید با باتریِ قبلی یکی باشه.'],
        'wiper-blades'     => [['خط انداختن روی شیشه', 'صدای جیرجیر', 'پاک نکردنِ کاملِ آب'], 'سالی یک بار یا با دیدنِ خط روی شیشه', 'طولِ تیغه‌ی راننده و شاگرد معمولاً فرق داره.'],
        'fuel-filter'      => [['افت قدرت در سربالایی', 'ریپ زدن در سرعتِ بالا', 'سخت روشن شدن'], 'معمولاً هر ۲۰ تا ۴۰ هزار کیلومتر', 'جهتِ جریانِ سوخت روی فیلتر رو رعایت کنید.'],
        'timing-belt-kit'  => [['صدای سوت یا تق‌تق از جلوی موتور', 'ترک‌خوردگیِ تسمه', 'رسیدن به کیلومترِ تعویض'], 'طبقِ دفترچه؛ معمولاً هر ۶۰ تا ۹۰ هزار کیلومتر', 'تسمه، هرزگرد و تسمه‌سفت‌کن رو با هم عوض کنید؛ پاره شدنِ تسمه به موتور آسیبِ جدی می‌زنه.'],
        'water-pump'       => [['نشتیِ آب از جلوی موتور', 'بالا رفتنِ دمای موتور', 'صدای زوزه از واترپمپ'], 'معمولاً همراهِ تعویضِ تسمه تایم', 'واشرِ نو و ضدیخِ مناسب استفاده کنید.'],
        'alternator'       => [['روشن شدنِ چراغِ باتری روی آمپر', 'ضعیف شدنِ نورِ چراغ‌ها', 'خالی شدنِ باتری در حینِ رانندگی'], 'هنگامِ خرابیِ ذغال، دیود یا بلبرینگ', 'قبل از تعویض، ولتاژِ شارژ (حدود ۱۴ ولت) رو اندازه بگیرید.'],
        'starter-motor'    => [['صدای تق بدونِ چرخیدنِ موتور', 'کند چرخیدن با باتریِ سالم', 'صدای سایش هنگامِ استارت'], 'هنگامِ خرابی؛ معمولاً بعد از ۱۵۰ هزار کیلومتر', 'اول باتری و اتصالاتِ برق رو چک کنید.'],
        'ignition-coil'    => [['لرزش و ریپِ موتور', 'روشن شدنِ چراغِ چک', 'افت قدرت و افزایشِ مصرف'], 'هنگامِ خرابی', 'معمولاً با دیاگ مشخص می‌شه کدوم کویل خرابه.'],
        'clutch-kit'       => [['بالا رفتنِ دور بدونِ افزایشِ سرعت', 'سفت شدنِ پدال', 'سخت جا رفتنِ دنده', 'بوی سوختگی'], 'معمولاً هر ۸۰ تا ۱۲۰ هزار کیلومتر', 'دیسک، صفحه و بلبرینگ رو با هم عوض کنید.'],
    ];
}

/* ---------- ۱) مقاله‌های دست‌نویس از seed ---------- */
/* با اولین بازدید (نه فقط باز کردنِ پیشخوان) ایمپورت می‌شن */
add_action('init', function () {
    if (get_transient('ap_article_seed_lock')) return;
    $done = (array) get_option('ap_article_seed_done', []);
    $files = (array) glob(get_template_directory() . '/seed/articles-*.json');
    $todo = array_filter($files, function ($f) use ($done) { return !in_array(basename($f), $done, true); });
    if (!$todo) return;
    set_transient('ap_article_seed_lock', 1, 120);
    foreach ($todo as $file) {
        foreach ((array) json_decode((string) file_get_contents($file), true) as $a) {
            $slug = sanitize_title($a['slug'] ?? '');
            if ($slug === '' || empty($a['title']) || get_page_by_path($slug, OBJECT, 'ap_article')) continue;
            $id = wp_insert_post(['post_type' => 'ap_article', 'post_status' => 'publish', 'post_name' => $slug,
                'post_title' => sanitize_text_field($a['title']), 'post_excerpt' => sanitize_textarea_field($a['excerpt'] ?? ''),
                'post_content' => wp_kses_post($a['content'] ?? '')]);
            if (!$id || is_wp_error($id)) continue;
            if (!empty($a['rel'])) update_post_meta($id, '_ap_rel', wp_json_encode($a['rel'], JSON_UNESCAPED_UNICODE));
            ap_art_seo_meta($id, $a['title'], $a['excerpt'] ?? '', $a['kw'] ?? '');
        }
        $done[] = basename($file);
    }
    update_option('ap_article_seed_done', $done, false);
    delete_transient('ap_article_seed_lock');
    if (function_exists('ap_pub_cache_clear')) ap_pub_cache_clear(['articles']);
}, 30);

function ap_art_seo_meta($id, $title, $excerpt, $kw = '') {
    $fa = trim(explode('|', $title)[0]);
    update_post_meta($id, 'rank_math_title', $fa . ' %sep% %sitename%');
    update_post_meta($id, 'rank_math_description', mb_substr(wp_strip_all_tags($excerpt), 0, 158));
    if ($kw !== '') update_post_meta($id, 'rank_math_focus_keyword', $kw);
}

/* عنوانِ نمایشی (فقط بخشِ فارسیِ «فارسی | English») */
function ap_art_display_title($t) { return trim(explode('|', (string) $t)[0]); }

/* ---------- ۲) محصولاتِ مرتبط ---------- */
function ap_art_guess_rel($title) {
    $t = mb_strtolower(str_replace("\u{200C}", ' ', (string) $title));
    $rel = ['cats' => []];
    $map = ['لنت' => 'brake-pads', 'brake pad' => 'brake-pads', 'دیسک' => 'brake-discs', 'سپر جلو' => 'front-bumper', 'سپر عقب' => 'rear-bumper',
            'چراغ جلو' => 'headlight', 'headlight' => 'headlight', 'چراغ عقب' => 'taillight', 'چراغ خطر' => 'taillight', 'آینه' => 'side-mirror',
            'گلگیر' => 'fender', 'کاپوت' => 'hood', 'درب موتور' => 'hood', 'جلو پنجره' => 'grille', 'رادیاتور' => 'radiator', 'مه شکن' => 'fog-light', 'مه‌شکن' => 'fog-light',
            'کمک فنر' => 'suspension', 'کمک‌فنر' => 'suspension', 'طبق' => 'suspension', 'جلوبندی' => 'suspension',
            'فیلتر' => 'engine-parts', 'تسمه' => 'engine-parts', 'واتر پمپ' => 'engine-parts', 'کلاچ' => 'engine-parts', 'روغن' => 'engine-parts',
            'شمع' => 'electric-parts', 'باتری' => 'electric-parts', 'دینام' => 'electric-parts', 'استارت' => 'electric-parts', 'کویل' => 'electric-parts', 'برف پاک کن' => 'body-parts'];
    foreach ($map as $k => $c) if (mb_strpos($t, $k) !== false && !in_array($c, $rel['cats'], true)) $rel['cats'][] = $c;
    if (function_exists('ap_lp_brands')) foreach (ap_lp_brands() as $b) {
        $names = array_filter([mb_strtolower($b['fa']), mb_strtolower(trim(preg_replace('/\s*\(.*$/u', '', $b['label']))), $b['short']]);
        $names[] = mb_strtolower(str_replace(' ', '', $b['fa']));
        foreach ($names as $n) if ($n !== '' && mb_strlen($n) > 1 && mb_strpos($t, $n) !== false) { $rel['veh'] = $b['short']; break 2; }
    }
    return $rel;
}
function ap_art_rel($aid) {
    $r = json_decode((string) get_post_meta($aid, '_ap_rel', true), true);
    return is_array($r) && $r ? $r : ap_art_guess_rel(get_the_title($aid));
}
function ap_art_veh_term($short) {
    if (is_numeric($short)) return (int) $short;
    foreach ((array) get_terms(['taxonomy' => 'ap_vehicle', 'parent' => 0, 'hide_empty' => false]) as $t) {
        if (!is_wp_error($t) && (ap_lp_short($t) === $short || $t->slug === $short)) return (int) $t->term_id;
    }
    return 0;
}
function ap_art_related_ids($aid, $limit = 8) {
    $key = 'ap_art_rel_' . (int) $aid . '_' . (function_exists('ap_lp_ver') ? ap_lp_ver() : 1);
    $c = get_transient($key);
    if (is_array($c)) return $c;
    $rel = ap_art_rel($aid);
    $tax = ['relation' => 'AND'];
    if (!empty($rel['veh'])) { $v = ap_art_veh_term($rel['veh']); if ($v) $tax[] = ['taxonomy' => 'ap_vehicle', 'terms' => $v, 'include_children' => true]; }
    if (!empty($rel['cats'])) $tax[] = ['taxonomy' => 'ap_cat', 'field' => 'slug', 'terms' => (array) $rel['cats'], 'include_children' => true];
    $args = ['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => $limit, 'fields' => 'ids', 'orderby' => 'rand'];
    if (count($tax) > 1) $args['tax_query'] = $tax;
    if (!empty($rel['ids'])) { $args['post__in'] = array_map('intval', (array) $rel['ids']); $args['orderby'] = 'post__in'; unset($args['tax_query']); }
    $ids = get_posts($args);
    /* اگه ترکیبِ برند + قطعه چیزی نداشت، فقط با نوعِ قطعه */
    if (!$ids && !empty($rel['cats']) && !empty($rel['veh'])) {
        $args['tax_query'] = ['relation' => 'AND', ['taxonomy' => 'ap_cat', 'field' => 'slug', 'terms' => (array) $rel['cats'], 'include_children' => true]];
        $ids = get_posts($args);
    }
    set_transient($key, $ids, 12 * HOUR_IN_SECONDS);
    return $ids;
}
function ap_art_related_html($aid) {
    $ids = ap_art_related_ids($aid);
    if (!$ids) return '';
    $h = '<section class="art-rel"><h2>محصولاتِ مرتبط با این مقاله</h2><div class="art-rel-grid">';
    foreach ($ids as $pid) {
        $img = function_exists('ap_gallery_urls') ? (array) ap_gallery_urls($pid) : [];
        $img = $img ? $img[0] : get_the_post_thumbnail_url($pid, 'medium');
        $pr = function_exists('ap_lp_price') ? ap_lp_price($pid) : (int) get_post_meta($pid, '_ap_price', true);
        $h .= '<a class="art-rel-card" href="' . esc_url(get_permalink($pid)) . '">'
            . ($img ? '<img src="' . esc_url($img) . '" alt="" loading="lazy">' : '<span class="ph">🔧</span>')
            . '<b>' . esc_html(ap_art_display_title(get_the_title($pid))) . '</b>'
            . '<small>' . esc_html($pr > 0 ? ap_fa_digits(number_format($pr, 0, '.', '٬')) . ' تومان' : 'برای قیمت تماس بگیرید') . '</small>'
            . '<i>مشاهده و خرید ←</i></a>';
    }
    return $h . '</div></section>';
}
/* مقاله‌های مرتبط (همون برند یا همون نوعِ قطعه) */
function ap_art_more_html($aid) {
    $rel = ap_art_rel($aid);
    $args = ['post_type' => 'ap_article', 'post_status' => 'publish', 'numberposts' => 6, 'post__not_in' => [(int) $aid], 'orderby' => 'rand', 'fields' => 'ids'];
    $mq = [];
    if (!empty($rel['veh'])) $mq[] = ['key' => '_ap_rel', 'value' => '"veh":"' . $rel['veh'] . '"', 'compare' => 'LIKE'];
    if ($mq) $args['meta_query'] = $mq;
    $ids = get_posts($args);
    if (count($ids) < 3) $ids = get_posts(['post_type' => 'ap_article', 'post_status' => 'publish', 'numberposts' => 6, 'post__not_in' => [(int) $aid], 'fields' => 'ids']);
    if (!$ids) return '';
    $h = '<section class="art-more"><h2>مقاله‌های مرتبط</h2><ul>';
    foreach ($ids as $id) $h .= '<li><a href="' . esc_url(get_permalink($id)) . '">' . esc_html(ap_art_display_title(get_the_title($id))) . '</a></li>';
    return $h . '</ul></section>';
}
function ap_art_css() {
    return '.art-rel,.art-more{max-width:720px;margin:22px auto 0}.art-rel h2,.art-more h2{font-size:17px;margin:0 0 12px}'
        . '.art-rel-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:10px}'
        . '.art-rel-card{display:flex;flex-direction:column;gap:4px;background:#fff;border-radius:16px;padding:10px;text-decoration:none;color:#201f2b;box-shadow:0 8px 22px rgba(0,0,0,.06)}'
        . '.art-rel-card img,.art-rel-card .ph{width:100%;aspect-ratio:1;object-fit:contain;border-radius:12px;background:#f3f2f7;display:grid;place-items:center;font-size:30px}'
        . '.art-rel-card b{font-size:12.5px;line-height:1.7}.art-rel-card small{font-size:12px;color:#B3122E;font-weight:700}.art-rel-card i{font-style:normal;font-size:11.5px;color:#7a7788}'
        . '.art-more ul{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px}.art-more a{display:block;background:#fff;border-radius:12px;padding:10px 14px;text-decoration:none;color:#201f2b;font-size:13.5px}'
        . 'html[data-theme="dark"] .art-rel-card,html[data-theme="dark"] .art-more a{background:#15141c;color:#ececf4}html[data-theme="dark"] .art-rel-card img,html[data-theme="dark"] .art-rel-card .ph{background:#1f1d28}';
}

/* ---------- ۳) تولیدِ خودکارِ روزانه ---------- */
/* فهرستِ «مدل + قطعه»هایی که محصولِ seed دارن: [modelTermId, partSlug, productId] به ترتیبِ برندهای پرمحصول */
function ap_art_combos() {
    $c = get_transient('ap_art_combos_v1');
    if (is_array($c)) return $c;
    global $wpdb;
    $rows = $wpdb->get_results("SELECT p.ID, m.meta_value AS k, tt.term_id AS veh
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_ap_seed_key'
        JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id AND tt.taxonomy = 'ap_vehicle'
        WHERE p.post_type = 'ap_product' AND p.post_status = 'publish' ORDER BY p.ID ASC");
    $parts = array_column(ap_parts_seed_list(), 0);
    $brandRank = [];
    if (function_exists('ap_lp_brands')) foreach (ap_lp_brands() as $i => $b) $brandRank[$b['id']] = $i;
    $out = [];
    foreach ((array) $rows as $r) {
        $ps = '';
        foreach ($parts as $p) if (substr($r->k, -strlen('-' . $p)) === '-' . $p) { $ps = $p; break; }
        if ($ps === '') continue;
        $anc = get_ancestors((int) $r->veh, 'ap_vehicle');
        $brand = $anc ? (int) end($anc) : (int) $r->veh;
        $out[(int) $r->veh . ':' . $ps] = [(int) $r->veh, $ps, (int) $r->ID, $brandRank[$brand] ?? 999];
    }
    $out = array_values($out);
    usort($out, function ($a, $b) { return [$a[3], $a[0], $a[1]] <=> [$b[3], $b[0], $b[1]]; });
    set_transient('ap_art_combos_v1', $out, DAY_IN_SECONDS);
    return $out;
}

/* یک مقاله برای [مدل، قطعه، نوعِ مقاله]؛ false اگه قبلاً ساخته شده یا داده کافی نیست */
function ap_art_generate_one($veh, $ps, $pid, $topic) {
    $genKey = $veh . ':' . $ps . ':' . $topic;
    global $wpdb;
    if ($wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ap_gen_key' AND meta_value = %s LIMIT 1", $genKey))) return false;
    $vt = get_term($veh, 'ap_vehicle');
    if (!$vt || is_wp_error($vt)) return false;
    $anc = get_ancestors($veh, 'ap_vehicle');
    $bt = $anc ? get_term((int) end($anc), 'ap_vehicle') : $vt;
    $list = []; foreach (ap_parts_seed_list() as $p) $list[$p[0]] = $p;
    if (!isset($list[$ps])) return false;
    [, $pname, $cslug, $cname, , $short, $para] = $list[$ps];
    $enParts = ap_parts_seed_en();
    $pen = $enParts[$ps] ?? '';
    [$ben, $bfa] = ap_lp_split($bt->name);
    $bfa = $bfa ?: $ben;
    [$men] = ap_lp_split($vt->name);
    $men = $men ?: ap_lp_fa($vt->name);
    $benNice = ucwords(strtolower($ben));
    $benNice = strtr($benNice, ['Mvm' => 'MVM', 'Jac' => 'JAC', 'Bmw' => 'BMW', 'Byd' => 'BYD', 'Kmc' => 'KMC', 'Mg' => 'MG']);
    $modelEn = (stripos($men, $benNice) === 0 ? '' : $benNice . ' ') . $men;
    $full = $pname . ' ' . $bfa . ' ' . $men;
    $K = ap_art_part_knowledge()[$ps] ?? [[], '', ''];
    $spec = (string) get_post_meta($pid, '_ap_spec', true);
    $years = preg_match('/\(([^)]*)\)\s*$/u', $spec, $m) ? $m[1] : '';
    $price = function_exists('ap_lp_price') ? ap_lp_price($pid) : 0;
    $plink = get_permalink($pid);
    $mlink = get_term_link($vt); $mlink = is_wp_error($mlink) ? home_url('/') : $mlink;
    $combo = trailingslashit($mlink) . 'part/' . $cslug . '/';
    $priceUrl = home_url('/parts-price/' . ap_lp_short($bt) . '/' . ap_lp_short($vt) . '/');
    $v = abs(crc32($genKey)) % 3;   /* کمی تنوع در جمله‌بندی */
    $priceTxt = $price > 0 ? 'قیمتِ روزِ ' . $pname . ' ' . $men . ' از حدود <b>' . ap_fa_digits(number_format($price, 0, '.', '٬')) . ' تومان</b> شروع می‌شه' : 'قیمتِ این قطعه به‌خاطرِ نوسانِ بازار تلفنی اعلام می‌شه';
    $li = function ($a) { return $a ? '<ul><li>' . implode('</li><li>', array_map('esc_html', $a)) . '</li></ul>' : ''; };
    $links = '<p>📌 <a href="' . esc_url($plink) . '">خرید ' . esc_html($full) . '</a> — <a href="' . esc_url($combo) . '">همه‌ی ' . esc_html($cname . ' ' . $men) . '</a> — <a href="' . esc_url($priceUrl) . '">لیست قیمت قطعات ' . esc_html($bfa . ' ' . $men) . '</a></p>';

    if ($topic === 'guide') {
        $title = 'راهنمای خرید و قیمت ' . $full . ' | ' . $modelEn . ' ' . $pen . ' Buying Guide';
        $slugBase = get_post_field('post_name', $pid) . '-buying-guide';
        $intro = [
            "اگه دنبالِ خریدِ <b>{$pname}</b> برای <b>{$bfa} {$men}</b> هستید، این راهنمای کوتاه کمک می‌کنه قطعه‌ی درست رو با قیمتِ مناسب انتخاب کنید.",
            "خریدِ <b>{$full}</b> وقتی ساده می‌شه که بدونید چه نوعی مناسبِ ماشینتونه و به چه نکاتی دقت کنید.",
            "در این مطلب نکاتِ مهمِ خریدِ <b>{$pname}</b> مخصوصِ <b>{$bfa} {$men}</b> رو خلاصه کردیم.",
        ][$v];
        $body = '<p>' . $intro . '</p><p>' . esc_html($para) . '</p>'
            . '<h2>سازگاری با ' . esc_html($bfa . ' ' . $men) . '</h2><p>این قطعه مخصوصِ ' . esc_html($bfa . ' ' . $men) . ($years ? ' مدل‌های ' . esc_html($years) : '') . ' هست. برای اطمینان، کدِ فنیِ (OEM) قطعه‌ی فعلی یا شماره‌ی شاسی رو قبل از خرید برامون بفرستید.</p>'
            . '<h2>اورجینال یا درجه یک؟</h2><p>' . esc_html($pname) . ' در دو کیفیتِ <b>اورجینال</b> و <b>درجه یک (آفترمارکت)</b> عرضه می‌شه؛ اورجینال بیشترین دوام رو داره و درجه یک انتخابِ اقتصادی‌تریه. ' . esc_html($K[2]) . '</p>'
            . '<h2>قیمت ' . esc_html($full) . '</h2><p>' . $priceTxt . '؛ قیمتِ نهایی به کیفیت (و در قطعه‌های دوطرفه، سمتِ چپ یا راست) بستگی داره.</p>' . $links;
        $excerpt = 'راهنمای خرید ' . $full . ': سازگاری، تفاوت اورجینال و درجه یک، نکات نصب و قیمت روز.';
        $kw = 'قیمت ' . $pname . ' ' . $men;
    } else {
        $title = 'علائم خرابی ' . $full . ' و زمان تعویض | ' . $modelEn . ' ' . $pen . ' Symptoms';
        $slugBase = get_post_field('post_name', $pid) . '-symptoms';
        $intro = [
            "<b>{$pname}</b> در <b>{$bfa} {$men}</b> هم مثلِ هر قطعه‌ی دیگه عمرِ مشخصی داره. با شناختنِ نشانه‌های خرابی، قبل از اینکه مشکل بزرگ‌تر بشه اقدام کنید.",
            "چطور بفهمیم <b>{$full}</b> خراب شده؟ در این مطلبِ کوتاه علائمِ رایج و زمانِ مناسبِ تعویض رو می‌خونید.",
            "اگه به خرابیِ <b>{$pname}</b> ماشینِ <b>{$bfa} {$men}</b> شک دارید، این نشانه‌ها رو بررسی کنید.",
        ][$v];
        $body = '<p>' . $intro . '</p>'
            . '<h2>علائم خرابی ' . esc_html($full) . '</h2>' . $li($K[0])
            . '<h2>زمان تعویض</h2><p>' . esc_html($K[1]) . '. اگه یکی از نشانه‌های بالا رو دیدید، تعویض رو عقب نندازید.</p>'
            . '<h2>نکته‌ی تعویض</h2><p>' . esc_html($K[2]) . '</p>'
            . '<p>' . $priceTxt . '. ' . esc_html($short) . '</p>' . $links;
        $excerpt = 'علائم خرابی ' . $full . '، زمان مناسب تعویض و نکات خرید قطعه‌ی سالم.';
        $kw = 'علائم خرابی ' . $pname . ' ' . $men;
    }
    $slug = sanitize_title($slugBase);
    if ($slug === '' || get_page_by_path($slug, OBJECT, 'ap_article')) return false;
    $id = wp_insert_post(['post_type' => 'ap_article', 'post_status' => 'publish', 'post_name' => $slug, 'post_title' => $title,
        'post_excerpt' => $excerpt, 'post_content' => $body]);
    if (!$id || is_wp_error($id)) return false;
    update_post_meta($id, '_ap_gen_key', $genKey);
    update_post_meta($id, '_ap_rel', wp_json_encode(['veh' => (string) $veh, 'cats' => [$cslug], 'ids' => [$pid]], JSON_UNESCAPED_UNICODE));
    ap_art_seo_meta($id, $title, $excerpt, $kw);
    return true;
}
function ap_art_run_batch($n = AP_ART_DAILY) {
    if (get_transient('ap_art_lock')) return 0;
    set_transient('ap_art_lock', 1, 900);
    @set_time_limit(600);
    $made = 0;
    $cursor = (int) get_option('ap_art_cursor', 0);
    $combos = ap_art_combos();
    $total = count($combos) * 2;
    $tries = 0;
    while ($made < $n && $cursor < $total && $tries < $n * 6) {
        $c = $combos[intdiv($cursor, 2)];
        $topic = $cursor % 2 === 0 ? 'guide' : 'symptoms';
        if (ap_art_generate_one($c[0], $c[1], $c[2], $topic)) $made++;
        $cursor++; $tries++;
    }
    update_option('ap_art_cursor', $cursor, false);
    update_option('ap_art_last', ['date' => (new DateTime('now', new DateTimeZone('Asia/Tehran')))->format('Y-m-d'), 'made' => $made, 'cursor' => $cursor, 'total' => $total], false);
    if ($made && function_exists('ap_pub_cache_clear')) ap_pub_cache_clear(['articles']);
    delete_transient('ap_art_lock');
    return $made;
}
/* زمان‌بندی: هر روز ساعتِ ۱۶ به وقتِ تهران (کرانِ وردپرس + چکِ پشتیبان بعد از جواب‌دادنِ بازدیدها) */
function ap_art_next_16() {
    $tz = new DateTimeZone('Asia/Tehran');
    $d = new DateTime('today 16:00', $tz);
    if ($d->getTimestamp() <= time()) $d->modify('+1 day');
    return $d->getTimestamp();
}
add_action('init', function () { if (!wp_next_scheduled('ap_art_daily')) wp_schedule_event(ap_art_next_16(), 'daily', 'ap_art_daily'); });
add_action('ap_art_daily', function () { ap_art_run_batch(); });
add_action('shutdown', function () {
    $tz = new DateTimeZone('Asia/Tehran');
    $now = new DateTime('now', $tz);
    /* شروعِ اولیه: یک‌بار ۶۰ مقاله همون اول (منتظرِ ساعتِ ۱۶ نمی‌مونه) */
    $boot = !get_option('ap_art_boot_v1');
    if (!$boot && (int) $now->format('G') < 16) return;
    $last = (array) get_option('ap_art_last', []);
    if ((!$boot && ($last['date'] ?? '') === $now->format('Y-m-d')) || get_transient('ap_art_lock') || get_transient('ap_art_tried')) return;
    set_transient('ap_art_tried', 1, 600);
    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
    elseif (function_exists('litespeed_finish_request')) @litespeed_finish_request();
    elseif (!is_admin()) return;
    @ignore_user_abort(true);
    if ($boot) { update_option('ap_art_boot_v1', time(), false); ap_art_run_batch(60); }
    else ap_art_run_batch();
}, 998);
