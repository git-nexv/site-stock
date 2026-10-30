<?php
if (!defined('ABSPATH')) { exit; }

/**
 * مدیریت ادمین‌ها.
 * - مالک سایت (کاربر وردپرسِ kaveh با نقش مدیر کل) شماره و رمز خودش رو از
 *   پیشخوان ← قطعات یدکی ← مدیریت ادمین‌ها عوض می‌کنه.
 * - ادمین‌های زیرمجموعه نقش ap_staff دارن و فقط بخش‌هایی که مالک براشون تیک زده رو می‌بینن.
 * - ورود همه‌شون از همون فرم سایت با شماره + رمزه؛ رمز با حساب وردپرسِ خودشون چک می‌شه.
 *   برای این‌که api.php (که بدون وردپرس اجرا می‌شه) بدونه کدوم شماره ادمینه، فهرست
 *   ادمین‌ها توی همون دیتابیس JSON فروشگاه (فیلد wp_user_id) همگام نگه داشته می‌شه.
 */

define('AP_OWNER_LOGIN', 'kaveh');

/* بخش‌هایی که به ادمین زیرمجموعه داده می‌شه ← توانایی‌های وردپرس */
function ap_staff_perms() {
    return [
        'orders'     => ['label' => 'سفارش‌ها (مشاهده، تغییر وضعیت، فاکتور)', 'caps' => ['ap_manage_orders']],
        'products'   => ['label' => 'محصولات، مقالات و دیدگاه‌ها', 'caps' => [
            'edit_posts', 'edit_others_posts', 'edit_published_posts', 'publish_posts',
            'delete_posts', 'delete_others_posts', 'delete_published_posts', 'upload_files', 'moderate_comments',
        ]],
        'categories' => ['label' => 'دسته‌بندی‌ها و برند/مدل/سال خودرو', 'caps' => ['manage_categories']],
        'appearance' => ['label' => 'ظاهر سایت (سفارشی‌سازی، بیلبوردها، ویترین)', 'caps' => ['edit_theme_options']],
    ];
}

add_action('init', function () {
    if (!get_role('ap_staff')) {
        add_role('ap_staff', 'ادمین زیرمجموعه', ['read' => true]);
    }
    if (get_option('ap_staff_version') !== '1') {
        $owner = get_user_by('login', AP_OWNER_LOGIN);
        if ($owner && !get_user_meta($owner->ID, 'ap_phone', true)) {
            update_user_meta($owner->ID, 'ap_phone', AP_OWNER_PHONE);
        }
        ap_staff_sync();
        /* تا وقتی حساب kaveh ساخته نشده، دوباره امتحان می‌شه. */
        if ($owner) update_option('ap_staff_version', '1');
    }
});

/* ادمین‌ها: مالک (kaveh) + همه‌ی کاربرانِ نقش ap_staff */
function ap_staff_users() {
    $list = [];
    $owner = get_user_by('login', AP_OWNER_LOGIN);
    if ($owner) $list[] = $owner;
    foreach (get_users(['role' => 'ap_staff']) as $u) $list[] = $u;
    return $list;
}

function ap_staff_user_perms($user) {
    $out = [];
    foreach (ap_staff_perms() as $key => $p) {
        if (!empty($user->allcaps[$p['caps'][0]])) $out[] = $key;
    }
    return $out;
}

function ap_normalize_phone($phone) {
    $d = preg_replace('/[^0-9]/', '', strtr((string) $phone, ['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9']));
    if (substr($d, 0, 2) === '98' && strlen($d) === 12) $d = '0' . substr($d, 2);
    return $d;
}

