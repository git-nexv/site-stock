<?php
if (!defined('ABSPATH')) { exit; }

/**
 * صفحه‌های فرودِ سئو (آذر ۱۴۰۵) — برای اینکه سایت روی اسمِ برند و کلمه‌های کلیِ بازار هم دیده بشه:
 *
 * ۱) صفحه‌های کلمه‌ی کلیدی (هر کدوم متنِ اختصاصی، پرسش‌های پرتکرار و لینک به برندها/دسته‌ها):
 *    /cartehran/          «کار تهران | تهران کار | CarTehran» (هویتِ برند)
 *    /car-parts/          «قطعات خودرو»
 *    /spare-parts/        «لوازم یدکی خودرو»
 *    /car-parts-tehran/   «لوازم یدکی در تهران»
 *    /chinese-car-parts/  «قطعات خودروهای چینی»
 * ۲) لیستِ قیمت (با قیمتِ واقعیِ محصولات؛ بدونِ محصول = ۴۰۴):
 *    /parts-price/                  همه‌ی برندها
 *    /parts-price/<brand>/          مثلاً /parts-price/mvm/
 *    /parts-price/<brand>/<model>/  مثلاً /parts-price/chery/tiggo-7/
 * ۳) سایت‌مپِ همه‌ی این‌ها: /landing-sitemap.xml (خودکار به sitemap_index.xml اضافه می‌شه).
 *
 * صفحه‌ها مستقل رندر می‌شن (مثلِ «قوانین»)، پس متنِ کامل بدونِ جاوااسکریپت در HTML هست.
 */

/* ---------- داده ---------- */
function ap_lp_split($name) {
    $name = trim((string) $name);
    if (preg_match('/^(.*?)\s*\((.+)\)\s*$/u', $name, $m)) return [trim($m[1]), trim($m[2])];
    return preg_match('/\p{Arabic}/u', $name) ? ['', $name] : [$name, ''];
}
/* «چری (Chery)» برای متن؛ اگه فارسی نداشت فقط انگلیسی */
function ap_lp_label($name) {
    [$en, $fa] = ap_lp_split($name);
    if ($en !== '' && preg_match('/^[A-Z0-9 \-]+$/', $en) && strlen($en) > 3) $en = ucwords(strtolower($en));
    $en = strtr($en, ['Mvm' => 'MVM', 'Jac' => 'JAC', 'Bmw' => 'BMW', 'Byd' => 'BYD', 'Kmc' => 'KMC', 'Mg' => 'MG', 'Ssangyong' => 'SsangYong', 'Kg Mobility' => 'KG Mobility']);
    return $fa !== '' ? ($en !== '' ? "$fa ($en)" : $fa) : $en;
}
function ap_lp_fa($name) { [$en, $fa] = ap_lp_split($name); return $fa !== '' ? $fa : ap_lp_label($name); }
function ap_lp_short($t) { return function_exists('ap_vehicle_short') ? ap_vehicle_short($t) : $t->slug; }
function ap_lp_ver() { return (int) get_option('ap_lp_ver', 1); }
add_action('save_post_ap_product', function () { update_option('ap_lp_ver', ap_lp_ver() + 1, false); });

/* برندهایی که محصول دارن: [term, label, fa, short, count] — مرتب بر اساسِ تعداد محصول */
function ap_lp_brands() {
    $key = 'ap_lp_brands_' . ap_lp_ver();
    $c = get_transient($key);
    if (is_array($c)) return $c;
    $out = [];
    foreach ((array) get_terms(['taxonomy' => 'ap_vehicle', 'parent' => 0, 'hide_empty' => false]) as $b) {
        if (is_wp_error($b)) continue;
        $n = (int) (new WP_Query(['post_type' => 'ap_product', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false,
            'tax_query' => [['taxonomy' => 'ap_vehicle', 'terms' => (int) $b->term_id, 'include_children' => true]]]))->found_posts;
        if ($n < 1) continue;
        $out[] = ['id' => (int) $b->term_id, 'label' => ap_lp_label($b->name), 'fa' => ap_lp_fa($b->name), 'short' => ap_lp_short($b), 'count' => $n];
    }
    usort($out, function ($a, $b) { return $b['count'] <=> $a['count']; });
    set_transient($key, $out, 12 * HOUR_IN_SECONDS);
    return $out;
}
function ap_lp_models($brandId) {
    $key = 'ap_lp_models_' . (int) $brandId . '_' . ap_lp_ver();
    $c = get_transient($key);
    if (is_array($c)) return $c;
    $out = [];
    foreach ((array) get_terms(['taxonomy' => 'ap_vehicle', 'parent' => (int) $brandId, 'hide_empty' => false]) as $m) {
        if (is_wp_error($m)) continue;
        $ids = get_posts(['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => 300, 'fields' => 'ids',
            'tax_query' => [['taxonomy' => 'ap_vehicle', 'terms' => (int) $m->term_id, 'include_children' => true]]]);
        if (!$ids) continue;
        $out[] = ['id' => (int) $m->term_id, 'label' => ap_lp_label($m->name), 'fa' => ap_lp_fa($m->name), 'short' => ap_lp_short($m), 'ids' => array_map('intval', $ids)];
    }
    usort($out, function ($a, $b) { return count($b['ids']) <=> count($a['ids']); });
    set_transient($key, $out, 12 * HOUR_IN_SECONDS);
    return $out;
}
/* کمترین قیمتِ محصول (قیمتِ اصلی یا ارزون‌ترین متغیر)؛ ۰ = تماس بگیرید */
function ap_lp_price($pid) {
    $all = [];
    $base = (int) get_post_meta($pid, '_ap_price', true);
    if ($base > 0) $all[] = $base;
    if (function_exists('ap_variant_prices')) foreach ((array) ap_variant_prices($pid) as $v) if ((int) $v > 0) $all[] = (int) $v;
    return $all ? min($all) : 0;
}
function ap_lp_money($n) {
    return $n > 0 ? ap_fa_digits(number_format($n, 0, '.', '٬')) . ' تومان' : 'تماس بگیرید';
}
/* دسته‌ی هر محصول (اولین دسته) */
function ap_lp_cat($pid) {
    $t = get_the_terms($pid, 'ap_cat');
    return ($t && !is_wp_error($t)) ? $t[0] : null;
}

