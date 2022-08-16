<?php

namespace Drupal\field_menu\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Menu\MenuActiveTrailInterface;
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
   * The active menu trail service.
   *
   * @var \Drupal\Core\Menu\MenuActiveTrailInterface
   */
  protected $menuActiveTrail;

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
   * @param \Drupal\Core\Menu\MenuActiveTrailInterface $menu_active_trail
   *   The active menu trail service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, MenuLinkTreeInterface $menu_link_tree, MenuActiveTrailInterface $menu_active_trail) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->menuLinkTree = $menu_link_tree;
    $this->menuActiveTrail = $menu_active_trail;
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
          $container->get('menu.link_tree'),
          $container->get('menu.active_trail')
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
      // Adjust the menu tree parameters based on the block's configuration.
      $menu_name = $item->menu;
      $level = $item->level;
      $depth = $item->depth;
      $expand_all_items = $item->expand;
      $parent = $item->parent;
      $follow = $item->follow;
      $follow_parent = $item->follow_parent;
      $following = FALSE;
      if ($expand_all_items) {
        $menu_parameters = new MenuTreeParameters();
        $active_trail = $this->menuActiveTrail->getActiveTrailIds($menu_name);
        $menu_parameters->setActiveTrail($active_trail);
      }
      else {
        $menu_parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menu_name);
      }

      $menu_parameters->setMinDepth($level);
      // If we're following the active trail and the active trail is deeper
      // than the initial starting level, we update the level to match the
      // active menu item's level in the menu.
      if ($follow && count($menu_parameters->activeTrail) > $level) {
        $level = count($menu_parameters->activeTrail);
        $following = TRUE;
      }

      // When the depth is configured to zero, there is no depth limit.
      // When depth is non-zero, it indicates the number of levels that
      // must be displayed.
      // Hence this is a relative depth that we must convert to an actual
      // (absolute) depth, that may never exceed the maximum depth.
      if ($depth > 0) {
        $menu_parameters->setMaxDepth(min($level + $depth - 1, $this->menuLinkTree->maxDepth()));
      }

      // For menu blocks with start level greater than 1, only show menu items
      // from the current active trail. Adjust the root according to the current
      // position in the menu in order to determine if we can show the subtree.
      if ($level > 1) {
        if (count($menu_parameters->activeTrail) >= $level) {
          // Active trail array is child-first. Reverse it, and pull the new
          // menu root based on the parent of the configured start level.
          $menu_trail_ids = array_reverse(array_values($menu_parameters->activeTrail));
          $menu_root = $menu_trail_ids[$level - 1];
          $menu_parameters->setRoot($menu_root)->setMinDepth(1);
          if ($depth > 0) {
            $menu_parameters->setMaxDepth(min($level - 1 + $depth - 1, $this->menuLinkTree->maxDepth()));
          }
        }
        else {
          return [];
        }
      }

      // If we're currently following an active menu item, or for menu blocks
      // with start level greater than 1, only show menu items from the current
      // trail. Adjust the root according to the current position in the menu in
      // order to determine if we can show the subtree. If we're not following
      // an active trail and using a fixed parent item, we'll skip this step.
      $fixed_parent_menu_link_id = str_replace($menu_name . ':', '', $parent);
      if ($following || ($level > 1 && !$fixed_parent_menu_link_id)) {
        if (count($menu_parameters->activeTrail) >= $level) {
          // Active trail array is child-first. Reverse it, and pull the
          // new menu root based on the parent of the configured start level.
          $menu_trail_ids = array_reverse(array_values($menu_parameters->activeTrail));
          $offset = ($following && $follow_parent === 'active') ? 2 : 1;
          $menu_root = $menu_trail_ids[$level - $offset];
          $menu_parameters->setRoot($menu_root)->setMinDepth(1);
          if ($depth > 0) {
            $menu_parameters->setMaxDepth(min($depth, $this->menuLinkTree->maxDepth()));
          }
        }
        else {
          return [];
        }
      }

      // If expandedParents is empty, the whole menu tree is built.
      if ($expand_all_items) {
        $menu_parameters->expandedParents = [];
      }

      // When a fixed parent item is set, root the menu tree at the given ID.
      if ($fixed_parent_menu_link_id) {
        // Clone the parameters so we can fall back to using them if we're
        // following the active menu item and the current page is part of the
        // active menu trail.
        $fixed_parameters = clone $parameters;
        $fixed_parameters->setRoot($fixed_parent_menu_link_id);
        $tree = $this->menuLinkTree->load($menu_name, $fixed_parameters);

        // Check if the tree contains links.
        if (empty($tree)) {
          // If the starting level is 1, we always want the child links to
          // appear, but the requested tree may be empty if the tree does
          // not contain the active trail. We're accessing the configuration
          // directly since the $level variable may have changed by this point.
          if ($level === 1 || $level === '1') {
            // Change the request to expand all children and limit the depth to
            // the immediate children of the root.
            $fixed_parameters->expandedParents = [];
            $fixed_parameters->setMinDepth(1);
            $fixed_parameters->setMaxDepth(1);
            // Re-load the tree.
            $tree = $this->menuLinkTree->load($menu_name, $fixed_parameters);
          }
        }
        elseif ($following) {
          // If we're following the active menu item, and the tree isn't empty
          // (which indicates we're currently in the active trail), we unset
          // the tree we made and just let the active menu parameters from
          // before do their thing.
          unset($tree);
        }
      }

      // Load the tree if we haven't already.
      if (!isset($tree)) {
        $tree = $this->menuLinkTree->load($menu_name, $menu_parameters);
      }
      $manipulators = [];
      $manipulators[] = ['callable' => 'menu.default_tree_manipulators:checkNodeAccess'];
      $manipulators[] = ['callable' => 'menu.default_tree_manipulators:checkAccess'];
      $manipulators[] = ['callable' => 'menu.default_tree_manipulators:generateIndexAndSort'];

      $tree = $this->menuLinkTree->transform($tree, $manipulators);
      $tree_render_array = $this->menuLinkTree->build($tree);
      $menu_title = trim($item->title);
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
