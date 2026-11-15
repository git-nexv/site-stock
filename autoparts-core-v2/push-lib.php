<?php
/**
 * اعلان‌های وب (Web Push) بدونِ هیچ کتابخونه یا سرویسِ بیرونی.
 *
 * - هم api.php (بدونِ وردپرس) و هم functions.php این فایل رو بارگذاری می‌کنن.
 * - مشترک‌ها (مشتری‌ها و دستگاه‌های مدیر) در ap-data/push/state.json نگه داشته می‌شن.
 * - متنِ اعلان طبقِ RFC 8291 (aes128gcm) رمزنگاری و همراهِ خودِ اعلان فرستاده می‌شه؛
 *   پس سرویس‌ورکر برای نشون‌دادنش به هیچ درخواستِ دیگه‌ای نیاز نداره.
 * - اگه رمزنگاری روی هاست ممکن نباشه، اعلانِ بدونِ محتوا فرستاده می‌شه و سرویس‌ورکر
 *   آخرین پیام رو از سایت می‌گیره (push_pull).
 */

if (!function_exists('ap_pl_b64u')) {

function ap_pl_b64u($bin) { return rtrim(strtr(base64_encode($bin), '+/', '-_'), '='); }
function ap_pl_b64u_dec($s) { $s = strtr((string) $s, '-_', '+/'); return (string) base64_decode($s . str_repeat('=', (4 - strlen($s) % 4) % 4)); }

/* ---------- ذخیره‌سازیِ مشترک‌ها ---------- */
function ap_pl_dir($dataDir) {
    $dir = rtrim($dataDir, '/') . '/push';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    if (!file_exists($dir . '/.htaccess')) {
        @file_put_contents($dir . '/.htaccess', "Require all denied\n<IfModule !mod_authz_core.c>\n  Order allow,deny\n  Deny from all\n</IfModule>\n");
    }
    return $dir;
}
function ap_pl_empty_state() { return ['subs' => [], 'msgs' => [], 'next' => 1]; }
function ap_pl_read($dataDir) {
    $f = ap_pl_dir($dataDir) . '/state.json';
    $d = file_exists($f) ? json_decode((string) @file_get_contents($f), true) : null;
    if (!is_array($d)) $d = ap_pl_empty_state();
    if (!isset($d['subs']) || !is_array($d['subs'])) $d['subs'] = [];
    if (!isset($d['msgs']) || !is_array($d['msgs'])) $d['msgs'] = [];
    if (empty($d['next'])) $d['next'] = 1;
    return $d;
}
/* خواندن/تغییر/نوشتن با قفل (چند درخواستِ هم‌زمان همدیگه رو خراب نمی‌کنن) */
function ap_pl_update($dataDir, callable $fn) {
    $dir = ap_pl_dir($dataDir);
    $lock = @fopen($dir . '/state.lock', 'c');
    if ($lock) @flock($lock, LOCK_EX);
    $state = ap_pl_read($dataDir);
    $ret = $fn($state);
    $tmp = $dir . '/state.json.tmp';
    if (@file_put_contents($tmp, json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !== false) @rename($tmp, $dir . '/state.json');
    if ($lock) { @flock($lock, LOCK_UN); @fclose($lock); }
    return $ret;
}
function ap_pl_sub_id($endpoint) { return substr(hash('sha256', (string) $endpoint), 0, 24); }

/* ثبت/به‌روزرسانیِ یک دستگاه. نقشِ «مدیر» یک‌بار داده می‌شه و خودکار پس گرفته نمی‌شه
   (تا اگه توکنِ مدیر منقضی شد، اعلانِ سفارش روی گوشیِ مدیر قطع نشه). */
function ap_pl_subscribe($dataDir, $sub, $isAdmin, $ua = '', $userId = null) {
    $ep = (string) ($sub['endpoint'] ?? '');
    $p256 = (string) ($sub['keys']['p256dh'] ?? '');
    $auth = (string) ($sub['keys']['auth'] ?? '');
    if (!preg_match('#^https://[a-z0-9.\-]+(:\d+)?/#i', $ep) || strlen($ep) > 1000) return null;
    if ($p256 !== '' && strlen(ap_pl_b64u_dec($p256)) !== 65) $p256 = '';
    if ($auth !== '' && strlen(ap_pl_b64u_dec($auth)) !== 16) $auth = '';
    $id = ap_pl_sub_id($ep);
    return ap_pl_update($dataDir, function (&$st) use ($id, $ep, $p256, $auth, $isAdmin, $ua, $userId) {
        $old = $st['subs'][$id] ?? [];
        $st['subs'][$id] = [
            'e' => $ep, 'k' => $p256, 'a' => $auth,
            'r' => ($isAdmin || ($old['r'] ?? '') === 'admin') ? 'admin' : 'customer',
            'u' => $userId ?: ($old['u'] ?? null),
            'ua' => substr(preg_replace('/[^\x20-\x7e]/', '', (string) $ua), 0, 160),
            'c' => $old['c'] ?? time(), 't' => time(),
        ];
        return $st['subs'][$id];
    });
}
function ap_pl_unsubscribe($dataDir, $endpoint) {
    $id = ap_pl_sub_id($endpoint);
    return ap_pl_update($dataDir, function (&$st) use ($id) { $had = isset($st['subs'][$id]); unset($st['subs'][$id]); return $had; });
}
function ap_pl_counts($dataDir) {
    $st = ap_pl_read($dataDir); $c = ['customer' => 0, 'admin' => 0, 'total' => 0];
    foreach ($st['subs'] as $s) { $c[($s['r'] ?? '') === 'admin' ? 'admin' : 'customer']++; $c['total']++; }
    return $c;
}
function ap_pl_target_match($to, $role) {
    if ($to === 'all') return true;
    if ($to === 'admins') return $role === 'admin';
    if ($to === 'customers') return $role !== 'admin';
    return false;
}

/* ---------- امضای VAPID (ES256) ---------- */
function ap_pl_der_to_raw($der) {
    $pos = 2; if (ord($der[1]) & 0x80) $pos += ord($der[1]) & 0x7f;
    $out = '';
    for ($i = 0; $i < 2; $i++) {
        $pos++; $len = ord($der[$pos]); $pos++;
        $int = substr($der, $pos, $len); $pos += $len;
        $out .= str_pad(ltrim($int, "\0"), 32, "\0", STR_PAD_LEFT);
    }
    return $out;
}
function ap_pl_jwt($vapid, $endpoint, $subject) {
    $p = parse_url($endpoint);
    $aud = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
    $data = ap_pl_b64u(json_encode(['typ' => 'JWT', 'alg' => 'ES256'])) . '.'
          . ap_pl_b64u(json_encode(['aud' => $aud, 'exp' => time() + 12 * 3600, 'sub' => $subject], JSON_UNESCAPED_SLASHES));
    if (!openssl_sign($data, $der, $vapid['private'], OPENSSL_ALGO_SHA256)) return null;
    return $data . '.' . ap_pl_b64u(ap_pl_der_to_raw($der));
}

/* ---------- رمزنگاریِ محتوای اعلان (RFC 8291 / RFC 8188, aes128gcm) ---------- */
function ap_pl_can_encrypt() {
    return function_exists('openssl_pkey_derive') && function_exists('hash_hkdf') && in_array('aes-128-gcm', openssl_get_cipher_methods(), true);
}
function ap_pl_raw_to_pem($raw65) {
    $der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . $raw65;
    return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
}
function ap_pl_encrypt($plaintext, $uaPublicB64u, $authB64u) {
    $uaPub = ap_pl_b64u_dec($uaPublicB64u); $auth = ap_pl_b64u_dec($authB64u);
    if (strlen($uaPub) !== 65 || strlen($auth) !== 16) return null;
    $eph = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
    if (!$eph) return null;
    $d = openssl_pkey_get_details($eph);
    $asPub = "\x04" . str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT) . str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT);
    $peer = openssl_pkey_get_public(ap_pl_raw_to_pem($uaPub));
    if (!$peer) return null;
    $secret = openssl_pkey_derive($peer, $eph, 32);
    if ($secret === false || strlen($secret) !== 32) return null;
    $ikm = hash_hkdf('sha256', $secret, 32, "WebPush: info\0" . $uaPub . $asPub, $auth);
    $salt = random_bytes(16);
    $cek = hash_hkdf('sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt);
    $nonce = hash_hkdf('sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt);
    $tag = '';
    $ct = openssl_encrypt($plaintext . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);
    if ($ct === false) return null;
    return $salt . pack('N', 4096) . chr(65) . $asPub . $ct . $tag;
}

/* ---------- ارسال (موازی با curl_multi؛ اگه curl نبود، یکی‌یکی) ---------- */
function ap_pl_build_request($vapid, $sub, $payload, $subject, $urgency = 'normal') {
    $jwt = ap_pl_jwt($vapid, $sub['e'], $subject);
    if (!$jwt) return null;
    $headers = ['TTL: 86400', 'Urgency: ' . $urgency, 'Authorization: vapid t=' . $jwt . ', k=' . $vapid['public']];
    $body = '';
    if ($payload !== null && !empty($sub['k']) && !empty($sub['a']) && ap_pl_can_encrypt()) {
        $enc = ap_pl_encrypt($payload, $sub['k'], $sub['a']);
        if ($enc !== null) { $body = $enc; $headers[] = 'Content-Encoding: aes128gcm'; $headers[] = 'Content-Type: application/octet-stream'; }
    }
    $headers[] = 'Content-Length: ' . strlen($body);
    return ['url' => $sub['e'], 'headers' => $headers, 'body' => $body];
}
function ap_pl_http_many(array $reqs, $concurrency = 24) {
    $out = [];
    if (!$reqs) return $out;
    if (function_exists('curl_multi_init')) {
        foreach (array_chunk($reqs, $concurrency, true) as $chunk) {
            $mh = curl_multi_init(); $hs = [];
            foreach ($chunk as $k => $r) {
                $ch = curl_init($r['url']);
                curl_setopt_array($ch, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $r['body'], CURLOPT_HTTPHEADER => $r['headers'],
                    CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 8]);
                curl_multi_add_handle($mh, $ch); $hs[$k] = $ch;
            }
            do { $st = curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 1.0); } while ($running && $st === CURLM_OK);
            foreach ($hs as $k => $ch) {
                $out[$k] = ['code' => (int) curl_getinfo($ch, CURLINFO_HTTP_CODE), 'err' => curl_error($ch), 'body' => substr((string) curl_multi_getcontent($ch), 0, 160)];
                curl_multi_remove_handle($mh, $ch); curl_close($ch);
            }
            curl_multi_close($mh);
        }
        return $out;
    }
    foreach ($reqs as $k => $r) {
        $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => implode("\r\n", $r['headers']), 'content' => $r['body'], 'timeout' => 15, 'ignore_errors' => true]]);
        $body = @file_get_contents($r['url'], false, $ctx);
        $code = 0;
        foreach ((array) ($http_response_header ?? []) as $h) { if (preg_match('#^HTTP/\S+\s+(\d+)#', $h, $m)) $code = (int) $m[1]; }
        $out[$k] = ['code' => $code, 'err' => $body === false ? 'connection failed' : '', 'body' => substr((string) $body, 0, 160)];
    }
    return $out;
}

