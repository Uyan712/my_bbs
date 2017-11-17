<?php

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/utils.php';
require_once __DIR__ . '/DbManager.php';

// 登録完了フラグ
$ok = false;

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // エラー保持用変数
  $errors = [];

  // ユーザから受け取ったユーザ名とパスワードを取得
  $username = isset($_POST['username']) ? e($_POST['username']) : false;
  $password = isset($_POST['password']) ? e($_POST['password']) : false;

  // ユーザー名/パスワードのペアが入力されているか
  $input_flag = $username && $password;
  if (!$input_flag) {
    $errors[] = '記入されていない項目があります．';
  }
  
  // 生成されたトークンを取得
  $token= isset($_POST['token']) ? e($_POST['token']) : false;
  if (!validate_token($token)) {
    $errors[] = '不正なアクセスです．';
  }

  // エラーがない場合はデータベースアクセス
  if (count($errors) === 0) {
    try {
      // データベースへの接続を確立
      $db = getDb();
      // パスワードのハッシュ値を計算
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      // データベースに登録
      $stmt = $db->prepare('INSERT INTO `users` (`username`, `password`) VALUES (:username, :password)');
      $stmt->bindValue(':username', $username);
      $stmt->bindValue(':password', $hashed_password);
      $stmt->execute();
    } catch (PDOException $e) {
      // 使用されているユーザー名
      if ($e->getCode() === '23000') {
        $errors[] = 'すでに使用されているUsernameです．';
      }
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
  <title>会員登録</title>
  <meta charset="UTF-8">
</head>
<body>
  <p>会員情報を記入して下さい。</p>
  <form method="post" action="">
    Username : <input type="text" name="username" value=""><br>
    Password : <input type="password" name="password" value=""><br>
    <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
    <input type="submit" value="登録">
  </form>
  <a href="./">戻る</a>
  <?php
    if (http_response_code() === 403) {
      foreach($errors as $e) {
        print "<p style=\"color: red;\">{$e}</p>";
      }
    } else if ($ok) {
      print "<p style=\"color: red;\">登録が完了しました．</p>";
    }
  ?>
</body>
</html>
