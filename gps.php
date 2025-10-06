<!DOCTYPE html>
<html>
<head>
  <link rel="stylesheet" href="sotuken.css">
  <title>リアルタイムGPS取得</title>

  <!-- Google Maps APIの読み込み。自分のAPIキーを使う -->
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA2-Yo-Z_8bTG8KKCSf7fOTlH026W5wDwg"></script>
</head>
<body>
  <!-- 地図を表示するエリア -->
  <div id="map" style="height:500px;width:100%;"></div>

  <script>
    // グローバル変数としてmap（地図）とmarker（現在地マーカー）を定義
    let map, marker;

    // --- マップを初期化する関数 ---
    function initMap(lat, lng) {
      // Googleマップを作成
      map = new google.maps.Map(document.getElementById("map"), {
        center: { lat: lat, lng: lng }, // 地図の中心を指定した座標に設定
        zoom: 18, // 拡大レベル（数値が大きいほど拡大）
      });

      // 現在地を示すマーカーを作成
      marker = new google.maps.Marker({
        position: { lat: lat, lng: lng }, // マーカーの位置
        map: map, // 表示するマップ
        title: "現在地", // マーカーにカーソルを合わせた時に出るテキスト
      });
    }

    // --- 位置情報をリアルタイムで取得する部分 ---
    if (navigator.geolocation) {
      // watchPosition()：位置情報を継続的に監視する関数
      navigator.geolocation.watchPosition(
        (position) => {
          // 現在の緯度・経度を取得
          const lat = position.coords.latitude;
          const lng = position.coords.longitude;

          // もしマップがまだ作られていなければ、最初の一度だけ初期化
          if (!map) {
            initMap(lat, lng);
          } else {
            // すでにマップがある場合、中心とマーカーを現在位置に更新
            map.setCenter({ lat: lat, lng: lng });
            marker.setPosition({ lat: lat, lng: lng });
          }
        },
        (error) => {
          // 位置情報が取得できなかった場合のエラー処理
          console.error("位置情報の取得に失敗:", error);
        },
        {
          enableHighAccuracy: true, // 高精度モードを有効にする（GPSを優先）
          maximumAge: 0,            // 古い位置情報を使わない
          timeout: 5000             // 5秒以内に位置情報が得られない場合はエラー
        }
      );
    } else {
      // Geolocation APIが使えない場合の警告
      alert("このブラウザでは位置情報が取得できません");
    }
  </script>
</body>
</html>
