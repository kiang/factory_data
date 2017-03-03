<?php

$cache = dirname(__DIR__) . '/cache';
if (!file_exists($cache)) {
    mkdir($cache, 0777);
}

$result = new stdClass();
$result->type = 'FeatureCollection';
$result->features = array();

$fh = fopen(dirname(__DIR__) . '/raw.csv', 'r');
$pool = array();
$poolCount = 0;
while ($line = fgetcsv($fh, 2000)) {
    if (!preg_match('/[0-9]+/', $line[0])) {
        continue;
    }
    $url = array_pop($line);
    $numField = false;
    foreach ($line AS $k => $v) {
        if ($k > 0 && false === $numField && preg_match('/[0-9]+/', $line[$k])) {
            $numField = $k;
        }
    }
    $line[$numField] = preg_replace('/[^0-9\\-]/', '', $line[$numField]);
    $sectionKey = $numField - 1;
    if (empty($line[$sectionKey]) || $line[$sectionKey] === '---') {
        $sectionKey = $numField - 2;
    }

    if (preg_match('/[0-9]+/', $line[$sectionKey])) {
        $section = $lastSection;
        $city = $lastCity;
    } else {
        $section = $line[$sectionKey];
        $city = $line[1];
    }
    if (mb_substr($section, -1, 1, 'utf-8') !== '段') {
        $section .= '段';
    }
    $nocity = false;
    switch ($section) {
        case '空白段':
        case '未登錄國有地段':
        case '未編訂道路段':
            continue(2);
            break;
        case '太子宮段':
            $section = '太子宮段太子宮小段';
            break;
        case '東勢段':
            if ($city === '桃園縣') {
                $section = '東勢段東勢小段';
            }
            break;
        case '圳股頭段':
            if ($city === '桃園縣' || $city === '桃園') {
                $section = '圳股頭段圳股頭小段';
            }
            break;
        case '古亭段':
            if ($city === '桃園') {
                $section = '圳股頭段古亭小段';
            }
            break;
        case '後厝段':
            if ($city === '桃園縣') {
                $section = '沙崙段後厝小段';
            }
            break;
        case '磚子磘段':
            $section = 'EL1700';
            $parts = explode('-', $line[$numField]);
            if (count($parts) === 2) {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            } else {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            }
            $nocity = true;
            break;
        case '拕子段':
            $section = 'EF2314';
            $parts = explode('-', $line[$numField]);
            if (count($parts) === 2) {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            } else {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            }
            $nocity = true;
            break;
        case '壟鈎坑段':
            $section = 'FB0353';
            $parts = explode('-', $line[$numField]);
            if (count($parts) === 2) {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            } else {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            }
            $nocity = true;
            break;
        case '山腳段':
            $section = 'QD0705';
            $parts = explode('-', $line[$numField]);
            if (count($parts) === 2) {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            } else {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            }
            $nocity = true;
            break;
        case '新磁磘段':
            $section = 'LB0307';
            $parts = explode('-', $line[$numField]);
            if (count($parts) === 2) {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT) . str_pad($parts[1], 4, '0', STR_PAD_LEFT);
            } else {
                $line[$numField] = str_pad($parts[0], 4, '0', STR_PAD_LEFT);
            }
            $nocity = true;
            break;
        case '後壁厝段':
        case '后寮段':
        case '大窠坑段':
            $section = $line[$sectionKey - 1] . '段' . $line[$sectionKey] . '小段';
            break;
        case '後壁厝小段':
        case '牛埔子小段':
        case '王公廟小段':
        case '三小段':
            $section = $line[$sectionKey - 1] . $section;
            break;
        case '中洲段':
            if ($city === '臺南市') {
                //RD0510
                $section = '中州段';
            }
            break;
    }

    $cacheFile = $cache . "/{$city}-{$section}-{$line[$numField]}.json";
    if (!file_exists($cacheFile)) {
        if ($nocity) {
            $fc = file_get_contents('http://twland.ronny.tw/index/search?lands[]=' . urlencode(implode(',', array(
                        $section, $line[$numField]
            ))));
        } else {
            $fc = file_get_contents('http://twland.ronny.tw/index/search?lands[]=' . urlencode(implode(',', array(
                        $city, $section, $line[$numField]
            ))));
        }
        $objs = json_decode($fc, true);
        if (count($objs['features']) === 0) {
            echo $url;
            print_r(array(
                $city, $section, $line[$numField]
            ));
            print_r($objs);
            exit();
        } else {
            file_put_contents($cacheFile, $fc);
        }
    }

    $json = json_decode(file_get_contents($cacheFile), true);
    if (count($json['features']) === 1) {
        $targetFeature = array_pop($json['features']);
    } else {
        $targetFeature = false;
        foreach ($json['features'] AS $f) {
            if (false !== strpos($f['properties']['鄉鎮'], $line[2])) {
                $targetFeature = $f;
            }
        }
        if (false === $targetFeature) {
            print_r($line);
            exit();
        }
    }
    $targetFeature['properties']['url'] = $url;
    $result->features[] = $targetFeature;
    /*
     * Array
      (
      [縣市] => 新北市
      [鄉鎮] => 樹林區
      [段名] => 武林段
      [地號] => 9680000
      [ymax] => 25.001066431467
      [ymin] => 25.000614814758
      [xmax] => 121.41339347098
      [xmin] => 121.4130445437
      [xcenter] => 121.41321900734
      [ycenter] => 25.000840623112
      )
     * 
     * Array
      (
      [縣市] => 新北市
      [鄉鎮] => 五股區
      [地段] => 五股坑段壟坑小段
      [事務所] => 新莊
      [id] => FB0353
      [段號] => 79-12
      [input] => FB0353,00790012
      [ymax] => 25.093044550449
      [ymin] => 25.092871673607
      [xmax] => 121.41782194659
      [xmin] => 121.41771940434
      [xcenter] => 121.41777067546
      [ycenter] => 25.092958112028
      )
     */

    $lastSection = $section;
    $lastCity = $city;
}

file_put_contents(dirname(__DIR__) . '/zones.json', json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
