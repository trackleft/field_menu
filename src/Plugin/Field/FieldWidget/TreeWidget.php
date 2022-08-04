<?php

namespace Drupal\field_menu\Plugin\Field\FieldWidget;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\system\Entity\Menu;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'field_menu_tree_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_menu_tree_widget",
 *   module = "field_menu",
 *   label = @Translation("Menu field"),
 *   field_types = {
 *     "field_menu"
 *   }
 * )
 */
class TreeWidget extends WidgetBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * The parent form selector service.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuParentSelector;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
    ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->menuParentSelector = $container->get('menu.parent_form_selector');
    $instance->menuTree = $container->get('menu.link_tree');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'menu_title' => '',
      'menu' => '',
      'level' => 2,
      'depth' => 0,
      'expand_all_items' => FALSE,
      'follow' => FALSE,
      'follow_parent' => 'child',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {

    $element['menu_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $items[$delta]->menu_title ?? $this->getSetting('menu_title'),
      '#description' => $this->t('Optional title for the menu.'),
    ];

    /* Build a select field with all the menus the current user
     * has access to with a unique key
     * (uses the same fuctionality as when a user adds a menu link to a node)
     */

    // Limit menu list from field settings.
    $menu_options = $this->getSelectableMenus($items);

    $element['menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Menu'),
      '#default_value' => $items[$delta]->menu ?? $this->getSetting('menu'),
      '#options' => $menu_options,
      '#description' => $this->t('Select a menu'),
    ];

    $element['menu_levels'] = [
      '#type' => 'details',
      '#title' => $this->t('Menu levels'),
      // Open if not set to defaults.
      '#open' => $this->getSetting('level') !== $items[$delta]->level || $this->getSetting('depth') !== $items[$delta]->depth,
      '#process' => [[get_class(), 'processMenuLevelParents']],
      '#states' => [
        'visible' => [
          ':input[name="settings[menu]"]' => ['filled' => TRUE],
        ],
      ],
    ];

    $options = range(0, $this->menuTree->maxDepth());
    unset($options[0]);

    $element['menu_levels']['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Initial visibility level'),
      '#default_value' => $items[$delta]->level ?? $this->getSetting('level'),
      '#options' => $options,
      '#description' => $this->t('The menu is only visible if the menu link for the current page is at this level or below it. Use level 1 to always display this menu.'),
      '#required' => TRUE,
    ];

    $options[0] = $this->t('Unlimited');

    $element['menu_levels']['depth'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of levels to display'),
      '#default_value' => $items[$delta]->depth ?? $this->getSetting('depth'),
      '#options' => $options,
      '#description' => $this->t('This maximum number includes the initial level.'),
      '#required' => TRUE,
    ];

    $element['menu_levels']['expand_all_items'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Expand all menu links'),
      '#default_value' => $items[$delta]->expand_all_items ?? $this->getSetting('expand_all_items'),
      '#description' => $this->t('Override the option found on each menu link used for expanding children and instead display the whole menu tree as expanded.'),
    ];

    $element['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced options'),
      '#open' => FALSE,
      '#process' => [[get_class(), 'processMenuFieldSets']],
    ];

    $element['advanced']['follow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('<strong>Make the initial visibility level follow the active menu item.</strong>'),
      '#default_value' => $items[$delta]->follow ?? $items->getSetting('follow') ?? FALSE,
      '#description' => $this->t('If the active menu item is deeper than the initial visibility level set above, the initial visibility level will be relative to the active menu item. Otherwise, the initial visibility level of the tree will remain fixed.'),
      '#attributes' => [
        //define static name so we can easier select it
        'name' => 'field_menu_follow-' . $delta,
      ],
    ];

    $element['advanced']['follow_parent'] = [
      '#type' => 'radios',
      '#title' => $this->t('Initial visibility level will be'),
      '#description' => $this->t('When following the active menu item, select whether the initial visibility level should be set to the active menu item, or its children.'),
      '#default_value' => $items[$delta]->follow_parent ??  $items->getSetting('follow_parent') ?? FALSE,
      '#options' => [
        'active' => $this->t('Active menu item'),
        'child' => $this->t('Children of active menu item'),
      ],
      '#states' => [
        'visible' => [
          ':input[name="field_menu_follow-' . $delta . '"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $element += [
      '#element_validate' => [
      [$this, 'validate'],
      ],
    ];

    // If cardinality is 1, ensure a label is output for the field by wrapping
    // it in a details element.
    if ($this->fieldDefinition->getFieldStorageDefinition()->getCardinality() === 1) {
      $element += [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['container']],
      ];
    }

    return $element;
  }

  /**
   * Returns the sorted selectable menus for a menu field.
   *
   * Adjusts the selectable menus based on admin settings.
   *
   * @return array
   *   An array of human-readable menu names, keyed by their menu machine names.
   */
  public static function getSelectableMenus(FieldItemListInterface $items) {
    $menus = [];
      $menu_options = array_map(function ($menu) {
        return $menu->label();
      }, Menu::loadMultiple());
      asort($menu_options);

    if (!empty($items->getSetting('menu_type_checkbox'))) {
      $negate = $items->getSetting('menu_type_checkbox_negate') ?? FALSE;
      if ($negate) {
        $menu_selected = array_diff($items->getSetting('menu_type_checkbox'), array_keys($menu_options));
        $menu_selected = array_intersect_key($menu_options, $menu_selected);
      }
      else {
        $menu_selected = array_diff($items->getSetting('menu_type_checkbox'), [0]);
        $menu_selected = array_intersect_key($menu_options, $menu_selected);
      }
      $menus = empty($menu_selected) ? $menu_options : $menu_selected;
    }

    return $menus;
  }

  /**
   * Form API callback: Processes the elements in field sets.
   *
   * Adjusts the #parents of field sets to save its children at the top level.
   */
  public static function processMenuFieldSets(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * Form API callback: Processes the menu_levels field element.
   *
   * Adjusts the #parents of menu_levels to save its children at the top level.
   */
  public static function processMenuLevelParents(&$element, FormStateInterface $form_state, &$complete_form) {
    array_pop($element['#parents']);
    return $element;
  }

  /**
   * Validate the Menu item Key field.
   */
  public function validate($element, FormStateInterface $form_state) {
    $menu = $element['menu']['#value'] ?? '';
    if (strlen($menu) === 0) {
      $form_state->setValueForElement($element['menu'], '');
      if ($element['menu_title']['#value']) {
        $form_state->setError($element['menu'], $this->t("You must select a menu item if you have set a title"));
      }
    }
  }

}
