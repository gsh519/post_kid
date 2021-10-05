<?php

define('DB_HOST', 'db');
define('DB_USER', 'test');
define('DB_PASS', 'test');
define('DB_NAME', 'test');

//変数の初期化
$csv_data = null;
$sql = null;
$pdo = null;
$option = null;
$posts = [];
$limit = null;
$stmt = null;

session_start();

//取得件数
if (!empty($_GET['limit'])) {
  if ($_GET['limit'] === 10) {
    $limit = 10;
  } elseif ($_GET['limit'] === 30) {
    $limit = 30;
  }
}

if (!empty($_SESSION['admin_login']) && $_SESSION['admin_login'] === true) {
  //データベースに接続
  try {
    $option = [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
    ];

    $pdo = new PDO('mysql:charset=UTF8;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);

    //メッセージのデータを取得する
    if (!empty($limit)) {
      $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at ASC LIMIT :limit");

      //値をセット
      $stmt->bindValue(':limit', $_GET['limit'], PDO::PARAM_INT);
    } else {
      $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at ASC");
    }

    $stmt->execute();
    $posts = $stmt->fetchAll();

    $stmt = null;
    $pdo = null;
  } catch (PDOException $e) {
    header("Location: ./admin.php");
    exit;
  }

  //出力の設定
  header("Content-Type: text/csv");
  header("Content-Disposition: attachment; filename=メッセージデータ.csv");
  header("Content-Transfer-Encoding: binary");

  // CSVデータを作成
  if (!empty($posts)) {

    // 1行目のラベル作成
    $csv_data .= '"ID","表示名","メッセージ","投稿日時"' . "\n";

    foreach ($posts as $post) {
      // データを1行ずつCSVファイルに書き込む
      $csv_data .= '"' . $post['id'] . '","' . $post['username'] . '","' . $post['message'] . '","' . $post['created_at'] . "\"\n";
    }
  }

  //ファイルを出力
  echo $csv_data;
} else {
  header("Location: ./admin.php");
  exit;
}

return;
