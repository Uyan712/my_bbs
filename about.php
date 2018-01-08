<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';
require_logined_session();

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <title>About</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" type="text/css" href="./library/css/bootstrap.min.css">

  <!-- Open Iconic CSS -->
  <link rel="stylesheet" type="text/css" href="./library/open-iconic/font/css/open-iconic-bootstrap.min.css">

  <!-- JavaScript -->
  <script src="./library/js/jquery-3.2.1.min.js"></script>
  <script src="./library/js/popper.js"></script>
  <script src="./library/js/bootstrap.min.js"></script>
</head>

<body>

  <!-- メニュー -->
  <nav class="navbar fixed-top navbar-expand-md navbar-light bg-white">
    <a class="navbar-brand" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php">掲示板</a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
  
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav mr-auto">
        <li class="nav-item">
          <a class="nav-link" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php"><span class="oi oi-home mr-1" title="home" area-hidden="true"></span>Home<span class="sr-only">(current)</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/profile.php"><span class="oi oi-person mr-1" title="person" area-hidden="true"></span>Profile</a>
        </li>
        <li class="nav-item active">
          <a class="nav-link" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/about.php"><span class="oi oi-spreadsheet mr-1" title="spreadsheet" area-hidden="true"></span>About</a>
        </li>
      </ul>
      <div class="d-flex">
        <div class="mr-2">ようこそ，<?= e($_SESSION['username']) ?></div>
        <div>さん</div>
        <a href="/logout.php?token=<?= e(generate_token()) ?>" class="ml-3"><span class="oi oi-share-boxed mr-1" title="share-boxed" area-hidden="true"></span></a>
      </div>
    </div>
  </nav>

  <div class="container-fluid">
    <div class="row justify-content-center" style="margin-top: 60px; height: 60px;"><h1>About</h1></div>

    <div class="row justify-content-center">
      <h4>使用ライブラリ</h4>
    </div>

    <div class="row justify-content-center">
      <ul>
        <li><a href="https://getbootstrap.com/">Bootstrap</a></li>
        <li><a href="https://useiconic.com/open/">Iconic</a></li>
      </ul>
    </div>

    <div class="row justify-content-center mt-5">
      <a href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php">戻る</a>
    </div>
  </div>
</body>
</html>