/* فهرست ادمین‌ها رو توی دیتابیس JSON فروشگاه هم ثبت می‌کنه تا ورود از فرم سایت کار کنه. */
function ap_staff_sync() {
    $data = ap_read_data();
    if (!isset($data['users']) || !is_array($data['users'])) $data['users'] = [];
    if (!isset($data['next_user_id'])) $data['next_user_id'] = 2;

    $staff = [];
    foreach (ap_staff_users() as $u) {
        $phone = ap_normalize_phone(get_user_meta($u->ID, 'ap_phone', true));
        if ($phone === '') continue;
        $isOwner = $u->user_login === AP_OWNER_LOGIN;
        $staff[$phone] = [
            'wp_user_id' => $u->ID,
            'is_admin'   => $isOwner || user_can($u, 'ap_manage_orders'),
            'name'       => $u->display_name,
        ];
    }

    foreach ($data['users'] as &$rec) {
        $phone = (string) ($rec['phone'] ?? '');
        if (isset($staff[$phone])) {
            $rec['wp_user_id'] = $staff[$phone]['wp_user_id'];
            $rec['is_staff']   = true;
            $rec['is_admin']   = $staff[$phone]['is_admin'];
            unset($staff[$phone]);
        } elseif (!empty($rec['wp_user_id']) || !empty($rec['is_staff']) || !empty($rec['is_admin'])) {
            unset($rec['wp_user_id'], $rec['is_staff']);
            $rec['is_admin'] = false;
        }
    }
    unset($rec);

    foreach ($staff as $phone => $s) {
        $data['users'][] = [
            'id'            => $data['next_user_id']++,
            'firstName'     => $s['name'],
            'lastName'      => '',
            'phone'         => (string) $phone,
            'email'         => null,
            'google_id'     => null,
            'password_hash' => null,
            'is_admin'      => $s['is_admin'],
            'is_staff'      => true,
            'wp_user_id'    => $s['wp_user_id'],
            'addresses'     => [],
            'created_at'    => date('c'),
        ];
    }
    ap_write_data($data);
}

/* شماره‌ی تکراری بین ادمین‌ها مجاز نیست */
function ap_staff_phone_taken($phone, $except_id = 0) {
    foreach (ap_staff_users() as $u) {
        if ($u->ID !== $except_id && ap_normalize_phone(get_user_meta($u->ID, 'ap_phone', true)) === $phone) return true;
    }
    return false;
}

add_action('admin_menu', function () {
    add_submenu_page('ap-dashboard', 'مدیریت ادمین‌ها', 'مدیریت ادمین‌ها', 'manage_options', 'ap-staff', 'ap_staff_page');
}, 10);

function ap_staff_redirect($msg, $ok = true) {
    wp_safe_redirect(add_query_arg(['page' => 'ap-staff', 'ap_msg' => rawurlencode($msg), 'ap_ok' => $ok ? 1 : 0], admin_url('admin.php')));
    exit;
}

/* حساب خود مالک: تغییر شماره و رمز */
add_action('admin_post_ap_staff_me', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_staff_me');
    $me = wp_get_current_user();

    $phone = ap_normalize_phone(wp_unslash($_POST['phone'] ?? ''));
    if (!preg_match('/^09\d{9}$/', $phone)) ap_staff_redirect('شماره موبایل معتبر نیست.', false);
    if (ap_staff_phone_taken($phone, $me->ID)) ap_staff_redirect('این شماره برای ادمین دیگه‌ای ثبت شده.', false);
    update_user_meta($me->ID, 'ap_phone', $phone);

    $pass  = (string) wp_unslash($_POST['pass'] ?? '');
    $pass2 = (string) wp_unslash($_POST['pass2'] ?? '');
    if ($pass !== '') {
        if (strlen($pass) < 6) ap_staff_redirect('رمز باید حداقل ۶ کاراکتر باشه.', false);
        if ($pass !== $pass2) ap_staff_redirect('رمز و تکرارش یکی نیستن.', false);
        wp_set_password($pass, $me->ID);
        /* wp_set_password سشن رو می‌بنده؛ دوباره وارد می‌شیم که از پیشخوان بیرون نیفتی. */
        wp_set_auth_cookie($me->ID, true);
    }
    ap_staff_sync();
    ap_staff_redirect('حساب شما ذخیره شد.');
});

