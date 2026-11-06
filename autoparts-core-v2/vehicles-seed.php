<?php
if (!defined('ABSPATH')) { exit; }

/**
 * فهرست اولیه‌ی برند ← مدل ← سال (خودروهای چینیِ رایج در ایران).
 * یک‌بار، اولین بار که مدیر وارد پیشخوان بشه، هر چیزی که هنوز توی «برند / مدل / سال»
 * نیست اضافه می‌شه؛ موارد موجود و چیزهایی که دستی اضافه یا حذف کردی دست نمی‌خورن.
 * برای اضافه‌کردن برند جدید: به فهرست اضافه کن و AP_VEHICLE_SEED_VERSION رو یکی بالا ببر.
 */

define('AP_VEHICLE_SEED_VERSION', '4');

function ap_vehicle_seed_data() {
    return [
        ['CHERY (چری)', 'chery', [
            ['تیگو ۷ پرو', 'tiggo-7-pro'], ['تیگو ۸ پرو', 'tiggo-8-pro'], ['تیگو ۵', 'tiggo-5'],
            ['آریزو ۵', 'arrizo-5'], ['آریزو ۶', 'arrizo-6'], ['آریزو ۶ پرو', 'arrizo-6-pro'],
            ['ام‌وی‌ام X22', 'mvm-x22'], ['ام‌وی‌ام X33', 'mvm-x33'], ['ام‌وی‌ام X55', 'mvm-x55'], ['ام‌وی‌ام ۳۱۵', 'mvm-315'],
        ]],
        ['FOWNIX (فونیکس)', 'fownix', [
            ['Arrizo 6 GT (آریزو ۶ جی‌تی)', 'arrizo-6-gt'],
            ['FX (اف‌ایکس)', 'fx'],
            ['Tiggo 7 Pro (تیگو ۷ پرو)', 'tiggo-7-pro'],
            ['Tiggo 7 Pro Max (تیگو ۷ پرو مکس)', 'tiggo-7-pro-max'],
            ['Tiggo 8 Pro (تیگو ۸ پرو)', 'tiggo-8-pro'],
            ['Tiggo 8 Pro Max (تیگو ۸ پرو مکس)', 'tiggo-8-pro-max'],
        ]],
        ['JAC (جک)', 'jac', [
            ['J4 (جی ۴)', 'j4'],
            ['J5 (جی ۵)', 'j5'],
            ['J7 (جی ۷)', 'j7'],
            ['S3 (اس ۳)', 's3'],
            ['S5 (اس ۵)', 's5'],
            ['S7 (اس ۷)', 's7'],
            ['KMC K7 (کی‌ام‌سی کی ۷)', 'kmc-k7'],
            ['KMC T8 (کی‌ام‌سی تی ۸)', 'kmc-t8'],
            ['KMC T9 (کی‌ام‌سی تی ۹)', 'kmc-t9'],
            ['KMC X5 (کی‌ام‌سی ایکس ۵)', 'kmc-x5'],
        ]],
        ['CHANGAN (چانگان)', 'changan', [
            ['CS35 (سی‌اس ۳۵)', 'cs35'],
            ['CS35 Plus (سی‌اس ۳۵ پلاس)', 'cs35-plus'],
            ['CS55 (سی‌اس ۵۵)', 'cs55'],
            ['CS55 Plus (سی‌اس ۵۵ پلاس)', 'cs55-plus'],
            ['Eado (ایدو)', 'eado'],
            ['UNI-K (یونی کی)', 'uni-k'],
            ['UNI-T (یونی تی)', 'uni-t'],
            ['UNI-V (یونی وی)', 'uni-v'],
        ]],
        ['BRILLIANCE (برلیانس)', 'brilliance', [
            ['H220', 'h220'], ['H230', 'h230'], ['H320', 'h320'], ['H330', 'h330'], ['V5', 'v5'],
        ]],
        ['LIFAN (لیفان)', 'lifan', [
            ['X60', 'x60'], ['X70', 'x70'], ['۸۲۰', '820'],
        ]],
        ['HAIMA (هایما)', 'haima', [
            ['S7', 's7'], ['8S', '8s'], ['S5', 's5'],
        ]],
        ['BYD (بی‌وای‌دی)', 'byd', [
            ['سانگ پلاس', 'song-plus'], ['اتو ۳', 'atto-3'], ['سیل', 'seal'], ['هان', 'han'], ['دلفین', 'dolphin'],
        ]],
        ['FIDELITY (فیدلیتی)', 'fidelity', [
            ['پرایم', 'prime'], ['الیت', 'elite'],
        ]],
        ['RESPECT (رسپکت)', 'respect', [
            ['رسپکت ۲', 'respect-2'],
        ]],
        ['DIGNITY (دیگنیتی)', 'dignity', [
            ['پرستیژ', 'prestige'],
        ]],
        ['HAVAL (هاوال)', 'haval', [
            ['H6', 'h6'], ['H9', 'h9'], ['جولیون', 'jolion'], ['دارگو', 'dargo'],
        ]],
        ['LAMARI (لاماری)', 'lamari', [
            ['ایما', 'eama'],
        ]],
    ];
}

