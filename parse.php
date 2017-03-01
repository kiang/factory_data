<?php
$header = array(
  '工廠名稱',
'工廠登記編號',
'工廠設立許可案號',
'工廠地址',
'工廠市鎮鄉村里',
'工廠負責人姓名',
'營利事業統一編號',
'工廠組織型態',
'工廠設立核准日期',
'工廠登記核准日期',
'工廠登記狀態',
'產業類別',
'主要產品',
);
$xml = new XMLReader();
$xml->open(__DIR__ . '/截至1051231之生產中工廠清冊.xml');
while ($xml->read()) {
  if ($xml->nodeType === XMLReader::ELEMENT && $xml->name === 'ROW') {
    $row = (array) simplexml_load_string($xml->readOuterXml(), 'SimpleXMLElement', LIBXML_NOCDATA);
    foreach ($row['COLUMN'] AS $k => $v) {
      $row['COLUMN'][$k] = is_string($v) ? $v : '';
    }
    $data = array_combine($header, $row['COLUMN']);
    $data['產業類別'] = explode(',', $data['產業類別']);
    $data['主要產品'] = explode(',', $data['主要產品']);
    $path = __DIR__ . '/factory/' . substr($data['工廠登記編號'], 0, 1);
    if(!file_exists($path)) {
      mkdir($path, 0777, true);
    }
    $targetFile = $path . '/' . $data['工廠登記編號'] . '.json';
    if(file_exists($targetFile)) {
      $jData = json_decode(file_get_contents($targetFile), true);
      if(empty($jData['egis'])) {
        unlink($targetFile);
      }
    }
    if(!file_exists($targetFile)) {
      file_get_contents('http://egis.moea.gov.tw/MoeaEGFxData_WebAPI_Inside/FACT_API/FACT_LIST?ID=' . $data['工廠登記編號']);
      if(!empty($http_response_header)) {
        foreach($http_response_header AS $line) {
          if(false !== strpos($line, 'Location: ')) {
            $url = urldecode($line);
            $data['egis'] = json_decode(substr(urldecode($url), strpos($url, '{')));
          }
        }
      }
      file_put_contents($targetFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
  }
}
