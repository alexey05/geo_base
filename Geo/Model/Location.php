<?php
App::import('Geo.Model', 'GeoAppModel');

/**
 * Класс для определения местонахождения по IP
 */
class Location extends GeoAppModel {

 public $name = 'Location';
 public $useTable = false;

/**
 * Определяет город и регион по IP
 * @param mixed $ip IP адрес
 */
 public function getIpInfo($ip = null) {
  if(!$ip || !$this->__isValidIp($ip))
   $ip = $this->__getIp();

  $long_ip = sprintf("%u", ip2long($ip));
  $q = "SELECT * FROM geo__base as base, geo__cities as city WHERE base.long_ip1<='" . $long_ip . "' AND base.long_ip2>='" . $long_ip . "' and base.city_id=city.city_id";
  $data = $this->query($q);
  return $data;
 }

/**
 * функция определяет ip адрес по глобальному массиву $_SERVER
 * ip адреса проверяются начиная с приоритетного, для определения возможного использования прокси
 * @return ip-адрес
 */
 private function __getIp() {
  $ip = false;
  if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
   $ipa[] = trim(strtok($_SERVER['HTTP_X_FORWARDED_FOR'], ','));

  if(isset($_SERVER['HTTP_CLIENT_IP']))
   $ipa[] = $_SERVER['HTTP_CLIENT_IP'];

  if(isset($_SERVER['REMOTE_ADDR']))
   $ipa[] = $_SERVER['REMOTE_ADDR'];

  if(isset($_SERVER['HTTP_X_REAL_IP']))
   $ipa[] = $_SERVER['HTTP_X_REAL_IP'];

  // проверяем ip-адреса на валидность начиная с приоритетного.
  foreach($ipa as $ips) {
   //  если ip валидный обрываем цикл, назначаем ip адрес и возвращаем его
   if($this->__isValidIp($ips)) {
    $ip = $ips;
    break;
   }
  }
  return $ip;
 }

/**
 * функция для проверки валидности ip адреса
 * @param string $ip адрес в формате 1.2.3.4
 * @return boolean true - если ip валидный, иначе false
 */
 private function __isValidIp($ip = null) {
  if(preg_match("#^([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})\.([0-9]{1,3})$#", $ip))
   return true; // если ip-адрес попадает под регулярное выражение, возвращаем true
  return false; // иначе возвращаем false
 }
}