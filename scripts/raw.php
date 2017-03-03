<?php

$path = dirname(__DIR__) . '/raw';
$pathPage = $path . '/pages';
if (!file_exists($pathPage)) {
    mkdir($pathPage, 0777, true);
}
$pathNat = $path . '/nat';
if (!file_exists($pathNat)) {
    mkdir($pathNat, 0777);
}

$keys = array('284');
for ($i = 310; $i < 320; $i++) {
    $keys[] = $i;
}

$fh = fopen(dirname(__DIR__) . '/raw.csv', 'w');

$base = array(
    '&#37390;' => '鈎',
    '&#25301;' => '拕',
    '&#30936;' => '磘',
    '&nbsp;' => '',
    '&#37739;' => '鍫',
);

foreach ($keys AS $key) {
    if (!file_exists($pathPage . '/' . $key)) {
        $url = 'http://www.cto.moea.gov.tw/web/application/detail.php?cid=6&id=' . $key . '&aid=32';
        file_put_contents($pathPage . '/' . $key, file_get_contents($url));
    }
    $page = file_get_contents($pathPage . '/' . $key);
    $pos = strpos($page, '<div class="panel panel-blue margin-bottom-40">');
    if (false !== $pos) {
        $posEnd = strpos($page, '<ul class=\'list-inline\'>', $pos);
        $main = substr($page, $pos, $posEnd - $pos);
        preg_match_all('/href="([^"]*)"/', $main, $matches);
        foreach ($matches[1] AS $url) {
            $parts = explode('/', $url);
            array_shift($parts);
            array_shift($parts);
            array_shift($parts);
            array_shift($parts);
            array_shift($parts);
            $natFile = $pathNat . '/' . implode('_', $parts);
            if (!file_exists($natFile)) {
                file_put_contents($natFile, file_get_contents($url));
            }
            $raw = file_get_contents($natFile);
            $nat = mb_convert_encoding($raw, 'utf-8', 'big5');

            $pos = strpos($nat, '>附表<');
            if (false === $pos) {
                $pos = strpos($nat, '>附件<');
            }
            if (false !== $pos) {
                $pool = array();
                $pos = strpos($nat, '<table', $pos);
                $posEnd = strpos($nat, '</table>', $pos);
                $table = substr($nat, $pos, $posEnd - $pos);
                $table = strtr($table, $base);
                $rows = explode('</tr>', $table);
                foreach ($rows AS $row) {
                    $cols = explode('</td>', $row);
                    if (count($cols) > 4) {
                        foreach ($cols AS $k => $v) {
                            $cols[$k] = preg_replace('/\s/', '', strip_tags($v));
                        }
                        $cols[] = $url;
                        fputcsv($fh, $cols);
                    }
                }
            }
        }
    }
}