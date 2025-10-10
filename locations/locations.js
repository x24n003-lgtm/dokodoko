async function sendMyLocation() {
  const pos = await new Promise((resolve, reject) => {
    if (!navigator.geolocation) {
      reject("ブラウザが位置情報をサポートしていません");
    } else {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        maximumAge: 0,
        timeout: 5000
      });
    }
  });

  const lat = pos.coords.latitude;
  const lng = pos.coords.longitude;
  const name = "久保柊馬"; // ここは文字化けしないように

  try {
    const res = await fetch("save_locations.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ name, lat, lng })
    });
    console.log(await res.text());
  } catch (err) {
    console.error("送信エラー:", err);
  }
}

// 全員の位置更新
async function updateLoop() {
  await sendMyLocation();
  await loadLocations();
}

// 5秒ごとにループ
setInterval(updateLoop, 5000);
