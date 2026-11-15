<?php
if (!defined('ABSPATH')) { exit; }

/* بخشِ بکاپ از قالب حذف شد (به درخواستِ مدیر؛ افزونه‌ی جدا ساخته می‌شه). به‌روزرسانیِ قالب فایل‌های
   قدیمی رو پاک نمی‌کنه، پس کدهای قبلیِ بکاپ یک‌بار از پوشه‌ی قالب حذف می‌شن. (فایل‌های بکاپی که قبلاً
   ساخته شدن، داخلِ wp-content/uploads/ap-backups دست‌نخورده می‌مونن.) */
add_action('admin_init', function () {
    if (get_option('ap_backup_code_removed_v1') || !current_user_can('manage_options')) return;
    $dir = get_template_directory();
    foreach (['/ap-backup/ap-backup.php', '/backup.php'] as $f) { if (is_file($dir . $f)) @unlink($dir . $f); }
    if (is_dir($dir . '/ap-backup')) @rmdir($dir . '/ap-backup');
    update_option('ap_backup_code_removed_v1', time(), false);
});

/**
 * این پوسته به هیچ افزونه‌ای (از جمله ووکامرس) وابسته نیست.
 * صفحه‌ی اصلی (site.html) برای تمام آدرس‌های سایت بارگذاری می‌شه؛
 * فقط صفحه‌ی اختصاصیِ هر محصول (نوع پست ap_product) با اطلاعات همان محصول نمایش داده می‌شه.
 */
/* این پوسته کاملاً اختصاصیه و نوار بالای سایت خودش رو به‌صورت position:fixed
   نمایش می‌ده؛ نوار مدیریت پیش‌فرض وردپرس (Admin Bar) با اون تداخل مکانی داره
   (باعث یک فاصله‌ی خالی بین نوار بنفش و هدر می‌شه)، پس برای بازدیدکننده‌های
   عادی غیرفعاله. برای مدیر سایت (شماره‌ای که در پیشخوان ← مشتریان به‌عنوان
   ادمین علامت خورده)، وقتی از همون فرم ورود سایت (نه wp-login.php) وارد بشه،
   یک سشن واقعی وردپرس هم پشت صحنه ساخته می‌شه که نوار مدیریت وردپرس، در حالت
   عادیِ بازدید از سایت، بالای صفحه نشونش بده. */
add_filter('show_admin_bar', function ($show) {
    return is_user_logged_in() && current_user_can('manage_options');
});

/* استایل اختصاصی نوار مدیریت (Admin Bar) بالای سایت — فقط برای همون مدیری که
   نوار براش نمایش داده می‌شه (شماره‌ی مالک سایت)، تیره و هماهنگ با برند سایت. */
add_action('wp_head', function () {
    if (!is_admin_bar_showing()) return;
    ?>
    <style>
        #wpadminbar{background:#1a1a1a !important; border-bottom:2px solid #ff5563;}
        #wpadminbar .ab-item, #wpadminbar a.ab-item, #wpadminbar > #wp-toolbar span.ab-label, #wpadminbar > #wp-toolbar span.noticon{color:#e6e6e6 !important;}
        #wpadminbar .ab-icon, #wpadminbar .ab-icon:before{color:#ff5563 !important;}
        #wpadminbar .hover > .ab-item, #wpadminbar li:hover > .ab-item, #wpadminbar li.hover > .ab-item{background:#2a2a2a !important; color:#fff !important;}
        #wpadminbar #wp-admin-bar-my-account.with-avatar .avatar{border-radius:4px;}
        #wpadminbar #wp-admin-bar-top-secondary{direction:ltr;}
    </style>
    <?php
});

/* گزینه‌ی «ویرایش محصول» در نوار مدیریت، دقیقاً مثل ووکامرس، وقتی توی
   صفحه‌ی اختصاصیِ یک محصول (ap_product) هستیم؛ کلیک روش می‌بره به همون
   صفحه‌ی ویرایش محصول در پیشخوان. چون این قالب صفحه‌ی محصول رو با کوئریِ
   استاندارد وردپرس (is_singular) میده ولی خودش the_post()/لوپ رو صدا نمی‌زنه،
   گره پیش‌فرض «Edit Post» وردپرس ممکنه ظاهر نشه؛ برای همین خودمون اضافه‌اش می‌کنیم. */
add_action('admin_bar_menu', function ($wp_admin_bar) {
    if (!function_exists('is_singular') || !is_singular('ap_product')) return;
    $pid = get_queried_object_id();
    if (!$pid || !current_user_can('edit_post', $pid)) return;
    $edit_link = get_edit_post_link($pid, '');
    if (!$edit_link) return;
    $wp_admin_bar->add_node([
        'id'    => 'ap-edit-product',
        'title' => 'ویرایش محصول',
        'href'  => $edit_link,
    ]);
}, 80);

/* شماره‌ی پیش‌فرضِ مالک سایت؛ بعدش از پنلِ سایت ← حسابِ کاربری ← ادمین‌ها قابل تغییره. */
define('AP_OWNER_PHONE', '09109973428');
/* حسابِ وردپرسیِ مدیرِ کل: نام کاربری = شماره‌ی مدیر */
define('AP_OWNER_WP_LOGIN', '09109973428');

/* $user: حسابِ فروشگاه (خروجیِ public_user در api.php). مدیرِ کل → حسابِ «ap-owner» با نقشِ
   مدیر کل وردپرس؛ ادمینِ زیرمجموعه با دسترسیِ «محصولات» → حسابِ جدا با نقشِ «ویرایشگر»
   (فقط محصولات/دسته‌ها/مقالات؛ نه تنظیمات، افزونه‌ها یا به‌روزرسانیِ قالب). بقیه سشنِ وردپرس نمی‌گیرن. */
function ap_wp_user_for_admin($user, $create = false) {
    if (!empty($user['is_owner'])) {
        $login = get_option('ap_owner_wp_login') ?: AP_OWNER_WP_LOGIN;
        $wp_user = get_user_by('login', $login) ?: get_user_by('login', 'ap-owner') ?: get_user_by('email', 'owner@nexv.local');
        if (!$wp_user && $create) {
            $new_id = wp_insert_user([
                'user_login'   => $login,
                'user_pass'    => wp_generate_password(24, true, true),
                'user_email'   => 'owner@nexv.local',
                'display_name' => 'مدیر سایت',
                'role'         => 'administrator',
            ]);
            if (!is_wp_error($new_id)) { $wp_user = get_user_by('id', $new_id); }
        }
        return $wp_user ?: null;
    }
    if (empty($user['id'])) return null;
    /* ادمین‌هایی که از سیستمِ قبلی (پیشخوان ← مدیریت ادمین‌ها) منتقل شدن، حسابِ وردپرسیِ خودشون رو دارن */
    if (!empty($user['wp_user_id']) && ($wu = get_user_by('id', (int) $user['wp_user_id']))) return $wu;
    $login = 'ap-admin-' . (int) $user['id'];
    $wp_user = get_user_by('login', $login);
    if (!$wp_user && $create) {
        $new_id = wp_insert_user([
            'user_login'   => $login,
            'user_pass'    => wp_generate_password(24, true, true),
            'user_email'   => 'admin-' . (int) $user['id'] . '@nexv.local',
            'display_name' => trim(($user['firstName'] ?? '') . ' ' . ($user['lastName'] ?? '')) ?: $login,
            'role'         => 'editor',
        ]);
        if (!is_wp_error($new_id)) { $wp_user = get_user_by('id', $new_id); }
    }
    return $wp_user ?: null;
}
function ap_admin_has_wp_access($user) {
    return !empty($user['is_admin']) && (!empty($user['is_owner']) || in_array('products', (array) ($user['perms'] ?? []), true));
}
/* نقشِ وردپرسیِ ادمین‌های زیرمجموعه با دسترسی‌هاشون هماهنگ می‌شه (برداشتنِ دسترسی = خروج از پیشخوان) */
function ap_wp_admin_sync($user) {
    if (!empty($user['is_owner'])) return;
    $wp_user = ap_wp_user_for_admin($user, ap_admin_has_wp_access($user));
    if (!$wp_user) return;
    if (ap_admin_has_wp_access($user)) {
        if (!in_array('editor', (array) $wp_user->roles, true)) $wp_user->set_role('editor');
    } else {
        $wp_user->set_role('subscriber');
        if (class_exists('WP_Session_Tokens')) WP_Session_Tokens::get_instance($wp_user->ID)->destroy_all();
    }
}
/* بعد از تغییرِ رمز: بقیه‌ی سشن‌های وردپرسِ همین ادمین بسته می‌شن (سشنِ همین مرورگر می‌مونه) */
function ap_wp_admin_logout_others($user) {
    $wp_user = ap_wp_user_for_admin($user, false);
    if (!$wp_user || !class_exists('WP_Session_Tokens')) return;
    $mgr = WP_Session_Tokens::get_instance($wp_user->ID);
    if (get_current_user_id() === (int) $wp_user->ID && function_exists('wp_get_session_token') && wp_get_session_token()) $mgr->destroy_others(wp_get_session_token());
    else $mgr->destroy_all();
}
function ap_bridge_wp_session($phone, $user = null) {
    if ($user === null) {
        if ($phone !== AP_OWNER_PHONE) return;
        $user = ['is_admin' => true, 'is_owner' => true];
    }
    if (!ap_admin_has_wp_access($user)) return;
    ap_wp_admin_sync($user);
    $wp_user = ap_wp_user_for_admin($user, true);
    if ($wp_user && !is_wp_error($wp_user)) {
        wp_clear_auth_cookie();
        wp_set_current_user($wp_user->ID);
        wp_set_auth_cookie($wp_user->ID, true);
    }
}
/* وقتی مدیرِ کل از پنلِ سایت شماره یا رمزش رو عوض می‌کنه، حسابِ وردپرسش هم همون می‌شه */
function ap_wp_owner_sync($user, $newPass = '') {
    if (empty($user['is_owner'])) return;
    $wp_user = ap_wp_user_for_admin($user, true);
    if (!$wp_user) return;
    global $wpdb;
    $stay = get_current_user_id() === (int) $wp_user->ID;
    $phone = preg_replace('/[^0-9]/', '', (string) ($user['phone'] ?? ''));
    if ($phone !== '' && $phone !== $wp_user->user_login && !username_exists($phone)) {
        $wpdb->update($wpdb->users, ['user_login' => $phone, 'user_nicename' => $phone], ['ID' => $wp_user->ID]);
        update_option('ap_owner_wp_login', $phone, false);
        clean_user_cache($wp_user->ID);
    }
    if ($newPass !== '') wp_set_password($newPass, $wp_user->ID);
    if ($stay) { wp_clear_auth_cookie(); wp_set_current_user($wp_user->ID); wp_set_auth_cookie($wp_user->ID, true); }   // همین مرورگر وارد بمونه
}

function ap_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
}
add_action('after_setup_theme', 'ap_theme_setup');

function ap_force_custom_template($template) {
    $custom = get_template_directory() . '/index.php';
    return file_exists($custom) ? $custom : $template;
}
add_filter('template_include', 'ap_force_custom_template', 99);

/* =========================================================================
 * نوع پست «محصول» + دسته‌بندیِ درختی (سلسله‌مراتبی) — دقیقاً مثل دسته‌بندی‌های
 * معمولیِ وردپرس: هر دسته می‌تونه دسته‌ی والد داشته باشه (مثلاً دسته‌ی اول
 * «سپر» و «چراغ»، و زیرمجموعه‌ی جدا برای هرکدوم). این تاکسونومی از پیشخوانِ
 * وردپرس (محصولات ← دسته‌بندی‌ها) مدیریت می‌شه و فیلتر پیشرفته‌ی سایت خودش رو
 * دقیقاً بر همین ساختار می‌سازه.
 * ========================================================================= */
function ap_register_product_cpt() {
    register_post_type('ap_product', [
        'labels' => [
            'name'               => 'محصولات',
            'singular_name'      => 'محصول',
            'add_new'            => 'افزودن محصول',
            'add_new_item'       => 'افزودن محصول جدید',
            'edit_item'          => 'ویرایش محصول',
            'new_item'           => 'محصول جدید',
            'view_item'          => 'مشاهده محصول',
            'all_items'          => 'همه‌ی محصولات',
            'search_items'       => 'جستجوی محصول',
            'not_found'          => 'محصولی پیدا نشد.',
            'menu_name'          => 'محصولات',
        ],
        'public'        => true,
        'has_archive'   => false,
        'show_in_menu'  => 'ap-dashboard',
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-cart',
        'supports'      => ['title', 'thumbnail', 'editor', 'comments'],
        'rewrite'       => ['slug' => 'product'],
    ]);
}
add_action('init', 'ap_register_product_cpt');

/* =========================================================================
 * نوع پست «مقاله»: برای بخش وبلاگ/مقالات سایت. از ادیتور استاندارد وردپرس
 * استفاده می‌کنه (همون صفحه‌ی افزودن/ویرایش نوشته‌ی معمولی وردپرس) که خودش
 * امکان قراردادن عکس وسط متن رو داره (دکمه‌ی «افزودن رسانه» / بلوک تصویر).
 * ========================================================================= */
function ap_register_article_cpt() {
    register_post_type('ap_article', [
        'labels' => [
            'name'               => 'مقالات',
            'singular_name'      => 'مقاله',
            'add_new'            => 'افزودن مقاله',
            'add_new_item'       => 'افزودن مقاله‌ی جدید',
            'edit_item'          => 'ویرایش مقاله',
            'new_item'           => 'مقاله‌ی جدید',
            'view_item'          => 'مشاهده‌ی مقاله',
            'all_items'          => 'همه‌ی مقالات',
            'search_items'       => 'جستجوی مقاله',
            'not_found'          => 'مقاله‌ای پیدا نشد.',
            'menu_name'          => 'مقالات',
        ],
        'public'        => true,
        'has_archive'   => 'blog',
        'show_in_menu'  => 'ap-dashboard',
        'show_in_rest'  => true,
        'menu_icon'     => 'dashicons-media-text',
        'supports'      => ['title', 'thumbnail', 'editor', 'excerpt'],
        'rewrite'       => ['slug' => 'article'],
    ]);
}
/* با تغییرِ has_archive/rewrite بالا، قوانینِ لینک‌دهیِ وردپرس باید یک‌بار
   دوباره ساخته بشن، وگرنه yoursite.com/blog موقتاً ۴۰۴ می‌ده. */
add_action('init', function () {
    if (get_option('ap_rewrite_flushed_v2')) return;
    flush_rewrite_rules();
    update_option('ap_rewrite_flushed_v2', 1);
}, 20);
/* ساختنِ خودکارِ صفحه‌ی «درباره ما» (فقط یک‌بار، اگه از قبل نساخته باشیم)؛
   پیوندش انگلیسیه: yoursite.com/about — طبق قانونِ پروژه برای همه‌ی URLها. */
add_action('init', function () {
    if (get_option('ap_about_page_created')) return;
    $existing = get_page_by_path('about');
    if (!$existing) {
        wp_insert_post([
            'post_type'   => 'page',
            'post_title'  => 'درباره ما',
            'post_name'   => 'about',
            'post_status' => 'publish',
            'post_content' => '',
        ]);
    }
    update_option('ap_about_page_created', 1);
});
/* ساختنِ خودکارِ صفحه‌ی «قوانین و مقررات» با پیوندِ yoursite.com/terms (فقط یک‌بار).
   محتواش از قالب خونده می‌شه، پس این صفحه فقط برای داشتنِ پیوند لازمه. */
add_action('init', function () {
    if (get_option('ap_terms_page_created')) return;
    if (!get_page_by_path('terms')) {
        wp_insert_post([
            'post_type'   => 'page',
            'post_title'  => 'قوانین و مقررات',
            'post_name'   => 'terms',
            'post_status' => 'publish',
            'post_content' => '',
        ]);
    }
    update_option('ap_terms_page_created', 1);
});
add_action('init', 'ap_register_article_cpt');

/* حذف خودکارِ نوشته‌ی پیش‌فرضِ «سلام دنیا»/Hello World که خودِ وردپرس موقع نصب
   می‌سازه؛ این نوشته ربطی به سایت نداره و طبق درخواست باید کامل حذف بشه. */
add_action('init', function () {
    if (get_option('ap_hello_world_removed')) return;
    $candidates = get_posts([
        'post_type'   => 'post',
        'post_status' => 'any',
        'numberposts' => 5,
        's'           => 'Hello world',
    ]);
    foreach ($candidates as $p) {
        if (in_array(strtolower($p->post_title), ['hello world!', 'hello world', 'سلام دنیا', 'سلام دنیا!'], true)) {
            wp_delete_post($p->ID, true);
        }
    }
    // اسلاگ فارسیِ احتمالی رو هم جدا چک کن (چون s= بالا فقط عنوان انگلیسی رو پیدا می‌کنه)
    $bySlug = get_page_by_path('سلام-دنیا', OBJECT, 'post');
    if ($bySlug) wp_delete_post($bySlug->ID, true);

    update_option('ap_hello_world_removed', 1);
});

/* =========================================================================
 * قانون طلایی پروژه: هیچ محصول یا مقاله‌ای نباید Permalink فارسی داشته باشه.
 * چون عنوان‌ها فارسی هستن (مثلاً «سپر جلو پژو 206»)، وردپرس به‌صورت پیش‌فرض یا
 * همون فارسی رو Percent-Encode می‌کنه توی اسلاگ، یا (بسته به تنظیمات هاست)
 * اسلاگ خالی/عددی می‌سازه که باعث تداخل و بازنشدنِ بعضی صفحات می‌شه.
 * این تابع از روی عنوانِ فارسیِ محصول/مقاله (نوع قطعه + برند/مدل خودرو + سال)
 * یه اسلاگِ کاملاً انگلیسیِ خوانا می‌سازه، مثلاً:
 *   «سپر جلو پژو 206»            -> peugeot-206-front-bumper
 *   «چراغ عقب سمند سورن (اورجینال)» -> samand-soren-original-taillight
 *   «رادیاتور آب کیا سراتو 2018»  -> kia-cerato-2018-water-radiator
 * ========================================================================= */
function ap_transliterate_title_to_slug($title) {
    $t = (string) $title;

    // اعداد فارسی/عربی -> انگلیسی
    $fa = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $ar = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $t = str_replace($fa, $en, $t);
    $t = str_replace($ar, $en, $t);

    // واژه‌های تکراری/رایج -> معادل انگلیسی (ترتیب مهمه: عبارت‌های بلندتر اول)
    $map = [
        // ---- نوع قطعه ----
        'سپر جلو' => 'front-bumper', 'سپر عقب' => 'rear-bumper', 'سپر' => 'bumper',
        'چراغ جلو' => 'headlight', 'چراغ عقب' => 'taillight', 'چراغ مه شکن' => 'fog-light',
        'چراغ مه‌شکن' => 'fog-light', 'چراغ' => 'light',
        'جلوپنجره' => 'grille', 'جلو پنجره' => 'grille',
        'درب موتور' => 'hood', 'کاپوت' => 'hood', 'درب صندوق' => 'trunk-lid', 'درب' => 'door',
        'گلگیر' => 'fender', 'رکاب' => 'rocker-panel',
        'رادیاتور آب' => 'water-radiator', 'رادیاتور کولر' => 'ac-condenser', 'رادیاتور بخاری' => 'heater-core',
        'رادیاتور' => 'radiator',
        'آینه بغل' => 'side-mirror', 'اینه بغل' => 'side-mirror', 'آینه' => 'mirror', 'اینه' => 'mirror',
        'شیشه جلو' => 'windshield', 'شیشه' => 'glass',
        'قطعات موتوری' => 'engine-parts', 'قطعات برقی' => 'electric-parts', 'درب موتور (کاور)' => 'engine-cover',
        'واشر سرسیلندر' => 'head-gasket', 'سرسیلندر' => 'cylinder-head', 'سیلندر' => 'cylinder',
        'واتر پمپ' => 'water-pump', 'واترپمپ' => 'water-pump', 'اویل پمپ' => 'oil-pump',
        'تسمه تایم' => 'timing-belt', 'تسمه دینام' => 'alternator-belt', 'تسمه' => 'belt',
        'لنت ترمز' => 'brake-pad', 'لنت' => 'brake-pad', 'دیسک ترمز' => 'brake-disc', 'دیسک' => 'disc',
        'کاسه چرخ' => 'brake-drum', 'کمک فنر' => 'shock-absorber', 'فنر لول' => 'coil-spring',
        'طبق' => 'control-arm', 'سیبک' => 'ball-joint', 'جعبه فرمان' => 'steering-rack',
        'دینام' => 'alternator', 'استارت' => 'starter', 'کویل' => 'coil', 'شمع' => 'spark-plug',
        'باتری' => 'battery', 'دیاگ' => 'diag', 'ای سی یو' => 'ecu', 'ایسیو' => 'ecu',
        'فیلتر روغن' => 'oil-filter', 'فیلتر هوا' => 'air-filter', 'فیلتر کابین' => 'cabin-filter',
        'فیلتر بنزین' => 'fuel-filter', 'فیلتر' => 'filter',
        'پمپ بنزین' => 'fuel-pump', 'انژکتور' => 'injector', 'سنسور' => 'sensor',
        'واشر' => 'gasket', 'بوش' => 'bush', 'یاتاقان' => 'bearing', 'پیستون' => 'piston',
        'اگزوز' => 'exhaust', 'منیفولد' => 'manifold', 'توربو' => 'turbo',
        'کلاچ' => 'clutch', 'گیربکس' => 'gearbox', 'دیفرانسیل' => 'differential',
        'پلوس' => 'driveshaft', 'چرخ' => 'wheel', 'رینگ' => 'rim', 'لاستیک' => 'tire',
        // ---- برند و مدل خودرو ----
        'ایران خودرو' => 'ikco', 'سایپا' => 'saipa', 'پارس خودرو' => 'pars-khodro',
        'مدیران خودرو' => 'modiran-khodro', 'کرمان موتور' => 'kerman-motor', 'گروه بهمن' => 'bahman',
        'پژو پارس' => 'peugeot-pars', 'پژو' => 'peugeot', 'پراید' => 'pride', 'سمند' => 'samand',
        'سورن' => 'soren', 'دنا' => 'dena', 'رانا' => 'rana', 'ساینا' => 'saina', 'کوییک' => 'quick',
        'تیبا' => 'tiba', 'شاهین' => 'shahin', 'اریسان' => 'arisan', 'وانت' => 'pickup',
        'کیا' => 'kia', 'سراتو' => 'cerato', 'اسپورتیج' => 'sportage',
        'هیوندای' => 'hyundai', 'النترا' => 'elantra', 'سانتافه' => 'santafe', 'توسان' => 'tucson',
        'تویوتا' => 'toyota', 'کرولا' => 'corolla', 'کمری' => 'camry',
        'نیسان' => 'nissan', 'رنو' => 'renault', 'ال نود' => 'l90', 'تندر' => 'tondar',
        'مزدا' => 'mazda', 'سوزوکی' => 'suzuki', 'سیتروئن' => 'citroen', 'زامیاد' => 'zamyad',
        'چری' => 'chery', 'آریزو' => 'arrizo', 'تیگو' => 'tiggo', 'ام وی ام' => 'mvm',
        'جک' => 'jac', 'هاوال' => 'haval', 'ام جی' => 'mg', 'لیفان' => 'lifan',
        'برلیانس' => 'brilliance', 'دانگ فنگ' => 'dongfeng', 'هایما' => 'haima',
        'چانگان' => 'changan', 'فونیکس' => 'fonix', 'زوتی' => 'zotye',
        // ---- کیفیت و توضیح ----
        'اورجینال' => 'original', 'افتر مارکت' => 'aftermarket', 'آفتر مارکت' => 'aftermarket',
        'های کپی' => 'copy', 'استوک' => 'stock', 'نو' => 'new', 'دست دوم' => 'used',
        'راست' => 'right', 'چپ' => 'left', 'جلو' => 'front', 'عقب' => 'rear',
        'مدل' => 'model', 'سال' => 'year', 'ست' => 'set', 'جفت' => 'pair',
    ];
    $t = strtr($t, $map);

    // نقطه‌ی اعشاری (۱۲.۹ اینچ) به خط تیره تبدیل بشه که در Slug مشکل ایجاد نکنه
    $t = str_replace('.', '-', $t);

    /* هر واژه‌ی فارسیِ دیگه‌ای که تو جدولِ بالا نبود، حرف‌به‌حرف به لاتین برگردونده می‌شه.
       قبلاً این حروف به sanitize_title می‌رسیدن و به‌صورتِ Percent-Encoded (%d8%a8...)
       توی اسلاگ می‌موندن؛ یعنی پیوندِ یکتا نصفه‌فارسی و ناخوانا می‌شد. */
    $letters = [
        'آ'=>'a','ا'=>'a','أ'=>'a','إ'=>'e','ب'=>'b','پ'=>'p','ت'=>'t','ث'=>'s','ج'=>'j','چ'=>'ch',
        'ح'=>'h','خ'=>'kh','د'=>'d','ذ'=>'z','ر'=>'r','ز'=>'z','ژ'=>'zh','س'=>'s','ش'=>'sh','ص'=>'s',
        'ض'=>'z','ط'=>'t','ظ'=>'z','ع'=>'a','غ'=>'gh','ف'=>'f','ق'=>'gh','ک'=>'k','ك'=>'k','گ'=>'g',
        'ل'=>'l','م'=>'m','ن'=>'n','و'=>'v','ه'=>'h','ة'=>'h','ی'=>'y','ي'=>'y','ئ'=>'y','ء'=>'',
        'َ'=>'','ِ'=>'','ُ'=>'','ً'=>'','ٍ'=>'','ٌ'=>'','ّ'=>'','ْ'=>'','‌'=>'-','ٔ'=>'',
    ];
    $t = strtr($t, $letters);

    $slug = sanitize_title($t);
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'item-' . substr(md5($title), 0, 6);
}

/* هر بار محصول یا مقاله‌ای ذخیره می‌شه (چه از پیشخوان، چه از درون‌ریزیِ CSV)،
   اسلاگش با تابع بالا به انگلیسی اجباری می‌شه؛ wp_unique_post_slug خودِ
   وردپرس هم در صورت تکراری‌بودن، به‌صورت خودکار -2 و -3 و... اضافه می‌کنه. */
add_filter('wp_insert_post_data', function ($data, $postarr) {
    if (in_array($data['post_type'], ['ap_product', 'ap_article'], true) && $data['post_status'] !== 'auto-draft') {
        // اگه post_name از قبل توسط خودمون (تابعِ بازتولیدِ دستیِ اسلاگ) صریحاً انگلیسی ست شده، دست نمی‌زنیم.
        /* اسلاگی که وردپرس از عنوانِ فارسی می‌سازه Percent-Encoded ئه (مثل %d8%a8...)
           که با اینکه فقط از حروفِ ASCII تشکیل شده، در واقع همون فارسیِ کدشده‌ست؛ پس
           وجودِ «%» یعنی هنوز انگلیسیِ خوانا نیست و باید نویسه‌گردانی بشه. */
        $alreadyAscii = $data['post_name'] !== '' && strpos($data['post_name'], '%') === false && $data['post_name'] === sanitize_title($data['post_name']) && !preg_match('/[^\x00-\x7f]/', $data['post_name']);
        if (!$alreadyAscii) {
            $baseSlug = ap_slug_from_title($data['post_title']);
            $postId = isset($postarr['ID']) ? (int) $postarr['ID'] : 0;
            $data['post_name'] = wp_unique_post_slug($baseSlug, $postId, $data['post_status'], $data['post_type'], $data['post_parent'] ?? 0);
        }
    }
    return $data;
}, 10, 2);

/* ---------- دسترسی به دیتابیس JSON فروشگاه (همون فایلی که api.php استفاده می‌کنه) ---------- */
function ap_data_file_path() {
    $dir = null;
    if (function_exists('wp_upload_dir')) {
        $u = wp_upload_dir();
        if (!empty($u['basedir']) && is_writable($u['basedir'])) { $dir = $u['basedir'] . '/ap-data'; }
    }
    if (!$dir) { $dir = get_template_directory() . '/data'; }
    if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
    return $dir . '/db.json';
}
function ap_read_data() {
    $file = ap_data_file_path();
    if (!file_exists($file)) return ['users' => [], 'orders' => [], 'products' => [], 'next_user_id' => 2, 'next_order_id' => 1, 'next_address_id' => 1];
    $raw = file_get_contents($file);
    $data = json_decode($raw, true);
    return is_array($data) ? $data : ['users' => []];
}
function ap_write_data($data) {
    $file = ap_data_file_path();
    // یک نسخه‌ی پشتیبان قبل از نوشتن، برای احتیاط
    if (file_exists($file)) {
        @copy($file, $file . '.bak-' . date('Ymd-His'));
        // فقط ۱۰ بکاپِ آخر نگه داشته می‌شه تا این فایل‌ها بی‌نهایت روی هاست تلنبار نشن.
        $baks = glob($file . '.bak-*');
        if ($baks && count($baks) > 10) {
            usort($baks, function ($a, $b) { return filemtime($a) <=> filemtime($b); });
            foreach (array_slice($baks, 0, count($baks) - 10) as $old) { @unlink($old); }
        }
    }
    file_put_contents($file, wp_json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}
function ap_get_all_customers() {
    $data = ap_read_data();
    $users = $data['users'] ?? [];
    return array_values(array_filter($users, function ($u) { return empty($u['is_admin']); }));
}
/* درون‌ریزیِ ردیف‌های مشتری: با شماره تماس پیدا/به‌روزرسانی می‌کنه؛ اگه نبود، مشتری جدید با یک
   رمز عبور پیش‌فرض تصادفی می‌سازه (بعداً از پیشخوان ← مشتریان قابل مشاهده‌ست). */
function ap_import_customers_rows($rows) {
    $data = ap_read_data();
    if (!isset($data['users'])) $data['users'] = [];
    if (!isset($data['next_user_id'])) $data['next_user_id'] = 2;
    $count = 0;
    foreach ($rows as $row) {
        $phone = $row['phone'];
        $found = null;
        foreach ($data['users'] as &$u) {
            if (($u['phone'] ?? '') === $phone) { $found = &$u; break; }
        }
        unset($u);
        if ($found) {
            if (!empty($row['firstName'])) $found['firstName'] = $row['firstName'];
            if (!empty($row['lastName']))  $found['lastName']  = $row['lastName'];
            if (!empty($row['email']))     $found['email']     = $row['email'];
            $target = &$found;
        } else {
            $randomPass = wp_generate_password(8, false);
            $data['users'][] = [
                'id' => $data['next_user_id'],
                'firstName' => $row['firstName'] ?: '',
                'lastName'  => $row['lastName'] ?: '',
                'phone' => $phone,
                'email' => $row['email'] ?: null,
                'google_id' => null,
                'password_hash' => password_hash($randomPass, PASSWORD_DEFAULT),
                'password_plain' => $randomPass,
                'is_admin' => false,
                'addresses' => [],
                'created_at' => current_time('c'),
            ];
            $data['next_user_id']++;
            $target = &$data['users'][count($data['users']) - 1];
        }
        if (!empty($row['address1']) || !empty($row['city'])) {
            if (!isset($data['next_address_id'])) $data['next_address_id'] = 1;
            if (!isset($target['addresses'])) $target['addresses'] = [];
            $target['addresses'][] = [
                'id' => $data['next_address_id']++,
                'label' => 'آدرس (درون‌ریزی‌شده)',
                'province' => $row['province'] ?: '',
                'city' => $row['city'] ?: '',
                'district' => '',
                'plaque' => '',
                'unit' => '',
                'postalCode' => $row['postalCode'] ?: '',
                'fullAddress' => $row['address1'] ?: '',
            ];
        }
        unset($target);
        $count++;
    }
    ap_write_data($data);
    return $count;
}

/* ---------- منوی یکپارچه‌ی فروشگاه در پیشخوان ----------
   محصولات، دسته‌بندی‌ها، کاربران و دیدگاه‌ها همه زیرمجموعه‌ی همین یک منو
   می‌شن تا نوار کناریِ پیشخوان تمیزتر و مرتب‌تر بمونه. */
add_action('admin_menu', function () {
    add_menu_page(
        'قطعات یدکی',
        'قطعات یدکی',
        'edit_posts',
        'ap-dashboard',
        function () {
            echo '<div class="wrap"><h1>پیشخوان فروشگاه</h1><p>از منوی کنار، محصولات، دسته‌بندی‌ها، کاربران و دیدگاه‌ها رو مدیریت کن.</p></div>';
        },
        'dashicons-store',
        3
    );
    add_submenu_page('ap-dashboard', 'دسته‌بندی محصولات', 'دسته‌بندی محصولات', 'manage_categories', 'edit-tags.php?taxonomy=ap_cat&post_type=ap_product');
    add_submenu_page('ap-dashboard', 'کاربران', 'کاربران', 'list_users', 'users.php');
    add_submenu_page('ap-dashboard', 'دیدگاه‌ها', 'دیدگاه‌ها', 'edit_posts', 'edit-comments.php');
    add_submenu_page('ap-dashboard', 'درون‌ریزی / برون‌ریزی', 'درون‌ریزی / برون‌ریزی', 'manage_options', 'ap-import-export', 'ap_import_export_page_html');
}, 9);

/* =========================================================================
 * درون‌ریزی / برون‌ریزی محصولات و مشتریان (فرمتِ CSV، مثل ووکامرس)
 * ========================================================================= */
/* ---------- یک بخشِ درون‌ریزی/برون‌ریزی با ۴ دکمه‌ی یکسان ----------
   ۱. انتخاب فایل CSV  ۲. درون‌ریزی (CSV)  ۳. برون‌ریزی (CSV)  ۴. حذف همه
   «حذف همه» در هر بخش فقط داده‌های همون بخش رو پاک می‌کنه، نه چیز دیگه‌ای از سایت. */
function ap_ie_section($key, $title, $help_html, $delete_confirm, $extra_html = '') {
    $import_action = 'ap_import_' . $key;
    $export_action = 'ap_export_' . $key;
    $delete_action = 'ap_delete_all_' . $key;
    $file_id = 'ap_csv_' . $key;
    ?>
    <div class="ap-ie-card">
        <h2><?php echo esc_html($title); ?></h2>
        <?php if ($help_html) : ?><div class="ap-ie-help"><?php echo $help_html; ?></div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field($import_action); ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($import_action); ?>">
            <?php /* input فایل مخفیه و با دکمه‌ی معمولی باز می‌شه (رفعِ باگِ کلیک‌نشدن روی سافاری موبایل). */ ?>
            <input type="file" name="ap_csv" id="<?php echo esc_attr($file_id); ?>" accept=".csv,text/csv" required
                   style="display:none;"
                   onchange="document.getElementById('<?php echo esc_js($file_id); ?>_name').textContent = this.files.length ? this.files[0].name : 'فایلی انتخاب نشده';">
            <?php echo $extra_html; ?>
            <div class="ap-ie-buttons">
                <button type="button" class="button" onclick="document.getElementById('<?php echo esc_js($file_id); ?>').click();">۱. انتخاب فایل CSV</button>
                <button type="submit" class="button button-primary">۲. درون‌ریزی (CSV)</button>
                <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . $export_action), $export_action)); ?>">۳. برون‌ریزی (CSV)</a>
                <a class="button ap-ie-delete"
                   href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=' . $delete_action), $delete_action)); ?>"
                   onclick="return confirm(<?php echo esc_attr(wp_json_encode($delete_confirm, JSON_UNESCAPED_UNICODE)); ?>);">۴. حذف همه</a>
            </div>
            <p class="ap-ie-filename" id="<?php echo esc_attr($file_id); ?>_name">فایلی انتخاب نشده</p>
        </form>
    </div>
    <?php
}

