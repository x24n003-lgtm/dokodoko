function loadLocations() {
  fetch("locations.php")
    .then(res => res.json())
    .then(data => {
      data.forEach(loc => {
        let color = "yellow";
        if (loc.name.includes("学校")) color = "green";
        else if (loc.name.includes("自宅")) color = "red";

        // 円を描画
        new google.maps.Circle({
          strokeColor: color,
          strokeOpacity: 0.8,
          strokeWeight: 2,
          fillColor: color,
          fillOpacity: 0.5,
          map: map,
          center: { lat: parseFloat(loc.lat), lng: parseFloat(loc.lng) },
          radius: 50
        });

        // マーカーを描画
        new google.maps.Marker({
          position: { lat: parseFloat(loc.lat), lng: parseFloat(loc.lng) },
          map: map,
          title: loc.name
        });
      });
    });
}