/* ---------- صفحه‌های کلمه‌ی کلیدی ---------- */
function ap_lp_keyword_pages() {
    $site = get_bloginfo('name') ?: 'کار تهران';
    return [
        'cartehran' => [
            'title' => 'کار تهران (CarTehran) | فروشگاه اینترنتی لوازم یدکی خودرو',
            'h1'    => 'کار تهران | CarTehran — فروشگاه آنلاین لوازم یدکی و قطعات خودرو',
            'desc'  => 'کار تهران (تهران کار، CarTehran) فروشگاه اینترنتی لوازم یدکی و قطعات خودرو در تهران؛ قطعات اورجینال و درجه یک خودروهای چینی و داخلی با قیمت روز و ارسال به سراسر ایران.',
            'kw'    => 'کار تهران، تهران کار، کارتهران، CarTehran، Car Tehran',
            'intro' => [
                '<b>کار تهران</b> (به انگلیسی <b>CarTehran</b> — بعضی‌ها ما رو با اسمِ «تهران کار» یا «کارتهران» هم جستجو می‌کنن) یک فروشگاهِ اینترنتیِ تخصصیِ <b>لوازم یدکی و قطعات خودرو</b> در تهرانه. هدفِ ما اینه که پیدا کردن و خریدِ قطعه‌ی درست برای ماشینتون ساده، سریع و مطمئن باشه.',
                'در کار تهران قطعات را بر اساسِ <b>برند، مدل، تیپ و سالِ ساختِ خودرو</b> دسته‌بندی کرده‌ایم؛ کافیه خودروی خودتون رو انتخاب کنید تا فقط قطعاتِ سازگار با همون ماشین نمایش داده بشه. از قطعاتِ موتوری و برقی تا بدنه، چراغ، جلوبندی و ترمز.',
                'همه‌ی قطعات به دو صورتِ <b>اورجینال (جنیون)</b> و <b>درجه یک (آفترمارکت)</b> عرضه می‌شن و قبل از ارسال، تطبیقِ قطعه با خودروی شما (از روی کد فنی یا شماره‌ی شاسی) بررسی می‌شه.',
            ],
            'faq' => [
                ['کار تهران چیست؟', 'کار تهران (CarTehran) فروشگاه اینترنتیِ لوازم یدکی و قطعات خودرو در تهران است که قطعات اورجینال و درجه یک خودروهای چینی و داخلی را با ارسال به سراسر ایران عرضه می‌کند.'],
                ['آدرس سایت کار تهران چیست؟', 'آدرس رسمی فروشگاه ' . preg_replace('#^https?://#', '', untrailingslashit(home_url('/'))) . ' است.'],
                ['تهران کار و کار تهران یکی هستند؟', 'بله؛ اسم فروشگاه «کار تهران» (CarTehran) است و بعضی کاربران آن را «تهران کار» یا «کارتهران» جستجو می‌کنند.'],
                ['آیا کار تهران به شهرستان ارسال دارد؟', 'بله، سفارش‌ها به سراسر ایران ارسال می‌شوند و در تهران امکان ارسال سریع وجود دارد.'],
            ],
        ],
        'car-parts' => [
            'title' => 'قطعات خودرو | خرید آنلاین قطعات یدکی ماشین با قیمت روز | ' . $site,
            'h1'    => 'خرید قطعات خودرو با قیمت روز',
            'desc'  => 'خرید اینترنتی قطعات خودرو: قطعات موتوری، برقی، بدنه، چراغ، ترمز و جلوبندی خودروهای چینی و داخلی؛ اورجینال و درجه یک با تطبیق دقیق مدل در ' . $site . '.',
            'kw'    => 'قطعات خودرو، قطعات ماشین، قطعات یدکی خودرو',
            'intro' => [
                'خریدِ <b>قطعات خودرو</b> وقتی ساده می‌شه که قطعه دقیقاً با مدل و سالِ ماشین شما بخونه. در ' . esc_html($site) . ' هر قطعه به مدلِ خودروی مربوطش وصل شده و می‌تونید قطعاتِ ماشینتون رو بر اساسِ برند و مدل پیدا کنید.',
                'قطعاتِ پرتقاضا مثلِ <b>لنت و دیسک ترمز</b>، <b>چراغ جلو و عقب</b>، <b>سپر و گلگیر</b>، <b>کمک فنر و طبق</b>، <b>فیلترها</b>، <b>رادیاتور</b>، <b>دینام و استارت</b> برای خودروهای چینی و داخلی موجوده.',
                'قیمتِ هر قطعه برای نوعِ اورجینال و درجه یک جداست؛ اگه برای قطعه‌ای قیمت ثبت نشده، با یک تماس قیمتِ روز رو اعلام می‌کنیم.',
            ],
            'faq' => [
                ['چطور قطعه‌ی مناسب ماشینم را پیدا کنم؟', 'برند و مدل خودرو را انتخاب کنید تا فقط قطعات سازگار نمایش داده شود؛ برای اطمینان، کد فنی (OEM) یا شماره‌ی شاسی را برای ما بفرستید.'],
                ['فرق قطعه اورجینال و درجه یک چیست؟', 'قطعه‌ی اورجینال (جنیون) همان قطعه‌ی کارخانه است؛ درجه یک (آفترمارکت) توسط برندهای معتبر با کیفیت نزدیک به اصلی و قیمت کمتر ساخته می‌شود.'],
                ['قطعات گارانتی دارند؟', 'اصالت همه‌ی قطعات تضمین می‌شود و شرایط مرجوعی در صفحه‌ی قوانین آمده است.'],
            ],
        ],
        'spare-parts' => [
            'title' => 'لوازم یدکی خودرو | فروشگاه اینترنتی لوازم یدکی ماشین | ' . $site,
            'h1'    => 'فروشگاه اینترنتی لوازم یدکی خودرو',
            'desc'  => 'خرید لوازم یدکی خودرو به‌صورت آنلاین؛ لوازم یدکی چری، ام وی ام، جک، هاوال، کی ام سی، دانگ فنگ و خودروهای داخلی با قیمت روز و ارسال سریع از ' . $site . '.',
            'kw'    => 'لوازم یدکی، لوازم یدکی خودرو، لوازم یدکی ماشین، فروشگاه لوازم یدکی',
            'intro' => [
                esc_html($site) . ' یک <b>فروشگاه اینترنتی لوازم یدکی خودرو</b>ه؛ یعنی بدون رفتن به بازارِ لوازم یدکی، می‌تونید قطعه‌ی ماشینتون رو آنلاین انتخاب، قیمتش رو ببینید و سفارش بدید.',
                'تمرکزِ ما روی <b>لوازم یدکی خودروهای چینی</b> (چری، ام وی ام، جک، هاوال، جیلی، بی وای دی، فونیکس و ...) و خودروهای پرتردد داخلیه؛ برای هر مدل، قطعاتِ پرمصرف از قبل آماده شده.',
                'اگه قطعه‌ی موردنظرتون رو پیدا نکردید، از طریقِ واتساپ یا تماس، مدلِ ماشین و اسمِ قطعه رو بفرستید تا تأمینش کنیم.',
            ],
            'faq' => [
                ['لوازم یدکی را چطور سفارش بدهم؟', 'محصول را به سبد اضافه کنید و سفارش را ثبت کنید، یا برای استعلام قیمت و موجودی از طریق واتساپ با ما در تماس باشید.'],
                ['لوازم یدکی چه خودروهایی موجود است؟', 'لوازم یدکی بیشتر خودروهای چینی و داخلیِ پرتردد؛ فهرست کامل برندها در همین صفحه آمده است.'],
                ['هزینه و زمان ارسال چقدر است؟', 'سفارش‌های تهران معمولاً همان روز یا روز بعد و شهرستان‌ها با پست/باربری ارسال می‌شوند.'],
            ],
        ],
        'car-parts-tehran' => [
            'title' => 'لوازم یدکی در تهران | خرید قطعات ماشین در تهران | ' . $site,
            'h1'    => 'خرید لوازم یدکی و قطعات خودرو در تهران',
            'desc'  => 'دنبال لوازم یدکی در تهران هستید؟ ' . $site . ' قطعات خودروهای چینی و داخلی را با قیمت روز، تطبیق دقیق مدل و ارسال سریع در تهران و سراسر ایران عرضه می‌کند.',
            'kw'    => 'لوازم یدکی تهران، قطعات خودرو تهران، فروشگاه لوازم یدکی در تهران',
            'intro' => [
                'اگه در تهران دنبالِ <b>لوازم یدکی</b> هستید، لازم نیست بین مغازه‌های بازار دنبالِ قطعه بگردید؛ در ' . esc_html($site) . ' قطعه رو آنلاین پیدا می‌کنید و سفارش در تهران سریع ارسال می‌شه.',
                'برای تعمیرکارها و صاحبانِ خودرو در تهران، امکانِ استعلامِ سریع از طریقِ واتساپ و تماس وجود داره؛ عکسِ قطعه یا کدِ فنی رو بفرستید تا قطعه‌ی دقیق رو پیشنهاد بدیم.',
                'به شهرستان‌ها هم با پست و باربری ارسال داریم.',
            ],
            'faq' => [
                ['ارسال در تهران چقدر طول می‌کشد؟', 'سفارش‌های تهران معمولاً همان روز یا روز کاری بعد ارسال می‌شوند.'],
                ['می‌توانم قبل از خرید استعلام بگیرم؟', 'بله؛ مدل خودرو و نام قطعه یا عکس آن را در واتساپ بفرستید.'],
            ],
        ],
        'chinese-car-parts' => [
            'title' => 'قطعات خودروهای چینی | لوازم یدکی چری، ام وی ام، جک، هاوال | ' . $site,
            'h1'    => 'قطعات و لوازم یدکی خودروهای چینی',
            'desc'  => 'خرید قطعات خودروهای چینی: لوازم یدکی چری، ام وی ام، جک، هاوال، جیلی، بی وای دی، فونیکس، دیگنیتی و ... اورجینال و درجه یک با قیمت روز در ' . $site . '.',
            'kw'    => 'قطعات خودرو چینی، لوازم یدکی چینی، لوازم یدکی چری، قطعات ام وی ام',
            'intro' => [
                'خودروهای چینی بخشِ بزرگی از ماشین‌های امروزِ ایران رو تشکیل می‌دن و پیدا کردنِ <b>قطعاتِ یدکیِ</b> درست براشون همیشه آسون نیست. ' . esc_html($site) . ' برای هر برند و مدلِ چینی، صفحه‌ی جدا با قطعاتِ سازگار داره.',
                'از <b>لوازم یدکی چری و ام وی ام</b> تا <b>جک، هاوال، جیلی، بی وای دی، فونیکس، دیگنیتی و فیدلیتی</b>؛ قطعاتِ موتوری، بدنه، چراغ و جلوبندی برای مدل‌های پرتردد موجوده.',
            ],
            'faq' => [
                ['قطعات خودروهای چینی اورجینال است؟', 'هر دو نوع اورجینال و درجه یک موجود است و نوع قطعه هنگام خرید مشخص می‌شود.'],
                ['قطعات ام وی ام و چری یکی هستند؟', 'بسیاری از مدل‌های ام وی ام بر پایه‌ی چری ساخته شده‌اند و بعضی قطعات مشترک‌اند؛ برای اطمینان کد فنی قطعه را بفرستید.'],
            ],
        ],
    ];
}

