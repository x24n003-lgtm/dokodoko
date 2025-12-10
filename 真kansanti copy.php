<?php
require_once 'db_config.php';
session_start();

// ------------------ ログイン & 権限確認 ------------------
if (!isset($_SESSION['user_id'], $_SESSION['user_type'])) {
    header('Location: login.php');
    exit();
}
if ($_SESSION['user_type'] !== 'teacher') {
    // 生徒は自分の画面へ
    header('Location: karennda-.php');
    exit();
}

$teacherId = (int)$_SESSION['user_id'];

date_default_timezone_set('Asia/Tokyo');

// ------------------ DB接続 ------------------
$pdo = getDbConnection();

// ------------------ 生徒リスト取得（studentのみ） ------------------
$students = [];
try {
    $stmt = $pdo->query("
        SELECT id, username, email
        FROM users
        WHERE user_type = 'student'
        ORDER BY username
    ");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("生徒一覧取得エラー: " . $e->getMessage());
}

// ------------------ 選択中の student_id ------------------
$selectedStudentId = null;
if (isset($_GET['student_id'])) {
    $selectedStudentId = (int)$_GET['student_id'];
} elseif (!empty($students)) {
    $selectedStudentId = (int)$students[0]['id'];
}

// student_id のバリデーション（存在して student であること）
if ($selectedStudentId !== null) {
    try {
        $check = $pdo->prepare("
            SELECT id FROM users
            WHERE id = :id AND user_type = 'student'
        ");
        $check->execute([':id' => $selectedStudentId]);
        if (!$check->fetch()) {
            $selectedStudentId = null;
        }
    } catch (PDOException $e) {
        error_log("生徒チェックエラー: " . $e->getMessage());
        $selectedStudentId = null;
    }
}

// ------------------ POST処理（追加・削除・テスト） ------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedStudentId !== null) {

    // プリセット（遅刻・欠席など）
    if (isset($_POST['add_value']) && isset($_POST['type_label'])) {
        $val  = (float)$_POST['add_value'];
        $type = trim($_POST['type_label']);
        try {
            $ins = $pdo->prepare("
                INSERT INTO kansanti (student_id, teacher_id, value, type, logged_at)
                VALUES (:sid, :tid, :value, :type, NOW())
            ");
            $ins->execute([
                ':sid'   => $selectedStudentId,
                ':tid'   => $teacherId,
                ':value' => $val,
                ':type'  => $type
            ]);
        } catch (PDOException $e) {
            error_log("換算値追加エラー: " . $e->getMessage());
        }
        header('Location: kansanti.php?student_id=' . $selectedStudentId);
        exit();
    }

    // 手動調整（任意の数値を加算/減算）
    if (isset($_POST['manual_value']) && $_POST['manual_value'] !== '') {
        $val = (float)$_POST['manual_value'];
        if ($val != 0) {
            $type = '手動調整 ' . ($val > 0 ? '(+)' : '(-)');
            try {
                $ins = $pdo->prepare("
                    INSERT INTO kansanti (student_id, teacher_id, value, type, logged_at)
                    VALUES (:sid, :tid, :value, :type, NOW())
                ");
                $ins->execute([
                    ':sid'   => $selectedStudentId,
                    ':tid'   => $teacherId,
                    ':value' => $val,
                    ':type'  => $type
                ]);
            } catch (PDOException $e) {
                error_log("手動調整エラー: " . $e->getMessage());
            }
        }
        header('Location: kansanti.php?student_id=' . $selectedStudentId);
        exit();
    }

    // テスト用：+40 追加
    if (isset($_POST['test_add_40'])) {
        try {
            $ins = $pdo->prepare("
                INSERT INTO kansanti (student_id, teacher_id, value, type, logged_at)
                VALUES (:sid, :tid, :value, :type, NOW())
            ");
            $ins->execute([
                ':sid'   => $selectedStudentId,
                ':tid'   => $teacherId,
                ':value' => 40.00,
                ':type'  => 'テスト：+40'
            ]);
        } catch (PDOException $e) {
            error_log("テスト+40エラー: " . $e->getMessage());
        }
        header('Location: kansanti.php?student_id=' . $selectedStudentId);
        exit();
    }

    // テスト用：全取り消し（その生徒のレコード全部削除）
    if (isset($_POST['test_delete_all'])) {
        try {
            $del = $pdo->prepare("DELETE FROM kansanti WHERE student_id = :sid");
            $del->execute([':sid' => $selectedStudentId]);
        } catch (PDOException $e) {
            error_log("テスト全削除エラー: " . $e->getMessage());
        }
        header('Location: kansanti.php?student_id=' . $selectedStudentId);
        exit();
    }

    // 個別削除
    if (isset($_POST['delete_id'])) {
        $delId = (int)$_POST['delete_id'];
        try {
            $del = $pdo->prepare("
                DELETE FROM kansanti
                WHERE id = :id AND student_id = :sid
            ");
            $del->execute([
                ':id'  => $delId,
                ':sid' => $selectedStudentId
            ]);
        } catch (PDOException $e) {
            error_log("個別削除エラー: " . $e->getMessage());
        }
        header('Location: kansanti.php?student_id=' . $selectedStudentId);
        exit();
    }
}

// ------------------ 選択中生徒の情報・合計・履歴取得 ------------------
$selectedStudent = null;
$totalValue      = 0.00;
$history         = [];

if ($selectedStudentId !== null) {
    try {
        $stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = :id");
        $stmt->execute([':id' => $selectedStudentId]);
        $selectedStudent = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(value), 0) AS total_value
            FROM kansanti
            WHERE student_id = :sid
        ");
        $stmt->execute([':sid' => $selectedStudentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $totalValue = (float)$row['total_value'];
        }

        $stmt = $pdo->prepare("
            SELECT id, value, type, logged_at
            FROM kansanti
            WHERE student_id = :sid
            ORDER BY logged_at DESC, id DESC
        ");
        $stmt->execute([':sid' => $selectedStudentId]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("換算値情報取得エラー: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>換算値管理（教師用）</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    background:#f5f5f5;
    margin:0;
    padding:0;
}
/* スマホ枠 */
.phone-container {
    max-width:414px;
    margin:0 auto;
    background:#fff;
    min-height:100vh;
    box-sizing:border-box;
    padding:12px 12px 70px;
    position:relative;
}
/* 上部バーっぽい */
.header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:10px;
}
.header-title {
    font-size:1.1rem;
    font-weight:bold;
}
.header-sub {
    font-size:0.8rem;
}
.back-link {
    font-size:0.8rem;
    text-decoration:none;
    color:#007bff;
}
.section-title {
    font-weight:bold;
    margin-top:8px;
    margin-bottom:4px;
    font-size:0.9rem;
}
.select-student {
    width:100%;
    padding:6px;
    font-size:0.85rem;
    border:1px solid #ccc;
    border-radius:6px;
    box-sizing:border-box;
}
.box {
    border:1px solid #eee;
    padding:8px;
    margin-top:8px;
    border-radius:8px;
    background:#fafafa;
}
.value-big {
    font-size:1.6rem;
    font-weight:bold;
    margin-top:4px;
}
.small {
    font-size:0.75rem;
    color:#666;
}
.button-row {
    margin-top:6px;
    display:flex;
    flex-wrap:wrap;
    gap:6px;
}
button {
    cursor:pointer;
}
.btn {
    padding:5px 8px;
    font-size:0.75rem;
    border-radius:6px;
    border:1px solid #ccc;
    background:#fff;
}
.btn-primary {
    background:#007bff;
    border-color:#007bff;
    color:#fff;
}
.btn-danger {
    background:#dc3545;
    border-color:#dc3545;
    color:#fff;
}
.btn-test {
    background:#e2e3ff;
    border-color:#a5a8ff;
}
input[type="number"] {
    padding:4px;
    width:90px;
    font-size:0.8rem;
    border-radius:4px;
    border:1px solid #ccc;
    box-sizing:border-box;
}
.history-table {
    width:100%;
    border-collapse:collapse;
    margin-top:6px;
    font-size:0.7rem;
}
.history-table th,
.history-table td {
    border:1px solid #ddd;
    padding:3px 4px;
    text-align:left;
}
.history-table th {
    background:#eee;
}
.bottom-note {
    font-size:0.7rem;
    color:#888;
    margin-top:4px;
}
</style>
</head>
<body>
<div class="phone-container">

    <div class="header">
        <div>
            <div class="header-title">換算値管理</div>
            <div class="header-sub">教師専用</div>
        </div>
        <a href="teachermypage.php" class="back-link">マイページへ</a>
    </div>

    <!-- 生徒選択 -->
    <div class="section-title">生徒を選択</div>
    <form method="GET" action="kansanti.php">
        <select name="student_id" class="select-student" onchange="this.form.submit()">
            <?php foreach ($students as $stu): ?>
                <option value="<?php echo (int)$stu['id']; ?>"
                    <?php if ($selectedStudentId === (int)$stu['id']) echo 'selected'; ?>>
                    <?php echo htmlspecialchars($stu['username'], ENT_QUOTES, 'UTF-8'); ?>
                    （<?php echo htmlspecialchars($stu['email'], ENT_QUOTES, 'UTF-8'); ?>）
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selectedStudent && $selectedStudentId !== null): ?>
        <!-- 合計表示 -->
        <div class="box">
            <div class="small">
                対象生徒：
                <?php echo htmlspecialchars($selectedStudent['username'], ENT_QUOTES, 'UTF-8'); ?>
                （<?php echo htmlspecialchars($selectedStudent['email'], ENT_QUOTES, 'UTF-8'); ?>）
            </div>
            <div style="margin-top:4px; font-size:0.8rem;">現在の換算値</div>
            <div class="value-big"><?php echo number_format($totalValue, 2); ?></div>
        </div>

        <!-- 換算値追加 -->
        <div class="box">
            <div class="section-title" style="margin-top:0;">換算値を追加</div>

            <!-- プリセットボタン -->
            <div class="button-row">
            <form method="POST">
                    <input type="hidden" name="add_value" value="0.08">
                    <input type="hidden" name="type_label" value="22.5分間の遅刻">
                    <button type="submit" class="btn">22.5分遅刻 (+0.08)</button>
                </form>
               <form method="POST">
                    <input type="hidden" name="add_value" value="0.15">
                    <input type="hidden" name="type_label" value="45分間の遅刻">
                    <button type="submit" class="btn">45分遅刻 (+0.15)</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="add_value" value="0.33">
                    <input type="hidden" name="type_label" value="90分間の遅刻">
                    <button type="submit" class="btn">90分遅刻 (+0.33)</button>
                </form>

                <form method="POST">
                    <input type="hidden" name="add_value" value="1.00">
                    <input type="hidden" name="type_label" value="一日欠席">
                    <button type="submit" class="btn">一日欠席 (+1.00)</button>
                </form>
            </div>

            <!-- 手動調整 -->
            <form method="POST" style="margin-top:6px;">
                <div class="small">任意の数値（例：-1, 0.50 など）</div>
                <div style="margin-top:2px;">
                    <input type="number" step="0.01" name="manual_value" placeholder="+/-値">
                    <button type="submit" class="btn btn-primary">手動調整</button>
                </div>
            </form>

            <!-- テスト用ボタン -->
            <div style="margin-top:8px;">
                <div class="small">※ テスト用（生徒側画面の警告動作確認）</div>
                <div class="button-row" style="margin-top:4px;">
                    <form method="POST" onsubmit="return confirm('テストでこの生徒の換算値に +40 しますか？');">
                        <input type="hidden" name="test_add_40" value="1">
                        <button type="submit" class="btn btn-test">テスト：+40 追加</button>
                    </form>

                    <form method="POST" onsubmit="return confirm('テスト用：この生徒の換算値をすべて削除します。よろしいですか？');">
                        <input type="hidden" name="test_delete_all" value="1">
                        <button type="submit" class="btn btn-danger">テスト：全取り消し</button>
                    </form>
                </div>
                <div class="bottom-note">
                    ※ 実運用前にテストして、問題なければテスト用ボタンをコードから削除してもOKです。
                </div>
            </div>
        </div>

        <!-- 履歴一覧 -->
        <div class="box">
            <div class="section-title" style="margin-top:0;">履歴</div>
            <?php if (empty($history)): ?>
                <div class="small">まだ換算値の履歴はありません。</div>
            <?php else: ?>
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>内容</th>
                            <th>値</th>
                            <th>日時</th>
                            <th>削除</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($history as $h): ?>
                        <tr>
                            <td><?php echo (int)$h['id']; ?></td>
                            <td><?php echo htmlspecialchars($h['type'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo ($h['value'] >= 0 ? '+' : '') . number_format($h['value'], 2); ?></td>
                            <td><?php echo htmlspecialchars($h['logged_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('このレコードを削除しますか？');">
                                    <input type="hidden" name="delete_id" value="<?php echo (int)$h['id']; ?>">
                                    <button type="submit" class="btn btn-danger">削除</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="box">
            <div class="small">生徒が登録されていないか、選択された生徒が無効です。</div>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