function ap_import_export_page_html() {
    $imported = isset($_GET['imported']) ? (int) $_GET['imported'] : null;
    $type = sanitize_key($_GET['type'] ?? '');
    ?>
    <div class="wrap">
        <style>
            .ap-ie-grid{display:grid; grid-template-columns:repeat(auto-fill,minmax(420px,1fr)); gap:18px; margin-top:16px;}
            @media (max-width:782px){ .ap-ie-grid{grid-template-columns:1fr;} }
            .ap-ie-card{background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:16px 18px;}
            .ap-ie-card h2{margin:0 0 8px; font-size:17px;}
            .ap-ie-help{color:#50575e; font-size:12.5px; line-height:1.9; margin-bottom:10px;}
            .ap-ie-help p{margin:0 0 6px;}
            .ap-ie-buttons{display:flex; flex-wrap:wrap; gap:8px; margin-top:10px;}
            .ap-ie-buttons .button{margin:0;}
            .ap-ie-delete{color:#b32d2e !important; border-color:#b32d2e !important;}
            .ap-ie-delete:hover{background:#fcf0f1 !important;}
            .ap-ie-filename{margin:8px 0 0; color:#666; font-size:12px;}
        </style>
        <h1>درون‌ریزی / برون‌ریزی</h1>

        <?php /* ---------- پیام‌های نتیجه ---------- */ ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="notice notice-success"><p><?php echo esc_html((int) $_GET['deleted']); ?> محصول حذف شد.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['catsdeleted'])): ?>
            <div class="notice notice-success"><p><?php echo esc_html((int) $_GET['catsdeleted']); ?> دسته‌بندی حذف شد.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['articlesdeleted'])): ?>
            <div class="notice notice-success"><p><?php echo esc_html((int) $_GET['articlesdeleted']); ?> مقاله حذف شد.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['usersdeleted'])): ?>
            <div class="notice notice-success"><p><?php echo esc_html((int) $_GET['usersdeleted']); ?> کاربر حذف شد.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['catsimported'])):
            $result = get_transient('ap_cat_import_result');
            delete_transient('ap_cat_import_result');
            $errs = is_array($result) ? ($result['errors'] ?? []) : [];
        ?>
            <div class="notice <?php echo (int) $_GET['catsimported'] > 0 ? 'notice-success' : 'notice-error'; ?>">
                <p><?php echo esc_html((int) $_GET['catsimported']); ?> دسته‌بندی از CSV درون‌ریزی/به‌روزرسانی شد.</p>
                <?php if ($errs): ?>
                    <p><strong>مواردی که وارد نشدن:</strong></p>
                    <ul style="list-style:disc; margin-inline-start:20px;">
                        <?php foreach ($errs as $e): ?><li><?php echo esc_html($e); ?></li><?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <?php if ($imported !== null):
            $typeLabels = ['customers' => 'کاربر', 'articles' => 'مقاله', 'products' => 'محصول'];
        ?>
            <div class="notice notice-success"><p><?php echo esc_html($imported); ?> مورد (<?php echo esc_html($typeLabels[$type] ?? 'محصول'); ?>) با موفقیت درون‌ریزی شد.</p></div>
            <?php if ($type === 'products' && !empty($_GET['skippedcats']) && (int) $_GET['skippedcats'] > 0): ?>
                <?php $missing = get_transient('ap_missing_cats'); delete_transient('ap_missing_cats'); ?>
                <div class="notice notice-warning">
                    <p>⚠️ <?php echo esc_html((int) $_GET['skippedcats']); ?> ارجاعِ دسته‌بندی در فایل با هیچ دسته‌بندیِ موجودی مطابقت نداشت و محصولِ مربوطه بدون اون دسته ثبت شد.</p>
                    <?php if (is_array($missing) && $missing): ?>
                        <p><strong>دسته‌هایی که پیدا نشدن:</strong></p>
                        <ul style="list-style:disc; margin-inline-start:20px;">
                            <?php foreach ($missing as $m): ?><li><?php echo esc_html($m); ?></li><?php endforeach; ?>
                        </ul>
                        <p>اول این‌ها رو در فایلِ دسته‌بندی‌ها اضافه و import کن، بعد دوباره محصولات رو import کن.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($_GET['cachecleared'])): ?>
            <div class="notice notice-success"><p>کشِ محصولات و دسته‌بندی‌ها پاک شد.</p></div>
        <?php endif; ?>
        <?php if (isset($_GET['slugsfixed'])): ?>
            <div class="notice notice-success"><p>پیوند یکتای <?php echo (int) $_GET['slugsfixed']; ?> دسته‌بندی/محصول/مقاله به انگلیسی تبدیل شد.</p></div>
        <?php endif; ?>

        <div class="ap-ie-grid">
        <?php
        /* ۱. محصولات */
        ap_ie_section(
            'products',
            '۱. محصولات',
            '<p>فرمت مثل CSV محصولات ووکامرس (Name/نام، Regular price/قیمت اصلی، Short description، Description، Categories، Images، Slug و ...). ستون‌های مخصوص قطعات: <code>Vehicles</code> به فرمت <code>peugeot &gt; peugeot-206 &gt; 1400</code> و <code>OEM Code</code>. گزینه‌ها: <code>Color Options</code>، <code>Storage Options</code> و <code>Variant Groups</code> به فرمت <code>نوع:روکاری اورجینال|های کپی;رنگ:سفید|مشکی</code>.</p>'
            . '<p>⚠️ ستون <code>Categories</code> باید مسیرِ کاملِ دسته‌ای باشه که از قبل ساخته شده (مثلاً <code>سپر &gt; سپر جلو &gt; پژو 206</code>)؛ درون‌ریزی دسته‌ی جدید نمی‌سازه.</p>',
            'مطمئنی؟ «همه‌ی محصولات» برای همیشه حذف می‌شن و قابل بازگشت نیست. دسته‌بندی‌ها، مقالات، کاربران، سفارش‌ها و تصاویرِ کتابخانه‌ی رسانه دست‌نخورده می‌مونن.',
            '<label style="display:block; font-size:12.5px; margin-top:4px;"><input type="checkbox" name="ap_download_images" value="1"> تصاویر در کتابخانه‌ی رسانه‌ی سایت دانلود بشن (کندتر)</label>'
        );

        /* ۲. دسته‌بندی */
        ap_ie_section(
            'categories',
            '۲. دسته‌بندی',
            '<p>ستون‌ها: <code>Name</code>، <code>Slug</code>، <code>Parent</code> (نام یا مسیرِ دسته‌ی والد با « &gt; »، خالی برای سطح اول)، <code>Description</code>، <code>Image</code>. خروجیِ همین بخش رو می‌تونی مستقیم دوباره درون‌ریزی کنی.</p>',
            'مطمئنی؟ «همه‌ی دسته‌بندی‌های محصولات» برای همیشه حذف می‌شن. خودِ محصولات، مقالات و کاربران حذف نمی‌شن (فقط محصولات بدون دسته‌بندی می‌مونن).'
        );

        /* ۳. مقالات */
        ap_ie_section(
            'articles',
            '۳. مقالات',
            '<p>ستون‌ها: <code>Title</code>، <code>Content</code> (می‌تونه تگ <code>&lt;img&gt;</code> وسط متن داشته باشه)، <code>Excerpt</code> (اختیاری)، <code>Image</code> (تصویر کاور، اختیاری)، <code>Slug</code> (اختیاری).</p>',
            'مطمئنی؟ «همه‌ی مقالات» برای همیشه حذف می‌شن. محصولات، دسته‌بندی‌ها، کاربران و صفحه‌ی «درباره ما» دست‌نخورده می‌مونن.'
        );

        /* ۴. کاربران */
        ap_ie_section(
            'customers',
            '۴. کاربران',
            '<p>ستون‌ها: <code>First Name</code>، <code>Last Name</code>، <code>Email</code>، <code>Phone</code> (یا Billing Phone)، <code>Billing Address 1</code>، <code>Billing City</code>، <code>Billing State</code>، <code>Billing Postcode</code>.</p>',
            'مطمئنی؟ «همه‌ی کاربران (مشتری‌ها)» برای همیشه حذف می‌شن. حساب مدیر، حساب‌های پیشخوان وردپرس، سفارش‌های ثبت‌شده، محصولات، دسته‌بندی‌ها و مقالات دست‌نخورده می‌مونن.'
        );
        ?>
        </div>

        <?php /* ابزارهای کمکیِ قبلی حذف نشدن تا قابلیتی از دست نره؛ فقط جدا و پایین صفحه قرار گرفتن. */ ?>
        <div style="margin-top:26px; padding-top:14px; border-top:1px solid #dcdcde;">
            <h3 style="margin:0 0 10px; font-size:14px;">ابزارهای کمکی</h3>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ap_fix_slugs'), 'ap_fix_slugs')); ?>"
               onclick="return confirm('پیوندِ یکتای همه‌ی محصولات و مقالاتی که الان فارسی/غیرانگلیسی هستن، به انگلیسی تبدیل می‌شه. ادامه بدم؟');">تبدیل پیوند یکتای محصولات و مقالات به انگلیسی</a>
            <a class="button" href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ap_clear_cache'), 'ap_clear_cache')); ?>">پاک کردن کشِ محصولات و دسته‌بندی‌ها</a>
        </div>
    </div>
    <?php
}

/* ---------- برون‌ریزی محصولات ---------- */
add_action('admin_post_ap_export_products', function () {
    if (!current_user_can('edit_posts')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_export_products');

    $posts = get_posts(['post_type' => 'ap_product', 'numberposts' => -1, 'post_status' => 'any']);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ap-products.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Type', 'SKU', 'Name', 'Published', 'Short description', 'Description', 'Regular price', 'Categories', 'Vehicles', 'OEM Code', 'Images', 'Color Options', 'Storage Options', 'Tags']);
    foreach ($posts as $p) {
        /* دسته‌ها به‌صورتِ «مسیرِ کاملِ اسلاگ» نوشته می‌شن (مثل iphone > iphone-parts > iphone-15)
           تا درون‌ریزیِ دوباره‌ی همین فایل، محصول رو دقیقاً زیرِ همون شاخه‌ی درست بذاره؛
           قبلاً فقط نامِ برگ نوشته می‌شد و موقعِ import پیدا نمی‌شد. */
        $catTerms = wp_get_post_terms($p->ID, 'ap_cat');
        $cats = [];
        if (!is_wp_error($catTerms)) {
            foreach ($catTerms as $ct) {
                $chain = [$ct->slug];
                $cur = $ct;
                $guard = 0;
                while ($cur->parent && $guard++ < 10) {
                    $cur = get_term($cur->parent, 'ap_cat');
                    if (!$cur || is_wp_error($cur)) break;
                    array_unshift($chain, $cur->slug);
                }
                $cats[] = implode(' > ', $chain);
            }
        }
        /* خودرو هم مثل دسته، به‌صورتِ مسیرِ کاملِ اسلاگ (brand > model > year) نوشته
           می‌شه تا درون‌ریزیِ همین فایل، قطعه رو دقیقاً به همون خودرو وصل کنه. */
        $vehTerms = wp_get_post_terms($p->ID, 'ap_vehicle');
        $vehs = [];
        if (!is_wp_error($vehTerms)) {
            foreach ($vehTerms as $vt) {
                $chain = [$vt->slug];
                $cur = $vt;
                $guard = 0;
                while ($cur->parent && $guard++ < 10) {
                    $cur = get_term($cur->parent, 'ap_vehicle');
                    if (!$cur || is_wp_error($cur)) break;
                    array_unshift($chain, $cur->slug);
                }
                $vehs[] = implode(' > ', $chain);
            }
        }
        $gallery = ap_gallery_urls($p->ID);
        fputcsv($out, [
            $p->ID,
            'simple',
            '',
            $p->post_title,
            $p->post_status === 'publish' ? 1 : 0,
            get_post_meta($p->ID, '_ap_short_desc', true),
            $p->post_content,
            get_post_meta($p->ID, '_ap_price', true),
            implode(', ', $cats),
            implode(', ', $vehs),
            get_post_meta($p->ID, '_ap_oem_code', true),
            implode(' | ', $gallery),
            get_post_meta($p->ID, '_ap_colors', true),
            get_post_meta($p->ID, '_ap_storages', true),
            str_replace('|', ', ', (string) get_post_meta($p->ID, '_ap_badge', true)),
        ]);
    }
    fclose($out);
    exit;
});

/* ---------- درون‌ریزی محصولات ---------- */
add_action('admin_post_ap_import_products', function () {
    if (!current_user_can('edit_posts')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_import_products');
    if (empty($_FILES['ap_csv']['tmp_name'])) {
        wp_redirect(admin_url('admin.php?page=ap-import-export'));
        exit;
    }

    // برای فایل‌های بزرگ (چند هزار محصول)، اجرای اسکریپت محدود به زمان پیش‌فرض نشه.
    @set_time_limit(0);
    @ignore_user_abort(true);

    $tmp_path = $_FILES['ap_csv']['tmp_name'];

    /* تشخیص خودکار جداکننده‌ی ستون‌ها: بعضی از خروجی‌های اکسل/ووکامرس به‌جای
       کاما از سمی‌کالن یا تب استفاده می‌کنن؛ اگه این تشخیص داده نشه، کل سطر
       به‌عنوان یک ستون خونده می‌شه و هیچ محصولی درون‌ریزی نمی‌شه (باگی که پیش اومد). */
    $first_line = '';
    $probe = fopen($tmp_path, 'r');
    if ($probe) { $first_line = (string) fgets($probe); fclose($probe); }
    $first_line = preg_replace('/^\xEF\xBB\xBF/', '', $first_line);
    $delimiter = ',';
    $counts = [',' => substr_count($first_line, ','), ';' => substr_count($first_line, ';'), "\t" => substr_count($first_line, "\t")];
    arsort($counts);
    $best = array_key_first($counts);
    if ($counts[$best] > 0) { $delimiter = $best; }

    $fh = fopen($tmp_path, 'r');
    $header = fgetcsv($fh, 0, $delimiter);
    if (!$header || count($header) < 2) {
        wp_die('ستون‌های فایل CSV به‌درستی تشخیص داده نشد. لطفاً مطمئن شو فایل واقعاً CSV هست (نه اکسل .xlsx) و دوباره امتحان کن.<br><a href="' . esc_url(admin_url('admin.php?page=ap-import-export')) . '">بازگشت</a>');
    }
    // حذف BOM احتمالی + یکدست‌سازی نام ستون‌ها (بدون‌حساسیت به فاصله‌ی اضافه و بزرگی/کوچکی حروف)
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $normalized = array_map(function ($h) { return strtolower(trim((string) $h)); }, $header);
    $idx = array_flip($normalized);

    $col = function ($row, $names) use ($idx) {
        foreach ((array) $names as $n) {
            $key = strtolower(trim($n));
            if (isset($idx[$key]) && isset($row[$idx[$key]])) return $row[$idx[$key]];
        }
        return '';
    };

    // اگه اصلاً ستونی برای نام محصول پیدا نشه، به‌جای درون‌ریزیِ صفر و بی‌صدا، خطای روشن بده.
    if (!isset($idx['name']) && !isset($idx['title']) && !isset($idx['نام'])) {
        wp_die(
            'ستونِ «Name» / «نام» توی فایل پیدا نشد، برای همین هیچ محصولی درون‌ریزی نشد.<br>' .
            'ستون‌هایی که توی فایلت پیدا کردم: ' . esc_html(implode(' | ', $header)) . '<br>' .
            '<a href="' . esc_url(admin_url('admin.php?page=ap-import-export')) . '">بازگشت</a>'
        );
    }

    $downloadImages = !empty($_POST['ap_download_images']);

    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $count = 0;
    $skippedCats = 0;
    $missingCats = [];   // نامِ دسته‌هایی که توی سایت پیدا نشدن، برای گزارش به مدیر
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (count($row) === 1 && trim((string) $row[0]) === '') continue; // ردیف خالی
        $name = trim((string) $col($row, ['Name', 'Title', 'نام']));
        if ($name === '') continue;

        $price = (int) preg_replace('/[^0-9]/', '', (string) $col($row, ['Regular price', 'Price', 'قیمت اصلی', 'قیمت']));
        $desc = (string) $col($row, ['Description', 'توضیحات']);
        $shortDesc = (string) $col($row, ['Short description', 'توضیح کوتاه']);
        $catsRaw = (string) $col($row, ['Categories', 'دسته‌ها', 'دسته ها']);
        $imagesRaw = (string) $col($row, ['Images', 'تصاویر']);
        /* خودرو: مسیرِ «برند > مدل > سال» با اسلاگ یا نام (چند خودرو با کاما).
           مثل دسته‌بندی، درون‌ریزی هیچ برند/مدل/سالِ جدیدی نمی‌سازه؛ فقط به
           موردی که از قبل در پیشخوان ثبت شده وصل می‌شه. */
        $vehiclesRaw = (string) $col($row, ['Vehicles', 'Vehicle', 'خودرو', 'خودروها', 'برند/مدل/سال']);
        $oemCode = trim((string) $col($row, ['OEM Code', 'OEM', 'SKU Code', 'کد فنی', 'کد قطعه']));
        $colorsRaw = (string) $col($row, ['Color Options', 'Colors', 'رنگ', 'رنگ‌ها', 'گزینه‌های رنگ']);
        $storagesRaw = (string) $col($row, ['Storage Options', 'Storage', 'حجم', 'ظرفیت', 'گزینه‌های حجم']);
        // برچسب‌ها (ستونِ Tags ووکامرس). چند برچسب با ویرگول، «،» یا «|» جدا می‌شن.
        $tagsRaw = (string) $col($row, ['Tags', 'Tag', 'Badges', 'Badge', 'برچسب', 'برچسب‌ها']);
        /* فرمت ستونِ گروه‌های متغیرِ سفارشی: هر گروه «برچسب:گزینه۱|گزینه۲|...» و بین گروه‌ها
           با ; جدا می‌شه؛ مثال: «نوع:روکاری اورجینال|های کپی» یا حتی چند گروه با هم:
           «نوع:روکاری اورجینال|های کپی;رنگ:سفید|مشکی». */
        $variantGroupsRaw = (string) $col($row, ['Variant Groups', 'گروه‌های متغیر', 'گروه های متغیر']);
        $publishedFlag = trim((string) $col($row, ['Published', 'منتشر شده']));
        $postStatus = ($publishedFlag === '0') ? 'draft' : 'publish';

        /* فیلدهای سئوی رنک‌مث: اگه توی فایل ستون‌های زیر موجود باشن، مستقیم
           روی متاباکسِ Rank Math ست می‌شن (کلمه‌ی کلیدی اصلی، عنوان سئو، توضیحات متا)
           تا لازم نباشه بعد از هر درون‌ریزی، تک‌تکِ محصولات دستی ویرایش بشن. */
        $seoFocusKeyword = trim((string) $col($row, ['Focus Keyword', 'کلمه کلیدی', 'کلمه کلیدی اصلی']));
        $seoTitle        = trim((string) $col($row, ['SEO Title', 'عنوان سئو']));
        $seoDescription  = trim((string) $col($row, ['Meta Description', 'توضیحات متا']));

        /* اگه ستونِ Slug توی فایل پر شده باشه، همون عیناً (فقط sanitize ساده) به‌عنوان
           پیوند یکتا استفاده می‌شه؛ وگرنه فیلترِ خودکارِ نویسه‌گردانیِ فارسی-به-انگلیسی
           (ap_transliterate_title_to_slug) از روی عنوان می‌سازتش. این ستون برای
           کنترلِ دقیق‌تر روی پیوندهای انگلیسی هست، مخصوصاً وقتی عنوان طولانی/جمله‌ایه. */
        $explicitSlug = trim((string) $col($row, ['Slug', 'پیوند یکتا', 'اسلاگ']));
        $explicitSlug = $explicitSlug !== '' ? sanitize_title($explicitSlug) : '';

        $existingId = (int) trim((string) $col($row, ['ID', 'شناسه']));
        $postArrCommon = [
            'post_title'   => sanitize_text_field($name),
            'post_content' => wp_kses_post($desc),
            'post_status'  => $postStatus,
        ];
        if ($explicitSlug !== '') { $postArrCommon['post_name'] = $explicitSlug; }

        if ($existingId && get_post_type($existingId) === 'ap_product') {
            wp_update_post(['ID' => $existingId] + $postArrCommon);
            $post_id = $existingId;
        } else {
            $post_id = wp_insert_post(['post_type' => 'ap_product'] + $postArrCommon);
        }
        if (is_wp_error($post_id) || !$post_id) continue;

        ap_save_price_with_date($post_id, $price);
        update_post_meta($post_id, '_ap_short_desc', sanitize_textarea_field($shortDesc));
        if ($colorsRaw !== '') update_post_meta($post_id, '_ap_colors', sanitize_text_field($colorsRaw));
        if ($storagesRaw !== '') update_post_meta($post_id, '_ap_storages', sanitize_text_field($storagesRaw));
        if (trim($tagsRaw) !== '') {
            $tagList = preg_split('/\s*[,،|]\s*/u', $tagsRaw);
            $tagList = array_values(array_unique(array_filter(array_map(function ($t) {
                return trim(sanitize_text_field($t));
            }, $tagList), 'strlen')));
            update_post_meta($post_id, '_ap_badge', implode('|', $tagList));
        }
        if ($variantGroupsRaw !== '') {
            $groups = [];
            foreach (explode(';', $variantGroupsRaw) as $groupChunk) {
                $groupChunk = trim($groupChunk);
                if ($groupChunk === '' || strpos($groupChunk, ':') === false) continue;
                [$gLabel, $gOptionsRaw] = array_map('trim', explode(':', $groupChunk, 2));
                $gOptions = array_values(array_filter(array_map('trim', explode('|', $gOptionsRaw))));
                if ($gLabel === '' || !$gOptions) continue;
                $groups[] = ['label' => sanitize_text_field($gLabel), 'options' => array_map('sanitize_text_field', $gOptions)];
            }
            if ($groups) update_post_meta($post_id, '_ap_variant_groups', wp_json_encode($groups, JSON_UNESCAPED_UNICODE));
        }
        if (!get_post_meta($post_id, '_ap_color', true)) update_post_meta($post_id, '_ap_color', '#ff3b4e');
        if (!get_post_meta($post_id, '_ap_icon', true)) update_post_meta($post_id, '_ap_icon', 'disc');

        /* ذخیره‌ی فیلدهای Rank Math (پلاگین این کلیدهای متا رو خودش می‌خونه و توی
           متاباکسِ سئوی صفحه‌ی ویرایش محصول نشون می‌ده - نیازی به تنظیم دستی نیست) */
        if ($seoFocusKeyword !== '') update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($seoFocusKeyword));
        if ($seoTitle !== '') update_post_meta($post_id, 'rank_math_title', sanitize_text_field($seoTitle));
        if ($seoDescription !== '') update_post_meta($post_id, 'rank_math_description', sanitize_textarea_field($seoDescription));

        /* دسته‌بندی‌ها: با کاما جدا از هم، و با « > » برای سلسله‌مراتب (دقیقاً فرمت ووکامرس).
           مهم: درون‌ریزیِ محصول هرگز دسته‌بندیِ جدید نمی‌سازه — فقط به دسته‌بندیِ
           از قبل موجود (که از صفحه‌ی «دسته‌بندی‌ها» ساخته شده) وصل می‌شه. اگه مسیر
           دقیقاً پیدا نشه، اون محصول بدون دسته‌بندی می‌مونه (skippedCats زیاد می‌شه)
           به‌جای اینکه یه دسته‌بندیِ نصفه‌ونیمه یا اشتباه بسازه. */
        if ($catsRaw !== '') {
            $termIds = [];
            foreach (explode(',', $catsRaw) as $catPath) {
                if (trim($catPath) === '') continue;
                // نامِ خودِ محصول به حل‌کننده داده می‌شه تا بینِ دسته‌های هم‌نام، درست رو انتخاب کنه
                $leafId = ap_resolve_cat_for_product($catPath, $name);
                if ($leafId) { $termIds[] = $leafId; }
                else { $skippedCats++; $missingCats[trim($catPath)] = true; }
            }
            $termIds = array_values(array_unique($termIds));
            if ($termIds) wp_set_post_terms($post_id, $termIds, 'ap_cat');
        }

        /* کد فنی (OEM) قطعه */
        if ($oemCode !== '') { update_post_meta($post_id, '_ap_oem_code', sanitize_text_field($oemCode)); }

        /* خودرو (برند ← مدل ← سال): هر مسیر جدا با کاما؛ داخل هر مسیر با « > ». */
        if ($vehiclesRaw !== '') {
            $vehIds = [];
            foreach (explode(',', $vehiclesRaw) as $vehPath) {
                $vehPath = trim($vehPath);
                if ($vehPath === '') continue;
                $parts = array_values(array_filter(array_map('trim', explode('>', $vehPath)), 'strlen'));
                $parentId = 0;
                $leafId = 0;
                foreach ($parts as $part) {
                    $found = get_terms([
                        'taxonomy'   => 'ap_vehicle',
                        'hide_empty' => false,
                        'parent'     => $parentId,
                        'slug'       => sanitize_title($part),
                    ]);
                    if (is_wp_error($found) || !$found) {
                        // با اسلاگ پیدا نشد؛ با نام هم امتحان کن
                        $byName = get_terms([
                            'taxonomy'   => 'ap_vehicle',
                            'hide_empty' => false,
                            'parent'     => $parentId,
                            'name'       => $part,
                        ]);
                        $found = (!is_wp_error($byName) && $byName) ? $byName : [];
                    }
                    if (!$found) { $leafId = 0; break; }
                    $leafId   = (int) $found[0]->term_id;
                    $parentId = $leafId;
                }
                if ($leafId) { $vehIds[] = $leafId; }
            }
            $vehIds = array_values(array_unique($vehIds));
            if ($vehIds) { wp_set_post_terms($post_id, $vehIds, 'ap_vehicle'); }
        }

        /* تصاویر: با « | » جدا از هم (فرمت ووکامرس).
           حالت پیش‌فرض (سریع): فقط آدرس تصویرها ذخیره می‌شه و مستقیم از همون سایت
           قبلی نمایش داده می‌شن؛ هیچ دانلودی انجام نمی‌شه، برای همین با هزاران
           محصول هم درون‌ریزی خیلی سریع تمومه.
           حالت اختیاری (کند): واقعاً دانلود و توی کتابخانه‌ی رسانه‌ی خودِ سایت ذخیره می‌شه. */
        if ($imagesRaw !== '') {
            $urls = array_filter(array_map('trim', explode('|', $imagesRaw)));
            if ($downloadImages) {
                $attIds = [];
                foreach ($urls as $url) {
                    $attId = media_sideload_image($url, $post_id, null, 'id');
                    if (!is_wp_error($attId)) $attIds[] = $attId;
                }
                if ($attIds) {
                    set_post_thumbnail($post_id, $attIds[0]);
                    update_post_meta($post_id, '_ap_gallery', implode(',', $attIds));
                }
            } elseif ($urls) {
                update_post_meta($post_id, '_ap_gallery', implode(',', array_map('rawurlencode', $urls)));
            }
        }

        $count++;
    }
    fclose($fh);
    delete_transient('ap_products_cache_v3');
    if ($missingCats) set_transient('ap_missing_cats', array_keys($missingCats), 120);
    wp_redirect(admin_url('admin.php?page=ap-import-export&imported=' . $count . '&type=products&skippedcats=' . $skippedCats));
    exit;
});

/* ---------- حذف همه‌ی محصولات ---------- */
add_action('admin_post_ap_delete_all_products', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_delete_all_products');
    @set_time_limit(0);

    $ids = get_posts(['post_type' => 'ap_product', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids']);
    foreach ($ids as $id) {
        wp_delete_post($id, true); // true = واقعاً حذف بشه، نره توی زباله‌دان
    }
    delete_transient('ap_products_cache_v3'); // مهم: وگرنه لیستِ کش‌شده‌ی قدیمی هنوز روی سایت نشون داده می‌شه
    delete_transient('ap_categories_cache_v1'); // تعدادِ کالای هر دسته عوض شده؛ خودِ دسته‌ها حذف نمی‌شن
    wp_redirect(admin_url('admin.php?page=ap-import-export&deleted=' . count($ids)));
    exit;
});

/* ---------- حذف همه‌ی مقالات ----------
   فقط نوع‌پستِ ap_article حذف می‌شه؛ برگه‌ها (مثل «درباره ما»)، نوشته‌های معمولی،
   محصولات، دسته‌بندی‌ها، کاربران و فایل‌های کتابخانه‌ی رسانه دست‌نخورده می‌مونن. */
add_action('admin_post_ap_delete_all_articles', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_delete_all_articles');
    @set_time_limit(0);

    $ids = get_posts(['post_type' => 'ap_article', 'numberposts' => -1, 'post_status' => 'any', 'fields' => 'ids']);
    $n = 0;
    foreach ($ids as $id) {
        // دوباره چک می‌کنیم که واقعاً مقاله باشه، تا هیچ‌وقت چیزِ دیگه‌ای حذف نشه
        if (get_post_type($id) === 'ap_article' && wp_delete_post($id, true)) { $n++; }
    }
    wp_redirect(admin_url('admin.php?page=ap-import-export&articlesdeleted=' . $n));
    exit;
});

/* ---------- حذف همه‌ی کاربران (مشتری‌ها) ----------
   فقط مشتری‌های دیتابیسِ فروشگاه حذف می‌شن. این‌ها دست‌نخورده می‌مونن:
   - حساب مدیر (is_admin)
   - حساب‌های پیشخوانِ وردپرس (wp_users) — این عملیات اصلاً به اون‌ها دست نمی‌زنه
   - سفارش‌های ثبت‌شده (نام و آدرسِ مشتری داخلِ خودِ سفارش ذخیره شده، پس فاکتورها سالم می‌مونن)
   - محصولات، دسته‌بندی‌ها، مقالات و بقیه‌ی تنظیمات دیتابیس */
add_action('admin_post_ap_delete_all_customers', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_delete_all_customers');

    $data = ap_read_data();
    $users = isset($data['users']) && is_array($data['users']) ? $data['users'] : [];
    $admins = array_values(array_filter($users, function ($u) { return !empty($u['is_admin']); }));
    $removed = count($users) - count($admins);

    if ($removed > 0) {
        $data['users'] = $admins;          // فقط لیستِ کاربران عوض می‌شه؛ بقیه‌ی کلیدهای دیتابیس همون‌طور می‌مونن
        ap_write_data($data);            // قبل از نوشتن، خودکار یک نسخه‌ی پشتیبان ساخته می‌شه
    }
    wp_redirect(admin_url('admin.php?page=ap-import-export&usersdeleted=' . $removed));
    exit;
});

/* ---------- تبدیل پیوند یکتای همه‌ی محصولات و مقالاتِ موجود به انگلیسی ----------
   این دکمه برای محصولات/مقاله‌هایی لازمه که قبل از فعال‌شدنِ قانونِ اسلاگ انگلیسی
   ساخته شدن و هنوز اسلاگِ فارسی/Percent-Encoded دارن. برای هر پستی که اسلاگش
   غیر-ASCII باشه، از روی همون عنوان دوباره وردپرس رو صدا می‌زنیم تا فیلترِ
   ap_transliterate_title_to_slug اجرا و اسلاگِ انگلیسیِ جدید جایگزین بشه. */
add_action('admin_post_ap_fix_slugs', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_fix_slugs');
    @set_time_limit(0);

    $fixed = ap_fix_all_category_slugs();
    foreach (['ap_product', 'ap_article'] as $pt) {
        $posts = get_posts(['post_type' => $pt, 'numberposts' => -1, 'post_status' => 'any']);
        foreach ($posts as $p) {
            if (preg_match('/[^\x00-\x7f]/', $p->post_name) || $p->post_name === '') {
                wp_update_post(['ID' => $p->ID, 'post_name' => '']); // خالی می‌ذاریم تا فیلترِ wp_insert_post_data از روی عنوان از نو بسازتش
                $fixed++;
            }
        }
    }
    wp_redirect(admin_url('admin.php?page=ap-import-export&slugsfixed=' . $fixed));
    exit;
});

/* ---------- حذف همیشگیِ محصولات «بدنه (شاسی)» ----------
   طبق درخواستِ صریح، این خط محصول کلاً از سایت حذف می‌شه (نه فقط پیش‌نویس). */
