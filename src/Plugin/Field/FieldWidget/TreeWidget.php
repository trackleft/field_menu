<?php

namespace Drupal\field_menu\Plugin\Field\FieldWidget;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

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
class TreeWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * Constructs a TreeWidget object.
   *
   * @param string $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Menu\MenuParentFormSelectorInterface $menu_parent_selector
   *   The menu link tree.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, MenuParentFormSelectorInterface $menu_parent_selector) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->menuParentSelector = $menu_parent_selector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('menu.parent_form_selector')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'max_depth' => 0,
      'menu_item_key' => '',
      'include_root' => FALSE,
      'menu_title' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['menu_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $items[$delta]->menu_title ?? $this->getSetting('menu_title'),
      '#description' => $this->t('Optional title for the menu.'),
    ];

    $menu_key_value = $items[$delta]->menu_item_key ?? $this->getSetting('menu_item_key');

    // Get existing data from field if there is any.
    $menu_key_value_arr = explode(':', $menu_key_value);
    $menu_name = $menu_key_value_arr[0] ?? NULL;
    $parent = $menu_key_value_arr[1] ?? NULL;
    $menu_link = $menu_key_value_arr[2] ?? NULL;
    $menu_parent = $menu_name . ':' . $parent;

    /* Build a select field with all the menus the current user
     * has access to with a unique key
     * (uses the same fuctionality as when a user adds a menu link to a node)
     */

    // Limit menu list from field settings.
    $menus = NULL;
    if (!empty($items->getSetting('menu_type_checkbox'))) {
      $negate = $items->getSetting('menu_type_checkbox_negate') ?? FALSE;
      if ($negate) {
        $menu_options = menu_ui_get_menus();
        $menu_selected = array_diff($items->getSetting('menu_type_checkbox'), array_keys($menu_options));
        $menu_selected = array_combine(array_keys($menu_selected), array_keys($menu_selected));
      }
      else {
        $menu_selected = array_diff($items->getSetting('menu_type_checkbox'), [0]);
      }
      $menus = empty($menu_selected) ? NULL : $menu_selected;
    }

    $menu_item_key_field = $this->menuParentSelector->parentSelectElement($menu_parent, $menu_link, $menus);
    $menu_item_key_field['#default_value'] = $menu_key_value;
    $menu_item_key_field['#description'] = $this->t('Select a menu root item from the available menu links');
    $menu_item_key_field += [
      '#empty_value' => '',
      '#title' => $this->t('Root'),
    ];

    $element['menu_item_key'] = $menu_item_key_field;

    $element['max_depth'] = [
      '#type' => 'number',
      '#title' => $this->t('Max depth'),
      '#default_value' => $items[$delta]->max_depth ?? $this->getSetting('max_depth'),
      '#description' => $this->t('Maximum depth of the menu tree (0 is no limit).'),
      '#min' => 0,
    ];

    $element['include_root'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include root?'),
      '#description' => $this->t('Include the root item in the tree or just the child elements'),
      '#default_value' => $items[$delta]->include_root ?? $this->getSetting('include_root'),
    ];

    $element += [
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() == 1) {
      $element += [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container']],
      ];
    }

    return $element;
  }

  /**
   * Validate the Menu item Key field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $menu_item_key = $element['menu_item_key']['#value'] ?? '';
    if (strlen($menu_item_key) == 0) {
      $form_state->setValueForElement($element['menu_item_key'], '');
      if ($element['menu_title']['#value']) {
        $form_state->setError($element['menu_item_key'], $this->t("You must select a menu item if you have set a title"));
      }
    }
  }

}
