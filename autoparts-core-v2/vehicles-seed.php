<?php
if (!defined('ABSPATH')) { exit; }

/**
 * فهرست برند ← مدل ← سال ساخت (خودروهای چینیِ رایج در ایران).
 * هر مدل بازه‌ی سال تولیدش در ایران رو داره (شمسی)؛ فقط همون سال‌ها ساخته می‌شن.
 * اجرا: یک‌بار برای هر نسخه، اولین بار که مدیر وارد پیشخوان بشه. موارد با اسلاگ پیدا و
 * به‌روز می‌شن (اسم/سال)، پس محصولاتی که به برند/مدل وصل شدن جدا نمی‌شن.
 * برای تغییر: فهرست رو ویرایش کن و AP_VEHICLE_SEED_VERSION رو یکی بالا ببر.
 */

define('AP_VEHICLE_SEED_VERSION', '11');

/* [نام نمایشی, اسلاگ, سال شروع, سال پایان] */
function ap_vehicle_seed_data() {
    return [
        ['CHERY (چری)', 'chery', [
            ['Arrizo 5 (آریزو ۵)', 'arrizo-5', 1394, 1405, ['manual-luxury', 'auto-luxury', 'auto-excellent']],
            ['Arrizo 5 Sport FL (آریزو ۵ اسپرت اف‌ال)', 'arrizo-5-sport', 1397, 1399],
            ['Arrizo 5TE (آریزو ۵ تی‌ای)', 'arrizo-5te', 1401, 1405, ['base', 'excellent-turbo']],
            ['Arrizo 5IE New (آریزو ۵ آی‌ای جدید)', 'arrizo-5ie', 1402, 1405, ['base', 'turbo']],
            ['Arrizo 6 (آریزو ۶)', 'arrizo-6', 1399, 1401, ['base', 'excellent']],
            ['Arrizo 6 Pro (آریزو ۶ پرو)', 'arrizo-6-pro', 1400, 1403],
            ['Tiggo 5 (تیگو ۵)', 'tiggo-5', 1394, 1398, ['base', 'excellent', 'luxury', 'luxury-sport']],
            ['Tiggo 5 IE (تیگو ۵ آی‌ای)', 'tiggo-5-ie', 1402, 1405],
            ['Tiggo 5 IL (تیگو ۵ آی‌ال)', 'tiggo-5-il', 1402, 1405],
            ['Tiggo 5 TE (تیگو ۵ تی‌ای)', 'tiggo-5-te', 1402, 1405, ['turbo']],
            ['Tiggo 7 (تیگو ۷)', 'tiggo-7', 1397, 1401, ['base', 'excellent']],
            ['Tiggo 7 IE (تیگو ۷ آی‌ای)', 'tiggo-7-ie', 1402, 1405],
            ['Tiggo 7 Pro (تیگو ۷ پرو)', 'tiggo-7-pro', 1400, 1405],
            ['Tiggo 8 Pro (تیگو ۸ پرو)', 'tiggo-8-pro', 1400, 1405],
            ['A15 Viana (ویانا A15)', 'a15-viana', 1388, 1391],
            ['Cowin (کویین)', 'cowin', 1388, 1391],
        ]],
        ['MVM (ام‌وی‌ام)', 'mvm', [
            ['315 (۳۱۵)', 'mvm-315', 1388, 1398],
            ['X22 (ایکس ۲۲)', 'mvm-x22', 1395, 1404],
            ['X33 (ایکس ۳۳)', 'mvm-x33', 1390, 1405],
            ['X55 (ایکس ۵۵)', 'mvm-x55', 1396, 1405],
        ]],
        ['FOWNIX (فونیکس)', 'fownix', [
            ['Arrizo 6 GT (آریزو ۶ جی‌تی)', 'arrizo-6-gt', 1402, 1405],
            ['FX (اف‌ایکس)', 'fx', 1402, 1405],
            ['Tiggo 7 Pro (تیگو ۷ پرو)', 'tiggo-7-pro', 1401, 1405],
            ['Tiggo 7 Pro Max (تیگو ۷ پرو مکس)', 'tiggo-7-pro-max', 1402, 1405],
            ['Tiggo 8 Pro (تیگو ۸ پرو)', 'tiggo-8-pro', 1401, 1404],
            ['Tiggo 8 Pro Max (تیگو ۸ پرو مکس)', 'tiggo-8-pro-max', 1402, 1405],
        ]],
        ['JAC (جک)', 'jac', [
            ['J4 (جی ۴)', 'j4', 1396, 1402],
            ['J5 (جی ۵)', 'j5', 1393, 1397],
            ['J7 (جی ۷)', 'j7', 1398, 1402],
            ['S3 (اس ۳)', 's3', 1393, 1401],
            ['S5 (اس ۵)', 's5', 1392, 1401],
            ['S7 (اس ۷)', 's7', 1398, 1400],
        ]],
        ['KMC (کی‌ام‌سی)', 'kmc', [
            ['A5 (ای ۵)', 'a5', 1402, 1405],
            ['eJ7 (ای‌جی ۷)', 'ej7', 1402, 1405],
            ['J7 (جی ۷)', 'j7', 1401, 1405],
            ['K7 (کی ۷)', 'k7', 1401, 1405],
            ['SR3 (اس‌آر ۳)', 'sr3', 1401, 1405],
            ['T8 (تی ۸)', 't8', 1399, 1405],
            ['T9 (تی ۹)', 't9', 1402, 1405],
            ['X5 (ایکس ۵)', 'x5', 1402, 1405],
        ]],
        ['CHANGAN (چانگان)', 'changan', [
            ['CS35 (سی‌اس ۳۵)', 'cs35', 1394, 1400, ['imported', 'montage']],
            ['CS35 Plus (سی‌اس ۳۵ پلاس)', 'cs35-plus', 1399, 1402, ['tip-2', 'tip-3']],
            ['CS55 (سی‌اس ۵۵)', 'cs55', 1400, 1402],
            ['CS55 Plus (سی‌اس ۵۵ پلاس)', 'cs55-plus', 1402, 1405],
            ['X5 Plus (ایکس ۵ پلاس)', 'x5-plus', 1403, 1405],
            ['Eado (ایدو)', 'eado', 1395, 1397],
            ['Eado EV460 (ایدو EV460)', 'eado-ev460', 1398, 1400],
            ['NEVO A05 (نوو A05)', 'nevo-a05', 1404, 1405],
            ['Hunter Plus Pickup (پیکاپ هانتر پلاس)', 'hunter-plus', 1403, 1405],
            ['UNI-K (یونی کی)', 'uni-k', 1402, 1405],
            ['UNI-T (یونی تی)', 'uni-t', 1402, 1405, ['turbo-15']],
            ['UNI-V (یونی وی)', 'uni-v', 1403, 1405],
        ]],
        ['CHANGHE (چانگهه)', 'changhe', [
            ['Karo Pickup (کارو)', 'karo', 1398, 1403, ['single-cab', 'double-cab']],
        ]],
        ['SSANGYONG (سانگ یانگ)', 'ssangyong', [
            ['Chairman (چیرمن)', 'chairman', 1387, 1391],
            ['Rexton Khan (خان)', 'khan', 1399, 1403],
            ['Musso Grand Khan (موسو گرند خان)', 'musso-grand-khan', 1400, 1404, ['signature', 'sahara']],
        ]],
        ['BRILLIANCE (برلیانس)', 'brilliance', [
            ['H220 (اچ ۲۲۰)', 'h220', 1395, 1399, ['manual', 'automatic']],
            ['H230 (اچ ۲۳۰)', 'h230', 1395, 1399, ['manual', 'automatic']],
            ['H320 (اچ ۳۲۰)', 'h320', 1394, 1399, ['manual' => ['1500cc', '1650cc'], 'automatic' => ['1500cc', '1650cc']]],
            ['H330 (اچ ۳۳۰)', 'h330', 1394, 1399, ['manual' => ['1500cc', '1650cc'], 'automatic' => ['1500cc', '1650cc']]],
            ['Cross (کراس)', 'cross', 1396, 1399, ['automatic' => ['1500cc', '1650cc']]],
            ['V5 (وی ۵)', 'v5', 1395, 1397],
            ['V6 (وی ۶)', 'v6', 1396, 1397],
            ['Van H2L (ون اچ۲ال)', 'van-h2l', 1396, 1399],
        ]],
        ['LIFAN (لیفان)', 'lifan', [
            ['820 (۸۲۰)', '820', 1396, 1398],
            ['X60 (ایکس ۶۰)', 'x60', 1392, 1398],
            ['X70 (ایکس ۷۰)', 'x70', 1397, 1398],
        ]],
        ['HAIMA (هایما)', 'haima', [
            ['8S (۸ اس)', '8s', 1400, 1403],
            ['S5 (اس ۵)', 's5', 1395, 1399],
            ['S7 (اس ۷)', 's7', 1394, 1399],
        ]],
        ['BYD (بی‌وای‌دی)', 'byd', [
            ['Atto 3 (اتو ۳)', 'atto-3', 1403, 1405],
            ['Dolphin (دلفین)', 'dolphin', 1403, 1405, ['fashion-ev']],
            ['Han (هان)', 'han', 1403, 1405, ['phev-15']],
            ['Seal (سیل)', 'seal', 1403, 1405],
            ['Seal 05 (سیل ۰۵)', 'seal-05', 1404, 1405],
            ['Seal 06 (سیل ۰۶)', 'seal-06', 1404, 1405, ['phev-15']],
            ['Sealion 06 (سی لاین ۰۶)', 'sealion-06', 1404, 1405, ['plus-170']],
            ['Destroyer 05 (دیستروير ۰۵)', 'destroyer-05', 1403, 1405, ['phev-15']],
            ['Qin Plus (چین پلاس)', 'qin-plus', 1403, 1405, ['phev-15-55km']],
            ['Qin L (چین ال)', 'qin-l', 1404, 1405, ['phev-15']],
            ['Song Plus (سانگ پلاس)', 'song-plus', 1403, 1405, ['turbo-15']],
            ['Seagull (سیگال)', 'seagull', 1404, 1405, ['fly']],
            ['F3 (اف ۳)', 'f3', 1389, 1392],
            ['S6 (اس ۶)', 's6', 1392, 1395],
            ['S7 (اس ۷)', 's7', 1394, 1397],
        ]],
        ['FIDELITY (فیدلیتی)', 'fidelity', [
            ['Prestige (پرستیژ)', 'fidelity-prestige', 1402, 1405, ['seats-5', 'seats-7']],
            ['Prime (پرایم)', 'prime', 1401, 1405],
            ['Elite (الیت)', 'elite', 1402, 1405],
            ['XB1 (ایکس بی ۱)', 'xb1', 1404, 1405],
        ]],
        ['RESPECT (رسپکت)', 'respect', [
            ['Respect 2 (رسپکت ۲)', 'respect-2', 1402, 1405],
        ]],
        ['DIGNITY (دیگنیتی)', 'dignity', [
            ['Prime (پرایم)', 'dignity-prime', 1402, 1405],
            ['Prestige (پرستیژ)', 'prestige', 1401, 1405],
        ]],
        ['HAVAL (هاوال)', 'haval', [
            ['Dargo (دارگو)', 'dargo', 1403, 1405],
            ['H6 (اچ ۶)', 'h6', 1397, 1405],
            ['H9 (اچ ۹)', 'h9', 1397, 1399],
            ['Jolion (جولیون)', 'jolion', 1402, 1405],
        ]],
        ['LAMARI (لاماری)', 'lamari', [
            ['Eama (ایما)', 'eama', 1401, 1405],
        ]],
        ['INFINITI (اینفینیتی)', 'infiniti', [
            ['FX35 (اف‌ایکس ۳۵)', 'fx35', 1386, 1391],
            ['G35 Coupe (جی ۳۵ کوپه)', 'g35-coupe', 1385, 1388],
            ['G37 Coupe (جی ۳۷ کوپه)', 'g37-coupe', 1388, 1392],
            ['Q50 (کیو ۵۰)', 'q50', 1393, 1397],
            ['QX80 (کیو‌ایکس ۸۰)', 'qx80', 1393, 1397],
        ]],
        ['KG MOBILITY (کی جی موبیلیتی)', 'kg-mobility', [
            ['Actyon (اکتیون)', 'actyon', 1404, 1405],
            ['Rexton (رکستون)', 'rexton', 1403, 1405],
            ['Tivoli (تیوولی)', 'tivoli', 1403, 1405],
            ['Torres (تورس)', 'torres', 1403, 1405],
        ]],
        ['HONDA (هوندا)', 'honda', [
            ['City (سیتی)', 'city', 1401, 1404],
        ]],
    ];
}

