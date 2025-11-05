// ======================================
// 住所から緯度・経度を取得してDBへ送信
// ======================================
async function sendLocationFromAddress() {
  // --- HTMLの入力欄から値を取得 ---
  const home_addressInput = document.getElementById("home_address"); // 住所入力欄
  const usernameInput = document.getElementById("username");         // ユーザー名入力欄

  // --- 入力された値を取得 ---
  const home_address = home_addressInput.value.trim(); // 住所（前後の空白除去）
  const username = usernameInput.value.trim();         // ユーザー名（前後の空白除去）

  // --- 入力チェック ---
  if (!home_address || !username) {
    alert("名前と住所を入力してください");
    return; // 入力がなければ処理中断
  }

  // --- Google Maps Geocoding APIのURLを生成 ---
  const apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"; // ←自分のAPIキーに変更
  const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(home_address)}&key=${apiKey}`;

  try {
    // --- 住所から緯度経度を取得する ---
    const res = await fetch(url);        // APIへリクエスト送信
    const geoData = await res.json();    // 結果をJSONとして受け取る

    // --- エラーチェック ---
    if (geoData.status !== "OK") {
      alert("住所から緯度経度を取得できませんでした。");
      console.error(geoData); // 詳細エラーをコンソールに出力
      return;
    }

    // --- 緯度経度を取り出す ---
    const location = geoData.results[0].geometry.location; // 結果の中から位置情報を取得
    const lat = location.lat; // 緯度
    const lng = location.lng; // 経度

    console.log(`住所: ${home_address} → 緯度: ${lat}, 経度: ${lng}`);

    // --- PHPサーバー（save_location.php）へ送信 ---
    const response = await fetch("save_location.php", {
      method: "POST", // POSTリクエスト
      headers: { "Content-Type": "application/json" }, // JSON形式で送信する指定
      body: JSON.stringify({
        username: username,   // ユーザー名
        lat: lat,             // 緯度
        lng: lng,             // 経度
        home_address: home_address // ← 住所も送っておくと便利
      }),
    });

    // --- サーバーの応答を取得 ---
    const text = await response.text();
    console.log("サーバー応答:", text);

  } catch (err) {
    // --- 予期せぬエラーをキャッチ ---
    console.error("位置情報送信エラー:", err);
  }
}



// ======================================
// 5秒ごとに位置比較を取得して表示
// ======================================
async function loadComparison() {
  try {
    // --- PHPから比較結果(JSON)を取得 ---
    const res = await fetch("compare_locations.php"); // データ取得
    const data = await res.json(); // JSONとして解釈

    // --- 表示先のHTML要素を取得 ---
    const container = document.getElementById("markers");

    // --- 以前のデータをクリア ---
    container.innerHTML = "";

    // --- 各ユーザーの状態を表示 ---
    data.forEach(loc => {
      const marker = document.createElement("div"); // 表示用のdivを作成
      marker.classList.add("marker");

      // --- 校内・校外で色を分ける ---
      if (loc.status === "校内") {
        marker.style.backgroundColor = "green"; // 緑：校内
      } else {
        marker.style.backgroundColor = "red";   // 赤：校外
      }

      // --- テキスト情報を設定 ---
      marker.textContent = `${loc.name} (${loc.status})`;

      // --- デザイン調整 ---
      marker.style.color = "white";
      marker.style.padding = "6px";
      marker.style.margin = "4px";
      marker.style.borderRadius = "8px";
      marker.style.display = "block";

      // --- コンテナに追加 ---
      container.appendChild(marker);
    });

  } catch (err) {
    // --- 通信やパースエラーをキャッチ ---
    console.error("比較結果の取得エラー:", err);
  }
}



// ======================================
// 初期化処理
// ======================================

// 5秒ごとに比較データを更新
setInterval(loadComparison, 5000);

// ページ読み込み時に即実行
loadComparison();