/* ---------- آدرس‌ها ---------- */
function ap_lp_all_urls() {
    $urls = [];
    foreach (array_keys(ap_lp_keyword_pages()) as $k) $urls[] = home_url("/$k/");
    $brands = ap_lp_brands();
    if ($brands) $urls[] = home_url('/parts-price/');
    foreach ($brands as $b) {
        $urls[] = home_url('/parts-price/' . $b['short'] . '/');
        foreach (ap_lp_models($b['id']) as $m) $urls[] = home_url('/parts-price/' . $b['short'] . '/' . $m['short'] . '/');
    }
    return $urls;
}

/* ---------- رندر ---------- */
function ap_lp_render($o) {
    $site = get_bloginfo('name') ?: 'کار تهران';
    $phone = (string) get_theme_mod('ap_contact_phone', '09109220001');
    $wa = preg_replace('/[^0-9]/', '', (string) get_theme_mod('ap_contact_whatsapp', '09109220001'));
    if (substr($wa, 0, 1) === '0') $wa = '98' . substr($wa, 1);
    $graph = [];
    $crumbs = array_merge([[$site, home_url('/')]], $o['crumbs']);
    $li = []; $i = 0;
    foreach ($crumbs as $c) $li[] = ['@type' => 'ListItem', 'position' => ++$i, 'name' => wp_strip_all_tags($c[0]), 'item' => $c[1]];
    $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $li];
    if (!empty($o['items'])) {
        $il = []; $i = 0;
        foreach (array_slice($o['items'], 0, 100) as $it) $il[] = ['@type' => 'ListItem', 'position' => ++$i, 'url' => $it[1], 'name' => wp_strip_all_tags($it[0])];
        $graph[] = ['@type' => 'ItemList', 'name' => wp_strip_all_tags($o['h1']), 'itemListElement' => $il];
    }
    if (!empty($o['faq'])) {
        $q = [];
        foreach ($o['faq'] as $f) $q[] = ['@type' => 'Question', 'name' => $f[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => wp_strip_all_tags($f[1])]];
        $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $q];
    }
    if (!empty($o['org'])) $graph = array_merge($graph, ap_lp_org_schema());
    status_header(200);
    header('Content-Type: text/html; charset=UTF-8');
    $og = function_exists('ap_icon_url') ? ap_icon_url('icon-512.png') : '';
    ?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<script>(function(){try{var t=localStorage.getItem('ap_theme'); if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php echo function_exists('ap_favicon_tags') ? ap_favicon_tags() : ''; ?>
<title><?php echo esc_html($o['title']); ?></title>
<meta name="description" content="<?php echo esc_attr($o['desc']); ?>">
<?php if (!empty($o['kw'])): ?><meta name="keywords" content="<?php echo esc_attr($o['kw']); ?>"><?php endif; ?>
<meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
<link rel="canonical" href="<?php echo esc_url($o['url']); ?>">
<meta property="og:locale" content="fa_IR"><meta property="og:type" content="website"><meta property="og:site_name" content="<?php echo esc_attr($site); ?>">
<meta property="og:title" content="<?php echo esc_attr($o['title']); ?>"><meta property="og:description" content="<?php echo esc_attr($o['desc']); ?>">
<meta property="og:url" content="<?php echo esc_url($o['url']); ?>"><?php if ($og): ?><meta property="og:image" content="<?php echo esc_url($og); ?>"><?php endif; ?>
<script type="application/ld+json"><?php echo wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<style>
:root{--bg:#f7f6fb;--card:#fff;--text:#201f2b;--dim:#5c5a6c;--line:#eee;--accent:#B3122E;--soft:#fbeff1}
html[data-theme="dark"]{--bg:#08080d;--card:#15141c;--text:#ececf4;--dim:#b9b6c9;--line:#26242f;--accent:#ff8a9b;--soft:#2a1418}
html,body{background:var(--bg)}
body{font-family:'Vazirmatn',Tahoma,sans-serif;margin:0;padding:28px 16px 120px;color:var(--text);line-height:2}
.lp{max-width:900px;margin:0 auto;background:var(--card);border-radius:24px;padding:30px 26px;box-shadow:0 20px 50px rgba(0,0,0,.07)}
.lp nav.bc{font-size:12.5px;color:var(--dim);margin-bottom:10px}.lp nav.bc a{color:var(--dim);text-decoration:none}
.lp h1{font-size:23px;line-height:1.6;margin:0 0 6px}.lp .upd{font-size:12.5px;color:var(--dim);margin:0 0 16px}
.lp h2{font-size:17px;margin:28px 0 10px;padding-top:18px;border-top:1px solid var(--line)}
.lp p{font-size:14.5px;color:var(--dim);margin:0 0 12px}.lp p b{color:var(--text)}
.lp a{color:var(--accent)}
.cta{display:flex;flex-wrap:wrap;gap:10px;margin:16px 0 4px}.cta a{flex:1 1 180px;text-align:center;padding:11px 14px;border-radius:14px;font-weight:700;font-size:14px;text-decoration:none;background:var(--accent);color:#fff}.cta a.alt{background:var(--soft);color:var(--accent)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:8px;padding:0;margin:0;list-style:none}
.grid a{display:block;padding:10px 12px;border:1px solid var(--line);border-radius:12px;text-decoration:none;color:var(--text);font-size:13.5px}.grid a small{display:block;color:var(--dim);font-size:12px}
table{width:100%;border-collapse:collapse;font-size:13.5px}td,th{padding:9px 6px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}th{color:var(--dim);font-weight:600}td.pr{white-space:nowrap;font-weight:700}
td a{color:var(--text);text-decoration:none}
details{border:1px solid var(--line);border-radius:12px;padding:10px 14px;margin:8px 0}summary{cursor:pointer;font-weight:700;font-size:14px}details p{margin:8px 0 0}
</style>
</head>
<body>
<main class="lp">
<nav class="bc"><?php $n = count($crumbs); foreach ($crumbs as $k => $c) { echo $k < $n - 1 ? '<a href="' . esc_url($c[1]) . '">' . esc_html($c[0]) . '</a> › ' : esc_html($c[0]); } ?></nav>
<h1><?php echo esc_html($o['h1']); ?></h1>
<?php if (!empty($o['updated'])): ?><p class="upd">آخرین به‌روزرسانیِ قیمت‌ها: <?php echo esc_html($o['updated']); ?></p><?php endif; ?>
<?php foreach ((array) $o['intro'] as $p) echo '<p>' . wp_kses($p, ['b' => [], 'a' => ['href' => []]]) . '</p>'; ?>
<div class="cta"><a href="<?php echo esc_url($o['shop'] ?? home_url('/')); ?>"><?php echo esc_html($o['shop_label'] ?? 'ورود به فروشگاه'); ?></a><?php if ($wa): ?><a class="alt" href="https://wa.me/<?php echo esc_attr($wa); ?>">استعلام در واتساپ</a><?php endif; ?><a class="alt" href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>">تماس: <span dir="ltr"><?php echo esc_html($phone); ?></span></a></div>
<?php echo $o['body']; ?>
<?php if (!empty($o['faq'])): ?><h2>پرسش‌های پرتکرار</h2><?php foreach ($o['faq'] as $f) echo '<details><summary>' . esc_html($f[0]) . '</summary><p>' . esc_html(wp_strip_all_tags($f[1])) . '</p></details>'; endif; ?>
<?php echo ap_lp_more_links($o['url']); ?>
</main>
<?php echo function_exists('ap_static_dock_html') ? ap_static_dock_html() : ''; ?>
</body>
</html><?php
    exit;
}

/* لینک‌های مشترکِ پایینِ صفحه‌ها (همه‌ی صفحه‌های فرود به هم لینک دارن) */
function ap_lp_more_links($self) {
    $links = [];
    $names = ['cartehran' => 'درباره کار تهران', 'spare-parts' => 'لوازم یدکی خودرو', 'car-parts' => 'قطعات خودرو', 'car-parts-tehran' => 'لوازم یدکی در تهران', 'chinese-car-parts' => 'قطعات خودروهای چینی'];
    foreach ($names as $k => $n) $links[] = [home_url("/$k/"), $n];
    $links[] = [home_url('/parts-price/'), 'لیست قیمت قطعات خودرو'];
    $h = '<h2>صفحه‌های مرتبط</h2><ul class="grid">';
    foreach ($links as $l) if (untrailingslashit($l[0]) !== untrailingslashit($self)) $h .= '<li><a href="' . esc_url($l[0]) . '">' . esc_html($l[1]) . '</a></li>';
    return $h . '</ul>';
}
function ap_lp_brand_grid($withPrice = false) {
    $h = '<ul class="grid">';
    foreach (ap_lp_brands() as $b) {
        $t = get_term($b['id'], 'ap_vehicle');
        $url = $withPrice ? home_url('/parts-price/' . $b['short'] . '/') : (($t && !is_wp_error($t)) ? get_term_link($t) : home_url('/'));
        if (is_wp_error($url)) continue;
        $h .= '<li><a href="' . esc_url($url) . '">' . ($withPrice ? 'قیمت قطعات ' : 'لوازم یدکی ') . esc_html($b['label']) . '<small>' . esc_html(ap_fa_digits($b['count'])) . ' قطعه</small></a></li>';
    }
    return $h . '</ul>';
}
function ap_lp_cat_grid() {
    $h = '<ul class="grid">';
    foreach ((array) get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC', 'number' => 40]) as $c) {
        if (is_wp_error($c)) continue;
        $l = get_term_link($c);
        if (!is_wp_error($l)) $h .= '<li><a href="' . esc_url($l) . '">' . esc_html($c->name) . '<small>' . esc_html(ap_fa_digits($c->count)) . ' محصول</small></a></li>';
    }
    return $h . '</ul>';
}

/* اسکیمای هویتِ برند (برای اسمِ سایت در گوگل و پنلِ دانش) */
function ap_lp_org_schema() {
    $site = get_bloginfo('name') ?: 'کار تهران';
    $alts = array_values(array_unique(array_filter(['کار تهران', 'تهران کار', 'کارتهران', 'CarTehran', 'Car Tehran', $site])));
    $ig = (string) get_theme_mod('ap_instagram_url', 'https://www.instagram.com/auto.nexv.ir');
    $logo = function_exists('ap_logo_url') ? ap_logo_url() : '';
    return [
        ['@type' => 'WebSite', '@id' => home_url('/#website'), 'url' => home_url('/'), 'name' => 'کار تهران', 'alternateName' => $alts, 'inLanguage' => 'fa-IR',
         'publisher' => ['@id' => home_url('/#organization')],
         'potentialAction' => ['@type' => 'SearchAction', 'target' => home_url('/?ap_search={search_term_string}'), 'query-input' => 'required name=search_term_string']],
        ['@type' => ['Organization', 'AutoPartsStore'], '@id' => home_url('/#organization'), 'name' => 'کار تهران', 'alternateName' => $alts, 'url' => home_url('/'),
         'logo' => $logo, 'image' => $logo, 'telephone' => (string) get_theme_mod('ap_contact_phone', '09109220001'),
         'description' => 'فروشگاه اینترنتی لوازم یدکی و قطعات خودرو در تهران؛ قطعات اورجینال و درجه یک خودروهای چینی و داخلی.',
         'address' => ['@type' => 'PostalAddress', 'addressLocality' => 'تهران', 'addressRegion' => 'تهران', 'addressCountry' => 'IR'],
         'areaServed' => ['@type' => 'Country', 'name' => 'Iran'], 'priceRange' => '$$',
         'sameAs' => array_values(array_filter([$ig]))],
    ];
}

/* ---------- مسیریابی (قبل از کوئریِ اصلیِ وردپرس؛ بدون نیاز به بازسازیِ پیوندها) ---------- */
add_action('parse_request', function () {
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $homePath = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($homePath !== '' && strpos($path, $homePath . '/') === 0) $path = substr($path, strlen($homePath) + 1);
    if ($path === '') return;
    $seg = explode('/', $path);
    if (!preg_match('#^[a-z0-9-]+(/[a-z0-9-]+){0,2}$#', $path)) return;
    $kw = ap_lp_keyword_pages();
    $site = get_bloginfo('name') ?: 'کار تهران';

    if (count($seg) === 1 && isset($kw[$seg[0]])) {
        $p = $kw[$seg[0]];
        $body = '<h2>لوازم یدکی بر اساس برند خودرو</h2>' . ap_lp_brand_grid(false)
              . '<h2>دسته‌بندی قطعات</h2>' . ap_lp_cat_grid();
        ap_lp_render(['url' => home_url('/' . $seg[0] . '/'), 'title' => $p['title'], 'h1' => $p['h1'], 'desc' => $p['desc'], 'kw' => $p['kw'],
            'intro' => $p['intro'], 'faq' => $p['faq'], 'body' => $body, 'crumbs' => [[$p['h1'], home_url('/' . $seg[0] . '/')]], 'org' => $seg[0] === 'cartehran']);
    }
    if ($seg[0] !== 'parts-price') return;

    $brands = ap_lp_brands();
    $today = function_exists('ap_jalali_pretty') ? ap_jalali_pretty(current_time('timestamp')) : '';
    if (count($seg) === 1) {
        if (!$brands) return;
        ap_lp_render(['url' => home_url('/parts-price/'), 'title' => 'لیست قیمت قطعات خودرو | قیمت روز لوازم یدکی | ' . $site,
            'h1' => 'لیست قیمت قطعات و لوازم یدکی خودرو', 'updated' => $today,
            'desc' => 'لیست قیمت روز قطعات و لوازم یدکی خودروهای چینی و داخلی به تفکیک برند و مدل؛ قیمت قطعات اورجینال و درجه یک در ' . $site . '.',
            'kw' => 'قیمت قطعات خودرو، قیمت لوازم یدکی، لیست قیمت قطعات',
            'intro' => ['برای دیدنِ <b>قیمتِ روزِ قطعات</b>، برندِ خودروتون رو انتخاب کنید. قیمت‌ها برای هر قطعه از ارزون‌ترین نوع (معمولاً درجه یک) شروع می‌شه و قطعه‌هایی که قیمت ندارن با یک تماس استعلام می‌شن.'],
            'faq' => [['قیمت‌ها به‌روز هستند؟', 'بله، قیمت‌ها از روی محصولات فروشگاه خوانده می‌شوند و با هر تغییر قیمت، این صفحه هم به‌روز می‌شود.'], ['چرا بعضی قطعات قیمت ندارند؟', 'قیمت بعضی قطعات به‌دلیل نوسان بازار هنگام سفارش اعلام می‌شود؛ برای استعلام تماس بگیرید.']],
            'body' => '<h2>قیمت قطعات بر اساس برند</h2>' . ap_lp_brand_grid(true), 'crumbs' => [['لیست قیمت قطعات', home_url('/parts-price/')]]]);
    }
    $brand = null;
    foreach ($brands as $b) if ($b['short'] === $seg[1]) { $brand = $b; break; }
    if (!$brand) return;
    $bterm = get_term($brand['id'], 'ap_vehicle');
    $blink = ($bterm && !is_wp_error($bterm)) ? get_term_link($bterm) : home_url('/');
    if (is_wp_error($blink)) $blink = home_url('/');
    $models = ap_lp_models($brand['id']);
    $bc = [['لیست قیمت قطعات', home_url('/parts-price/')], ['قیمت قطعات ' . $brand['fa'], home_url('/parts-price/' . $brand['short'] . '/')]];

    if (count($seg) === 2) {
        if (!$models) return;
        $body = '<h2>قیمت قطعات بر اساس مدل ' . esc_html($brand['fa']) . '</h2><ul class="grid">';
        $items = [];
        foreach ($models as $m) {
            $u = home_url('/parts-price/' . $brand['short'] . '/' . $m['short'] . '/');
            $items[] = ['قیمت قطعات ' . $m['label'], $u];
            $body .= '<li><a href="' . esc_url($u) . '">قیمت قطعات ' . esc_html($m['label']) . '<small>' . esc_html(ap_fa_digits(count($m['ids']))) . ' قطعه</small></a></li>';
        }
        $body .= '</ul>';
        /* نوعِ قطعه‌های این برند ← صفحه‌های «برند + قطعه» */
        $cats = [];
        foreach ($models as $m) foreach ($m['ids'] as $pid) { $c = ap_lp_cat($pid); if ($c) $cats[$c->term_id] = $c; }
        if ($cats) {
            $body .= '<h2>قطعات ' . esc_html($brand['fa']) . ' بر اساس نوع قطعه</h2><ul class="grid">';
            foreach ($cats as $c) $body .= '<li><a href="' . esc_url(trailingslashit($blink) . 'part/' . $c->slug . '/') . '">' . esc_html($c->name . ' ' . $brand['fa']) . '</a></li>';
            $body .= '</ul>';
        }
        $n = ap_fa_digits($brand['count']); $mc = ap_fa_digits(count($models));
        ap_lp_render(['url' => home_url('/parts-price/' . $brand['short'] . '/'),
            'title' => 'قیمت قطعات ' . $brand['label'] . ' | لیست قیمت لوازم یدکی ' . $brand['fa'] . ' | ' . $site,
            'h1' => 'لیست قیمت قطعات و لوازم یدکی ' . $brand['label'], 'updated' => $today,
            'desc' => 'قیمت روز ' . $n . ' قطعه‌ی یدکی ' . $brand['fa'] . ' برای ' . $mc . ' مدل؛ لوازم یدکی اورجینال و درجه یک ' . $brand['label'] . ' با تطبیق دقیق مدل در ' . $site . '.',
            'kw' => 'قیمت قطعات ' . $brand['fa'] . '، لوازم یدکی ' . $brand['fa'] . '، قطعات ' . $brand['label'],
            'intro' => ['در این صفحه <b>قیمتِ روزِ قطعات و لوازم یدکی ' . esc_html($brand['label']) . '</b> رو برای ' . $mc . ' مدل می‌بینید (در مجموع ' . $n . ' قطعه). مدلِ ماشینتون رو انتخاب کنید تا فهرستِ کاملِ قطعات با قیمت نمایش داده بشه.',
                        'همه‌ی قطعاتِ ' . esc_html($brand['fa']) . ' به‌صورتِ <b>اورجینال</b> و <b>درجه یک</b> عرضه می‌شن؛ برای اطمینان از سازگاری، کد فنی یا شماره‌ی شاسی رو برامون بفرستید.'],
            'faq' => [['قیمت قطعات ' . $brand['fa'] . ' چقدر است؟', 'قیمت هر قطعه در صفحه‌ی مدل مربوط آمده است و از ارزان‌ترین نوع شروع می‌شود؛ قطعات بدون قیمت با تماس استعلام می‌شوند.'],
                      ['قطعات ' . $brand['fa'] . ' را از کجا بخرم؟', 'از فروشگاه اینترنتی ' . $site . ' با ارسال به تهران و سراسر ایران.']],
            'items' => $items, 'shop' => $blink, 'shop_label' => 'خرید قطعات ' . $brand['fa'], 'body' => $body, 'crumbs' => $bc]);
    }
    if (count($seg) !== 3) return;
    $model = null;
    foreach ($models as $m) if ($m['short'] === $seg[2]) { $model = $m; break; }
    if (!$model) return;
    $mterm = get_term($model['id'], 'ap_vehicle');
    $mlink = ($mterm && !is_wp_error($mterm)) ? get_term_link($mterm) : $blink;
    if (is_wp_error($mlink)) $mlink = $blink;
    $rows = ''; $items = []; $cats = []; $min = 0; $max = 0; $last = 0;
    foreach ($model['ids'] as $pid) {
        $pr = ap_lp_price($pid);
        if ($pr > 0) { $min = $min ? min($min, $pr) : $pr; $max = max($max, $pr); }
        $last = max($last, (int) get_post_modified_time('U', true, $pid));
        $t = get_the_title($pid); $u = get_permalink($pid);
        $c = ap_lp_cat($pid); if ($c) $cats[$c->term_id] = $c;
        $items[] = [$t, $u];
        $rows .= '<tr><td><a href="' . esc_url($u) . '">' . esc_html(trim(explode('|', $t)[0])) . '</a></td><td>' . esc_html($c ? $c->name : '') . '</td><td class="pr">' . esc_html(ap_lp_money($pr)) . '</td></tr>';
    }
    $mfa = $model['fa']; $full = trim($brand['fa'] . ' ' . $mfa);
    $body = '<h2>جدول قیمت قطعات ' . esc_html($full) . '</h2><table><thead><tr><th>قطعه</th><th>دسته</th><th>قیمت از</th></tr></thead><tbody>' . $rows . '</tbody></table>';
    if ($cats) {
        $body .= '<h2>قطعات ' . esc_html($full) . ' بر اساس نوع</h2><ul class="grid">';
        foreach ($cats as $c) $body .= '<li><a href="' . esc_url(trailingslashit($mlink) . 'part/' . $c->slug . '/') . '">' . esc_html($c->name . ' ' . $full) . '</a></li>';
        $body .= '</ul>';
    }
    $sib = array_slice(array_filter($models, function ($m) use ($model) { return $m['id'] !== $model['id']; }), 0, 12);
    if ($sib) {
        $body .= '<h2>قیمت قطعات مدل‌های دیگر ' . esc_html($brand['fa']) . '</h2><ul class="grid">';
        foreach ($sib as $m) $body .= '<li><a href="' . esc_url(home_url('/parts-price/' . $brand['short'] . '/' . $m['short'] . '/')) . '">قیمت قطعات ' . esc_html($m['label']) . '</a></li>';
        $body .= '</ul>';
    }
    $n = ap_fa_digits(count($model['ids']));
    $range = $min ? ('از ' . ap_lp_money($min) . ($max > $min ? ' تا ' . ap_lp_money($max) : '')) : '';
    ap_lp_render(['url' => home_url('/parts-price/' . $brand['short'] . '/' . $model['short'] . '/'),
        'title' => 'قیمت قطعات ' . $full . ' | لیست قیمت لوازم یدکی ' . $model['label'] . ' | ' . $site,
        'h1' => 'لیست قیمت قطعات و لوازم یدکی ' . $full, 'updated' => ap_jalali_pretty($last ?: current_time('timestamp')),
        'desc' => 'قیمت روز ' . $n . ' قطعه‌ی ' . $full . ($range ? ' ' . $range : '') . '؛ لنت، چراغ، سپر، فیلتر، کمک فنر و سایر لوازم یدکی ' . $model['label'] . '، اورجینال و درجه یک.',
        'kw' => 'قیمت قطعات ' . $full . '، لوازم یدکی ' . $full . '، قطعات ' . $model['label'],
        'intro' => ['قیمتِ روزِ <b>' . $n . ' قطعه‌ی یدکیِ ' . esc_html($full) . '</b> رو در جدولِ زیر می‌بینید' . ($range ? ' (' . esc_html($range) . ')' : '') . '. قیمتِ هر قطعه از ارزون‌ترین نوع شروع می‌شه و در صفحه‌ی محصول می‌تونید نوعِ اورجینال یا درجه یک و سمتِ چپ/راست رو انتخاب کنید.',
                    'قبل از سفارش، مدل و سالِ ساخت رو با کارتِ ماشین تطبیق بدید یا کد فنیِ قطعه‌ی فعلی رو برامون بفرستید.'],
        'faq' => [['قیمت قطعات ' . $full . ' چقدر است؟', $range ? ('قیمت قطعات ' . $full . ' ' . $range . ' است؛ قیمت دقیق هر قطعه در جدول آمده است.') : 'قیمت هر قطعه با تماس یا واتساپ اعلام می‌شود.'],
                  ['قطعات ' . $full . ' اورجینال است؟', 'هر قطعه در دو نوع اورجینال و درجه یک عرضه می‌شود و نوع آن در صفحه‌ی محصول قابل انتخاب است.'],
                  ['ارسال قطعات ' . $full . ' چطور است؟', 'سفارش‌ها در تهران سریع و به شهرستان‌ها با پست/باربری ارسال می‌شوند.']],
        'items' => $items, 'shop' => $mlink, 'shop_label' => 'خرید قطعات ' . $full, 'body' => $body,
        'crumbs' => array_merge($bc, [['قیمت قطعات ' . $mfa, home_url('/parts-price/' . $brand['short'] . '/' . $model['short'] . '/')]])]);
}, 1);

/* ---------- سایت‌مپ ---------- */
add_filter('rank_math/sitemap/index', function ($xml) {
    return $xml . "\t<sitemap>\n\t\t<loc>" . esc_url(home_url('/landing-sitemap.xml')) . "</loc>\n\t\t<lastmod>" . esc_html(gmdate('c', (int) get_option('ap_lp_mod', time()))) . "</lastmod>\n\t</sitemap>\n";
}, 21);
add_action('save_post_ap_product', function () { update_option('ap_lp_mod', time(), false); });
add_action('wp_loaded', function () {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#/landing-sitemap\.xml$#', $path)) return;
    $mod = gmdate('c', (int) get_option('ap_lp_mod', time()));
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (ap_lp_all_urls() as $u) echo "\t<url>\n\t\t<loc>" . esc_url($u) . "</loc>\n\t\t<lastmod>" . esc_html($mod) . "</lastmod>\n\t</url>\n";
    echo '</urlset>';
    exit;
}, 1);

/* ---------- صفحه‌ی اصلی و صفحه‌های خودرو: عنوان/توضیحِ سئو + هویتِ برند در اسکیمای Rank Math ---------- */
function ap_lp_home_title() { return 'کار تهران (CarTehran) | فروشگاه اینترنتی لوازم یدکی و قطعات خودرو در تهران'; }
function ap_lp_home_desc() { return 'کار تهران (تهران کار، CarTehran)؛ خرید آنلاین لوازم یدکی و قطعات خودروهای چینی و داخلی — چری، ام وی ام، جک، هاوال و ... — اورجینال و درجه یک با قیمت روز و ارسال سریع.'; }
add_filter('rank_math/frontend/title', function ($t) {
    if (function_exists('is_front_page') && is_front_page()) return ap_lp_home_title();
    if (function_exists('is_tax') && is_tax('ap_vehicle') && !(function_exists('ap_part_combo') && ap_part_combo())) {
        $q = get_queried_object();
        if ($q && !is_wp_error($q)) return 'قطعات و لوازم یدکی ' . ap_lp_vehicle_text($q) . ' | قیمت روز | ' . (get_bloginfo('name') ?: 'کار تهران');
    }
    return $t;
}, 100);
add_filter('rank_math/frontend/description', function ($d) {
    if (function_exists('is_front_page') && is_front_page()) return ap_lp_home_desc();
    return $d;
}, 100);
/* «چری Tiggo 7» از زنجیره‌ی خودرو */
function ap_lp_vehicle_text($term) {
    $chain = [];
    foreach (array_reverse(get_ancestors($term->term_id, 'ap_vehicle')) as $aid) { $t = get_term($aid, 'ap_vehicle'); if ($t && !is_wp_error($t)) $chain[] = $t->name; }
    $chain[] = $term->name;
    if (function_exists('ap_vehicle_label')) return ap_vehicle_label($chain);
    return implode(' ', array_map('ap_lp_fa', $chain));
}
/* اسکیمای WebSite/Organization که خودِ Rank Math می‌سازه: اسمِ برند + اسم‌های دیگه + اینستاگرام */
add_filter('rank_math/json_ld', function ($data) {
    if (!is_array($data)) return $data;
    $mine = ap_lp_org_schema();
    foreach ($data as $k => $e) {
        if (!is_array($e) || empty($e['@type'])) continue;
        $types = (array) $e['@type'];
        if (in_array('WebSite', $types, true)) {
            $data[$k]['name'] = $mine[0]['name']; $data[$k]['alternateName'] = $mine[0]['alternateName'];
        } elseif (array_intersect($types, ['Organization', 'AutoPartsStore', 'Store', 'LocalBusiness'])) {
            $data[$k]['alternateName'] = $mine[1]['alternateName'];
            $data[$k]['sameAs'] = array_values(array_unique(array_merge((array) ($e['sameAs'] ?? []), $mine[1]['sameAs'])));
            if (empty($e['telephone'])) $data[$k]['telephone'] = $mine[1]['telephone'];
            if (empty($e['description'])) $data[$k]['description'] = $mine[1]['description'];
        }
    }
    return $data;
}, 99);
