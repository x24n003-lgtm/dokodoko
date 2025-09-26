<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sotuken";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("接続失敗: " . $conn->connect_error);

$name = $_POST['name'];
$address = $_POST['address'];

// Google Maps Geocoding API
$apiKey = "AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg";
$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($address)."&key=".$apiKey;
$response = file_get_contents($url);
$json = json_decode($response, true);

if($json['status'] === 'OK') {
    $lat = $json['results'][0]['geometry']['location']['lat'];
    $lng = $json['results'][0]['geometry']['location']['lng'];

    $stmt = $conn->prepare("INSERT INTO locations (name, address, lat, lng) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdd", $name, $address, $lat, $lng);
    $stmt->execute();

    echo "登録完了: $name ($lat, $lng)";
} else {
    echo "住所変換失敗";
}

$conn->close();
?>
<div id="map" style="height: 500px; width: 90%; margin: 20px auto;"></div>

<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY"></script>
<script>
let map = new google.maps.Map(document.getElementById("map"), {
    center: { lat: 35.681, lng: 139.767 }, // 東京駅中心
    zoom: 12
});

// PHP で取得した locations を JS に渡す場合
let locations = <?php echo json_encode($locations, JSON_UNESCAPED_UNICODE); ?>;

locations.forEach(loc => {
    let color = "yellow";
    if (loc.name.includes("学校")) color = "green";
    if (loc.name.includes("自宅")) color = "red";

    new google.maps.Marker({
        position: { lat: parseFloat(loc.lat), lng: parseFloat(loc.lng) },
        map: map,
        title: loc.name,
        icon: {
            path: google.maps.SymbolPath.CIRCLE,
            scale: 10,
            fillColor: color,
            fillOpacity: 0.8,
            strokeWeight: 0
        }
    });
});
</script>
