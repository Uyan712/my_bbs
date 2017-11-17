<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/DbManager.php';

require_logined_session();

// エラー保持用変数
$errors = [];

// スレッドIDとタイトルを取得
$thread_id = filter_input(INPUT_GET, 'id', FILTER_CALLBACK, array('options' => 'e'));
$thread_title = filter_input(INPUT_GET, 'title', FILTER_CALLBACK, array('options' => 'e'));

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // ユーザーIDと投稿内容を取得
  $user_id = e($_SESSION['id']);
  $content = filter_input(INPUT_POST, 'content', FILTER_CALLBACK, array('options' => 'e'));

  // トークンの検証
  $token = filter_input(INPUT_POST, 'token', FILTER_CALLBACK, array('options' => 'e'));
  if (!validate_token($token)) {
    $errors[] = '不正なアクセスです．';
  }
  // 空文字列検証
  if (mb_strlen($content) === 0) {
    $errors[] = '入力がありません．';
  }

  // エラーが起こっていない場合
  if (count($errors) === 0) {
    try {
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
    } catch (PDOException $e) {
      $db->rollback();
      $errors[] = '投稿できませんでした．';
      print "エラーメッセージ：{$e->getMessage()}";
    }
  }
}

try {
  // データベースへの接続を確立
  if (!isset($db)) {
    $db = getDb();
  }
  // SELECT命令の実行
  $stmt = $db->prepare('SELECT `user_id`, `content`, `updated_at` FROM `messages` where `messages`.`thread_id` = :thread_id ORDER BY `messages`.`posted_at` ASC;');
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
  <title></title>
  <meta charset="UTF-8" />
</head>
<body>
  <h1>Thread</h1>
  <h2><?= e($thread_title) ?></h2>

  <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
    <div>
      <p><?= nl2br(e($row['content'])) ?></p>
      <p>ユーザー：<?= e($row['user_id']) ?>，投稿時間：<?= e($row['updated_at']) ?></p>
    </div>
  <?php } ?>

  <form method="post" action="">
    投稿内容：<br>
    <textarea cols="60" rows="10" name="content" value="" required></textarea><br>
    <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
    <input type="submit" value="送信">
  </form>

  <?php
    if (count($errors) !== 0) {
      foreach($errors as $e) {
        print "<p style=\"color: red;\">{$e}</p>";
      }
    }
  ?>

  <p><a href="./">戻る</a></p>
</body>
</html>
