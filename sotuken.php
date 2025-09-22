<?php
$supabase_url = "https://ndufiudrmiyamcolrbjw.supabase.co/rest/v1/students";
$service_role_key = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc1ODM0MjQ3MiwiZXhwIjoyMDczOTE4NDcyfQ.62L7Sm1cNZXyFWMvtBw0z-9rZ1MPMylrvm797OEn7WE";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $supabase_url . "?select=*");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $service_role_key",
    "Content-Type: application/json"
]);

$response = curl_exec($ch);

if($response === false){
    echo "cURL Error: " . curl_error($ch);
} else {
    $data = json_decode($response, true);
    print_r($data);
}

curl_close($ch);
?>


<!DOCTYPE html>

<html lang="ja">
<head>
<meta charset="UTF-8">
<title>Leafletで現在地</title>
<link rel="stylesheet" href="sotuken.css">


<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<style>
  #map { height: 500px; width: 90%; margin: 20px auto; }
</style>
</head>
<body>

<button onclick="getLocation()">現在地取得</button>
<p id="output"></p>
<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
let map = L.map('map').setView([35.681, 139.767], 12); // 東京駅中心
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker;

function getLocation() {
  const output = document.getElementById('output');
  if (!navigator.geolocation) {
    output.textContent = "このブラウザでは位置情報はサポートされていません";
    return;
  }
  output.textContent = "位置情報を取得しています...";

  navigator.geolocation.getCurrentPosition(
    pos => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      output.textContent = `緯度: ${lat.toFixed(6)}, 経度: ${lng.toFixed(6)}`;

      if (marker) marker.remove();
      marker = L.marker([lat, lng]).addTo(map).bindPopup("あなたの現在地").openPopup();
      map.setView([lat, lng], 16);
    },
    err => {
      output.textContent = `エラー: ${err.code} - ${err.message}`;
    }
  );
}
</script>

</body>
</html>