/* پیدا کردن با اسلاگ (یا اسلاگِ پیشوندخورده با والد) زیر همون والد؛ نبود، ساخته می‌شه؛ بود، اسمش به‌روز می‌شه. */
function ap_vehicle_seed_term($name, $slug, $parent) {
    $p = $parent ? get_term($parent, 'ap_vehicle') : null;
    $alt = ($p && !is_wp_error($p)) ? $p->slug . '-' . $slug : '';
    $kids = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $parent, 'hide_empty' => false]);
    if (!is_wp_error($kids)) {
        foreach ($kids as $k) {
            if ($k->slug === $slug || ($alt && $k->slug === $alt) || $k->name === $name) {
                if ($k->name !== $name) wp_update_term($k->term_id, 'ap_vehicle', ['name' => $name]);
                return (int) $k->term_id;
            }
        }
    }
    $res = wp_insert_term($name, 'ap_vehicle', ['slug' => $slug, 'parent' => $parent]);
    if (is_wp_error($res) && $alt) $res = wp_insert_term($name, 'ap_vehicle', ['slug' => $alt, 'parent' => $parent]);
    if (is_wp_error($res)) $res = wp_insert_term($name, 'ap_vehicle', ['slug' => ($alt ?: $slug) . '-' . $parent, 'parent' => $parent]);
    return is_wp_error($res) ? 0 : (int) $res['term_id'];
}

