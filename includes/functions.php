<?php
// Funkcje pomocnicze UI i formatowania
function shorten($text, $len = 80) {
    if (mb_strlen($text) <= $len) return $text;
    return mb_substr($text,0,$len-3) . '...';
}
function format_price($n) {
    return number_format((float)$n, 0, ',', ' ') . ' zł';
}