add_action('admin_post_ap_delete_chassis', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_delete_chassis');

    $posts = get_posts([
        'post_type'      => 'ap_product',
        'numberposts'    => -1,
        'post_status'    => 'any',
        's'              => 'بدنه',
    ]);
    $count = 0;
    foreach ($posts as $p) {
        if (mb_strpos($p->post_title, 'بدنه') === 0) {
            wp_delete_post($p->ID, true);
            $count++;
        }
    }
    delete_transient('ap_products_cache_v3');
    wp_redirect(admin_url('admin.php?page=ap-import-export&chassisdeleted=' . $count));
    exit;
});

/* ---------- پاک کردنِ دستیِ کامل کش (برای وقتی که مطمئن نیستیم چرا سایت داده‌ی قدیمی نشون می‌ده) ---------- */
add_action('admin_post_ap_clear_cache', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_clear_cache');
    delete_transient('ap_products_cache_v3');
    delete_transient('ap_categories_cache_v1');
    flush_rewrite_rules();
    wp_redirect(admin_url('admin.php?page=ap-import-export&cachecleared=1'));
    exit;
});

/* ---------- حذف همه‌ی دسته‌بندی‌ها ---------- */
add_action('admin_post_ap_delete_all_categories', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_delete_all_categories');
    @set_time_limit(0);

    $terms = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false, 'fields' => 'ids']);
    $n = 0;
    if (!is_wp_error($terms)) {
        foreach ($terms as $tid) { if (wp_delete_term($tid, 'ap_cat')) $n++; }
    }
    delete_transient('ap_categories_cache_v1'); // مهم: وگرنه درخت دسته‌بندیِ قدیمی هنوز کش شده و روی سایت می‌مونه
    delete_transient('ap_products_cache_v3'); // چون لیستِ محصولاتِ کش‌شده هم اسمِ دسته‌ها رو داخل خودش داره
    wp_redirect(admin_url('admin.php?page=ap-import-export&catsdeleted=' . $n));
    exit;
});

/* ---------- ساختن سریعِ ساختار دسته‌بندی‌ها (بدون محصول) — همچنان از راه کد قابل استفاده‌ست ---------- */
add_action('admin_post_ap_bulk_categories', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_bulk_categories');
    @set_time_limit(0);

    $paths = explode("\n", (string) ($_POST['ap_cat_paths'] ?? ''));
    $count = 0;
    foreach ($paths as $path) {
        $parts = array_filter(array_map('trim', explode('>', $path)));
        if (!$parts) continue;
        $parentId = 0;
        foreach ($parts as $partName) {
            $existing = term_exists($partName, 'ap_cat', $parentId);
            if ($existing) {
                $termId = (int) (is_array($existing) ? $existing['term_id'] : $existing);
            } else {
                $created = wp_insert_term($partName, 'ap_cat', ['parent' => $parentId]);
                $termId = is_wp_error($created) ? 0 : (int) $created['term_id'];
                if ($termId) $count++;
            }
            if ($termId) $parentId = $termId;
        }
    }
    wp_redirect(admin_url('admin.php?page=ap-import-export&catscreated=' . $count));
    exit;
});

/* ---------- برون‌ریزی دسته‌بندی‌ها (CSV، مثل ووکامرس) ---------- */
add_action('admin_post_ap_export_categories', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_export_categories');
    @set_time_limit(0);

    $terms = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false]);
    if (is_wp_error($terms)) $terms = [];

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="ap-categories-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM تا اکسل فارسی رو درست نشون بده
    fputcsv($out, ['ID', 'Name', 'Slug', 'Parent', 'Description', 'Image']);
    foreach ($terms as $t) {
        /* والد با «اسلاگ» نوشته می‌شه، نه نام: اسلاگ یکتاست و وقتی چند دسته‌ی هم‌نام
           زیرِ شاخه‌های مختلف داریم، درون‌ریزیِ دوباره‌ی همین فایل دقیقاً همون ساختار
           رو بازمی‌سازه. (درون‌ریزی نامِ دسته رو هم مثل قبل قبول می‌کنه.) */
        $parentName = '';
        if ($t->parent) {
            $p = get_term($t->parent, 'ap_cat');
            if ($p && !is_wp_error($p)) $parentName = $p->slug;
        }
        $thumbId = get_term_meta($t->term_id, 'ap_cat_image', true);
        $imageUrl = $thumbId ? wp_get_attachment_url($thumbId) : '';
        fputcsv($out, [$t->term_id, $t->name, $t->slug, $parentName, $t->description, $imageUrl]);
    }
    fclose($out);
    exit;
});

/* ---------- درون‌ریزی دسته‌بندی‌ها از CSV (مثل ووکامرس) ----------
 * ستون‌ها: Name، Slug (اختیاری)، Parent (نامِ دسته‌ی والد یا خالی)، Description، Image.
 * چون ترتیبِ ردیف‌های فایل ممکنه دسته‌ی والد رو قبل یا بعد از فرزندش بیاره،
 * تا ۵ دور روی فایل عبور می‌کنه تا هر والدی که هنوز ساخته نشده، توی دورِ بعد پیدا بشه. */
add_action('admin_post_ap_import_categories', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_import_categories');
    @set_time_limit(0);

    if (empty($_FILES['ap_csv']['tmp_name']) || !is_uploaded_file($_FILES['ap_csv']['tmp_name'])) {
        set_transient('ap_cat_import_result', ['count' => 0, 'errors' => ['هیچ فایلی دریافت نشد؛ دوباره فایل رو انتخاب و آپلود کن.']], 60);
        wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=0'));
        exit;
    }

    // پاک‌سازیِ رشته: حذفِ BOM و کاراکترهای جهتیِ نامرئی (RLM/LRM) که کپی‌پیست از
    // ورد/اکسل فارسی معمولاً همراه متن میاره و باعث می‌شه مقایسه‌ی متنِ والد/فرزند
    // با اینکه چشمی یکی به نظر میان، از نظر کد یکی نباشن و درون‌ریزی ساکت شکست بخوره.
    $clean = function ($s) {
        $s = (string) $s;
        $s = str_replace(["\xEF\xBB\xBF", "\xE2\x80\x8E", "\xE2\x80\x8F"], '', $s); // BOM, LRM, RLM
        return trim($s);
    };

    $raw = file_get_contents($_FILES['ap_csv']['tmp_name']);
    if ($raw === false || trim($raw) === '') {
        set_transient('ap_cat_import_result', ['count' => 0, 'errors' => ['فایل خالیه یا خونده نشد.']], 60);
        wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=0'));
        exit;
    }
    // اگه فایل با انکودینگِ ویندوزیِ فارسی (نه UTF-8) ذخیره شده باشه (مثلاً از اکسل
    // ویندوز بدون انتخاب "UTF-8")، تبدیلش می‌کنیم تا حروف فارسی خراب نشن.
    if (function_exists('mb_detect_encoding') && !mb_check_encoding($raw, 'UTF-8')) {
        $converted = function_exists('iconv') ? @iconv('Windows-1256', 'UTF-8//IGNORE', $raw) : false;
        if ($converted) $raw = $converted;
    }

    // تشخیص خودکارِ جداکننده‌ی ستون‌ها؛ بعضی اکسل‌های فارسی به‌جای کاما از سِمی‌کالن استفاده می‌کنن.
    $firstLine = strtok($raw, "\r\n");
    $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';

    $fh = fopen('php://temp', 'r+');
    fwrite($fh, $raw);
    rewind($fh);

    $header = fgetcsv($fh, 0, $delimiter);
    if (!$header) {
        set_transient('ap_cat_import_result', ['count' => 0, 'errors' => ['ردیفِ سرستون (Name, Slug, Parent, ...) پیدا نشد.']], 60);
        wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=0'));
        exit;
    }
    $colIndex = [];
    foreach ($header as $i => $h) { $colIndex[strtolower($clean($h))] = $i; }
    if (!isset($colIndex['name'])) {
        set_transient('ap_cat_import_result', ['count' => 0, 'errors' => ['ستونِ Name توی فایل پیدا نشد. سرستون‌های فایل باید دقیقاً Name, Slug, Parent, Description, Image باشن.']], 60);
        wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=0'));
        exit;
    }
    $get = function ($row, $key) use ($colIndex, $clean) {
        $key = strtolower($key);
        return isset($colIndex[$key], $row[$colIndex[$key]]) ? $clean($row[$colIndex[$key]]) : '';
    };

    $rows = [];
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (count($row) === 1 && $clean($row[0]) === '') continue;
        $rows[] = $row;
    }
    fclose($fh);

    if (!$rows) {
        set_transient('ap_cat_import_result', ['count' => 0, 'errors' => ['بعد از ردیفِ سرستون، هیچ ردیفِ داده‌ای توی فایل پیدا نشد.']], 60);
        wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=0'));
        exit;
    }

    /* دسته‌بندی‌ها ممکنه هم‌نام باشن ولی زیرِ والدهای متفاوت (مثلاً «آیفون سری ۴» هم زیرِ
       «آیفون» باشه، هم زیرِ «قطعات»، هم زیرِ «اکسسوری»). قبلاً والد فقط با نامِ ساده پیدا
       می‌شد (get_term_by('name', ...)) که چون بینِ چند ترمِ هم‌نام فرق نمی‌ذاره، همیشه یکی
       از اون‌ها رو برمی‌گردوند؛ در نتیجه هر ردیفِ بعدی با همون اسم، والدِ ترمِ از‌قبل‌ساخته‌شده
       رو عوض می‌کرد و همه‌چیز آخرش زیرِ آخرین شاخه‌ی پردازش‌شده جمع می‌شد (برای همینه که توی
       فیلتر پیشرفته «آیفون» و «قطعات» زیرمجموعه نداشتن ولی «اکسسوری» داشت).
       برای رفع این مشکل، ستونِ Parent می‌تونه یک مسیرِ کامل با « > » باشه (مثلاً
       «قطعات > آیفون سری 4») تا والدِ درست، بین چند ترمِ هم‌نام هم به‌درستی پیدا بشه؛
       تک‌نام هم مثلِ قبل کار می‌کنه. جست‌وجوی هر پاره از مسیر هم با term_exists($name, tax,
       $parentId) انجام می‌شه که والد رو در نظر می‌گیره، نه فقط نام. */
    $resolveTermInParent = function ($name, $parentId) {
        return ap_find_cat_in_parent($name, $parentId);
    };

    $count = 0;
    $orderCounter = 0;
    $errors = [];
    for ($pass = 0; $pass < 8 && $rows; $pass++) {
        $remaining = [];
        foreach ($rows as $row) {
            $name = $get($row, 'name');
            if ($name === '') continue;
            $slug = $get($row, 'slug');
            $parentPath = $get($row, 'parent');
            $desc = $get($row, 'description');
            $imageUrl = $get($row, 'image');

            $parentId = 0;
            $parentUnresolved = false;
            if ($parentPath !== '') {
                $parts = array_filter(array_map('trim', explode('>', $parentPath)));
                // اسلاگ و نامِ خودِ این ردیف، به‌عنوان سرنخ برای انتخابِ والدِ درست بینِ هم‌نام‌ها
                $ctx = $slug . ' ' . $name;
                foreach ($parts as $partName) {
                    $pid = ap_find_cat_by_name_ctx($partName, $parentId, $ctx);
                    if (!$pid) { $parentUnresolved = true; break; }
                    $parentId = $pid;
                }
            }
            if ($parentUnresolved) {
                $remaining[] = $row; // والدش هنوز ساخته نشده؛ دور بعد دوباره امتحان کن
                continue;
            }

            // بررسیِ وجودِ خودِ ترم هم باید محدود به همین والد باشه، نه هر جای درخت؛
            // وگرنه یک ترمِ هم‌نامِ زیرِ والدِ دیگه پیدا و جابه‌جا می‌شه.
            /* شناساییِ ردیف: اگه ستونِ Slug پر باشه، «فقط و فقط» با همون اسلاگ شناسایی می‌شه.
               نامِ دسته هیچ‌وقت ملاک نیست، چون توی این فروشگاه ده‌ها دسته‌ی هم‌نام زیرِ
               شاخه‌های مختلف هست («سری 1» زیرِ قطعات/خرید/لوازم جانبی) و شناساییِ نامی
               باعث می‌شد ردیف‌ها روی هم بیفتن و از ۴۵۴ دسته فقط ۹۳ تا ساخته بشه. */
            $existing = null;
            if ($slug !== '') {
                $bySlug = get_term_by('slug', $slug, 'ap_cat');
                if ($bySlug) $existing = $bySlug;
            } else {
                $foundId = ap_find_cat_in_parent($name, $parentId, false);
                if ($foundId) $existing = get_term($foundId, 'ap_cat');
            }
            $args = ['description' => $desc, 'parent' => $parentId];
            if ($slug !== '') $args['slug'] = $slug;

            if ($existing && !is_wp_error($existing)) {
                $updated = wp_update_term($existing->term_id, 'ap_cat', array_merge(['name' => $name], $args));
                $termId = is_wp_error($updated) ? $existing->term_id : $existing->term_id;
            } else {
                $created = wp_insert_term($name, 'ap_cat', $args);
                if (is_wp_error($created)) {
                    /* خطای term_exists یعنی ترمی با همین نام از قبل هست. این فروشگاه پر از
                       دسته‌های هم‌نام زیرِ شاخه‌های مختلفه («سری 1» هم زیرِ قطعات، هم خرید،
                       هم لوازم جانبی)، پس اون ترمِ موجود رو جابه‌جا نمی‌کنیم — این کار قبلاً
                       باعث می‌شد ۴۵۴ ردیفِ فایل آخرش فقط ~۹۰ دسته بسازن و درخت به هم بریزه.
                       به‌جاش اگه واقعاً همون ترمِ این ردیفه (اسلاگ یا والدش یکیه) به‌روزش
                       می‌کنیم، وگرنه یک ترمِ تازه با اسلاگِ یکتا ساخته می‌شه. */
                    $data = (int) $created->get_error_data('term_exists');
                    $same = $data ? get_term($data, 'ap_cat') : null;
                    $isSameRow = $same && !is_wp_error($same) && (
                        ($slug !== '' && $same->slug === $slug)                    // دقیقاً همین ردیف
                        || ($slug === '' && (int) $same->parent === (int) $parentId)
                    );
                    if ($isSameRow) {
                        $termId = (int) $same->term_id;
                        wp_update_term($termId, 'ap_cat', array_merge(['name' => $name], $args));
                    } else {
                        $uniqueSlug = $slug !== '' ? $slug : sanitize_title($name);
                        if ($uniqueSlug === '' ) $uniqueSlug = 'cat';
                        $try = $uniqueSlug; $i = 2;
                        while (get_term_by('slug', $try, 'ap_cat') && $i < 50) { $try = $uniqueSlug . '-' . $i; $i++; }
                        $retry = wp_insert_term($name, 'ap_cat', array_merge($args, ['slug' => $try]));
                        if (is_wp_error($retry)) {
                            $errors[] = $name . ': ' . $retry->get_error_message();
                            continue;
                        }
                        $termId = (int) $retry['term_id'];
                    }
                } else {
                    $termId = (int) $created['term_id'];
                }
            }

            /* وردپرس موقعِ ساختِ دسته‌ی هم‌نام، اسلاگ رو خودش عوض می‌کنه و اسلاگِ والد رو
               بهش می‌چسبونه. نتیجه‌اش این بود که ردیفِ بعدیِ فایل، اسلاگِ خودش رو روی همین
               ترم پیدا می‌کرد و به‌جای ساختِ دسته‌ی جدید، همین رو بازنویسی می‌کرد — برای همین
               ۴۵۴ ردیف آخرش ۹۳ دسته می‌شد. این‌جا اسلاگِ خودِ ردیف دوباره اعمال می‌شه. */
            if ($slug !== '') {
                $cur = get_term($termId, 'ap_cat');
                if ($cur && !is_wp_error($cur) && $cur->slug !== $slug) {
                    $owner = get_term_by('slug', $slug, 'ap_cat');
                    if (!$owner || (int) $owner->term_id === $termId) {
                        wp_update_term($termId, 'ap_cat', ['slug' => $slug]);
                    }
                }
            }

            // ترتیبِ چینش: همون ترتیبِ ردیف‌های فایل، طوری که در فیلتر پیشرفته‌ی سایت هم
            // به همین ترتیب نشون داده بشه (نه ترتیبِ الفباییِ پیش‌فرضِ وردپرس).
            update_term_meta($termId, 'ap_cat_order', $orderCounter++);

            if ($imageUrl !== '' && function_exists('media_sideload_image')) {
                $attId = media_sideload_image($imageUrl, 0, null, 'id');
                if (!is_wp_error($attId)) update_term_meta($termId, 'ap_cat_image', $attId);
            }
            $count++;
        }
        $rows = $remaining;
    }
    foreach ($rows as $leftover) {
        $errors[] = $get($leftover, 'name') . ': دسته‌ی والدش («' . $get($leftover, 'parent') . '») پیدا نشد.';
    }

    set_transient('ap_cat_import_result', ['count' => $count, 'errors' => $errors], 60);
    delete_transient('ap_categories_cache_v1');
    delete_transient('ap_products_cache_v3');
    wp_redirect(admin_url('admin.php?page=ap-import-export&catsimported=' . $count));
    exit;
});

/* ---------- برون‌ریزی مقالات ---------- */
add_action('admin_post_ap_export_articles', function () {
    if (!current_user_can('edit_posts')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_export_articles');

    $posts = get_posts(['post_type' => 'ap_article', 'numberposts' => -1, 'post_status' => 'any']);
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ap-articles.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['ID', 'Title', 'Published', 'Excerpt', 'Content', 'Image']);
    foreach ($posts as $p) {
        $thumbId = get_post_thumbnail_id($p->ID);
        $thumbUrl = $thumbId ? wp_get_attachment_url($thumbId) : '';
        fputcsv($out, [
            $p->ID,
            $p->post_title,
            $p->post_status === 'publish' ? 1 : 0,
            $p->post_excerpt,
            $p->post_content,
            $thumbUrl,
        ]);
    }
    fclose($out);
    exit;
});

/* ---------- درون‌ریزی مقالات ----------
   ستون‌ها: Title، Content (می‌تونه تگ <img> وسط متن داشته باشه)، Excerpt (اختیاری)،
   Image (آدرس تصویر کاور، اختیاری - فقط لینک ذخیره می‌شه، دانلود نمی‌شه تا سریع بمونه). */
add_action('admin_post_ap_import_articles', function () {
    if (!current_user_can('edit_posts')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_import_articles');

    if (empty($_FILES['ap_csv']['tmp_name'])) wp_die('فایلی انتخاب نشده.');
    $fh = fopen($_FILES['ap_csv']['tmp_name'], 'r');
    if (!$fh) wp_die('فایل قابل خواندن نیست.');

    $firstLine = fgets($fh);
    rewind($fh);
    $delimiter = (substr_count($firstLine, "\t") > substr_count($firstLine, ',')) ? "\t" : ',';
    $bom = "\xEF\xBB\xBF";
    $header = fgetcsv($fh, 0, $delimiter);
    if ($header && strpos($header[0], $bom) === 0) $header[0] = substr($header[0], strlen($bom));

    $col = function ($row, array $names) use ($header) {
        foreach ($names as $name) {
            $idx = array_search($name, $header);
            if ($idx !== false && isset($row[$idx])) return $row[$idx];
        }
        return '';
    };

    $count = 0;
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (count($row) === 1 && trim((string) $row[0]) === '') continue;
        $title = trim((string) $col($row, ['Title', 'Name', 'عنوان']));
        if ($title === '') continue;

        $content = (string) $col($row, ['Content', 'متن', 'محتوا']);
        $excerpt = (string) $col($row, ['Excerpt', 'خلاصه']);
        $imageUrl = trim((string) $col($row, ['Image', 'تصویر']));
        $publishedFlag = trim((string) $col($row, ['Published', 'منتشر شده']));
        $postStatus = ($publishedFlag === '0') ? 'draft' : 'publish';
        $existingId = (int) trim((string) $col($row, ['ID', 'شناسه']));
        $explicitSlug = trim((string) $col($row, ['Slug', 'پیوند یکتا', 'اسلاگ']));
        $explicitSlug = $explicitSlug !== '' ? sanitize_title($explicitSlug) : '';

        $postArr = [
            'post_type'    => 'ap_article',
            'post_title'   => sanitize_text_field($title),
            'post_content' => wp_kses_post($content),
            'post_excerpt' => sanitize_textarea_field($excerpt),
            'post_status'  => $postStatus,
        ];
        if ($explicitSlug !== '') { $postArr['post_name'] = $explicitSlug; }

        if ($existingId && get_post_type($existingId) === 'ap_article') {
            $postArr['ID'] = $existingId;
            wp_update_post($postArr);
            $post_id = $existingId;
        } else {
            $post_id = wp_insert_post($postArr);
        }
        if (is_wp_error($post_id) || !$post_id) continue;

        if ($imageUrl !== '') {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
            $attId = media_sideload_image($imageUrl, $post_id, null, 'id');
            if (!is_wp_error($attId)) set_post_thumbnail($post_id, $attId);
        }

        $count++;
    }
    fclose($fh);
    wp_redirect(admin_url('admin.php?page=ap-import-export&imported=' . $count . '&type=articles'));
    exit;
});

/* ---------- برون‌ریزی مشتریان (از دیتابیس JSON فروشگاه) ---------- */
add_action('admin_post_ap_export_customers', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_export_customers');

    $users = ap_get_all_customers();
    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ap-customers.csv');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['First Name', 'Last Name', 'Email', 'Phone', 'Address Label', 'Billing Address 1', 'Billing City', 'Billing State', 'Billing Postcode']);
    foreach ($users as $u) {
        $addresses = $u['addresses'] ?? [];
        if (!$addresses) { $addresses = [[]]; }
        foreach ($addresses as $a) {
            fputcsv($out, [
                $u['firstName'] ?? '',
                $u['lastName'] ?? '',
                $u['email'] ?? '',
                $u['phone'] ?? '',
                $a['label'] ?? '',
                trim(($a['district'] ?? '') . ' ' . ($a['fullAddress'] ?? '')),
                $a['city'] ?? '',
                $a['province'] ?? '',
                $a['postalCode'] ?? '',
            ]);
        }
    }
    fclose($out);
    exit;
});

/* ---------- درون‌ریزی مشتریان ---------- */
add_action('admin_post_ap_import_customers', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_import_customers');
    if (empty($_FILES['ap_csv']['tmp_name'])) {
        wp_redirect(admin_url('admin.php?page=ap-import-export'));
        exit;
    }
    @set_time_limit(0);

    $tmp_path = $_FILES['ap_csv']['tmp_name'];
    $first_line = '';
    $probe = fopen($tmp_path, 'r');
    if ($probe) { $first_line = (string) fgets($probe); fclose($probe); }
    $first_line = preg_replace('/^\xEF\xBB\xBF/', '', $first_line);
    $delimiter = ',';
    $counts = [',' => substr_count($first_line, ','), ';' => substr_count($first_line, ';'), "\t" => substr_count($first_line, "\t")];
    arsort($counts);
    $best = array_key_first($counts);
    if ($counts[$best] > 0) { $delimiter = $best; }

    $fh = fopen($tmp_path, 'r');
    $header = fgetcsv($fh, 0, $delimiter);
    if (!$header || count($header) < 2) {
        wp_die('ستون‌های فایل CSV به‌درستی تشخیص داده نشد. لطفاً مطمئن شو فایل واقعاً CSV هست و دوباره امتحان کن.<br><a href="' . esc_url(admin_url('admin.php?page=ap-import-export')) . '">بازگشت</a>');
    }
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $normalized = array_map(function ($h) { return strtolower(trim((string) $h)); }, $header);
    $idx = array_flip($normalized);
    $col = function ($row, $names) use ($idx) {
        foreach ((array) $names as $n) {
            $key = strtolower(trim($n));
            if (isset($idx[$key]) && isset($row[$idx[$key]])) return $row[$idx[$key]];
        }
        return '';
    };

    if (!isset($idx['phone']) && !isset($idx['billing phone'])) {
        wp_die(
            'ستونِ «Phone» توی فایل پیدا نشد، برای همین هیچ مشتری‌ای درون‌ریزی نشد.<br>' .
            'ستون‌هایی که توی فایلت پیدا کردم: ' . esc_html(implode(' | ', $header)) . '<br>' .
            '<a href="' . esc_url(admin_url('admin.php?page=ap-import-export')) . '">بازگشت</a>'
        );
    }

    $rows = [];
    while (($row = fgetcsv($fh, 0, $delimiter)) !== false) {
        if (count($row) === 1 && trim((string) $row[0]) === '') continue;
        $phone = preg_replace('/[^0-9]/', '', (string) $col($row, ['Phone', 'Billing Phone']));
        if ($phone === '') continue;
        $rows[] = [
            'firstName' => (string) $col($row, ['First Name']),
            'lastName'  => (string) $col($row, ['Last Name']),
            'email'     => (string) $col($row, ['Email']),
            'phone'     => $phone,
            'city'      => (string) $col($row, ['Billing City']),
            'province'  => (string) $col($row, ['Billing State']),
            'postalCode'=> (string) $col($row, ['Billing Postcode']),
            'address1'  => (string) $col($row, ['Billing Address 1']),
        ];
    }
    fclose($fh);

    $count = ap_import_customers_rows($rows);
    wp_redirect(admin_url('admin.php?page=ap-import-export&imported=' . $count . '&type=customers'));
    exit;
});

add_action('admin_menu', function () {
    remove_menu_page('users.php');
    remove_menu_page('edit-comments.php');
}, 999);

/* صفحه‌ی «افزودن/ویرایش محصول» به‌جای ویرایشگر بلوکی (گوتنبرگ) با ویرایشگر
   کلاسیک (همون چیزی که قبلاً بود، شبیه فرم محصول ووکامرس) نمایش داده می‌شه؛
   باکس مشخصات محصول + کادر توضیحات کامل، هر دو همون‌جا و منظم در دسترسن. */
add_filter('use_block_editor_for_post_type', function ($use_block_editor, $post_type) {
    return ($post_type === 'ap_product') ? false : $use_block_editor;
}, 10, 2);

/* نظرات (نظرسنجی) روی محصولات فروشگاه نیازی به تایید دستی ادمین ندارن و بلافاصله
   بعد از ثبت نمایش داده می‌شن؛ این فقط مخصوص محصولاته و روی بقیه‌ی نوشته‌های
   سایت (مثلاً وبلاگ)، همون تنظیمات پیش‌فرض مدیریت دیدگاه‌های وردپرس دست‌نخورده می‌مونه. */
add_filter('pre_comment_approved', function ($approved, $commentdata) {
    if (!empty($commentdata['comment_post_ID']) && get_post_type($commentdata['comment_post_ID']) === 'ap_product') {
        return 1;
    }
    return $approved;
}, 10, 2);

/* نظرات (نظرسنجی/تیم) براشون پیش‌فرض بازه ---------- */
add_filter('get_default_comment_status', function ($status, $post_type = 'post', $comment_type = 'comment') {
    return ($post_type === 'ap_product') ? 'open' : $status;
}, 10, 3);

/* نام و ایمیلِ نمایش داده‌شده روی هر نظر، همیشه از حساب کاربریِ خودِ مشتریه
   (که با جاوااسکریپت روی فرم ست می‌شه)، نه از سشنِ ورودیِ وردپرس — چون ممکنه
   خودِ ادمین هم همزمان با حساب وردپرسش وارد باشه و نظرها به اسم "admin" ثبت بشن. */
add_filter('preprocess_comment', function ($commentdata) {
    if (get_post_type($commentdata['comment_post_ID']) === 'ap_product') {
        if (!empty($_POST['author'])) {
            $commentdata['comment_author'] = sanitize_text_field(wp_unslash($_POST['author']));
        }
        if (!empty($_POST['email'])) {
            $commentdata['comment_author_email'] = sanitize_email(wp_unslash($_POST['email']));
        }
        // این نظرها همیشه به‌عنوان دیدگاهِ «مهمان» ثبت بشن، نه به حساب کاربریِ وردپرسیِ فعلاً واردشده
        $commentdata['user_id'] = 0;
    }
    return $commentdata;
});

function ap_register_category_taxonomy() {
    register_taxonomy('ap_cat', ['ap_product'], [
        'labels' => [
            'name'              => 'دسته‌بندی محصولات',
            'singular_name'     => 'دسته‌بندی',
            'search_items'      => 'جستجوی دسته‌بندی',
            'all_items'         => 'همه‌ی دسته‌بندی‌ها',
            'parent_item'       => 'دسته‌بندی والد',
            'parent_item_colon' => 'دسته‌بندی والد:',
            'edit_item'         => 'ویرایش دسته‌بندی',
            'add_new_item'      => 'افزودن دسته‌بندی جدید',
            'new_item_name'     => 'نام دسته‌بندی جدید',
            'menu_name'         => 'دسته‌بندی‌ها',
        ],
        'hierarchical'      => true, // درختی: هر دسته می‌تونه زیرمجموعه‌ی یک دسته‌ی دیگه باشه
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'show_in_menu'      => 'ap-dashboard', // مستقیم زیرِ منوی فروشگاه نشون داده می‌شه
        'show_ui'           => true,
        /* hierarchical => true لازمه تا وردپرس برای زیردسته‌ها هم قانون بازنویسیِ
           چندسطحی بسازه (مثلاً /product-cat/آیفون/آیفون-سری-6/آیفون-6/)؛ بدون این،
           فقط یک قانونِ تک‌سطحی ساخته می‌شه و پیوندِ دسته‌های تودرتو (که با
           get_term_link ساخته و روی سایت نمایش داده می‌شن) موقعِ بازدید یا رفرشِ
           مستقیم ۴۰۴ می‌داد؛ برای سئو و بوکمارک‌کردن باید هر پیوند واقعاً لود بشه. */
        'rewrite'           => ['slug' => 'product-cat', 'hierarchical' => true, 'with_front' => false],
    ]);
}
add_action('init', 'ap_register_category_taxonomy');

/* =========================================================================
 * خودرو: تاکسونومی درختیِ «برند ← مدل ← سال» (ap_vehicle) — همون چیزی که در
 * پوسته‌ی قبلیِ قطعات یدکی بود، دست‌نخورده منتقل شده تا داده‌های فعلی سایت
 * (برندها، مدل‌ها، سال‌ها و لوگوی برندها) سر جاشون بمونن و کار کنن.
 * ========================================================================= */
function ap_register_vehicle_taxonomies() {
    // تاکسونومی واحد و واقعاً درختی — دقیقاً مثل «دسته‌بندی محصولات» ووکامرس:
    // سطح ۱ = برند، سطح ۲ = مدل، سطح ۳ = سال ساخت (و می‌تونه عمیق‌تر هم بشه).
    // چون همه‌شون یه تاکسونومی‌ان، انتخاب والد تو پیشخوان (چک‌باکس درختی
    // استاندارد وردپرس) خودش درست کار می‌کنه و نیازی به فیلد سفارشی نیست.
    register_taxonomy('ap_vehicle', ['ap_product'], [
        'labels' => [
            'name'          => 'خودرو (برند / مدل / سال)',
            'singular_name' => 'خودرو',
            'all_items'     => 'همه‌ی برند/مدل/سال‌ها',
            'add_new_item'  => 'افزودن مورد جدید',
            'parent_item'       => 'والد',
            'parent_item_colon' => 'والد:',
            'menu_name'     => 'برند / مدل / سال',
        ],
        'hierarchical'      => true,
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'show_in_menu'      => 'ap-dashboard',
        'show_ui'           => true,
        'rewrite'           => ['slug' => 'car', 'hierarchical' => true], // پیوند تو در تو: /car/peugeot/206/2023/ — یکتا و بدون تداخل بین سال‌های یکسان مدل‌های مختلف
    ]);

    // تاکسونومی‌های قدیمی رو فقط برای مهاجرتِ داده‌ی قبلی نگه می‌داریم؛ از UI و REST قایم‌ان.
    register_taxonomy('ap_brand', ['ap_product'], ['hierarchical' => false, 'show_ui' => false, 'show_in_menu' => false, 'show_in_rest' => false, 'public' => false]);
    register_taxonomy('ap_model', ['ap_product'], ['hierarchical' => false, 'show_ui' => false, 'show_in_menu' => false, 'show_in_rest' => false, 'public' => false]);
    register_taxonomy('ap_year', ['ap_product'], ['hierarchical' => false, 'show_ui' => false, 'show_in_menu' => false, 'show_in_rest' => false, 'public' => false]);
}
add_action('init', 'ap_register_vehicle_taxonomies');

/* =========================================================================
 * مهاجرتِ یک‌باره‌ی داده‌های قدیمی (برند/مدل/سال جدا) به تاکسونومی واحدِ
 * درختیِ جدید (ap_vehicle)، و انتقال محصولات به ترم‌های جدید متناظرشون.
 * ========================================================================= */
