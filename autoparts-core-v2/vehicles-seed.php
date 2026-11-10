<?php
if (!defined('ABSPATH')) { exit; }

/**
 * فهرست برند ← مدل ← سال ساخت (خودروهای چینیِ رایج در ایران).
 * هر مدل بازه‌ی سال تولیدش در ایران رو داره (شمسی)؛ فقط همون سال‌ها ساخته می‌شن.
 * اجرا: یک‌بار برای هر نسخه، اولین بار که مدیر وارد پیشخوان بشه. موارد با اسلاگ پیدا و
 * به‌روز می‌شن (اسم/سال)، پس محصولاتی که به برند/مدل وصل شدن جدا نمی‌شن.
 * برای تغییر: فهرست رو ویرایش کن و AP_VEHICLE_SEED_VERSION رو یکی بالا ببر.
 */

define('AP_VEHICLE_SEED_VERSION', '13');

function ap_vehicle_seed_data() {
    /* منبع: دیوار (divar.ir) ← خودرو ← برند و تیپ، آبان ۱۴۰۵. هر مدل: [نام نمایشی, اسلاگ, سال شروع, سال پایان, تیپ‌ها]
       تیپ‌ها: ['اسلاگ' => 'نام فارسی'] (یک سطح) یا ['اسلاگ' => ['اسلاگ' => 'نام']] (دو سطح، مثل گیربکس ← حجم موتور).
       سال‌ها تقریبی‌اند. */
    return [
        ['CHERY (چری)', 'chery', [
            ['Arrizo 5 (آریزو ۵)', 'arrizo-5', 1394, 1405, [
                'manual-luxury' => 'دنده‌ای لاکچری', 'auto-luxury' => 'اتوماتیک لاکچری', 'auto-excellent' => 'اتوماتیک اکسلنت',
                'sport-fl' => 'اسپرت (اف ال)', '5ie-new' => '5IE جدید', '5ie-new-turbo' => '5IE جدید توربو',
                '5te' => '5TE', '5te-excellent-turbo' => '5TE اکسلنت توربو']],
            ['Arrizo 6 (آریزو ۶)', 'arrizo-6', 1399, 1402, ['base' => 'استاندارد', 'excellent' => 'اکسلنت']],
            ['Tiggo 5 (تیگو ۵)', 'tiggo-5', 1394, 1405, [
                'excellent' => 'اکسلنت', 'luxury' => 'لاکچری', 'luxury-sport' => 'لاکچری اسپرت',
                't5-ie' => 'IE', 't5-il' => 'IL', 't5-te-turbo' => 'TE توربو']],
            ['Tiggo 7 (تیگو ۷)', 'tiggo-7', 1397, 1405, ['excellent' => 'اکسلنت', 't7-ie' => 'IE']],
            ['Cowin (کویین)', 'cowin', 1388, 1391],
            ['A15 Viana (ویانا A15)', 'a15-viana', 1388, 1391],
        ]],
        ['MVM (ام‌وی‌ام)', 'mvm', [
            ['110 (۱۱۰)', 'mvm-110', 1386, 1395],
            ['110S (۱۱۰ اس)', 'mvm-110s', 1393, 1396],
            ['315 Sedan (۳۱۵ صندوق‌دار)', 'mvm-315', 1388, 1398],
            ['315 Hatchback (۳۱۵ هاچبک)', 'mvm-315-hatchback', 1390, 1398],
            ['315 Hatchback Plus (۳۱۵ هاچبک پلاس)', 'mvm-315-hatchback-plus', 1395, 1398],
            ['530 (۵۳۰)', 'mvm-530', 1388, 1393],
            ['550 (۵۵۰)', 'mvm-550', 1390, 1395],
            ['Arrizo 5 EV (آریزو ۵ برقی)', 'mvm-arrizo-5-ev', 1402, 1405],
            ['Arrizo 5 Sport FL (آریزو ۵ اسپرت اف‌ال)', 'mvm-arrizo-5-sport-fl', 1397, 1400],
            ['M5 Pro (ام ۵ پرو)', 'mvm-m5-pro', 1404, 1405],
            ['X5 (ایکس ۵)', 'mvm-x5', 1404, 1405],
            ['X22 (ایکس ۲۲)', 'mvm-x22', 1395, 1401],
            ['X22 Pro (ایکس ۲۲ پرو)', 'mvm-x22-pro', 1400, 1405, ['ie' => 'IE', 'mt' => 'دنده‌ای (MT)']],
            ['X33 (ایکس ۳۳)', 'mvm-x33', 1390, 1398, ['cross-auto' => 'Cross اتوماتیک', 'automatic' => 'اتوماتیک', 'manual' => 'دنده‌ای']],
            ['X33 S (ایکس ۳۳ اس)', 'mvm-x33-s', 1396, 1401],
            ['X55 (ایکس ۵۵)', 'mvm-x55', 1396, 1405, ['pro-ie' => 'Pro IE', 'pro-ie-sport' => 'Pro IE اسپرت']],
            ['X77 (ایکس ۷۷)', 'mvm-x77', 1403, 1405],
        ]],
        ['FOWNIX (فونیکس)', 'fownix', [
            ['Arrizo 6 Pro (آریزو ۶ پرو)', 'arrizo-6-pro', 1400, 1404],
            ['Arrizo 6 GT (آریزو ۶ جی‌تی)', 'arrizo-6-gt', 1402, 1405],
            ['Arrizo 8 (آریزو ۸)', 'arrizo-8', 1403, 1405],
            ['FX (اف‌ایکس)', 'fx', 1402, 1405, ['premium-2wd' => 'پرمیوم تک دیفرانسیل', 'premium-awd' => 'پرمیوم دو دیفرانسیل']],
            ['FX EV (اف‌ایکس برقی)', 'fx-ev', 1403, 1405],
            ['Tiggo 7 Pro (تیگو ۷ پرو)', 'tiggo-7-pro', 1400, 1405, ['excellent' => 'اکسلنت', 'premium' => 'پریمیوم', 'e-plus-phev' => 'ای پلاس پلاگین هیبرید']],
            ['Tiggo 7 Pro Max (تیگو ۷ پرو مکس)', 'tiggo-7-pro-max', 1402, 1405, ['turbo-16-2wd' => '1.6 لیتر توربو تک دیفرانسیل', 'turbo-16-awd' => '1.6 لیتر توربو دو دیفرانسیل']],
            ['Tiggo 8 Pro (تیگو ۸ پرو)', 'tiggo-8-pro', 1400, 1405, ['base' => 'استاندارد', 'e-plus-phev' => 'ای پلاس پلاگین هیبرید']],
            ['Tiggo 8 Pro Max (تیگو ۸ پرو مکس)', 'tiggo-8-pro-max', 1402, 1405, ['ie' => 'IE', 'excellent' => 'اکسلنت']],
            ['Tiggo 9 (تیگو ۹)', 'tiggo-9', 1404, 1405],
        ]],
        ['JAC (جک)', 'jac', [
            ['J3 (جی ۳)', 'j3', 1392, 1397, ['sedan' => 'سدان', 'hatchback' => 'هاچبک']],
            ['J4 (جی ۴)', 'j4', 1396, 1402],
            ['J5 (جی ۵)', 'j5', 1393, 1397, ['auto-1800' => 'اتوماتیک 1800cc', 'manual-1500' => 'دنده‌ای 1500cc']],
            ['S3 (اس ۳)', 's3', 1393, 1401],
            ['S5 (اس ۵)', 's5', 1392, 1401, ['automatic' => 'اتوماتیک', 'newface' => 'نیوفیس', 'manual' => 'دنده‌ای']],
            ['Van (ون جک)', 'jac-van', 1395, 1402],
        ]],
        ['KMC (کی‌ام‌سی)', 'kmc', [
            ['A5 1.5 Turbo (ای ۵ ۱.۵ توربو)', 'a5', 1402, 1405],
            ['Eagle (ایگل)', 'eagle', 1404, 1405],
            ['GS6 (جی اس ۶)', 'gs6', 1403, 1405],
            ['J7 (جی ۷)', 'j7', 1401, 1405],
            ['J7 EV (جی ۷ برقی)', 'ej7', 1402, 1405],
            ['J7 EV Plus (جی ۷ برقی پلاس)', 'j7-ev-plus', 1403, 1405],
            ['K7 (کی ۷)', 'k7', 1401, 1405],
            ['SR3 (اس‌آر ۳)', 'sr3', 1401, 1405],
            ['SR6 1.5L Turbo (اس‌آر ۶ توربو)', 'sr6', 1404, 1405],
            ['T8 (تی ۸)', 't8', 1399, 1405],
            ['T9 Pickup (پیکاپ تی ۹)', 't9', 1402, 1405],
            ['X5 (ایکس ۵)', 'x5', 1402, 1405],
        ]],
        ['CHANGAN (چانگان)', 'changan', [
            ['CS35 (سی‌اس ۳۵)', 'cs35', 1394, 1400],
            ['CS35 Assembled (سی‌اس ۳۵ مونتاژ)', 'cs35-montage', 1396, 1400],
            ['CS35 Plus (سی‌اس ۳۵ پلاس)', 'cs35-plus', 1399, 1402, ['tip-2' => 'تیپ 2', 'tip-3' => 'تیپ 3']],
            ['CS55 Plus (سی‌اس ۵۵ پلاس)', 'cs55-plus', 1402, 1405],
            ['Eado (ایدو)', 'eado', 1395, 1397],
            ['Eado EV460 (ایدو EV460)', 'eado-ev460', 1398, 1400],
            ['Hunter Plus Pickup (پیکاپ هانتر پلاس)', 'hunter-plus', 1403, 1405],
            ['NEVO A05 (نوو A05)', 'nevo-a05', 1404, 1405],
            ['UNI-T (یونی تی)', 'uni-t', 1402, 1405, ['turbo-15' => '1.5 لیتر توربو']],
            ['X5 Plus (ایکس ۵ پلاس)', 'x5-plus', 1403, 1405],
        ]],
        ['CHANGHE (چانگهه)', 'changhe', [
            ['Karo Pickup (کارو)', 'karo', 1398, 1403, ['single-cab' => 'تک کابین', 'double-cab' => 'دو کابین']],
        ]],
        ['BRILLIANCE (برلیانس)', 'brilliance', [
            ['H220 (اچ ۲۲۰)', 'h220', 1395, 1399],
            ['H230 (اچ ۲۳۰)', 'h230', 1395, 1399, ['manual' => 'دنده‌ای', 'automatic' => 'اتوماتیک']],
            ['H320 (اچ ۳۲۰)', 'h320', 1394, 1399, ['manual' => ['1500cc'], 'automatic' => ['1500cc', '1650cc']]],
            ['H330 (اچ ۳۳۰)', 'h330', 1394, 1399, ['manual' => ['1500cc', '1650cc'], 'automatic' => ['1500cc', '1650cc']]],
            ['V5 (وی ۵)', 'v5', 1396, 1399],
            ['V6 (وی ۶)', 'v6', 1397, 1399],
            ['Cross (کراس)', 'cross', 1396, 1399, ['automatic' => ['1500cc', '1650cc']]],
            ['Van (ون برلیانس)', 'brilliance-van', 1394, 1399],
        ]],
        ['LIFAN (لیفان)', 'lifan', [
            ['520 (۵۲۰)', '520', 1387, 1393, ['520' => '520', '520i' => '520i']],
            ['620 (۶۲۰)', '620', 1388, 1395, ['1600cc' => '1600cc', '1800cc' => '1800cc']],
            ['820 (۸۲۰)', '820', 1395, 1398],
            ['X50 (ایکس ۵۰)', 'x50', 1395, 1398],
            ['X60 (ایکس ۶۰)', 'x60', 1392, 1399, ['manual' => 'دنده‌ای', 'automatic' => 'اتوماتیک']],
            ['X70 (ایکس ۷۰)', 'x70', 1397, 1399],
        ]],
        ['HAIMA (هایما)', 'haima', [
            ['7X (۷ ایکس)', '7x', 1403, 1405, ['turbo-16' => '1.6 لیتر توربو']],
            ['8S (۸ اس)', '8s', 1400, 1403],
            ['S5 (اس ۵)', 's5', 1396, 1403, ['auto-6' => 'اتوماتیک ۶ سرعته', 'pro' => 'Pro', 'cvt' => 'اتوماتیک CVT']],
            ['S7 (اس ۷)', 's7', 1394, 1403, ['pro-turbo-15' => 'Pro 1.5 لیتر توربو', 'plus-turbo-18' => 'پلاس 1.8 لیتر توربو', 'turbo-1800' => 'توربو 1800cc', '2000cc' => '2000cc']],
        ]],
        ['BYD (بی‌وای‌دی)', 'byd', [
            ['Destroyer 05 (دیسترویر ۰۵)', 'destroyer-05', 1403, 1405, ['phev-15' => '1.5 لیتر پلاگین هیبرید']],
            ['Dolphin (دلفین)', 'dolphin', 1403, 1405, ['fashion-ev' => 'فشن تمام برقی']],
            ['Han (هان)', 'han', 1403, 1405, ['phev-15' => '1.5 لیتر پلاگین هیبرید']],
            ['Qin L 1.5 PHEV (چین ال ۱.۵ پلاگین هیبرید)', 'qin-l', 1404, 1405],
            ['Qin Plus (چین پلاس)', 'qin-plus', 1403, 1405, ['phev-15-55km' => 'پلاگین هیبرید 1.5 لیتر 55 کیلومتر']],
            ['Seagull (سیگال)', 'seagull', 1404, 1405, ['fly' => 'فلای']],
            ['Seal 05 (سیل ۰۵)', 'seal-05', 1404, 1405, ['phev-15' => '1.5 لیتر پلاگین هیبرید']],
            ['Seal 06 (سیل ۰۶)', 'seal-06', 1404, 1405, ['phev-15' => '1.5 لیتر پلاگین هیبرید']],
            ['Sealion 06 (سی لاین ۰۶)', 'sealion-06', 1404, 1405, ['plus-170' => '170 پلاس']],
            ['Song Plus 1.5 Turbo (سانگ پلاس ۱.۵ توربو)', 'song-plus', 1403, 1405],
            ['F3 (اف ۳)', 'f3', 1389, 1392],
            ['S6 (اس ۶)', 's6', 1392, 1395],
            ['S7 (اس ۷)', 's7', 1394, 1397],
        ]],
        ['FIDELITY (فیدلیتی)', 'fidelity', [
            ['Elite (الیت)', 'elite', 1402, 1405, ['seats-5' => '۵ نفره', 'seats-7' => '۷ نفره']],
            ['Prime (پرایم)', 'prime', 1401, 1405, ['seats-5' => '۵ نفره', 'seats-7' => '۷ نفره']],
            ['Prestige (پرستیژ)', 'fidelity-prestige', 1402, 1405, ['seats-5' => '۵ نفره', 'seats-7' => '۷ نفره']],
            ['XB1 (ایکس بی ۱)', 'xb1', 1404, 1405],
        ]],
        ['DIGNITY (دیگنیتی)', 'dignity', [
            ['Prime (پرایم)', 'dignity-prime', 1402, 1405],
            ['Prestige (پرستیژ)', 'prestige', 1401, 1405],
        ]],
        ['RESPECT (ریسپکت)', 'respect', [
            ['Prime (پرایم)', 'respect-prime', 1402, 1405, ['tip-1' => 'تیپ ۱', 'tip-2' => 'تیپ ۲']],
        ]],
        ['HAVAL (هاوال)', 'haval', [
            ['H2 Assembled (H2 مونتاژ)', 'h2', 1397, 1401],
            ['H6 (اچ ۶)', 'h6', 1397, 1405, ['hybrid' => 'هیبرید', 'montage' => 'مونتاژ']],
            ['H8 (اچ ۸)', 'h8', 1396, 1398],
            ['H9 (اچ ۹)', 'h9', 1397, 1399],
            ['M4 Assembled (M4 مونتاژ)', 'm4', 1394, 1397],
        ]],
        ['LAMARI (لاماری)', 'lamari', [
            ['Eama (ایما)', 'eama', 1401, 1405, ['base' => 'استاندارد', 'hev-15-turbo' => 'HEV هیبرید 1.5 لیتر توربو']],
            ['Eco 1.5 Turbo (اکو ۱.۵ توربو)', 'eco', 1404, 1405],
            ['Hyrcani 1.6 Turbo (هیرکانی ۱.۶ توربو)', 'hyrcani', 1404, 1405],
            ['Neo 1.5 Turbo (نئو ۱.۵ توربو)', 'neo', 1404, 1405],
        ]],
        ['SSANGYONG (سانگ یانگ)', 'ssangyong', [
            ['Actyon (اکتیون)', 'actyon-old', 1385, 1391],
            ['New Actyon (نیو اکتیون)', 'new-actyon', 1391, 1397],
            ['Chairman (چیرمن)', 'chairman', 1387, 1391],
            ['Khan (خان)', 'khan', 1399, 1403],
            ['Korando (کوراندو)', 'korando-old', 1391, 1396],
            ['New Korando (نیو کوراندو)', 'new-korando', 1394, 1398],
            ['Kyron (کایرون)', 'kyron', 1385, 1391],
            ['Musso (موسو)', 'musso', 1383, 1404, ['grand-khan-signature' => 'گرند خان سیگنچر', 'grand-khan-sahara' => 'گرند خان ساهارا',
                'auto-3200' => '3200cc اتوماتیک', 'manual-3200' => '3200cc دنده‌ای', 'manual-2300' => '2300cc دنده‌ای', 'pickup' => 'پیکاپ']],
            ['Rexton (رکستون)', 'rexton-ssy', 1385, 1398, ['base' => 'استاندارد', 'montage' => 'مونتاژ']],
            ['Rodius (رودیوس)', 'rodius', 1387, 1392],
            ['Tivoli (تیوولی)', 'tivoli-ssy', 1394, 1399, ['elite' => 'الیت', 'armor' => 'ارمور', 'montage' => 'مونتاژ']],
        ]],
        ['KG MOBILITY (کی جی موبیلیتی)', 'kg-mobility', [
            ['Actyon (اکتیون)', 'actyon', 1404, 1405, ['turbo-15' => '1.5 لیتر توربو']],
            ['Korando (کوراندو)', 'korando', 1403, 1405, ['turbo-15' => '1.5 لیتر توربو']],
            ['Rexton (رکستون)', 'rexton', 1403, 1405, ['turbo-20' => '2.0 لیتر توربو']],
            ['Tivoli (تیوولی)', 'tivoli', 1403, 1405, ['turbo-15' => '1.5 لیتر توربو']],
            ['Torres (تورس)', 'torres', 1403, 1405],
        ]],
        ['INFINITI (اینفینیتی)', 'infiniti', [
            ['FX35 (اف‌ایکس ۳۵)', 'fx35', 1386, 1391],
            ['G35 Coupe (جی ۳۵ کوپه)', 'g35-coupe', 1385, 1388],
            ['G37 Coupe (جی ۳۷ کوپه)', 'g37-coupe', 1388, 1392],
            ['Q50 (کیو ۵۰)', 'q50', 1393, 1397],
            ['QX80 (کیو‌ایکس ۸۰)', 'qx80', 1393, 1397],
        ]],
        ['HONDA (هوندا)', 'honda', [
            ['Accord (آکورد)', 'accord', 1385, 1405, ['phev' => 'پلاگین هیبرید', 'ex' => 'EX', 'exa' => 'EXA', 'exl' => 'EXL', 'exb' => 'EXB']],
            ['City (سیتی)', 'city', 1401, 1404],
            ['Civic (سیویک)', 'civic', 1385, 1397],
            ['CR-V (سی‌آر‌وی)', 'cr-v', 1387, 1397],
            ['CR-X (سی‌آر‌ایکس)', 'cr-x', 1370, 1375],
            ['ENS1 EV (ای ان اس ۱ برقی)', 'ens1', 1403, 1405],
            ['HR-V (اچ‌آر‌وی)', 'hr-v', 1402, 1405, ['base' => 'استاندارد', 'ehev-15' => 'eHEV 1.5 لیتر']],
            ['Legend (لجند)', 'legend', 1385, 1392],
            ['Vezel (وزل)', 'vezel', 1402, 1405],
            ['ZR-V Hybrid (زد‌آر‌وی هیبرید)', 'zr-v', 1403, 1405],
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
        'excellent-turbo' => 'اکسلنت توربو', 'manual-luxury' => 'دنده‌ای لاکچری', 'auto-luxury' => 'اتوماتیک لاکچری', 'auto-excellent' => 'اتوماتیک اکسلنت', 'seats-5' => '۵ نفره', 'seats-7' => '۷ نفره', 'turbo-20' => '2.0 لیتر توربو'];
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
            $name = (!is_int($k) && is_string($v)) ? $v : ap_vehicle_variant_name($slug);   /* 'اسلاگ' => 'نام فارسی' */
            $sub  = (is_int($k) || is_string($v)) ? null : $v;
            $id = ap_vehicle_seed_term($name, $slug, $parent);
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
            /* فقط جابه‌جایی‌های مشخص (MVM که قبلاً زیرِ چری بود)؛ اسلاگ‌های مشترک مثل s5 (جک و هایما) دست نمی‌خورن */
            $moves = ['mvm' => ['chery']];
            if ($parentTerm && !is_wp_error($parentTerm) && (int) $parentTerm->parent === 0 && (int) $t->parent !== $bid
                && in_array($parentTerm->slug, $moves[$bslug] ?? [], true)) {
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
