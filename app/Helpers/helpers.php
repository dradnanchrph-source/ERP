<?php
// ── Money ─────────────────────────────────────────────────────────
function money(float $amt, string $sym='Rs.'): string {
    return $sym . ' ' . number_format($amt, 2);
}
function compact_money(float $amt): string {
    if (abs($amt)>=1000000) return 'Rs. '.number_format($amt/1000000,1).'M';
    if (abs($amt)>=1000)    return 'Rs. '.number_format($amt/1000,1).'K';
    return 'Rs. '.number_format($amt,2);
}

// ── Dates ─────────────────────────────────────────────────────────
function fmt_date(?string $d, string $f='d M Y'): string {
    if (!$d || $d==='0000-00-00') return '—';
    $ts = strtotime($d);
    return $ts ? date($f,$ts) : '—';
}
function fmt_datetime(?string $d): string { return fmt_date($d,'d M Y H:i'); }
function days_until(?string $d): int {
    if (!$d) return 9999;
    return (int)ceil((strtotime($d)-time())/86400);
}

// ── Status badge ──────────────────────────────────────────────────
function badge(string $status, ?string $label=null): string {
    static $map = [
        'active'=>'success','paid'=>'success','completed'=>'success',
        'delivered'=>'success','approved'=>'success','received'=>'success',
        'pending'=>'warning','draft'=>'warning','partial'=>'warning',
        'unpaid'=>'danger','cancelled'=>'danger','rejected'=>'danger',
        'inactive'=>'secondary','expired'=>'danger','open'=>'info',
        'in_progress'=>'info','processing'=>'info','confirmed'=>'primary',
        'both'=>'purple','customer'=>'primary','supplier'=>'success',
    ];
    $cls   = $map[strtolower($status)] ?? 'secondary';
    $text  = $label ?? ucwords(str_replace(['_','-'],' ',$status));
    return '<span class="badge bg-'.$cls.'">'.e($text).'</span>';
}

// ── HTML escape ───────────────────────────────────────────────────
function e(?string $s): string { return htmlspecialchars($s??'',ENT_QUOTES,'UTF-8'); }

// ── Truncate ──────────────────────────────────────────────────────
function trunc(?string $s, int $n=50): string {
    $s = strip_tags($s??'');
    return mb_strlen($s)>$n ? mb_substr($s,0,$n).'…' : $s;
}

// ── Number ────────────────────────────────────────────────────────
function num(float $n, int $d=2): string { return number_format($n,$d); }

// ── Active nav ────────────────────────────────────────────────────
function active(string $path): string {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    return str_starts_with($uri, $path) ? 'active' : '';
}

// ── CSRF field ────────────────────────────────────────────────────
function csrf_field(): string {
    return '<input type="hidden" name="_token" value="'.e(Auth::csrf()).'">';
}

// ── Flash messages ────────────────────────────────────────────────
function flash(string $key, string $msg): void  { $_SESSION['flash'][$key] = $msg; }
function get_flash(string $key): ?string {
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}
function has_flash(): bool { return !empty($_SESSION['flash']); }

// ── Pagination HTML ───────────────────────────────────────────────
function pagination(array $p, string $baseUrl=''): string {
    if ($p['pages']<=1) return '';
    $url  = $baseUrl ?: strtok($_SERVER['REQUEST_URI'],'?');
    $qs   = $_GET; $html = '<nav><ul class="pagination pagination-sm mb-0">';
    if ($p['page']>1) {
        $qs['page']=$p['page']-1;
        $html.='<li class="page-item"><a class="page-link" href="'.$url.'?'.http_build_query($qs).'">‹</a></li>';
    }
    $start = max(1,$p['page']-2); $end = min($p['pages'],$p['page']+2);
    for ($i=$start;$i<=$end;$i++) {
        $qs['page']=$i;
        $active = $i==$p['page'] ? ' active' : '';
        $html.='<li class="page-item'.$active.'"><a class="page-link" href="'.$url.'?'.http_build_query($qs).'">'.$i.'</a></li>';
    }
    if ($p['page']<$p['pages']) {
        $qs['page']=$p['page']+1;
        $html.='<li class="page-item"><a class="page-link" href="'.$url.'?'.http_build_query($qs).'">›</a></li>';
    }
    return $html.'</ul></nav>';
}
