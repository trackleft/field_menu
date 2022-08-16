CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Use

INTRODUCTION
------------

The Field Menu module provides a field type that allows the selection
of a menu tree to display in page.

This is useful if, for example, you want a really customised sitemap.
Simply apply it to a Content Type or Paragraph entity so you can output
your own, dynamic, sitemap page with minimal configuration.

Other possiblitities include:
Upgrade from the Drupal 7 menu_bean module using Drupal 8/9/10 core content blocks.
Adding menus to content blocks, which can be embedded easily using entity_embed.
Adding blocks without the use of the block field module.
Replacement for menu_block.
Create a menu Paragraph
Add a menu to media items.
Add a menu to taxonomy terms.
Add a menu to a content type.
Add a menu to a fieldable entity.

REQUIREMENTS
------------

This module requires no modules outside of Drupal core.

INSTALLATION
------------

 * Install the Field Menu module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.

CONFIGURATION
-------------

Navigate to your chosen entity type, and add a field:
 * Click the add field button
 * Select the 'Menu item' field type and give your field a Label
 * Set the Field Settings to add the field to the entity type

 USE
-------------

CONFIGURING MENU FIELD
-----------------------

When adding or configuring a menu field, several configuration options are
available:

Basic Options:

  Title
    An optional title for the selected menu.

  Display title
    Checkbox to have the block title visible or not. If unchecked, the block
    title will remain accessible, but hidden visually.

Menu levels:

  Initial menu level
    The menu will only be visible if the menu item for the current page is at or
    below the selected starting level. Select level 1 to always keep this menu
    visible.

  Maximum number of menu levels to display
    The maximum number of menu levels to show, starting from the initial menu
    level. For example: with an initial level 2 and a maximum number of 3, menu
    levels 2, 3 and 4 can be displayed.

Advanced options:

  Expand all menu links
    All menu links that have children will "Show as expanded".

  Fixed parent item
    Alter the options in “Menu levels” to be relative to the fixed parent item.
    The block will only contain children of the selected menu link.

See the field configuration page within your site to see all options.

 THEME
-------------
