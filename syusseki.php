<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>出席管理</title>
    <link rel="stylesheet" href="syusseki.css">
</head>
<body>

<?php
// ------------------ 基本設定 ------------------
// タイムゾーンを日本時間に設定（時刻表示などのズレを防ぐ）
date_default_timezone_set('Asia/Tokyo');

// ------------------ サンプル生徒データ ------------------
// 本来ならデータベースやGPS連携で取得する部分。
// 今はサンプルとしてPHP内に配列で生徒情報を定義。
$students = [
    [
        'id' => 1,
        'name' => '坂巻 颯汰',        // 生徒の名前
        'avatar' => 'avatar1.jpg',    // アバター画像（未使用）
        'status' => '登校済',         // 出席ステータス
        'status_detail' => '',        // 詳細（遅刻なら時間など）
        'location' => 'school'        // 現在地（school, home, other）
    ],
    [
        'id' => 2,
        'name' => '小野寺 柊',
        'avatar' => 'avatar2.jpg',
        'status' => '欠席',
        'status_detail' => '',
        'location' => 'home'
    ],
    [
        'id' => 3,
        'name' => '佐野 花音',
        'avatar' => 'avatar3.jpg',
        'status' => '遅刻',
        'status_detail' => 'AM11:20',
        'location' => 'home'
    ],
    [
        'id' => 4,
        'name' => '櫻井 蒼',
        'avatar' => 'avatar4.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ],
    [
        'id' => 5,
        'name' => '菊安 悠太',
        'avatar' => 'avatar5.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ],
    [
        'id' => 6,
        'name' => '斎藤 行輝',
        'avatar' => 'avatar6.jpg',
        'status' => '遅刻',
        'status_detail' => 'AM10:10',
        'location' => 'other'
    ],
    [
        'id' => 7,
        'name' => '佐々木 雄太',
        'avatar' => 'avatar7.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ],
    [
        'id' => 8,
        'name' => '狩野 空',
        'avatar' => 'avatar8.jpg',
        'status' => '遅刻',
        'status_detail' => 'AM10:30',
        'location' => 'other'
    ],
    [
        'id' => 9,
        'name' => '綿本 雅重',
        'avatar' => 'avatar9.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ],
    [
        'id' => 10,
        'name' => '久保 柊馬',
        'avatar' => 'avatar10.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ],
    [
        'id' => 11,
        'name' => '大塚 湊太郎',
        'avatar' => 'avatar11.jpg',
        'status' => '登校済',
        'status_detail' => '',
        'location' => 'school'
    ]
];
    

// ------------------ 位置情報の色を返す関数 ------------------
// 各生徒の「現在地」に応じて表示する色を決める。
// 緑：登校、赤：自宅、黄：その他、灰：不明
function getLocationColor($location) {
    switch($location) {
        case 'school':
            return 'green';
        case 'home':
            return 'red';
        case 'other':
            return 'yellow';
        default:
            return 'gray';
    }
}

// ------------------ 位置情報のテキスト変換関数 ------------------
// location（school, homeなど）を日本語に変換。
function getLocationText($location) {
    switch($location) {
        case 'school':
            return '学校';
        case 'home':
            return '自宅';
        case 'other':
            return 'その他';
        default:
            return '不明';
    }
}
?>

<!-- ここからHTML部分（スマホ風レイアウト） -->
<div class="phone-container">
    
    <!-- ステータスバー（上部の時刻や電波マークなど） -->
    <div class="status-bar">
        <span>9:41</span>
        <div class="status-icons">
            <span>📶</span>
            <span>📡</span>
            <span>🔋</span>
        </div>
    </div>

    <!-- ヘッダー部分（クラス名とクラス選択） -->
    <div class="header">
        <div class="class-info">
            <span class="class-name">橘 今どこ</span>
        </div>
        <div class="class-selector">
            <!-- クラスを切り替えるドロップダウン -->
            <select class="class-dropdown">
                <option>2N1</option>
                <option>2N2</option>
                <option>2N3</option>
            </select>
        </div>
    </div>

    <!-- 検索バー（生徒を検索する入力欄） -->
    <div class="search-bar">
        <input type="text" placeholder="Search" class="search-input">
    </div>

    <!-- 生徒リストのメイン部分 -->
    <div class="content">
        <div class="student-list">
            <?php foreach ($students as $student): ?>
                <!-- 各生徒を1人ずつ表示するボックス -->
                <div class="student-item">

                    <!-- アバター部分（円形アイコン） -->
                    <div class="student-avatar">
                        <div class="avatar-circle"></div>
                    </div>

                    <!-- 名前とステータス（例: 登校済 / 遅刻AM11:20など） -->
                    <div class="student-info">
                        <div class="student-name">
                            <?php echo htmlspecialchars($student['name']); ?>
                        </div>
                        <div class="student-detail">
                            <?php if ($student['status_detail']): ?>
                                <!-- 遅刻などで詳細時間がある場合 -->
                                <?php echo htmlspecialchars($student['status_detail']); ?>
                            <?php else: ?>
                                <!-- 通常の出席ステータスを表示 -->
                                <?php echo htmlspecialchars($student['status']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- ステータス表示（遅刻なら時間も表示） -->
                    <div class="status-display">
                        <div class="status-text">
                            <?php if ($student['status'] == '遅刻'): ?>
                                遅刻<br>
                                <span class="status-time"><?php echo htmlspecialchars($student['status_detail']); ?></span>
                            <?php else: ?>
                                <?php echo htmlspecialchars($student['status']); ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 現在地の色インジケーター -->
                    <div class="location-indicator <?php echo getLocationColor($student['location']); ?>">
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 下部のナビゲーションバー（ホーム・チャット・追加ボタンなど） -->
    <div class="bottom-nav">
        <!-- ホームアイコン（選択中） -->
        <button class="nav-item active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </button>
        <!-- チャットアイコン -->
        <button class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
            </svg>
        </button>
        <!-- 追加ボタン（＋） -->
        <button class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"></circle>
                <path d="M12 1v6m0 6v6m9-9h-6m-6 0H3"></path>
            </svg>
        </button>
    </div>
</div>

</body>
</html>
