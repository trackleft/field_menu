<?php

namespace Drupal\field_menu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Entity\Menu;


/**
 * Plugin implementation of the 'field_menu' field type.
 *
 * @FieldType(
 *   id = "field_menu",
 *   label = @Translation("Menu field"),
 *   module = "field_menu",
 *   description = @Translation("Select and configure a menu to display."),
 *   default_widget = "field_menu_tree_widget",
 *   default_formatter = "field_menu_tree_formatter"
 * )
 */
class MenuItemId extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'menu' => [
          'description' => "The selected menu name.",
          'type' => 'varchar_ascii',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ],
        'options' => [
          'description' => "Includes menu parameters and other options.",
          'type' => 'blob',
          'size' => 'big',
          'serialize' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'menu';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('menu')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['menu'] = DataDefinition::create('string')
      ->setLabel('Menu');
    $properties['options'] = MapDataDefinition::create()
      ->setLabel(t('Menu Options'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'menu_type_checkbox' => [],
      'menu_type_checkbox_negate' => FALSE,
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {

    $element = [];

    $menu_options = array_map(function ($menu) { return $menu->label(); }, Menu::loadMultiple());
    asort($menu_options);

    $default_value = $this->getSetting('menu_type_checkbox') ?? [];
    $element['menu_type_checkbox'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Available menus'),
      '#options' => $menu_options,
      '#default_value' => $default_value,
      '#description' => $this->t('Select menu(s) to make available. Leave empty to show all.'),
    ];
    $default_value = $this->getSetting('menu_type_checkbox_negate') ?? FALSE;
    $element['menu_type_checkbox_negate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Negate the condition'),
      '#default_value' => $default_value,
      '#description' => $this->t('Selected menu(s) will be hidden.'),
    ];

    return $element;
  }

}