/* نام نمایشیِ گزینه‌ها: گیربکس و حجم موتور */
function ap_vehicle_variant_name($slug) {
    $map = ['manual' => 'دنده‌ای', 'automatic' => 'اتوماتیک',
        'phev-15' => '1.5 لیتر پلاگین هیبرید', 'phev-15-55km' => 'پلاگین هیبرید 1.5 لیتر 55 کیلومتر',
        'turbo-15' => '1.5 لیتر توربو', 'fashion-ev' => 'فشن تمام برقی', 'plus-170' => '170 پلاس', 'fly' => 'فلای',
        'imported' => 'وارداتی', 'montage' => 'مونتاژ', 'tip-2' => 'تیپ 2', 'tip-3' => 'تیپ 3',
        'single-cab' => 'تک کابین', 'double-cab' => 'دو کابین', 'signature' => 'سیگنچر', 'sahara' => 'ساهارا',
        'base' => 'استاندارد', 'excellent' => 'اکسلنت', 'luxury' => 'لاکچری', 'luxury-sport' => 'لاکچری اسپرت', 'turbo' => 'توربو',
        'excellent-turbo' => 'اکسلنت توربو', 'manual-luxury' => 'دنده‌ای لاکچری', 'auto-luxury' => 'اتوماتیک لاکچری', 'auto-excellent' => 'اتوماتیک اکسلنت', 'seats-5' => '۵ نفره', 'seats-7' => '۷ نفره'];
    return $map[$slug] ?? $slug;
}

