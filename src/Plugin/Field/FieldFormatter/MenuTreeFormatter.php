<?php

namespace Drupal\field_menu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Plugin implementation of the 'field_menu_tree_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_menu_tree_formatter",
 *   module = "field_menu",
 *   label = @Translation("Menu tree formatter"),
 *   field_types = {
 *     "field_menu"
 *   }
 * )
 */
class MenuTreeFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The menu link tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuLinkTree;

  /**
   * Constructs a MenuTreeFormatter instance.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings settings.
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_link_tree
   *   The menu link tree.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MenuLinkTreeInterface $menu_link_tree) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->menuLinkTree = $menu_link_tree;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('menu.link_tree')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    // Set the values here so we can pass them to the menu.
    $this->configuration['providing_entity'] = $items->getEntity();
    $this->configuration['view_mode'] = $this->viewMode;

    foreach ($items as $delta => $item) {

      $menu_key_value_arr = explode(':', $item->menu_item_key);
      $menu_name = (isset($menu_key_value_arr[0]) && $menu_key_value_arr[0]) ? $menu_key_value_arr[0] : NULL;
      $parent = (isset($menu_key_value_arr[1]) && $menu_key_value_arr[1]) ? $menu_key_value_arr[1] : NULL;
      $menu_link = (isset($menu_key_value_arr[2]) && $menu_key_value_arr[2]) ? $menu_key_value_arr[2] : NULL;

      $menu_route = ($parent == 'menu_link_content') ? $parent . ':' . $menu_link : $parent;

      $menu_parameters = new MenuTreeParameters();
      $menu_parameters->setRoot($menu_route);
      $menu_parameters->onlyEnabledLinks();
      if ($item->max_depth > 0) {
        $menu_parameters->setMaxDepth($item->max_depth);
      }
      if (!$item->include_root) {
        $menu_parameters->excludeRoot();
      }

      $tree = $this->menuLinkTree->load($menu_name, $menu_parameters);

      $manipulators = [
        ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'],
        ['callable' => 'menu.default_tree_manipulators:checkAccess'],
        ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'],
      ];

      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $tree_render_array = $this->menuLinkTree->build($tree);
      $menu_title = trim($item->menu_title);
      if (!empty($tree_render_array['#theme'])) {
        // Add the configuration for use in menu_block_theme_suggestions_menu().
        $tree_render_array['#field_menu_configuration'] = $this->configuration;
        // Set the generated label into the configuration array so it is
        // propagated to the theme preprocessor and template(s) as needed.
        $tree_render_array['#field_menu_configuration']['label'] = $menu_title;
        // Remove the menu name-based suggestion so we can control its
        // precedence better in menu_block_theme_suggestions_menu().
        $tree_render_array['#theme'] = 'menu';
      }
      $elements[$delta] = [
        '#theme' => 'field_menu_item',
        '#title' => $menu_title,
        '#tree' => $tree_render_array,
        '#cache' => [
          'contexts' => [
            'languages:' . LanguageInterface::TYPE_INTERFACE,
          ],
        ],
      ];
    }

    return $elements;
  }

}
