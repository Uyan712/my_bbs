<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';

require_logined_session();

// 登録完了フラグ
$ok = false;
// 投稿内容のハッシュ値
$content_hash = '';
// 二重送信フラグ
$double_transmission_flag = false;

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // エラー保持用変数
  $errors = [];

  // タイトルと書き込みを取得
  $thread_title = isset($_POST['title']) ? $_POST['title'] : false;
  $content = isset($_POST['content']) ? $_POST['content'] : false;

  // 入力があるか
  if (!$thread_title) {
    $errors[] = 'タイトルを入力して下さい．';
  } else if (!$content) {
    $errors[] = '書き込みを入力して下さい．';
  }
  
  // 生成されたトークンを取得
  $token = isset($_POST['token']) ? e($_POST['token']) : false;
  if (!validate_token($token)) {
    $errors[] = '不正なアクセスです．';
  }

  // 二重投稿検証
  $content_hash = md5($content);
  if (isset($_SESSION['content_hash'])) {
    if ($_SESSION['content_hash'] === $content_hash) {
      $double_transmission_flag = true;
    }
  }

  // エラーがない場合はデータベースアクセス
  if (count($errors) === 0 && !$double_transmission_flag) {
    try {
      // ユーザーIDを取得
      $user_id = e($_SESSION['id']);
      // データベースへの接続を確立
      $db = getDb();
      // トランザクション開始
      $db->beginTransaction();
      // スレッドを登録
      $stmt = $db->prepare('INSERT INTO `threads` (title) VALUES (:thread_title)');
      $stmt->bindValue(':thread_title', $thread_title);
      $stmt->execute();
      // メッセージの書き込み
      $stmt = $db->prepare('INSERT INTO `messages` (`user_id`, `thread_id`, `content`) VALUES (:user_id, (SELECT id from `threads` where `title` = :thread_title), :content)');
      $stmt->bindValue(':user_id', $user_id);
      $stmt->bindValue(':thread_title', $thread_title);
      $stmt->bindValue(':content', $content);
      $stmt->execute();
      // コメント数と更新時間を更新
      $stmt = $db->prepare('UPDATE `threads` SET `number_of_comments` = `number_of_comments` + 1, `updated_at` = CURRENT_TIMESTAMP WHERE `threads`.`title` = :title');
      $stmt->bindValue(':title', $thread_title);
      $stmt->execute();
      // トランザクション終了
      $db->commit();
    } catch (PDOException $e) {
      $db->rollback();
      $errors[] = '作成に失敗しました．';
    } finally {
      $db = null;
      // 完了した投稿のハッシュ値をセッションに保存
      $_SESSION['content_hash'] = $content_hash;
    }
  }

  // エラーが発生した場合
  if (count($errors) !== 0) {
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
  <title>新規スレッド作成</title>
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

  <div class="container-fluid">
    <div class="row justify-content-center" style="margin-top: 60px; height: 60px;"><h1>新規スレッド作成</h1></div>

    <div class="row justify-content-center">
      <p>タイトルと最初の書き込みを入力して下さい．</p>
    </div>

    <div class="row justify-content-center text-danger">
      <?php
        if (http_response_code() === 403) {
          foreach($errors as $e) {
            print "<p>{$e}</p>";
          }
        } else if ($ok) {
          print "<p>作成が完了しました．</p>";
        }
      ?>
    </div>

    <div class="row justify-content-center">
      <form class="w-25" method="post" action="">
        <div class="form-group">
          <label for="inputTitle" class="col-form-label">タイトル</label>
          <div>
            <input type="text" name="title" class="form-control" id="inputTitle">
          </div>
        </div>
        <div class="form-group">
          <label for="inputContent" class="col-form-label">投稿内容</label>
          <textarea class="form-control" rows="8" name="content" id="inputContent" required></textarea>
        </div>
        <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
        <div style="text-align: center; margin-top: 20px;"><button type="submit" class="btn btn-primary">作成</button></div>
      </form>
    </div>

    <div class="row justify-content-center mt-3">
      <a href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php">戻る</a>
    </div>
  </div>

</body>
</html>
