<?php
// htmlspechialcharsを短くする関数
function h($value)
{
    return htmlspecialchars($value, ENT_QUOTES);
}

//データベースに接続
function dbconnect()
{
    $db = new mysqli('localhost', 'root', 'root', 'min_bbs');
    if (!$db) {
        die($db->error);
    }

    return $db;
}
