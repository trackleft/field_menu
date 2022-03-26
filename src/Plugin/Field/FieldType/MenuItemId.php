<?php

namespace Drupal\field_menu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_menu' field type.
 *
 * @FieldType(
 *   id = "field_menu",
 *   label = @Translation("Menu item"),
 *   module = "field_menu",
 *   description = @Translation("Select a valid Menu item"),
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
        'menu_title' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'menu_item_key' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'max_depth' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'small',
          'not null' => FALSE,
        ],
        'include_root' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('menu_item_key')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['menu_title'] = DataDefinition::create('string')->setLabel(t('Title'));
    $properties['menu_item_key'] = DataDefinition::create('string')->setLabel('');
    $properties['include_root'] = DataDefinition::create('integer')->setLabel(t('Include root'));
    $properties['max_depth'] = DataDefinition::create('integer')->setLabel(t('Max depth'));

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
    $menu_options = menu_ui_get_menus();
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