add_action('init', function () {
    if (get_option('ap_vehicle_migrated') === '1') { return; }

    $brand_map = []; // old ap_brand term_id → new ap_vehicle term_id
    $brands = get_terms(['taxonomy' => 'ap_brand', 'hide_empty' => false]);
    foreach ($brands as $b) {
        $existing = get_term_by('slug', $b->slug, 'ap_vehicle');
        if ($existing) { $brand_map[$b->term_id] = $existing->term_id; continue; }
        $r = wp_insert_term($b->name, 'ap_vehicle', ['slug' => $b->slug]);
        if (!is_wp_error($r)) { $brand_map[$b->term_id] = $r['term_id']; }
    }

    $model_map = []; // old ap_model term_id → new ap_vehicle term_id
    $models = get_terms(['taxonomy' => 'ap_model', 'hide_empty' => false]);
    foreach ($models as $m) {
        $old_brand_id = ap_get_model_brand_id($m->term_id);
        $new_parent = $brand_map[$old_brand_id] ?? 0;
        $existing = get_term_by('slug', $m->slug, 'ap_vehicle');
        if ($existing && (int) $existing->parent === (int) $new_parent) { $model_map[$m->term_id] = $existing->term_id; continue; }
        $r = wp_insert_term($m->name, 'ap_vehicle', ['slug' => $m->slug, 'parent' => $new_parent]);
        if (!is_wp_error($r)) { $model_map[$m->term_id] = $r['term_id']; }
    }

    $year_map = []; // old ap_year term_id → new ap_vehicle term_id
    $years = get_terms(['taxonomy' => 'ap_year', 'hide_empty' => false]);
    foreach ($years as $y) {
        $old_model_id = ap_get_year_model_id($y->term_id);
        $new_parent = $model_map[$old_model_id] ?? 0;
        $slug = $y->slug ?: ($y->name . '-' . wp_generate_password(4, false));
        $existing = get_term_by('slug', $slug, 'ap_vehicle');
        if ($existing && (int) $existing->parent === (int) $new_parent) { $year_map[$y->term_id] = $existing->term_id; continue; }
        $r = wp_insert_term($y->name, 'ap_vehicle', ['slug' => $slug, 'parent' => $new_parent]);
        if (!is_wp_error($r)) { $year_map[$y->term_id] = $r['term_id']; }
    }

    // محصولات: هر محصولی که قبلاً به یه برند/مدل/سال قدیمی وصل بوده، به معادل جدیدش وصل می‌شه
    $products = get_posts(['post_type' => 'ap_product', 'posts_per_page' => -1, 'post_status' => 'any', 'fields' => 'ids']);
    foreach ($products as $post_id) {
        $new_terms = [];
        foreach (wp_get_object_terms($post_id, 'ap_brand', ['fields' => 'ids']) as $id) { if (isset($brand_map[$id])) $new_terms[] = $brand_map[$id]; }
        foreach (wp_get_object_terms($post_id, 'ap_model', ['fields' => 'ids']) as $id) { if (isset($model_map[$id])) $new_terms[] = $model_map[$id]; }
        foreach (wp_get_object_terms($post_id, 'ap_year', ['fields' => 'ids']) as $id) { if (isset($year_map[$id])) $new_terms[] = $year_map[$id]; }
        if ($new_terms) { wp_set_object_terms($post_id, array_unique($new_terms), 'ap_vehicle', true); }
    }

    update_option('ap_vehicle_migrated', '1');
    flush_rewrite_rules();
}, 1000);

/* =========================================================================
 * تاکسونومی‌های قدیمیِ برند/مدل/سالِ جدا دیگه تو پیشخوان نشون داده نمی‌شن
 * (جایگزین‌شون تاکسونومی درختیِ واحدِ ap_vehicle شده) — فقط این دو تابع
 * کمکی برای خوندن رابطه‌ی قدیمی حین مهاجرت لازمه.
 * ========================================================================= */
function ap_get_model_brand_id($model_term_id) {
    return (int) get_term_meta($model_term_id, 'ap_model_brand_id', true);
}
function ap_get_year_model_id($year_term_id) {
    return (int) get_term_meta($year_term_id, 'ap_year_model_id', true);
}

/* =========================================================================
 * لوگوی برند خودرو: فیلد آپلود عکس کوچیک — فقط برای ترم‌های سطح ۱ (برند)
 * تاکسونومی درختیِ ap_vehicle معنا داره.
 * ========================================================================= */
add_action('ap_vehicle_add_form_fields', function () {
    ?>
    <div class="form-field">
        <label>لوگوی برند</label>
        <div id="ap-brand-logo-preview" style="margin-bottom:8px;"></div>
        <input type="hidden" name="ap_brand_logo_id" id="ap_brand_logo_id" value="">
        <button type="button" class="button" id="ap-brand-logo-btn">انتخاب لوگو</button>
        <p class="description">فقط برای ترم‌های سطح اول (برند) استفاده می‌شه؛ برای مدل/سال لازم نیست.</p>
    </div>
    <?php
});
add_action('ap_vehicle_edit_form_fields', function ($term) {
    if ($term->parent) { return; } // فقط سطح ۱ (برند) لوگو داره
    $logo_id = get_term_meta($term->term_id, 'ap_brand_logo_id', true);
    $url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
    ?>
    <tr class="form-field">
        <th><label>لوگوی برند</label></th>
        <td>
            <div id="ap-brand-logo-preview" style="margin-bottom:8px;">
                <?php if ($url): ?><img src="<?php echo esc_url($url); ?>" style="width:70px;height:70px;object-fit:contain;border:1px solid #ddd;border-radius:8px;padding:4px;"><?php endif; ?>
            </div>
            <input type="hidden" name="ap_brand_logo_id" id="ap_brand_logo_id" value="<?php echo esc_attr($logo_id); ?>">
            <button type="button" class="button" id="ap-brand-logo-btn">انتخاب لوگو</button>
            <button type="button" class="button" id="ap-brand-logo-clear">حذف</button>
            <p class="description">یه آیکون/لوگوی کوچیک مربعی برای این برند (اختیاری).</p>
        </td>
    </tr>
    <?php
});
add_action('created_ap_vehicle', 'ap_save_brand_logo_meta');
add_action('edited_ap_vehicle', 'ap_save_brand_logo_meta');
function ap_save_brand_logo_meta($term_id) {
    if (isset($_POST['ap_brand_logo_id'])) {
        $id = (int) $_POST['ap_brand_logo_id'];
        if ($id) { update_term_meta($term_id, 'ap_brand_logo_id', $id); }
        else { delete_term_meta($term_id, 'ap_brand_logo_id'); }
    }
}
add_action('admin_enqueue_scripts', function ($hook) {
    if ($hook !== 'edit-tags.php' && $hook !== 'term.php') return;
    if (!isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'ap_vehicle') return;
    wp_enqueue_media();
    wp_add_inline_script('media-editor', "
        document.addEventListener('DOMContentLoaded', function(){
            var frame;
            function bind(){
                var btn = document.getElementById('ap-brand-logo-btn');
                var clearBtn = document.getElementById('ap-brand-logo-clear');
                if (!btn) return;
                btn.addEventListener('click', function(e){
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({ title: 'انتخاب لوگوی برند', multiple: false, library: { type: 'image' } });
                    frame.on('select', function(){
                        var att = frame.state().get('selection').first().toJSON();
                        document.getElementById('ap_brand_logo_id').value = att.id;
                        document.getElementById('ap-brand-logo-preview').innerHTML = '<img src=\"'+(att.sizes && att.sizes.thumbnail ? att.sizes.thumbnail.url : att.url)+'\" style=\"width:70px;height:70px;object-fit:contain;border:1px solid #ddd;border-radius:8px;padding:4px;\">';
                    });
                    frame.open();
                });
                if (clearBtn) clearBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    document.getElementById('ap_brand_logo_id').value = '';
                    document.getElementById('ap-brand-logo-preview').innerHTML = '';
                });
            }
            bind();
        });
    ");
});

/* توضیح: فیلدهای سئوی سفارشی (عنوان/توضیحات متا) از اینجا حذف شدن، چون
   افزونه‌ی Rank Math SEO از قبل پنل کامل خودش رو برای دسته‌بندی‌ها نشون
   می‌ده و استفاده‌ی هم‌زمان از هردو تکراری/گیج‌کننده بود. */

/* فلاش کردن یک‌بارهٔ rewrite rules بعد از اضافه شدن نوع‌پست/تاکسونومی جدید،
   چه در نصب اول قالب و چه وقتی این نسخه جایگزین نسخه‌ی قبلی می‌شه. */
function ap_flush_rewrites_on_switch() {
    ap_register_product_cpt();
    ap_register_category_taxonomy();
    ap_register_vehicle_taxonomies();
    flush_rewrite_rules();
}
add_action('after_switch_theme', 'ap_flush_rewrites_on_switch');

add_action('init', function () {
    /* شماره‌ی نسخه بالا رفت (v2 -> v3) چون قانونِ hierarchical به rewrite دسته‌بندی
       اضافه شد؛ بدون بالا بردنِ این عدد، سایت‌هایی که قبلاً یک‌بار v2 رو flush کرده
       بودن، دیگه هیچ‌وقت flush جدید رو نمی‌گرفتن و پیوندِ دسته‌های تودرتو همچنان ۴۰۴ می‌داد. */
    if (get_option('ap_rewrite_flushed_v3') !== '1') {
        flush_rewrite_rules();
        update_option('ap_rewrite_flushed_v3', '1');
    }
}, 20);

/* =========================================================================
 * باکس اطلاعات محصول (قیمت، مشخصات کوتاه، کد فنی، رنگ، آیکون، برچسب) در صفحه‌ی
 * ویرایشِ هر محصول در پیشخوان.
 * ========================================================================= */
/* لیستِ URL تصاویر یک محصول: اول گالریِ دستی (اگه تنظیم شده)، وگرنه فقط همون تصویر شاخص. */
function ap_gallery_urls($post_id, $fallback_img = null) {
    if ($fallback_img === null) { $fallback_img = get_the_post_thumbnail_url($post_id, 'large'); }
    $raw = trim((string) get_post_meta($post_id, '_ap_gallery', true));
    $urls = [];
    if ($raw !== '') {
        foreach (explode(',', $raw) as $token) {
            $token = rawurldecode(trim($token));
            if ($token === '') continue;
            if (ctype_digit($token)) {
                // شناسه‌ی یک تصویرِ واقعاً آپلودشده در کتابخانه‌ی رسانه
                $url = wp_get_attachment_image_url((int) $token, 'large');
                if ($url) $urls[] = $url;
            } else {
                // آدرس مستقیم یک تصویر (بدون دانلود در سایت؛ حالت درون‌ریزیِ سریع)
                $urls[] = $token;
            }
        }
    }
    if (!$urls && $fallback_img) { $urls[] = $fallback_img; }
    return $urls;
}

/* =========================================================================
 * رنگِ کارتِ محصول: محصولاتی که قبلاً (با پوسته/درون‌ریزیِ قدیمی) ساخته شدن،
 * رنگِ بنفشِ پوسته‌ی قبلی رو توی متای خودشون ذخیره دارن و همون بنفش روی کارت
 * دیده می‌شد. این تابع رنگ‌های بنفشِ قدیمی رو به قرمزِ برند تبدیل می‌کنه؛ اگه
 * مدیر عمداً رنگِ دیگه‌ای (مثلاً مشکی یا آبی) گذاشته باشه، دست‌نخورده می‌مونه.
 * ========================================================================= */
if (!function_exists('ap_brand_card_color')) {
    function ap_brand_card_color($raw) {
        $red = '#ff3b4e';
        $raw = strtolower(trim((string) $raw));
        if ($raw === '') { return $red; }
        $legacy = ['#6a4dff', '#8b6bff', '#8f6bff', '#a68bff', '#7c5cff', '#5b3df5', '#8b5cf6', '#7c9dff', '#6a4eff'];
        if (in_array($raw, $legacy, true)) { return $red; }
        /* هر بنفشِ دیگه‌ای هم (آبیِ پررنگ‌تر از قرمز و سبز) قرمز می‌شه */
        if (preg_match('/^#([0-9a-f]{2})([0-9a-f]{2})([0-9a-f]{2})$/', $raw, $m)) {
            $r = hexdec($m[1]); $g = hexdec($m[2]); $b = hexdec($m[3]);
            if ($b > $r + 25 && $b > $g + 20) { return $red; }
        }
        return $raw;
    }
}

function ap_product_meta_box() {
    add_meta_box('ap_product_details', 'جزئیات محصول (قیمت، مشخصات، رنگ و ...)', 'ap_product_meta_box_html', 'ap_product', 'normal', 'high');
}
add_action('add_meta_boxes', 'ap_product_meta_box');

