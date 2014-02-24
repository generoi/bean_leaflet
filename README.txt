Bean Leaflet
============

Provide a filterable (only taxonomies for now) leaflet bean with a GeoJSON
backend.

Note! For small amounts of data consider using leaflet_geojson or leaflet_views
instead, the reason to use this module is to bypass entity loading in views and
instead hardcode the database query for optimal performance.

If leaflet_geojson is installed, views_geojson backends are supported as well.
