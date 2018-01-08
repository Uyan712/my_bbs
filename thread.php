<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';

require_logined_session();

// エラー保持用変数
$errors = [];
// 投稿内容のハッシュ値
$content_hash = '';
// 二重送信フラグ
$double_transmission_flag = false;

// スレッドIDとタイトルを取得
$thread_id = filter_input(INPUT_GET, 'id');
$thread_title = filter_input(INPUT_GET, 'title');

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ユーザーIDを取得
  $user_id = $_SESSION['id'];

  // トークンの検証
  $token = filter_input(INPUT_POST, 'token');
  if (!validate_token($token)) {
    $errors[] = '不正なアクセスです．';
  }

  // 新規投稿：null
  // 削除　　：'delete'
  // 編集　　：'edit'
  $deleteOrEdit = filter_input(INPUT_POST, 'deleteOrEdit');

  // 投稿内容を取得
  $content = filter_input(INPUT_POST, 'content');

  // 二重送信の検証
  $content_hash = md5($content);
  if ($deleteOrEdit === 'delete') {
    $content_hash = md5(session_id() . filter_input(INPUT_POST, 'message-id'));
  }
  if (isset($_SESSION['content_hash'])) {
    if ($_SESSION['content_hash'] === $content_hash) {
      $double_transmission_flag = true;
    }
  }

  // 空文字列検証
  if ($deleteOrEdit !== 'delete') {
    if (mb_strlen($content) === 0) {
      $errors[] = '入力がありません．';
    }
  }

  // エラーが起こっていない場合
  if (count($errors) === 0 && !$double_transmission_flag) {
    try {
      // 新規投稿
      if ($deleteOrEdit === null) {
        // データベースへの接続を確立
        $db = getDb();
        // トランザクション開始
        $db->beginTransaction();
        // メッセージの書き込み
        $stmt = $db->prepare('INSERT INTO `messages` (`user_id`, `thread_id`, `content`) VALUES (:user_id, :thread_id, :content)');
        $stmt->bindValue(':user_id', $user_id);
        $stmt->bindValue(':thread_id', $thread_id);
        $stmt->bindValue(':content', $content);
        $stmt->execute();
        // コメント数と更新時間を更新
        $stmt = $db->prepare('UPDATE `threads` SET `number_of_comments` = `number_of_comments` + 1, `updated_at` = CURRENT_TIMESTAMP WHERE `threads`.`id` = :thread_id');
        $stmt->bindValue(':thread_id', $thread_id);
        $stmt->execute();
        // トランザクション終了
        $db->commit();
      // 投稿内容編集
      } elseif ($deleteOrEdit === 'edit') {
        // 変更するメッセージのIDを取得
        $message_id = filter_input(INPUT_POST, 'message-id');
        // データベースへの接続を確立
        $db = getDb();
        // トランザクション開始
        $db->beginTransaction();
        // メッセージの更新
        $stmt = $db->prepare('UPDATE `messages` SET `content` = :content, `updated_at` = CURRENT_TIMESTAMP WHERE `messages`.`id` = :message_id');
        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':message_id', $message_id);
        $stmt->execute();
        // 更新時間を更新
        $stmt = $db->prepare('UPDATE `threads` SET `updated_at` = CURRENT_TIMESTAMP WHERE `threads`.`id` = :thread_id');
        $stmt->bindValue(':thread_id', $thread_id);
        $stmt->execute();
        // トランザクション終了
        $db->commit();
      // 投稿内容削除
      } else {
        // 変更するメッセージのIDを取得
        $message_id = filter_input(INPUT_POST, 'message-id');
        // データベースへの接続を確立
        $db = getDb();
        // トランザクション開始
        $db->beginTransaction();
        // メッセージの削除
        $stmt = $db->prepare('DELETE FROM `messages` WHERE `messages`.`id` = :message_id');
        $stmt->bindValue(':message_id', $message_id);
        $stmt->execute();
        // 更新時間を更新
        $stmt = $db->prepare('UPDATE `threads` SET `number_of_comments` = `number_of_comments` - 1, `updated_at` = CURRENT_TIMESTAMP WHERE `threads`.`id` = :thread_id');
        $stmt->bindValue(':thread_id', $thread_id);
        $stmt->execute();
        // トランザクション終了
        $db->commit();
      }
    } catch (PDOException $e) {
      $db->rollback();
      $errors[] = '送信に失敗しました．';
      print "エラーメッセージ：{$e->getMessage()}";
    } finally {
      // 投稿内容のハッシュ値をセッションに保存
      $_SESSION['content_hash'] = $content_hash;
    }
  }
}