function ap_product_meta_box_html($post) {
    wp_nonce_field('ap_save_product_meta', 'ap_product_meta_nonce');
    $price = get_post_meta($post->ID, '_ap_price', true);
    $spec  = get_post_meta($post->ID, '_ap_spec', true);
    $color = get_post_meta($post->ID, '_ap_color', true);
    $ic    = get_post_meta($post->ID, '_ap_icon', true);
    $badge = get_post_meta($post->ID, '_ap_badge', true);
    $color = ap_brand_card_color($color);
    if ($ic === '')    { $ic = 'bumper'; }
    $icons = [
        'bumper'   => 'سپر و بدنه',
        'light'    => 'چراغ',
        'radiator' => 'رادیاتور و خنک‌کاری',
        'mirror'   => 'آینه',
        'fender'   => 'گلگیر',
        'hood'     => 'کاپوت / درب موتور',
        'engine'   => 'قطعات موتوری',
        'electric' => 'قطعات برقی',
        'filter'   => 'فیلتر',
        'brake'    => 'ترمز',
        'disc'     => 'سایر',
    ];
    ?>
    <p style="background:#fff1f2;border:1px solid #ffc9ce;border-radius:6px;padding:10px 12px;color:#9f1223;">
        📸 برای <strong>عکس محصول</strong>: از باکس «تصویر شاخص» (سمت راست همین صفحه) استفاده کن.<br>
        📝 برای <strong>توضیح کامل محصول</strong>: از کادر بزرگ متن بالای همین صفحه (زیر عنوان) استفاده کن؛ همون توی صفحه‌ی محصول کامل نمایش داده می‌شه.
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">قیمت (تومان)</label>
        <input type="number" name="ap_price" value="<?php echo esc_attr($price); ?>" style="width:100%" min="0">
        <?php $priceDate = ap_price_updated_label($post->ID); ?>
        <p class="description" style="margin-top:4px;">
            <?php if ($priceDate) : ?>
                آخرین به‌روزرسانی قیمت: <b><?php echo esc_html($priceDate); ?></b>
                — همین تاریخ کنارِ قیمت به مشتری نشون داده می‌شه.
            <?php else : ?>
                تاریخِ به‌روزرسانی، اولین باری که قیمت ذخیره بشه خودکار ثبت می‌شه.
            <?php endif; ?>
        </p>
    </p>
    <?php
    /* ---- قیمتِ جداگانه برای هر متغیر ----
       نگاشتی به شکل {"برچسبِ گروه||گزینه": قیمت}. هر گزینه‌ای که قیمت داشته باشه،
       موقعِ انتخابش در سایت، همون قیمت جایگزینِ قیمتِ اصلی می‌شه. */
    $variantPricesRaw = (string) get_post_meta($post->ID, '_ap_variant_prices', true);
    ?>
    <div style="margin:0 0 1em;">
        <button type="button" class="button" id="ap_vp_toggle">💰 قیمت جداگانه برای هر متغیر</button>
        <span class="description" id="ap_vp_summary" style="margin-inline-start:8px;"></span>
        <input type="hidden" name="ap_variant_prices" id="ap_vp_data" value="<?php echo esc_attr($variantPricesRaw); ?>">
        <div id="ap_vp_box" style="display:none; margin-top:10px; padding:12px 14px; border:1px solid #ccd0d4; border-radius:6px; background:#fbfbfc;">
            <p class="description" style="margin:0 0 10px;">
                برای هر گزینه‌ای که قیمتش با بقیه فرق دارد، قیمتش را بنویس (تومان). گزینه‌های خالی همان
                <b>قیمت اصلیِ محصول</b> را می‌گیرند. اگر یک محصول چند گروهِ متغیر داشته باشد و برای چند گروه
                قیمت بگذاری، قیمتِ <b>گروهِ پایین‌تر در این لیست</b> ملاک است.
            </p>
            <div id="ap_vp_list"></div>
        </div>
    </div>
    <script>
    jQuery(function($){
        var data = {};
        try { data = JSON.parse($('#ap_vp_data').val() || '{}') || {}; } catch(e){ data = {}; }

        function groups(){
            var out = [];
            function split(v){ return String(v || '').split(',').map(function(x){ return x.trim(); }).filter(Boolean); }
            var colors = split($('input[name="ap_colors"]').val());
            if (colors.length) out.push({ label: 'رنگ', options: colors });
            var storages = split($('input[name="ap_storages"]').val());
            if (storages.length) out.push({ label: 'حجم/ظرفیت', options: storages });
            $('.ap-variant-group-row').each(function(){
                var label = ($(this).find('input[name="ap_variant_group_label[]"]').val() || '').trim();
                var opts = split($(this).find('input[name="ap_variant_group_options[]"]').val());
                if (label && opts.length) out.push({ label: label, options: opts });
            });
            return out;
        }
        function sync(){
            var out = {};
            $('#ap_vp_list input[data-vp-key]').each(function(){
                var v = String($(this).val() || '').replace(/[^0-9]/g, '');
                if (v !== '') out[$(this).data('vp-key')] = parseInt(v, 10);
            });
            data = out;
            $('#ap_vp_data').val(JSON.stringify(out));
            summary();
        }
        function summary(){
            var n = Object.keys(data).length;
            $('#ap_vp_summary').text(n ? ('برای ' + n + ' گزینه قیمت جدا ثبت شده') : 'همه‌ی گزینه‌ها با قیمت اصلی');
        }
        function render(){
            var list = $('#ap_vp_list').empty();
            var gs = groups();
            if (!gs.length) {
                list.append('<p style="margin:0;color:#8a1c28;">هنوز هیچ متغیری تعریف نشده. اول پایینِ همین صفحه گزینه‌های رنگ/حجم یا یک «گروه متغیر سفارشی» بساز، بعد اینجا برایشان قیمت بگذار.</p>');
                return;
            }
            gs.forEach(function(g){
                var box = $('<div style="margin-bottom:12px;"></div>');
                box.append($('<strong></strong>').text(g.label));
                var rows = $('<div style="display:grid; grid-template-columns:repeat(auto-fill,minmax(230px,1fr)); gap:8px; margin-top:6px;"></div>');
                g.options.forEach(function(opt){
                    var key = g.label + '||' + opt;
                    var row = $('<label style="display:flex; align-items:center; gap:6px; font-size:13px;"></label>');
                    row.append($('<span style="flex:0 0 42%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>').text(opt));
                    var inp = $('<input type="number" min="0" step="1000" style="flex:1; min-width:0;" placeholder="قیمت اصلی">');
                    inp.attr('data-vp-key', key).data('vp-key', key);
                    if (data[key] != null) inp.val(data[key]);
                    inp.on('input change', sync);
                    row.append(inp);
                    rows.append(row);
                });
                box.append(rows);
                list.append(box);
            });
        }
        $('#ap_vp_toggle').on('click', function(e){
            e.preventDefault();
            var box = $('#ap_vp_box');
            if (box.is(':visible')) { box.hide(); return; }
            render();            // هر بار باز شدن، گزینه‌های تازه‌نوشته‌شده هم می‌آن
            box.show();
        });
        summary();
    });
    </script>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">مشخصات کوتاه (زیر عنوان، در کارت محصول)</label>
        <input type="text" name="ap_spec" value="<?php echo esc_attr($spec); ?>" style="width:100%" placeholder="مثلاً: اورجینال | چپ">
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">کد فنی قطعه (OEM)</label>
        <input type="text" name="ap_oem_code" value="<?php echo esc_attr(get_post_meta($post->ID, '_ap_oem_code', true)); ?>" style="width:100%" dir="ltr" placeholder="مثلاً: 6202-B1">
        <span class="description">کد روی خودِ قطعه یا کاتالوگ کارخانه؛ توی صفحه‌ی محصول و جستجو هم نشون داده می‌شه.</span>
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">رنگ کارت (کد HEX)</label>
        <input type="text" name="ap_color" value="<?php echo esc_attr($color); ?>" style="width:100%">
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">آیکون</label>
        <select name="ap_icon" style="width:100%">
            <?php foreach ($icons as $key => $label) : ?>
                <option value="<?php echo esc_attr($key); ?>" <?php selected($ic, $key); ?>><?php echo esc_html($label); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
    /* برچسب‌ها: چند برچسب برای هر محصول. در دیتابیس با «|» از هم جدا ذخیره می‌شن
       (محصولاتِ قدیمی که فقط یک برچسب داشتن، بدون تغییر درست کار می‌کنن). */
    $badgeList = array_values(array_filter(array_map('trim', explode('|', (string) $badge)), 'strlen'));
    ?>
    <div style="margin:1em 0;">
        <label style="display:block;font-weight:600;margin-bottom:4px;">برچسب‌ها (اختیاری، مثلاً «جدید»، «پرفروش»، «اورجینال»)</label>
        <div class="ap-tags" id="apTags">
            <?php foreach ($badgeList as $b): ?>
                <span class="ap-tag"><span><?php echo esc_html($b); ?></span><button type="button" aria-label="حذف">×</button></span>
            <?php endforeach; ?>
            <input type="text" class="ap-tag-input" id="apTagInput" placeholder="برچسب را بنویس و اینتر بزن" autocomplete="off">
        </div>
        <input type="hidden" name="ap_badge" id="apBadgeHidden" value="<?php echo esc_attr(implode('|', $badgeList)); ?>">
        <p class="description" style="margin-top:4px;">بعد از نوشتنِ هر برچسب <b>Enter</b> (یا ویرگول) بزن تا ثبت بشه و برو سراغِ برچسبِ بعدی. برای حذف روی × بزن.</p>
    </div>
    <style>
        .ap-tags{display:flex; flex-wrap:wrap; align-items:center; gap:6px; padding:6px 8px; min-height:38px;
            border:1px solid #8c8f94; border-radius:4px; background:#fff; cursor:text;}
        .ap-tags:focus-within{border-color:#2271b1; box-shadow:0 0 0 1px #2271b1;}
        .ap-tag{display:inline-flex; align-items:center; gap:4px; padding:3px 4px 3px 10px; border-radius:999px;
            background:#ffe4e6; color:#9f1223; font-size:13px; font-weight:600; line-height:1.4;}
        .ap-tag button{border:0; background:rgba(159,18,35,.12); color:#9f1223; width:20px; height:20px; border-radius:50%;
            cursor:pointer; font-size:14px; line-height:1; padding:0;}
        .ap-tag button:hover{background:#9f1223; color:#fff;}
        .ap-tag-input{flex:1 1 140px; min-width:120px; border:0 !important; box-shadow:none !important; outline:0; padding:4px !important; background:transparent;}
    </style>
    <script>
    (function(){
        var box = document.getElementById('apTags'), input = document.getElementById('apTagInput'),
            hidden = document.getElementById('apBadgeHidden');
        if(!box || !input || !hidden) return;
        function values(){ return Array.prototype.map.call(box.querySelectorAll('.ap-tag > span'), function(s){ return s.textContent.trim(); }); }
        function sync(){ hidden.value = values().join('|'); }
        function add(text){
            text = String(text || '').replace(/[|]/g, ' ').trim();
            if(!text) return;
            if(values().some(function(v){ return v === text; })) return;   // تکراری اضافه نمی‌شه
            var tag = document.createElement('span'); tag.className = 'ap-tag';
            var label = document.createElement('span'); label.textContent = text;
            var x = document.createElement('button'); x.type = 'button'; x.setAttribute('aria-label', 'حذف'); x.textContent = '×';
            tag.appendChild(label); tag.appendChild(x);
            box.insertBefore(tag, input);
            sync();
        }
        input.addEventListener('keydown', function(e){
            // Enter نباید فرمِ ویرایشِ محصول رو ذخیره کنه؛ فقط برچسب رو ثبت می‌کنه
            if(e.key === 'Enter' || e.key === ',' || e.key === '،'){
                e.preventDefault();
                add(input.value); input.value = '';
            } else if(e.key === 'Backspace' && input.value === ''){
                var tags = box.querySelectorAll('.ap-tag');
                if(tags.length){ tags[tags.length - 1].remove(); sync(); }
            }
        });
        // اگه چیزی نوشته شده ولی Enter زده نشده، موقعِ خروج از کادر یا ذخیره‌ی محصول هم ثبت بشه
        input.addEventListener('blur', function(){ if(input.value.trim()){ add(input.value); input.value = ''; } });
        var form = input.form;
        if(form) form.addEventListener('submit', function(){ if(input.value.trim()){ add(input.value); input.value = ''; } sync(); });
        box.addEventListener('click', function(e){
            if(e.target.tagName === 'BUTTON' && e.target.parentNode.classList.contains('ap-tag')){
                e.target.parentNode.remove(); sync(); input.focus();
            } else if(e.target === box){ input.focus(); }
        });
    })();
    </script>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">توضیحات کوتاه (بین باکس محصول و توضیحات کامل نمایش داده می‌شه)</label>
        <textarea name="ap_short_desc" rows="3" style="width:100%"><?php echo esc_textarea(get_post_meta($post->ID, '_ap_short_desc', true)); ?></textarea>
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">تصاویر محصول (چند عکس؛ اولی روی کارت محصول نمایش داده می‌شه)</label>
        <input type="hidden" name="ap_gallery" id="ap_gallery_ids" value="<?php echo esc_attr(get_post_meta($post->ID, '_ap_gallery', true)); ?>">
        <div id="ap_gallery_preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:8px;"></div>
        <button type="button" class="button" id="ap_gallery_pick">انتخاب / مرتب‌سازی تصاویر</button>
    </p>
    <p style="background:#fff8e1;border:1px solid #ffe0a3;border-radius:6px;padding:10px 12px;">
        🎨 <strong>گزینه‌های محصول</strong> (مثل رنگ یا حجم): مشتری قبل از افزودن به سبد، یکی از این گزینه‌ها رو انتخاب می‌کنه.
        برای هر گزینه می‌تونی قیمتِ جدا بذاری: بالای همین صفحه، دکمه‌ی «💰 قیمت جداگانه برای هر متغیر» کنارِ قیمت.
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">گزینه‌های رنگ (با کاما جدا کن؛ خالی بذار اگه لازم نیست)</label>
        <input type="text" name="ap_colors" value="<?php echo esc_attr(get_post_meta($post->ID, '_ap_colors', true)); ?>" style="width:100%" placeholder="مثلاً: مشکی, سفید, آبی, بنفش">
    </p>
    <p>
        <label style="display:block;font-weight:600;margin-bottom:4px;">گزینه‌های حجم/ظرفیت (با کاما جدا کن؛ خالی بذار اگه لازم نیست)</label>
        <input type="text" name="ap_storages" value="<?php echo esc_attr(get_post_meta($post->ID, '_ap_storages', true)); ?>" style="width:100%" placeholder="مثلاً: 128 گیگ, 256 گیگ, 512 گیگ">
    </p>
    <p style="background:#eef7ee;border:1px solid #bfe3bf;border-radius:6px;padding:10px 12px;">
        ➕ <strong>گروه‌های متغیر سفارشی</strong>: برای محصولاتی که رنگ/حجم کافی نیست و به یک متغیرِ دیگه نیاز دارن
        (مثلاً برای LCD: «نوع» با گزینه‌های «روکاری اورجینال» و «کپی»). هر ردیف یک گروه‌ست: یک برچسب + چند گزینه با کاما جدا.
        این بخش رو <u>فقط وقتی محصول واقعاً به یک متغیرِ دیگه نیاز داره</u> پر کن؛ برای بقیه‌ی محصولات (مثل باتری) خالی بذار.
    </p>
    <div id="ap_variant_groups_wrap">
        <?php
        $variantGroupsRaw = get_post_meta($post->ID, '_ap_variant_groups', true);
        $variantGroups = [];
        if ($variantGroupsRaw) {
            $decoded = json_decode($variantGroupsRaw, true);
            if (is_array($decoded)) { $variantGroups = $decoded; }
        }
        if (!$variantGroups) { $variantGroups = [['label' => '', 'options' => []]]; }
        foreach ($variantGroups as $vg):
            $vgLabel = isset($vg['label']) ? $vg['label'] : '';
            $vgOptions = isset($vg['options']) && is_array($vg['options']) ? implode(', ', $vg['options']) : '';
        ?>
        <div class="ap-variant-group-row" style="display:flex; gap:8px; margin-bottom:6px;">
            <input type="text" name="ap_variant_group_label[]" value="<?php echo esc_attr($vgLabel); ?>" placeholder="برچسب گروه، مثلاً: نوع" style="width:35%">
            <input type="text" name="ap_variant_group_options[]" value="<?php echo esc_attr($vgOptions); ?>" placeholder="گزینه‌ها با کاما، مثلاً: روکاری اورجینال, کپی" style="width:55%">
            <button type="button" class="button ap-variant-group-remove">حذف</button>
        </div>
        <?php endforeach; ?>
    </div>
    <p><button type="button" class="button" id="ap_variant_group_add">+ افزودن گروهِ متغیرِ دیگر</button></p>
    <script>
    jQuery(function($){
        $('#ap_variant_group_add').on('click', function(){
            var row = $('<div class="ap-variant-group-row" style="display:flex; gap:8px; margin-bottom:6px;">'
                + '<input type="text" name="ap_variant_group_label[]" placeholder="برچسب گروه، مثلاً: نوع" style="width:35%">'
                + '<input type="text" name="ap_variant_group_options[]" placeholder="گزینه‌ها با کاما، مثلاً: روکاری اورجینال, کپی" style="width:55%">'
                + '<button type="button" class="button ap-variant-group-remove">حذف</button>'
                + '</div>');
            $('#ap_variant_groups_wrap').append(row);
        });
        $(document).on('click', '.ap-variant-group-remove', function(){
            $(this).closest('.ap-variant-group-row').remove();
        });
    });
    </script>
    <p style="color:#666;">دسته‌بندی محصول رو از باکس «دسته‌بندی محصولات» در همین صفحه (سمت راست) انتخاب کن.</p>
    <script>
    jQuery(function($){
        var ids = ($('#ap_gallery_ids').val() || '').split(',').filter(Boolean).map(Number);
        var frame;
        function renderPreview(){
            var wrap = $('#ap_gallery_preview').empty();
            ids.forEach(function(id){
                wrap.append('<img src="' + wp.media.attachment(id).get('url') + '" style="width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #ccc;">');
            });
        }
        if(ids.length){
            ids.forEach(function(id){ wp.media.attachment(id).fetch(); });
            setTimeout(renderPreview, 400);
        }
        $('#ap_gallery_pick').on('click', function(e){
            e.preventDefault();
            if(frame){ frame.open(); return; }
            frame = wp.media({ title:'انتخاب تصاویر محصول', multiple:true, library:{type:'image'} });
            frame.on('select', function(){
                var selection = frame.state().get('selection');
                ids = selection.map(function(att){ return att.get('id'); });
                $('#ap_gallery_ids').val(ids.join(','));
                renderPreview();
            });
            frame.open();
        });
    });
    </script>
    <?php
}

function ap_save_product_meta($post_id) {
    if (!isset($_POST['ap_product_meta_nonce']) || !wp_verify_nonce($_POST['ap_product_meta_nonce'], 'ap_save_product_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }

    if (isset($_POST['ap_price'])) { ap_save_price_with_date($post_id, (int) $_POST['ap_price']); }
    if (isset($_POST['ap_spec']))  { update_post_meta($post_id, '_ap_spec', sanitize_text_field($_POST['ap_spec'])); }
    if (isset($_POST['ap_oem_code'])) { update_post_meta($post_id, '_ap_oem_code', sanitize_text_field($_POST['ap_oem_code'])); }
    if (isset($_POST['ap_color'])) { update_post_meta($post_id, '_ap_color', sanitize_text_field($_POST['ap_color'])); }
    if (isset($_POST['ap_icon']))  { update_post_meta($post_id, '_ap_icon', sanitize_text_field($_POST['ap_icon'])); }
    // چند برچسب، جداشده با «|»؛ هر کدوم جدا تمیز می‌شه و تکراری‌ها حذف می‌شن
    $badgesIn = array_map('sanitize_text_field', explode('|', (string) wp_unslash($_POST['ap_badge'] ?? '')));
    $badgesIn = array_values(array_unique(array_filter(array_map('trim', $badgesIn), 'strlen')));
    update_post_meta($post_id, '_ap_badge', implode('|', $badgesIn));
    update_post_meta($post_id, '_ap_short_desc', sanitize_textarea_field($_POST['ap_short_desc'] ?? ''));
    update_post_meta($post_id, '_ap_colors', sanitize_text_field($_POST['ap_colors'] ?? ''));
    update_post_meta($post_id, '_ap_storages', sanitize_text_field($_POST['ap_storages'] ?? ''));

    /* گروه‌های متغیر سفارشی: هر ردیف یک برچسب + چند گزینه (با کاما جدا) بود؛
       این‌ها رو به‌صورت JSON ذخیره می‌کنیم تا فرانت هر تعداد گروه دلخواه رو نشون بده،
       نه فقط رنگ/حجمِ ثابت. ردیف‌های خالی (بدون برچسب یا بدون هیچ گزینه‌ای) نادیده گرفته می‌شن. */
    $vgLabels  = isset($_POST['ap_variant_group_label']) ? (array) $_POST['ap_variant_group_label'] : [];
    $vgOptions = isset($_POST['ap_variant_group_options']) ? (array) $_POST['ap_variant_group_options'] : [];
    $variantGroups = [];
    foreach ($vgLabels as $i => $label) {
        $label = sanitize_text_field($label);
        $optionsRaw = isset($vgOptions[$i]) ? (string) $vgOptions[$i] : '';
        $options = array_values(array_filter(array_map('trim', explode(',', $optionsRaw))));
        if ($label === '' || !$options) continue;
        $variantGroups[] = ['label' => $label, 'options' => array_map('sanitize_text_field', $options)];
    }
    update_post_meta($post_id, '_ap_variant_groups', $variantGroups ? wp_json_encode($variantGroups, JSON_UNESCAPED_UNICODE) : '');

    /* قیمتِ هر متغیر: {"گروه||گزینه": قیمت} — فقط عددهای معتبر ذخیره می‌شن. */
    if (isset($_POST['ap_variant_prices'])) {
        $vpRaw = (string) wp_unslash($_POST['ap_variant_prices']);
        $vp = json_decode($vpRaw, true);
        $clean = [];
        if (is_array($vp)) {
            foreach ($vp as $k => $v) {
                $k = sanitize_text_field((string) $k);
                $v = (int) preg_replace('/[^0-9]/', '', (string) $v);
                if ($k !== '' && $v > 0) { $clean[$k] = $v; }
            }
        }
        $vpOld = ap_variant_prices($post_id);
        update_post_meta($post_id, '_ap_variant_prices', $clean ? wp_json_encode($clean, JSON_UNESCAPED_UNICODE) : '');
        if ($clean != $vpOld && ($clean || (int) get_post_meta($post_id, '_ap_price', true) > 0)) {
            update_post_meta($post_id, '_ap_price_updated', (int) current_time('timestamp'));
        }
    }
    if (isset($_POST['ap_gallery'])) {
        $ids = array_filter(array_map('intval', explode(',', $_POST['ap_gallery'])));
        update_post_meta($post_id, '_ap_gallery', implode(',', $ids));
    }
}
add_action('save_post_ap_product', 'ap_save_product_meta');

/* باطل‌کردنِ کشِ لیست محصولات/دسته‌ها هنگام هر تغییر از خودِ پیشخوان وردپرس.
   قبلاً این هوک‌ها فقط داخل api.php ثبت شده بودن که موقعِ ذخیره در پیشخوان اجرا
   نمی‌شد؛ برای همین بعد از ویرایش، سایت تا چند دقیقه نسخه‌ی کش‌شده‌ی قدیمی رو
   نشون می‌داد. حالا این‌جا هم ثبت می‌شن تا تغییرات بلافاصله روی سایت دیده بشن. */
add_action('save_post_ap_product', function () { delete_transient('ap_products_cache_v3'); }, 20);
add_action('before_delete_post', function ($post_id) {
    if (get_post_type($post_id) === 'ap_product') { delete_transient('ap_products_cache_v3'); }
});
add_action('trashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'ap_product') { delete_transient('ap_products_cache_v3'); }
});
add_action('untrashed_post', function ($post_id) {
    if (get_post_type($post_id) === 'ap_product') { delete_transient('ap_products_cache_v3'); }
});
add_action('edited_ap_cat',  function () { delete_transient('ap_categories_cache_v1'); delete_transient('ap_products_cache_v3'); });
add_action('created_ap_cat', function () { delete_transient('ap_categories_cache_v1'); });
add_action('delete_ap_cat',  function () { delete_transient('ap_categories_cache_v1'); delete_transient('ap_products_cache_v3'); });
/* با هر تغییر در برند/مدل/سالِ خودرو، کشِ فیلترِ «انتخاب خودرو» و لیست محصولات باطل می‌شه */
add_action('edited_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v1'); delete_transient('ap_products_cache_v3'); });
add_action('created_ap_vehicle', function () { delete_transient('ap_vehicles_cache_v1'); });
add_action('delete_ap_vehicle',  function () { delete_transient('ap_vehicles_cache_v1'); delete_transient('ap_products_cache_v3'); });

/* برای انتخاب چند تصویر محصول، کتابخانه‌ی رسانه‌ی وردپرس (wp.media) لازمه. */
add_action('admin_enqueue_scripts', function ($hook) {
    global $post_type;
    if (in_array($hook, ['post.php', 'post-new.php'], true) && $post_type === 'ap_product') {
        wp_enqueue_media();
    }
});

/* ستون قیمت در لیست محصولاتِ پیشخوان، برای دیدِ سریع‌تر مدیر سایت. */
add_filter('manage_ap_product_posts_columns', function ($columns) {
    $columns['ap_price'] = 'قیمت (تومان)';
    return $columns;
});
add_action('manage_ap_product_posts_custom_column', function ($column, $post_id) {
    if ($column === 'ap_price') {
        $price = (int) get_post_meta($post_id, '_ap_price', true);
        echo $price ? esc_html(number_format($price)) : 'تماس بگیرید';
    }
}, 10, 2);

/* =========================================================================
 * نوار بالای سایت — از پیشخوان ← شخصی‌سازی (Customizer) قابل تنظیمه.
 * ========================================================================= */
function ap_customize_register($wp_customize) {
    $wp_customize->add_section('ap_topbar', [
        'title'    => 'نوار بالای سایت',
        'priority' => 30,
    ]);

    $wp_customize->add_setting('ap_topbar_enabled', [
        'default'           => true,
        'sanitize_callback' => 'rest_sanitize_boolean',
    ]);
    $wp_customize->add_control('ap_topbar_enabled', [
        'label'   => 'نمایش نوار بالای سایت',
        'section' => 'ap_topbar',
        'type'    => 'checkbox',
    ]);

    $wp_customize->add_setting('ap_contact_phone', [
        'default'           => '09109973428',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('ap_contact_phone', [
        'label'       => 'شماره تماس',
        'description' => 'در نوار بالای سایت (کنار آیکون تلفن) نمایش داده می‌شه.',
        'section'     => 'ap_topbar',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('ap_contact_whatsapp', [
        'default'           => '09109973428',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('ap_contact_whatsapp', [
        'label'       => 'شماره واتساپ',
        'description' => 'روی کلیک، گفتگوی واتساپ با همین شماره باز می‌شه.',
        'section'     => 'ap_topbar',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('ap_instagram_url', [
        'default' => 'https://www.instagram.com/auto.nexv.ir',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('ap_instagram_url', [
        'label'       => 'لینک صفحه‌ی اینستاگرام',
        'description' => 'در نوار بالای سایت، کنار شماره تماس و واتساپ نمایش داده می‌شه.',
        'section'     => 'ap_topbar',
        'type'        => 'text',
    ]);

    $wp_customize->add_setting('ap_topbar_bg', [
        'default'           => '#ff5563',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'ap_topbar_bg', [
        'label'   => 'رنگ پس‌زمینه‌ی نوار',
        'section' => 'ap_topbar',
    ]));
}

/* ---------- پیدا کردنِ یک دسته زیرِ یک والدِ مشخص ----------
   مقدارِ ورودی می‌تونه «اسلاگ» باشه یا «نامِ نمایشی»؛ هر دو کار می‌کنن.
   قبلاً فقط با نام جست‌وجو می‌شد و فایل‌هایی که ستونِ Parent (یا Categories) رو
   با اسلاگ پر کرده بودن، «والد پیدا نشد» می‌گرفتن و ردیف وارد نمی‌شد. */
/* ------------------------------------------------------------------
   قیمتِ جدا برای هر متغیر (ترکیبِ رنگ / حجم / گروه‌های سفارشی)
   نقشه‌ی قیمت‌ها توی _ap_variant_prices به شکل JSON ذخیره می‌شه:
     { "<کلیدِ ترکیب>": 4500000, ... }
   کلیدِ ترکیب = مقدارها به ترتیبِ [رنگ، حجم، گروه‌ها به ترتیبِ خودشون]
   که با «|~|» به هم چسبیدن. اگه محصول یکی از این بُعدها رو نداشته باشه،
   جای اون رشته‌ی خالی می‌مونه تا جایگاه‌ها همیشه ثابت بمونن و سمتِ
   مرورگر و سمتِ سرور دقیقاً یک کلید بسازن.
------------------------------------------------------------------- */
function ap_variant_key($values) {
    $parts = array_map(function ($v) { return trim((string) $v); }, (array) $values);
    return implode('|~|', $parts);
}

function ap_norm_variant_key($k) {
    $parts = array_map(function ($v) {
        $v = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹','٠','١','٢','٣','٤','٥','٦','٧','٨','٩'], ['0','1','2','3','4','5','6','7','8','9','0','1','2','3','4','5','6','7','8','9'], (string) $v);
        $v = str_replace(["\xE2\x80\x8C", "\xE2\x80\x8E", "\xE2\x80\x8F", "\xC2\xA0", 'ي', 'ك'], [' ', ' ', ' ', ' ', 'ی', 'ک'], $v);
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $v)), 'UTF-8');
    }, explode('|~|', (string) $k));
    while ($parts && end($parts) === '') array_pop($parts);
    return implode('|~|', $parts);
}

function ap_variant_prices($post_id) {
    $raw = get_post_meta($post_id, '_ap_variant_prices', true);
    if (!$raw) return [];
    $decoded = json_decode((string) $raw, true);
    if (!is_array($decoded)) return [];
    $out = [];
    foreach ($decoded as $k => $v) {
        $v = (int) $v;
        if ($v > 0) $out[(string) $k] = $v;
    }
    return $out;
}

/* قیمتِ نهاییِ یک ترکیب: اگه قیمتِ اختصاصی داشته باشه همون، وگرنه قیمتِ پایه. */
function ap_price_for_variant($post_id, $values) {
    $map = ap_variant_prices($post_id);
    $key = ap_variant_key($values);
    if (isset($map[$key])) return (int) $map[$key];
    $n = ap_norm_variant_key($key);
    foreach ($map as $k => $v) { if (ap_norm_variant_key($k) === $n) return (int) $v; }
    return (int) get_post_meta($post_id, '_ap_price', true);
}

/* ------------------------------------------------------------------
   تاریخِ آخرین به‌روزرسانیِ قیمت
   هر جا قیمتِ یک محصول ذخیره می‌شه (پیشخوان یا درون‌ریزیِ CSV) از همین
   تابع رد می‌شه؛ اگه عددِ قیمت واقعاً عوض شده باشه، زمانِ همون لحظه توی
   _ap_price_updated ثبت می‌شه. اگه قیمت تغییری نکرده، تاریخِ قبلی دست
   نمی‌خوره تا یه ذخیره‌ی بی‌تغییر، تاریخ رو الکی جلو نبره.
------------------------------------------------------------------- */
function ap_save_price_with_date($post_id, $price) {
    $price = (int) $price;
    $old   = get_post_meta($post_id, '_ap_price', true);
    $isNew = ($old === '' || $old === null);
    update_post_meta($post_id, '_ap_price', $price);
    $hasDate = get_post_meta($post_id, '_ap_price_updated', true) !== '';
    // اگه تاریخی از قبل ثبت نشده باشه (محصولاتی که قبل از این قابلیت ساخته شدن)،
    // همین ذخیره‌ی فعلی به‌عنوان اولین تاریخ ثبت می‌شه
    if ($isNew || !$hasDate || (int) $old !== $price) {
        if ($price > 0) {
            update_post_meta($post_id, '_ap_price_updated', (int) current_time('timestamp'));
        } else {
            // قیمت برداشته شده («تماس بگیرید»)، پس تاریخ هم معنایی نداره
            delete_post_meta($post_id, '_ap_price_updated');
        }
    }
}

if (!function_exists('ap_gregorian_to_jalali')) {
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
}

/* تبدیلِ رقم‌های لاتین به فارسی، برای شماره‌گذاریِ درستِ متن‌های فارسی. */
if (!function_exists('ap_fa_digits')) {
    function ap_fa_digits($v) {
        return strtr((string) $v, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']);
    }
}

/* «۱۴ مهر ۱۴۰۵» از یک timestamp؛ اگه تاریخی ثبت نشده باشه رشته‌ی خالی برمی‌گردونه. */
function ap_jalali_pretty($ts) {
    $ts = (int) $ts;
    if ($ts <= 0) return '';
    $months = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
    $digits = ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹'];
    list($jy, $jm, $jd) = ap_gregorian_to_jalali((int) date('Y', $ts), (int) date('n', $ts), (int) date('j', $ts));
    $name = $months[max(0, min(11, $jm - 1))];
    return strtr((string) $jd, $digits) . ' ' . $name . ' ' . strtr((string) $jy, $digits);
}

/* رشته‌ی آماده‌ی نمایش برای کنارِ قیمتِ یک محصول (خالی، اگه قیمت یا تاریخ نباشه). */
function ap_price_updated_label($post_id) {
    /* محصولی که فقط قیمتِ متغیرها رو داره (قیمتِ پایه صفره) هم تاریخ می‌گیره */
    $hasVariant = (bool) ap_variant_prices($post_id);
    if ((int) get_post_meta($post_id, '_ap_price', true) <= 0 && !$hasVariant) return '';
    $ts = get_post_meta($post_id, '_ap_price_updated', true);
    if ($ts === '' && $hasVariant) $ts = (int) get_post_modified_time('U', false, $post_id);
    return ap_jalali_pretty($ts);
}

function ap_find_cat_in_parent($value, $parentId = 0, $allowGlobalName = true) {
    $value = trim((string) $value);
    if ($value === '') return 0;

    /* ۱) اسلاگ: اسلاگ در وردپرس در کلِ یک تاکسونومی یکتاست، پس محدود کردنش به والد
       لازم نیست و حتی مضره؛ فایل‌هایی که ستونِ Parent رو فقط با اسلاگِ دسته پر می‌کنن
       (مثل «iphone-parts» بدون نوشتنِ مسیرِ کامل) این‌طوری درست پیدا می‌شن. */
    /* اسلاگِ فارسی در وردپرس به شکلِ Percent-Encoded ذخیره می‌شه (%d8%a2...). فایل‌های
       واقعی گاهی اسلاگِ کدشده دارن و گاهی همون فارسیِ خوانا («خرید-اپل-واچ»)؛ پس هر دو
       شکل (و شکلِ نرمال‌شده‌ی وردپرس) امتحان می‌شه تا والد در هر صورت پیدا بشه. */
    $slugTries = [$value];
    $decoded = rawurldecode($value);
    if ($decoded !== $value) $slugTries[] = $decoded;
    $sanitized = sanitize_title($value);
    if ($sanitized !== '' && !in_array($sanitized, $slugTries, true)) $slugTries[] = $sanitized;
    $sanitizedDecoded = sanitize_title($decoded);
    if ($sanitizedDecoded !== '' && !in_array($sanitizedDecoded, $slugTries, true)) $slugTries[] = $sanitizedDecoded;

    $bySlug = get_terms([
        'taxonomy'   => 'ap_cat',
        'slug'       => $slugTries,
        'hide_empty' => false,
        'number'     => 1,
    ]);
    if (!is_wp_error($bySlug) && !empty($bySlug)) return (int) $bySlug[0]->term_id;

    // ۲) نامِ دقیق، محدود به همین والد
    $byName = get_terms([
        'taxonomy'   => 'ap_cat',
        'name'       => $value,
        'parent'     => $parentId,
        'hide_empty' => false,
        'number'     => 1,
    ]);
    if (!is_wp_error($byName) && !empty($byName)) return (int) $byName[0]->term_id;

    // ۳) روشِ خودِ وردپرس (نام یا اسلاگِ نرمال‌شده)، محدود به همین والد
    $existing = term_exists($value, 'ap_cat', $parentId);
    if ($existing) return (int) (is_array($existing) ? $existing['term_id'] : $existing);

    /* ۴) نام، در کلِ سایت — فقط وقتی دقیقاً یک دسته با این نام وجود داره.
       این برای پیدا کردنِ «والد» مفیده، ولی موقعِ ساختِ خودِ دسته باید خاموش باشه
       ($allowGlobalName = false)؛ وگرنه ردیفِ «سری 1»ِ زیرِ «خرید» به دسته‌ی هم‌نامِ
       زیرِ «قطعات» وصل می‌شد و ۴۵۴ ردیفِ فایل آخرش فقط ~۹۰ دسته می‌ساخت. */
    if (!$allowGlobalName) return 0;
    $anyName = get_terms([
        'taxonomy'   => 'ap_cat',
        'name'       => $value,
        'hide_empty' => false,
    ]);
    if (!is_wp_error($anyName) && count($anyName) === 1) return (int) $anyName[0]->term_id;

    return 0;
}

/* نرمال‌سازیِ متنِ فارسی برای مقایسه: ارقامِ فارسی/عربی به انگلیسی، حذفِ نیم‌فاصله،
   یکسان‌سازیِ ی/ک، و حذفِ فاصله و نشانه‌ها. با این کار «آیپد پرو ۱۲.۹/۱۳ اینچ» و
   «آیپد پرو 13 اینچ» قابلِ مقایسه می‌شن. */
function ap_norm_fa($s) {
    $s = (string) $s;
    $s = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], ['0','1','2','3','4','5','6','7','8','9'], $s);
    $s = str_replace(['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'], ['0','1','2','3','4','5','6','7','8','9'], $s);
    $s = str_replace(['ي', 'ك', "\xE2\x80\x8C", "\xE2\x80\x8E", "\xE2\x80\x8F"], ['ی', 'ک', '', '', ''], $s);
    // حذفِ فاصله و نشانه‌ها تا «آیپد پرو ۱۲.۹/۱۳ اینچ» و «آیپد پرو 13 اینچ» قابلِ مقایسه بشن
    $s = str_replace([' ', "\t", "\r", "\n", '.', '-', '_', '(', ')', '[', ']', '،', ',', '/', '\\', '«', '»', '"', "'"], '', $s);
    return trim(mb_strtolower($s, 'UTF-8'));
}

/* همه‌ی دسته‌ها یک‌بار خونده و کش می‌شن (برای فایل‌های چندهزار ردیفی مهمه). */
function ap_all_cats($fresh = false) {
    static $cache = null;
    if ($fresh) $cache = null;
    if ($cache !== null) return $cache;
    $terms = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false]);
    $cache = is_wp_error($terms) ? [] : $terms;
    return $cache;
}

/* زنجیره‌ی والدهای یک دسته، از ریشه تا خودش (آرایه‌ای از نام‌ها). */
function ap_cat_chain($termId) {
    $names = [];
    $guard = 0;
    $t = get_term($termId, 'ap_cat');
    while ($t && !is_wp_error($t) && $guard++ < 10) {
        array_unshift($names, $t->name);
        if (!$t->parent) break;
        $t = get_term($t->parent, 'ap_cat');
    }
    return $names;
}

/* ---------- پیدا کردنِ دسته‌ی درست برای یک محصول ----------
   در فایل‌های واقعیِ سایت، ستونِ Categories فقط «نامِ آخرین دسته» رو داره (مثل «نسل ۸»)
   در حالی که همون نام زیرِ چند شاخه‌ی مختلف هست (قطعات/خرید/لوازم جانبی و چند مدلِ متفاوت).
   این تابع بینِ همه‌ی دسته‌های هم‌نام، اونی رو انتخاب می‌کنه که والدهاش بیشترین
   هم‌خوانی رو با نامِ خودِ محصول دارن؛ و اگه باز هم مساوی بودن، شاخه‌ی «قطعات» رو
   ترجیح می‌ده (چون محصولاتِ این فروشگاه قطعه‌ان). */
function ap_resolve_cat_for_product($value, $productTitle = '') {
    $value = trim((string) $value);
    if ($value === '') return 0;

    // مسیرِ کامل («A > B > C») یا اسلاگ، همون روالِ قطعیِ قبلی
    if (strpos($value, '>') !== false) return ap_resolve_cat_path($value);
    $direct = ap_find_cat_in_parent($value, 0);

    $needle = ap_norm_fa($value);
    $cands = [];
    foreach (ap_all_cats() as $t) {
        if (ap_norm_fa($t->name) === $needle || $t->slug === $value) $cands[] = $t;
    }
    if (!$cands) return $direct ?: 0;
    if (count($cands) === 1) return (int) $cands[0]->term_id;

    $title = ap_norm_fa($productTitle);
    $best = 0; $bestScore = -1; $tie = false;
    foreach ($cands as $t) {
        $chain = ap_cat_chain($t->term_id);
        array_pop($chain);              // خودِ دسته رو کنار می‌ذاریم؛ فقط والدها مهم‌ان
        $score = 0;
        foreach ($chain as $ancestor) {
            $a = ap_norm_fa($ancestor);
            if ($a === '') continue;
            if (mb_strpos($a, 'قطعات') !== false) { $score += 2; continue; }   // ترجیحِ شاخه‌ی قطعات
            if (mb_strpos($a, 'خرید') !== false || mb_strpos($a, 'لوازمجانبی') !== false) { $score -= 1; continue; }
            if ($title !== '' && mb_strpos($title, $a) !== false) { $score += 6; continue; }
            // تطبیقِ جزئی: چند تکه‌ی اولِ نامِ والد (مثل «آیپدپرو» از «آیپدپرو12.9/13اینچ»)
            $len = mb_strlen($a);
            for ($cut = $len; $cut >= 6; $cut--) {
                if ($title !== '' && mb_strpos($title, mb_substr($a, 0, $cut)) !== false) { $score += 3; break; }
            }
        }
        if ($score > $bestScore) { $bestScore = $score; $best = (int) $t->term_id; $tie = false; }
        elseif ($score === $bestScore) { $tie = true; }
    }
    if ($tie && $bestScore <= 0) return $direct ?: 0;   // واقعاً قابلِ تشخیص نیست
    return $best ?: ($direct ?: 0);
}

/* ---------- انتخابِ دسته‌ی درست بینِ چند دسته‌ی هم‌نام، با کمکِ «متنِ کمکی» ----------
   در فایل‌های واقعی، ستونِ Parent گاهی فقط نامه («سری 1») در حالی که سه دسته با همین نام
   زیرِ «قطعات»، «خرید» و «لوازم جانبی» وجود داره. ولی اسلاگِ خودِ ردیفِ فرزند معمولاً
   شاخه رو در خودش داره (مثل «۳۸-میلی‌متر-سری-1-قطعات-اپل-واچ»). این تابع از همون
   اسلاگ استفاده می‌کنه تا والدِ درست انتخاب بشه. */
function ap_find_cat_by_name_ctx($name, $parentId, $context) {
    $name = trim((string) $name);
    if ($name === '') return 0;

    /* مهم: اول باید دید چند دسته با این «نام» هست. اگه چند تا بود، هرگز نباید سراغِ
       جست‌وجوی اسلاگ رفت؛ چون نامی مثل «سری 1» وقتی به اسلاگ تبدیل می‌شه دقیقاً برابرِ
       اسلاگِ یکی از همون چند دسته‌ست و همیشه همون یکی (شاخه‌ی «خرید») انتخاب می‌شد و
       بقیه‌ی زیرشاخه‌ها اشتباه زیرِ اون می‌رفتن. */

    /* کاندیدها مستقیم از دیتابیس خونده می‌شن (نه از کش)، چون وسطِ همین درون‌ریزی
       دسته‌های جدید ساخته می‌شن و کش قدیمی می‌شه. */
    $cands = get_terms(['taxonomy' => 'ap_cat', 'name' => $name, 'hide_empty' => false]);
    if (is_wp_error($cands) || !$cands) {
        $needle = ap_norm_fa($name);
        $cands = [];
        foreach (ap_all_cats(true) as $t) {
            if (ap_norm_fa($t->name) === $needle) $cands[] = $t;
        }
    }
    // هیچ دسته‌ای با این نام نیست → احتمالاً اسلاگ بوده، نه نام
    if (!$cands) return ap_find_cat_in_parent($name, $parentId, false);
    if (count($cands) === 1) return (int) $cands[0]->term_id;

    // چند دسته‌ی هم‌نام: اگه دقیقاً یکی‌شون زیرِ همین والده، همون درسته
    $inParent = [];
    foreach ($cands as $t) { if ((int) $t->parent === (int) $parentId) $inParent[] = $t; }
    if (count($inParent) === 1) return (int) $inParent[0]->term_id;
    if (count($inParent) > 1) $cands = $inParent;

    $ctx = ap_norm_fa(rawurldecode((string) $context));
    $best = 0; $bestLen = -1;
    foreach ($cands as $t) {
        $slugTxt = ap_norm_fa(rawurldecode($t->slug));
        if ($slugTxt !== '' && $ctx !== '' && mb_strpos($ctx, $slugTxt) !== false) {
            // بلندترین تطبیق برنده‌ست (دقیق‌ترین شاخه)
            $len = mb_strlen($slugTxt);
            if ($len > $bestLen) { $bestLen = $len; $best = (int) $t->term_id; }
        }
    }
    if ($best) return $best;

    // اگه از اسلاگ نشد، از نامِ شاخه‌های والدِ هر کاندید کمک بگیر
    foreach ($cands as $t) {
        foreach (ap_cat_chain($t->term_id) as $anc) {
            $a = ap_norm_fa($anc);
            if ($a !== '' && $ctx !== '' && mb_strpos($ctx, $a) !== false) {
                $len = mb_strlen($a);
                if ($len > $bestLen) { $bestLen = $len; $best = (int) $t->term_id; }
            }
        }
    }
    return $best;
}

function ap_cat_child_by_name($name, $parentId) {
    $needle = ap_norm_fa($name);
    $kids = get_terms(['taxonomy' => 'ap_cat', 'parent' => (int) $parentId, 'hide_empty' => false]);
    if (is_wp_error($kids)) return 0;
    foreach ($kids as $k) { if (ap_norm_fa($k->name) === $needle) return (int) $k->term_id; }
    return 0;
}
/* مسیرِ کاملِ دسته («آیفون > قطعات آیفون > آیفون 15» یا با اسلاگ) رو به آیدیِ برگ تبدیل می‌کنه. */
function ap_resolve_cat_path($path) {
    $parts = array_filter(array_map('trim', explode('>', (string) $path)), 'strlen');
    $parentId = 0; $leafId = 0;
    foreach ($parts as $part) {
        /* اول نامِ دقیق زیرِ همین والد؛ قبلاً اول اسلاگ (بدون توجه به والد) چک می‌شد و
           مثلاً «سری 18» در مسیرِ «قطعات آیفون» به «سری 18»ی شاخه‌ی «خرید آیفون» می‌رسید. */
        $id = ap_cat_child_by_name($part, $parentId);
        if (!$id) $id = ap_find_cat_in_parent($part, $parentId);
        if (!$id) return 0;
        $parentId = $id; $leafId = $id;
    }
    return $leafId;
}

add_action('customize_register', 'ap_customize_register');

/* ---------- ویترینِ بخشِ اولِ صفحه (باکسِ شیشه‌ای) ----------
   پیشخوان ← نمایش ← سفارشی‌سازی ← «ویترین صفحه اول»
   تا ۶ عکسِ محصول + نام؛ عکس‌ها داخل باکسِ شیشه‌ای یکی‌یکی نمایش داده می‌شن.
   بهترین نتیجه: PNG با پس‌زمینه‌ی شفاف یا عکس با پس‌زمینه‌ی سفید (سفید خودکار
   با رنگِ باکس یکی می‌شه). اگه هیچ عکسی انتخاب نشه، آیکون‌های پیش‌فرض نمایش داده می‌شن. */
function ap_customize_hero_showcase($wp_customize) {
    $wp_customize->add_section('ap_hero_showcase', [
        'title'       => 'ویترین صفحه اول',
        'description' => 'پوستر افقی بالای صفحه‌ی اول: نام مدل (انگلیسی) بزرگ و بولد در پشت، و عکس محصول ایستاده با کمی زاویه در سمت راست. بهترین نتیجه: عکس رو‌به‌روی محصول، ایستاده، PNG بدون پس‌زمینه (یا با پس‌زمینه‌ی سفید که خودکار همرنگ پوستر میشه). اندازه‌ی پیشنهادی: ارتفاع حدود ۱۲۰۰ پیکسل. فقط از عکس‌هایی استفاده کن که حق استفاده‌شون رو داری.',
        'priority'    => 31,
    ]);
    for ($i = 1; $i <= 6; $i++) {
        $wp_customize->add_setting("ap_hero_img_$i", ['default' => '', 'sanitize_callback' => 'esc_url_raw']);
        $wp_customize->add_control(new WP_Customize_Image_Control($wp_customize, "ap_hero_img_$i", [
            'label'   => "عکس محصول $i",
            'section' => 'ap_hero_showcase',
        ]));
        $wp_customize->add_setting("ap_hero_name_$i", ['default' => '', 'sanitize_callback' => 'sanitize_text_field']);
        $wp_customize->add_control("ap_hero_name_$i", [
            'label'       => "نام مدل $i (انگلیسی)",
            'description' => 'به انگلیسی و با فونت بولد پشت محصول نمایش داده می‌شه؛ مثلاً: iPhone 17 Pro',
            'section'     => 'ap_hero_showcase',
            'type'        => 'text',
        ]);
    }
}
add_action('customize_register', 'ap_customize_hero_showcase');

/* ---------- بیلبوردهای تبلیغاتیِ صفحه‌ی اول ----------
   پیشخوان ← نمایش ← سفارشی‌سازی ← «بیلبوردهای تبلیغاتی» */
function ap_customize_billboards($wp_customize) {
    $wp_customize->add_section('ap_billboards', [
        'title'       => 'بیلبوردهای تبلیغاتی',
        'description' => 'دو بیلبوردِ متحرکِ زیرِ باکس‌های ویژگی در صفحه‌ی اول. اگه فیلدی خالی بمونه، متنِ پیش‌فرض نمایش داده می‌شه.',
        'priority'    => 32,
    ]);
    $fields = [
        'ap_bb_deal_badge' => ['برچسب بیلبورد تخفیف', '٪ پیشنهاد ویژه', 'text'],
        'ap_bb_deal_title' => ['تیتر بیلبورد تخفیف', 'تخفیف‌های ویژه‌ی قطعات یدکی', 'text'],
        'ap_bb_deal_text'  => ['متن بیلبورد تخفیف', 'پیشنهادهای محدودِ این هفته روی قطعاتِ اورجینال', 'textarea'],
        'ap_bb_deal_link'  => ['لینک بیلبورد تخفیف (خالی = پرش به محصولات)', '', 'url'],
        'ap_bb_ticker'     => ['کلمه‌های نوار متحرک (با ویرگول جدا کن)', 'سپر، چراغ، رادیاتور، آینه، گلگیر، قطعات موتوری، قطعات اورجینال', 'text'],
        'ap_bb_help_title' => ['تیتر بیلبورد مشاوره', 'مطمئن نیستی کدوم قطعه؟', 'text'],
        'ap_bb_help_text'  => ['متن بیلبورد مشاوره', 'مدل دستگاهت رو بفرست؛ قطعه‌ی دقیق و سازگار رو رایگان برات پیدا می‌کنیم.', 'textarea'],
    ];
    foreach ($fields as $id => [$label, $default, $type]) {
        $wp_customize->add_setting($id, [
            'default' => $default,
            'sanitize_callback' => $type === 'url' ? 'esc_url_raw' : ($type === 'textarea' ? 'sanitize_textarea_field' : 'sanitize_text_field'),
        ]);
        $wp_customize->add_control($id, ['label' => $label, 'section' => 'ap_billboards', 'type' => $type === 'url' ? 'url' : $type]);
    }
}
add_action('customize_register', 'ap_customize_billboards');

function ap_hero_slides() {
    $slides = [];
    for ($i = 1; $i <= 6; $i++) {
        $src = trim((string) get_theme_mod("ap_hero_img_$i", ''));
        if ($src === '') continue;
        $slides[] = ['src' => esc_url($src), 'name' => (string) get_theme_mod("ap_hero_name_$i", '')];
    }
    if ($slides) return $slides;

    /* عکسِ پیش‌فرض: پوسترِ خودرو که همراه قالب می‌آد (پوشه‌ی hero). به‌محضِ اینکه از
       «سفارشی‌سازی ← ویترین صفحه اول» عکس بذاری، این‌ها کنار می‌رن. اگه این فایل هم
       نبود، خودِ سایت پوسترهای آیکونیِ دسته‌ها رو نشون می‌ده. */
    $dir = get_template_directory() . '/hero/';
    $u   = get_template_directory_uri() . '/hero/';
    $out = [];
    for ($i = 1; $i <= 4; $i++) {
        if (file_exists($dir . "car-$i.jpg")) {
            $out[] = ['src' => $u . "car-$i.jpg", 'name' => '', 'bg' => '#ffffff', 'wide' => true, 'fit' => true];
        }
    }
    return $out;
}

/* به‌روزرسانی خودکار قالب از گیت‌هاب (updater.php) */
require_once __DIR__ . '/updater.php';

/* یک‌بار: شماره‌ی تماس و واتساپ سایت به شماره‌ی جدید عوض بشه (حتی اگه قبلاً از سفارشی‌سازی ذخیره شده بود). */
add_action('init', function () {
    if (get_option('ap_phone_migrated_v1')) return;
    set_theme_mod('ap_contact_phone', '09109973428');
    set_theme_mod('ap_contact_whatsapp', '09109973428');
    update_option('ap_phone_migrated_v1', 1);
});

/* =========================================================================
 * سایت‌مپ Rank Math: مقالات و دسته‌بندی‌ها هم حتماً داخل سایت‌مپ باشن،
 * هر سایت‌مپ تا ۱۰۰۰ لینک، و عکس‌های گالریِ محصول هم برای Google Images.
 * ========================================================================= */
add_filter('rank_math/sitemap/exclude_post_type', function ($exclude, $type) {
    return in_array($type, ['ap_product', 'ap_article'], true) ? false : $exclude;
}, 20, 2);
add_filter('rank_math/sitemap/exclude_taxonomy', function ($exclude, $type) {
    return in_array($type, ['ap_cat', 'ap_vehicle'], true) ? false : $exclude;   /* برند/مدل/تیپِ خودرو هم توی سایت‌مپ */
}, 20, 2);
add_filter('rank_math/sitemap/entries_per_page', function () { return 1000; });
add_filter('rank_math/sitemap/urlimages', function ($images, $post_id) {
    if (get_post_type($post_id) !== 'ap_product' || !function_exists('ap_gallery_urls')) return $images;
    $have = wp_list_pluck((array) $images, 'src');
    $title = get_the_title($post_id);
    foreach ((array) ap_gallery_urls($post_id) as $src) {
        if ($src && !in_array($src, $have, true)) { $images[] = ['src' => $src, 'title' => $title, 'alt' => $title]; $have[] = $src; }
    }
    return $images;
}, 20, 2);

/* =========================================================================
 * صفحه‌های کمکیِ سبد خرید/حساب: در گوگل ایندکس نشن و در سایت‌مپ نباشن؛
 * «برگه نمونه»ی پیش‌فرض وردپرس هم به صفحه‌ی اصلی ریدایرکت (۳۰۱) می‌شه.
 * ========================================================================= */
function ap_utility_page_slugs() { return ['cart', 'checkout', 'my-account', 'shop']; }
add_action('template_redirect', function () {
    if (is_page(['برگه-نمونه', 'sample-page'])) { wp_safe_redirect(home_url('/'), 301); exit; }
});
add_filter('wp_robots', function ($robots) {
    return $robots;
}, 99);
add_filter('rank_math/frontend/robots', function ($robots) {
    return $robots;
}, 99);
add_filter('rank_math/sitemap/entry', function ($url, $type, $object) {
    if ($type === 'post' && $object instanceof WP_Post && $object->post_type === 'page'
        && in_array(urldecode($object->post_name), ['برگه-نمونه', 'sample-page'], true)) {
        return false;
    }
    return $url;
}, 20, 3);

/* =========================================================================
 * پیوند یکتای انگلیسی برای دسته‌بندی‌های محصول (ap_cat)
 * هر دسته‌ای که ساخته/ویرایش بشه و اسلاگش فارسی باشه، از روی نامش انگلیسی می‌شه
 * (مثلاً «قطعات آیفون» -> iphone-parts). اگه تکراری بود، اسلاگِ والد به آخرش اضافه می‌شه.
 * اسلاگِ فارسیِ قبلی نگه داشته می‌شه تا لینک‌های قدیمی با ۳۰۱ به لینکِ جدید برن.
 * ========================================================================= */
function ap_slug_is_english($slug) {
    return $slug !== '' && strpos($slug, '%') === false && !preg_match('/[^\x00-\x7f]/', $slug);
}
function ap_english_term_slug($term) {
    $base = ap_transliterate_title_to_slug($term->name);
    $slug = $base;
    $taken = function ($s) use ($term) { $t = get_term_by('slug', $s, 'ap_cat'); return $t && (int) $t->term_id !== (int) $term->term_id; };
    if ($taken($slug) && $term->parent) {
        $parent = get_term($term->parent, 'ap_cat');
        if ($parent && !is_wp_error($parent)) $slug = $base . '-' . (ap_slug_is_english($parent->slug) ? $parent->slug : ap_transliterate_title_to_slug($parent->name));
    }
    $i = 2; $try = $slug;
    while ($taken($try)) { $try = $slug . '-' . $i++; }
    return $try;
}
function ap_fix_category_slug($term_id) {
    static $busy = false;
    if ($busy) return false;
    $term = get_term($term_id, 'ap_cat');
    if (!$term || is_wp_error($term) || ap_slug_is_english($term->slug)) return false;
    $busy = true;
    add_term_meta($term->term_id, '_ap_old_slug', urldecode($term->slug));
    $r = wp_update_term($term->term_id, 'ap_cat', ['slug' => ap_english_term_slug($term)]);
    $busy = false;
    return !is_wp_error($r);
}
add_action('created_ap_cat', 'ap_fix_category_slug', 20);
add_action('edited_ap_cat', 'ap_fix_category_slug', 20);

function ap_fix_all_category_slugs() {
    $fixed = 0;
    /* اول والدها، بعد فرزندها (ترتیب بر اساس عمق) تا اسلاگِ والد قبل از فرزند انگلیسی شده باشه */
    $terms = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false]);
    if (is_wp_error($terms)) return 0;
    usort($terms, function ($a, $b) { return count(get_ancestors($a->term_id, 'ap_cat')) - count(get_ancestors($b->term_id, 'ap_cat')); });
    foreach ($terms as $t) { if (ap_fix_category_slug($t->term_id)) $fixed++; }
    delete_transient('ap_categories_cache_v1');
    delete_transient('ap_products_cache_v2');
    flush_rewrite_rules();
    return $fixed;
}
/* یک بار به‌صورت خودکار بعد از به‌روزرسانیِ قالب اجرا می‌شه */
add_action('admin_init', function () {
    if (get_option('ap_cat_slugs_en_v1') || !current_user_can('manage_options')) return;
    @set_time_limit(0);
    ap_fix_all_category_slugs();
    update_option('ap_cat_slugs_en_v1', 1, false);
});

/* لینک‌های قدیمیِ فارسیِ دسته‌بندی -> ۳۰۱ به لینکِ انگلیسیِ جدید */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = trim(urldecode(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)), '/');
    if (!preg_match('#(?:^|/)product-cat/(.+)$#u', $path, $m)) return;
    $parts = explode('/', trim($m[1], '/'));
    $last = end($parts);
    $found = get_terms(['taxonomy' => 'ap_cat', 'hide_empty' => false, 'number' => 1, 'meta_key' => '_ap_old_slug', 'meta_value' => $last]);
    if (!$found || is_wp_error($found)) { $t = get_term_by('slug', sanitize_title($last), 'ap_cat'); $found = $t ? [$t] : []; }
    if ($found) { $link = get_term_link($found[0]); if (!is_wp_error($link)) { wp_redirect($link, 301); exit; } }
}, 1);

/* =========================================================================
 * لوگوی سایت (logo.png در پوشه‌ی قالب) — برای صفحه‌های مستقل و آیکونِ تب
 * ========================================================================= */
define('AP_ICON_VER', '20261101');
/* آیکون‌های سایت در پوشه‌ی icons قالب (از نسخه‌ی ۴۰۹۶ پیکسلی، با کیفیتِ کامل کوچیک شدن) */
function ap_icon_url($file) { return get_template_directory_uri() . '/icons/' . $file . '?v=' . AP_ICON_VER; }
/* اگه بعداً از پیشخوان یک «نماد سایت» دیگه انتخاب بشه، همون استفاده می‌شه؛ وگرنه آیکون‌های قالب */
function ap_use_theme_icons() {
    return !(function_exists('has_site_icon') && has_site_icon()) || (int) get_option('site_icon') === (int) get_option('ap_site_icon_id');
}
function ap_favicon_tags() {
    if (!ap_use_theme_icons()) {
        $v = '?v=' . (int) get_option('site_icon');
        return '<link rel="icon" href="' . esc_url(get_site_icon_url(32) . $v) . '" sizes="32x32">'
             . '<link rel="icon" href="' . esc_url(get_site_icon_url(192) . $v) . '" sizes="192x192">'
             . '<link rel="apple-touch-icon" href="' . esc_url(get_site_icon_url(180) . $v) . '">';
    }
    $t = '';
    foreach ([16, 32, 48, 96, 192] as $px) {
        $t .= '<link rel="icon" type="image/png" sizes="' . $px . 'x' . $px . '" href="' . esc_url(ap_icon_url('icon-' . $px . '.png')) . '">';
    }
    $t .= '<link rel="shortcut icon" href="' . esc_url(ap_icon_url('favicon.ico')) . '">';
    $t .= '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url(ap_icon_url('apple-touch-icon.png')) . '">';
    return $t;
}
/* «نماد سایت» وردپرس (آیکونِ تبِ پیشخوان، صفحه‌ی ورود، /favicon.ico) یک‌بار با آیکونِ جدید عوض می‌شه.
   اندازه‌ها از قبل با کیفیتِ بالا ساخته شدن و مستقیم استفاده می‌شن (وردپرس دوباره کوچیکشون نمی‌کنه). */
add_action('admin_init', function () {
    if (get_option('ap_site_icon_v') === AP_ICON_VER || !current_user_can('manage_options')) return;
    $src = get_template_directory() . '/icons';
    if (!file_exists($src . '/icon-512.png')) return;
    $up = wp_upload_dir();
    if (!empty($up['error']) || !wp_mkdir_p($up['path'])) return;
    $base = 'ap-site-icon-' . AP_ICON_VER;
    $files = ['' => 'icon-512.png', '-32x32' => 'icon-32.png', '-180x180' => 'apple-touch-icon.png', '-192x192' => 'icon-192.png', '-270x270' => 'icon-270.png'];
    foreach ($files as $suffix => $f) { if (!@copy($src . '/' . $f, trailingslashit($up['path']) . $base . $suffix . '.png')) return; }
    $old_icon = (int) get_option('site_icon');
    $id = wp_insert_attachment([
        'post_title' => get_bloginfo('name') . ' — نماد سایت', 'post_mime_type' => 'image/png', 'post_status' => 'inherit',
        'guid' => trailingslashit($up['url']) . $base . '.png',
    ], trailingslashit($up['path']) . $base . '.png');
    if (!$id || is_wp_error($id)) return;
    $sizes = [];
    foreach ([32, 180, 192, 270] as $px) $sizes['site_icon-' . $px] = ['file' => $base . '-' . $px . 'x' . $px . '.png', 'width' => $px, 'height' => $px, 'mime-type' => 'image/png'];
    wp_update_attachment_metadata($id, ['width' => 512, 'height' => 512, 'file' => ltrim(trailingslashit($up['subdir']), '/') . $base . '.png', 'sizes' => $sizes, 'image_meta' => []]);
    update_post_meta($id, '_wp_attachment_context', 'site-icon');
    update_option('site_icon', $id);
    update_option('ap_site_icon_id', $id, false);
    update_option('ap_site_icon_v', AP_ICON_VER, false);
    /* لوگوی سازمان در Rank Math (اسکیمای گوگل) اگه خالی یا همون آیکونِ قبلی بود، اون هم به‌روز می‌شه */
    $rm = get_option('rank-math-options-titles');
    if (is_array($rm)) {
        $cur_url = (string) ($rm['knowledgegraph_logo'] ?? '');
        $cur_id = (int) ($rm['knowledgegraph_logo_id'] ?? 0);
        if ($cur_url === '' || ($old_icon && $cur_id === $old_icon) || preg_match('#/themes/[^/]+/logo\.png#', $cur_url)) {
            $rm['knowledgegraph_logo'] = wp_get_attachment_url($id);
            $rm['knowledgegraph_logo_id'] = $id;
            update_option('rank-math-options-titles', $rm);
        }
    }
});
/* اگه در وردپرس «نماد سایت» تنظیم نشده، همین لوگو آیکونِ تب و گوگل می‌شه */
add_action('wp_head', function () { if (!has_site_icon()) echo ap_favicon_tags(); }, 2);

/* =========================================================================
 * حذفِ نوشته‌ی پیش‌فرضِ «سلام دنیا!» وردپرس (یک‌بار) + ریدایرکتِ ۳۰۱ آدرسش
 * ========================================================================= */
add_action('admin_init', function () {
    if (get_option('ap_hello_removed') || !current_user_can('manage_options')) return;
    foreach (['hello-world', 'سلام-دنیا', rawurlencode('سلام-دنیا')] as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'post');
        if ($p) wp_delete_post($p->ID, true);
    }
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_title IN ('سلام دنیا!', 'Hello world!')");
    foreach ($ids as $id) wp_delete_post((int) $id, true);
    update_option('ap_hello_removed', 1, false);
});
add_action('template_redirect', function () {
    $path = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if (preg_match('#/(سلام-دنیا|hello-world)/?$#u', $path)) { wp_redirect(home_url('/'), 301); exit; }
}, 0);

/* =========================================================================
 * نوار داینامیکِ پایین برای صفحه‌های مستقل (وبلاگ، مقاله، قوانین، درباره ما)
 * همون ظاهرِ نوارِ صفحه‌ی اصلی؛ دکمه‌ها به بخش‌های فروشگاه می‌برن.
 * ========================================================================= */
function ap_static_dock_parts() {
    static $parts = null;
    if ($parts !== null) return $parts;
    $h = (string) @file_get_contents(get_template_directory() . '/site.html');
    $cut = function ($from, $to, $incTo = false) use ($h) {
        $a = strpos($h, $from); if ($a === false) return '';
        $b = strpos($h, $to, $a + strlen($from)); if ($b === false) return '';
        return substr($h, $a, $b - $a + ($incTo ? strlen($to) : 0));
    };
    $vars  = $cut(":root{\n  /* Liquid Glass", "\n}\n", true) . "\n" . $cut("[data-theme=\"dark\"]{\n  --bg:", "\n}\n", true);
    $dockA = strpos($h, 'DYNAMIC DOCK — نوار پایینِ شناور');
    $dockA = $dockA !== false ? strrpos(substr($h, 0, $dockA), '/*') : false;
    $dockB = $dockA !== false ? strpos($h, 'HERO — طراحیِ جدید', $dockA) : false;
    $dockB = $dockB !== false ? strrpos(substr($h, 0, $dockB), '/*') : false;
    $dockCss = ($dockA !== false && $dockB !== false) ? substr($h, $dockA, $dockB - $dockA) : '';
    $kf = '';
    foreach (['dockJelly', 'bump'] as $k) {
        if (preg_match('/@keyframes ' . $k . '\s*\{(?:[^{}]*\{[^{}]*\})*[^{}]*\}/', $h, $m)) $kf .= $m[0] . "\n";
    }
    $html = $cut('<div class="ap-dock" id="apDock">', "\n<script>");
    $js   = $cut('(function initNexvDock(){', "\n})();", true);
    $parts = compact('vars', 'dockCss', 'kf', 'html', 'js');
    return $parts;
}
function ap_static_dock_html($active = '') {
    $home = home_url('/');
    $d = ap_static_dock_parts();
    ob_start(); ?>
<style>
<?php echo $d['vars']; ?>
<?php echo $d['dockCss']; ?>
<?php echo $d['kf']; ?>
.ap-dock .search-results{display:none !important;}
body{padding-bottom:calc(110px + env(safe-area-inset-bottom)) !important;}
/* پس‌زمینه‌ی ثابت و متحرکِ صفحه‌ی اصلی؛ باکس‌ها به‌صورت شیشه‌ای روش اسکرول می‌شن */
html, body{background:transparent !important;}
html{background:#f3f1f9 !important;} html[data-theme="dark"]{background:#08080d !important;}
#apBg{
  position:fixed; inset:0;
  /* کمی بلندتر از صفحه تا موقعِ جمع‌شدنِ نوار آدرسِ سافاری، لبه‌ی پایین دیده نشه */
  bottom:-120px;
  z-index:-1; pointer-events:none; overflow:hidden; contain:strict;
  background:linear-gradient(165deg, #f5f3fc 0%, #f1f1f8 50%, #f3f1f9 100%);
}
html[data-theme="dark"] #apBg{background:linear-gradient(165deg, #0a0912 0%, #08080d 50%, #0a0a13 100%);}
#apBg .blob{
  position:absolute; border-radius:50%; will-change:transform;
  background:radial-gradient(circle closest-side, var(--c) 0%, color-mix(in srgb, var(--c) 45%, transparent) 45%, transparent 100%);
  animation:var(--anim) var(--dur) ease-in-out infinite alternate;
}
/* رنگ‌های گرم و ملایم: قرمز، مرجانی، هلویی و صورتیِ پودری (هماهنگ با صفحه‌ی اصلی) */
#apBg .b1{--c:rgba(255,90,105,.26); width:78vmax; height:78vmax; left:-22vmax; top:-26vmax; --anim:apDance1; --dur:26s;}
#apBg .b2{--c:rgba(255,130,120,.20); width:62vmax; height:62vmax; right:-20vmax; top:6vh;    --anim:apDance2; --dur:31s;}
#apBg .b3{--c:rgba(255,190,160,.24); width:70vmax; height:70vmax; left:18vw;  bottom:-34vmax; --anim:apDance3; --dur:37s;}
#apBg .b4{--c:rgba(255,175,95,.18); width:54vmax; height:54vmax; left:-16vmax; bottom:4vh;  --anim:apDance4; --dur:43s;}
#apBg .b5{--c:rgba(244,170,185,.16); width:42vmax; height:42vmax; right:4vw;  top:44vh;       --anim:apDance5; --dur:29s;}
html[data-theme="dark"] #apBg .b1{--c:rgba(220,60,80,.24);}
html[data-theme="dark"] #apBg .b2{--c:rgba(255,95,110,.14);}
html[data-theme="dark"] #apBg .b3{--c:rgba(240,120,80,.15);}
html[data-theme="dark"] #apBg .b4{--c:rgba(255,150,70,.13);}
html[data-theme="dark"] #apBg .b5{--c:rgba(210,120,170,.07);}
/* لایه‌ی نورِ نرم برای عمق و خوانایی متن */
#apBg::after{
  content:""; position:absolute; inset:0;
  background:radial-gradient(120% 80% at 50% 0%, rgba(255,255,255,.35), transparent 60%);
}
html[data-theme="dark"] #apBg::after{background:radial-gradient(120% 80% at 50% 0%, rgba(255,255,255,.04), transparent 60%);}

@keyframes apDance1{
  0%  {transform:translate3d(0,0,0) scale(1);}
  50% {transform:translate3d(14vw, 10vh,0) scale(1.12);}
  100%{transform:translate3d(26vw,-4vh,0) scale(.94);}
}
@keyframes apDance2{
  0%  {transform:translate3d(0,0,0) scale(1);}
  50% {transform:translate3d(-18vw,14vh,0) scale(.9);}
  100%{transform:translate3d(-8vw,32vh,0) scale(1.1);}
}
@keyframes apDance3{
  0%  {transform:translate3d(0,0,0) scale(1.05);}
  50% {transform:translate3d(-20vw,-16vh,0) scale(.92);}
  100%{transform:translate3d(10vw,-30vh,0) scale(1.14);}
}
@keyframes apDance4{
  0%  {transform:translate3d(0,0,0) scale(.95);}
  50% {transform:translate3d(22vw,-20vh,0) scale(1.1);}
  100%{transform:translate3d(36vw,-6vh,0) scale(1);}
}
@keyframes apDance5{
  0%  {transform:translate3d(0,0,0) scale(1); opacity:.7;}
  50% {transform:translate3d(-26vw,-22vh,0) scale(1.25); opacity:1;}
  100%{transform:translate3d(-40vw,10vh,0) scale(.9); opacity:.75;}
}
@media (prefers-reduced-motion: reduce){ #apBg .blob{animation:none;} }
.body-content, .blog-card, .terms-box, .about-box, .rp-card{
  background:rgba(255,255,255,.62) !important; border:1px solid rgba(255,255,255,.7) !important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.6), 0 14px 36px rgba(20,10,60,.08) !important;
  -webkit-backdrop-filter:blur(16px) saturate(170%); backdrop-filter:blur(16px) saturate(170%);
}
/* کارت‌های ردیفِ افقی blur نمی‌گیرن (وگرنه یه نوارِ روشن پشتِ ردیف کشیده می‌شه) */
.rp-card{-webkit-backdrop-filter:none !important; backdrop-filter:none !important; background:rgba(255,255,255,.72) !important;}
html[data-theme="dark"] .body-content, html[data-theme="dark"] .blog-card, html[data-theme="dark"] .terms-box,
html[data-theme="dark"] .about-box, html[data-theme="dark"] .rp-card{
  background:rgba(30,30,40,.55) !important; border-color:rgba(255,255,255,.08) !important;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.06), 0 14px 36px rgba(0,0,0,.45) !important;
}
</style>
<div id="apBg" aria-hidden="true"><span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span><span class="blob b4"></span><span class="blob b5"></span></div>
<span id="cartCount" style="display:none">0</span>
<?php echo $d['html']; ?>
<script>
/* همون نوارِ صفحه‌ی اصلی؛ اینجا هر گزینه به بخشِ مربوطش در فروشگاه می‌ره */
(function(){
  var HOME = <?php echo wp_json_encode($home); ?>;
  window.HOME_URL = HOME; window.SINGLE_VIEW_ID = 'static';
  window.currentUser = function(){ return null; };
  window.openAuth = window.openAccount = function(){ location.href = HOME + 'my-account/'; };
  window.openDrawer = function(){ location.href = HOME + 'cart/'; };
  window.renderAdvancedFilter = window.lockBodyScroll = window.renderSearchResults = function(){};
  try{ var c = JSON.parse(localStorage.getItem('ap_cart') || '{}'), n = 0; for(var k in c) n += Number(c[k]) || 0;
       document.getElementById('cartCount').textContent = n; }catch(e){}
  document.addEventListener('keydown', function(e){
    var i = document.getElementById('searchInput');
    if(e.key === 'Enter' && document.activeElement === i && i.value.trim()){
      location.href = HOME + '?ap_search=' + encodeURIComponent(i.value.trim());
    }
  });
})();
var HOME_URL = window.HOME_URL, SINGLE_VIEW_ID = window.SINGLE_VIEW_ID;
<?php echo $d['js']; ?>
</script>
<?php
    return ob_get_clean();
}

