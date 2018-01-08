<?php

require_once __DIR__ . '/library/php/auth.php';
require_once __DIR__ . '/library/php/utils.php';
require_once __DIR__ . '/library/php/DbManager.php';
require_logined_session();

try {
  // データベースへの接続を確立
  $db = getDb();
  // ページネーション関連
  $limit = 30;                        // 1ページの表示件数
  $page = 1;                          // ページ番号
  if (isset($_GET['page']) && ctype_digit($_GET['page'])) {
    $page = (int) $_GET['page'];
  }
  $offset = $limit * ($page - 1);     // オフセット
  $num_of_threads = $db->query('SELECT count(*) AS count FROM `threads`')->fetch(PDO::FETCH_ASSOC)['count'];  // スレッド数
  $num_of_pages = (int) (($num_of_threads % $limit === 0) ? floor($num_of_threads / $limit) : floor($num_of_threads / $limit) + 1);  // ページ数
  // SELECT命令の実行
  $stmt = $db->prepare('SELECT `id`, `title`, `number_of_comments`, `created_at`, `updated_at` FROM `threads` ORDER BY `updated_at` DESC LIMIT :limit OFFSET :offset;');
  $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
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
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" type="text/css" href="./library/css/bootstrap.min.css">

  <!-- Open Iconic CSS -->
  <link rel="stylesheet" type="text/css" href="./library/open-iconic/font/css/open-iconic-bootstrap.min.css">

  <!-- JavaScript -->
  <script src="./library/js/jquery-3.2.1.min.js"></script>
  <script src="./library/js/popper.js"></script>
  <script src="./library/js/bootstrap.min.js"></script>
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
        <li class="nav-item active">
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

  <div class="container-fluid">
    <div class="row justify-content-center" style="margin-top: 60px; height: 60px;"><h1>スレッド一覧</h1></div>

    <div class="row">
      <div class="container">
        <div class="row"><a href="./thread_create.php" class="btn btn-primary mx-auto mt-3 mb-5">新規スレッドを作成</a></div>

        <div class="row">
          <div class="list-group w-100">
          <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
            <a href="http://<?= e($_SERVER['HTTP_HOST']) ?>/thread.php?id=<?= urlencode($row['id']) ?>&title=<?= urlencode($row['title']) ?>" class="list-group-item list-group-item-action mb-3" style="height: 74px;">
              <div class="w-100" style="height: 14px;">
                <h5 class="text-primary"><?= e($row['title']) ?></h5>
              </div>
              <div class="w-100" style="height: 6px;">
                <hr>
              </div>
              <div class="d-flex w-100 justify-content-between" style="height: 20px;">
                <div class="d-flex-inline">
                  <small class="mr-3 align-top">作成：<?= e($row['created_at']) ?></small>
                  <small class="align-top">更新：<?= e($row['updated_at']) ?></small>
                </div>
                <div class="d-flex">
                  <div class="d-flex justify-content-between" style="width: 7em;">
                    <p style="font-size: 85%;" class="align-top">コメント</p>
                    <p style="font-size: 85%;"><span class="badge badge-pill badge-info align-text-bottom" style="font-size: 90%;"><?= e($row['number_of_comments']) ?></span></p>
                  </div>
                </div>
              </div>
            </a>
          <?php } ?>

          </div>
        </div>
      </div>
    </div>

    <div class="row justify-content-center mt-5">
      <nav aria-label="Page navigation example">
        <ul class="pagination">
          <li class="page-item">
          <a class="page-link<?php if ($page === 1) print(' text-muted') ?>" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php?page=<?= e(($page - 1) === 0 ? 1 : ($page - 1)) ?>" aria-label="Previous">
              <span aria-hidden="true">&laquo;</span>
              <span class="sr-only">Previous</span>
            </a>
          </li>
          <?php $i = 1; while ($i <= $num_of_pages) { ?>
          <li class="page-item<?php if ($i === $page) print(' active') ?>"><a class="page-link" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php?page=<?= e($i) ?>"><?= e($i) ?></a></li>
          <?php $i++; } ?>
          <li class="page-item">
            <a class="page-link<?php if ($page === $num_of_pages) print(' text-muted') ?>" href="http://<?= e($_SERVER['HTTP_HOST']) ?>/index.php?page=<?= e(($page + 1) > $num_of_pages ? $page : $page + 1) ?>" aria-label="Next">
              <span aria-hidden="true">&raquo;</span>
              <span class="sr-only">Next</span>
            </a>
          </li>
        </ul>
      </nav>
    </div>

  </div>
</body>
</html>
