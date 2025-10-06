<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="sotuken.css">
  <title>リアルタイムGPS取得</title>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
</head>
<body>
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

    // 位置情報をリアルタイムで取得
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
        },
        (error) => {
          console.error("位置情報の取得に失敗:", error);
        },
        { enableHighAccuracy: true, maximumAge: 0, timeout: 5000 }
      );
    } else {
      alert("このブラウザでは位置情報が取得できません");
    }
  </script>
</body>
</html>