/* =========================================================================
 * محصولاتِ مرتبط با یک مقاله (بر اساسِ کلمه‌های عنوانِ مقاله)
 * ========================================================================= */
function ap_related_products_for_text($text, $limit = 8) {
    $n = ap_norm_fa($text);
    $devices = ['آیفون' => 'آیفون', 'iphone' => 'آیفون', 'آیپد' => 'آیپد', 'ipad' => 'آیپد', 'اپلواچ' => 'اپلواچ', 'applewatch' => 'اپلواچ', 'ایرپاد' => 'ایرپاد', 'مکبوک' => 'مکبوک'];
    $parts = ['باتری', 'السیدی', 'مادربرد', 'دوربین', 'بدنه', 'تاچ', 'اسپیکر', 'شارژر', 'کابل', 'قاب', 'گلس', 'فلت'];
    $dev = ''; foreach ($devices as $k => $v) { if (mb_strpos($n, $k) !== false) { $dev = $v; break; } }
    $foundParts = array_values(array_filter($parts, function ($p) use ($n) { return mb_strpos($n, $p) !== false; }));
    preg_match_all('/\d+/', ap_norm_fa($text), $m);
    $nums = array_unique($m[0]);
    $products = get_posts(['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids']);
    $scored = [];
    foreach ($products as $pid) {
        $t = ap_norm_fa(get_the_title($pid));
        $s = 0;
        if ($dev !== '' && mb_strpos($t, $dev) !== false) $s += 3;
        foreach ($foundParts as $p) if (mb_strpos($t, $p) !== false) $s += 4;
        foreach ($nums as $num) if (preg_match('/(?<!\d)' . $num . '(?!\d)/', $t)) $s += 2;
        if ($s >= 3) $scored[$pid] = $s + (has_post_thumbnail($pid) ? 0.5 : 0);
    }
    arsort($scored);
    return array_slice(array_keys($scored), 0, $limit);
}
function ap_related_products_html($ids, $title = 'محصولات مرتبط') {
    if (!$ids) return '';
    ob_start(); ?>
<style>
.rp-wrap{max-width:960px; margin:34px auto 0;}
.rp-wrap h2{font-size:19px; margin:0 4px 14px; font-weight:800;}
.rp-track{display:flex; gap:14px; overflow-x:auto; scroll-snap-type:x mandatory; padding:24px 4px 64px; margin:-24px -4px -58px; scrollbar-width:none; -webkit-overflow-scrolling:touch;}
.rp-track::-webkit-scrollbar{display:none;}
.rp-card{flex:0 0 200px; scroll-snap-align:start; background:#fff; border:1px solid rgba(255,255,255,.7); border-radius:22px; padding:14px; text-decoration:none; color:inherit;
  display:flex; flex-direction:column; box-shadow:inset 0 1px 0 rgba(255,255,255,.6), 0 12px 28px rgba(20,10,60,.08); transition:transform .3s ease;}
.rp-card:hover{transform:translateY(-4px);}
.rp-img{width:100%; aspect-ratio:1/1; border-radius:14px; overflow:hidden; display:grid; place-items:center; margin-bottom:12px;
  background:linear-gradient(160deg,#ff3b4e 0%, rgba(255,59,78,.25) 120%);}
.rp-img img{width:100%; height:100%; object-fit:cover; display:block;}
.rp-img.has{background:#f1f0f6;}
.rp-img svg{width:46px; height:46px; stroke:#fff;}
.rp-card h3{font-size:14px; line-height:1.6; margin:0 0 8px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; min-height:3.2em;}
.rp-price{margin-top:auto; font-weight:800; font-size:14.5px;}
.rp-price small{font-size:11px; color:#7a7788; font-weight:400; margin-right:3px;}
.rp-price .call{font-size:12px; color:#ff3b4e;}
html[data-theme="dark"] .rp-card{background:#15141c; border-color:rgba(255,255,255,.08); box-shadow:0 12px 28px rgba(0,0,0,.45);}
html[data-theme="dark"] .rp-img.has{background:#1f1e29;}
</style>
<section class="rp-wrap">
  <h2><?php echo esc_html($title); ?></h2>
  <div class="rp-track">
  <?php foreach ($ids as $pid):
      $img = get_the_post_thumbnail_url($pid, 'medium');
      if (!$img && function_exists('ap_gallery_urls')) { $g = ap_gallery_urls($pid); $img = $g[0] ?? ''; }
      $price = (int) get_post_meta($pid, '_ap_price', true);
      $vp = function_exists('ap_variant_prices') ? array_values(ap_variant_prices($pid)) : [];
      $all = array_filter(array_merge($vp, $price > 0 ? [$price] : []));
      $min = $all ? min($all) : 0;
      $varies = $all && max($all) !== $min;
  ?>
    <a class="rp-card" href="<?php echo esc_url(get_permalink($pid)); ?>">
      <div class="rp-img<?php echo $img ? ' has' : ''; ?>"><?php if ($img): ?><img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr(get_the_title($pid)); ?>" loading="lazy"><?php else: ?><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="3"/></svg><?php endif; ?></div>
      <h3><?php echo esc_html(get_the_title($pid)); ?></h3>
      <div class="rp-price"><?php if ($min): echo ($varies ? 'از ' : '') . esc_html(number_format($min)) . '<small>تومان</small>'; else: ?><span class="call">برای دریافت قیمت تماس بگیرید</span><?php endif; ?></div>
    </a>
  <?php endforeach; ?>
  </div>
</section>
<?php
    return ob_get_clean();
}

/* =========================================================================
 * پیوند یکتای انگلیسیِ محصولات و مقالات از روی بخشِ انگلیسیِ عنوان
 * عنوان‌ها به شکلِ «فارسی | English» هستن؛ بخشِ بعد از «|» دقیق‌ترین منبع برای
 * پیوندِ انگلیسیه (مثلاً «دوربین جلو آیفون ۵ | iPhone 5 Front Camera» → iphone-5-front-camera).
 * اگه عنوان بخشِ انگلیسی نداشت، از جدولِ ترجمه‌ی فارسی-به-انگلیسی استفاده می‌شه.
 * ========================================================================= */
function ap_slug_from_title($title) {
    $title = (string) $title;
    if (strpos($title, '|') !== false) {
        $en = trim(substr($title, strrpos($title, '|') + 1));
        if (preg_match('/[A-Za-z]/', $en) && !preg_match('/[^\x00-\x7f]/', $en)) {
            $en = str_replace('.', '-', $en);
            $en = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $en));
            $en = trim(preg_replace('/-+/', '-', $en), '-');
            if ($en !== '') return $en;
        }
    }
    return ap_transliterate_title_to_slug($title);
}

/* (کارِ یک‌باره‌ی «بازسازیِ پیوندِ همه‌ی محصولات» سایتِ اپل اینجا حذف شد؛ پیوندِ محصولاتِ استوک از ساخت انگلیسیه.) */
add_action('admin_notices', function () {
    $r = get_transient('ap_slug_fix_report');
    if (!$r || !current_user_can('manage_options')) return;
    delete_transient('ap_slug_fix_report');
    echo '<div class="notice notice-success is-dismissible"><p><strong>پیوند یکتای محصولات:</strong> ' . (int) $r['changed'] . ' محصول اصلاح شد، ' . (int) $r['same'] . ' محصول از قبل درست بود. لینک‌های قدیمی با ۳۰۱ به لینکِ جدید می‌رن.</p>';
    if ($r['examples']) { echo '<ul style="direction:ltr;text-align:left;list-style:disc;padding-left:20px">'; foreach ($r['examples'] as $e) echo '<li><code>' . esc_html($e) . '</code></li>'; echo '</ul>'; }
    echo '</div>';
});

/* ریدایرکتِ ۳۰۱ لینک‌های قدیمیِ محصول (پشتیبانِ ریدایرکتِ خودِ وردپرس) */
add_action('template_redirect', function () {
    if (!is_404()) return;
    $path = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if (!preg_match('#/product/([^/]+)/?$#u', $path, $m)) return;
    global $wpdb;
    $old = $m[1];
    $id = $wpdb->get_var($wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->postmeta} m JOIN {$wpdb->posts} p ON p.ID = m.post_id WHERE m.meta_key = '_wp_old_slug' AND (m.meta_value = %s OR m.meta_value = %s) AND p.post_type = 'ap_product' AND p.post_status = 'publish' LIMIT 1",
        $old, strtolower(rawurlencode($old))
    ));
    if ($id) { wp_redirect(get_permalink((int) $id), 301); exit; }
}, 2);

/* =========================================================================
 * سئو: اصلاحِ گزارش‌های Search Console
 * ========================================================================= */
/* ۱) http → https (گوگل http://nexv.ir/ رو به‌عنوان صفحه‌ی تکراری دیده بود) */
add_action('template_redirect', function () {
    if (is_ssl() || strpos(home_url('/'), 'https://') !== 0 || (defined('WP_CLI') && WP_CLI)) return;
    /* پشتِ CDN (آروان، کلودفلر و ...) اتصالِ کاربر https هست حتی اگه سرور http ببینه؛ حلقه نسازه */
    $xf = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') . (string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '') . (string) ($_SERVER['HTTP_CF_VISITOR'] ?? ''));
    if (strpos($xf, 'https') !== false || strpos($xf, 'on') !== false) return;
    wp_redirect('https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], 301);
    exit;
}, -10);

/* ۲) جستجوی پیش‌فرضِ وردپرس (?s=...) در این قالب صفحه‌ی جدایی نداره؛ آدرسِ
   جستجوی اسکیمای Rank Math به جستجوی خودِ فروشگاه اشاره کنه و صفحه‌های جستجو ایندکس نشن. */
add_filter('rank_math/json_ld/search_url', function () { return home_url('/?ap_search={search_term_string}'); });
add_action('template_redirect', function () {
    if (isset($_GET['s']) && !is_admin()) {
        $q = trim(wp_unslash((string) $_GET['s']));
        wp_redirect(($q === '' || strpos($q, '{') !== false) ? home_url('/') : add_query_arg('ap_search', rawurlencode($q), home_url('/')), 301);
        exit;
    }
}, 1);
add_filter('rank_math/frontend/robots', function ($r) {
    if (isset($_GET['ap_search']) || isset($_GET['ap_tag'])) { $r['index'] = 'noindex'; $r['follow'] = 'follow'; }
    return $r;
}, 99);

/* ۳) مقاله‌هایی که قبلاً آدرسِ فارسی داشتن: اگه مقاله‌ای با همون عنوان و آدرسِ انگلیسی هست، ۳۰۱ به اون */
add_action('template_redirect', function () {
    $path = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH));
    if (!preg_match('#/article/([^/]+)/?$#u', $path, $m) || !preg_match('/[^\x00-\x7f]/', $m[1])) return;
    $needle = ap_norm_fa(str_replace('-', ' ', $m[1]));
    foreach (get_posts(['post_type' => 'ap_article', 'post_status' => 'publish', 'numberposts' => -1]) as $a) {
        if (ap_slug_is_english(urldecode($a->post_name)) && ap_norm_fa($a->post_title) === $needle) {
            wp_redirect(get_permalink($a), 301); exit;
        }
    }
    if (is_404()) { wp_redirect(get_post_type_archive_link('ap_article') ?: home_url('/'), 301); exit; }
}, 3);

/* ۴) گزارشِ محصولاتِ تکراری (دو محصول با عنوانِ یکسان → آدرس‌های ...-2) در پیشخوان */
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, ['dashboard', 'edit-ap_product', 'toplevel_page_ap-dashboard'], true)) return;
    global $wpdb;
    $rows = $wpdb->get_results("SELECT post_title, GROUP_CONCAT(ID ORDER BY ID) ids, COUNT(*) c FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN ('publish','draft','private','pending') GROUP BY post_title HAVING c > 1 LIMIT 50");
    if (!$rows) return;
    echo '<div class="notice notice-warning"><p><strong>محصولاتِ تکراری (' . count($rows) . ' عنوان):</strong> این محصولات دو بار در سایت هستن و آدرسِ یکی‌شون با «-2» تموم می‌شه. نسخه‌ی اضافه رو (معمولاً شماره‌ی بزرگ‌تر) حذف کنید.</p><ul style="list-style:disc;padding-right:20px">';
    foreach ($rows as $r) {
        $links = array_map(function ($id) { return '<a href="' . esc_url(get_edit_post_link((int) $id)) . '">#' . (int) $id . '</a>'; }, explode(',', $r->ids));
        echo '<li>' . esc_html($r->post_title) . ' — ' . implode('، ', $links) . '</li>';
    }
    echo '</ul></div>';
});

/* =========================================================================
 * سایت‌مپِ برگه‌ها: سبد/تسویه/حساب/فروشگاه noindex و بیرون از سایت‌مپ؛ «برگه نمونه» حذف
 * (Rank Math هر برگه‌ای که noindex باشه رو خودش از سایت‌مپ برمی‌داره)
 * ========================================================================= */
add_action('admin_init', function () {
    if (get_option('ap_pages_cleanup_v1') || !current_user_can('manage_options')) return;
    foreach (ap_utility_page_slugs() as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'page');
        if ($p) update_post_meta($p->ID, 'rank_math_robots', ['noindex', 'follow']);
    }
    global $wpdb;
    $ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND (post_name IN ('sample-page', '" . esc_sql(rawurlencode('برگه-نمونه')) . "', '" . esc_sql(strtolower(rawurlencode('برگه-نمونه'))) . "', 'برگه-نمونه') OR post_title IN ('برگه نمونه', 'Sample Page'))");
    foreach ($ids as $id) wp_delete_post((int) $id, true);
    update_option('ap_pages_cleanup_v1', 1, false);
});
add_filter('rank_math/sitemap/exclude_post', function ($exclude, $post) {
    if ($post && isset($post->post_type) && $post->post_type === 'page' && in_array(urldecode($post->post_name), ['برگه-نمونه', 'sample-page'], true)) return true;
    return $exclude;
}, 20, 2);

/* سایت‌مپِ برگه‌ها (نسخه‌ی ۲): شناسه‌ی برگه‌های کمکی به تنظیمِ «Exclude Posts» خودِ Rank Math
   اضافه می‌شه (همون فیلدی که در تنظیمات نقشه سایت ← عمومی هست)، برگه‌ی نمونه حذف می‌شه
   و کشِ سایت‌مپِ Rank Math پاک می‌شه تا تغییر همون لحظه دیده بشه. */
