<?php
if (!defined('ABSPATH') && !defined('ap_INVOICE_STANDALONE')) { exit; }

/**
 * فاکتورِ فروش (آذر ۱۴۰۵): A5، کاملاً سیاه‌وسفید (برای پرینترِ تک‌رنگ)، با لوگو.
 * - اطلاعاتِ فرستنده از همون منبعِ صفحه‌ی «درباره ما» خونده می‌شه (ap_sender_info)؛ اگه شماره/آدرس
 *   رو از «سفارشی‌سازی ← نوار بالای سایت» عوض کنی، فاکتورها (حتی قبلی‌ها) خودکار به‌روز می‌شن.
 * - اطلاعاتِ گیرنده همونیه که مشتری موقعِ سفارش وارد کرده.
 * - هر کالا: نام، متغیرهای انتخاب‌شده (رنگ، حافظه، سمت، کیفیت و ...)، نوعِ کالا (دسته) و کدِ کالا.
 */

/* اطلاعاتِ فروشگاه (فرستنده) — صفحه‌ی «درباره ما» هم از همین استفاده می‌کنه */
function ap_sender_info() {
    $phone = (string) get_theme_mod('ap_contact_phone', '09109220001');
    $ig = (string) get_theme_mod('ap_instagram_url', 'https://www.instagram.com/auto.nexv.ir');
    $igUser = preg_match('#instagram\.com/([^/?\#]+)#i', $ig, $m) ? '@' . $m[1] : '';
    return [
        'name'    => (get_bloginfo('name') ?: 'کار تهران'),
        'name_en' => 'CarTehran',
        'tagline' => 'فروشگاه تخصصی قطعات یدکی خودرو',
        'address' => (string) get_theme_mod('ap_contact_address', 'تهران، پاکدشت، انتهای بلوار سپاه، پلاک ۱۰، طبقه ۱'),
        'phone'   => $phone,
        'whatsapp'=> (string) get_theme_mod('ap_contact_whatsapp', $phone),
        'website' => preg_replace('#^https?://(www\.)?#', '', untrailingslashit(home_url('/'))),
        'instagram' => $igUser,
    ];
}

/* لوگوی سیاه‌وسفید (data URI تا موقعِ چاپ حتماً دیده بشه) */
function ap_invoice_logo() {
    $f = get_template_directory() . '/icons/invoice-logo.png';
    return is_file($f) ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($f)) : '';
}

/* متغیرهای انتخاب‌شده از کلیدِ سبد (id::c:رنگ::s:حافظه::x:برچسب=گزینه) */
function ap_invoice_variants($rawId) {
    $out = [];
    foreach (array_slice(explode('::', (string) $rawId), 1) as $p) {
        if (strpos($p, 'c:') === 0) $out[] = ['رنگ', rawurldecode(substr($p, 2))];
        elseif (strpos($p, 's:') === 0) $out[] = ['حافظه', rawurldecode(substr($p, 2))];
        elseif (strpos($p, 'x:') === 0 && ($eq = strpos($p, '=')) !== false) $out[] = [rawurldecode(substr($p, 2, $eq - 2)), rawurldecode(substr($p, $eq + 1))];
    }
    return $out;
}