/* زیرِ یک گره: یا مستقیم سال‌ها (spec خالی)، یا گزینه‌ها (گیربکس ← حجم موتور) و سال‌ها زیر آخرین سطح.
   هر چیزی که با این ساختار نمی‌خونه (سال‌های خارج از بازه یا سال‌های قبلی زیر مدلی که حالا گزینه داره) حذف می‌شه. */
function ap_vehicle_seed_leaves($parent, $spec, $from, $to) {
    $keep = [];
    if (empty($spec)) {
        for ($y = $from; $y <= $to; $y++) $keep[] = ap_vehicle_seed_term((string) $y, (string) $y, $parent);
    } else {
        $i = 0;
        foreach ($spec as $k => $v) {
            $slug = is_int($k) ? $v : $k;
            $sub  = is_int($k) ? null : $v;
            $id = ap_vehicle_seed_term(ap_vehicle_variant_name($slug), $slug, $parent);
            if (!$id) continue;
            update_term_meta($id, 'ap_order', ++$i);
            $keep[] = $id;
            ap_vehicle_seed_leaves($id, $sub, $from, $to);
        }
    }
    $now = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $parent, 'hide_empty' => false, 'fields' => 'ids']);
    if (!is_wp_error($now)) {
        foreach (array_diff(array_map('intval', $now), array_map('intval', $keep)) as $old) {
            $subs = get_term_children($old, 'ap_vehicle');
            if (!is_wp_error($subs)) foreach ($subs as $x) wp_delete_term($x, 'ap_vehicle');
            wp_delete_term($old, 'ap_vehicle');
        }
    }
}

