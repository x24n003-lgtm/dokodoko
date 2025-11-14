<?php
// エラー表示を有効化
ini_set('display_errors', 1);
error_reporting(E_ALL);
 
session_start();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>会員登録</title>
    <link rel="stylesheet" href = touroku.css>
</head>
<body>
    <div class="register-container">
        <h2>🎓 新規会員登録</h2>
       
        <?php
        // エラーメッセージの表示
        if (isset($_SESSION['error'])) {
            echo '<div class="message error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
       
        <form action="register.php" method="POST" id="registerForm">
            <div class="form-group">
                <label for="email">メールアドレス *</label>
                <input type="email" id="email" name="email" required
                       placeholder="学生: x12345@... / 教員: teacher@..." onchange="toggleClassField()">
                <p class="note">※ 学生の方はxから始まるメールアドレスを入力</p>
            </div>
           
   
           
            <div class="form-group">
                <label for="username">ユーザー名 *</label>
                <input type="text" id="username" name="username" required
                       placeholder="山田太郎">
            </div>
     
            <div class="form-group">
                <label for="password">パスワード *</label>
                <input type="password" id="password" name="password" required minlength="6"
                       placeholder="6文字以上">
            </div>
           
            <div class="form-group">
                <label for="password_confirm">パスワード確認 *</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="6"
                       placeholder="もう一度入力">
            </div>
     
            <div class="form-group">
                <label for="phone">電話番号 *</label>
                <input type="tel" id="phone" name="phone" required
                       placeholder="090-1234-5678">
            </div>
     
            <div class="form-group">
                <label for="gender">性別 *</label>
                <select id="gender" name="gender" required>
                    <option value="">選択してください</option>
                    <option value="男性">男性</option>
                    <option value="女性">女性</option>
                    <option value="その他">その他</option>
                </select>
            </div>
     
            <div class="form-group">
                <label for="home_address">自宅住所 *</label>
                <input type="text" id="home_address" name="home_address" required
                       placeholder="東京都渋谷区...">
            </div>
     
            <button type="submit" class="register-btn">登録する</button>
        </form>
       
    </div>
   
    <script>
       <script>
/* ======================================
   Google Geocoding を使って住所から緯度経度取得
   DB に送信して更新
====================================== */
async function sendLocationFromAddress() {
    const home_addressInput = document.getElementById("home_address");
    const usernameInput = document.getElementById("username");

    const home_address = home_addressInput.value.trim();
    const username = usernameInput.value.trim();

    if (!home_address || !username) {
        alert("名前と住所を入力してください");
        return;
    }

    // Google Maps Geocoding API URL
    const apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"; // ← 自分のキーに変更
    const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(home_address)}&key=${apiKey}`;

    try {
        // --- 住所を緯度経度に変換 ---
        const res = await fetch(url);
        const geoData = await res.json();

        if (geoData.status !== "OK") {
            alert("住所から緯度経度を取得できませんでした");
            console.error(geoData);
            return;
        }

        const location = geoData.results[0].geometry.location;
        const lat = location.lat;
        const lng = location.lng;

        console.log(`住所: ${home_address} → 緯度: ${lat}, 経度: ${lng}`);

        // --- PHPサーバーに送信して DB 更新 ---
        const response = await fetch("save_location.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                username: username,
                home_address: home_address,
                lat: lat,
                lng: lng
            })
        });

        const text = await response.text();
        console.log("DB 更新結果:", text);

        // --- 更新後、比較表示を即更新 ---
        loadComparison();

    } catch (err) {
        console.error("位置情報送信エラー:", err);
    }
}

/* ======================================
   DB から位置情報を取得して校内/校外表示
====================================== */
async function loadComparison() {
    try {
        const res = await fetch("compare_locations.php");
        const data = await res.json();

        // 表示用コンテナが無ければ作成
        let container = document.getElementById("markers");
        if (!container) {
            container = document.createElement("div");
            container.id = "markers";
            document.body.appendChild(container);
        }

        container.innerHTML = ""; // 以前の表示をクリア

        data.forEach(loc => {
            const marker = document.createElement("div");
            marker.classList.add("marker");

            // --- 緯度経度未取得は灰色で「未取得」と表示 ---
            if (loc.lat === null || loc.lng === null) {
                marker.textContent = `${loc.username} (位置未取得)`;
                marker.style.backgroundColor = "gray";
            } else {
                marker.textContent = `${loc.username} (${loc.status})`;
                marker.style.backgroundColor = loc.status === "校内" ? "green" : "red";
            }

            // --- デザイン調整 ---
            marker.style.color = "white";
            marker.style.padding = "6px";
            marker.style.margin = "4px";
            marker.style.borderRadius = "8px";
            marker.style.display = "block";

            container.appendChild(marker);
        });

    } catch (err) {
        console.error("比較結果の取得エラー:", err);
    }
}

/* ======================================
   フォーム送信時に住所→緯度経度取得も組み込む
====================================== */
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    const gender = document.getElementById('gender').value;

    // バリデーションチェック
    if (password !== passwordConfirm) {
        e.preventDefault();
        alert('パスワードが一致しません。');
        return false;
    }

    if (password.length < 6) {
        e.preventDefault();
        alert('パスワードは6文字以上で入力してください。');
        return false;
    }

    if (gender === '') {
        e.preventDefault();
        alert('性別を選択してください。');
        return false;
    }

    if (!confirm('この内容で登録しますか？')) {
        e.preventDefault();
        return false;
    }

    // --- 住所から緯度経度を取得して DB 更新 ---
    e.preventDefault(); // まず通常の送信を止める
    await sendLocationFromAddress();

    // --- 本登録を register.php に送信 ---
    const formData = new FormData(this);
    const res = await fetch(this.action, { method: "POST", body: formData });
    const text = await res.text();
    console.log("登録結果:", text);

    alert("登録が完了しました。ページをリロードします。");
    location.reload();
});

/* ======================================
   ページロード時に比較表示開始
====================================== */
setInterval(loadComparison, 5000); // 5秒ごとに更新
loadComparison();                  // 初回即表示
</script>

    </script>
</body>
</html>