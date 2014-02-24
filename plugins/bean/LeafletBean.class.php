<?php
/**
 * @file
 * Leaflet GeoJSON bean plugin.
 */

class LeafletBean extends BeanPlugin {
  /**
   * Declares default block settings.
   */
  public function values() {
    $values = array(
      'settings' => array(
        'map' => NULL,
        'height' => '400',
        'geojson_source' => FALSE,
        'source' => NULL,
        'content_type' => NULL,
        'view_mode' => 'teaser',
        'geofield' => NULL,
        'init_markers' => TRUE,
        'override_map_settings' => FALSE,
        'map_settings' => array(
          // Main options
          'center' => array('lat' => 0, 'lon' => 0),
          'zoom' => 1,
          'minZoom' => 1,
          'maxZoom' => 18,
          'maxBounds' => NULL,

          // Interaction options
          'dragging' => TRUE,
          'touchZoom' => TRUE,
          'scrollWheelZoom' => TRUE,
          'doubleClickZoom' => TRUE,
          'boxZoom' => TRUE,
          'tap' => TRUE,
          'tapTolerance' => 15,
          'worldCopyJump' => FALSE,
          'bounceAtZoomLimits' => TRUE,

          // Keyboard options
          'keyboard' => TRUE,
          'keyboardPanOffset' => 80,
          'keyboardZoomOffset' => 1,

          // Panning inertia options
          'inertia' => TRUE,
          'inertiaDeceleration' => 3000,
          'inertiaMaxSpeed' => 1500,
          'inertiaThreshold' => NULL,

          // Control options
          'zoomControl' => TRUE,
          'attributionControl' => TRUE,

          // Animation options
          'fadeAnimation' => NULL,
          'zoomAnimation' => NULL,
          'zoomAnimationThreshold' => 4,
          'markerZoomAnimation' => NULL,

          // MarkerCluster options
          'showCoverageOnHover' => TRUE,
          'zoomToBoundsOnClick' => TRUE,
          'spiderifyOnMaxZoom' => TRUE,
          'removeOutsideVisibleBounds' => TRUE,
          'animateAddingMarkers' => TRUE,
          'disableClusteringAtZoom' => NULL,
          'maxClusterRadius' => 80,
          'singleMarkerMode' => FALSE,
          'spiderfyDistanceMultiplier' => 1,

          // Custom
          'trackResize' => TRUE,
          'closePopupOnClick' => TRUE,
        ),
      ),
      'filters' => array(),
    );
    return array_merge(parent::values(), $values);
  }
  /**
   * Builds extra settings for the block edit form.
   */
  public function form($bean, $form, &$form_state) {
    $form = array();
    $form['settings'] = array(
      '#type' => 'fieldset',
      '#tree' => 1,
      '#title' => t('Settings'),
    );
    $form['settings']['#attached']['css'][] = drupal_get_path('module', 'bean_leaflet') . '/bean_leaflet.admin.css';

    if (module_exists('leaflet_geojson')) {
      $form['settings']['geojson_source'] = array(
        '#type' => 'checkbox',
        '#title' => t('Use a custom GeoJSON source'),
        '#description' => t('E.g. Views GeoJSON (requires leaflet_geojson module)'),
        '#default_value' => $bean->settings['geojson_source'],
      );
      // Choose a geojson source.
      $source_options = array('' => t('None')) + $this->getSourceOptions();
      $default_source = isset($bean->settings['source']) ? $bean->settings['source'] : NULL;
      $form['settings']['source'] = array(
        '#type' => 'select',
        '#title' => t('GeoJSON source'),
        '#options' => $source_options,
        '#default_value' => $default_source,
        '#description' => t('Choose the GeoJSON source that will provide the map data.'),
        '#states' => array(
          'visible' => array(
            ':input[name="settings[geojson_source]"]' => array('checked' => TRUE),
          ),
        ),
      );
    }
    // Choose a map preset.
    $map_options = $this->getMapOptions();
    $default_map = isset($bean->settings['map']) ? $bean->settings['map'] : key($map_options);
    $form['settings']['map'] = array(
      '#title' => t('Leaflet map'),
      '#type' => 'select',
      '#options' => $map_options,
      '#default_value' => $default_map,
      '#required' => TRUE,
      '#description' => t('Select the Leaflet map that will display the data.'),

    );
    $form['settings']['height'] = array(
      '#title' => t('Map height'),
      '#type' => 'textfield',
      '#field_suffix' => t('px'),
      '#size' => 4,
      '#default_value' => $bean->settings['height'],
      '#required' => FALSE,
      '#description' => t('Set the map height in pixels.'),
    );

    $form['settings']['init_markers'] = array(
      '#type' => 'checkbox',
      '#title' => t('Load markers on page init'),
      '#description' => t('If unchecked, markers wont be loaded until filtering begins.'),
      '#default_value' => $bean->settings['init_markers'],
    );

    $content_types = node_type_get_names();
    $form['settings']['content_type'] = array(
      '#type' => 'select',
      '#title' => t('Content type'),
      '#options' => $content_types,
      '#default_value' => $bean->settings['content_type'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[geojson_source]"]' => array('checked' => FALSE),
        ),
      ),
    );
    $form['settings']['view_mode'] = array(
      '#type' => 'select',
      '#title' => t('Content types view mode'),
      '#options' => $this->getViewModes(),
      '#default_value' => $bean->settings['view_mode'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[geojson_source]"]' => array('checked' => FALSE),
        ),
      ),
    );

    $form['settings']['geofield'] = array(
      '#type' => 'select',
      '#title' => t('Geofield'),
      '#options' => $this->getFieldByType('node', $bean->settings['content_type'], 'geofield'),
      '#default_value' => $bean->settings['geofield'],
      '#states' => array(
        'visible' => array(
          ':input[name="settings[geojson_source]"]' => array('checked' => FALSE),
        ),
      ),
    );

    // Optionally override map settings.
    $form['settings']['override_map_settings'] = array(
      '#type' => 'checkbox',
      '#title' => 'Override map settings',
      '#default_value' => $bean->settings['override_map_settings'],
      '#description' => t('Choose to override settings as zoom level & center of the map.'),
    );

    $textfield_base = array(
      '#type' => 'textfield',
      '#size' => 20,
    );
    $checkbox_base = array(
      '#type' => 'checkbox',
    );

    $form['settings']['map_settings'] = array(
      '#type' => 'fieldset',
      '#title' => 'Map settings overrides',
      '#tree' => TRUE,
      '#states' => array(
        'visible' => array(
          ':input[name="settings[override_map_settings]"]' => array('checked' => TRUE),
        ),
      ),
    );
    $form['settings']['map_settings']['zoom'] = $textfield_base + array(
      '#title' => t('Zoom'),
      '#default_value' => $bean->settings['map_settings']['zoom'],
    );
    $form['settings']['map_settings']['center'] = array(
      '#type' => 'fieldset',
      '#title' => 'Map center',
      '#tree' => TRUE,
      '#description' => t('Provide a default map center'),
    );
    $form['settings']['map_settings']['center']['lon'] = $textfield_base + array(
      '#title' => t('Center longitude'),
      '#default_value' => $bean->settings['map_settings']['center']['lon'],
    );
    $form['settings']['map_settings']['center']['lat'] = $textfield_base + array(
      '#title' => t('Center latitude'),
      '#default_value' => $bean->settings['map_settings']['center']['lat'],
    );
    $form['settings']['map_settings']['minZoom'] = $textfield_base + array(
      '#title' => t('Minimum zoom'),
      '#default_value' => $bean->settings['map_settings']['minZoom'],
    );
    $form['settings']['map_settings']['maxZoom'] = $textfield_base + array(
      '#title' => t('Maximum zoom'),
      '#default_value' => $bean->settings['map_settings']['maxZoom'],
    );

    if (module_exists('leaflet_markercluster')) {
      $form['settings']['map_settings']['showCoverageOnHover'] = $checkbox_base + array(
        '#title' => t('Show coverage on hover'),
        '#default_value' => $bean->settings['map_settings']['showCoverageOnHover'],
      );
      $form['settings']['map_settings']['zoomToBoundsOnClick'] = $checkbox_base + array(
        '#title' => t('Zoom to bounds on click'),
        '#default_value' => $bean->settings['map_settings']['zoomToBoundsOnClick'],
      );
      $form['settings']['map_settings']['spiderifyOnMaxZoom'] = $checkbox_base + array(
        '#title' => t('Spiderify on max zoom'),
        '#default_value' => $bean->settings['map_settings']['spiderifyOnMaxZoom'],
      );
    }


    $form['filters'] = array(
      '#type' => 'fieldset',
      '#tree' => 1,
      '#title' => t('Filters'),
      '#states' => array(
        'visible' => array(
          ':input[name="settings[geojson_source]"]' => array('checked' => FALSE),
        ),
      ),
    );
    if (!empty($bean->settings['content_type'])) {
      $term_fields = array(
        '<none>' => t('None'),
      );
      $term_fields += $this->getFieldByType('node', $bean->settings['content_type'], 'taxonomy_term_reference');
      // Create 5 configurable filters
      // @TODO autogrow, autorebuild
      foreach (range(0, 3) as $filter_id) {
        $form['filters'][$filter_id] = array(
          '#type' => 'container',
          '#attributes' => array('class' => array('leaflet-filter-config')),
        );
        $form['filters'][$filter_id]['term_field'] = array(
          '#type' => 'select',
          '#title' => t('Taxonomy term field'),
          '#options' => $term_fields,
          '#default_value' => isset($bean->filters[$filter_id]['term_field']) ? $bean->filters[$filter_id]['term_field'] : NULL,
          '#required' => FALSE,
          '#multiple' => FALSE,
        );
        $form['filters'][$filter_id]['title'] = array(
          '#type' => 'textfield',
          '#title' => t('Title (localized)'),
          '#size' => 25,
          '#default_value' => isset($bean->filters[$filter_id]['title']) ? $bean->filters[$filter_id]['title'] : '',
        );
      }
    }
    else {
      $form['filters'][] = array(
        '#markup' => t('Select  a content type first, then save the bean and come back again (sorry)'),
      );
    }

    return $form;
  }

  /**
   * Displays the bean.
   */
  public function view($bean, $content, $view_mode = 'default', $langcode = NULL) {
    $entity_id = entity_id('bean', $bean);
    // Prepare leaflet map settings.
    $map = leaflet_map_get_info($bean->settings['map']);
    $height = $bean->settings['height'];

    if ($bean->settings['override_map_settings']) {
      $custom_settings = $bean->settings['map_settings'];
      if (isset($custom_settings['center'])) {
        $map['center'] = $custom_settings['center'];
        unset($custom_settings['center']);
      }
      $map['settings'] = array_merge($map['settings'], $custom_settings);
      foreach (array('showCoverageOnHover', 'zoomToBoundsOnClick', 'spiderifyOnMaxZoom') as $boolean_option) {
        if (isset($map['settings'][$boolean_option])) {
          $map['settings'][$boolean_option] = (bool) $map['settings'][$boolean_option];
        }
      }
      foreach (array('minZoom', 'maxZoom', 'zoom') as $boolean_option) {
        if (isset($map['settings'][$boolean_option])) {
          $map['settings'][$boolean_option] = (int) $map['settings'][$boolean_option];
        }
      }
    }

    $features = array();
    if ($bean->settings['init_markers']) {
      if ($bean->settings['geojson_source'] && module_exists('leaflet_geojson')) {
        $source_info = leaflet_geojson_source_get_info($bean->settings['source']);
        $context = array(
          'map' => &$map,
          'source_info' => &$source_info,
          'bean' => &$bean,

        );
        drupal_alter('leaflet_geojson_bean_view_features', $features, $context);
      }
      else {
        // $features = bean_leaflet_get_points($bean);
        $features[] = array(
          'type' => 'json',
          'json' => bean_leaflet_get_geojson($bean),
        );
      }
    }

    // Leaflet requires zoom and center to be set so for usability we set them 
    // if not otherwise set.
    if (!isset($map['settings']['zoom'])) {
      $map['settings']['zoom'] = 3;
    }
    if (!isset($map['center'])) {
      $map['center'] = array('lat' => 51.505, 'lon' => -0.09);
    }

    $filters = array();
    foreach ($bean->filters as $filter) {
      if ($filter['term_field'] == '<none>') {
        continue;
      }
      $field_info = field_info_field($filter['term_field']);
      $vocabulary_name = $field_info['settings']['allowed_values'][0]['vocabulary'];
      $vocabulary = taxonomy_vocabulary_machine_name_load($vocabulary_name);
      $terms = module_exists('i18n_taxonomy') ? i18n_taxonomy_get_tree($vocabulary->vid, i18n_langcode()) : taxonomy_get_tree($vocabulary->vid);
      $options = array();
      foreach ($terms as $term) {
        $options[$term->tid] = $term->name;
      }

      $filters[] = array(
        '#theme' => 'bean_leaflet_filter_single',
        '#name' => $filter['term_field'],
        '#title' => !empty($filter['title']) ? t($filter['title'], array(), array('context' => 'bean_leaflet:label')) : '',
        '#options' => $options,
      );
    }

    $content['#attached']['library'][] = array('bean_leaflet', 'leaflet');
    $content['#attached']['js'][] = array(
      'data' => array(
        'bean_leaflet' => array(
          '#bean-leaflet-' . $entity_id => array(
            'geojson' => url('geojson/' . $entity_id),
          ),
        ),
      ),
      'type' => 'setting',
    );

    $content['leaflet'] = array(
      '#type' => 'container',
      '#attributes' => array('id' => 'bean-leaflet-' . $entity_id),
    );
    $content['leaflet']['map'] = array(
      '#markup' => leaflet_render_map($map, $features, $height . 'px'),
    );
    $content['leaflet']['filters'] = array(
      '#theme' => 'bean_leaflet_filter',
    ) + $filters;
    return $content;
  }

  protected function getMapOptions() {
    $map_options = array();
    foreach (leaflet_map_get_info() as $key => $map) {
      $map_options[$key] = t($map['label']);
    }
    return $map_options;
  }

  protected function getSourceOptions() {
    $sources = leaflet_geojson_source_get_info(NULL, TRUE);
    $source_options = array();
    foreach ($sources as $id => $source) {
      $source_options[$id] = $source['title'];
    }
    return $source_options;
  }

  protected function getViewModes() {
    $node_view_modes = array();
    $entity_info = entity_get_info();
    foreach ($entity_info['node']['view modes'] as $key => $value) {
      $node_view_modes[$key] = $value['label'];
    }
    return $node_view_modes;
  }

  protected function getFieldByType($entity_type, $bundle, $field_type) {
    $term_fields = array();
    $fields = field_info_instances($entity_type, $bundle);
    foreach ($fields as $field_name => $field) {
      $field_info = field_info_field($field_name);
      if ($field_info['type'] == $field_type) {
        $term_fields[$field_name] = $field['label'];
      }
    }
    return $term_fields;
  }

}
