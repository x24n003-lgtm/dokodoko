function loadLocations() {
  fetch("locations.php")
    .then(res => res.json())
    .then(data => {
      const container = document.getElementById("markers");
      container.innerHTML = ""; // 以前のマークをクリア

      data.forEach(loc => {
        const marker = document.createElement("div");
        marker.classList.add("marker");

        // 色判定
        if (loc.name.includes("学校")) {
          marker.classList.add("inside");  // 緑
        } else if (loc.name.includes("自宅")) {
          marker.classList.add("outside"); // 赤
        } else {
          marker.classList.add("other");   // 黄色
        }

        marker.title = loc.name; // ホバーで名前表示
        container.appendChild(marker);
      });
    })
    .catch(err => console.error(err));
}
