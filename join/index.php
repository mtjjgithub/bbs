<!-- ここからPHP----------------------------------------------- -->
<?php
session_start();
require('../library.php');

// 書き直し対策
if (isset($_GET['action']) && $_GET['action'] === 'rewrite' && isset($_SESSION['form'])) {
    $form = $_SESSION['form'];
} else {
    $form = [
        'name' => '',
        'email' => '',
        'password' => ''
    ];
}

$error = [];

// フォームの内容をチェック
if ($_SERVER['REQUEST_METHOD'] === 'POST') { //フォームが送信された時に実行

    // チェック ニックネーム
    $form['name'] = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING); //受け取ったネームをチェックして代入
    if ($form['name'] === '') { //空だったら
        $error['name'] = 'blank'; //ネームエラーにする
    }

    // チェック メールアドレス
    $form['email'] = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    if ($form['email'] === '') {
        $error['email'] = 'blank';
    } else {
        $db = dbconnect();
        // 既に使われているメールアドレスかチェック
        $stmt = $db->prepare('select count(*) from members where email=?');
        if (!$stmt) {
            die($db->error);
        }
        $stmt->bind_param('s', $form['email']);
        $stmt->bind_result($cnt);
        $success = $stmt->execute();
        if (!$success) {
            die($db->error);
        }
        $stmt->bind_result($cnt);
        $stmt->fetch();

        if ($cnt > 0) {
            $error['email'] = 'duplicate';
        }
    }

    // チェック パスワード
    $form['password'] = filter_input(INPUT_POST, 'password', FILTER_SANITIZE_STRING);
    if ($form['password'] === '') {
        $error['password'] = 'blank';
    } else if (strlen($form['password']) < 4) {
        $error['password'] = 'length';
    }

    // チェック 画像
    $image = $_FILES['image'];
    if ($image['name'] !== 'blank' && $image['error'] === 0) {
        $type = mime_content_type($image['tmp_name']);
        var_dump($type);
        if ($type !== 'image/png' && $type !== 'image/jpeg') {
            $error['image'] = 'type';
        }
    }

    // エラーがなければ実行
    if (empty($error)) {
        $_SESSION['form'] = $form;

        // 画像のアップロード
        if ($image['name'] !== '') {
            $filename = date('YmdHis') . '_' . $image['name'];
            if (!move_uploaded_file($image['tmp_name'], '../member_picture/' . $filename)) {
                die('ファイルのアップロードに失敗しました');
            }
            $_SESSION['form']['image'] = $filename;
        } else {
            $_SESSION['form']['image'] = '';
        }

        header('Location: check.php');
        exit();
    }
}
?>
<!-- PHPここまで----------------------------------------------- -->


<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>会員登録</title>

    <link rel="stylesheet" href="../style.css" />
</head>

<body>
    <div id="wrap">
        <div id="head">
            <h1>会員登録</h1>
        </div>

        <div id="content">
            <p>次のフォームに必要事項をご記入ください。</p>
            <form action="" method="post" enctype="multipart/form-data">
                <dl>
                    <!-- ニックネーム入力----------------------------------------------- -->
                    <dt>ニックネーム<span class="required">必須</span></dt>
                    <dd>
                        <input type="text" name="name" size="35" maxlength="255" value="<?php echo h($form['name']); ?>" />
                        <!-- エラーメッセージ * isset()は指定した変数に値が設定されている、かつNullではない場合にtrueを返す =初期状態では値が未設定なので実行されない-->
                        <?php if (isset($error['name']) && $error['name'] === 'blank') : ?>
                            <p class="error">* ニックネームを入力してください</p>
                        <?php endif; ?>
                    </dd>
                    <!-- メールアドレス入力----------------------------------------------- -->
                    <dt>メールアドレス<span class="required">必須</span></dt>
                    <dd>
                        <input type="text" name="email" size="35" maxlength="255" value="<?php echo h($form['email']); ?>" />
                        <!-- エラーメッセージ -->
                        <?php if (isset($error['email']) && $error['email'] === 'blank') : ?>
                            <p class="error">* メールアドレスを入力してください</p>
                        <?php endif; ?>
                        <?php if(isset($error['email']) && $error['email'] === 'duplicate'): ?>
                        <p class="error">* 指定されたメールアドレスはすでに登録されています</p>
                        <?php endif; ?>
                        <!-- パスワード入力----------------------------------------------- -->
                    <dt>パスワード<span class="required">必須</span></dt>
                    <dd>
                        <input type="password" name="password" size="10" maxlength="20" value="<?php echo h($form['password']); ?>" />
                        <!-- エラーメッセージ -->
                        <?php if (isset($error['password']) && $error['password'] === 'blank') : ?>
                            <p class="error">* パスワードを入力してください</p>
                        <?php endif; ?>
                        <?php if (isset($error['password']) && $error['password'] === 'length') : ?>
                            <p class="error">* パスワードは4文字以上で入力してください</p>
                        <?php endif; ?>

                    </dd>
                    <!-- 写真入力----------------------------------------------- -->
                    <dt>写真など</dt>
                    <dd>
                        <input type="file" name="image" size="35" value="" />
                        <!-- エラーメッセージ -->
                        <?php if (isset($error['images']) && $error['image'] === 'type') : ?>
                            <p class="error">* 写真などは「.png」または「.jpg」の画像を指定してください</p>
                        <?php endif; ?>
                        <p class="error">* 恐れ入りますが、画像を改めて指定してください</p>
                    </dd>
                </dl>
                <div><input type="submit" value="入力内容を確認する" /></div>
            </form>
        </div>
</body>

</html>