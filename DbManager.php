<?php
function getDb() {
  $dsn = 'mysql:dbname=bbs; host=127.0.0.1; charset=utf8mb4';
  $usr = 'bbs_user';
  $passwd = 'h3WwK5uqUPdn8nfmmc5rnepckMk21F.1Y79hYaVt5KtN0iDANUw6W';
  $db = new PDO($dsn, $usr, $passwd, [PDO::ATTR_PERSISTENT => true]);
  $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  return $db;
}
