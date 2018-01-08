<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';

require_unlogined_session();

// POSTメソッドのときのみ実行
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // ユーザから受け取ったユーザ名とパスワードを取得
  $username = filter_input(INPUT_POST, 'username');
  $password = filter_input(INPUT_POST, 'password');
  
  // 生成されたトークンを取得
  $token = filter_input(INPUT_POST, 'token');

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
    // ログイン完了後に index.php に遷移
    header('Location: http://' . $_SERVER['HTTP_HOST'] . '/index.php');
  // 認証が失敗したとき
  } else {
    //「403 Forbidden」を送信
    http_response_code(403);
  }
}

?>

<!DOCTYPE html>
<html lang="ja">
<head>
  <title>ログイン</title>
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
          <h1 class="mx-auto">掲示板へようこそ！</h1>
        </div>

        <div class="row"><p class="mx-auto">ログインして下さい．</p></div>

        <div class="row justify-content-center mt-3">
          <form class="w-100 " method="post" action="">
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
            <input type="hidden" name="token" value="<?= e(generate_token()) ?>">
            <div style="text-align: center; margin-top: 20px;"><button type="submit" class="btn btn-primary">ログイン</button></div>
          </form>
        </div>

      <div class="row justify-content-center mt-3">
        <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/registration.php">ユーザー登録</a>
      </div>

      <div class="row justify-content-center mt-2 text-danger">
        <?php if (http_response_code() === 403): ?>
          <p>ユーザ名またはパスワードが間違っています．</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</body>
</html>
