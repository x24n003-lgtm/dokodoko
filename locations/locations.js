// ======================================
// 住所から緯度・経度を取得してDBへ送信（セッション方式）
// ======================================
async function sendLocationFromAddress() {
  console.log("✅ sendLocationFromAddress() が呼ばれました");

  const home_addressInput = document.getElementById("home_address");
  if (!home_addressInput) {
    console.error("❌ #home_address が見つかりません");
    return;
  }

  const home_address = home_addressInput.value.trim();
  console.log("🏠 入力住所:", home_address);

  if (!home_address) {
    alert("住所を入力してください");
    return;
  }

  const apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg";
  const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(home_address)}&key=${apiKey}`;

  try {
    console.log("🌐 Google API にアクセス中:", url);
    const res = await fetch(url);
    const geoData = await res.json();

    console.log("📦 取得結果:", geoData);

    if (geoData.status !== "OK") {
      alert("住所から緯度経度を取得できませんでした。");
      console.error("❌ Geocoding失敗:", geoData);
      return;
    }

    const location = geoData.results[0].geometry.location;
    const lat = location.lat;
    const lng = location.lng;

    console.log(`📍 緯度: ${lat}, 経度: ${lng}`);

    // --- PHPサーバーへ送信（セッションから user_id を取得） ---
    console.log("📤 save_locations.php に送信開始");

    const response = await fetch("save_locations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        lat: lat,
        lng: lng,
        home_address: home_address
      }),
    });

    const text = await response.text();
    console.log("📨 サーバー応答:", text);

  } catch (err) {
    console.error("💥 位置情報送信エラー:", err);
  }
}

// ======================================
// 5秒ごとに位置比較を取得して表示
// ======================================
async function loadComparison() {
  try {
    const res = await fetch("compare_locations.php");
    const data = await res.json();

    const container = document.getElementById("markers");
    if (!container) {
      console.warn("⚠️ #markers が見つからないためスキップ");
      return;
    }

    container.innerHTML = "";

    data.forEach(loc => {
      const marker = document.createElement("div");
      marker.classList.add("marker");

      if (loc.status === "学校内") {
        marker.style.backgroundColor = "green";
      } else if (loc.status === "学校外") {
        marker.style.backgroundColor = "red";
      } else {
        marker.style.backgroundColor = "gray";
      }

      marker.textContent = `${loc.username || "不明"} (${loc.status})`;
      marker.style.color = "white";
      marker.style.padding = "6px";
      marker.style.margin = "4px";
      marker.style.borderRadius = "8px";
      marker.style.display = "block";

      container.appendChild(marker);
    });

  } catch (err) {
    console.error("💥 比較結果の取得エラー:", err);
  }
}

// ======================================
// 初期化処理
// ======================================
console.log("🚀 JS 読み込み完了：5秒ごとに比較データ更新開始");
setInterval(loadComparison, 5000);
loadComparison();
