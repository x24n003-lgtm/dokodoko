<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>位置情報取得サンプル</title>
</head>
<body>

<button id="getLocation">位置情報を取得</button>
<p id="output"></p>

<script>
  const btn = document.getElementById('getLocation');
  const output = document.getElementById('output');

  btn.addEventListener('click', () => {
    // Geolocationがサポートされているか確認
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        position => {
          const lat = position.coords.latitude;   // 緯度
          const lon = position.coords.longitude;  // 経度
          output.textContent = `緯度: ${lat}, 経度: ${lon}`;
        },
        error => {
          switch(error.code) {
            case error.PERMISSION_DENIED:
              output.textContent = "位置情報の取得が許可されていません";
              break;
            case error.POSITION_UNAVAILABLE:
              output.textContent = "位置情報が利用できません";
              break;
            case error.TIMEOUT:
              output.textContent = "タイムアウトしました";
              break;
            default:
              output.textContent = "不明なエラーです";
          }
        }
      );
    } else {
      output.textContent = "このブラウザではGeolocationはサポートされていません";
    }
  });
</script>

</body>
</html>
