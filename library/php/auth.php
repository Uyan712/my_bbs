<?php

/**
 * CSRFトークン生成に使うハッシュアルゴリズム
 */
const CSRF_TOKEN_HASHALGO = 'sha256';

/**
 * ログイン状態によってリダイレクトを行うsession_startのラッパー関数
 * 初回時または失敗時にはヘッダを送信してexitする
 */
function require_unlogined_session() {
  // セッション開始
  @session_start();
  // ログインしていれば / に遷移
  if (isset($_SESSION['username'])) {
    header('Location: /');
    exit;
  }
}

function require_logined_session() {
  // セッション開始
  @session_start();
  // ログインしていなければ /login.php に遷移
  if (!isset($_SESSION['username'])) {
    header('Location: /login.php');
    exit;
  }
}

/**
 * CSRFトークンの生成
 *
 * @return string トークン
 */
function generate_token() {
  return hash(CSRF_TOKEN_HASHALGO, session_id());
}

/**
 * CSRFトークンの検証
 *
 * @param string $token
 * @return bool 検証結果
 */
function validate_token($token) {
  return $token === generate_token();
}
