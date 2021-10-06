<?php

//データベースの接続情報
define('DB_HOST', 'db');
define('DB_USER', 'test');
define('DB_PASS', 'test');
define('DB_NAME', 'test');

$posts = [];
$errors = [];

session_start();
//管理者としてログインしているか確認
if (empty($_SESSION['admin_login']) || $_SESSION['admin_login'] !== true) {
  //ログインページへリダイレクト
  header("Location: ./admin.php");
  exit;
}

//データベースに接続
try {
  $option = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => false
  ];

  $pdo = new PDO('mysql:charset=UTF8;dbname=' . DB_NAME . ';host=' . DB_HOST, DB_USER, DB_PASS, $option);
} catch (PDOException $e) {
  //接続エラーのときエラー内容を取得する
  $errors[] = $e->getMessage();
}

if (!empty($_GET['id']) && empty($_POST['id'])) {
  //投稿を取得するコードが入る
  //SQL作成
  $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = :id");

  //値をセット
  $stmt->bindValue(':id', $_GET['id'], PDO::PARAM_INT);

  //SQLクエリの実行
  $stmt->execute();

  //表示するデータを取得
  $posts = $stmt->fetch();

  if (empty($posts)) {
    header("Location: ./admin.php");
    exit;
  }
} elseif (!empty($_POST['id'])) {
  // 空白除去
  $view_name = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['view_name']);
  $message = preg_replace('/\A[\p{C}\p{Z}]++|[\p{C}\p{Z}]++\z/u', '', $_POST['message']);

  //表示名の入力チェック
  if (empty($view_name)) {
    $errors[] = '表示名を入力してください';
  }

  //メッセージの入力チェック
  if (empty($message)) {
    $errors[] = '一言メッセージを入力してください';
  } else {
    //文字数を確認
    if (100 < mb_strlen($message, 'UTF-8')) {
      $errors[] = 'ひと言メッセージは100文字以内で入力してください';
    }
  }

  if (empty($errors)) {
    //トランザクションの開始
    $pdo->beginTransaction();

    try {
      //SQL作成
      $stmt = $pdo->prepare("UPDATE posts SET username = :view_name, message = :message WHERE id = :id");

      //値をセット
      $stmt->bindParam(':view_name', $view_name, PDO::PARAM_STR);
      $stmt->bindParam(':message', $message, PDO::PARAM_STR);
      $stmt->bindValue(':id', $_POST['id'], PDO::PARAM_INT);

      //SQLクエリを実行
      $stmt->execute();

      //コミット(処理の実行)
      $res = $pdo->commit();
    } catch (Exception $e) {
      //エラーが発生したときはロールバック
      $pdo->rollBack();
    }

    //更新に成功したら一覧に戻る
    if ($res) {
      header("Location: ./admin.php");
      exit;
    }
  }
}

//データベースの接続を閉じる
$stmt = null;
$pdo = null;
?>
<!DOCTYPE html>
<html lang="ja">

