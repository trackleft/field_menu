<?php

namespace Drupal\field_menu\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_menu_tree_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_menu_tree_widget",
 *   module = "field_menu",
 *   label = @Translation("Menu item as tree key"),
 *   field_types = {
 *     "field_menu"
 *   }
 * )
 */
class TreeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_depth' => 0,
      'menu_item_key' => '',
      'include_root' => FALSE,
      'menu_title' => ''
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $elements = [];

    // Fist let's group in a fieldset
    $elements['menu_item_fieldset'] = [
      '#type' => 'details',
      '#title' => t('Menu item'),
      '#description' => t('A menu item to be displayed as a tree of menu links.'),
      '#open' => TRUE,
    ];

    $elements['menu_item_fieldset']['menu_title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => isset($items[$delta]->menu_title) ? $items[$delta]->menu_title : $this->getSetting('menu_title'),
      '#description' => t('Optional title for the menu.'),
    ];

    $menu_key_value = isset($items[$delta]->menu_item_key) ? $items[$delta]->menu_item_key : $this->getSetting('menu_item_key');

    // Get existing data from field if there is any
    $menu_key_value_arr = explode(':', $menu_key_value);
    $menu_name = (isset($menu_key_value_arr[0]) && $menu_key_value_arr[0]) ? $menu_key_value_arr[0]:null;
    $parent = (isset($menu_key_value_arr[1]) && $menu_key_value_arr[1]) ? $menu_key_value_arr[1]:null;
    $menu_link = (isset($menu_key_value_arr[2]) && $menu_key_value_arr[2]) ? $menu_key_value_arr[2]:null;
    $menu_parent = $menu_name  . ':' . $parent;

    // This builds a select field with all the menus the current user has access to with a unique key 
    // (using the same fuctionality as in when the CMS user adds a menu link to a node)
    $element += \Drupal::service('menu.parent_form_selector')->parentSelectElement($menu_parent, $menu_link);
    $element += [
      '#empty_value' => '',
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];
    $element['#default_value'] = $menu_key_value;
    $element['#description'] = t('Select a menu item from the available menu links');

    $elements['menu_item_fieldset']['menu_item_key'] = $element;

    $elements['menu_item_fieldset']['max_depth'] = [
      '#type' => 'number',
      '#title' => t('Max depth'),
      '#default_value' => isset($items[$delta]->max_depth) ? $items[$delta]->max_depth : $this->getSetting('max_depth'),
      '#description' => t('Maximum depth of the menu tree (0 is no limit).'),
      '#min' => 0,
    ];

    $elements['menu_item_fieldset']['include_root'] = [
      '#type' => 'checkbox',
      '#title' => t('Include root?'),
      '#description' => t('Include the root item in the tree or just the child elements'),
      '#default_value' => isset($items[$delta]->include_root) ? $items[$delta]->include_root : $this->getSetting('include_root'),
    ];


    return $elements;
  }

  /**
   * Validate the Menu item Key field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $menu_item_key = $element['#value'];
    if (strlen($menu_item_key) == 0) {
      $form_state->setValueForElement($element, '');
      return;
    }
  }


}