function ap_flush_rank_math_sitemap() {
    if (class_exists('\RankMath\Sitemap\Cache')) { try { \RankMath\Sitemap\Cache::invalidate_storage(); } catch (\Throwable $e) {} }
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_rank\_math\_sitemap%' OR option_name LIKE '\_transient\_timeout\_rank\_math\_sitemap%' OR option_name LIKE '%rank_math%sitemap%cache%'");
}
add_action('admin_init', function () {
    if (get_option('ap_pages_cleanup_v2') || !current_user_can('manage_options')) return;
    global $wpdb;
    $samples = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND (post_title IN ('برگه نمونه', 'Sample Page') OR post_name = 'sample-page' OR post_name LIKE '%25d8%25a8%25d8%25b1%25da%25af%25d9%2587-%' OR post_name LIKE '%D8%A8%D8%B1%DA%AF%D9%87-%')");
    foreach ($samples as $id) wp_delete_post((int) $id, true);
    $ids = [];
    foreach (ap_utility_page_slugs() as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'page');
        if ($p) { $ids[] = (int) $p->ID; update_post_meta($p->ID, 'rank_math_robots', ['noindex', 'follow']); }
    }
    $opt = get_option('rank-math-options-sitemap', []);
    if (is_array($opt) && $ids) {
        $cur = array_filter(array_map('intval', preg_split('/\s*,\s*/', (string) ($opt['exclude_posts'] ?? ''))));
        $opt['exclude_posts'] = implode(',', array_values(array_unique(array_merge($cur, $ids))));
        update_option('rank-math-options-sitemap', $opt);
    }
    ap_flush_rank_math_sitemap();
    update_option('ap_pages_cleanup_v2', 1, false);
}, 40);

/* بازگردانی (به درخواستِ مدیر سایت): سبد خرید، فروشگاه، تسویه حساب و حساب کاربری
   دوباره قابلِ ایندکس و داخلِ سایت‌مپ هستن؛ فقط «برگه نمونه» حذف شده می‌مونه. */
add_action('admin_init', function () {
    if (get_option('ap_pages_restore_v1') || !current_user_can('manage_options')) return;
    $ids = [];
    foreach (ap_utility_page_slugs() as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'page');
        if ($p) {
            $ids[] = (int) $p->ID;
            delete_post_meta($p->ID, 'rank_math_robots');
            if ($p->post_status !== 'publish') wp_update_post(['ID' => $p->ID, 'post_status' => 'publish']);
        }
    }
    $opt = get_option('rank-math-options-sitemap', []);
    if (is_array($opt) && isset($opt['exclude_posts'])) {
        $cur = array_filter(array_map('intval', preg_split('/\\s*,\\s*/', (string) $opt['exclude_posts'])));
        $opt['exclude_posts'] = implode(',', array_values(array_diff($cur, $ids)));
        update_option('rank-math-options-sitemap', $opt);
    }
    ap_flush_rank_math_sitemap();
    update_option('ap_pages_restore_v1', 1, false);
}, 50);

/* =========================================================================
 * «تسویه حساب» همون «سبد خرید»ه و «فروشگاه» همون صفحه‌ی اصلی (به درخواستِ مدیر سایت):
 * برگه‌هاشون حذف و آدرس‌هاشون ۳۰۱ می‌شن: /checkout/ → /cart/ ، /shop/ → صفحه‌ی اصلی
 * ========================================================================= */
