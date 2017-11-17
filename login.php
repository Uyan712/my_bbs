<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/DbManager.php';

require_unlogined_session();

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ユーザから受け取ったユーザ名とパスワードを取得
  $username = filter_input(INPUT_POST, 'username', FILTER_CALLBACK, array('options' => 'e'));
  $password = filter_input(INPUT_POST, 'password', FILTER_CALLBACK, array('options' => 'e'));
  
  // 生成されたトークンを取得
  $token = filter_input(INPUT_POST, 'token', FILTER_CALLBACK, array('options' => 'e'));

  try {
    // データベースへの接続を確立
    $db = getDb();
    // SELECT命令の準備
    $stmt = $db->prepare('SELECT id, password FROM users WHERE username = :username');
    $stmt->bindValue(':username', $username);
    // SELECT命令を実行
    $stmt->execute();
    // 結果を連想配列で取得
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    print "エラーメッセージ：{$e->getMessage()}";
  } finally {
    $db = null;
  }

  // 検証対象の取得
  // ユーザ名が存在しないときだけ極端に速くなるのを防ぐ
  $target = isset($user_data['password']) ? $user_data['password'] : '$2y$10$JlkG6jZp4etKMkkOsLaau';

  // 認証が成功したとき
  if (validate_token($token) && password_verify($password, $target)) {
    // セッションIDの追跡を防ぐ
    session_regenerate_id(true);
    // IDとユーザ名をセット
    $_SESSION['id'] = $user_data['id'];
    $_SESSION['username'] = $username;
    // ログイン完了後に / に遷移
    header('Location: /');
  }
  // 認証が失敗したとき
  else {
    //「403 Forbidden」を送信
    http_response_code(403);
  }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <title>ログインページ</title>
  <meta charset="UTF-8">
</head>
<body>
  <h1>掲示板へようこそ！</h1>
  <p>ログインして下さい．</p>
  <form method="post" action="">
    Username : <input type="text" name="username" value=""><br>
    Password : <input type="password" name="password" value=""><br>
    <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
    <input type="submit" value="ログイン">
  </form>
  <a href="./registration.php">ユーザー登録</a>
  <?php if (http_response_code() === 403): ?>
    <p style="color: red;">ユーザ名またはパスワードが違います．</p>
  <?php endif; ?>
</body>
</html>