add_action('admin_init', function () {
    if (get_option('ap_vehicle_seed') === AP_VEHICLE_SEED_VERSION || !current_user_can('manage_options')) return;
    if (!taxonomy_exists('ap_vehicle')) return;
    @set_time_limit(300);

    /* مدل‌هایی که جای برندشون عوض شده (مثلاً MVM که قبلاً زیرِ چری بود و برندِ خودشه) جابه‌جا می‌شن،
       نه حذف و ساختِ دوباره؛ این‌طوری محصولاتی که بهشون وصلن جدا نمی‌شن. */
    foreach (ap_vehicle_seed_data() as [$brand, $bslug, $models]) {
        $bid = ap_vehicle_seed_term($brand, $bslug, 0);
        if (!$bid) continue;
        foreach ($models as $row) {
            $t = get_term_by('slug', $row[1], 'ap_vehicle');
            if (!$t) continue;
            $parentTerm = $t->parent ? get_term($t->parent, 'ap_vehicle') : null;
            if ($parentTerm && !is_wp_error($parentTerm) && (int) $parentTerm->parent === 0 && (int) $t->parent !== $bid) {
                wp_update_term($t->term_id, 'ap_vehicle', ['parent' => $bid, 'name' => $row[0]]);
            }
        }
    }
    clean_term_cache([], 'ap_vehicle');

    foreach (ap_vehicle_seed_data() as [$brand, $bslug, $models]) {
        $bid = ap_vehicle_seed_term($brand, $bslug, 0);
        if (!$bid) continue;
        $keep = [];
        foreach ($models as $i => $model_row) {
            [$model, $mslug, $from, $to] = $model_row;
            $mid = ap_vehicle_seed_term($model, $mslug, $bid);
            if (!$mid) continue;
            $keep[] = $mid;
            update_term_meta($mid, 'ap_order', $i + 1);
            ap_vehicle_seed_leaves($mid, $model_row[4] ?? null, $from, $to);
        }
        /* مدل‌هایی که دیگه توی فهرست نیستن (مثل KMC قبلی زیر JAC) حذف می‌شن */
        $models_now = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $bid, 'hide_empty' => false, 'fields' => 'ids']);
        if (!is_wp_error($models_now)) {
            foreach (array_diff(array_map('intval', $models_now), $keep) as $old) {
                $sub = get_term_children($old, 'ap_vehicle');
                if (!is_wp_error($sub)) foreach ($sub as $s) wp_delete_term($s, 'ap_vehicle');
                wp_delete_term($old, 'ap_vehicle');
            }
        }
    }
    delete_transient('ap_vehicles_cache_v2');
    update_option('ap_vehicle_seed', AP_VEHICLE_SEED_VERSION);
});
