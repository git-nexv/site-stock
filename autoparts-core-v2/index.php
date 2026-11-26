<?php
/**
 * این قالب برای همه‌ی صفحات، همون طراحی اختصاصی سایت (site.html) رو مستقیم نمایش می‌ده
 * و هدر/فوتر پیش‌فرض وردپرس رو دخالت نمی‌ده. اگه درخواست مربوط به صفحه‌ی
 * اختصاصیِ یک محصول (نوع‌پست ap_product) باشه، همون اطلاعات به جاوااسکریپت
 * صفحه تزریق می‌شه تا فقط همون یک محصول (به همراه پیوند یکتای خودش) نمایش داده بشه.
 */
if (!defined('ABSPATH')) { exit; }

/* ---------- صفحه‌ی اختصاصیِ «درباره ما» ----------
   این صفحه کاملاً جدا و مستقله (نه بخشی از site.html)، چون فقط اطلاعات ثابتِ
   تماس/آدرس رو نشون می‌ده و نیازی به کل اپلیکیشنِ فروشگاه نداره. آدرس صفحه:
   yoursite.com/about (پیوند انگلیسی، طبق قانونِ پروژه). */
/* ---------- صفحه‌ی اختصاصیِ هر مقاله ----------
   هر مقاله یک URL کاملاً مستقل و انگلیسی داره (چون اسلاگش طبق فیلترِ
   ap_transliterate_title_to_slug انگلیسی ساخته می‌شه)، مثلاً:
   yoursite.com/article/brake-pad-replacement-guide
   این صفحه، برخلاف صفحه‌ی محصول، به کل اپلیکیشنِ فروشگاه نیازی نداره؛
   یه صفحه‌ی ساده و مستقل با همون ظاهر کلیِ سایته. */
