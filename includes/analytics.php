<?php
/**
 * Privacy-friendly visitor statistics.
 *  - No raw IP address or cookie is stored. A visitor is a keyed hash of IP + browser.
 *  - Country comes from the CDN header when there is one, otherwise a one-off lookup that is cached.
 *  - Staff, bots and error pages are not counted.
 */

const VISIT_RETENTION_DAYS = 400;

function visit_country_names(): array
{
    static $m = null;
    if ($m === null) {
        $m = [];
        $list = 'AF=Afghanistan|AL=Albania|DZ=Algeria|AD=Andorra|AO=Angola|AR=Argentina|AM=Armenia|AU=Australia|AT=Austria|AZ=Azerbaijan|BS=Bahamas|BH=Bahrain|BD=Bangladesh|BB=Barbados|BY=Belarus|BE=Belgium|BZ=Belize|BJ=Benin|BT=Bhutan|BO=Bolivia|BA=Bosnia and Herzegovina|BW=Botswana|BR=Brazil|BN=Brunei|BG=Bulgaria|BF=Burkina Faso|BI=Burundi|KH=Cambodia|CM=Cameroon|CA=Canada|CV=Cape Verde|CF=Central African Republic|TD=Chad|CL=Chile|CN=China|CO=Colombia|KM=Comoros|CG=Congo|CD=DR Congo|CR=Costa Rica|CI=Cote d\'Ivoire|HR=Croatia|CU=Cuba|CY=Cyprus|CZ=Czechia|DK=Denmark|DJ=Djibouti|DO=Dominican Republic|EC=Ecuador|EG=Egypt|SV=El Salvador|GQ=Equatorial Guinea|ER=Eritrea|EE=Estonia|SZ=Eswatini|ET=Ethiopia|FJ=Fiji|FI=Finland|FR=France|GA=Gabon|GM=Gambia|GE=Georgia|DE=Germany|GH=Ghana|GR=Greece|GT=Guatemala|GN=Guinea|GW=Guinea-Bissau|GY=Guyana|HT=Haiti|HN=Honduras|HK=Hong Kong|HU=Hungary|IS=Iceland|IN=India|ID=Indonesia|IR=Iran|IQ=Iraq|IE=Ireland|IL=Israel|IT=Italy|JM=Jamaica|JP=Japan|JO=Jordan|KZ=Kazakhstan|KE=Kenya|KW=Kuwait|KG=Kyrgyzstan|LA=Laos|LV=Latvia|LB=Lebanon|LS=Lesotho|LR=Liberia|LY=Libya|LT=Lithuania|LU=Luxembourg|MG=Madagascar|MW=Malawi|MY=Malaysia|MV=Maldives|ML=Mali|MT=Malta|MR=Mauritania|MU=Mauritius|MX=Mexico|MD=Moldova|MN=Mongolia|ME=Montenegro|MA=Morocco|MZ=Mozambique|MM=Myanmar|NA=Namibia|NP=Nepal|NL=Netherlands|NZ=New Zealand|NI=Nicaragua|NE=Niger|NG=Nigeria|MK=North Macedonia|NO=Norway|OM=Oman|PK=Pakistan|PS=Palestine|PA=Panama|PG=Papua New Guinea|PY=Paraguay|PE=Peru|PH=Philippines|PL=Poland|PT=Portugal|QA=Qatar|RO=Romania|RU=Russia|RW=Rwanda|SA=Saudi Arabia|SN=Senegal|RS=Serbia|SC=Seychelles|SL=Sierra Leone|SG=Singapore|SK=Slovakia|SI=Slovenia|SO=Somalia|ZA=South Africa|KR=South Korea|SS=South Sudan|ES=Spain|LK=Sri Lanka|SD=Sudan|SR=Suriname|SE=Sweden|CH=Switzerland|SY=Syria|TW=Taiwan|TJ=Tajikistan|TZ=Tanzania|TH=Thailand|TL=Timor-Leste|TG=Togo|TT=Trinidad and Tobago|TN=Tunisia|TR=Turkey|TM=Turkmenistan|UG=Uganda|UA=Ukraine|AE=United Arab Emirates|GB=United Kingdom|US=United States|UY=Uruguay|UZ=Uzbekistan|VE=Venezuela|VN=Vietnam|YE=Yemen|ZM=Zambia|ZW=Zimbabwe';
        foreach (explode('|', $list) as $pair) {
            [$c, $n] = explode('=', $pair, 2);
            $m[$c] = $n;
        }
    }
    return $m;
}

function visit_country_name(?string $code): string
{
    if (!$code) {
        return 'Unknown';
    }
    return visit_country_names()[$code] ?? $code;
}

/** Flag emoji from a two-letter country code. */
function visit_flag(?string $code): string
{
    if (!$code || !preg_match('/^[A-Z]{2}$/', $code)) {
        return "\u{1F310}";
    }
    return mb_chr(0x1F1E6 + ord($code[0]) - 65) . mb_chr(0x1F1E6 + ord($code[1]) - 65);
}

