<?php
/**
 * アップロード設定ファイル（ローカル & Linux サーバー両対応）
 */

function isLinuxServer() {
    return stripos(PHP_OS, 'LINUX') !== false;
}

// ===== 1. アップロード基準ディレクトリ =====
if (isLinuxServer()) {
    define('UPLOAD_BASE_DIR', '/var/www/html/dokodoko/uploads/');
    define('UPLOAD_BASE_URL', 'http://172.16.199.21/dokodoko/uploads/');
} else {
    define('UPLOAD_BASE_DIR', __DIR__ . '/uploads/');
    define('UPLOAD_BASE_URL', 'http://localhost/dokodoko/uploads/');
}

// ===== 2. ロゴ用 =====
define('LOGO_DIR', UPLOAD_BASE_DIR . 'logos/');
define('LOGO_URL', UPLOAD_BASE_URL . 'logos/');

// ===== 3. ディレクトリ作成 =====
function ensureUploadDirectory($dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        chmod($dir, 0777);
    }
}

// ===== 4. DB に保存されたパス → 表示用 URL に変換 =====
function getImageUrl($fileName) {
    if (empty($fileName)) return 'default_icon.png';

    // uploads/logos/xxxx.jpg のように相対パスで保存されている場合
    if (strpos($fileName, 'uploads/') === 0) {
        return UPLOAD_BASE_URL . substr($fileName, strlen('uploads/'));
    }

    // ただのファイル名だけの場合（logo_xxx.jpg）
    if (!str_contains($fileName, '/')) {
        return LOGO_URL . $fileName;
    }

    // それ以外（安全のため）
    return $fileName;
}

// ===== 5. ファイル存在チェック =====
function imageExists($fileName) {
    if (empty($fileName)) return false;

    // uploads/logos/xxx.jpg の場合
    if (file_exists(UPLOAD_BASE_DIR . $fileName)) return true;

    // logos/xxx.jpg の場合
    if (file_exists(LOGO_DIR . basename($fileName))) return true;

    return false;
}
?>