function ap_render_invoice(array $order) {
    $S = ap_sender_info();
    $fa = function ($s) { return strtr((string) $s, ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']); };
    $money = function ($n) use ($fa) { return $fa(number_format((float) $n, 0, '.', '٬')); };
    $h = function ($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };

    $shipLabels = ['post' => 'پست پیشتاز', 'tipax' => 'تیپاکس (پس‌کرایه)', 'mahex' => 'ماهکس (پس‌کرایه)', 'peyk' => 'پیک — فقط تهران (پس‌کرایه)'];
    $ship = (string) ($order['shippingMethod'] ?? 'post');
    $isCod = $ship !== 'post';
    $statusLabels = ['pending' => 'در انتظار بررسی', 'confirmed' => 'تأیید شده', 'packing' => 'در حال بسته‌بندی', 'courier' => 'تحویل به پستچی', 'shipped' => 'ارسال شده', 'rejected' => 'رد شده'];
    $status = (string) ($order['status'] ?? 'pending');

    $num = 'CT-' . str_pad((string) (int) $order['id'], 6, '0', STR_PAD_LEFT);
    $d = date_create((string) ($order['created_at'] ?? 'now')) ?: new DateTime();
    if (function_exists('wp_timezone')) $d->setTimezone(wp_timezone()); else $d->setTimezone(new DateTimeZone('Asia/Tehran'));
    [$jy, $jm, $jd] = ap_gregorian_to_jalali((int) $d->format('Y'), (int) $d->format('n'), (int) $d->format('j'));
    $date = $fa(sprintf('%04d/%02d/%02d', $jy, $jm, $jd));
    $time = $fa($d->format('H:i'));

    $a = (array) ($order['address'] ?? []);
    $addrLine = trim(implode('، ', array_filter([
        (string) ($a['district'] ?? ''),
        (string) ($a['fullAddress'] ?? ($a['line'] ?? ($a['full'] ?? ($a['text'] ?? '')))),
        !empty($a['plaque']) ? 'پلاک ' . $a['plaque'] : '',
        !empty($a['unit']) ? 'واحد ' . $a['unit'] : '',
    ], 'strlen')));
    $region = trim(implode('، ', array_filter([(string) ($a['province'] ?? ''), (string) ($a['city'] ?? '')], 'strlen')));
    $postal = (string) ($a['postalCode'] ?? ($a['postal_code'] ?? ''));
    $recvName = trim((string) ($a['receiverName'] ?? '')) ?: (string) ($order['customerName'] ?? '');
    $recvPhone = trim((string) ($a['receiverPhone'] ?? '')) ?: (string) ($order['phone'] ?? '');

    $rows = ''; $i = 0; $count = 0;
    foreach ((array) ($order['items'] ?? []) as $it) {
        $i++;
        $qty = max(1, (int) ($it['qty'] ?? 1)); $count += $qty;
        $price = (int) ($it['price'] ?? 0);
        $rawId = (string) ($it['id'] ?? '');
        $pid = (int) explode('::', $rawId)[0];
        $title = trim((string) ($it['title'] ?? ''));
        /* عنوانِ دوزبانه («فارسی | English»): فارسی پررنگ، انگلیسی زیرش */
        $en = '';
        if (strpos($title, '|') !== false) { $parts = array_map('trim', explode('|', $title, 2)); $title = $parts[0]; $en = $parts[1]; }
        $vars = ap_invoice_variants($rawId);
        /* متغیرها گاهی داخلِ خودِ عنوان هم نوشته شدن («… (سمت: راست)»)؛ تکراری نشه */
        $title = trim(preg_replace('/\s*\((?:[^()]*:[^()]*)\)\s*$/u', '', $title));
        $type = '';
        if ($pid && function_exists('get_the_terms')) {
            $terms = get_the_terms($pid, 'ap_cat');
            if ($terms && !is_wp_error($terms)) { $t = end($terms); $type = $t->name; }
        }
        $sku = $pid && function_exists('get_post_meta') ? trim((string) get_post_meta($pid, '_ap_oem_code', true)) : '';
        $meta = [];
        if ($type !== '') $meta[] = 'نوع کالا: ' . $h($type);
        foreach ($vars as $v) $meta[] = $h($v[0]) . ': <b>' . $h($v[1]) . '</b>';
        if ($sku !== '') $meta[] = 'کد فنی (OEM): <span class="ltr">' . $h($sku) . '</span>';
        if ($pid) $meta[] = 'کد کالا: ' . $fa($pid);
        $rows .= '<tr><td class="n">' . $fa($i) . '</td><td class="d"><div class="t">' . $h($title) . '</div>'
            . ($en !== '' ? '<div class="en" dir="ltr">' . $h($en) . '</div>' : '')
            . '<div class="m">' . implode(' <span class="sep">|</span> ', $meta) . '</div></td>'
            . '<td class="n">' . $fa($qty) . '</td><td class="p">' . $money($price) . '</td><td class="p">' . $money($price * $qty) . '</td></tr>';
    }
    $itemsTotal = (int) ($order['itemsTotal'] ?? 0);
    $shipFee = (int) ($order['shippingFee'] ?? 0);
    $grand = $isCod ? $itemsTotal : (int) ($order['grandTotal'] ?? ($itemsTotal + $shipFee));
    $logo = ap_invoice_logo();
    header('Content-Type: text/html; charset=utf-8');
    ?><!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>فاکتور <?php echo $h($num); ?> — <?php echo $h($S['name']); ?></title>
<link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
<style>
@page{size:A5 portrait;margin:7mm}
*{box-sizing:border-box}
html,body{background:#e9e9e9}
body{margin:0;font-family:Vazirmatn,Tahoma,sans-serif;color:#000;font-size:9.6pt;line-height:1.65}
.sheet{width:148mm;min-height:210mm;margin:12px auto;background:#fff;padding:7mm;box-shadow:0 4px 18px rgba(0,0,0,.18)}
.bar{width:148mm;margin:12px auto 0;display:flex;gap:8px}
.bar button{flex:1;font:inherit;font-weight:700;font-size:10.5pt;padding:9px;border:1.5px solid #000;border-radius:8px;background:#000;color:#fff;cursor:pointer}
.bar button.alt{background:#fff;color:#000}
/* سربرگ */
.head{display:flex;align-items:center;gap:10px;padding-bottom:3mm;border-bottom:2px solid #000}
.head img{width:15mm;height:15mm;border-radius:3.5mm;object-fit:cover;filter:grayscale(1)}
.brand{flex:1;min-width:0}
.brand b{display:block;font-size:14pt;font-weight:900;line-height:1.3}
.brand span{display:block;font-size:8.4pt}
.brand .en{font-size:8pt;letter-spacing:.6px;font-weight:700;text-align:right}
.doc{border:1.5px solid #000;border-radius:2.5mm;padding:1.6mm 3mm;text-align:center;min-width:38mm}
.doc .ttl{font-size:11.5pt;font-weight:900;border-bottom:1px solid #000;padding-bottom:1mm;margin-bottom:1mm}
.doc div{font-size:8.4pt;white-space:nowrap}
.doc b{font-weight:800}
/* فرستنده/گیرنده */
.parties{display:grid;grid-template-columns:1fr 1fr;gap:2.5mm;margin:3mm 0}
.box{border:1px solid #000;border-radius:2.5mm;padding:2mm 2.6mm}
.box h3{margin:-2mm -2.6mm 1.6mm;padding:1mm 2.6mm;font-size:9pt;font-weight:900;background:#000;color:#fff;border-radius:2.2mm 2.2mm 0 0;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.box p{margin:0;font-size:8.6pt}
.box p+p{margin-top:.6mm}
.box .k{font-weight:700}
.ltr{direction:ltr;unicode-bidi:embed;display:inline-block}
/* جدول */
table{width:100%;border-collapse:collapse;font-size:8.6pt}
thead th{border:1px solid #000;border-bottom:2px solid #000;padding:1.4mm 1mm;font-weight:900;background:#ececec;-webkit-print-color-adjust:exact;print-color-adjust:exact}
tbody td{border:1px solid #000;padding:1.6mm 1.4mm;vertical-align:top}
td.n{text-align:center;width:9mm}
td.p{text-align:left;white-space:nowrap;width:23mm;direction:ltr}
td.d .t{font-weight:800;font-size:9.2pt}
td.d .en{font-size:7.6pt;text-align:left}
td.d .m{font-size:7.8pt;margin-top:.6mm}
td.d .sep{color:#000;opacity:.5}
tr{page-break-inside:avoid}
/* جمع */
.sum{display:grid;grid-template-columns:1fr 62mm;gap:2.5mm;margin-top:2.5mm;align-items:start}
.info p{margin:0 0 .8mm;font-size:8.6pt}
.tot{border:1px solid #000;border-radius:2.5mm;overflow:hidden}
.tot div{display:flex;justify-content:space-between;padding:1.3mm 2.6mm;font-size:8.8pt;border-bottom:1px dashed #000}
.tot div:last-child{border-bottom:0}
.tot .g{font-size:10.8pt;font-weight:900;background:#000;color:#fff;-webkit-print-color-adjust:exact;print-color-adjust:exact}
.words{margin-top:1.4mm;font-size:8pt}
/* امضا و پانویس */
.sign{display:grid;grid-template-columns:1fr 1fr;gap:2.5mm;margin-top:4mm}
.sign div{border:1px dashed #000;border-radius:2.5mm;height:17mm;padding:1.2mm 2.4mm;font-size:8pt;font-weight:700}
.foot{margin-top:3mm;padding-top:2mm;border-top:1px solid #000;font-size:7.8pt;text-align:center}
@media print{
  html,body{background:#fff}
  .bar{display:none}
  .sheet{margin:0;box-shadow:none;width:auto;min-height:0;padding:0}
}
@media screen and (max-width:600px){.sheet,.bar{width:auto;margin:8px}.sheet{padding:4mm}}
</style>
</head>
<body>
<div class="bar"><button onclick="window.print()">🖨 چاپ / ذخیره‌ی PDF</button><button class="alt" onclick="history.length>1?history.back():window.close()">بستن</button></div>
<div class="sheet">
  <div class="head">
    <?php if ($logo): ?><img src="<?php echo $logo; ?>" alt=""><?php endif; ?>
    <div class="brand"><b><?php echo $h($S['name']); ?></b><span class="en" dir="ltr"><?php echo $h($S['name_en']); ?></span><span><?php echo $h($S['tagline']); ?></span></div>
    <div class="doc"><div class="ttl">فاکتور فروش</div>
      <div>شماره: <b class="ltr"><?php echo $h($num); ?></b></div>
      <div>تاریخ: <b><?php echo $date; ?></b> — ساعت <b><?php echo $time; ?></b></div></div>
  </div>

  <div class="parties">
    <div class="box"><h3>فرستنده</h3>
      <p><span class="k"><?php echo $h($S['name']); ?></span></p>
      <?php if ($S['address'] !== ''): ?><p><span class="k">آدرس: </span><?php echo $h($S['address']); ?></p><?php endif; ?>
      <p><span class="k">تلفن: </span><span class="ltr"><?php echo $h($fa($S['phone'])); ?></span></p>
      <p><span class="k">وب‌سایت: </span><span class="ltr"><?php echo $h($S['website']); ?></span></p>
      <?php if ($S['instagram'] !== ''): ?><p><span class="k">اینستاگرام: </span><span class="ltr"><?php echo $h($S['instagram']); ?></span></p><?php endif; ?>
    </div>
    <div class="box"><h3>گیرنده</h3>
      <p><span class="k"><?php echo $h($recvName); ?></span></p>
      <p><span class="k">تلفن: </span><span class="ltr"><?php echo $h($fa($recvPhone)); ?></span></p>
      <?php if ($region !== ''): ?><p><span class="k">استان/شهر: </span><?php echo $h($region); ?></p><?php endif; ?>
      <?php if ($addrLine !== ''): ?><p><span class="k">آدرس: </span><?php echo $h($addrLine); ?></p><?php endif; ?>
      <?php if ($postal !== ''): ?><p><span class="k">کد پستی: </span><span class="ltr"><?php echo $h($fa($postal)); ?></span></p><?php endif; ?>
    </div>
  </div>

  <table>
    <thead><tr><th>ردیف</th><th>شرح کالا</th><th>تعداد</th><th>فی (تومان)</th><th>مبلغ (تومان)</th></tr></thead>
    <tbody><?php echo $rows; ?></tbody>
  </table>

  <div class="sum">
    <div class="info">
      <p><span class="k"><b>روش ارسال:</b></span> <?php echo $h($shipLabels[$ship] ?? $ship); ?></p>
      <p><b>پرداخت:</b> <?php echo !empty($order['receiptUrl']) ? 'کارت به کارت (رسید ثبت شده)' : 'آنلاین / کارت به کارت'; ?></p>
      <p><b>وضعیت سفارش:</b> <?php echo $h($statusLabels[$status] ?? $status); ?></p>
      <p><b>تعداد اقلام:</b> <?php echo $fa(count((array) ($order['items'] ?? []))); ?> قلم (<?php echo $fa($count); ?> عدد)</p>
    </div>
    <div>
      <div class="tot">
        <div><span>جمع کالاها</span><span><?php echo $money($itemsTotal); ?> تومان</span></div>
        <div><span>هزینه ارسال</span><span><?php echo $isCod ? 'پس‌کرایه' : ($shipFee > 0 ? $money($shipFee) . ' تومان' : 'رایگان'); ?></span></div>
        <div class="g"><span>مبلغ قابل پرداخت</span><span><?php echo $money($grand); ?> تومان</span></div>
      </div>
      <?php if ($isCod): ?><div class="words">* هزینه‌ی ارسال هنگامِ تحویل به باربری/پیک پرداخت می‌شه.</div><?php endif; ?>
    </div>
  </div>

  <div class="sign"><div>مهر و امضای فروشنده</div><div>امضای تحویل‌گیرنده</div></div>

  <div class="foot">از خریدتون ممنونیم — برای پیگیری، شماره‌ی فاکتور رو نگه دارید. پشتیبانی: <span class="ltr"><?php echo $h($fa($S['phone'])); ?></span> — <span class="ltr"><?php echo $h($S['website']); ?></span></div>
</div>
</body>
</html><?php
}