add_action('template_redirect', function () {
    $path = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $base = trim((string) parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($base !== '' && strpos($path, $base) === 0) $path = trim(substr($path, strlen($base)), '/');
    if ($path === 'checkout') { wp_redirect(home_url('/cart/'), 301); exit; }
    if ($path === 'shop')     { wp_redirect(home_url('/'), 301); exit; }
}, 0);
add_action('admin_init', function () {
    if (get_option('ap_pages_merge_v1') || !current_user_can('manage_options')) return;
    foreach (['checkout', 'shop'] as $slug) {
        $p = get_page_by_path($slug, OBJECT, 'page');
        if ($p) wp_delete_post($p->ID, true);
    }
    ap_flush_rank_math_sitemap();
    update_option('ap_pages_merge_v1', 1, false);
}, 60);

/* عکسِ کارتِ محصول در اندازه‌ی medium_large (برای لیست‌ها؛ صفحه‌ی محصول همون عکسِ بزرگ رو داره) */
function ap_card_thumb_url($post_id) {
    $raw = trim((string) get_post_meta($post_id, '_ap_gallery', true));
    if ($raw !== '') {
        $first = rawurldecode(trim(explode(',', $raw)[0]));
        if (ctype_digit($first)) return wp_get_attachment_image_url((int) $first, 'medium_large') ?: '';
        return '';
    }
    $tid = get_post_thumbnail_id($post_id);
    return $tid ? (wp_get_attachment_image_url($tid, 'medium_large') ?: '') : '';
}

/* =========================================================================
 * سفارش‌ها در پیشخوانِ وردپرس (فروشگاه ← سفارش‌ها) + اطلاع‌رسانیِ سفارشِ جدید
 * این صفحه مستقیم از همون فایلِ سفارش‌های سایت می‌خونه؛ یعنی حتی اگه پنلِ داخلِ
 * سایت به هر دلیلی باز نشه، مدیر همیشه سفارش‌ها رو اینجا می‌بینه.
 * ========================================================================= */
function ap_orders_db_path() {
    $u = wp_upload_dir();
    $cands = [];
    if (!empty($u['basedir'])) $cands[] = $u['basedir'] . '/ap-data/db.json';
    $cands[] = WP_CONTENT_DIR . '/uploads/ap-data/db.json';
    $cands[] = get_template_directory() . '/data/db.json';
    foreach ($cands as $c) { if (file_exists($c)) return $c; }
    return $cands[0];
}
function ap_orders_read() {
    $f = ap_orders_db_path();
    $d = file_exists($f) ? json_decode((string) file_get_contents($f), true) : null;
    return is_array($d) ? $d : null;
}
function ap_order_status_labels() {
    return ['pending' => 'در انتظار بررسی', 'confirmed' => 'تأیید شد', 'rejected' => 'رد شد', 'packing' => 'در حال بسته‌بندی', 'courier' => 'تحویل به پیک/پست', 'shipped' => 'ارسال شد'];
}

/* اطلاع‌رسانیِ سفارشِ جدید: ایمیل به مدیر + شمارنده‌ی «سفارشِ جدید» روی منوی فروشگاه */
function ap_notify_new_order($order, $detached = true) {
    update_option('ap_unseen_orders', (int) get_option('ap_unseen_orders', 0) + 1, false);
    /* اعلان روی گوشیِ مدیر (اگه دستگاهی ثبت شده باشه) */
    if (function_exists('ap_push_order_notify')) { try { ap_push_order_notify($order); } catch (\Throwable $e) {} }
    $to = get_option('admin_email');
    /* ایمیل فقط وقتی فرستاده می‌شه که جوابِ مشتری از قبل کامل فرستاده شده باشه (معطلش نکنه) */
    if (!$detached || !$to || strpos($to, '@nexv.local') !== false) return;
    $lines = [];
    foreach ((array) ($order['items'] ?? []) as $it) $lines[] = '- ' . ($it['title'] ?? '') . ' × ' . (int) ($it['qty'] ?? 1) . ' — ' . number_format((int) ($it['price'] ?? 0)) . ' تومان';
    $body = "سفارش جدید #" . $order['id'] . "\n\nمشتری: " . ($order['customerName'] ?? '') . "\nتلفن: " . ($order['phone'] ?? '')
          . "\n\n" . implode("\n", $lines) . "\n\nجمع کل: " . number_format((int) ($order['grandTotal'] ?? 0)) . " تومان\n\nرسید: " . ($order['receiptUrl'] ?? '')
          . "\n\nمشاهده در پیشخوان: " . admin_url('admin.php?page=ap-orders');
    @wp_mail($to, get_bloginfo('name') . ' — سفارش جدید #' . $order['id'], $body);
}

add_action('admin_post_ap_order_status', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_order_status');
    $id = (int) ($_POST['order_id'] ?? 0);
    $st = sanitize_key($_POST['status'] ?? '');
    if (!isset(ap_order_status_labels()[$st])) wp_die('وضعیت نامعتبر.');
    $f = ap_orders_db_path();
    $fp = fopen($f, 'c+');
    if ($fp && flock($fp, LOCK_EX)) {
        $d = json_decode(stream_get_contents($fp), true);
        if (is_array($d)) {
            $changed = null;
            foreach ($d['orders'] as &$o) {
                if ((int) $o['id'] === $id) {
                    $o['status'] = $st;
                    $o['statusHistory'] = array_merge((array) ($o['statusHistory'] ?? []), [['status' => $st, 'at' => date('c')]]);
                    $changed = $o; break;
                }
            }
            unset($o);
            if ($changed) { ap_archive_order_wp($changed, 'status:' . $st); ap_mirror_order_to_wp($changed); }
            $json = json_encode($d, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            ftruncate($fp, 0); rewind($fp); fwrite($fp, $json); fflush($fp);
            @file_put_contents(dirname($f) . '/backups/latest.json', $json);
        }
        flock($fp, LOCK_UN);
    }
    if ($fp) fclose($fp);
    wp_redirect(admin_url('admin.php?page=ap-orders&updated=' . $id . '#order-' . $id));
    exit;
});


/* =========================================================================
 * سفارش‌ها — منوی جداگانه در پیشخوان، سه نسخه‌ی مستقل، بکاپ و خروجی
 * ۱) فایلِ اصلیِ سایت (db.json)  ۲) آرشیوِ دائمیِ هر سفارش (orders-archive/)
 * ۳) دیتابیسِ وردپرس (نوع‌پستِ مخفیِ ap_order، با همون تاریخِ ثبت)
 * لیستِ این صفحه از هر سه منبع ساخته می‌شه؛ اگه یکی آسیب ببینه، بقیه سفارش رو نگه داشتن.
 * ========================================================================= */
add_action('init', function () {
    register_post_type('ap_order', ['public' => false, 'show_ui' => false, 'exclude_from_search' => true, 'publicly_queryable' => false, 'rewrite' => false, 'query_var' => false, 'label' => 'سفارش‌ها']);
});
function ap_orders_archive_dir() { return dirname(ap_orders_db_path()) . '/orders-archive'; }
function ap_order_key($o) { return (int) ($o['id'] ?? 0) . '|' . (string) ($o['created_at'] ?? ''); }
function ap_archive_order_wp($order, $event = 'save') {
    $dir = ap_orders_archive_dir();
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $file = $dir . '/order-' . (int) $order['id'] . '.json';
    if (file_exists($file)) {
        $prev = json_decode((string) @file_get_contents($file), true);
        if (is_array($prev) && !empty($prev['created_at']) && !empty($order['created_at']) && $prev['created_at'] !== $order['created_at']) {
            $file = $dir . '/order-' . (int) $order['id'] . '-' . preg_replace('/\D/', '', $order['created_at']) . '.json';
        }
    }
    $json = wp_json_encode($order, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (@file_put_contents($file . '.tmp', $json, LOCK_EX) !== false) @rename($file . '.tmp', $file);
    @file_put_contents($dir . '/orders-log.jsonl', wp_json_encode(['event' => $event, 'at' => date('c'), 'order' => $order], JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}
function ap_mirror_order_to_wp($order) {
    if (empty($order['id'])) return;
    $key = ap_order_key($order);
    $found = get_posts(['post_type' => 'ap_order', 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'meta_key' => '_ap_order_key', 'meta_value' => $key]);
    $ts = strtotime((string) ($order['created_at'] ?? '')) ?: time();
    $arr = [
        'post_type' => 'ap_order', 'post_status' => 'private',
        'post_title' => 'سفارش #' . (int) $order['id'] . ' — ' . ($order['customerName'] ?? ''),
        'post_content' => wp_slash(wp_json_encode($order, JSON_UNESCAPED_UNICODE)),
        'post_date' => wp_date('Y-m-d H:i:s', $ts), 'post_date_gmt' => gmdate('Y-m-d H:i:s', $ts),
    ];
    if ($found) { $arr['ID'] = $found[0]; wp_update_post($arr); $pid = $found[0]; }
    else { $pid = wp_insert_post($arr); if ($pid && !is_wp_error($pid)) update_post_meta($pid, '_ap_order_key', $key); }
    if (!empty($pid) && !is_wp_error($pid)) { update_post_meta($pid, '_ap_order_id', (int) $order['id']); update_post_meta($pid, '_ap_order_status', (string) ($order['status'] ?? '')); }
}
/* همه‌ی سفارش‌ها از هر سه منبع، بدون تکرار؛ جدیدترین اول */
function ap_all_orders() {
    $by = [];
    $add = function ($o, $src) use (&$by) {
        if (!is_array($o) || empty($o['id'])) return;
        $k = ap_order_key($o);
        if (!isset($by[$k])) { $by[$k] = $o; $by[$k]['_src'] = [$src]; return; }
        $by[$k]['_src'][] = $src;
        /* اگه یکی از منابع وضعیتِ جدیدتری داره (تاریخچه‌ی بلندتر)، همون نشون داده می‌شه */
        if (count((array) ($o['statusHistory'] ?? [])) > count((array) ($by[$k]['statusHistory'] ?? []))) { $src0 = $by[$k]['_src']; $by[$k] = $o; $by[$k]['_src'] = $src0; }
    };
    $d = ap_orders_read();
    foreach ((array) ($d['orders'] ?? []) as $o) $add($o, 'db');
    foreach ((array) glob(ap_orders_archive_dir() . '/order-*.json') as $f) $add(json_decode((string) @file_get_contents($f), true), 'archive');
    foreach (get_posts(['post_type' => 'ap_order', 'post_status' => 'any', 'numberposts' => -1]) as $p) $add(json_decode($p->post_content, true), 'wp');
    $all = array_values($by);
    usort($all, function ($a, $b) { return strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')) ?: ((int) $b['id'] <=> (int) $a['id']); });
    return $all;
}
/* یک‌بار: همه‌ی سفارش‌های فعلی آرشیو و در دیتابیسِ وردپرس هم ثبت می‌شن */
add_action('admin_init', function () {
    if (get_option('ap_orders_mirror_v1') || !current_user_can('manage_options')) return;
    $d = ap_orders_read();
    foreach ((array) ($d['orders'] ?? []) as $o) {
        if (!file_exists(ap_orders_archive_dir() . '/order-' . (int) $o['id'] . '.json')) ap_archive_order_wp($o, 'backfill');
        ap_mirror_order_to_wp($o);
    }
    update_option('ap_orders_mirror_v1', 1, false);
});

/* منوی مستقلِ «سفارش‌ها» در پیشخوان */
add_action('admin_menu', function () {
    $n = (int) get_option('ap_unseen_orders', 0);
    $badge = $n > 0 ? ' <span class="awaiting-mod">' . $n . '</span>' : '';
    add_menu_page('سفارش‌ها', 'سفارش‌ها' . $badge, 'manage_options', 'ap-orders', 'ap_orders_page_html', 'dashicons-cart', 2);
});

function ap_jalali_dt($iso) {
    $ts = strtotime((string) $iso);
    if (!$ts) return '';
    return (function_exists('ap_jalali_pretty') ? ap_jalali_pretty($ts) : date_i18n('Y/m/d', $ts)) . ' — ساعت ' . wp_date('H:i', $ts);
}
function ap_backup_dir() { $d = dirname(ap_orders_db_path()) . '/orders-archive/backups'; if (!is_dir($d)) @mkdir($d, 0775, true); return $d; }

/* ---------- بکاپ و خروجی‌ها ---------- */
add_action('admin_post_ap_orders_backup', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_orders_backup');
    $f = ap_backup_dir() . '/orders-' . wp_date('Y-m-d_H-i-s') . '.json';
    $ok = @file_put_contents($f, wp_json_encode(array_map(function ($o) { unset($o['_src']); return $o; }, ap_all_orders()), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
    wp_redirect(admin_url('admin.php?page=ap-orders&backup=' . ($ok ? 'ok' : 'fail')));
    exit;
});
add_action('admin_post_ap_orders_download', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_orders_download');
    $fmt = sanitize_key($_GET['fmt'] ?? 'json');
    $orders = array_map(function ($o) { unset($o['_src']); return $o; }, ap_all_orders());
    $stamp = wp_date('Y-m-d_H-i');
    nocache_headers();
    if ($fmt === 'file') {   // دانلودِ یکی از بکاپ‌های ذخیره‌شده
        $name = basename((string) ($_GET['name'] ?? ''));
        $path = ap_backup_dir() . '/' . $name;
        if (!preg_match('/^orders-[\d_-]+\.json$/', $name) || !file_exists($path)) wp_die('فایل پیدا نشد.');
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        readfile($path); exit;
    }
    if ($fmt === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="ap-orders-' . $stamp . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['شماره', 'تاریخ ثبت', 'مشتری', 'تلفن', 'استان/شهر', 'آدرس', 'کدپستی', 'اقلام', 'جمع اقلام', 'هزینه ارسال', 'روش ارسال', 'جمع کل', 'وضعیت', 'رسید']);
        $labels = ap_order_status_labels();
        foreach ($orders as $o) {
            $a = (array) ($o['address'] ?? []);
            $items = implode(' | ', array_map(function ($it) { return ($it['title'] ?? '') . ' × ' . (int) ($it['qty'] ?? 1) . ' (' . (int) ($it['price'] ?? 0) . ')'; }, (array) ($o['items'] ?? [])));
            fputcsv($out, [(int) $o['id'], ap_jalali_dt($o['created_at'] ?? ''), $o['customerName'] ?? '', $o['phone'] ?? '',
                trim(($a['province'] ?? '') . ' / ' . ($a['city'] ?? ''), ' /'), trim(($a['district'] ?? '') . ' ' . ($a['fullAddress'] ?? '') . ' پلاک ' . ($a['plaque'] ?? '') . ' واحد ' . ($a['unit'] ?? '')),
                $a['postalCode'] ?? '', $items, (int) ($o['itemsTotal'] ?? 0), (int) ($o['shippingFee'] ?? 0), $o['shippingMethod'] ?? '', (int) ($o['grandTotal'] ?? 0),
                $labels[$o['status'] ?? ''] ?? ($o['status'] ?? ''), $o['receiptUrl'] ?? '']);
        }
        fclose($out); exit;
    }
    if ($fmt === 'zip' && class_exists('ZipArchive')) {
        $tmp = wp_tempnam('ap-orders.zip');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('orders.json', wp_json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $rdir = dirname(ap_orders_db_path()) . '/receipts';
            foreach ($orders as $o) {
                $r = basename((string) ($o['receiptUrl'] ?? ''));
                if ($r && file_exists($rdir . '/' . $r)) $zip->addFile($rdir . '/' . $r, 'receipts/' . $r);
            }
            $zip->close();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="ap-orders-full-' . $stamp . '.zip"');
            header('Content-Length: ' . filesize($tmp));
            readfile($tmp); @unlink($tmp); exit;
        }
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="ap-orders-' . $stamp . '.json"');
    echo wp_json_encode($orders, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
});

function ap_orders_page_html() {
    update_option('ap_unseen_orders', 0, false);
    $orders = ap_all_orders();
    $labels = ap_order_status_labels();
    $api = get_template_directory_uri() . '/api.php';
    $filter = sanitize_key($_GET['st'] ?? '');
    $q = trim((string) wp_unslash($_GET['q'] ?? ''));
    $counts = array_count_values(array_map(function ($o) { return (string) ($o['status'] ?? 'pending'); }, $orders));
    $shown = array_filter($orders, function ($o) use ($filter, $q) {
        if ($filter && ($o['status'] ?? '') !== $filter) return false;
        if ($q !== '' && strpos((string) ($o['phone'] ?? '') . ' ' . ($o['customerName'] ?? '') . ' #' . $o['id'], $q) === false) return false;
        return true;
    });
    $dl = function ($fmt) { return wp_nonce_url(admin_url('admin-post.php?action=ap_orders_download&fmt=' . $fmt), 'ap_orders_download'); };
    $backups = array_reverse((array) glob(ap_backup_dir() . '/orders-*.json'));
    $total = 0; foreach ($orders as $o) if (($o['status'] ?? '') !== 'rejected') $total += (int) ($o['grandTotal'] ?? 0);
    ?>
    <div class="wrap nx-wrap">
        <h1>سفارش‌ها</h1>
        <?php if (isset($_GET['updated'])): ?><div class="notice notice-success is-dismissible"><p>وضعیت سفارش #<?php echo (int) $_GET['updated']; ?> ذخیره شد.</p></div><?php endif; ?>
        <?php if (($_GET['backup'] ?? '') === 'ok'): ?><div class="notice notice-success is-dismissible"><p>بکاپِ همه‌ی سفارش‌ها ساخته شد و پایینِ همین صفحه قابل دانلوده.</p></div><?php endif; ?>
        <?php if (($_GET['backup'] ?? '') === 'fail'): ?><div class="notice notice-error"><p>ساختِ بکاپ ممکن نشد (دسترسیِ نوشتن روی هاست).</p></div><?php endif; ?>
        <style>
            .nx-wrap .nx-top{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0 16px}
            .nx-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:10px 0 18px}
            .nx-card{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:12px 14px}
            .nx-card b{display:block;font-size:20px;margin-top:4px}
            .nx-safe{background:#edfaef;border:1px solid #b8e6c1;border-radius:10px;padding:10px 14px;margin:6px 0 14px;color:#1d5b2b;font-size:13px;line-height:1.9}
            .nx-orders{display:grid;gap:14px}
            .nx-order{background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 16px;display:grid;grid-template-columns:1fr 150px;gap:16px}
            .nx-order h2{margin:0 0 6px;font-size:16px}
            .nx-order .meta{color:#50575e;font-size:13px;line-height:1.9}
            .nx-order table{border-collapse:collapse;margin:8px 0;font-size:13px}
            .nx-order td{padding:3px 8px;border-bottom:1px solid #f0f0f1}
            .nx-order .rc img{width:150px;height:150px;object-fit:cover;border-radius:8px;border:1px solid #ddd}
            .nx-hist{font-size:12px;color:#646970;margin:4px 0 8px}
            .nx-st{display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;background:#f0f0f1}
            .nx-st.pending{background:#fff4d6;color:#8a5a00}.nx-st.confirmed,.nx-st.shipped{background:#d9f7e6;color:#0a6b3a}.nx-st.rejected{background:#fde2e2;color:#a00}
            .nx-src{font-size:11px;color:#8c8f94}
            @media(max-width:782px){.nx-order{grid-template-columns:1fr}}
        </style>

        <div class="nx-cards">
            <div class="nx-card">همه‌ی سفارش‌ها<b><?php echo count($orders); ?></b></div>
            <div class="nx-card">در انتظار بررسی<b><?php echo (int) ($counts['pending'] ?? 0); ?></b></div>
            <div class="nx-card">ارسال‌شده<b><?php echo (int) ($counts['shipped'] ?? 0); ?></b></div>
            <div class="nx-card">جمع فروش (بدون ردشده‌ها)<b style="font-size:16px"><?php echo esc_html(number_format($total)); ?> تومان</b></div>
        </div>

        <div class="nx-safe">🔒 هر سفارش در <b>سه جای مستقل</b> ذخیره می‌شه (فایل اصلی سایت، آرشیو دائمی، دیتابیس وردپرس) و <b>امکانِ حذف سفارش غیرفعاله</b>. تاریخِ ثبت هیچ‌وقت عوض نمی‌شه و هر تغییرِ وضعیت با تاریخش ثبت می‌شه. هر روز هم یک نسخه‌ی کامل به‌صورت خودکار نگه داشته می‌شه.</div>

        <div class="nx-top">
            <a class="button button-primary" href="<?php echo esc_url($dl('zip')); ?>">⬇ دانلود بکاپ کامل (سفارش‌ها + عکس رسیدها، ZIP)</a>
            <a class="button" href="<?php echo esc_url($dl('csv')); ?>">⬇ دانلود اکسل (CSV)</a>
            <a class="button" href="<?php echo esc_url($dl('json')); ?>">⬇ دانلود JSON</a>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
                <input type="hidden" name="action" value="ap_orders_backup"><?php wp_nonce_field('ap_orders_backup'); ?>
                <button class="button">💾 ساخت بکاپ روی هاست</button>
            </form>
        </div>

        <form method="get" class="nx-top">
            <input type="hidden" name="page" value="ap-orders">
            <select name="st"><option value="">همه‌ی وضعیت‌ها</option><?php foreach ($labels as $k => $l): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($filter, $k); ?>><?php echo esc_html($l . ' (' . (int) ($counts[$k] ?? 0) . ')'); ?></option><?php endforeach; ?></select>
            <input type="search" name="q" value="<?php echo esc_attr($q); ?>" placeholder="جستجو: شماره سفارش، نام یا تلفن">
            <button class="button">فیلتر</button>
        </form>

        <div class="nx-orders">
        <?php foreach ($shown as $o):
            $a = (array) ($o['address'] ?? []);
            $st = (string) ($o['status'] ?? 'pending');
        ?>
            <div class="nx-order" id="order-<?php echo (int) $o['id']; ?>">
                <div>
                    <h2>سفارش #<?php echo (int) $o['id']; ?> <span class="nx-st <?php echo esc_attr($st); ?>"><?php echo esc_html($labels[$st] ?? $st); ?></span></h2>
                    <div class="meta">
                        🗓 ثبت: <b><?php echo esc_html(ap_jalali_dt($o['created_at'] ?? '')); ?></b><br>
                        <b><?php echo esc_html($o['customerName'] ?? ''); ?></b> — <a href="tel:<?php echo esc_attr($o['phone'] ?? ''); ?>" style="direction:ltr;display:inline-block"><?php echo esc_html($o['phone'] ?? ''); ?></a><br>
                        <?php echo esc_html(implode('، ', array_filter([$a['province'] ?? '', $a['city'] ?? '', $a['district'] ?? '', !empty($a['plaque']) ? 'پلاک ' . $a['plaque'] : '', !empty($a['unit']) ? 'واحد ' . $a['unit'] : '']))); ?>
                        <?php if (!empty($a['postalCode'])) echo ' — کدپستی ' . esc_html($a['postalCode']); ?><br><?php echo esc_html($a['fullAddress'] ?? ''); ?>
                    </div>
                    <table>
                        <?php foreach ((array) ($o['items'] ?? []) as $it): ?>
                            <tr><td><?php echo esc_html($it['title'] ?? ''); ?></td><td>× <?php echo (int) ($it['qty'] ?? 1); ?></td><td><?php echo esc_html(number_format((int) ($it['price'] ?? 0))); ?> تومان</td></tr>
                        <?php endforeach; ?>
                        <tr><td>ارسال (<?php echo esc_html($o['shippingMethod'] ?? ''); ?>)</td><td></td><td><?php echo esc_html(number_format((int) ($o['shippingFee'] ?? 0))); ?> تومان</td></tr>
                        <tr><td><b>جمع کل</b></td><td></td><td><b><?php echo esc_html(number_format((int) ($o['grandTotal'] ?? 0))); ?> تومان</b></td></tr>
                    </table>
                    <?php if (!empty($o['statusHistory'])): ?>
                        <div class="nx-hist">تاریخچه: <?php echo esc_html(implode('  ←  ', array_map(function ($h) use ($labels) { return ($labels[$h['status'] ?? ''] ?? ($h['status'] ?? '')) . ' (' . ap_jalali_dt($h['at'] ?? '') . ')'; }, (array) $o['statusHistory']))); ?></div>
                    <?php endif; ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                        <input type="hidden" name="action" value="ap_order_status"><input type="hidden" name="order_id" value="<?php echo (int) $o['id']; ?>">
                        <?php wp_nonce_field('ap_order_status'); ?>
                        <select name="status"><?php foreach ($labels as $k => $l): ?><option value="<?php echo esc_attr($k); ?>" <?php selected($st, $k); ?>><?php echo esc_html($l); ?></option><?php endforeach; ?></select>
                        <button class="button button-primary">ذخیره وضعیت</button>
                        <a class="button" target="_blank" href="<?php echo esc_url($api . '?action=invoice&order_id=' . (int) $o['id']); ?>">فاکتور</a>
                        <span class="nx-src">ذخیره‌شده در: <?php echo esc_html(implode('، ', array_map(function ($s) { return ['db' => 'فایل اصلی', 'archive' => 'آرشیو', 'wp' => 'دیتابیس وردپرس'][$s] ?? $s; }, array_unique((array) ($o['_src'] ?? []))))); ?></span>
                    </form>
                </div>
                <div class="rc"><?php if (!empty($o['receiptUrl'])): ?><a href="<?php echo esc_url($o['receiptUrl']); ?>" target="_blank"><img src="<?php echo esc_url($o['receiptUrl']); ?>" alt="رسید" loading="lazy"></a><?php endif; ?></div>
            </div>
        <?php endforeach; ?>
        <?php if (!$shown): ?><p><?php echo $orders ? 'سفارشی با این فیلتر پیدا نشد.' : 'هنوز سفارشی ثبت نشده.'; ?></p><?php endif; ?>
        </div>

        <h2 style="margin-top:28px">بکاپ‌های ذخیره‌شده روی هاست</h2>
        <?php if ($backups): ?>
            <ul><?php foreach (array_slice($backups, 0, 50) as $b): $n = basename($b); ?>
                <li><a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ap_orders_download&fmt=file&name=' . rawurlencode($n)), 'ap_orders_download')); ?>"><?php echo esc_html($n); ?></a> — <?php echo esc_html(size_format(filesize($b))); ?></li>
            <?php endforeach; ?></ul>
        <?php else: ?><p>هنوز بکاپ دستی ساخته نشده. (بکاپ روزانه‌ی خودکار جدا نگه داشته می‌شه.)</p><?php endif; ?>
    </div>
    <?php
}

/* =========================================================================
 * اعلان‌ها (PWA + Web Push): اعلانِ سفارشِ جدید برای مدیر + اعلانِ همگانی برای مشتری‌ها
 * - کلیدهای VAPID یک‌بار روی خودِ سایت ساخته می‌شن (بدون سرویس یا کتابخونه‌ی بیرونی)
 * - مشترک‌ها و ارسال: push-lib.php (مشترکِ همین فایل و api.php)
 * - روی آیفون فقط وقتی کار می‌کنه که سایت «Add to Home Screen» شده و از همون آیکون باز بشه
 * ========================================================================= */
if (file_exists(get_template_directory() . '/push-lib.php')) require_once get_template_directory() . '/push-lib.php';
function ap_b64u($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
function ap_vapid() {
    $v = get_option('ap_vapid');
    if (is_array($v) && !empty($v['private']) && !empty($v['public'])) return $v;
    if (!function_exists('openssl_pkey_new')) return null;
    $key = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    if (!$key) return null;
    openssl_pkey_export($key, $pem);
    $d = openssl_pkey_get_details($key);
    $x = str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT); $y = str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
    $v = ['private' => $pem, 'public' => ap_b64u("\x04" . $x . $y)];
    update_option('ap_vapid', $v, false);
    return $v;
}
function ap_push_subject() { $e = (string) get_option('admin_email'); return 'mailto:' . (is_email($e) && strpos($e, '@nexv.local') === false ? $e : 'admin@' . (parse_url(home_url(), PHP_URL_HOST) ?: 'localhost')); }
function ap_push_data_dir() { return dirname(ap_orders_db_path()); }
/* اعلانِ سفارشِ جدید → فقط دستگاه‌های مدیر */
function ap_push_order_notify($order) {
    if (!function_exists('ap_pl_send')) return null;
    $v = ap_vapid(); if (!$v) return null;
    $items = implode('، ', array_map(function ($it) { return ($it['title'] ?? '') . ' ×' . (int) ($it['qty'] ?? 1); }, array_slice((array) ($order['items'] ?? []), 0, 3)));
    return ap_pl_send(ap_push_data_dir(), $v, 'admins', [
        'title' => '🛒 سفارش جدید #' . (int) ($order['id'] ?? 0),
        'body'  => trim(($order['customerName'] ?? '') . ' — ' . number_format((int) ($order['grandTotal'] ?? 0)) . ' تومان' . ($items !== '' ? "\n" . $items : '')),
        'url'   => home_url('/?ap_admin=orders'),
        'tag'   => 'ap-order-' . (int) ($order['id'] ?? 0),
    ], ap_push_subject());
}
/* دستگاه‌هایی که با نسخه‌ی قبلی (لیستِ داخلِ تنظیماتِ وردپرس) ثبت شده بودن، یک‌بار منتقل می‌شن */
add_action('admin_init', function () {
    $old = get_option('ap_push_subs', null);
    if (!is_array($old) || !function_exists('ap_pl_subscribe')) return;
    foreach ($old as $s) { if (!empty($s['endpoint'])) ap_pl_subscribe(ap_push_data_dir(), ['endpoint' => $s['endpoint']], true, $s['ua'] ?? ''); }
    delete_option('ap_push_subs'); delete_option('ap_push_last'); delete_option('ap_push_test_until');
});
function ap_manifest_data() {
    return [
        'name' => get_bloginfo('name') . ' — فروشگاه قطعات یدکی خودرو', 'short_name' => get_bloginfo('name'), 'lang' => 'fa', 'dir' => 'rtl', 'id' => home_url('/'),
        'start_url' => home_url('/?source=pwa'), 'scope' => home_url('/'), 'display' => 'standalone',
        'background_color' => '#f7f3f3', 'theme_color' => '#ff3b4e',
        'icons' => [
            ['src' => ap_icon_url('icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => ap_icon_url('icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => ap_icon_url('icon-maskable-192.png'), 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'maskable'],
            ['src' => ap_icon_url('icon-maskable-512.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
    ];
}

/* راه‌های جایگزین برای سرویس‌ورکر و manifest (اصلی‌ها از api.php سرو می‌شن):
   /?ap_sw=1 و /?ap_manifest=1 ، و آدرس‌های قبلی /ap-sw.js و /ap-manifest.webmanifest */
add_action('init', function () {
    if (!function_exists('ap_pl_serve_sw')) return;
    $path = trim((string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH), '/');
    $base = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($base !== '' && strpos($path, $base) === 0) $path = trim(substr($path, strlen($base)), '/');
    if ($path === 'ap-manifest.webmanifest' || ($path === '' && isset($_GET['ap_manifest']))) {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo wp_json_encode(ap_manifest_data(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    if ($path === 'ap-sw.js' || ($path === '' && isset($_GET['ap_sw']))) {
        if (!defined('DONOTCACHEPAGE')) define('DONOTCACHEPAGE', true);
        ap_pl_serve_sw();
    }
}, 0);

/* =========================================================================
 * انتقالِ یک‌باره از سیستمِ قبلیِ ادمین‌ها (پیشخوان ← مدیریت ادمین‌ها، نقشِ ap_staff)
 * به سیستمِ جدید (پنلِ سایت ← حسابِ کاربری ← ادمین‌ها). هر ادمینِ قبلی با همون شماره،
 * نام و دسترسی‌های معادل ثبت می‌شه و اولین بار با همون رمزِ قبلیش وارد می‌شه
 * (api.php رمز رو با حسابِ وردپرسش چک و ذخیره می‌کنه).
 * ========================================================================= */
add_action('init', function () {
    if (get_option('ap_staff_to_admins_v1')) return;
    if (!function_exists('ap_read_data') || !function_exists('ap_write_data')) return;
    $data = ap_read_data();
    if (!is_array($data) || !isset($data['users']) || !is_array($data['users'])) { update_option('ap_staff_to_admins_v1', time(), false); return; }
    if (!isset($data['next_user_id'])) $data['next_user_id'] = count($data['users']) + 1;
    $norm = function ($p) {
        $d = preg_replace('/[^0-9]/', '', strtr((string) $p, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']));
        if (substr($d, 0, 2) === '98' && strlen($d) === 12) $d = '0' . substr($d, 2);
        return $d;
    };
    $users = get_role('ap_staff') ? get_users(['role' => 'ap_staff']) : [];
    foreach ($users as $wu) {
        $phone = $norm(get_user_meta($wu->ID, 'ap_phone', true));
        if (!preg_match('/^09\d{9}$/', $phone)) continue;
        $perms = [];
        if (!empty($wu->allcaps['ap_manage_orders'])) { $perms[] = 'orders'; $perms[] = 'order_status'; $perms[] = 'customers'; }
        if (!empty($wu->allcaps['edit_posts']) || !empty($wu->allcaps['manage_categories'])) $perms[] = 'products';
        $found = false;
        foreach ($data['users'] as &$rec) {
            if ((string) ($rec['phone'] ?? '') !== $phone) continue;
            $found = true;
            if (empty($rec['is_owner'])) { $rec['is_admin'] = true; $rec['perms'] = $perms; }
            $rec['wp_user_id'] = $wu->ID;
        }
        unset($rec);
        if (!$found) {
            $data['users'][] = [
                'id' => $data['next_user_id']++, 'firstName' => $wu->display_name, 'lastName' => '',
                'phone' => $phone, 'email' => null, 'google_id' => null, 'password_hash' => null,
                'is_admin' => true, 'perms' => $perms, 'wp_user_id' => $wu->ID,
                'addresses' => [], 'created_at' => date('c'),
            ];
        }
        /* حسابِ وردپرسشون با سیستمِ جدید هماهنگ می‌شه: دسترسیِ محصولات = ویرایشگر، وگرنه بدونِ پیشخوان */
        $wu->set_role(in_array('products', $perms, true) ? 'editor' : 'subscriber');
    }
    ap_write_data($data);
    if (get_role('ap_staff')) remove_role('ap_staff');
    update_option('ap_staff_to_admins_v1', time(), false);
}, 30);

/* ---------- لوگوی سایت در وردپرس: صفحه‌ی ورود، نوار مدیریت و فاویکون پیشخوان ---------- */
function ap_logo_url($file = 'logo.png') {
    return get_template_directory_uri() . '/assets/' . $file;
}
add_action('login_enqueue_scripts', function () {
    echo '<style>#login h1 a{background-image:url(' . esc_url(ap_logo_url()) . ') !important;background-size:contain !important;width:96px !important;height:96px !important;border-radius:22px;}</style>';
});
add_filter('login_headerurl', function () { return home_url('/'); });
add_filter('login_headertext', function () { return get_bloginfo('name'); });

function ap_adminbar_logo_css() {
    if (!is_admin_bar_showing()) return;
    echo '<style>#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before{content:"" !important;}'
        . '#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon{background:url(' . esc_url(ap_logo_url('logo-32.png')) . ') center/20px no-repeat !important;width:20px;}</style>';
}
add_action('admin_head', 'ap_adminbar_logo_css');
add_action('wp_head', 'ap_adminbar_logo_css');
/* منوی لوگوی وردپرس (درباره وردپرس و ...) به لینک سایت تبدیل می‌شه */
add_action('admin_bar_menu', function ($bar) {
    foreach (['about', 'wporg', 'documentation', 'learn', 'support-forums', 'feedback', 'contribute'] as $n) $bar->remove_node($n);
    $bar->add_node(['id' => 'wp-logo', 'href' => home_url('/'), 'meta' => ['title' => get_bloginfo('name')]]);
}, 20);
add_action('admin_head', function () {
    if (!has_site_icon()) echo '<link rel="icon" href="' . esc_url(ap_logo_url('logo-32.png')) . '">';
});
add_action('login_head', function () {
    if (!has_site_icon()) echo '<link rel="icon" href="' . esc_url(ap_logo_url('logo-32.png')) . '">';
});

/* یک‌بار: لوگوی قالب توی کتابخانه‌ی رسانه‌ی وردپرس آپلود و به‌عنوان «آیکون سایت» و «لوگوی سایت»
   ثبت می‌شه؛ این همون آیکونیه که گوگل کنار نتیجه‌ی جستجو نشون می‌ده. */
add_action('admin_init', function () {
    if (get_option('ap_logo_installed_v1') || !current_user_can('manage_options')) return;
    $src = get_template_directory() . '/assets/logo.png';
    if (!file_exists($src)) return;
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $tmp = wp_tempnam('cartehran-logo.png');
    copy($src, $tmp);
    $id = media_handle_sideload(['name' => 'cartehran-logo.png', 'tmp_name' => $tmp], 0, 'لوگوی CarTehran');
    if (is_wp_error($id)) { @unlink($tmp); return; }
    update_option('site_icon', $id);
    set_theme_mod('custom_logo', $id);
    update_option('ap_logo_installed_v1', 1);
});
add_action('after_setup_theme', function () { add_theme_support('custom-logo'); });

/* برند/مدل/سالِ خودروهای چینی رایج (vehicles-seed.php) */
require_once __DIR__ . '/vehicles-seed.php';

/* محصولات پایه برای همه‌ی مدل‌ها (parts-seed.php) */
require_once __DIR__ . '/parts-seed.php';

/* یک‌بار: صفحه‌ی اینستاگرامِ سایت (@auto.nexv.ir) توی نوارِ بالا و صفحه‌ی «درباره ما» */
add_action('init', function () {
    if (get_option('ap_instagram_set_v1')) return;
    if (trim((string) get_theme_mod('ap_instagram_url', '')) === '') set_theme_mod('ap_instagram_url', 'https://www.instagram.com/auto.nexv.ir');
    update_option('ap_instagram_set_v1', 1, false);
});

/* =========================================================================
 * پیوندِ سئو برای «خودرو + نوعِ قطعه»: /car/chery/tiggo-7/part/brake-pads/
 * (برند/مدل/تیپ خودشون از قبل پیوند دارن: /car/chery/tiggo-7/excellent/)
 * همون صفحه‌ی فروشگاه باز می‌شه، با خودرو و دسته‌ی انتخاب‌شده، عنوان/توضیح/canonicalِ
 * مخصوصِ خودش. اگه محصولی نداشته باشه noindex می‌شه تا صفحه‌ی خالی ایندکس نشه.
 * ========================================================================= */
add_action('init', function () {
    add_rewrite_tag('%ap_part_cat%', '([a-z0-9\-]+)');
    add_rewrite_rule('^car/(.+?)/part/([a-z0-9\-]+)/?$', 'index.php?ap_vehicle=$matches[1]&ap_part_cat=$matches[2]', 'top');
    if (get_option('ap_part_rewrite_v1') !== '1') { flush_rewrite_rules(false); update_option('ap_part_rewrite_v1', '1', false); }
}, 20);
add_filter('query_vars', function ($v) { $v[] = 'ap_part_cat'; return $v; });

/* «خودرو + قطعه»ی فعلی، یا null */
function ap_part_combo() {
    static $c = false;
    if ($c !== false) return $c;
    $c = null;
    if (!function_exists('is_tax') || !is_tax('ap_vehicle')) return $c;
    $slug = sanitize_title((string) get_query_var('ap_part_cat'));
    if ($slug === '') return $c;
    $cat = get_term_by('slug', $slug, 'ap_cat');
    $veh = get_queried_object();
    if (!$cat || !$veh || is_wp_error($veh)) return $c;
    $chain = [];
    foreach (array_reverse(get_ancestors($veh->term_id, 'ap_vehicle')) as $aid) { $t = get_term($aid, 'ap_vehicle'); if ($t && !is_wp_error($t)) $chain[] = $t->name; }
    $chain[] = $veh->name;
    $label = function_exists('ap_vehicle_label') ? ap_vehicle_label($chain) : implode(' ', $chain);
    $ids = get_posts(['post_type' => 'ap_product', 'post_status' => 'publish', 'numberposts' => 100, 'fields' => 'ids', 'tax_query' => [
        'relation' => 'AND',
        ['taxonomy' => 'ap_vehicle', 'terms' => (int) $veh->term_id, 'include_children' => true],
        ['taxonomy' => 'ap_cat', 'terms' => (int) $cat->term_id, 'include_children' => true],
    ]]);
    $vlink = get_term_link($veh);
    $c = ['cat' => $cat, 'veh' => $veh, 'label' => $label, 'ids' => $ids,
          'url' => is_wp_error($vlink) ? home_url('/') : trailingslashit($vlink) . 'part/' . $cat->slug . '/',
          'title' => $cat->name . ' ' . $label];
    return $c;
}
add_filter('rank_math/frontend/canonical', function ($u) { $c = ap_part_combo(); return $c ? $c['url'] : $u; }, 99);
add_filter('rank_math/frontend/title', function ($t) { $c = ap_part_combo(); return $c ? 'خرید ' . $c['title'] . ' | قیمت روز | ' . get_bloginfo('name') : $t; }, 99);
add_filter('rank_math/frontend/description', function ($d) {
    $c = ap_part_combo();
    return $c ? 'خرید ' . $c['title'] . '؛ اورجینال و آفتر مارکت با گارانتی اصالت، قیمت روز و ارسال سریع به سراسر ایران.' : $d;
}, 99);
add_filter('rank_math/frontend/robots', function ($r) {
    $c = ap_part_combo();
    if ($c && !$c['ids']) { $r['index'] = 'noindex'; $r['follow'] = 'follow'; }
    return $r;
}, 99);

/* =========================================================================
 * سایت‌مپِ کامل برای سرچ کنسول (Rank Math): آدرسِ اصلی /sitemap_index.xml
 * ۱) یک‌بار: ماژولِ سایت‌مپ روشن و نوع‌های درست انتخاب می‌شن (محصول، مقاله، برگه، دسته، خودرو).
 * ۲) برند و مدلِ خودرو داخلِ سایت‌مپ (محصولات به «مدل» وصلن، پس برند در وردپرس خالی دیده
 *    می‌شد و Rank Math حذفش می‌کرد)؛ تیپ و سالِ بدونِ محصول بیرون می‌مونن.
 * ۳) سایت‌مپِ جدا برای صفحه‌های «خودرو + نوعِ قطعه» (/car/.../part/...) — فقط ترکیب‌هایی که
 *    محصول دارن — که خودکار به فهرستِ اصلیِ Rank Math اضافه می‌شه.
 * ========================================================================= */
add_action('admin_init', function () {
    if (get_option('ap_sitemap_setup_v1') || !current_user_can('manage_options')) return;
    $mods = get_option('rank_math_modules', []);
    if (is_array($mods) && $mods && !in_array('sitemap', $mods, true)) { $mods[] = 'sitemap'; update_option('rank_math_modules', $mods); }
    $o = get_option('rank-math-options-sitemap', []);
    if (!is_array($o)) $o = [];
    $o = array_merge($o, [
        'items_per_page' => 1000, 'include_images' => 'on', 'include_featured_image' => 'on',
        'pt_ap_product_sitemap' => 'on', 'pt_ap_article_sitemap' => 'on', 'pt_page_sitemap' => 'on',
        'pt_attachment_sitemap' => 'off',
        'tax_ap_cat_sitemap' => 'on', 'tax_ap_vehicle_sitemap' => 'on',
        'tax_category_sitemap' => 'off', 'tax_post_tag_sitemap' => 'off', 'tax_ap_brand_sitemap' => 'off',
    ]);
    update_option('rank-math-options-sitemap', $o);
    if (function_exists('ap_flush_rank_math_sitemap')) ap_flush_rank_math_sitemap();
    flush_rewrite_rules(false);
    update_option('ap_sitemap_setup_v1', 1, false);
}, 70);

/* برند/مدلِ خودرو حتی وقتی در وردپرس «خالی» حساب می‌شن */
add_filter('rank_math/sitemap/exclude_empty_terms', function ($exclude, $taxonomies = []) {
    return in_array('ap_vehicle', (array) $taxonomies, true) ? false : $exclude;
}, 20, 2);
/* تیپ/سال (سطحِ سوم به بعد) فقط اگه واقعاً محصول داشته باشن */
add_filter('rank_math/sitemap/entry', function ($url, $type, $object) {
    if ($type === 'term' && $object instanceof WP_Term && $object->taxonomy === 'ap_vehicle') {
        if (count(get_ancestors($object->term_id, 'ap_vehicle')) >= 2 && (int) $object->count === 0) return false;
    }
    return $url;
}, 30, 3);

/* ترکیب‌های «خودرو + نوعِ قطعه» که محصول دارن: [[url, lastmod], ...] (۱۲ ساعت کش) */
function ap_car_part_sitemap_rows() {
    $rows = get_transient('ap_car_part_sitemap_v1');
    if (is_array($rows)) return $rows;
    global $wpdb;
    $pairs = $wpdb->get_results("
        SELECT tv.term_id AS veh, tc.term_id AS cat, MAX(p.post_modified_gmt) AS mod_gmt
        FROM {$wpdb->posts} p
        JOIN {$wpdb->term_relationships} rv ON rv.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} tv ON tv.term_taxonomy_id = rv.term_taxonomy_id AND tv.taxonomy = 'ap_vehicle'
        JOIN {$wpdb->term_relationships} rc ON rc.object_id = p.ID
        JOIN {$wpdb->term_taxonomy} tc ON tc.term_taxonomy_id = rc.term_taxonomy_id AND tc.taxonomy = 'ap_cat'
        WHERE p.post_type = 'ap_product' AND p.post_status = 'publish'
        GROUP BY tv.term_id, tc.term_id");
    $map = [];
    foreach ((array) $pairs as $r) {
        /* خودِ خودرو + همه‌ی والدهاش (برند هم صفحه‌ی «برند + قطعه» داره) */
        $vehs = array_merge([(int) $r->veh], array_map('intval', get_ancestors((int) $r->veh, 'ap_vehicle')));
        foreach ($vehs as $v) {
            $k = $v . ':' . (int) $r->cat;
            if (!isset($map[$k]) || $r->mod_gmt > $map[$k]) $map[$k] = $r->mod_gmt;
        }
    }
    $rows = [];
    foreach ($map as $k => $mod) {
        [$v, $c] = array_map('intval', explode(':', $k));
        $vt = get_term($v, 'ap_vehicle'); $ct = get_term($c, 'ap_cat');
        if (!$vt || is_wp_error($vt) || !$ct || is_wp_error($ct)) continue;
        $link = get_term_link($vt);
        if (is_wp_error($link)) continue;
        $rows[] = [trailingslashit($link) . 'part/' . $ct->slug . '/', $mod ? gmdate('c', strtotime($mod . ' UTC')) : ''];
    }
    usort($rows, function ($a, $b) { return strcmp($a[0], $b[0]); });
    set_transient('ap_car_part_sitemap_v1', $rows, 12 * HOUR_IN_SECONDS);
    return $rows;
}
add_action('save_post_ap_product', function () { delete_transient('ap_car_part_sitemap_v1'); });

/* به فهرستِ اصلیِ سایت‌مپ (sitemap_index.xml) اضافه می‌شه */
add_filter('rank_math/sitemap/index', function ($xml) {
    $rows = ap_car_part_sitemap_rows();
    $pages = (int) ceil(count($rows) / 1000);
    for ($i = 1; $i <= $pages; $i++) {
        $last = '';
        foreach (array_slice($rows, ($i - 1) * 1000, 1000) as $r) if ($r[1] > $last) $last = $r[1];
        $xml .= "\t<sitemap>\n\t\t<loc>" . esc_url(home_url('/car-parts-sitemap' . ($i > 1 ? $i : '') . '.xml')) . "</loc>\n"
              . ($last ? "\t\t<lastmod>" . esc_html($last) . "</lastmod>\n" : '') . "\t</sitemap>\n";
    }
    return $xml;
}, 20);

/* خودِ فایلِ /car-parts-sitemap.xml (و car-parts-sitemap2.xml و ...) */
add_action('wp_loaded', function () {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#/car-parts-sitemap(\d*)\.xml$#', $path, $m)) return;
    $page = max(1, (int) ($m[1] ?: 1));
    $rows = array_slice(ap_car_part_sitemap_rows(), ($page - 1) * 1000, 1000);
    if (!$rows) { status_header(404); exit; }
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($rows as $r) {
        echo "\t<url>\n\t\t<loc>" . esc_url($r[0]) . "</loc>\n" . ($r[1] ? "\t\t<lastmod>" . esc_html($r[1]) . "</lastmod>\n" : '') . "\t</url>\n";
    }
    echo '</urlset>';
    exit;
}, 1);

/* =========================================================================
 * /sitemap_index.xml همیشه کار کنه: اگه ماژولِ سایت‌مپِ Rank Math روشن باشه همون جواب می‌ده؛
 * وگرنه (مثلِ الان که robots.txt به wp-sitemap.xml وردپرس اشاره می‌کرد و این آدرس صفحه‌ی اصلی
 * رو نشون می‌داد) خودِ قالب یک فهرستِ کامل می‌سازه: سایت‌مپ‌های داخلیِ وردپرس (محصول، مقاله،
 * برگه، دسته، خودرو) + سایت‌مپِ «خودرو + قطعه». فهرستِ کاربران (نویسنده‌ها) حذف می‌شه.
 * ========================================================================= */
function ap_rank_math_sitemap_active() {
    return class_exists('\RankMath\Helper') && method_exists('\RankMath\Helper', 'is_module_active') && \RankMath\Helper::is_module_active('sitemap');
}
/* سایت‌مپِ داخلیِ وردپرس: بدونِ فهرستِ کاربران و نوع‌های بی‌استفاده */
add_filter('wp_sitemaps_add_provider', function ($provider, $name) { return $name === 'users' ? false : $provider; }, 10, 2);
add_filter('wp_sitemaps_post_types', function ($types) { unset($types['post'], $types['attachment']); return $types; });
add_filter('wp_sitemaps_taxonomies', function ($tax) { unset($tax['category'], $tax['post_tag'], $tax['ap_brand'], $tax['post_format']); return $tax; });
/* برند و مدلِ خودرو حتی وقتی «خالی» حساب می‌شن (محصولات به مدل وصلن)؛ تیپ/سالِ بی‌محصول نه */
add_filter('wp_sitemaps_taxonomies_query_args', function ($args, $taxonomy) {
    if ($taxonomy !== 'ap_vehicle') return $args;
    $args['hide_empty'] = false;
    $skip = get_transient('ap_vehicle_sitemap_skip_v1');
    if (!is_array($skip)) {
        $skip = [];
        foreach ((array) get_terms(['taxonomy' => 'ap_vehicle', 'hide_empty' => false, 'fields' => 'id=>parent']) as $id => $parent) {
            if ($parent && (int) get_term_field('count', $id, 'ap_vehicle') === 0) {
                $pt = get_term($parent, 'ap_vehicle');
                if ($pt && !is_wp_error($pt) && $pt->parent) $skip[] = (int) $id;   /* سطحِ سوم به بعد و خالی */
            }
        }
        set_transient('ap_vehicle_sitemap_skip_v1', $skip, 12 * HOUR_IN_SECONDS);
    }
    if ($skip) $args['exclude'] = $skip;
    return $args;
}, 10, 2);
add_filter('wp_sitemaps_max_urls', function () { return 1000; });

add_action('wp_loaded', function () {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#/sitemap_index\.xml$#', $path) || ap_rank_math_sitemap_active()) return;
    $locs = [];
    if (function_exists('wp_sitemaps_get_server')) {
        $server = wp_sitemaps_get_server();
        if ($server && isset($server->index)) foreach ((array) $server->index->get_sitemap_list() as $e) {
            $loc = is_array($e) ? (string) ($e['loc'] ?? '') : '';
            if ($loc !== '') $locs[] = $loc;
        }
    }
    $rows = function_exists('ap_car_part_sitemap_rows') ? ap_car_part_sitemap_rows() : [];
    for ($i = 1, $n = (int) ceil(count($rows) / 1000); $i <= $n; $i++) $locs[] = home_url('/car-parts-sitemap' . ($i > 1 ? $i : '') . '.xml');
    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    header('X-Robots-Tag: noindex, follow');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach (array_unique($locs) as $l) echo "\t<sitemap>\n\t\t<loc>" . esc_url($l) . "</loc>\n\t</sitemap>\n";
    echo '</sitemapindex>';
    exit;
}, 2);
/* robots.txt هم به همین فهرستِ کامل اشاره کنه */
add_filter('robots_txt', function ($out, $public) {
    if (!$public) return $out;
    $line = 'Sitemap: ' . home_url('/sitemap_index.xml');
    if (strpos($out, $line) === false) $out = rtrim($out) . "\n" . $line . "\n";
    return $out;
}, 99, 2);

/* =========================================================================
 * پیوندِ کوتاه و تمیز برای همه‌ی سطح‌های خودرو:
 *   /car/suzuki/grand-vitara-montage/auto-2000/1392/  (نه  .../1392-auto-2000-grand-vitara-montage/)
 * هر سطح «اسلاگِ کوتاهِ» خودش (term meta: ap_short) رو داره؛ پیوند از همین‌ها ساخته می‌شه و
 * موقعِ باز شدنِ آدرس، مسیر سطح‌به‌سطح به خودروی درست برگردونده می‌شه. آدرس‌های قبلی (اسلاگِ
 * بلند) با ۳۰۱ به آدرسِ کوتاه می‌رن.
 * ========================================================================= */
function ap_vehicle_short($term) {
    $s = (string) get_term_meta($term->term_id, 'ap_short', true);
    return $s !== '' ? sanitize_title($s) : $term->slug;
}
add_filter('term_link', function ($url, $term, $taxonomy) {
    if ($taxonomy !== 'ap_vehicle' || !is_object($term)) return $url;
    $parts = [ap_vehicle_short($term)];
    $guard = 0; $cur = $term;
    while ($cur->parent && $guard++ < 8) {
        $cur = get_term((int) $cur->parent, 'ap_vehicle');
        if (!$cur || is_wp_error($cur)) return $url;          /* والدِ حذف‌شده (یتیم) */
        array_unshift($parts, ap_vehicle_short($cur));
    }
    return home_url('/car/' . implode('/', $parts) . '/');
}, 10, 3);

/* مسیرِ /car/a/b/c/ → خودروی درست (سطح‌به‌سطح با اسلاگِ کوتاه یا اسلاگِ واقعی) */
function ap_vehicle_resolve_path($path) {
    $segs = array_values(array_filter(explode('/', trim((string) $path, '/')), 'strlen'));
    if (!$segs) return null;
    $parent = 0; $found = null;
    foreach ($segs as $seg) {
        $seg = sanitize_title(urldecode($seg));
        $kids = get_terms(['taxonomy' => 'ap_vehicle', 'parent' => $parent, 'hide_empty' => false]);
        if (is_wp_error($kids) || !$kids) return null;
        $found = null;
        foreach ($kids as $k) { if (ap_vehicle_short($k) === $seg) { $found = $k; break; } }
        if (!$found) foreach ($kids as $k) { if ($k->slug === $seg) { $found = $k; break; } }
        if (!$found) return null;
        $parent = (int) $found->term_id;
    }
    return $found;
}
add_filter('request', function ($vars) {
    if (empty($vars['ap_vehicle']) || !is_string($vars['ap_vehicle'])) return $vars;
    $t = ap_vehicle_resolve_path($vars['ap_vehicle']);
    if ($t) $vars['ap_vehicle'] = $t->slug;
    return $vars;
});
/* آدرس‌های قبلی/غیراستاندارد → ۳۰۱ به پیوندِ کوتاه */
add_action('template_redirect', function () {
    if (!is_tax('ap_vehicle') || is_admin() || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;
    $t = get_queried_object();
    if (!$t || is_wp_error($t)) return;
    $want = get_term_link($t);
    if (is_wp_error($want)) return;
    $part = sanitize_title((string) get_query_var('ap_part_cat'));
    if ($part !== '') $want = trailingslashit($want) . 'part/' . $part . '/';
    $cur = trailingslashit(urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH)));
    $wantPath = trailingslashit((string) parse_url($want, PHP_URL_PATH));
    if (strpos($cur, '/page/') !== false || $cur === $wantPath) return;
    $qs = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    wp_safe_redirect($want . ($qs !== '' ? '?' . $qs : ''), 301);
    exit;
}, 5);

/* یک‌بار (و بعد از هر به‌روزرسانیِ فهرستِ خودروها): سطح‌هایی که والدشون حذف شده پاک می‌شن
   (همون‌ها که پیوندِ خراب /car//1395-... داشتن)؛ کشِ فهرستِ خودروها و سایت‌مپ پاک می‌شه. */
add_action('init', function () {
    $tag = defined('AP_VEHICLE_SEED_VERSION') ? AP_VEHICLE_SEED_VERSION : '0';
    if (get_option('ap_vehicle_orphans_done') === $tag || get_option('ap_vehicle_seed') !== $tag) return;
    $all = get_terms(['taxonomy' => 'ap_vehicle', 'hide_empty' => false, 'fields' => 'id=>parent']);
    if (!is_wp_error($all)) {
        $exists = array_flip(array_map('intval', array_keys($all)));
        $removed = 0;
        foreach ($all as $id => $parent) {
            if ((int) $parent && !isset($exists[(int) $parent])) {
                foreach ((array) get_term_children((int) $id, 'ap_vehicle') as $c) { if (!is_wp_error($c)) wp_delete_term((int) $c, 'ap_vehicle'); }
                wp_delete_term((int) $id, 'ap_vehicle');
                $removed++;
            }
        }
    }
    foreach (['ap_vehicles_cache_v2', 'ap_vehicle_sitemap_skip_v1', 'ap_car_part_sitemap_v1', 'ap_products_cache_v3'] as $k) delete_transient($k);
    update_option('ap_vehicle_orphans_done', $tag, false);
}, 40);

/* =========================================================================
 * انتقال دامنه: auto.nexv.ir → دامنه‌ی جدید (cartehran.ir)
 * وقتی «نشانی سایت» در پیشخوان (تنظیمات ← عمومی) به دامنه‌ی جدید عوض بشه، هر درخواستی
 * که هنوز با دامنه‌ی قدیمی بیاد (لینک‌های گوگل، اینستاگرام، بوکمارک‌ها) با ۳۰۱ به همون
 * صفحه روی دامنه‌ی جدید می‌ره؛ اعتبارِ سئو منتقل می‌شه. تا وقتی نشانی عوض نشده، کاری نمی‌کنه.
 * ========================================================================= */
add_action('init', function () {
    if ((defined('WP_CLI') && WP_CLI) || (defined('DOING_CRON') && DOING_CRON)) return;
    $req  = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    $req  = preg_replace('/:\d+$/', '', $req);
    $old  = ['auto.nexv.ir', 'www.auto.nexv.ir'];
    $home = strtolower((string) parse_url(home_url('/'), PHP_URL_HOST));
    if (!in_array($req, $old, true) || $home === '' || in_array($home, $old, true)) return;
    wp_redirect('https://' . $home . ($_SERVER['REQUEST_URI'] ?? '/'), 301);
    exit;
}, 0);

/* =========================================================================
 * اصلاحِ پیوندِ محصولاتی که هنوز پیوندِ انگلیسیِ درست ندارن (مثل brake-pad-rear-by-vay-dy-han).
 * پیوندِ درست: از بخشِ انگلیسیِ عنوان («فارسی | English») یا کلیدِ ساختِ خودکار.
 * فقط پیوندهای «بد» عوض می‌شن: فارسی/کدشده، پسوندِ -2، یا عمدتاً نویسه‌گردانیِ فارسی؛ پیوندی که
 * خودش انگلیسیِ درستیه دست نمی‌خوره. پیوندِ قبلی با ۳۰۱ ریدایرکت می‌شه. محصولی که فقط عنوانِ فارسی
 * داره و نمی‌شه براش پیوندِ انگلیسی ساخت، در پیشخوان فهرست می‌شه.
 * ========================================================================= */
function ap_expected_product_slug($id, $title) {
    if (strpos($title, '|') !== false && function_exists('ap_slug_from_title')) {
        $en = trim(substr($title, strrpos($title, '|') + 1));
        if (preg_match('/[A-Za-z]/', $en) && !preg_match('/[^\x00-\x7f]/', $en)) return ap_slug_from_title($title);
    }
    $k = (string) get_post_meta($id, '_ap_seed_key', true);
    return preg_match('/^[a-z0-9-]+$/', $k) ? $k : '';
}
function ap_slug_is_bad($slug, $want) {
    $slug = urldecode((string) $slug);
    if ($slug === '' || preg_match('/[^a-z0-9-]/', $slug) || preg_match('/-\d+$/', $slug) && !preg_match('/-\d+$/', $want)) return true;
    if ($want === '' || $slug === $want) return false;
    $have = array_filter(explode('-', $slug)); $ok = array_flip(array_filter(explode('-', $want)));
    $hit = 0; foreach ($have as $t) if (isset($ok[$t])) $hit++;
    return $have && ($hit / count($have)) < 0.5;     /* بیشترِ کلمه‌ها ربطی به نامِ انگلیسی ندارن = نویسه‌گردانی */
}
add_action('init', function () {
    if (get_option('ap_product_slug_fix_v1') === 'done' || (defined('DOING_AJAX') && DOING_AJAX)) return;
    if (get_transient('ap_slug_fix_lock')) return;
    set_transient('ap_slug_fix_lock', 1, 60);
    global $wpdb;
    $last = (int) get_option('ap_product_slug_fix_last', 0);
    $rows = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_name, post_status FROM {$wpdb->posts} WHERE post_type = 'ap_product' AND post_status IN ('publish','draft','pending','private') AND ID > %d ORDER BY ID ASC LIMIT 300", $last));
    if (!$rows) { update_option('ap_product_slug_fix_v1', 'done', false); delete_transient('ap_slug_fix_lock'); delete_transient('ap_products_cache_v3'); return; }
    $manual = get_option('ap_product_slug_manual', []);
    foreach ($rows as $r) {
        $last = (int) $r->ID;
        $want = ap_expected_product_slug((int) $r->ID, (string) $r->post_title);
        if ($want === '') { if (ap_slug_is_bad($r->post_name, '')) $manual[] = (int) $r->ID; continue; }
        if (!ap_slug_is_bad($r->post_name, $want)) continue;
        $new = wp_unique_post_slug($want, (int) $r->ID, 'publish', 'ap_product', 0);
        if ($new !== $want) { $manual[] = (int) $r->ID; continue; }   /* پیوندِ درست دستِ محصولِ دیگه‌ست؛ دستی بررسی بشه */
        $wpdb->update($wpdb->posts, ['post_name' => $new], ['ID' => (int) $r->ID]);
        if ($r->post_name !== '') add_post_meta((int) $r->ID, '_wp_old_slug', $r->post_name);
        clean_post_cache((int) $r->ID);
    }
    update_option('ap_product_slug_manual', array_values(array_unique($manual)), false);
    update_option('ap_product_slug_fix_last', $last, false);
    delete_transient('ap_slug_fix_lock');
}, 45);
add_action('admin_notices', function () {
    if (!current_user_can('manage_options') || get_option('ap_product_slug_fix_v1') !== 'done') return;
    $ids = array_filter((array) get_option('ap_product_slug_manual', []), function ($id) { return get_post_status($id); });
    if (!$ids) return;
    echo '<div class="notice notice-warning"><p><strong>محصولاتی که پیوندِ انگلیسی ندارن (' . count($ids) . '):</strong> به عنوانشون بخشِ انگلیسی اضافه کنید («نام فارسی | English name») تا پیوندِ انگلیسی بگیرن.</p><ul style="list-style:disc;padding-right:20px">';
    foreach (array_slice($ids, 0, 30) as $id) echo '<li><a href="' . esc_url(get_edit_post_link($id)) . '">' . esc_html(get_the_title($id)) . '</a> — <code dir="ltr">' . esc_html(urldecode(get_post_field('post_name', $id))) . '</code></li>';
    echo '</ul></div>';
});

/* سایت‌مپ: حذفِ «XSLT» (استایلِ مرورگر) که کروم داره پشتیبانیش رو برمی‌داره و بالای صفحه هشدار می‌داد.
   گوگل به استایل نیازی نداره؛ مرورگر همون XMLِ خام رو نشون می‌ده. */
add_action('wp_loaded', function () {
    $path = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
    if (!preg_match('#(sitemap[^/]*\.xml|sitemap\.xsl)$#i', $path)) return;
    ob_start(function ($buf) { return preg_replace('#<\?xml-stylesheet[^>]*\?>\s*#i', '', $buf); });
}, 0);

/* یک‌بار: نامِ سایت (عنوانِ وردپرس که آخرِ عنوانِ همه‌ی صفحه‌ها میاد) از NEXV به CarTehran */
add_action('init', function () {
    if (get_option('ap_brand_rename_v1')) return;
    foreach (['blogname', 'blogdescription'] as $opt) {
        $v = (string) get_option($opt, '');
        $n = str_replace(['نکس‌وی', 'نکس وی', 'NEXV', 'Nexv', 'nexv'], ['کارتهران', 'کارتهران', 'CarTehran', 'CarTehran', 'CarTehran'], $v);
        if ($n !== $v) update_option($opt, $n);
    }
    update_option('ap_brand_rename_v1', 1, false);
});