try {
  // データベースへの接続を確立
  if (!isset($db)) {
    $db = getDb();
  }
  // SELECT命令の実行
  $sql = 'SELECT `user_id`, `username`, `content`, `messages`.`id`, `messages`.`updated_at` FROM `messages` LEFT JOIN `users` ON `messages`.`user_id` = `users`.`id` where `messages`.`thread_id` = :thread_id ORDER BY `messages`.`posted_at` ASC';
  $stmt = $db->prepare($sql);
  $stmt->bindValue(':thread_id', $thread_id);
  $stmt->execute();
} catch (PDOException $e) {
  print "エラーメッセージ：{$e->getMessage()}";
} finally {
  $db = null;
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <title><?= e($thread_title) ?></title>
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

  <!-- JavaScript -->
  <script type="text/javascript">
    $(function() {
      // Tooltipの設定
      $('[data-toggle="tooltip"]').tooltip()

      // Radioボタンによってテキストエリアのプロパティを制御
      function mouse_click_event_delete() {
        var p_form = $(this).parents('form');
        p_form.find('.edit-content').prop('disabled', true);
      }
      function mouse_click_event_edit() {
        var p_form = $(this).parents('form');
        p_form.find('.edit-content').prop('disabled', false);
      }

      var delete_buttons = $('.delete-button');
      for (var i = 0; i < delete_buttons.length; i++) {
        delete_buttons[i].addEventListener('click', mouse_click_event_delete, false);
      }
      var edit_buttons = $('.edit-button');
      for (var i = 0; i < edit_buttons.length; i++) {
        edit_buttons[i].addEventListener('click', mouse_click_event_edit, false);
      }
    });
  </script>

  <!-- Style -->
  <style type="text/css">
    .little-down {
      position: relative;
      top: 4px;
    }
  </style>
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
    <div class="row justify-content-center mb-5"><h1><?= e($thread_title) ?></h1></div>

    <!-- 投稿内容 -->
    <div class="row justify-content-center">
    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
      <div class="media w-100 my-2">
        <img class="mx-2 little-down" src="http://<?= e($_SERVER['HTTP_HOST']) ?>/images/<?= e(get_icon($_SERVER['DOCUMENT_ROOT'].'/images', $row['user_id'])) ?>" width="32" height="32" alt="user image">
        <div class="media-body">
          <div class="border rounded">
            <div class="d-flex justify-content-between bg-light">
              <div class="pl-3 bg-light">
                <h6 class="mt-0 mb-0"><?= e($row['username']) ?></h6>
                <small class="mt-0 text-muted"><?= e($row['updated_at']) ?></small>
              </div>
              <?php if ($row['username'] === $_SESSION['username']) { ?>
                <div class="pl-3">
                  <div data-toggle="tooltip" data-placement="top" title="編集"><a href="#edit-<?= e($row['id']) ?>" data-toggle="collapse"><span class="oi oi-pencil m-1" title="pencil"></span></a></div>
                </div>
              <?php } ?>
            </div>
            <p class="border border-bottom-0 border-left-0 border-right-0 pt-3 pl-3"><?= nl2br(e($row['content'])) ?></p>

            <?php if ($row['username'] === $_SESSION['username']) { ?>
              <div class="collapse" id="edit-<?= e($row['id']) ?>">
                <div class="card">
                  <div class="card-header bg-secondary text-white">投稿内容の編集</div>
                  <div class="card-body">
                    <form method="post" action="" id="editForm<?= e($row['id']) ?>">
                      <div class="form-group ml-4">
                        <div class="form-check">
                          <input class="form-check-input delete-button" type="radio" name="deleteOrEdit" id="deleteFlag<?= e($row['id']) ?>" value="delete">
                          <label class="form-check-label" for="deleteFlag<?= e($row['id']) ?>">削除</label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input edit-button" type="radio" name="deleteOrEdit" id="editFlag<?= e($row['id']) ?>" value="edit">
                          <label class="form-check-label" for="editFlag<?= e($row['id']) ?>">編集</label>
                        </div>
                      </div>
                      <div class="form-group">
                        <label for="editForm">変更内容</label>
                        <textarea class="form-control edit-content" name="content" rows="5" required disabled></textarea>
                      </div>
                      <input type="hidden" name="message-id" value="<?= e($row['id']) ?>">
                      <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
                      <button class="btn btn-primary" type="submit">送信</button>
                    </form>
                  </div>
                </div>
              </div>
            <?php } ?>

          </div>
        </div>
      </div>
      <hr>
    <?php } ?>
    </div>

    <!-- 投稿フォーム -->
    <div class="row justify-content-center">
      <form class="w-50" method="post" action="">
          <div class="form-group row">
            <label for="inputContent" class="col-form-label">投稿内容</label>
            <textarea class="form-control" name="content" id="inputContent" rows="8" required></textarea>
          </div>
        <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
        <div style="text-align: center; margin-top: 20px;"><button type="submit" class="btn btn-primary">送信</button></div>
      </form>
    </div>

    <!-- エラー表示 -->
    <div class="row justify-content-center text-danger mt-3">
    <?php
      if (count($errors) !== 0) {
        foreach($errors as $e) {
          print "<p>{$e}</p>";
        }
      }
    ?>
  </div>

  <div class="row justify-content-center">
    <p><a href="http://<?= $_SERVER['HTTP_HOST'] ?>/index.php">戻る</a></p>
  </div>
</body>
</html>
