fetch('locations.php')
  .then(res => res.json())
  .then(data => {
    data.forEach(loc => {
      let color;
      if (loc.name.includes("学校")) color = "green";
      else if (loc.name.includes("自宅")) color = "red";
      else color = "yellow";

      L.circle([parseFloat(loc.lat), parseFloat(loc.lng)], {
        color: color,
        fillColor: color,
        fillOpacity: 0.5,
        radius: 50
      }).addTo(map).bindPopup(loc.name);
    });
  });
