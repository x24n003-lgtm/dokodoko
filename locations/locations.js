// ======================================
// 住所から緯度・経度を取得してDBへ送信（id方式）
// ======================================
async function sendLocationFromAddress() {
  // --- HTMLの住所入力欄 ---
  const home_addressInput = document.getElementById("home_address");
  const home_address = home_addressInput.value.trim();



  if (!home_address) {
    alert("住所を入力してください");
    return;
  }

  // --- Google Maps Geocoding API ---
  const apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"; // ←自分のAPIキーに変更
  const url = `https://maps.googleapis.com/maps/api/geocode/json?address=${encodeURIComponent(home_address)}&key=${apiKey}`;

  try {
    const res = await fetch(url);
    const geoData = await res.json();

    if (geoData.status !== "OK") {
      alert("住所から緯度経度を取得できませんでした。");
      console.error(geoData);
      return;
    }

    const location = geoData.results[0].geometry.location;
    const lat = location.lat;
    const lng = location.lng;

    console.log(`住所: ${home_address} → 緯度: ${lat}, 経度: ${lng}`);

    // --- DB の id を指定（htmlから持ってきて自動ID割り振り） ---
    const userIdInput = document.getElementById("user_id");
    const user_id = parseInt(userIdInput.value, 10);
    

    // --- PHPサーバーへ送信 ---
    const response = await fetch("save_locations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        id: user_id,
        lat: lat,
        lng: lng,
        home_address: home_address
      }),
    });

    const text = await response.text();
    console.log("サーバー応答:", text);

  } catch (err) {
    console.error("位置情報送信エラー:", err);
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
    container.innerHTML = "";

    data.forEach(loc => {
      const marker = document.createElement("div");
      marker.classList.add("marker");

      if (loc.status === "学校内") {
        marker.style.backgroundColor = "green";
      } else if (loc.status === "学校外") {
        marker.style.backgroundColor = "red";
      } else {
        marker.style.backgroundColor = "gray"; // 位置情報なし
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
    console.error("比較結果の取得エラー:", err);
  }
}

// ======================================
// 初期化処理
// ======================================
setInterval(loadComparison, 5000);
loadComparison();
