<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="sotuken.css">
    <title>ストリートビュー連動GPS</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
</head>
<body>
    <div id="map" style="height:300px;width:100%;"></div>
    <div id="pano" style="height:500px;width:100%;"></div>

    <script>
        let map, marker, panorama;

        const initialLat = 35.704517; // 初期位置
        const initialLng = 139.984413;

        function initMap() {
            // マップ
            map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: initialLat, lng: initialLng },
                zoom: 18,
            });

            marker = new google.maps.Marker({
                position: { lat: initialLat, lng: initialLng },
                map: map,
                title: "現在地",
            });

            // ストリートビュー
            panorama = new google.maps.StreetViewPanorama(
                document.getElementById("pano"),
                {
                    position: { lat: initialLat, lng: initialLng },
                    pov: { heading: 165, pitch: 0 },
                    visible: true
                }
            );

            // ストリートビューの移動を監視
            panorama.addListener("position_changed", () => {
                const pos = panorama.getPosition();
                const lat = pos.lat();
                const lng = pos.lng();

                // マーカーと地図を更新
                marker.setPosition({ lat, lng });
                map.setCenter({ lat, lng });

                console.log("現在位置:", lat, lng);
            });
        }

        window.onload = initMap;
    </script>
</body>
</html>
