<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8">
<title>位置情報マップ</title>
<link rel="stylesheet" href="sotuken.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>
<body>

<button onclick="getLocation()">現在地取得</button>
<p id="output"></p>
<div id="map"></div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
let map = L.map('map').setView([35.681, 139.767], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let userMarker;

// PHP から取得したデータを fetch
fetch('locations.php')
    .then(res => res.json())
    .then(data => {
        data.forEach(loc => {
            let color = 'yellow'; // デフォルト: その他
            if (loc.name.includes('学校')) color = 'green';
            else if (loc.name.includes('自宅')) color = 'red';

            L.circle([loc.lat, loc.lng], { radius: 50, color: color }).addTo(map)
                .bindPopup(loc.name);
        });
    });

// 現在地取得
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

            if (userMarker) userMarker.remove();
            userMarker = L.marker([lat, lng]).addTo(map)
                .bindPopup("あなたの現在地").openPopup();
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
