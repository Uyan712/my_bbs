<?php

require_once __DIR__ . '/library/php/auth.php';
require_logined_session();

// CSRFトークンを検証
if (!validate_token(filter_input(INPUT_GET, 'token'))) {
    // 「400 Bad Request」
    header('Content-Type: text/plain; charset=UTF-8', true, 400);
    exit('トークンが無効です');
}

// セッション変数を空に
$_SESSION = [];

// セッションクッキーが存在する場合には破棄
if (isset($_COOKIE[session_name()])) {
  $cparam = session_get_cookie_params();
  setcookie(session_name(), '', time() - 3600,
    $cparam['path'], $cparam['domain'],
    $cparam['secure'], $cparam['httponly']);
}

// セッションを破棄
session_destroy();
// ログアウト完了後に /login.php に遷移
header('Location: http://' . $_SERVER['HTTP_HOST'] . '/login.php');