/* افزودن / ویرایش ادمین زیرمجموعه */
add_action('admin_post_ap_staff_save', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    check_admin_referer('ap_staff_save');

    $id    = (int) ($_POST['user_id'] ?? 0);
    $name  = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
    $phone = ap_normalize_phone(wp_unslash($_POST['phone'] ?? ''));
    $pass  = (string) wp_unslash($_POST['pass'] ?? '');
    $perms = array_intersect(array_keys(ap_staff_perms()), (array) ($_POST['perms'] ?? []));

    if ($name === '') ap_staff_redirect('نام رو وارد کن.', false);
    if (!preg_match('/^09\d{9}$/', $phone)) ap_staff_redirect('شماره موبایل معتبر نیست.', false);
    if (ap_staff_phone_taken($phone, $id)) ap_staff_redirect('این شماره برای ادمین دیگه‌ای ثبت شده.', false);
    if ($pass !== '' && strlen($pass) < 6) ap_staff_redirect('رمز باید حداقل ۶ کاراکتر باشه.', false);

    if ($id) {
        $user = get_user_by('id', $id);
        if (!$user || !in_array('ap_staff', (array) $user->roles, true)) ap_staff_redirect('ادمین پیدا نشد.', false);
        wp_update_user(['ID' => $id, 'display_name' => $name, 'first_name' => $name]);
        if ($pass !== '') wp_set_password($pass, $id);
    } else {
        if ($pass === '') ap_staff_redirect('برای ادمین جدید رمز لازمه.', false);
        $login = 'staff_' . $phone;
        if (username_exists($login)) ap_staff_redirect('کاربری با این شماره از قبل وجود داره.', false);
        $id = wp_insert_user([
            'user_login'   => $login,
            'user_pass'    => $pass,
            'user_email'   => $login . '@ap.local',
            'display_name' => $name,
            'first_name'   => $name,
            'role'         => 'ap_staff',
        ]);
        if (is_wp_error($id)) ap_staff_redirect('ساخت ادمین نشد: ' . $id->get_error_message(), false);
        $user = get_user_by('id', $id);
    }

    update_user_meta($id, 'ap_phone', $phone);
    foreach (ap_staff_perms() as $key => $p) {
        foreach ($p['caps'] as $cap) {
            in_array($key, $perms, true) ? $user->add_cap($cap) : $user->remove_cap($cap);
        }
    }
    ap_staff_sync();
    ap_staff_redirect('ادمین ذخیره شد.');
});

add_action('admin_post_ap_staff_delete', function () {
    if (!current_user_can('manage_options')) wp_die('دسترسی غیرمجاز.');
    $id = (int) ($_GET['user_id'] ?? 0);
    check_admin_referer('ap_staff_delete_' . $id);
    $user = get_user_by('id', $id);
    if (!$user || !in_array('ap_staff', (array) $user->roles, true)) ap_staff_redirect('ادمین پیدا نشد.', false);
    require_once ABSPATH . 'wp-admin/includes/user.php';
    wp_delete_user($id, get_current_user_id());
    ap_staff_sync();
    ap_staff_redirect('ادمین حذف شد.');
});

