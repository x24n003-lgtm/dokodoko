<?php
/**
 * アップロード設定ファイル
 * グループ開発用：ローカルとサーバーの環境を自動判定
 */

// サーバーの種類を判定
function isLinuxServer() {
    return stripos(PHP_OS, 'LINUX') !== false;
}

// アップロードディレクトリの設定
if (isLinuxServer()) {
    // Linuxサーバー環境
    define('UPLOAD_BASE_DIR', '/var/www/html/dokodoko/uploads/');
    define('UPLOAD_BASE_URL', 'http://172.16.199.21/dokodoko/uploads/');
} else {
    // Windowsローカル環境（開発用）
    define('UPLOAD_BASE_DIR', __DIR__ . '/uploads/');
    define('UPLOAD_BASE_URL', 'http://localhost/dokodoko/uploads/');
}

// ロゴ画像用ディレクトリ
define('LOGO_DIR', UPLOAD_BASE_DIR . 'logos/');
define('LOGO_URL', UPLOAD_BASE_URL . 'logos/');

/**
 * アップロードディレクトリを作成（存在しない場合）
 */
function ensureUploadDirectory($dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        chmod($dir, 0777);
    }
}

/**
 * 画像パスをURLに変換
 */
function getImageUrl($imagePath) {
    if (empty($imagePath)) {
        return 'default_icon.png';
    }
    
    // 絶対パスの場合はURLに変換
    if (strpos($imagePath, UPLOAD_BASE_DIR) === 0) {
        return str_replace(UPLOAD_BASE_DIR, UPLOAD_BASE_URL, $imagePath);
    }
    
    // 相対パスの場合はそのまま返す
    return $imagePath;
}

/**
 * ファイルの存在確認（サーバー/ローカル両対応）
 */
function imageExists($imagePath) {
    if (empty($imagePath)) {
        return false;
    }
    
    // 絶対パスの場合
    if (file_exists($imagePath)) {
        return true;
    }
    
    // 相対パスの場合
    if (file_exists(__DIR__ . '/' . $imagePath)) {
        return true;
    }
    
    return false;
}
?>