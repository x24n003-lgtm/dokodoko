<?php
// save_locations.php
// --- エラー表示設定 ---
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- ヘッダー設定（JSON形式で返す） ---
header("Content-Type: application/json; charset=UTF-8");

// --- データベース接続設定 ---
$host = "172.16.199.21";  // 接続先MariaDBサーバーのIPアドレス（Linux側）
$user = "x24n007";         // 接続ユーザー名
$pass = "n051211";         // パスワード
$db   = "dokodoko";        // 使用するデータベース名

// --- MySQLサーバーへ接続 ---
$conn = new mysqli($host, $user, $pass, $db);

// 接続に失敗した場合はエラーメッセージを出して終了
if ($conn->connect_error) {
    echo json_encode([
        "error" => "DB接続失敗: " . $conn->connect_error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- JSONデータの受信 ---
// フロントエンド（JavaScriptなど）から送信されたJSONを取得
$input = file_get_contents("php://input");

// JSON文字列を連想配列に変換
$data = json_decode($input, true);

// データが空、または必要なキー(username, lat, lng)がなければ終了
if (!$data || !isset($data["username"], $data["lat"], $data["lng"])) {
    echo json_encode([
        "error" => "不正なデータ"
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- 変数へ代入 ---
$username = $data["username"];  // 名前（ユーザー識別用）
$lat = $data["lat"];            // 緯度
$lng = $data["lng"];            // 経度

// --- 既に同じ username のデータがあるか確認 ---
$sql_check = "SELECT id FROM users WHERE username = ?";  // ? はプレースホルダ（後で値を入れる）
$stmt = $conn->prepare($sql_check);  // SQLを安全に実行するためにプリペアドステートメントを使用
$stmt->bind_param("s", $username);   // "s" は string 型を表す
$stmt->execute();                    // 実行
$result = $stmt->get_result();       // 結果を取得

// --- データが存在するかで分岐 ---
if ($result->num_rows > 0) {
    // ✅ 既に同じ名前のデータがある場合 → 「更新」処理
    $sql_update = "UPDATE users SET lat = ?, lng = ?, location_updated_at = NOW() WHERE username = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dds", $lat, $lng, $username);  // d=double, s=string
    
    if ($stmt_update->execute()) {
        echo json_encode([
            "success" => "位置情報を更新しました",
            "action" => "update",
            "username" => $username,
            "lat" => $lat,
            "lng" => $lng
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "error" => "更新に失敗しました: " . $stmt_update->error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt_update->close();
} else {
    // 🆕 同じ名前のデータがない場合 → 「新規追加」処理
    $sql_insert = "INSERT INTO users (username, lat, lng, location_updated_at) VALUES (?, ?, ?, NOW())";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("sdd", $username, $lat, $lng);  // s=string, d=double
    
    if ($stmt_insert->execute()) {
        echo json_encode([
            "success" => "位置情報を追加しました",
            "action" => "insert",
            "username" => $username,
            "lat" => $lat,
            "lng" => $lng
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            "error" => "追加に失敗しました: " . $stmt_insert->error
        ], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt_insert->close();
}

// --- 使用したステートメントと接続を閉じる ---
$stmt->close();
$conn->close();
?>