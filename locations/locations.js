async function loadComparison() {
  try {
    const res = await fetch("compare_locations.php");
    const data = await res.json();

    const container = document.getElementById("markers");
    container.innerHTML = ""; // 以前の表示を消す

    data.forEach(loc => {
      const marker = document.createElement("div");
      marker.classList.add("marker");

      // 校内・校外で色を分ける
      if (loc.status === "校内") {
        marker.style.backgroundColor = "green"; // 緑：校内
      } else {
        marker.style.backgroundColor = "red"; // 赤：校外
      }

      // 情報表示
      marker.textContent = `${loc.name} (${loc.status})`;
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

// 5秒ごとに実行
setInterval(loadComparison, 5000);

// 初回起動時にも即実行
loadComparison();
