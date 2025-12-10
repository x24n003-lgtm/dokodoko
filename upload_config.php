<?php
/**
 * アップロード設定ファイル（ローカル & Linux サーバー両対応）
 */

function isLinuxServer() {
    return stripos(PHP_OS, 'LINUX') !== false;
}

// ===== 1. アップロード基準ディレクトリ =====
if (isLinuxServer()) {
    // ★★★ dokodoko4 に変更 ★★★
    define('UPLOAD_BASE_DIR', '/var/www/html/dokodoko4/uploads/');
    define('UPLOAD_BASE_URL', 'http://172.16.199.21/dokodoko4/uploads/');
} else {
    define('UPLOAD_BASE_DIR', __DIR__ . '/uploads/');
    define('UPLOAD_BASE_URL', 'http://localhost/dokodoko4/uploads/');
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

    if (str_starts_with($fileName, 'logos/')) {
        return UPLOAD_BASE_URL . $fileName;
    }

    if (str_starts_with($fileName, 'uploads/')) {
        return UPLOAD_BASE_URL . substr($fileName, strlen('uploads/'));
    }

    if (!str_contains($fileName, '/')) {
        return LOGO_URL . $fileName;
    }

    return $fileName;
}

// ===== 5. ファイル存在チェック =====
function imageExists($fileName) {
    if (empty($fileName)) return false;

    if (file_exists(UPLOAD_BASE_DIR . $fileName)) return true;

    return false;
}

// ===== 6. 必ずディレクトリを作成 =====
ensureUploadDirectory(UPLOAD_BASE_DIR);
ensureUploadDirectory(LOGO_DIR);

?>