/* یک پیام می‌سازه، در صفِ پیام‌ها نگه می‌داره (برای حالتِ بدونِ رمزنگاری) و برای
   همه‌ی دستگاه‌های مقصد می‌فرسته. $to: all | customers | admins */
function ap_pl_send($dataDir, $vapid, $to, array $msg, $subject = '') {
    if ($subject === '') $subject = 'mailto:admin@' . (function_exists('home_url') ? (parse_url(home_url(), PHP_URL_HOST) ?: 'localhost') : 'localhost');
    $msg = [
        'title' => mb_substr(trim((string) ($msg['title'] ?? '')), 0, 80, 'UTF-8') ?: (function_exists('get_bloginfo') ? get_bloginfo('name') : ''),
        'body'  => mb_substr(trim((string) ($msg['body'] ?? '')), 0, 600, 'UTF-8'),
        'url'   => (string) ($msg['url'] ?? ''),
        'tag'   => (string) ($msg['tag'] ?? ('ap-' . time())),
    ];
    $st = ap_pl_update($dataDir, function (&$st) use (&$msg, $to) {
        $msg['id'] = (int) $st['next']; $st['next']++;
        $msg['to'] = $to; $msg['at'] = time();
        $st['msgs'][] = $msg;
        $st['msgs'] = array_slice(array_values(array_filter($st['msgs'], function ($m) { return ($m['at'] ?? 0) > time() - 7 * 86400; })), -40);
        return $st;
    });
    $payload = json_encode(['id' => $msg['id'], 'title' => $msg['title'], 'body' => $msg['body'], 'url' => $msg['url'], 'tag' => $msg['tag']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $reqs = [];
    foreach ($st['subs'] as $id => $s) {
        if (!ap_pl_target_match($to, $s['r'] ?? 'customer')) continue;
        $r = ap_pl_build_request($vapid, $s, $payload, $subject, $to === 'admins' ? 'high' : 'normal');
        if ($r) $reqs[$id] = $r;
    }
    $res = ap_pl_http_many($reqs);
    $sent = 0; $gone = []; $errors = [];
    foreach ($res as $id => $r) {
        if ($r['code'] >= 200 && $r['code'] < 300) { $sent++; continue; }
        if (in_array($r['code'], [404, 410], true)) { $gone[] = $id; continue; }   // دستگاه دیگه این اشتراک رو نداره
        $host = parse_url($reqs[$id]['url'], PHP_URL_HOST) ?: '?';
        $errors[$host . ': ' . ($r['code'] ? 'HTTP ' . $r['code'] . ' ' . $r['body'] : $r['err'])] = true;
    }
    $result = ['sent' => $sent, 'total' => count($reqs), 'removed' => count($gone), 'errors' => array_slice(array_keys($errors), 0, 5), 'at' => time(), 'id' => $msg['id']];
    ap_pl_update($dataDir, function (&$st) use ($gone, $result, $to) {
        foreach ($gone as $id) unset($st['subs'][$id]);
        $st['last'][$to === 'admins' ? 'admins' : 'broadcast'] = $result;
    });
    return $result;
}

/* حالتِ بدونِ رمزنگاری: سرویس‌ورکر آخرین پیامِ مربوط به همین دستگاه رو می‌گیره (فقط خواندن) */
function ap_pl_pull($dataDir, $endpoint) {
    $st = ap_pl_read($dataDir);
    $sub = $st['subs'][ap_pl_sub_id($endpoint)] ?? null;
    if (!$sub) return null;
    for ($i = count($st['msgs']) - 1; $i >= 0; $i--) {
        $m = $st['msgs'][$i];
        if (($m['at'] ?? 0) > time() - 86400 && ap_pl_target_match($m['to'] ?? '', $sub['r'] ?? 'customer')) {
            return ['id' => $m['id'], 'title' => $m['title'], 'body' => $m['body'], 'url' => $m['url'], 'tag' => $m['tag']];
        }
    }
    return null;
}

/* ---------- سرویس‌ورکر ---------- */
function ap_pl_sw_js() {
    return <<<'JS'
/* service worker — فقط برای اعلان‌ها؛ هیچ درخواستی از سایت رو کش یا دست‌کاری نمی‌کنه */
const SELF = new URL(self.location.href);
const THEME = SELF.pathname.indexOf('/wp-content/') !== -1 ? SELF.origin + SELF.pathname.replace(/[^\/]*$/, '') : '';
const HOME = SELF.origin + (SELF.pathname.indexOf('/wp-content/') !== -1 ? SELF.pathname.replace(/wp-content\/.*$/, '') : SELF.pathname.replace(/[^\/]*$/, ''));
const API = THEME ? THEME + 'api.php' : (SELF.searchParams.get('api') || '');
const ICON = THEME ? THEME + 'icons/icon-192.png' : (SELF.searchParams.get('icon') || '');
const BADGE = THEME ? THEME + 'icons/badge-96.png' : (SELF.searchParams.get('badge') || ICON);
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', e => e.waitUntil(self.clients.claim()));
function show(m){
  m = m || {};
  const url = m.url ? new URL(m.url, HOME).href : HOME;
  return self.registration.showNotification(m.title || self.location.hostname, {
    body: m.body || '', icon: ICON || undefined, badge: BADGE || undefined, tag: m.tag || ('ap-' + Date.now()), renotify: true,
    dir: 'rtl', lang: 'fa', data: { url }
  });
}
self.addEventListener('push', e => {
  e.waitUntil((async () => {
    let m = null;
    try{ if(e.data){ m = e.data.json(); } }catch(_){ try{ m = { title: self.location.hostname, body: e.data.text() }; }catch(__){} }
    if(!m && API){
      try{
        const sub = await self.registration.pushManager.getSubscription();
        const r = await fetch(API + '?action=push_pull', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ endpoint: sub ? sub.endpoint : '' }), cache: 'no-store' });
        const d = await r.json();
        if(d && d.message) m = d.message;
      }catch(_){}
    }
    await show(m || { title: self.location.hostname, body: 'یک اعلان جدید دارید.' });
  })());
});
self.addEventListener('notificationclick', e => {
  e.notification.close();
  const url = (e.notification.data && e.notification.data.url) || HOME;
  e.waitUntil((async () => {
    const list = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    for(const c of list){
      if(c.url && c.url.indexOf(SELF.origin) === 0 && 'focus' in c){
        try{ await c.focus(); if('navigate' in c) await c.navigate(url); return; }catch(_){}
      }
    }
    return self.clients.openWindow(url);
  })());
});
self.addEventListener('pushsubscriptionchange', e => {
  e.waitUntil((async () => {
    try{
      const old = e.oldSubscription;
      const opts = old && old.options ? old.options : null;
      if(!opts || !API) return;
      const sub = await self.registration.pushManager.subscribe(opts);
      await fetch(API + '?action=push_subscribe', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ subscription: sub.toJSON(), old: old ? old.endpoint : '' }) });
    }catch(_){}
  })());
});
JS;
}
function ap_pl_serve_sw() {
    while (ob_get_level() > 0) @ob_end_clean();
    header('Content-Type: application/javascript; charset=utf-8', true);
    header('Service-Worker-Allowed: /');
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('X-LiteSpeed-Cache-Control: no-cache');
    header('X-Content-Type-Options: nosniff');
    echo ap_pl_sw_js();
    exit;
}

}
