<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/DbManager.php';

require_logined_session();

// 登録完了フラグ
$ok = false;

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // エラー保持用変数
  $errors = [];

  // タイトルと書き込みを取得
  $thread_title = isset($_POST['title']) ? e($_POST['title']) : false;
  $content = isset($_POST['content']) ? e($_POST['content']) : false;

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

  // エラーがない場合はデータベースアクセス
  if (count($errors) === 0) {
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
  <title>スレッド作成</title>
  <meta charset="UTF-8">
</head>
<body>
  <p>タイトルと最初の書き込みを入力して下さい．</p>
  <form method="post" action="">
    タイトル：<input type="text" name="title" value=""><br>
    投稿内容：<br>
    <textarea cols="60" rows="10" name="content" value="" required></textarea><br>
    <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
    <input type="submit" value="作成">
  </form>
  <a href="./">戻る</a>
  <?php
    if (http_response_code() === 403) {
      foreach($errors as $e) {
        print "<p style=\"color: red;\">{$e}</p>";
      }
    } else if ($ok) {
      print "<p style=\"color: red;\">作成が完了しました．</p>";
    }
  ?>
</body>
</html>
