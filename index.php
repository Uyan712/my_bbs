<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/DbManager.php';
require_logined_session();

try {
  // データベースへの接続を確立
  $db = getDb();
  // SELECT命令の実行
  $stmt = $db->prepare("SELECT id, title, number_of_comments, created_at, updated_at FROM threads ORDER BY updated_at DESC;");
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
  <title>掲示板</title>
  <meta charset="UTF-8" />
</head>
<body>
  <h1>ようこそ，<?= e($_SESSION['id']) ?>番の<?= e($_SESSION['username']) ?>さん！</h1>

  <p><a href="./thread_create.php">新規スレッドを作成</a></p>

  <ul>
  <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
    <li><a href="./thread.php?id=<?= e($row['id']) ?>&title=<?= e($row['title']) ?>"><?= e($row['title']) ?></a>，コメント：<?= e($row['number_of_comments']) ?>，作成：<?= e($row['created_at']) ?>，更新：<?= e($row['updated_at']) ?></li>
  <?php } ?>
  </ul>

  <a href="/logout.php?token=<?= e(generate_token()) ?>">ログアウト</a>
</body>
</html>