if (function_exists('is_singular') && is_singular('ap_article')) {
    $aid = get_queried_object_id();
    $atitle = function_exists('ap_art_display_title') ? ap_art_display_title(get_the_title($aid)) : get_the_title($aid);
    $aexcerpt = (string) get_post_field('post_excerpt', $aid);
    $aimg = get_the_post_thumbnail_url($aid, 'large');
    $acontent = apply_filters('the_content', get_post_field('post_content', $aid));
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <script>(function(){try{var t=localStorage.getItem('ap_theme'); if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
        <style>html{background:#f7f6fb;}html[data-theme="dark"]{background:#08080d;}</style>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php echo function_exists('ap_favicon_tags') ? ap_favicon_tags() : ''; ?>
        <title><?php echo esc_html($atitle); ?> | وبلاگ <?php bloginfo('name'); ?></title>
        <meta name="description" content="<?php echo esc_attr($aexcerpt !== '' ? $aexcerpt : wp_trim_words(wp_strip_all_tags($acontent), 30)); ?>">
        <link rel="canonical" href="<?php echo esc_url(get_permalink($aid)); ?>">
        <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large">
        <meta property="og:type" content="article"><meta property="og:title" content="<?php echo esc_attr($atitle); ?>"><meta property="og:url" content="<?php echo esc_url(get_permalink($aid)); ?>">
        <script type="application/ld+json"><?php echo wp_json_encode(['@context' => 'https://schema.org', '@graph' => [
            ['@type' => 'Article', 'headline' => $atitle, 'description' => $aexcerpt, 'datePublished' => get_the_date('c', $aid), 'dateModified' => get_the_modified_date('c', $aid),
             'mainEntityOfPage' => get_permalink($aid), 'inLanguage' => 'fa-IR', 'author' => ['@type' => 'Organization', 'name' => get_bloginfo('name')], 'publisher' => ['@id' => home_url('/#organization')]],
            ['@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => get_bloginfo('name'), 'item' => home_url('/')],
                ['@type' => 'ListItem', 'position' => 2, 'name' => 'مقالات', 'item' => home_url('/blog/')],
                ['@type' => 'ListItem', 'position' => 3, 'name' => $atitle, 'item' => get_permalink($aid)]]]]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap"></noscript>
        <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style.css'); ?>">
        <style>
            body{font-family:'Vazirmatn',Tahoma,sans-serif; background:#f7f6fb; margin:0; padding:30px 16px 60px; color:#201f2b;}
            .article-page{max-width:720px; margin:0 auto;}
            .article-page .back{display:inline-block; margin-bottom:18px; font-size:13px; color:#7a7788; text-decoration:none;}
            .article-page .cover{width:100%; border-radius:18px; margin-bottom:22px; max-height:360px; object-fit:cover;}
            .article-page h1{font-size:24px; line-height:1.6; margin-bottom:18px;}
            .article-page .body-content{background:#fff; border-radius:18px; padding:26px 22px; font-size:15px; line-height:2.1; color:#3a3846; box-shadow:0 14px 34px rgba(0,0,0,.06);}
            .article-page .body-content img{max-width:100%; border-radius:12px; margin:14px 0;}
            .article-page .body-content p{margin-bottom:16px;}
            /* حالت شب: همون کلیدی که کلیدِ روشن/تاریکِ سایت ذخیره می‌کنه (ap_theme) */
            html[data-theme="dark"] body{background:#08080d; color:#ececf4;}
            html[data-theme="dark"] .article-page .back{color:#9a97ad;}
            html[data-theme="dark"] .article-page .body-content{background:#15141c; color:#d5d3e0; box-shadow:0 14px 34px rgba(0,0,0,.5);}
            /* ورودِ نرمِ صفحه، هماهنگ با بقیه‌ی سایت */
            @keyframes apEnter{from{opacity:.35;} to{opacity:1;}} /* فقط opacity؛ transform روی body باعث می‌شد نوارِ پایین و پس‌زمینه پین نمونن */
            body{animation:apEnter .38s cubic-bezier(.16,1,.3,1) both;}
            @media (prefers-reduced-motion: reduce){ body{animation:none;} }
            <?php echo function_exists('ap_art_css') ? ap_art_css() : ''; ?>
        </style>
    </head>
    <body>
        <div class="article-page">
            <a class="back" onclick="try{if(document.referrer&&new URL(document.referrer).origin===location.origin&&history.length>1){history.back();return false;}}catch(e){}" href="<?php echo esc_url(home_url('/#articles')); ?>">← بازگشت به مقالات</a>
            <?php if ($aimg): ?><img class="cover" src="<?php echo esc_url($aimg); ?>" alt="<?php echo esc_attr($atitle); ?>"><?php endif; ?>
            <h1><?php echo esc_html($atitle); ?></h1>
            <div class="body-content"><?php echo $acontent; ?></div>
            <?php /* محصولاتی که به همین مقاله مربوطن (برند/مدل + نوعِ قطعه) و مقاله‌های مرتبط */
            if (function_exists('ap_art_related_html')) { echo ap_art_related_html($aid); echo ap_art_more_html($aid); } ?>
        </div>
        <?php echo ap_static_dock_html(); ?>
    </body>
    </html>
    <?php
    exit;
}

/* ---------- صفحه‌ی «همه‌ی مقالات» ----------
   آدرس: yoursite.com/blog — همه‌ی مقالاتِ منتشرشده رو به‌شکلِ کارتی نشون می‌ده؛
   هر کارت به پیوندِ یکتای (انگلیسیِ) خودِ همون مقاله می‌ره. */
if (function_exists('is_post_type_archive') && is_post_type_archive('ap_article')) {
    /* صفحه‌بندی (با زیاد شدنِ مقاله‌ها): /blog/?pg=2 */
    $ap_pg = max(1, (int) ($_GET['pg'] ?? 1));
    $ap_total = (int) (wp_count_posts('ap_article')->publish ?? 0);
    $ap_pages = max(1, (int) ceil($ap_total / 30));
    $arts = get_posts(['post_type' => 'ap_article', 'posts_per_page' => 30, 'paged' => $ap_pg, 'post_status' => 'publish']);
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <script>(function(){try{var t=localStorage.getItem('ap_theme'); if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
        <style>html{background:#f7f6fb;}html[data-theme="dark"]{background:#08080d;}</style>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php echo function_exists('ap_favicon_tags') ? ap_favicon_tags() : ''; ?>
        <title>مقالات آموزشی | <?php bloginfo('name'); ?></title>
        <meta name="description" content="مجموعه مقالات آموزشی درباره قطعات یدکی، تعمیر و نگهداری خودرو.">
        <link rel="canonical" href="<?php echo esc_url(get_post_type_archive_link('ap_article')); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap"></noscript>
        <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style.css'); ?>">
        <style>
            body{font-family:'Vazirmatn',Tahoma,sans-serif; background:#f7f6fb; margin:0; padding:30px 16px 60px; color:#201f2b;}
            .blog-wrap{max-width:960px; margin:0 auto;}
            .blog-wrap .back{display:inline-block; margin-bottom:18px; font-size:13px; color:#7a7788; text-decoration:none;}
            .blog-wrap h1{font-size:22px; margin-bottom:22px;}
            .blog-grid{display:grid; grid-template-columns:repeat(3,1fr); gap:18px;}
            @media (max-width:760px){ .blog-grid{grid-template-columns:1fr;} }
            .blog-card{display:block; background:#fff; border-radius:16px; overflow:hidden; text-decoration:none; color:inherit; box-shadow:0 12px 26px rgba(0,0,0,.06);}
            .blog-card img{width:100%; aspect-ratio:16/9; object-fit:cover;}
            .blog-card .b{padding:14px 16px 18px;}
            .blog-card h3{font-size:14.5px; line-height:1.6; margin-bottom:6px;}
            .blog-card p{font-size:12.5px; color:#7a7788; line-height:1.8; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;}
            /* حالت شب */
            html[data-theme="dark"] body{background:#08080d; color:#ececf4;}
            html[data-theme="dark"] .blog-wrap .back{color:#9a97ad;}
            html[data-theme="dark"] .blog-card{background:#15141c; box-shadow:0 12px 26px rgba(0,0,0,.5);}
            html[data-theme="dark"] .blog-card p{color:#9a97ad;}
            /* ورودِ نرمِ صفحه، هماهنگ با بقیه‌ی سایت */
            @keyframes apEnter{from{opacity:.35;} to{opacity:1;}} /* فقط opacity؛ transform روی body باعث می‌شد نوارِ پایین و پس‌زمینه پین نمونن */
            body{animation:apEnter .38s cubic-bezier(.16,1,.3,1) both;}
            @media (prefers-reduced-motion: reduce){ body{animation:none;} }
        </style>
    </head>
    <body>
        <div class="blog-wrap">
            <a class="back" onclick="try{if(document.referrer&&new URL(document.referrer).origin===location.origin&&history.length>1){history.back();return false;}}catch(e){}" href="<?php echo esc_url(home_url('/')); ?>">← بازگشت به فروشگاه</a>
            <h1>مقالات آموزشی <?php bloginfo('name'); ?></h1>
            <div class="blog-grid">
                <?php foreach ($arts as $a):
                    $img = get_the_post_thumbnail_url($a->ID, 'medium');
                    $excerpt = $a->post_excerpt ?: wp_trim_words(wp_strip_all_tags($a->post_content), 20);
                ?>
                <a class="blog-card" href="<?php echo esc_url(get_permalink($a)); ?>">
                    <?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title($a)); ?>"><?php endif; ?>
                    <div class="b">
                        <h3><?php echo esc_html(function_exists('ap_art_display_title') ? ap_art_display_title(get_the_title($a)) : get_the_title($a)); ?></h3>
                        <p><?php echo esc_html($excerpt); ?></p>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if ($ap_pages > 1): ?><nav style="display:flex;flex-wrap:wrap;gap:6px;justify-content:center;margin-top:22px">
                <?php for ($i = 1; $i <= $ap_pages; $i++): ?><a href="<?php echo esc_url($i > 1 ? add_query_arg('pg', $i, home_url('/blog/')) : home_url('/blog/')); ?>" style="min-width:34px;text-align:center;padding:6px 10px;border-radius:10px;text-decoration:none;<?php echo $i === $ap_pg ? 'background:#B3122E;color:#fff' : 'background:#fff;color:#201f2b'; ?>"><?php echo esc_html(ap_fa_digits($i)); ?></a><?php endfor; ?>
            </nav><?php endif; ?>
        </div>
        <?php echo ap_static_dock_html(); ?>
    </body>
    </html>
    <?php
    exit;
}

/* ---------- صفحه‌ی اختصاصیِ «قوانین و مقررات» ----------
   yoursite.com/terms  (پیوند انگلیسی، طبق قانونِ پروژه)
   مهم‌ترین بندش قانونِ قیمته: اگه قیمتِ یه کالا به‌روز نشده باشه، فروشگاه
   می‌تونه سفارش رو لغو کنه. این صفحه مستقل رندر می‌شه (مثل «درباره ما») تا
   اپلیکیشنِ اصلیِ سایت لود نشه و سریع باز بشه. */
if (function_exists('is_page') && is_page('terms')) {
    $phone = get_theme_mod('ap_contact_phone', '09109220001');
    $tel_href = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $phone);
    $rules = [
        [
            'قیمت‌ها و لغو سفارش',
            'قیمت قطعات یدکی وابسته به نرخ روز ارز و بازار است و ممکن است روزانه تغییر کند. کنار قیمت هر کالا، تاریخ آخرین به‌روزرسانی قیمت نوشته شده است. <b>اگر قیمت یک کالا به‌روزرسانی نشده باشد، امکان لغو سفارش از سمت ما وجود دارد؛</b> در این حالت قبل از هر اقدامی با شما تماس می‌گیریم و اگر مبلغی پرداخت کرده باشید، تمام آن بدون کسر هیچ هزینه‌ای برگردانده می‌شود.',
        ],
        [
            'ثبت و تأیید سفارش',
            'ثبت سفارش در سایت به معنی نهایی‌شدن آن نیست. هر سفارش پس از بررسی موجودی انبار و قیمت روز توسط پشتیبانی تأیید می‌شود. اگر کالا موجود نباشد، سفارش لغو و مبلغ پرداختی به‌طور کامل عودت داده می‌شود.',
        ],
        [
            'پرداخت و رسید',
            'پرداخت به‌صورت کارت‌به‌کارت انجام می‌شود و بارگذاری تصویر رسید در پنل کاربری الزامی است. سفارشی که رسید معتبر نداشته باشد پردازش نمی‌شود. مبلغ واریزی باید دقیقاً برابر مبلغ نهایی فاکتور باشد.',
        ],
        [
            'ارسال و هزینه‌ی آن',
            'ارسال با پست پیشتاز (هزینه‌ی ثابت، پرداخت هم‌زمان با سفارش) یا با تیپاکس، ماهکس و پیک (پس‌کرایه، هزینه هنگام تحویل از شما گرفته می‌شود) انجام می‌شود. زمان ارسال از لحظه‌ی تأیید پرداخت حساب می‌شود، نه از لحظه‌ی ثبت سفارش. تأخیرهای شرکت حمل‌ونقل خارج از کنترل ماست.',
        ],
        [
            'اصالت کالا و گارانتی',
            'همه‌ی قطعات عرضه‌شده اورجینال هستند و سالم بودنشان پیش از ارسال بررسی می‌شود. گارانتی قطعات، گارانتی سلامت کالا هنگام تحویل است و شامل خرابی‌های ناشی از نصب نادرست، تصادف، ضربه یا اتصال کوتاه نمی‌شود.',
        ],
        [
            'مرجوعی و تعویض',
            'اگر کالا ایراد فنی داشته باشد یا با سفارش شما مطابقت نداشته باشد، حداکثر تا ۲۴ ساعت پس از تحویل با پشتیبانی تماس بگیرید تا تعویض یا عودت انجام شود. شرط پذیرش مرجوعی، دست‌نخورده بودن پک و نداشتن آثار نصب یا آسیب فیزیکی است.',
        ],
        [
            'مسئولیت نصب',
            'قطعات این فروشگاه برای نصب توسط تعمیرکار متخصص عرضه می‌شوند. مسئولیت آسیب‌هایی که در جریان نصب به قطعه یا خودرو وارد می‌شود بر عهده‌ی خریدار یا تعمیرکار است.',
        ],
        [
            'اطلاعات کاربران',
            'شماره تماس و نشانی شما فقط برای پردازش و ارسال سفارش استفاده می‌شود و در اختیار هیچ شخص یا شرکت دیگری قرار نمی‌گیرد.',
        ],
        [
            'به‌روزرسانی قوانین',
            'این قوانین ممکن است به‌روزرسانی شوند. قوانین معتبر برای هر سفارش، همان چیزی است که در لحظه‌ی ثبت آن سفارش در این صفحه منتشر شده است.',
        ],
    ];
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <script>(function(){try{var t=localStorage.getItem('ap_theme'); if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
        <style>html{background:#f7f6fb;}html[data-theme="dark"]{background:#08080d;}</style>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php echo function_exists('ap_favicon_tags') ? ap_favicon_tags() : ''; ?>
        <title>قوانین و مقررات | <?php bloginfo('name'); ?></title>
        <meta name="description" content="قوانین خرید از فروشگاه؛ شرایط قیمت‌گذاری و لغو سفارش، پرداخت، ارسال، گارانتی و مرجوعی قطعات یدکی خودرو.">
        <link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
        <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap"></noscript>
        <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style.css'); ?>">
        <style>
            body{font-family:'Vazirmatn',Tahoma,sans-serif; background:#f7f6fb; margin:0; padding:40px 16px 60px; color:#201f2b;}
            .terms-box{max-width:760px; margin:0 auto; background:#fff; border-radius:24px; padding:38px 30px 30px; box-shadow:0 20px 50px rgba(0,0,0,.08);}
            .terms-logo{width:52px; height:52px; border-radius:15px; margin:0 auto 16px; display:block; object-fit:cover; box-shadow:0 10px 24px rgba(20,10,60,.18);}
            .terms-box h1{font-size:23px; margin:0 0 6px; text-align:center;}
            .terms-box .tagline{color:#7a7788; font-size:13px; margin:0 0 30px; text-align:center;}
            .rule{padding:20px 0; border-top:1px solid #eee;}
            .rule:first-of-type{border-top:none; padding-top:4px;}
            .rule h2{display:flex; align-items:center; gap:10px; font-size:15.5px; margin:0 0 8px;}
            .rule h2 i{flex:none; width:26px; height:26px; border-radius:9px; background:#fbeff1; color:#B3122E;
                font-size:12.5px; font-weight:800; font-style:normal; display:grid; place-items:center;}
            .rule p{margin:0; font-size:14px; line-height:2.05; color:#4c4a5c;}
            .rule p b{color:#201f2b;}
            .terms-note{margin-top:26px; padding:16px 18px; border-radius:14px; background:#fbeff1; color:#7A0C1F; font-size:13.5px; line-height:1.95;}
            .terms-note a{color:#9f1223; font-weight:800; text-decoration:none;}
            .terms-back{display:inline-block; margin-top:22px; font-size:12.5px; color:#7a7788; text-decoration:none;}
            /* حالت شب */
            html[data-theme="dark"] body{background:#08080d; color:#ececf4;}
            html[data-theme="dark"] .terms-box{background:#15141c; box-shadow:0 20px 50px rgba(0,0,0,.5);}
            html[data-theme="dark"] .terms-box .tagline{color:#9a97ad;}
            html[data-theme="dark"] .rule{border-top-color:#26242f;}
            html[data-theme="dark"] .rule p{color:#b9b6c9;}
            html[data-theme="dark"] .rule p b{color:#ececf4;}
            html[data-theme="dark"] .rule h2 i{background:#2a1418; color:#ff9aa8;}
            html[data-theme="dark"] .terms-note{background:#2a1418; color:#ffc9d1;}
            html[data-theme="dark"] .terms-note a{color:#ff9aa8;}
            @keyframes apEnter{from{opacity:.35;} to{opacity:1;}} /* فقط opacity؛ transform روی body باعث می‌شد نوارِ پایین و پس‌زمینه پین نمونن */
            body{animation:apEnter .38s cubic-bezier(.16,1,.3,1) both;}
            @media (prefers-reduced-motion: reduce){ body{animation:none;} }
        </style>
    </head>
    <body>
        <div class="terms-box">
            <img class="terms-logo" src="<?php echo esc_url(ap_icon_url('icon-192.png')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" width="52" height="52">
            <h1>قوانین و مقررات</h1>
            <p class="tagline">شرایط خرید از فروشگاه <?php bloginfo('name'); ?> — لطفاً پیش از ثبت سفارش بخوانید</p>
            <?php $i = 0; foreach ($rules as $rule) : $i++; ?>
                <div class="rule">
                    <h2><i><?php echo esc_html(ap_fa_digits($i)); ?></i><?php echo esc_html($rule[0]); ?></h2>
                    <p><?php echo wp_kses($rule[1], ['b' => [], 'br' => []]); ?></p>
                </div>
            <?php endforeach; ?>
            <div class="terms-note">
                هر سؤالی درباره‌ی این قوانین داشتید، قبل از خرید با ما تماس بگیرید:
                <a href="<?php echo esc_attr($tel_href); ?>" dir="ltr"><?php echo esc_html($phone); ?></a>
            </div>
            <a class="terms-back" onclick="try{if(document.referrer&&new URL(document.referrer).origin===location.origin&&history.length>1){history.back();return false;}}catch(e){}" href="<?php echo esc_url(home_url('/')); ?>">← بازگشت به فروشگاه</a>
        </div>
        <?php echo ap_static_dock_html(); ?>
    </body>
    </html>
    <?php
    exit;
}

if (function_exists('is_page') && is_page('about')) {
    $phone = get_theme_mod('ap_contact_phone', '09109220001');
    $wa = get_theme_mod('ap_contact_whatsapp', '09109220001');
    $ig = get_theme_mod('ap_instagram_url', 'https://www.instagram.com/auto.nexv.ir');
    $tel_href = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $phone);
    $wa_href = 'https://wa.me/98' . ltrim(preg_replace('/[^0-9]/', '', (string) $wa), '0');
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="utf-8">
        <script>(function(){try{var t=localStorage.getItem('ap_theme'); if(t==='dark'){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
        <style>html{background:#f7f6fb;}html[data-theme="dark"]{background:#08080d;}</style>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php echo function_exists('ap_favicon_tags') ? ap_favicon_tags() : ''; ?>
        <title>درباره ما | <?php bloginfo('name'); ?></title>
        <meta name="description" content="اطلاعات تماس و آدرس فروشگاه؛ فروشگاه تخصصی قطعات یدکی خودرو، اورجینال و آفتر مارکت.">
        <link rel="canonical" href="<?php echo esc_url(get_permalink()); ?>">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap"></noscript>
        <link rel="stylesheet" href="<?php echo esc_url(get_template_directory_uri() . '/style.css'); ?>">
        <style>
            body{font-family:'Vazirmatn',Tahoma,sans-serif; background:#f7f6fb; margin:0; padding:40px 16px; color:#201f2b;}
            .about-box{max-width:520px; margin:0 auto; background:#fff; border-radius:20px; padding:36px 28px; box-shadow:0 20px 50px rgba(0,0,0,.08); text-align:center;}
            .about-logo{width:56px; height:56px; border-radius:16px; margin:0 auto 18px; display:block; object-fit:cover; box-shadow:0 10px 24px rgba(20,10,60,.18);}
            .about-box h1{font-size:22px; margin-bottom:6px;}
            .about-box .tagline{color:#7a7788; font-size:13.5px; margin-bottom:26px;}
            .about-row{display:flex; align-items:flex-start; gap:10px; text-align:right; padding:14px 0; border-top:1px solid #eee;}
            .about-row:first-of-type{border-top:none;}
            .about-row b{display:block; font-size:12.5px; color:#7a7788; margin-bottom:3px;}
            .about-row span, .about-row a{font-size:14.5px; color:#201f2b; text-decoration:none;}
            /* حالت شب */
            html[data-theme="dark"] body{background:#08080d; color:#ececf4;}
            html[data-theme="dark"] .about-box{background:#15141c; box-shadow:0 20px 50px rgba(0,0,0,.5);}
            html[data-theme="dark"] .about-box .tagline, html[data-theme="dark"] .about-row b{color:#9a97ad;}
            html[data-theme="dark"] .about-row{border-top-color:#26242f;}
            html[data-theme="dark"] .about-row span, html[data-theme="dark"] .about-row a{color:#ececf4;}
            .about-links{display:flex; gap:10px; justify-content:center; margin-top:24px;}
            .about-links a{padding:10px 18px; border-radius:10px; background:#fbeff1; color:#B3122E; font-size:13.5px; text-decoration:none; font-weight:700;}
            .about-back{display:inline-block; margin-top:22px; font-size:12.5px; color:#7a7788; text-decoration:none;}
            /* ورودِ نرمِ صفحه، هماهنگ با بقیه‌ی سایت */
            @keyframes apEnter{from{opacity:.35;} to{opacity:1;}} /* فقط opacity؛ transform روی body باعث می‌شد نوارِ پایین و پس‌زمینه پین نمونن */
            body{animation:apEnter .38s cubic-bezier(.16,1,.3,1) both;}
            @media (prefers-reduced-motion: reduce){ body{animation:none;} }
        </style>
    </head>
    <body>
        <div class="about-box">
            <img class="about-logo" src="<?php echo esc_url(ap_icon_url('icon-192.png')); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" width="56" height="56">
            <h1><?php bloginfo('name'); ?></h1>
            <p class="tagline">فروشگاه تخصصی قطعات یدکی خودرو</p>

            <div class="about-row">
                <div>
                    <b>آدرس</b>
                    <span><?php echo esc_html(function_exists('ap_sender_info') ? ap_sender_info()['address'] : get_theme_mod('ap_contact_address', 'تهران، پاکدشت، انتهای بلوار سپاه، پلاک ۱۰، طبقه ۱')); ?></span>
                </div>
            </div>
            <div class="about-row">
                <div>
                    <b>شماره تماس</b>
                    <a href="<?php echo esc_attr($tel_href); ?>" dir="ltr"><?php echo esc_html($phone); ?></a>
                </div>
            </div>

            <div class="about-links">
                <a href="<?php echo esc_url($wa_href); ?>" target="_blank" rel="noopener">واتساپ</a>
                <?php if ($ig): ?><a href="<?php echo esc_url($ig); ?>" target="_blank" rel="noopener">اینستاگرام</a><?php endif; ?>
            </div>

            <a class="about-back" onclick="try{if(document.referrer&&new URL(document.referrer).origin===location.origin&&history.length>1){history.back();return false;}}catch(e){}" href="<?php echo esc_url(home_url('/')); ?>">← بازگشت به فروشگاه</a>
        </div>
        <?php echo ap_static_dock_html(); ?>
    </body>
    </html>
    <?php
    exit;
}



/* نامِ خوانای خودرو از زنجیره‌ی برند/مدل/سال؛ اگه نامِ مدل خودش شاملِ نامِ برند
   باشه (مثل «پژو» و «پژو ۲۰۶»)، تکرار حذف می‌شه. */
if (!function_exists('ap_vehicle_label')) {
    function ap_vehicle_label(array $parts) {
        $out = [];
        foreach ($parts as $raw) {
            $part = trim((string) $raw);
            if ($part === '') { continue; }
            if ($out) {
                $last = $out[count($out) - 1];
                if (strpos($part, $last) === 0 || strpos(implode(' ', $out), $part) !== false) {
                    $out[count($out) - 1] = $part;
                    continue;
                }
            }
            $out[] = $part;
        }
        return implode(' ', $out);
    }
}

$html    = file_get_contents(__DIR__ . '/site.html');
/* نامِ فروشگاه (از پیشخوان ← تنظیمات ← عنوان سایت) توی همه‌ی جاهای طراحی
   (لوگو، فوتر، تیترها و پنجره‌ی ورود) جایگزینِ نگه‌دارنده‌ی {{SITE_NAME}} می‌شه؛
   این‌طوری با عوض‌کردنِ عنوان سایت، همه‌جا خودکار عوض می‌شه. */
$ap_site_name = get_bloginfo('name');
if (trim((string) $ap_site_name) === '') { $ap_site_name = 'قطعات یدکی'; }
$html = str_replace('{{SITE_NAME}}', esc_html($ap_site_name), $html);
$html = str_replace('{{LOGO_URL}}', esc_url(get_template_directory_uri() . '/assets/logo.png?v=' . (defined('AP_ICON_VER') ? AP_ICON_VER : '1')), $html);
/* آیکونِ تب و آیکونِ صفحه‌ی گوشی (همه‌ی اندازه‌ها؛ نگاه کن به ap_favicon_tags) */
$ap_icon_tags = ap_favicon_tags();
/* PWA: نصب روی صفحه‌ی اصلیِ گوشی (برای اعلانِ سفارشِ جدید روی آیفون لازمه) */
$ap_icon_tags .= '<link rel="manifest" href="' . esc_url(get_template_directory_uri() . '/api.php?action=manifest') . '">'
    . '<meta name="theme-color" content="#B3122E"><meta name="apple-mobile-web-app-capable" content="yes">'
    . '<meta name="mobile-web-app-capable" content="yes"><meta name="apple-mobile-web-app-title" content="' . esc_attr($ap_site_name) . '">'
    . '<meta name="apple-mobile-web-app-status-bar-style" content="default">';
$html = preg_replace('#</head>#i', $ap_icon_tags . '</head>', $html, 1);
$api_url = esc_url(get_template_directory_uri() . '/api.php');
$home_url = esc_url_raw(home_url('/'));

/* ---------- نوار بالای سایت: نوار تماس (شماره تماس + واتساپ) ----------
   از پیشخوان ← شخصی‌سازی ← «نوار بالای سایت» قابل تنظیمه (شماره‌ها و رنگ).
   نوار بنفش و هدر با هم داخل یک wrapper مشترک (#apStickyTop) قرار می‌گیرن
   و کل اون wrapper با هم sticky می‌شه؛ این‌جوری همیشه دقیقاً بهم چسبیده‌ن،
   بدون نیاز به هیچ اندازه‌گیری جاوااسکریپتی یا مقدار پیکسلِ حدسی. */
$topbar_enabled = get_theme_mod('ap_topbar_enabled', true);
$topbar_bg      = get_theme_mod('ap_topbar_bg', '#B3122E');
$contact_phone  = get_theme_mod('ap_contact_phone', '09109220001');
$contact_wa     = get_theme_mod('ap_contact_whatsapp', '09109220001');
$instagram_url  = get_theme_mod('ap_instagram_url', 'https://www.instagram.com/auto.nexv.ir');

$topbar_html  = '';
/* بخش ۱ هدر (نوار بنفش): همون‌جای قبلی می‌مونه، فقط ۱ میلی‌متر از بالا/چپ/راستِ لبه‌ی
   سایت فاصله داره و لبه‌هاش کامل نیم‌دایره‌ست. wrapper شفافه تا دورِ کپسول دیده بشه. */
$topbar_style = '#apStickyTop{position:sticky;top:0;z-index:80;padding:12px 10px 0;background:transparent;pointer-events:none;}#apStickyTop > *{pointer-events:auto;}';
if ($topbar_enabled && (trim((string) $contact_phone) !== '' || trim((string) $contact_wa) !== '' || trim((string) $instagram_url) !== '')) {
    $tel_href = 'tel:' . preg_replace('/[^0-9+]/', '', (string) $contact_phone);

    $wa_digits = preg_replace('/[^0-9]/', '', (string) $contact_wa);
    if (substr($wa_digits, 0, 1) === '0') { $wa_digits = '98' . substr($wa_digits, 1); }
    $wa_href = 'https://wa.me/' . $wa_digits;

    $phone_ic = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.68 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.32 1.85.55 2.81.68A2 2 0 0 1 22 16.92z"/></svg>';
    $wa_ic    = '<svg viewBox="0 0 32 32" fill="currentColor"><path d="M16.04 3C9.4 3 4 8.4 4 15.04c0 2.23.6 4.32 1.65 6.12L4 29l8.02-1.6a12 12 0 0 0 4.02.7c6.64 0 12.04-5.4 12.04-12.06C28.08 8.4 22.68 3 16.04 3zm0 21.86c-1.9 0-3.68-.5-5.23-1.4l-.37-.22-4.76.95.95-4.64-.24-.38a9.86 9.86 0 0 1-1.5-5.13c0-5.46 4.44-9.9 9.9-9.9a9.86 9.86 0 0 1 6.99 2.9 9.83 9.83 0 0 1 2.9 6.99c0 5.46-4.44 9.83-9.64 9.83zm5.42-7.4c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.07-.15-.67-1.6-.91-2.2-.24-.57-.49-.5-.67-.5h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.01-1.04 2.47s1.06 2.86 1.21 3.06c.15.2 2.09 3.2 5.08 4.48.7.3 1.26.49 1.68.62.7.22 1.35.19 1.85.12.57-.09 1.75-.71 2-1.4.24-.68.24-1.27.17-1.4-.08-.13-.28-.2-.58-.35z"/></svg>';
    $ig_ic    = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/></svg>';

    $ig_link = '';
    if (trim((string) $instagram_url) !== '') {
        $ig_link = '<a class="ap-topbar-item" href="' . esc_url($instagram_url) . '" target="_blank" rel="noopener">' . $ig_ic . '<span>اینستاگرام</span></a>';
    }

    $topbar_html = '<div id="apTopbar"><div class="ap-topbar-inner">'
        . '<a class="ap-topbar-item" href="' . esc_url($tel_href) . '">' . $phone_ic . '<span><span class="ap-tb-label">تماس با ما: </span>' . esc_html($contact_phone) . '</span></a>'
        . '<a class="ap-topbar-item" href="' . esc_url($wa_href) . '" target="_blank" rel="noopener">' . $wa_ic . '<span>پیام در واتساپ</span></a>'
        . $ig_link
        . '</div></div>';
    /* دکمه‌ی گردِ زبان (FA/EN) کنارِ نوار؛ هم‌قد و هم‌رنگِ خودِ نوار */
    $topbar_html = '<div id="apTopRow">' . $topbar_html
        . '<button type="button" id="apLangBtn" class="ap-lang-btn" onclick="apToggleLang()" aria-label="English">EN</button></div>';

    $topbar_style .= '
        #apTopbar{
            /* هم‌خانواده با نوار داینامیکِ پایین: همون فاصله از لبه‌ها، همون عرضِ حداکثر و گردیِ کامل */
            width:min(560px, calc(100vw - 72px));margin:0;min-height:42px;display:flex;align-items:center;
            position:relative;overflow:hidden;border-radius:999px;font-family:"Vazirmatn",sans-serif;
            background:color-mix(in srgb, ' . esc_attr($topbar_bg) . ' 86%, transparent);
            -webkit-backdrop-filter:blur(18px) saturate(180%);backdrop-filter:blur(18px) saturate(180%);
            border:1px solid rgba(255,255,255,.28);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.45), inset 0 -1px 0 rgba(0,0,0,.08), 0 8px 24px color-mix(in srgb, ' . esc_attr($topbar_bg) . ' 30%, transparent);
        }
        #apTopRow{display:flex;align-items:center;justify-content:center;gap:12px;width:100%;}
        #apLangBtn{
            flex:0 0 42px;width:42px;height:42px;border-radius:50%;cursor:pointer;padding:0;position:relative;overflow:hidden;
            font-family:"Vazirmatn",sans-serif;font-size:12.5px;font-weight:800;letter-spacing:.5px;color:#fff;
            background:color-mix(in srgb, ' . esc_attr($topbar_bg) . ' 86%, transparent);
            -webkit-backdrop-filter:blur(18px) saturate(180%);backdrop-filter:blur(18px) saturate(180%);
            border:1px solid rgba(255,255,255,.28);
            box-shadow:inset 0 1px 0 rgba(255,255,255,.45), inset 0 -1px 0 rgba(0,0,0,.08), 0 8px 24px color-mix(in srgb, ' . esc_attr($topbar_bg) . ' 30%, transparent);
            transition:transform .2s ease;
        }
        #apLangBtn::before{content:"";position:absolute;inset:0;border-radius:inherit;pointer-events:none;
            background:linear-gradient(180deg, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 55%);}
        #apLangBtn:hover{transform:scale(1.06);}
        #apLangBtn:active{transform:scale(.95);}
        #apTopbar::before{content:"";position:absolute;inset:0;border-radius:inherit;pointer-events:none;
            background:linear-gradient(180deg, rgba(255,255,255,.22) 0%, rgba(255,255,255,0) 55%);}
        .ap-topbar-inner{position:relative;width:100%;display:flex;align-items:center;justify-content:center;gap:20px;padding:6px 18px;flex-wrap:nowrap;white-space:nowrap;}
        .ap-topbar-item{display:inline-flex;align-items:center;gap:6px;color:#fff;font-size:12.5px;font-weight:500;opacity:.97;transition:opacity .2s ease;}
        .ap-topbar-item:hover{opacity:1;text-decoration:underline;}
        .ap-topbar-item svg{width:14px;height:14px;flex-shrink:0;}
        @media (max-width:560px){
            #apTopRow{gap:8px;}
            #apTopbar{width:calc(100vw - 70px);}
            #apLangBtn{flex-basis:38px;width:38px;height:38px;font-size:11.5px;}
            .ap-topbar-inner{gap:14px;padding:5px 14px;}
            .ap-topbar-item{font-size:11.5px;gap:5px;}
            .ap-tb-label{display:none;}
            .ap-topbar-item svg{width:13px;height:13px;}
        }';
}
$topbar_style = '<style>' . $topbar_style . '</style>';

/* ---------- حالت صفحه‌ی اختصاصیِ یک محصول ---------- */
$single_product = null;
$cat_seo_slug = null;
$vehicle_slug = null;
$vehicle_label = '';
$cat_seo_desc = '';
if (function_exists('is_singular') && is_singular('ap_product')) {
    $pid = get_queried_object_id();
    if ($pid) {
        $terms    = get_the_terms($pid, 'ap_cat');
        $cat_slug = (!empty($terms) && !is_wp_error($terms)) ? $terms[0]->slug : '';
        $price    = (int) get_post_meta($pid, '_ap_price', true);
        $priceUpdated = function_exists('ap_price_updated_label') ? ap_price_updated_label($pid) : '';
        $spec     = get_post_meta($pid, '_ap_spec', true);
        $color    = get_post_meta($pid, '_ap_color', true);
        $ic       = get_post_meta($pid, '_ap_icon', true);
        $badge    = get_post_meta($pid, '_ap_badge', true);

        $img = get_the_post_thumbnail_url($pid, 'large');
        $gallery = function_exists('ap_gallery_urls') ? ap_gallery_urls($pid, $img) : ($img ? [$img] : []);

        /* نظرسنجی/نظرات مشتری همیشه زیر هر محصولی نمایش داده می‌شه؛ حتی برای
           محصولاتی که قبل از فعال‌شدن این قابلیت ساخته شدن. */
        if (get_post_field('comment_status', $pid) !== 'open') {
            wp_update_post(['ID' => $pid, 'comment_status' => 'open']);
        }

        ob_start();
        $approved = get_comments(['post_id' => $pid, 'status' => 'approve', 'order' => 'DESC']);
        $reviews_count = is_array($approved) ? count($approved) : 0;
        if ($approved) {
            echo '<ol class="ap-review-list">';
            wp_list_comments(['style' => 'ol', 'short_ping' => true, 'avatar_size' => 42], $approved);
            echo '</ol>';
        }
        $reviews_list_html = ob_get_clean();

        ob_start();
        $ap_force_guest_fields = function () {
            return '<p class="comment-form-author" style="display:none;"><input id="author" name="author" type="text" value="" size="30"></p>'
                 . '<p class="comment-form-email" style="display:none;"><input id="email" name="email" type="text" value="" size="30"></p>';
        };
        add_filter('comment_form_logged_in', $ap_force_guest_fields);
        comment_form([
            'title_reply'         => 'ثبت نظر شما',
            'label_submit'        => 'ارسال نظر',
            'comment_notes_after' => '',
            'fields'              => [
                'author' => '<p class="comment-form-author" style="display:none;"><input id="author" name="author" type="text" value="" size="30"></p>',
                'email'  => '<p class="comment-form-email" style="display:none;"><input id="email" name="email" type="text" value="" size="30"></p>',
            ],
        ], $pid);
        remove_filter('comment_form_logged_in', $ap_force_guest_fields);
        $reviews_form_html = ob_get_clean();

        $single_product = [
            'id'    => $pid,
            'title' => get_the_title($pid),
            'spec'  => $spec ? $spec : '',
            'price' => $price,
            'priceUpdated' => $priceUpdated,
            'color' => function_exists('ap_brand_card_color') ? ap_brand_card_color($color) : ($color ?: '#b3122e'),
            'ic'    => $ic ? $ic : 'disc',
            'badge' => $badge ? $badge : null,
            'cat'   => $cat_slug,
            'oem'   => (string) get_post_meta($pid, '_ap_oem_code', true),
            /* خودرو (برند/مدل/سال) این قطعه، برای نمایشِ «سازگار با» در صفحه‌ی محصول */
            'vehicleNames' => (function () use ($pid) {
                $names = [];
                $vts = get_the_terms($pid, 'ap_vehicle');
                if ($vts && !is_wp_error($vts)) {
                    foreach ($vts as $vt) {
                        $chain = [];
                        foreach (array_reverse(get_ancestors($vt->term_id, 'ap_vehicle')) as $aid) {
                            $at = get_term($aid, 'ap_vehicle');
                            if ($at && !is_wp_error($at)) { $chain[] = $at->name; }
                        }
                        $chain[] = $vt->name;
                        $names[] = function_exists('ap_vehicle_label') ? ap_vehicle_label($chain) : implode(' ', $chain);
                    }
                }
                return array_values(array_unique($names));
            })(),
            'url'   => get_permalink($pid),
            'image' => $gallery[0] ?? ($img ?: null),
            'images' => $gallery,
            'shortDesc' => (string) get_post_meta($pid, '_ap_short_desc', true),
            'colors' => array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($pid, '_ap_colors', true))))),
            'storages' => array_values(array_filter(array_map('trim', explode(',', (string) get_post_meta($pid, '_ap_storages', true))))),
            'variantPrices' => (function () use ($pid) {
                $raw = get_post_meta($pid, '_ap_variant_prices', true);
                if (!$raw) return (object) [];
                $decoded = json_decode($raw, true);
                return is_array($decoded) && $decoded ? $decoded : (object) [];
            })(),
            'variantGroups' => (function () use ($pid) {
                $raw = get_post_meta($pid, '_ap_variant_groups', true);
                if (!$raw) return [];
                $decoded = json_decode($raw, true);
                return is_array($decoded) ? $decoded : [];
            })(),
            'description' => apply_filters('the_content', get_post_field('post_content', $pid)),
            'reviewsListHtml' => $reviews_list_html,
            'reviewsFormHtml' => $reviews_form_html,
            'reviewsCount'    => $reviews_count,
        ];

        $safe_title = esc_html($single_product['title']) . ' | ' . get_bloginfo('name');
        $html = preg_replace('#<title>.*?</title>#su', '<title>' . $safe_title . '</title>', $html, 1);
    }
} elseif (function_exists('is_tax') && is_tax('ap_cat')) {
    /* هر دسته‌بندی پیوند یکتای خودش رو داره (/product-cat/اسلاگ/)؛ برای سئوی
       بهتر، عنوان و توضیحات متا از فیلدهای سئوِ همون دسته (اگه پر شده باشن)
       خونده می‌شه، وگرنه از نام/توضیحات خودِ دسته استفاده می‌شه. */
    $term = get_queried_object();
    if ($term && !is_wp_error($term)) {
        $cat_seo_slug = $term->slug;
        $seo_title = trim((string) get_term_meta($term->term_id, 'ap_seo_title', true));
        $seo_desc  = trim((string) get_term_meta($term->term_id, 'ap_seo_desc', true));
        $cat_seo_desc = $seo_desc !== '' ? $seo_desc : trim((string) $term->description);

        $safe_title = esc_html($seo_title !== '' ? $seo_title : ($term->name . ' | ' . get_bloginfo('name')));
        $html = preg_replace('#<title>.*?</title>#su', '<title>' . $safe_title . '</title>', $html, 1);
    }
} elseif (function_exists('is_tax') && is_tax('ap_vehicle')) {
    /* پیوندِ یکتای هر برند/مدل/سال (مثل /car/peugeot/peugeot-206/1400/): همون صفحه‌ی
       اصلی باز می‌شه ولی فیلترِ «انتخاب خودرو» از همون لحظه روی همین خودرو ست می‌شه،
       پس مشتری (و گوگل) فقط قطعاتِ سازگار با این خودرو رو می‌بینه. */
    $vterm = get_queried_object();
    if ($vterm && !is_wp_error($vterm)) {
        $vehicle_slug = $vterm->slug;
        $chain = [];
        foreach (array_reverse(get_ancestors($vterm->term_id, 'ap_vehicle')) as $aid) {
            $at = get_term($aid, 'ap_vehicle');
            if ($at && !is_wp_error($at)) { $chain[] = $at->name; }
        }
        $chain[] = $vterm->name;
        $vehicle_label = ap_vehicle_label($chain);

        $ap_combo = function_exists('ap_part_combo') ? ap_part_combo() : null;
        $safe_title = esc_html($ap_combo ? ('خرید ' . $ap_combo['title'] . ' | ' . get_bloginfo('name')) : ('قطعات و لوازم یدکی ' . $vehicle_label . ' | قیمت روز | ' . get_bloginfo('name')));
        $html = preg_replace('#<title>.*?</title>#su', '<title>' . $safe_title . '</title>', $html, 1);
        $cat_seo_desc = trim((string) $vterm->description) !== ''
            ? $vterm->description
            : ('خرید قطعات و لوازم یدکی ' . $vehicle_label . ' از ' . get_bloginfo('name') . '؛ لنت، چراغ، سپر، فیلتر، کمک فنر و سایر قطعات اورجینال و درجه یک با قیمت روز و ارسال سریع.');
    }
}

/* دانلودِ لیستِ محصولات همزمان با صفحه شروع می‌شه (نه بعد از اجرای جاوااسکریپت) */
$inject = '<link rel="preload" as="fetch" crossorigin="anonymous" href="' . $api_url . '?action=products">' . "<script>\nwindow.AP_API_URL = " . json_encode($api_url) . ";\n";
$inject .= "window.AP_HOME_URL = " . json_encode($home_url) . ";\n";
/* کلیدِ عمومیِ اعلان‌ها (برای پرسیدنِ اجازه‌ی اعلان از بازدیدکننده‌ها) */
$ap_vapid_pub = function_exists('ap_vapid') ? (ap_vapid()['public'] ?? '') : '';
$inject .= "window.AP_PUSH_KEY = " . json_encode((string) $ap_vapid_pub) . ";\n";
$inject .= "window.AP_SITE_NAME = " . json_encode($ap_site_name) . ";\n";
if (!empty($vehicle_slug)) {
    $inject .= "window.AP_VEHICLE_SLUG = " . json_encode($vehicle_slug) . ";\n";
    $inject .= "window.AP_VEHICLE_LABEL = " . json_encode($vehicle_label, JSON_UNESCAPED_UNICODE) . ";\n";
    if (!empty($ap_combo)) $inject .= "window.AP_PART_CAT = " . json_encode($ap_combo['cat']->slug) . ";\n";
}
/* شماره‌ی تماسِ همون نوارِ بالای سایت رو به جاوااسکریپت هم می‌فرستیم تا روی
   عبارت «برای دریافت قیمت تماس بگیرید» هم بشه با تپ مستقیم تماس گرفت. */
$inject .= "window.AP_ADMIN_URL = " . json_encode(admin_url()) . ";\n";
$inject .= "window.AP_CONTACT_PHONE = " . json_encode(get_theme_mod('ap_contact_phone', '09109220001')) . ";\n";
$inject .= "window.AP_HERO_SLIDES = " . json_encode(function_exists('ap_hero_slides') ? ap_hero_slides() : [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ";\n";

/* بیلبوردها: متن‌ها از سفارشی‌سازی؛ لینکِ واتساپ از همون شماره‌ی نوارِ بالای سایت */
$bb_wa_digits = preg_replace('/[^0-9]/', '', (string) get_theme_mod('ap_contact_whatsapp', '09109220001'));
if (substr($bb_wa_digits, 0, 1) === '0') { $bb_wa_digits = '98' . substr($bb_wa_digits, 1); }
$bb_ticker = array_values(array_filter(array_map('trim', preg_split('/[,،]/u', (string) get_theme_mod('ap_bb_ticker', 'سپر، چراغ، رادیاتور، آینه، گلگیر، قطعات موتوری، قطعات اورجینال')))));
$inject .= "window.AP_BILLBOARDS = " . json_encode([
    'dealBadge' => (string) get_theme_mod('ap_bb_deal_badge', ''),
    'dealTitle' => (string) get_theme_mod('ap_bb_deal_title', ''),
    'dealText'  => (string) get_theme_mod('ap_bb_deal_text', ''),
    'dealLink'  => esc_url((string) get_theme_mod('ap_bb_deal_link', '')),
    'ticker'    => $bb_ticker,
    'helpTitle' => (string) get_theme_mod('ap_bb_help_title', ''),
    'helpText'  => (string) get_theme_mod('ap_bb_help_text', ''),
    'helpLink'  => $bb_wa_digits !== '' ? 'https://wa.me/' . (substr($bb_wa_digits, 0, 1) === '0' ? '98' . substr($bb_wa_digits, 1) : $bb_wa_digits) : '',
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ";\n";if ($single_product) {
    $inject .= "window.AP_SINGLE_PRODUCT = " . json_encode($single_product, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . ";\n";
}
if ($cat_seo_slug) {
    $inject .= "window.AP_CAT_SLUG = " . json_encode($cat_seo_slug) . ";\n";
}
/* صفحه‌های دسته / خودرو / «خودرو + قطعه»: سرتیتر و مسیرِ راهنما (breadcrumb) تا صفحه شبیهِ
   یک صفحه‌ی فهرستِ واقعی باشه، نه صفحه‌ی اصلی. */
$ap_page_head = null;
if (function_exists('is_tax') && (is_tax('ap_cat') || is_tax('ap_vehicle')) && empty($single_product)) {
    $qt = get_queried_object();
    if ($qt && !is_wp_error($qt)) {
        $crumbs = [['name' => 'صفحه اصلی', 'url' => home_url('/')]];
        $tax = $qt->taxonomy;
        foreach (array_reverse(get_ancestors($qt->term_id, $tax)) as $aid) {
            $at = get_term($aid, $tax);
            if ($at && !is_wp_error($at)) { $l = get_term_link($at); $crumbs[] = ['name' => $at->name, 'url' => is_wp_error($l) ? '' : $l]; }
        }
        $self = get_term_link($qt);
        $crumbs[] = ['name' => $qt->name, 'url' => is_wp_error($self) ? '' : $self];
        $combo = ($tax === 'ap_vehicle' && function_exists('ap_part_combo')) ? ap_part_combo() : null;
        if ($combo) $crumbs[] = ['name' => $combo['cat']->name, 'url' => $combo['url']];
        $h1 = $combo ? $combo['title'] : ($tax === 'ap_vehicle' ? 'قطعات ' . (!empty($vehicle_label) ? $vehicle_label : $qt->name) : $qt->name);
        $ap_page_head = ['h1' => $h1, 'crumbs' => $crumbs, 'desc' => $cat_seo_desc];
    }
}
if ($ap_page_head) $inject .= "window.AP_PAGE_HEAD = " . json_encode($ap_page_head, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) . ";\n";
$inject .= "</script>";
if ($cat_seo_desc !== '') {
    $inject .= '<meta name="description" content="' . esc_attr(wp_trim_words($cat_seo_desc, 30, '...')) . '">';
}
$inject .= $topbar_style;

/* ---------- سئوی صفحه‌های اپلیکیشنِ اصلی (صفحه‌ی اصلی، محصول، دسته) ----------
   این قالب wp_head() رو صدا نمی‌زنه، برای همین تا الان توضیحات متا، canonical، تگ‌های
   شبکه‌های اجتماعی و اسکیمای Rank Math روی صفحه‌ی محصول‌ها اصلاً چاپ نمی‌شد و گوگل
   فقط یه صفحه‌ی تقریباً خالی (که با جاوااسکریپت پر می‌شه) می‌دید. حالا خروجیِ سرِ صفحه‌ی
   Rank Math مستقیم اضافه می‌شه، به‌علاوه‌ی اسکیمای محصول و نسخه‌ی HTMLِ محتوای صفحه. */
$seo_head = '';
if (has_action('rank_math/head')) { ob_start(); do_action('rank_math/head'); $seo_head = (string) ob_get_clean(); }
$qid = function_exists('get_queried_object_id') ? get_queried_object_id() : 0;
$is_prod = function_exists('is_singular') && is_singular('ap_product') && $qid;
$is_cat  = function_exists('is_tax') && is_tax('ap_cat');
$is_veh  = function_exists('is_tax') && is_tax('ap_vehicle');
$ap_combo = ($is_veh && function_exists('ap_part_combo')) ? ap_part_combo() : null;
$canon = $is_prod ? get_permalink($qid) : ($ap_combo ? $ap_combo['url'] : (($is_cat || $is_veh) ? get_term_link(get_queried_object()) : (is_front_page() || is_home() ? home_url('/') : '')));
if (is_wp_error($canon)) $canon = '';
if (stripos($seo_head, 'rel="canonical"') === false && $canon) $seo_head .= '<link rel="canonical" href="' . esc_url($canon) . '">';
if (stripos($seo_head, 'name="description"') === false) {
    $desc = '';
    if ($is_prod) $desc = (string) get_post_meta($qid, '_ap_short_desc', true) ?: wp_strip_all_tags(get_post_field('post_content', $qid));
    elseif ($ap_combo) $desc = 'خرید ' . $ap_combo['title'] . '؛ اورجینال و آفتر مارکت با گارانتی اصالت و قیمت روز.';
    elseif ($is_veh && !empty($cat_seo_desc)) $desc = $cat_seo_desc;
    elseif (!$is_cat) $desc = 'فروشگاه تخصصی قطعات یدکی خودرو؛ قطعات موتوری، بدنه، برقی و جلوبندی خودروهای چینی و داخلی، اورجینال و آفتر مارکت.';
    if ($desc !== '') $seo_head .= '<meta name="description" content="' . esc_attr(wp_trim_words(wp_strip_all_tags($desc), 30, '...')) . '">';
} else {
    $inject = preg_replace('#<meta name="description"[^>]*>#', '', $inject); // تکراری نشه
}
$ssr = '';
if ($is_prod) {
    $p_title = get_the_title($qid);
    $p_desc  = wp_strip_all_tags((string) get_post_meta($qid, '_ap_short_desc', true));
    $p_imgs  = function_exists('ap_gallery_urls') ? array_values(array_filter(ap_gallery_urls($qid))) : [];
    $p_vp    = function_exists('ap_variant_prices') ? array_values(ap_variant_prices($qid)) : [];
    $p_base  = (int) get_post_meta($qid, '_ap_price', true);
    $p_all   = array_filter(array_merge($p_vp, $p_base > 0 ? [$p_base] : []));
    $schema  = ['@context' => 'https://schema.org', '@type' => 'Product', 'name' => $p_title, 'url' => get_permalink($qid),
                'description' => $p_desc ?: wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $qid)), 40, '...'),
                ];
    if ($p_imgs) $schema['image'] = $p_imgs;
    if ($p_all) $schema['offers'] = ['@type' => 'AggregateOffer', 'priceCurrency' => 'IRR', 'lowPrice' => min($p_all) * 10, 'highPrice' => max($p_all) * 10,
                                     'offerCount' => count($p_all), 'availability' => 'https://schema.org/InStock', 'url' => get_permalink($qid)];
    if (stripos($seo_head, '"Product"') === false) $seo_head .= '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    /* نسخه‌ی HTMLِ محتوا برای خزنده‌ها؛ به‌محضِ اجرای جاوااسکریپت، نسخه‌ی کاملِ صفحه جاش رو می‌گیره */
    $terms = get_the_terms($qid, 'ap_cat');
    $crumbs = '';
    if ($terms && !is_wp_error($terms)) {
        foreach (array_reverse(get_ancestors($terms[0]->term_id, 'ap_cat')) as $aid) { $t = get_term($aid, 'ap_cat'); if ($t) $crumbs .= '<a href="' . esc_url(get_term_link($t)) . '">' . esc_html($t->name) . '</a> › '; }
        $crumbs .= '<a href="' . esc_url(get_term_link($terms[0])) . '">' . esc_html($terms[0]->name) . '</a>';
    }
    $ssr = '<main id="apSsr"><nav>' . $crumbs . '</nav><h1>' . esc_html($p_title) . '</h1>'
         . ($p_imgs ? '<img src="' . esc_url($p_imgs[0]) . '" alt="' . esc_attr($p_title) . '" width="600" height="600">' : '')
         . ($p_desc ? '<p>' . esc_html($p_desc) . '</p>' : '')
         . '<div>' . apply_filters('the_content', get_post_field('post_content', $qid)) . '</div></main>';
} elseif ($is_cat) {
    $term = get_queried_object();
    $ids = get_posts(['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => 200, 'fields' => 'ids',
                      'tax_query' => [['taxonomy' => 'ap_cat', 'terms' => (int) $term->term_id, 'include_children' => true]]]);
    $kids = get_terms(['taxonomy' => 'ap_cat', 'parent' => (int) $term->term_id, 'hide_empty' => true]);
    $ssr = '<main id="apSsr"><h1>' . esc_html($term->name) . '</h1><ul>';
    if ($kids && !is_wp_error($kids)) foreach ($kids as $k) $ssr .= '<li><a href="' . esc_url(get_term_link($k)) . '">' . esc_html($k->name) . '</a></li>';
    foreach ($ids as $pid) $ssr .= '<li><a href="' . esc_url(get_permalink($pid)) . '">' . esc_html(get_the_title($pid)) . '</a></li>';
    $ssr .= '</ul></main>';
} elseif ($is_veh) {
    /* صفحه‌ی خودرو (و «خودرو + قطعه»): فهرستِ پیوندِ زیرمدل/تیپ‌ها، نوعِ قطعه‌ها و محصولات برای خزنده‌ها */
    $vt = get_queried_object();
    $vlink = get_term_link($vt);
    $ssr = '<main id="apSsr"><h1>' . esc_html($ap_combo ? $ap_combo['title'] : ('قطعات ' . ($vehicle_label ?? $vt->name))) . '</h1>';
    $kids = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => (int) $vt->term_id, 'hide_empty' => false]);
    if (!$ap_combo && $kids && !is_wp_error($kids)) { $ssr .= '<ul>'; foreach ($kids as $k) $ssr .= '<li><a href="' . esc_url(get_term_link($k)) . '">' . esc_html($k->name) . '</a></li>'; $ssr .= '</ul>'; }
    $ids = $ap_combo ? $ap_combo['ids'] : get_posts(['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => 200, 'fields' => 'ids',
        'tax_query' => [['taxonomy' => 'ap_vehicle', 'terms' => (int) $vt->term_id, 'include_children' => true]]]);
    if (!$ap_combo && $ids && !is_wp_error($vlink)) {
        $catIds = [];
        foreach ($ids as $pid) foreach ((array) wp_get_post_terms($pid, 'ap_cat', ['fields' => 'ids']) as $cid) $catIds[(int) $cid] = true;
        if ($catIds) { $ssr .= '<ul>'; foreach (array_keys($catIds) as $cid) { $ct = get_term($cid, 'ap_cat'); if ($ct && !is_wp_error($ct)) $ssr .= '<li><a href="' . esc_url(trailingslashit($vlink) . 'part/' . $ct->slug . '/') . '">' . esc_html($ct->name . ' ' . ($vehicle_label ?? $vt->name)) . '</a></li>'; } $ssr .= '</ul>'; }
    }
    $ssr .= '<ul>'; foreach ($ids as $pid) $ssr .= '<li><a href="' . esc_url(get_permalink($pid)) . '">' . esc_html(get_the_title($pid)) . '</a></li>'; $ssr .= '</ul></main>';
}
if ($ssr !== '') {
    $ssr = '<style>#apSsr{max-width:900px;margin:30px auto;padding:0 16px;font-family:Vazirmatn,Tahoma,sans-serif}#apSsr img{max-width:100%;height:auto}</style>'
         . $ssr . '<script>(function(){var e=document.getElementById("apSsr");if(e)e.remove();})();</script>';
    $html = preg_replace_callback('#<body([^>]*)>#i', function ($m) use ($ssr) { return $m[0] . $ssr; }, $html, 1);
}
/* ---------- فوترِ لینک‌دار + اسکیمای WebSite/Organization برای زیرلینک‌های گوگل ---------- */
$foot = '';
$groups = [];
$ap_tops = get_terms(['taxonomy' => 'ap_cat', 'parent' => 0, 'hide_empty' => true]);
foreach ((is_array($ap_tops) ? $ap_tops : []) as $top) {
    if (is_wp_error($top)) continue;
    $kids = get_terms(['taxonomy' => 'ap_cat', 'parent' => $top->term_id, 'hide_empty' => true]);
    $links = [];
    if ($kids && !is_wp_error($kids)) foreach ($kids as $k) $links[] = [get_term_link($k), $k->name];
    if (!$links) $links[] = [get_term_link($top), $top->name];
    $groups[] = [$top->name, $links];
}
/* صفحه‌های فرودِ سئو و لیستِ قیمتِ برندهای پرمحصول (seo-landing.php) */
if (function_exists('ap_lp_brands')) {
    $groups[] = ['فروشگاه کار تهران', [[home_url('/spare-parts/'), 'لوازم یدکی خودرو'], [home_url('/car-parts/'), 'قطعات خودرو'], [home_url('/car-parts-tehran/'), 'لوازم یدکی در تهران'], [home_url('/chinese-car-parts/'), 'قطعات خودروهای چینی'], [home_url('/cartehran/'), 'درباره کار تهران']]];
    $lpl = [[home_url('/parts-price/'), 'لیست قیمت قطعات خودرو']];
    foreach (array_slice(ap_lp_brands(), 0, 7) as $lb) $lpl[] = [home_url('/parts-price/' . $lb['short'] . '/'), 'قیمت قطعات ' . $lb['fa']];
    $groups[] = ['قیمت قطعات', $lpl];
}
$groups[] = [$ap_site_name, [[get_post_type_archive_link('ap_article') ?: home_url('/blog/'), 'مقالات آموزشی'], [home_url('/about/'), 'درباره ما'], [home_url('/terms/'), 'قوانین و مقررات']]];
foreach ($groups as $g) {
    $foot .= '<div><h4>' . esc_html($g[0]) . '</h4><ul>';
    foreach ($g[1] as $l) if (!is_wp_error($l[0])) $foot .= '<li><a href="' . esc_url($l[0]) . '">' . esc_html($l[1]) . '</a></li>';
    $foot .= '</ul></div>';
}
$html = str_replace('<!--AP_FOOT_LINKS-->', '<nav class="foot-nav" aria-label="لینک‌های اصلی سایت">' . $foot . '</nav>', $html);
if (function_exists('is_front_page') && is_front_page()) {
    $site_schema = ['@context' => 'https://schema.org', '@graph' => [
        ['@type' => 'WebSite', '@id' => home_url('/#website'), 'url' => home_url('/'), 'name' => $ap_site_name,
         'inLanguage' => 'fa-IR', 'potentialAction' => ['@type' => 'SearchAction', 'target' => home_url('/?ap_search={search_term_string}'), 'query-input' => 'required name=search_term_string']],
        ['@type' => 'Organization', '@id' => home_url('/#organization'), 'name' => $ap_site_name, 'url' => home_url('/'),
         'logo' => function_exists('ap_logo_url') ? ap_logo_url() : '', 'telephone' => (string) get_theme_mod('ap_contact_phone', '09109220001')],
    ]];
    /* هرکدوم که Rank Math خودش چاپ نکرده باشه اضافه می‌شه (Organization با لوگو رو ابزارِ Rich Results گوگل تشخیص می‌ده) */
    if (function_exists('ap_lp_org_schema')) $site_schema['@graph'] = ap_lp_org_schema();
    $graph = [];
    if (stripos($seo_head, '"WebSite"') === false) $graph[] = $site_schema['@graph'][0];
    if (stripos($seo_head, '"Organization"') === false || stripos($seo_head, '"logo"') === false) $graph[] = $site_schema['@graph'][1];
    if ($graph) $seo_head .= '<script type="application/ld+json">' . wp_json_encode(['@context' => 'https://schema.org', '@graph' => $graph], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
}
/* متاتگِ اینماد (تأییدِ مالکیت برای نماد اعتماد الکترونیکی) */
if (defined('AP_ENAMAD_CODE')) $seo_head = '<meta name="enamad" content="' . AP_ENAMAD_CODE . '" />' . $seo_head;
/* صفحه‌ی اصلی: عنوان موقتاً دقیقاً کدِ اینماد (روشِ «تغییر عنوان»؛ ap_enamad_title_active در seo-landing.php) */
if (function_exists('is_front_page') && is_front_page() && function_exists('ap_enamad_title_active') && ap_enamad_title_active()) {
    $html = preg_replace('#<title>.*?</title>#su', '<title>' . AP_ENAMAD_CODE . '</title>', $html, 1);
}
$inject = $seo_head . $inject;
$html = str_replace('<head>', "<head>\n" . $inject, $html);

/* ---------- نوار مدیریت وردپرس (Admin Bar) ----------
   وردپرس این نوار رو فقط از طریق هوک‌های wp_head()/wp_footer() چاپ می‌کنه؛
   چون این قالب کاملاً سفارشیه و هیچ‌کدوم از این هوک‌ها رو صدا نمی‌زنه، خودِ
   HTML/CSS/JS لازم رو اینجا مستقیم می‌سازیم و تزریق می‌کنیم. */
$admin_bar_html = '';
$body_class = '';
if (function_exists('is_admin_bar_showing') && is_admin_bar_showing()) {
    require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';
    require_once ABSPATH . 'wp-includes/admin-bar.php';

    /* گرهِ کاملِ نوار مدیریت باید یک‌بار مقداردهیِ اولیه بشه؛ چون این قالب هوکِ
       wp_footer/wp_head رو صدا نمی‌زنه، خودمون _wp_admin_bar_init رو صدا می‌زنیم.
       (فایلِ درستِ این توابع wp-includes/admin-bar.php هست، نه مسیرِ wp-admin که
        وجود نداره و باعثِ خطای مرگبارِ صفحه برای مدیر می‌شد.) */
    if (function_exists('_wp_admin_bar_init')) { _wp_admin_bar_init(); }

    ob_start();
    if (function_exists('wp_admin_bar_render')) { wp_admin_bar_render(); }
    $bar_markup = ob_get_clean();

    /* آیکون‌های نوار مدیریت با فونتِ Dashicons رسم می‌شن؛ قبلاً این فونت لود نمی‌شد و
       به‌جای آیکون‌ها فقط مربع‌های خالی (مثل عکس) نمایش داده می‌شد. */
    $wp_ver = get_bloginfo('version');
    $admin_bar_html = '<link rel="stylesheet" href="' . esc_url(includes_url('css/dashicons.min.css') . '?ver=' . $wp_ver) . '">'
        . '<link rel="stylesheet" href="' . esc_url(includes_url('css/admin-bar.min.css') . '?ver=' . $wp_ver) . '">'
        . '<style>
            body.admin-bar{margin-top:32px !important;}
            @media screen and (max-width:782px){ body.admin-bar{margin-top:46px !important;} }
            #wpadminbar{position:fixed !important; top:0; right:0; left:0; z-index:99999;}
            /* جلوگیری از تداخلِ استایل‌های عمومیِ قالب با نوار مدیریت */
            #wpadminbar *{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;}
            #wpadminbar .ab-icon, #wpadminbar .ab-item:before, #wpadminbar .ab-icon:before, #wpadminbar #adminbarsearch:before{font-family:dashicons !important;}
            #wpadminbar img, #wpadminbar svg{display:inline-block;}
            #wpadminbar .avatar{display:inline-block !important;}
            /* نوار بنفش (بخش ۱ هدر) زیرِ نوار مدیریت بچسبه، نه پشتش */
            body.admin-bar #apStickyTop{top:32px;}
            @media screen and (max-width:782px){ body.admin-bar #apStickyTop{top:46px;} }
            /* لایه‌های تمام‌صفحه (فیلتر، سبد، پنجره‌ها) از زیرِ نوار مدیریت شروع بشن تا
               دکمه‌ی بستن و سرتیترشون پشتِ نوار پنهان نشه. */
            body.admin-bar .filter-screen, body.admin-bar .overlay, body.admin-bar .drawer{top:32px;}
            @media screen and (max-width:782px){
                body.admin-bar .filter-screen, body.admin-bar .overlay, body.admin-bar .drawer{top:46px;}
            }
        </style>'
        . (function_exists('ap_adminbar_logo_css') ? (function () { ob_start(); ap_adminbar_logo_css(); return ob_get_clean(); })() : '')
        . $bar_markup
        . '<script src="' . esc_url(includes_url('js/hoverintent-js.min.js') . '?ver=' . $wp_ver) . '"></script>'
        . '<script src="' . esc_url(includes_url('js/admin-bar.min.js') . '?ver=' . $wp_ver) . '"></script>';

    $body_class = ' class="admin-bar"';
}

$body_open = '<body' . $body_class . '>';
$html = str_replace('<body>', $body_open, $html);
$html = str_replace($body_open, $body_open . "\n" . $admin_bar_html . "\n<div id=\"apStickyTop\">" . $topbar_html, $html);
$html = str_replace('</header>', "</header>\n</div>", $html);

echo $html;
exit;