function visit_client_ip(): string
{
    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
}

function visit_is_public_ip(string $ip): bool
{
    return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
}

function visit_device(string $ua): string
{
    if (preg_match('/ipad|tablet|kindle|silk|playbook/i', $ua) || (preg_match('/android/i', $ua) && !preg_match('/mobile/i', $ua))) {
        return 'tablet';
    }
    return preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|windows phone/i', $ua) ? 'mobile' : 'desktop';
}

/** Records one page view. Never throws: statistics must not break a page. */
function track_visit(string $title): void
{
    try {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET' || http_response_code() >= 400) {
            return;
        }
        $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if ($ua === '' || preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|uptime|curl|wget|python|java\/|headless|lighthouse|pingdom|scanner|httpclient|go-http|axios|php\//i', $ua)) {
            return;
        }
        if (isset($_SERVER['HTTP_PURPOSE']) || (($_SERVER['HTTP_SEC_PURPOSE'] ?? '') !== '') || ($_SERVER['HTTP_DNT'] ?? '') === '1') {
            return;
        }
        if (is_staff()) {
            return;
        }
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($script === 'index.php') {
            $page = '/';
        } else {
            $page = '/' . $script;
            foreach (['slug', 'id'] as $k) {
                if (!empty($_GET[$k]) && is_scalar($_GET[$k])) {
                    $page .= '?' . $k . '=' . substr(preg_replace('/[^A-Za-z0-9_\-]/', '', (string) $_GET[$k]), 0, 80);
                    break;
                }
            }
        }
        $ip = visit_client_ip();
        $key = twofa_key();
        $visitor = substr(hash_hmac('sha256', $ip . '|' . $ua, $key), 0, 16);
        $ipHash = substr(hash_hmac('sha256', $ip, $key), 0, 32);

        $country = strtoupper((string) ($_SERVER['HTTP_CF_IPCOUNTRY'] ?? ''));
        $needLookup = false;
        if (!preg_match('/^[A-Z]{2}$/', $country) || $country === 'XX' || $country === 'T1') {
            $country = null;
            $row = q('SELECT country FROM ip_country_cache WHERE ip_hash = ? AND checked_at > DATE_SUB(NOW(), INTERVAL 30 DAY)', [$ipHash])->fetch();
            if ($row) {
                $country = $row['country'];
            } elseif (visit_is_public_ip($ip)) {
                $needLookup = true;
            }
        }
        $ref = null;
        $rh = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_HOST);
        if ($rh && strcasecmp($rh, parse_url(site_origin(), PHP_URL_HOST) ?: '') !== 0) {
            $ref = mb_substr(preg_replace('/^www\./i', '', strtolower($rh)), 0, 120);
        }
        q('INSERT INTO site_visits (visited_at, visitor, page, title, country, device, referrer) VALUES (NOW(), ?,?,?,?,?,?)',
            [$visitor, $page, mb_substr($title, 0, 160), $country, visit_device($ua), $ref]);
        $id = (int) db()->lastInsertId();

        if (random_int(1, 200) === 1) {
            q('DELETE FROM site_visits WHERE visited_at < DATE_SUB(NOW(), INTERVAL ' . VISIT_RETENTION_DAYS . ' DAY)');
            q('DELETE FROM ip_country_cache WHERE checked_at < DATE_SUB(NOW(), INTERVAL 90 DAY)');
        }
        if ($needLookup) {
            // After the page has been sent, so the visitor never waits for the lookup.
            register_shutdown_function(function () use ($ip, $ipHash, $id) {
                try {
                    if (function_exists('fastcgi_finish_request')) {
                        fastcgi_finish_request();
                    } elseif (function_exists('litespeed_finish_request')) {
                        litespeed_finish_request();
                    }
                    $ctx = stream_context_create(['http' => ['timeout' => 3, 'ignore_errors' => true, 'header' => "User-Agent: guco-site\r\n"]]);
                    $json = @file_get_contents('https://ipwho.is/' . rawurlencode($ip) . '?fields=success,country_code', false, $ctx);
                    $d = $json ? json_decode($json, true) : null;
                    $cc = is_array($d) && !empty($d['success']) && preg_match('/^[A-Z]{2}$/', (string) ($d['country_code'] ?? '')) ? $d['country_code'] : null;
                    if ($d !== null) {
                        q('REPLACE INTO ip_country_cache (ip_hash, country, checked_at) VALUES (?,?,NOW())', [$ipHash, $cc]);
                        if ($cc) {
                            q('UPDATE site_visits SET country = ? WHERE id = ?', [$cc, $id]);
                        }
                    }
                } catch (Throwable $e) {
                }
            });
        }
    } catch (Throwable $e) {
        // Table missing before the migration is applied, or a database hiccup: ignore.
    }
}
