// ===============================
// locations.js 完全版
// ===============================

document.addEventListener("DOMContentLoaded", () => {

  // ===============================
  // 1️⃣ ユーザー情報取得
  // ===============================
  // セッションから取得（カレンダーや出席ページ用）
  const userEmail = window.USER_EMAIL || "<?php echo $_SESSION['email'] ?? ''; ?>";

  if (!userEmail) {
      console.warn("ログイン情報(email)なし → 位置情報送信しません");
      return;
  }

  // ===============================
  // 2️⃣ 位置情報送信関数
  // ===============================
  async function sendLocation(lat, lng, home_address = null) {
      try {
          const payload = { email: userEmail, lat, lng };
          if (home_address) payload.home_address = home_address;

          const res = await fetch("../locations/save_locations.php", {
              method: "POST",
              headers: { "Content-Type": "application/json" },
              body: JSON.stringify(payload)
          });

          const result = await res.json();
          console.log("位置情報送信結果:", result);

      } catch (err) {
          console.error("位置情報送信エラー:", err);
      }
  }

  // ===============================
  // 3️⃣ 現在位置を取得して送信
  // ===============================
  if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
          pos => {
              sendLocation(pos.coords.latitude, pos.coords.longitude);
          },
          err => console.error("位置情報取得エラー:", err)
      );
  } else {
      console.warn("ブラウザが位置情報に非対応");
  }

  // ===============================
  // 4️⃣ 住所から緯度経度取得して送信（フォーム用）
  // ===============================
  window.sendLocationFromAddress = async function(home_address, email = null) {
      email = email || userEmail;

      if (!home_address || !email) {
          alert("メールアドレスと住所を入力してください");
          return;
      }

      const apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg";
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
          console.log(`住所: ${home_address} → 緯度: ${location.lat}, 経度: ${location.lng}`);

          await sendLocation(location.lat, location.lng, home_address);

      } catch (err) {
          console.error("位置情報送信エラー:", err);
      }
  };

  // ===============================
  // 5️⃣ 学校内外ステータス取得（5秒ごと）
  // ===============================
  async function loadComparison() {
      try {
          const res = await fetch("../locations/compare_locations.php");
          const data = await res.json();

          const container = document.getElementById("markers");
          if (!container) return;
          container.innerHTML = "";

          data.forEach(loc => {
              const marker = document.createElement("div");
              marker.classList.add("marker");

              switch(loc.status) {
                  case "学校内": marker.style.backgroundColor = "green"; break;
                  case "学校外": marker.style.backgroundColor = "red"; break;
                  default: marker.style.backgroundColor = "gray";
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

  setInterval(loadComparison, 5000);
  loadComparison();
});