function ap_staff_page() {
    $me     = wp_get_current_user();
    $perms  = ap_staff_perms();
    $staff  = get_users(['role' => 'ap_staff']);
    $editId = (int) ($_GET['edit'] ?? 0);
    $edit   = $editId ? get_user_by('id', $editId) : null;
    if ($edit && !in_array('ap_staff', (array) $edit->roles, true)) $edit = null;
    $editPerms = $edit ? ap_staff_user_perms($edit) : [];
    ?>
    <div class="wrap">
        <h1>مدیریت ادمین‌ها</h1>
        <?php if (isset($_GET['ap_msg'])): ?>
            <div class="notice notice-<?php echo !empty($_GET['ap_ok']) ? 'success' : 'error'; ?> is-dismissible"><p><?php echo esc_html(rawurldecode(wp_unslash($_GET['ap_msg']))); ?></p></div>
        <?php endif; ?>

        <h2>حساب من</h2>
        <p>با این شماره و رمز از فرم ورودِ سایت وارد می‌شی و مستقیم میای به پیشخوان.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ap_staff_me'); ?>
            <input type="hidden" name="action" value="ap_staff_me">
            <table class="form-table">
                <tr><th><label for="ap_me_phone">شماره موبایل</label></th>
                    <td><input type="tel" id="ap_me_phone" name="phone" dir="ltr" class="regular-text" required value="<?php echo esc_attr(get_user_meta($me->ID, 'ap_phone', true)); ?>"></td></tr>
                <tr><th><label for="ap_me_pass">رمز جدید</label></th>
                    <td><input type="password" id="ap_me_pass" name="pass" dir="ltr" class="regular-text" autocomplete="new-password">
                        <p class="description">اگه نمی‌خوای رمز عوض بشه خالی بذار.</p></td></tr>
                <tr><th><label for="ap_me_pass2">تکرار رمز جدید</label></th>
                    <td><input type="password" id="ap_me_pass2" name="pass2" dir="ltr" class="regular-text" autocomplete="new-password"></td></tr>
            </table>
            <?php submit_button('ذخیره‌ی حساب من'); ?>
        </form>

        <hr>
        <h2>ادمین‌های زیرمجموعه</h2>
        <?php if ($staff): ?>
            <table class="widefat striped" style="max-width:900px">
                <thead><tr><th>نام</th><th>شماره</th><th>دسترسی‌ها</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($staff as $u):
                    $labels = array_map(function ($k) use ($perms) { return $perms[$k]['label']; }, ap_staff_user_perms($u)); ?>
                    <tr>
                        <td><?php echo esc_html($u->display_name); ?></td>
                        <td dir="ltr"><?php echo esc_html(get_user_meta($u->ID, 'ap_phone', true)); ?></td>
                        <td><?php echo $labels ? esc_html(implode('، ', $labels)) : '<em>بدون دسترسی</em>'; ?></td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ap-staff&edit=' . $u->ID)); ?>">ویرایش</a> |
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin-post.php?action=ap_staff_delete&user_id=' . $u->ID), 'ap_staff_delete_' . $u->ID)); ?>"
                               style="color:#b32d2e" onclick="return confirm('این ادمین حذف بشه؟');">حذف</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>هنوز ادمین زیرمجموعه‌ای نساختی.</p>
        <?php endif; ?>

        <h3><?php echo $edit ? 'ویرایش ادمین: ' . esc_html($edit->display_name) : 'افزودن ادمین جدید'; ?></h3>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('ap_staff_save'); ?>
            <input type="hidden" name="action" value="ap_staff_save">
            <input type="hidden" name="user_id" value="<?php echo $edit ? (int) $edit->ID : 0; ?>">
            <table class="form-table">
                <tr><th><label for="ap_st_name">نام</label></th>
                    <td><input type="text" id="ap_st_name" name="name" class="regular-text" required value="<?php echo $edit ? esc_attr($edit->display_name) : ''; ?>"></td></tr>
                <tr><th><label for="ap_st_phone">شماره موبایل</label></th>
                    <td><input type="tel" id="ap_st_phone" name="phone" dir="ltr" class="regular-text" required placeholder="09xxxxxxxxx" value="<?php echo $edit ? esc_attr(get_user_meta($edit->ID, 'ap_phone', true)) : ''; ?>"></td></tr>
                <tr><th><label for="ap_st_pass">رمز</label></th>
                    <td><input type="password" id="ap_st_pass" name="pass" dir="ltr" class="regular-text" autocomplete="new-password" <?php echo $edit ? '' : 'required'; ?>>
                        <?php if ($edit): ?><p class="description">برای عوض نکردن رمز خالی بذار.</p><?php endif; ?></td></tr>
                <tr><th>دسترسی‌ها</th>
                    <td><fieldset>
                        <?php foreach ($perms as $key => $p): ?>
                            <label style="display:block;margin-bottom:6px">
                                <input type="checkbox" name="perms[]" value="<?php echo esc_attr($key); ?>" <?php checked(in_array($key, $editPerms, true)); ?>>
                                <?php echo esc_html($p['label']); ?>
                            </label>
                        <?php endforeach; ?>
                        <p class="description">ادمین زیرمجموعه هیچ‌وقت به تنظیمات کل سایت، افزونه‌ها و مدیریت ادمین‌ها دسترسی نداره.</p>
                    </fieldset></td></tr>
            </table>
            <?php submit_button($edit ? 'ذخیره‌ی تغییرات' : 'افزودن ادمین'); ?>
            <?php if ($edit): ?><a href="<?php echo esc_url(admin_url('admin.php?page=ap-staff')); ?>">انصراف</a><?php endif; ?>
        </form>
    </div>
    <?php
}

/* ادمین زیرمجموعه بعد از ورود مستقیم بره پیشخوان؛ نوار مدیریت هم براش نمایش داده بشه. */
add_filter('show_admin_bar', function ($show) {
    return $show || (is_user_logged_in() && in_array('ap_staff', (array) wp_get_current_user()->roles, true));
}, 20);
