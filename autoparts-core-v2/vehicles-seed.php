<?php
if (!defined('ABSPATH')) { exit; }

/**
 * فهرست اولیه‌ی برند ← مدل ← سال (خودروهای چینیِ رایج در ایران).
 * یک‌بار، اولین بار که مدیر وارد پیشخوان بشه، هر چیزی که هنوز توی «برند / مدل / سال»
 * نیست اضافه می‌شه؛ موارد موجود و چیزهایی که دستی اضافه یا حذف کردی دست نمی‌خورن.
 * برای اضافه‌کردن برند جدید: به فهرست اضافه کن و AP_VEHICLE_SEED_VERSION رو یکی بالا ببر.
 */

define('AP_VEHICLE_SEED_VERSION', '3');

function ap_vehicle_seed_data() {
    return [
        ['CHERY (چری)', 'chery', [
            ['تیگو ۷ پرو', 'tiggo-7-pro'], ['تیگو ۸ پرو', 'tiggo-8-pro'], ['تیگو ۵', 'tiggo-5'],
            ['آریزو ۵', 'arrizo-5'], ['آریزو ۶', 'arrizo-6'], ['آریزو ۶ پرو', 'arrizo-6-pro'],
            ['ام‌وی‌ام X22', 'mvm-x22'], ['ام‌وی‌ام X33', 'mvm-x33'], ['ام‌وی‌ام X55', 'mvm-x55'], ['ام‌وی‌ام ۳۱۵', 'mvm-315'],
        ]],
        ['FOWNIX (فونیکس)', 'fownix', [
            ['تیگو ۷ پرو', 'tiggo-7-pro'], ['تیگو ۸ پرو مکس', 'tiggo-8-pro-max'],
            ['آریزو ۶ GT', 'arrizo-6-gt'], ['FX', 'fx'],
        ]],
        ['JAC (جک)', 'jac', [
            ['S3', 's3'], ['S5', 's5'], ['J4', 'j4'], ['J7', 'j7'],
            ['KMC T8', 'kmc-t8'], ['KMC K7', 'kmc-k7'], ['KMC J7', 'kmc-j7'],
        ]],
        ['CHANGAN (چانگان)', 'changan', [
            ['CS35', 'cs35'], ['CS55', 'cs55'], ['UNI-T', 'uni-t'], ['UNI-K', 'uni-k'],
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
    foreach (ap_vehicle_seed_data() as [$brand, $bslug, $models]) {
        $bid = ap_vehicle_seed_term($brand, $bslug, 0);
        if (!$bid) continue;
        foreach ($models as [$model, $mslug]) {
            $mid = ap_vehicle_seed_term($model, $mslug, $bid);
            if (!$mid) continue;
            foreach ($years as $y) ap_vehicle_seed_term((string) $y, (string) $y, $mid);
        }
    }
    update_option('ap_vehicle_seed', AP_VEHICLE_SEED_VERSION);
});
