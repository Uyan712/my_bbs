<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';
require_logined_session();

// 投稿内容のハッシュ値
$content_hash = '';
// 二重送信フラグ
$double_transmission_flag = false;
// 登録完了フラグ
$ok = false;

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // アップロードファイルの情報を取得
  $ext = pathinfo($_FILES['upfile']['name']);
  $lower_ext = strtolower($ext['extension']);
  $content_hash = md5(implode('', $ext));
  // アップロードを許可する拡張子を配列で定義
  $perm = ['jpg', 'jpeg', 'png'];
  // 二重投稿でないかを検証
  if (isset($_SESSION['content_hash']) && ($_SESSION['content_hash'] === $content_hash)) {
    $double_transmission_flag = true;
  // アップロード処理そのものの成否をチェック
  } elseif ($_FILES['upfile']['error'] !== UPLOAD_ERR_OK) {
    $msg = [
      UPLOAD_ERR_INI_SIZE => 'php.iniのupload_max_filesize制限を越えています．',
      UPLOAD_ERR_FORM_SIZE => 'HTMLのMAX_FILE_SIZE制限を越えています．',
      UPLOAD_ERR_PARTIAL => 'ファイルが一部しかアップロードされていません．',
      UPLOAD_ERR_NO_FILE => 'ファイルはアップロードされませんでした．',
      UPLOAD_ERR_NO_TMP_DIR => '一時保存フォルダが存在しません．',
      UPLOAD_ERR_CANT_WRITE => 'ディスクへの書き込みに失敗しました．',
      UPLOAD_ERR_EXTENSION => '拡張モジュールによってアップロードが中断されました．',
    ];
    $err_msg = $msg[$_FILES['upfile']['error']];
  // 拡張子が許可されたものであるかを判定
  } elseif (!in_array($lower_ext, $perm)) {
    $err_msg = '画像以外のファイルはアップロードできません．';
  // エラーチェックを終えたら、アップロード処理
  } else {
    $img_info = @getimagesize($_FILES['upfile']['tmp_name']);
    if (!img_info) {
      $err_msg = 'ファイルの内容が画像ではありません．';
    } elseif (!($img_info[0] === 32 && $img_info[1] === 32)) {
      $err_msg = '32×32の画像をアップロードして下さい．';
    } else {
      // 以前にアップロードしたファイルが存在すれば削除
      remove_icon($_SERVER['DOCUMENT_ROOT'].'/images', $_SESSION['id']);
      $src = $_FILES['upfile']['tmp_name'];
      $dest = 'user_' . $_SESSION['id'] . '.' . $lower_ext;
      if (!move_uploaded_file($src, $_SERVER['DOCUMENT_ROOT'].'/images/'.$dest)) {
        $err_msg = 'アップロード処理に失敗しました．';
      }
      // 完了した投稿のハッシュ値をセッションに保存
      $_SESSION['content_hash'] = $content_hash;
    }
  }

  // エラーが発生した場合
  if (isset($err_msg)) {
    //「403 Forbidden」を送信
    http_response_code(403);
  } else {
    $ok = true;
  }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <title>ユーザー情報</title>
  <meta charset="UTF-8">
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
        <li class="nav-item active">
          <a class="nav-link" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/profile.php"><span class="oi oi-person mr-1" title="person" area-hidden="true"></span>Profile</a>
        </li>
        <li class="nav-item">
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

  <div class="container">
    <div class="row" style="height: 60px;"></div>

    <div class="row">
      <div class="container-fluid">
        <div class="row">
          <h1 class="mx-auto">ユーザー情報</h1>
        </div>

        <div class="row justify-content-center mt-5">
        <img src="http://<?= e($_SERVER['HTTP_HOST']) ?>/images/<?= e(get_icon($_SERVER['DOCUMENT_ROOT'].'/images', $_SESSION['id'])) ?>" class="img-thumbnail" alt="user image">
        </div>

        <div class="row justify-content-center mt-3">
          Username : <?= e($_SESSION['username']) ?>
        </div>

        <div class="row justify-content-center mt-3">
          <form method="post" action="" enctype="multipart/form-data">
            <div class="form-group">
              <label for="upfile" class="col-form-label">アイコン画像のアップロード</label>
              <input type="file" name="upfile" class="form-control-file" id="upfile">
              <small class="text-muted">(32x32 only, png, jpg, jpeg)</small>
            </div>
            <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
            <input type="hidden" name="max_file_size" value="1000000" />
            <div style="text-align: center; margin-top: 20px;"><button type="submit" class="btn btn-primary">アップロード</button></div>
          </form>
        </div>

        <div class="row justify-content-center mt-3 text-danger">
          <?php
            if (http_response_code() === 403) {
              print "<p>{$err_msg}</p>";
            } else if ($ok) {
              print "<p>アップロードが完了しました．</p>";
            }
          ?>
      </div>

      <div class="row justify-content-center">
        <a href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php">戻る</a>
      </div>

    </div>
  </div>
</body>
</html>