function ap_vehicle_seed_term($name, $slug, $parent) {
    $found = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $parent, 'hide_empty' => false, 'name' => $name, 'number' => 1]);
    if (!is_wp_error($found) && $found) return (int) $found[0]->term_id;
    $res = wp_insert_term($name, 'ap_vehicle', ['slug' => $slug, 'parent' => $parent]);
    if (is_wp_error($res)) {
        /* اسلاگ تکراری زیر والد دیگه: با اسلاگِ والد یکتا می‌شه */
        $p = $parent ? get_term($parent, 'ap_vehicle') : null;
        $res = wp_insert_term($name, 'ap_vehicle', ['slug' => ($p ? $p->slug . '-' : '') . $slug, 'parent' => $parent]);
    }
    return is_wp_error($res) ? 0 : (int) $res['term_id'];
}

add_action('admin_init', function () {
    if (get_option('ap_vehicle_seed') === AP_VEHICLE_SEED_VERSION || !current_user_can('manage_options')) return;
    if (!taxonomy_exists('ap_vehicle')) return;
    @set_time_limit(300);
    /* نسخه‌ی ۳: همه‌ی برند/مدل/سال‌های قبلی پاک و فهرست تمیز از نو ساخته می‌شه. */
    if (version_compare((string) get_option('ap_vehicle_seed', '0'), '3', '<')) {
        $all = get_terms(['taxonomy' => 'ap_vehicle', 'hide_empty' => false, 'fields' => 'ids']);
        if (!is_wp_error($all)) foreach ($all as $tid) wp_delete_term($tid, 'ap_vehicle');
    }
    $years = range(1398, 1405);
    /* نسخه‌ی ۴: مدل‌های فونیکس، جک و چانگان از نو و به ترتیب ساخته می‌شن. */
    $rebuild = version_compare((string) get_option('ap_vehicle_seed', '0'), '4', '<') ? ['fownix', 'jac', 'changan'] : [];
    foreach (ap_vehicle_seed_data() as [$brand, $bslug, $models]) {
        $bid = ap_vehicle_seed_term($brand, $bslug, 0);
        if (!$bid) continue;
        if (in_array($bslug, $rebuild, true)) {
            $kids = get_term_children($bid, 'ap_vehicle');
            if (!is_wp_error($kids)) foreach ($kids as $k) wp_delete_term($k, 'ap_vehicle');
        }
        foreach ($models as $i => [$model, $mslug]) {
            $mid = ap_vehicle_seed_term($model, $mslug, $bid);
            if (!$mid) continue;
            update_term_meta($mid, 'ap_order', $i + 1);
            foreach ($years as $y) ap_vehicle_seed_term((string) $y, (string) $y, $mid);
        }
    }
    update_option('ap_vehicle_seed', AP_VEHICLE_SEED_VERSION);
});