<head>
  <meta charset="utf-8">
  <title>ひと言掲示板 管理ページ</title>
  <style>
    /*------------------------------

Reset Style

------------------------------*/
    html,
    body,
    div,
    span,
    object,
    iframe,
    h1,
    h2,
    h3,
    h4,
    h5,
    h6,
    p,
    blockquote,
    pre,
    abbr,
    address,
    cite,
    code,
    del,
    dfn,
    em,
    img,
    ins,
    kbd,
    q,
    samp,
    small,
    strong,
    sub,
    sup,
    var,
    b,
    i,
    dl,
    dt,
    dd,
    ol,
    ul,
    li,
    fieldset,
    form,
    label,
    legend,
    table,
    caption,
    tbody,
    tfoot,
    thead,
    tr,
    th,
    td,
    article,
    aside,
    canvas,
    details,
    figcaption,
    figure,
    footer,
    header,
    hgroup,
    menu,
    nav,
    section,
    summary,
    time,
    mark,
    audio,
    video {
      margin: 0;
      padding: 0;
      border: 0;
      outline: 0;
      font-size: 100%;
      vertical-align: baseline;
      background: transparent;
    }

    body {
      line-height: 1;
    }

    article,
    aside,
    details,
    figcaption,
    figure,
    footer,
    header,
    hgroup,
    menu,
    nav,
    section {
      display: block;
    }

    nav ul {
      list-style: none;
    }

    blockquote,
    q {
      quotes: none;
    }

    blockquote:before,
    blockquote:after,
    q:before,
    q:after {
      content: '';
      content: none;
    }

    a {
      margin: 0;
      padding: 0;
      font-size: 100%;
      vertical-align: baseline;
      background: transparent;
    }

    /* change colours to suit your needs */
    ins {
      background-color: #ff9;
      color: #000;
      text-decoration: none;
    }

    /* change colours to suit your needs */
    mark {
      background-color: #ff9;
      color: #000;
      font-style: italic;
      font-weight: bold;
    }

    del {
      text-decoration: line-through;
    }

    abbr[title],
    dfn[title] {
      border-bottom: 1px dotted;
      cursor: help;
    }

    table {
      border-collapse: collapse;
      border-spacing: 0;
    }

    hr {
      display: block;
      height: 1px;
      border: 0;
      border-top: 1px solid #cccccc;
      margin: 1em 0;
      padding: 0;
    }

    input,
    select {
      vertical-align: middle;
    }

    /*------------------------------

Common Style

------------------------------*/
    body {
      padding: 50px;
      font-size: 100%;
      font-family: 'ヒラギノ角ゴ Pro W3', 'Hiragino Kaku Gothic Pro', 'メイリオ', Meiryo, 'ＭＳ Ｐゴシック', sans-serif;
      color: #222;
      background: #f7f7f7;
    }

    a {
      color: #007edf;
      text-decoration: none;
    }

    a:hover {
      text-decoration: underline;
    }

    h1 {
      margin-bottom: 30px;
      font-size: 100%;
      color: #222;
      text-align: center;
    }


    /*-----------------------------------
入力エリア
-----------------------------------*/

    label {
      display: block;
      margin-bottom: 7px;
      font-size: 86%;
    }

    input[type="text"],
    textarea {
      margin-bottom: 20px;
      padding: 10px;
      font-size: 86%;
      border: 1px solid #ddd;
      border-radius: 3px;
      background: #fff;
    }

    input[type="text"] {
      width: 200px;
    }

    textarea {
      width: 50%;
      max-width: 50%;
      height: 70px;
    }

    input[type="submit"] {
      appearance: none;
      -webkit-appearance: none;
      padding: 10px 20px;
      color: #fff;
      font-size: 86%;
      line-height: 1.0em;
      cursor: pointer;
      border: none;
      border-radius: 5px;
      background-color: #37a1e5;
    }

    input[type=submit]:hover,
    button:hover {
      background-color: #2392d8;
    }

    hr {
      margin: 20px 0;
      padding: 0;
    }

    .success_message {
      margin-bottom: 20px;
      padding: 10px;
      color: #48b400;
      border-radius: 10px;
      border: 1px solid #4dc100;
    }

    .error_message {
      margin-bottom: 20px;
      padding: 10px;
      color: #ef072d;
      list-style-type: none;
      border-radius: 10px;
      border: 1px solid #ff5f79;
    }

    .success_message,
    .error_message li {
      font-size: 86%;
      line-height: 1.6em;
    }

    .btn_cancel {
      display: inline-block;
      margin-right: 10px;
      padding: 10px 20px;
      color: #555;
      font-size: 86%;
      border-radius: 5px;
      border: 1px solid #999;
    }

    .btn_cancel:hover {
      color: #999;
      border-color: #999;
      text-decoration: none;
    }


    /*-----------------------------------
掲示板エリア
-----------------------------------*/

    article {
      margin-top: 20px;
      padding: 20px;
      border-radius: 10px;
      background: #fff;
    }

    article.reply {
      position: relative;
      margin-top: 15px;
      margin-left: 30px;
    }

    article.reply::before {
      position: absolute;
      top: -10px;
      left: 20px;
      display: block;
      content: "";
      border-top: none;
      border-left: 7px solid #f7f7f7;
      border-right: 7px solid #f7f7f7;
      border-bottom: 10px solid #fff;
    }

    .info {
      margin-bottom: 10px;
    }

    .info h2 {
      display: inline-block;
      margin-right: 10px;
      color: #222;
      line-height: 1.6em;
      font-size: 86%;
    }

    .info time {
      color: #999;
      line-height: 1.6em;
      font-size: 72%;
    }

    article p {
      color: #555;
      font-size: 86%;
      line-height: 1.6em;
    }

    @media only screen and (max-width: 1000px) {

      body {
        padding: 30px 5%;
      }

      input[type="text"] {
        width: 100%;
      }

      textarea {
        width: 100%;
        max-width: 100%;
        height: 70px;
      }
    }
  </style>
</head>

<body>
  <h1>ひと言掲示板 管理ページ</h1>
  <?php if (!empty($errors)) : ?>
    <ul class="error_message">
      <?php foreach ($errors as $error) : ?>
        <li><?php echo $error; ?></li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
  <form method="post">
    <div>
      <label for="view_name">表示名</label>
      <input type="text" id="view_name" name="view_name" value="<?php if (!empty($posts['username'])) {
                                                                  echo $posts['username'];
                                                                } ?>">
    </div>
    <div>
      <label for="message">ひと言メッセージ</label>
      <textarea name="message" id="message"><?php if (!empty($posts['message'])) {
                                              echo $posts['message'];
                                            } ?></textarea>
    </div>
    <a class="btn_cancel" href="admin.php">キャンセル</a>
    <input type="submit" name="btn_submit" value="更新">
    <input type="hidden" name="id" value="<?php if (!empty($posts['id'])) {
                                            echo $posts['id'];
                                          } ?>">
  </form>
</body>

</html>
