<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>リアルタイムGPS送信</title>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
</head>
<body>
  <h2>現在地をリアルタイム送信中...</h2>
  <div id="map" style="height:500px;width:100%;"></div>

  <script>
    let map, marker;

    function initMap(lat, lng) {
      map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: lat, lng: lng },
        zoom: 18,
      });

      marker = new google.maps.Marker({
        position: { lat: lat, lng: lng },
        map: map,
        title: "現在地",
      });
    }

    // 位置情報の監視
    if (navigator.geolocation) {
      navigator.geolocation.watchPosition(
        (position) => {
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          if (!map) {
            initMap(lat, lng);
          } else {
            map.setCenter({ lat: lat, lng: lng });
            marker.setPosition({ lat: lat, lng: lng });
          }

          // ★ DBに送信する部分 ★
          const name = "久保柊馬"; // ←自分の識別名に変更

          fetch("save_locations.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
              name: name,
              lat: lat,
              lng: lng
            }),
          })
          .then(res => res.text())
          .then(txt => console.log("サーバー応答:", txt))
          .catch(err => console.error("送信エラー:", err));
        },
        (error) => {
          console.error("位置情報取得失敗:", error);
        },
        {
          enableHighAccuracy: true,
          maximumAge: 0,
          timeout: 5000
        }
      );
    } else {
      alert("このブラウザでは位置情報が取得できません");
    }
  </script>
</body>
</html>
