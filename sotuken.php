<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>位置判定マーク</title>
<style>
  .marker {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    display: inline-block;
    margin: 5px;
    border: 1px solid #333;
  }
  .inside { background-color: green; }
  .outside { background-color: red; }
</style>
</head>
<body>

<div id="markers"></div>

<script>
  // 判定したい地点
  const locations = [
    { name: "学校", lat: 35.704517, lng: 139.984413 },
    { name: "自宅", lat: 35.650174, lng: 140.095524 }
  ];

  // 判定範囲（例：学校付近0.01度以内を緑、それ以外赤）
  const range = { latMin: 35.694517, latMax: 35.714517, lngMin: 139.974413, lngMax: 139.994413 };

  const markersDiv = document.getElementById("markers");

  locations.forEach(loc => {
    const marker = document.createElement("div");
    marker.classList.add("marker");

    // 緯度経度で判定
    if (
      loc.lat >= range.latMin && loc.lat <= range.latMax &&
      loc.lng >= range.lngMin && loc.lng <= range.lngMax
    ) {
      marker.classList.add("inside");  // 緑
    } else {
      marker.classList.add("outside"); // 赤
    }

    marker.title = loc.name; // ホバーで名称表示
    markersDiv.appendChild(marker);
  });
</script>

</body>
</html>
