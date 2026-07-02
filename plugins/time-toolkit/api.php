<?php
/**
 * Time Toolkit — REST API
 * Route: /plugins/time-toolkit/api?action=...
 */
defined('AWAN') or require_once __DIR__ . '/../../_bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Vary: Accept-Encoding');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }

$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? 'help'));

function tt_json($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function tt_error(string $msg, int $code = 400): void {
    tt_json(['error' => $msg, 'status' => $code, 'hint' => 'See ?action=help for usage'], $code);
}

function tt_get(string $key, string $default = ''): string {
    return trim($_GET[$key] ?? $_POST[$key] ?? $default);
}

/* ── Parse a flexible date/time string into a UTC DateTime ── */
function tt_parse(string $q): ?array {
    $q = trim($q);
    if ($q === '') return null;

    // 10-digit unix timestamp
    if (preg_match('/^\d{10}$/', $q)) {
        $d = new DateTime('@' . $q); $d->setTimezone(new DateTimeZone('UTC'));
        return ['type' => 'unix', 'date' => $d, 'ts' => (int)$q];
    }
    // 13-digit unix ms
    if (preg_match('/^\d{13}$/', $q)) {
        $ts = intdiv((int)$q, 1000);
        $d = new DateTime('@' . $ts); $d->setTimezone(new DateTimeZone('UTC'));
        return ['type' => 'unix_ms', 'date' => $d, 'ts' => $ts];
    }
    // ISO 8601 / any PHP-parseable
    try {
        $d = new DateTime($q, new DateTimeZone('UTC'));
        return ['type' => 'datetime', 'date' => $d, 'ts' => $d->getTimestamp()];
    } catch (Exception) {}
    return null;
}

/* ─────────────────────────────────────────────────────────────────────────────
   CALENDAR MATH HELPERS
───────────────────────────────────────────────────────────────────────────── */

/** JDN (Julian Day Number) → Gregorian DateTime */
function tt_jdn_to_greg(int $jdn): DateTime {
    $l = $jdn + 68569; $n = intdiv(4 * $l, 146097);
    $l = $l - intdiv(146097 * $n + 3, 4);
    $i = intdiv(4000 * ($l + 1), 1461001);
    $l = $l - intdiv(1461 * $i, 4) + 31;
    $j = intdiv(80 * $l, 2447);
    $day   = $l - intdiv(2447 * $j, 80);
    $l     = intdiv($j, 11);
    $month = $j + 2 - 12 * $l;
    $year  = 100 * ($n - 49) + $i + $l;
    $d = new DateTime(); $d->setTimezone(new DateTimeZone('UTC'));
    $d->setDate($year, $month, $day)->setTime(0, 0, 0);
    return $d;
}

/** Gregorian y/m/d → JDN */
function tt_greg_to_jdn(int $y, int $m, int $d): int {
    $a = intdiv(14 - $m, 12); $y2 = $y + 4800 - $a; $m2 = $m + 12 * $a - 3;
    return $d + intdiv(153 * $m2 + 2, 5) + 365 * $y2 + intdiv($y2, 4) - intdiv($y2, 100) + intdiv($y2, 400) - 32045;
}

/** Gregorian → Hijri */
function tt_to_hijri(int $y, int $m, int $d): array {
    $jdn = tt_greg_to_jdn($y, $m, $d);
    $l   = $jdn - 1948440 + 10632;
    $n   = intdiv($l - 1, 10631);
    $l   = $l - 10631 * $n + 354;
    $j   = intdiv(10985 - $l, 5316) * intdiv(50 * $l, 17719) + intdiv($l, 5670) * intdiv(43 * $l, 15238);
    $l   = $l - intdiv(30 - $j, 15) * intdiv(17719 * $j, 50) - intdiv($j, 16) * intdiv(15238 * $j, 43) + 29;
    $hm  = intdiv(24 * $l, 709);
    $hd  = $l - intdiv(709 * $hm, 24);
    $hy  = 30 * $n + $j - 30;
    $months = ['','Muharram','Safar','Rabi al-Awwal','Rabi al-Thani','Jumada al-Awwal',
               'Jumada al-Thani','Rajab',"Sha'ban",'Ramadan','Shawwal',"Dhu al-Qi'dah",'Dhu al-Hijjah'];
    return [
        'year' => $hy, 'month' => $hm, 'day' => $hd,
        'month_name' => $months[$hm] ?? '', 'is_ramadan' => ($hm === 9),
        'formatted' => "$hd {$months[$hm]} $hy AH",
    ];
}

/** Hijri Y/M/D → JDN → Gregorian DateTime */
function tt_from_hijri(int $hy, int $hm, int $hd): DateTime {
    $jdn = $hd + (int)ceil(29.5001 * ($hm - 1)) + ($hy - 1) * 354
         + intdiv(3 + 11 * $hy, 30) + 1948440 - 385;
    return tt_jdn_to_greg($jdn);
}

/** Gregorian → Persian (Jalali) */
function tt_to_jalali(int $y, int $m, int $d): array {
    $gDim = [31,28,31,30,31,30,31,31,30,31,30,31];
    $jDim = [31,31,31,31,31,31,30,30,30,30,30,29];
    $gy=$y-1600; $gm=$m-1; $gd=$d-1;
    $gdn = 365*$gy+intdiv($gy+3,4)-intdiv($gy+99,100)+intdiv($gy+399,400);
    for ($i=0;$i<$gm;$i++) $gdn+=$gDim[$i];
    if ($gm>1&&(($gy%4===0&&$gy%100!==0)||$gy%400===0)) $gdn++;
    $gdn+=$gd; $jdn=$gdn-79;
    $jnp=intdiv($jdn,12053); $jdn=$jdn%12053;
    $jy=979+33*$jnp+4*intdiv($jdn,1461); $jdn=$jdn%1461;
    if ($jdn>=366) { $jy+=intdiv($jdn-1,365); $jdn=($jdn-1)%365; }
    for ($jm=0;$jm<11&&$jdn>=$jDim[$jm];$jm++) $jdn-=$jDim[$jm];
    $jd2=$jdn+1;
    $months=['','Farvardin','Ordibehesht','Khordad','Tir','Mordad','Shahrivar','Mehr','Aban','Azar','Dey','Bahman','Esfand'];
    return ['year'=>$jy,'month'=>$jm+1,'day'=>$jd2,'month_name'=>$months[$jm+1],'formatted'=>"$jd2 {$months[$jm+1]} $jy AP"];
}

/** Persian (Jalali) Y/M/D → Gregorian DateTime */
function tt_from_jalali(int $jy, int $jm, int $jd): DateTime {
    $jy+=1595;
    $days=-355779+365*$jy+intdiv($jy,33)*8+intdiv(($jy%33)+3,4)+$jd;
    if ($jm<=6) $days+=($jm-1)*31; else $days+=(($jm-7)*30)+186;
    $gy=400*intdiv($days,146097); $days=$days%146097;
    if ($days>36524) { $gy+=100*intdiv(--$days,36524); $days=$days%36524; if ($days>=365) $days++; }
    $gy+=4*intdiv($days,1461); $days=$days%1461;
    if ($days>364) { $gy+=intdiv($days-1,365); $days=($days-1)%365; }
    $sa=[0,31,59,90,120,151,181,212,243,273,304,334];
    for ($gm=0;$gm<11&&$days>=$sa[$gm+1];$gm++);
    $gd=$days-$sa[$gm]+1; $gm++;
    $dt = new DateTime(); $dt->setTimezone(new DateTimeZone('UTC'));
    $dt->setDate($gy,$gm,$gd)->setTime(0,0,0);
    return $dt;
}

/** Gregorian → Julian Calendar (proleptic) */
function tt_to_julian_cal(int $y, int $m, int $d): array {
    $jdn = tt_greg_to_jdn($y,$m,$d);
    // JDN → Julian proleptic calendar (correct formula)
    $a  = $jdn + 32082;
    $d2 = intdiv(4*$a + 3, 1461);
    $e  = $a - intdiv(1461*$d2, 4);
    $jm = intdiv(5*$e + 2, 153);
    $jd2 = $e - intdiv(153*$jm + 2, 5) + 1;
    $jmo = $jm + 3 - 12*intdiv($jm, 10);
    $jyr = $d2 - 4800 + intdiv($jm, 10);
    $mn=['','January','February','March','April','May','June','July','August','September','October','November','December'];
    return ['year'=>$jyr,'month'=>$jmo,'day'=>$jd2,'month_name'=>$mn[$jmo],'formatted'=>"$jd2 {$mn[$jmo]} $jyr JC"];
}

/** Julian Calendar Y/M/D → JDN → Gregorian */
function tt_from_julian_cal(int $jy, int $jm, int $jd): DateTime {
    $a=intdiv(14-$jm,12); $y2=$jy+4800-$a; $m2=$jm+12*$a-3;
    $jdn=$jd+intdiv(153*$m2+2,5)+365*$y2+intdiv($y2,4)-32083;
    return tt_jdn_to_greg($jdn);
}

/** Gregorian → Hebrew (approximate) */
function tt_to_hebrew(int $y, int $m, int $d): array {
    $hy = ($m>=9) ? $y+3761 : $y+3760;
    $map=[1=>5,2=>6,3=>7,4=>8,5=>9,6=>10,7=>11,8=>12,9=>1,10=>2,11=>3,12=>4];
    $hm=$map[$m]; $hd=$d;
    $mn=['','Tishri','Cheshvan','Kislev','Tevet','Shevat','Adar','Nisan','Iyar','Sivan','Tammuz','Av','Elul'];
    return ['year'=>$hy,'month'=>$hm,'day'=>$hd,'month_name'=>$mn[$hm],'formatted'=>"$hd {$mn[$hm]} $hy AM",'note'=>'Approximate'];
}

/** Gregorian → Chinese Zodiac year name */
function tt_chinese_zodiac(int $y): string {
    $signs=['Rat','Ox','Tiger','Rabbit','Dragon','Snake','Horse','Goat','Monkey','Rooster','Dog','Pig'];
    $elements=['Wood','Fire','Earth','Metal','Water'];
    $sign=$signs[($y-4)%12]; $elem=$elements[intdiv(($y-4)%10,2)];
    return "$elem $sign";
}

/** Moon phase for a date */
function tt_moon(DateTime $d): array {
    $knownNew = 947182440; // 2000-01-06 18:14 UTC
    $synodic  = 29.530588853;
    $ts = $d->getTimestamp();
    $daysSince = ($ts - $knownNew) / 86400;
    $age = fmod(fmod($daysSince, $synodic) + $synodic, $synodic);
    $illum = (int)round((1 - cos($age / $synodic * 2 * M_PI)) / 2 * 100);
    $phases=[
        [0,1.85,'New Moon'],[1.85,7.38,'Waxing Crescent'],[7.38,11.08,'First Quarter'],
        [11.08,14.77,'Waxing Gibbous'],[14.77,16.61,'Full Moon'],[16.61,22.15,'Waning Gibbous'],
        [22.15,25.84,'Last Quarter'],[25.84,29.53,'Waning Crescent'],
    ];
    $phaseName='New Moon';
    foreach ($phases as [$s,$e,$n]) { if ($age>=$s&&$age<$e){$phaseName=$n;break;} }
    $remFull = fmod(14.77-$age+$synodic,$synodic);
    $nf = new DateTime('@'.($ts+(int)($remFull*86400))); $nf->setTimezone(new DateTimeZone('UTC'));
    return ['phase'=>$phaseName,'age_days'=>round($age,2),'illumination'=>$illum,'next_full_moon'=>$nf->format('Y-m-d')];
}

/** Sun rise/set (NOAA simplified) */
function tt_sun(float $lat, float $lng, DateTime $d): array {
    $deg=M_PI/180; $rad=180/M_PI;
    [$y,$m,$da]=[(int)$d->format('Y'),(int)$d->format('n'),(int)$d->format('j')];
    $jd=367*$y-(int)(7*($y+(int)(($m+9)/12))/4)+(int)(275*$m/9)+$da+1721013.5;
    $n=$jd-2451545.0;
    $L=fmod(280.460+0.9856474*$n,360); $g=fmod(357.528+0.9856003*$n,360);
    $lambda=$L+1.915*sin($g*$deg)+0.020*sin(2*$g*$deg);
    $eps=23.439-0.0000004*$n;
    $sinDec=sin($eps*$deg)*sin($lambda*$deg); $cosDec=sqrt(1-$sinDec*$sinDec);
    $RA=fmod(atan2(cos($eps*$deg)*sin($lambda*$deg),cos($lambda*$deg))*$rad+360,360);
    $noon=12-$lng/15-($L-$RA)*4/60;
    $cosH=(cos(90.833*$deg)-$sinDec*sin($lat*$deg))/($cosDec*cos($lat*$deg));
    if ($cosH<-1||$cosH>1) return ['sunrise'=>'N/A','sunset'=>'N/A','solar_noon'=>tt_frac_t($noon),'day_length'=>'N/A'];
    $H=acos($cosH)*$rad; $rise=$noon-$H/15; $set=$noon+$H/15;
    $dm=(int)round(($set-$rise)*60);
    return ['sunrise'=>tt_frac_t($rise).' UTC','sunset'=>tt_frac_t($set).' UTC','solar_noon'=>tt_frac_t($noon).' UTC','day_length'=>intdiv($dm,60).'h '.($dm%60).'m'];
}

function tt_frac_t(float $f): string {
    $t=(int)round((fmod($f,24)+24)*60)%1440;
    return str_pad(intdiv($t,60),2,'0',STR_PAD_LEFT).':'.str_pad($t%60,2,'0',STR_PAD_LEFT);
}

function tt_relative(int $ts): string {
    $diff=time()-$ts;
    $a=abs($diff); $dir=$diff>0?'ago':'from now';
    if ($a<60) return 'just now';
    if ($a<3600) return round($a/60).' minutes '.$dir;
    if ($a<86400) return round($a/3600).' hours '.$dir;
    if ($a<2592000) return round($a/86400).' days '.$dir;
    return round($a/2592000).' months '.$dir;
}

function tt_cron_expand(string $field, int $min, int $max): array {
    $vals=[];
    foreach (explode(',',$field) as $part) {
        if (str_contains($part,'/')) {
            [$range,$step]=explode('/',$part);
            [$lo,$hi]=$range==='*'?[$min,$max]:(str_contains($range,'-')?explode('-',$range):[(int)$range,$max]);
            for ($i=(int)$lo;$i<=(int)$hi;$i+=(int)$step) $vals[]=$i;
        } elseif (str_contains($part,'-')) {
            [$lo,$hi]=explode('-',$part);
            for ($i=(int)$lo;$i<=(int)$hi;$i++) $vals[]=$i;
        } else { $vals[]=(int)$part; }
    }
    return $vals;
}

function tt_all_conversions(DateTime $g): array {
    $y=(int)$g->format('Y'); $m=(int)$g->format('n'); $d=(int)$g->format('j');
    return [
        'gregorian' => ['year'=>$y,'month'=>$m,'day'=>$d,'formatted'=>$g->format('l, F j, Y'),'iso'=>$g->format('Y-m-d')],
        'hijri'     => tt_to_hijri($y,$m,$d),
        'persian'   => tt_to_jalali($y,$m,$d),
        'julian'    => tt_to_julian_cal($y,$m,$d),
        'hebrew'    => tt_to_hebrew($y,$m,$d),
        'chinese'   => ['zodiac'=>tt_chinese_zodiac($y),'year'=>$y],
        'moon'      => tt_moon($g),
    ];
}

/* ─────────────────────────────────────────────────────────────────────────────
   ROUTE DISPATCH
───────────────────────────────────────────────────────────────────────────── */

switch ($action) {

    // ── Help / Docs ──────────────────────────────────────────────────────────
    case 'help':
    case 'docs':
        $base = (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].'/plugins/time-toolkit/api';
        tt_json([
            'name'    => 'Time Toolkit API',
            'version' => '1.0.0',
            'base_url'=> $base,
            'actions' => [
                'now'       => ['method'=>'GET','params'=>[],'description'=>'Current time in all formats'],
                'parse'     => ['method'=>'GET','params'=>['q'=>'Any date/timestamp/ISO string'],'description'=>'Parse any temporal input'],
                'convert'   => ['method'=>'GET','params'=>['date'=>'YYYY-MM-DD','from'=>'gregorian|hijri|persian|julian','to'=>'gregorian|hijri|persian|julian|hebrew|all'],'description'=>'Calendar system conversion'],
                'diff'      => ['method'=>'GET','params'=>['from'=>'YYYY-MM-DD','to'=>'YYYY-MM-DD'],'description'=>'Date difference in multiple units'],
                'cron'      => ['method'=>'GET','params'=>['expr'=>'Cron expression (5 fields)'],'description'=>'Explain cron + next 10 runs'],
                'moon'      => ['method'=>'GET','params'=>['date'=>'YYYY-MM-DD (default: today)'],'description'=>'Moon phase and illumination'],
                'sunrise'   => ['method'=>'GET','params'=>['lat'=>'Latitude','lng'=>'Longitude','date'=>'YYYY-MM-DD (default: today)'],'description'=>'Sunrise, sunset, solar noon'],
                'timestamp' => ['method'=>'GET','params'=>['ts'=>'Unix timestamp (10 digits)'],'description'=>'Full breakdown of a unix timestamp'],
                'formats'   => ['method'=>'GET','params'=>['date'=>'YYYY-MM-DD (default: today)'],'description'=>'Date in 15+ international formats'],
            ],
            'examples' => [
                $base.'?action=now',
                $base.'?action=parse&q=2025-06-15T14:30:00Z',
                $base.'?action=convert&date=2025-01-15&from=gregorian&to=all',
                $base.'?action=diff&from=2025-01-01&to=2025-12-31',
                $base.'?action=cron&expr=0+9+*+*+1-5',
                $base.'?action=moon&date=2025-01-15',
                $base.'?action=sunrise&lat=31.5204&lng=74.3587',
                $base.'?action=timestamp&ts=1719838273',
                $base.'?action=formats&date='.date('Y-m-d'),
            ],
        ]);
        break;

    // ── Now ──────────────────────────────────────────────────────────────────
    case 'now':
        $now = new DateTime('now', new DateTimeZone('UTC'));
        $ts  = $now->getTimestamp();
        tt_json(array_merge([
            'status'      => 'ok',
            'unix'        => $ts,
            'unix_ms'     => $ts * 1000,
            'iso8601'     => $now->format(DateTime::ATOM),
            'rfc2822'     => $now->format(DateTime::RFC2822),
            'utc'         => $now->format('Y-m-d H:i:s') . ' UTC',
            'date'        => $now->format('Y-m-d'),
            'time'        => $now->format('H:i:s'),
            'weekday'     => $now->format('l'),
            'week_number' => (int)$now->format('W'),
            'day_of_year' => (int)$now->format('z') + 1,
            'quarter'     => 'Q' . (int)ceil((int)$now->format('n') / 3),
            'leap_year'   => (bool)$now->format('L'),
        ], tt_all_conversions($now)));
        break;

    // ── Parse ────────────────────────────────────────────────────────────────
    case 'parse':
        $q = tt_get('q');
        if ($q === '') tt_error('Missing param: q');
        $p = tt_parse($q);
        if (!$p) tt_error("Cannot parse: $q");
        $d = $p['date']; $ts = $p['ts'];
        tt_json(array_merge([
            'status'      => 'ok',
            'input'       => $q,
            'type'        => $p['type'],
            'unix'        => $ts,
            'unix_ms'     => $ts * 1000,
            'iso8601'     => $d->format(DateTime::ATOM),
            'rfc2822'     => $d->format(DateTime::RFC2822),
            'utc'         => $d->format('Y-m-d H:i:s') . ' UTC',
            'date'        => $d->format('Y-m-d'),
            'time'        => $d->format('H:i:s'),
            'weekday'     => $d->format('l'),
            'week'        => (int)$d->format('W'),
            'day_of_year' => (int)$d->format('z') + 1,
            'leap_year'   => (bool)$d->format('L'),
            'relative'    => tt_relative($ts),
        ], tt_all_conversions($d)));
        break;

    // ── Convert ──────────────────────────────────────────────────────────────
    case 'convert':
        $dateStr = tt_get('date', date('Y-m-d'));
        $from    = tt_get('from', 'gregorian');
        $to      = tt_get('to', 'all');

        $gDate = null;
        switch ($from) {
            case 'gregorian':
                try { $gDate = new DateTime($dateStr.' 00:00:00', new DateTimeZone('UTC')); } catch(Exception) {}
                break;
            case 'hijri':
                if (preg_match('/^(\d+)[\/\-](\d+)[\/\-](\d+)$/', $dateStr, $mx))
                    $gDate = tt_from_hijri((int)$mx[1],(int)$mx[2],(int)$mx[3]);
                break;
            case 'persian': case 'jalali':
                if (preg_match('/^(\d+)[\/\-](\d+)[\/\-](\d+)$/', $dateStr, $mx))
                    $gDate = tt_from_jalali((int)$mx[1],(int)$mx[2],(int)$mx[3]);
                break;
            case 'julian':
                if (preg_match('/^(\d+)[\/\-](\d+)[\/\-](\d+)$/', $dateStr, $mx))
                    $gDate = tt_from_julian_cal((int)$mx[1],(int)$mx[2],(int)$mx[3]);
                break;
        }
        if (!$gDate) tt_error("Cannot parse date '$dateStr' from calendar '$from'. Use YYYY-MM-DD format.");

        $all = tt_all_conversions($gDate);
        $result = ['status'=>'ok','input'=>$dateStr,'from'=>$from,'to'=>$to,'gregorian_equivalent'=>$gDate->format('Y-m-d')];
        if ($to === 'all' || $to === '') $result = array_merge($result, $all);
        else $result[$to] = $all[$to] ?? tt_error("Unknown target calendar: $to");

        tt_json($result);
        break;

    // ── Diff ─────────────────────────────────────────────────────────────────
    case 'diff':
        $fromStr = tt_get('from'); $toStr = tt_get('to', date('Y-m-d'));
        if ($fromStr === '') tt_error('Missing param: from');
        try {
            $d1 = new DateTime($fromStr.' 00:00:00', new DateTimeZone('UTC'));
            $d2 = new DateTime($toStr.' 00:00:00', new DateTimeZone('UTC'));
        } catch (Exception $e) { tt_error('Invalid date: '.$e->getMessage()); }
        $diff = $d1->diff($d2); $td = (int)(($d2->getTimestamp()-$d1->getTimestamp())/86400);
        tt_json([
            'status'         => 'ok',
            'from'           => $d1->format('Y-m-d'),
            'to'             => $d2->format('Y-m-d'),
            'direction'      => $td >= 0 ? 'future' : 'past',
            'years'          => $diff->y,
            'months'         => $diff->m,
            'days'           => $diff->d,
            'total_days'     => abs($td),
            'total_weeks'    => round(abs($td)/7, 2),
            'total_hours'    => abs($td)*24,
            'total_minutes'  => abs($td)*1440,
            'total_seconds'  => abs($td)*86400,
            'business_days'  => tt_biz_days($d1,$d2),
            'human_readable' => abs($diff->y).'y '.abs($diff->m).'m '.abs($diff->d).'d',
        ]);
        break;

    // ── Cron ─────────────────────────────────────────────────────────────────
    case 'cron':
        $expr = tt_get('expr');
        if ($expr === '') tt_error('Missing param: expr (e.g. 0 9 * * 1-5)');
        $parts = preg_split('/\s+/', $expr);
        if (count($parts) !== 5) tt_error('Invalid cron: must have exactly 5 fields');
        [$mn,$hr,$dom,$mo,$dow] = $parts;
        $nexts=[]; $now=new DateTime('now',new DateTimeZone('UTC')); $check=clone $now;
        $check->modify('+1 minute'); $check->setTime((int)$check->format('H'),(int)$check->format('i'),0);
        $found=0; $iter=0;
        while ($found<10&&$iter<527040) {
            $iter++;
            $okM  = $mn==='*'  || in_array((int)$check->format('i'), tt_cron_expand($mn,0,59));
            $okH  = $hr==='*'  || in_array((int)$check->format('H'), tt_cron_expand($hr,0,23));
            $okD  = $dom==='*' || in_array((int)$check->format('j'), tt_cron_expand($dom,1,31));
            $okMo = $mo==='*'  || in_array((int)$check->format('n'), tt_cron_expand($mo,1,12));
            $okW  = $dow==='*' || in_array((int)$check->format('w'), tt_cron_expand($dow,0,6));
            if ($okM&&$okH&&$okD&&$okMo&&$okW) { $nexts[]=$check->format(DateTime::ATOM); $found++; }
            $check->modify('+1 minute');
        }
        tt_json(['status'=>'ok','expression'=>$expr,'fields'=>compact('mn','hr','dom','mo','dow'),'next_10_runs'=>$nexts]);
        break;

    // ── Moon ─────────────────────────────────────────────────────────────────
    case 'moon':
        $dateStr = tt_get('date', date('Y-m-d'));
        try { $d = new DateTime($dateStr.' 00:00:00', new DateTimeZone('UTC')); } catch(Exception) { tt_error('Invalid date'); }
        tt_json(array_merge(['status'=>'ok','date'=>$dateStr], tt_moon($d)));
        break;

    // ── Sunrise ───────────────────────────────────────────────────────────────
    case 'sunrise':
        if (!isset($_GET['lat'])&&!isset($_POST['lat'])) tt_error('Missing params: lat, lng');
        $lat = (float)tt_get('lat'); $lng = (float)tt_get('lng');
        $dateStr = tt_get('date', date('Y-m-d'));
        try { $d = new DateTime($dateStr.' 12:00:00', new DateTimeZone('UTC')); } catch(Exception) { tt_error('Invalid date'); }
        tt_json(array_merge(['status'=>'ok','date'=>$dateStr,'lat'=>$lat,'lng'=>$lng], tt_sun($lat,$lng,$d)));
        break;

    // ── Timestamp ────────────────────────────────────────────────────────────
    case 'timestamp':
        $tsStr = tt_get('ts');
        if ($tsStr === '') tt_error('Missing param: ts (10-digit unix timestamp)');
        if (!preg_match('/^\-?\d{1,13}$/', $tsStr)) tt_error('ts must be a numeric unix timestamp');
        $ts = (int)$tsStr;
        // Auto-detect ms
        if (strlen($tsStr)===13) { $ts=intdiv($ts,1000); $type='unix_ms'; } else { $type='unix'; }
        $d = new DateTime('@'.$ts); $d->setTimezone(new DateTimeZone('UTC'));
        tt_json(array_merge([
            'status'   => 'ok',
            'input'    => $tsStr,
            'type'     => $type,
            'unix'     => $ts,
            'unix_ms'  => $ts*1000,
            'unix_us'  => $ts*1000000,
            'iso8601'  => $d->format(DateTime::ATOM),
            'rfc2822'  => $d->format(DateTime::RFC2822),
            'utc'      => $d->format('Y-m-d H:i:s').' UTC',
            'date'     => $d->format('Y-m-d'),
            'time'     => $d->format('H:i:s'),
            'weekday'  => $d->format('l'),
            'relative' => tt_relative($ts),
        ], tt_all_conversions($d)));
        break;

    // ── Formats ───────────────────────────────────────────────────────────────
    case 'formats':
        $dateStr = tt_get('date', date('Y-m-d'));
        try { $d = new DateTime($dateStr.' 00:00:00', new DateTimeZone('UTC')); } catch(Exception) { tt_error('Invalid date'); }
        tt_json(['status'=>'ok','input'=>$dateStr,'formats'=>[
            'ISO 8601'        => $d->format('Y-m-d'),
            'ISO 8601 Full'   => $d->format(DateTime::ATOM),
            'US (M/D/Y)'      => $d->format('m/d/Y'),
            'EU (D.M.Y)'      => $d->format('d.m.Y'),
            'UK (D/M/Y)'      => $d->format('d/m/Y'),
            'RFC 2822'        => $d->format(DateTime::RFC2822),
            'Long English'    => $d->format('l, F j, Y'),
            'Short English'   => $d->format('M j, Y'),
            'Abbreviated'     => $d->format('D, d M Y'),
            'SQL Datetime'    => $d->format('Y-m-d H:i:s'),
            'SQL Date'        => $d->format('Y-m-d'),
            'Unix Timestamp'  => $d->getTimestamp(),
            'Ordinal'         => $d->format('jS F Y'),
            'Year / Week'     => $d->format('o/W'),
            'Month Year'      => $d->format('F Y'),
        ]]);
        break;

    default:
        tt_error("Unknown action: '$action'. Use action=help to see available actions.", 404);
}

function tt_biz_days(DateTime $a, DateTime $b): int {
    $count=0; $cur=clone $a; $cur->setTime(0,0,0); $end=clone $b; $end->setTime(0,0,0);
    $step=$cur<=$end?1:-1;
    while ($cur!=$end) { $w=(int)$cur->format('w'); if ($w!==0&&$w!==6) $count++; $cur->modify($step.' day'); }
    return $count;
}
