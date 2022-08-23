<?php

namespace Drupal\field_menu\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
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
        'title' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'menu' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'follow_parent' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'level' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'small',
          'not null' => FALSE,
        ],
        'depth' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'small',
          'not null' => FALSE,
        ],
        'follow' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'expand_all_items' => [
          'type' => 'int',
          'unsigned' => FALSE,
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'parent' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'render_parent' => [
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
    $properties['title'] = DataDefinition::create('string')->setLabel(t('Title'));
    $properties['menu'] = DataDefinition::create('string')->setLabel('Menu');
    $properties['follow_parent'] = DataDefinition::create('string')->setLabel('Follow parent');
    $properties['level'] = DataDefinition::create('integer')->setLabel(t('Level'));
    $properties['depth'] = DataDefinition::create('integer')->setLabel(t('Depth'));
    $properties['follow'] = DataDefinition::create('integer')->setLabel('Follow');
    $properties['expand_all_items'] = DataDefinition::create('integer')->setLabel('Expand all items');
    $properties['parent'] = DataDefinition::create('string')->setLabel('Parent menu link');
    $properties['render_parent'] = DataDefinition::create('integer')->setLabel('Render parent menu link');

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
