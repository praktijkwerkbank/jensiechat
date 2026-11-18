<?php
// Jensiechat, basisversie. Copyright (c) 2026 Jens Kemna, Praktijkwerkbank (praktijkwerkbank.nl). MIT-licentie, zie LICENSE.
// De accentkleur uit config.php als CSS, en het tabbladicoon (?icoon=1). Werkt ook zonder config (dan de standaardkleur).
define('JC_APP', 'thema');
require __DIR__ . '/lib.php';
$hex = jc_kleur(); $rgb = [hexdec(substr($hex, 1, 2)), hexdec(substr($hex, 3, 2)), hexdec(substr($hex, 5, 2))];
function th_lum($c) { $c = array_map(function ($v) { $v /= 255; return $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4); }, $c); return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2]; }
function th_contrast($a, $b) { $x = th_lum($a); $y = th_lum($b); return (max($x, $y) + 0.05) / (min($x, $y) + 0.05); }
function th_donker($c, $f) { return array_map(fn($v) => (int)round($v * (1 - $f)), $c); }
function th_hex($c) { return sprintf('#%02x%02x%02x', $c[0], $c[1], $c[2]); }
// Tekst op de accentkleur: wit of bijna zwart, wat het meeste contrast geeft.
$op = th_contrast([255, 255, 255], $rgb) >= th_contrast([17, 20, 28], $rgb) ? '#ffffff' : '#11141c';
// Een donkere variant voor tekst op een lichte ondergrond: zo ver donkerder tot het contrast met wit minstens 4,5 is.
$inkt = $rgb; for ($i = 0; $i < 20 && th_contrast($inkt, [255, 255, 255]) < 4.5; $i++) $inkt = th_donker($inkt, 0.08);
$sterk = th_donker($rgb, 0.1);
header('Cache-Control: no-cache'); header('X-Content-Type-Options: nosniff');
if (isset($_GET['icoon'])) {
  header('Content-Type: image/svg+xml');
  $letter = htmlspecialchars(mb_strtoupper(mb_substr(jc_merk()['naam'], 0, 1)), ENT_QUOTES, 'UTF-8');
  echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="32" fill="' . $hex . '"/><text x="32" y="43" text-anchor="middle" font-family="system-ui,sans-serif" font-size="32" font-weight="700" fill="' . $op . '">' . $letter . '</text></svg>';
  exit;
}
header('Content-Type: text/css; charset=utf-8');
echo ':root{--accent:' . $hex . ';--accent-strong:' . th_hex($sterk) . ';--accent-ink:' . th_hex($inkt) . ';--on-accent:' . $op . ';--accent-soft:rgba(' . implode(',', $rgb) . ',.16);--accent-veld:rgba(' . implode(',', $rgb) . ',.28);}' . "\n";
