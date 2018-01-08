<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';

// 登録完了フラグ
$ok = false;

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // エラー保持用変数
  $errors = [];

  // ユーザから受け取ったユーザ名とパスワードを取得
  $username = isset($_POST['username']) ? e($_POST['username']) : false;
  $password= isset($_POST['password']) ? e($_POST['password']) : false;
  $re_password= isset($_POST['re_password']) ? e($_POST['re_password']) : false;

  // ユーザー名/パスワードのペアが入力されているか
  $input_flag = $username && $password && $re_password;
  if (!$input_flag) {
    $errors[] = '入力されていない項目があります．';
  }
  
  // 同じパスワードが入力されているか
  if ($input_flag && !($password === $re_password)) {
    $errors[] = '入力されたパスワードが一致しません．';
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
        $errors[] = 'Usernameが既に使用されています．';
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
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" type="text/css" href="./library/css/bootstrap.min.css">

  <!-- JavaScript -->
  <script src="./library/js/jquery-3.2.1.min.js"></script>
  <script src="./library/js/popper.js"></script>
  <script src="./library/js/bootstrap.min.js"></script>
</head>

<body>
  <div class="container">
    <div class="row" style="height: 60px;"></div>

    <div class="row">
      <div class="container-fluid">
        <div class="row">
          <h1 class="mx-auto">ユーザー登録</h1>
        </div>

        <div class="row justify-content-center"><p>ユーザー情報を入力して下さい．</p></div>
    
        <div class="row justify-content-center mt-3">
          <form class="w-100" method="post" action="">
            <div class="form-group row w-50 mx-auto">
              <label for="inputUsername" class="col-md-3 col-form-label">Username</label>
              <div class="col-md-9">
                <input type="text" name="username" class="form-control" id="inputUsername" placeholder="Enter Username">
              </div>
            </div>
            <div class="form-group row w-50 mx-auto">
              <label for="inputPassword" class="col-md-3 col-form-label">Password</label>
              <div class="col-md-9">
                <input type="password" name="password" class="form-control" id="inputPassword" placeholder="Enter Password">
              </div>
            </div>
            <div class="form-group row w-50 mx-auto">
              <label for="inputReEnterPassword" class="col-md-3 col-form-label align-top">Validation</label>
              <div class="col-md-9">
                <input type="password" name="re_password" class="form-control" id="inputReEnterPassword" placeholder="Re-Enter Password">
              </div>
            </div>
            <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
            <div style="text-align: center; margin-top: 20px;"><button type="submit" class="btn btn-primary">登録</button></div>
          </form>
        </div>

        <div class="row justify-content-center mt-3 text-danger">
          <?php
            if (http_response_code() === 403) {
              foreach($errors as $e) {
                print "<p>{$e}</p>";
              }
            } else if ($ok) {
              print "<p>登録が完了しました．</p>";
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
