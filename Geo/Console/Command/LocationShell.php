<?php

/**
 * Класс для обновления базы Geo
 */
class LocationShell extends AppShell {
 public $uses = array('Geo.Location');

/**
 * Функция скачивает файл базы Geo, разархивирует его, создает в БД две таблицы, заполняет их данными из файлов и удаляет скачанные файлы
 */
 public function update() {
  $this->autoRender = false;
  set_time_limit(120);

  // получаем файл geo базы
  $file = file_get_contents('http://ipgeobase.ru/files/db/Main/geo_files.zip');
  // прописываем пути для файлов
  $path_dir = ROOT.DS.APP_DIR.DS.'Plugin'.DS.'Geo'.DS.'tmp'.DS;
  $path_file = 'geo_base.zip';
  $unzip = 'unzip';

  $f = fopen($path_dir.$path_file, 'wb'); // пишем полученную гео-базу в файл
  if(!$f) {
   die('Cannot open file');
  }
  fwrite($f, $file);
  fclose($f);

  $zip = new ZipArchive; // разархивируем базу
  $res = $zip->open($path_dir.$path_file);
  if($res === true) {
   $zip->extractTo($path_dir.$unzip);
   $zip->close();
   $cities = fopen($path_dir.$unzip.DS.'cities.txt', 'rt');
   $cidr_optim = fopen($path_dir.$unzip.DS.'cidr_optim.txt', 'rt');
   if(!$cities || !$cidr_optim) {
    die('Cannot open file');
   }

   $create_base = '
    DROP TABLE IF EXISTS `geo__base`;
    CREATE TABLE IF NOT EXISTS `geo__base` (
     `long_ip1` bigint(20) NOT NULL,
     `long_ip2` bigint(20) NOT NULL,
     `ip1` varchar(16) NOT NULL,
     `ip2` varchar(16) NOT NULL,
     `country` varchar(2) NOT NULL,
     `city_id` int(10) NOT NULL,
     KEY `INDEX` (`long_ip1`,`long_ip2`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
    $create_cities = '
    DROP TABLE IF EXISTS `geo__cities`;
    CREATE TABLE IF NOT EXISTS `geo__cities` (
     `city_id` int(10) NOT NULL,
     `city` varchar(128) NOT NULL,
     `region` varchar(128) NOT NULL,
     `district` varchar(128) NOT NULL,
     `lat` float NOT NULL,
     `lng` float NOT NULL,
     PRIMARY KEY (`city_id`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;';
   $this->Location->query($create_base);
   $this->Location->query($create_cities);

   $query = 'INSERT INTO geo__cities VALUES ';
   while($data = fgetcsv($cities, 1000, "\t")) { // набираем запрос
    $query .= "('".$data[0]."', '".$data[1]."', '".$data[2]."', '".$data[3]."', '".$data[4]."', '".$data[5]."'),";
   }
   $this->__insert($query); // и выполняем его

   $query = 'INSERT INTO geo__base VALUES ';
   $i = 0;
   while($data = fgetcsv($cidr_optim, 1000, "\t")) {
    if($data[4] == '-') { // в последней колонке хранится ID города. его может не быть для западной Европы, поэтому меняем дефис на ноль
     $data[4] = '0';
    }
    $pos = strpos($data[2], ' - '); // третья колонка не разбита табуляцией, а только " - ", поэтому делим ее на две
    $data[5] = substr($data[2], $pos + 3);
    $data[2] = substr($data[2], 0, $pos);
    $query .= "('".$data[0]."', '".$data[1]."', '".$data[2]."', '".$data[5]."', '".$data[3]."', '".$data[4]."'),";
    $i++;
    if($i >= 5000) {
     $this->__insert($query);
     $query = 'INSERT INTO geo__base VALUES ';
     $i = 0;
    }
   }
   if(strlen($query) > 40) {
    $this->__insert($query);
   }

   fclose($cities);
   fclose($cidr_optim);
   unlink($path_dir.$unzip.DS.'cities.txt');
   unlink($path_dir.$unzip.DS.'cidr_optim.txt');
   unlink($path_dir.$path_file);
  } else {
   echo 'failed, code:'.$res.' ';
  }

  echo 'done';
 }

/**
 * выполняет запрос для вставки данных в БД
 *
 * @param string $query запрос
 */
 private function __insert($query) {
  $query = substr($query, 0, strlen($query) - 1);
  $query .= ';';
  $query = iconv('windows-1251', 'utf-8', $query);
  $this->Location->query($query);
 }
}