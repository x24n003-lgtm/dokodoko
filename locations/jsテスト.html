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
        const apiKey = "YOUR_API_KEY"; // ← 自分のキーに変更
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
    
            const container = document.getElementById("markers");
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
       フォーム送信時に緯度経度取得も組み込む
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
    
        // 住所更新後に本登録も送信したい場合は fetch で register.php に POST する
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
    
    <!--
    ※ HTML 側に
    <div id="markers"></div>
    があれば、校内/校外マーカーが表示されます
    -->
    