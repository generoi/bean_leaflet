<?php

/**
 * Theme the leaflet filters.
 */
function theme_bean_leaflet_filter($variables) {
  $element = $variables['element'];
  $output = '<div ' . drupal_attributes($variables['#attributes']) . '>';
  foreach (element_children($element) as $filter) {
    $output .= '<div>' . render($element[$filter]) . '</div>';
  }
  $output .= '</div>';
  return $output;
}

/**
 * Theme a single leaflet filter.
 */
function theme_bean_leaflet_filter_single($variables) {
  $name = $variables['name'];
  $title = $variables['title'];
  $options = $variables['options'];
  $links = array();
  foreach ($options as $id => $option) {
    $class = 'no-margin-left leaflet-filter-' . $id;
    $links[$class] = array(
      'title' => $option,
      'href' => 'taxonomy/term/' . $id, // @TODO,
      'attributes' => array(
        'data-filter-value' => $id,
        'class' => array('button'),
      ),
    );
  }
  $element = array(
    'links' => $links,
    'attributes' => array(
      'class' => array('margin-top', 'leaflet-filter-links', 'no-bullet', 'inline-list'),
      'data-filter-name' => $name,
    ),
  );
  if (!empty($title)) {
    $element['heading'] = array(
      'text' => $title,
      'level' => 'h4',
    );
  }
  $output = theme('links', $element);
  return $output;
}

function template_preprocess_bean_leaflet_filter(&$variables) {
  $variables['#attributes'] = array(
    'class' => array('leaflet-filters'),
  );
}

function template_preprocess_bean_leaflet_filter_single(&$variables) {
}
