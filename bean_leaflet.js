/* global L:true */
(function($) {
  Drupal.behaviors.beanLeaflet = {
    attach: function(context) {
      var settings = Drupal.settings.bean_leaflet;
      if (settings) {
        for (var selector in settings) if (settings.hasOwnProperty(selector)) {
          var $leaflet = $(selector).once('bean-leaflet');
          if ($leaflet.length) new BeanLeaflet($leaflet, settings[selector]);
        }
      }
    }
  };

  Drupal.beanLeaflet = Drupal.beanLeaflet || {};

  Drupal.beanLeaflet.getMap = function(mapId) {
    // Find the leaflet map. This property is exposed by a custom patch in
    // leaflet_markercluster.drupal.js
    for (var i = 0, l = Drupal.settings.leaflet.length; i < l; i++) {
      var leaflet = Drupal.settings.leaflet[i];
      if (leaflet.mapId !== mapId || !leaflet.cluster_layer) continue;
      return leaflet = leaflet;
    }
  };

  Drupal.beanLeaflet.geojsonOptions = {
    onEachFeature: function(feature, layer) {
      layer.bindPopup(feature.properties.popup);
    }
  };

  function BeanLeaflet($el, settings) {
    this.$el = $el;
    this.settings = settings;
    // Hack, but leaflet module should really expose this.
    this.$map = $el.find('.leaflet-map > div');
    this.mapId = this.$map.prop('id');
    this.$filter = $el.find('.leaflet-filters');
    // Used for populating the query parameters to the geojson request.
    this.filters = {};
    this.layerCache = {};

    this.$filter.on('click', '.leaflet-filter-links a', $.proxy(this.triggerFilter, this));
  }

  BeanLeaflet.prototype.triggerFilter = function(e) {
    var $this = $(e.target)
      , value = $this.data('filter-value')
      , filter = $this.parents('[data-filter-name]').data('filter-name');

    $this.addClass('throbber');

    this.filters[filter] = value;
    e.preventDefault();
    this.fetchGeoJSON();
  };

  BeanLeaflet.prototype.fetchGeoJSON = function() {
    var query = $.param(this.filters)
      , url = this.settings.geojson + '?' + query
      , that = this;

    function cancelThrobber() {
      $('.leaflet-filter-links a').removeClass('throbber');
    }

    // If we've already fetched this layer, dont request it again.
    if (this.layerCache[query]) {
      this.rebuildMap(this.layerCache[query]);
      cancelThrobber();
      return;
    }

    $.getJSON(url, function(data) {
      var geojsonLayer = new L.GeoJSON(data, Drupal.beanLeaflet.geojsonOptions);
      that.layerCache[query] = geojsonLayer;
      that.rebuildMap(geojsonLayer);
      cancelThrobber();
    });
  };

  BeanLeaflet.prototype.rebuildMap = function(layer) {
    if (!this.leaflet) this.leaflet = Drupal.beanLeaflet.getMap(this.mapId);
    this.leaflet.cluster_layer.clearLayers();
    this.leaflet.cluster_layer.addLayer(layer);
  };

}(jQuery));
