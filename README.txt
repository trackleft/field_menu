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

This is useful if for example you want a really customised sitemap.
Simply apply it to a Content Type or Paragraph entity so you can output
your own, dynamic, sitemap page with minimal configuration.

Other possiblitities include:
A replacement for the Drupal 7 menu_bean module using content blocks.
Adding menus to content blocks, which can be embedded easily using entity_embed.
Adding blocks without the use of the block field module.
Replacement for menu_block.

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

A Menu item field appering in an entity edit form has the following options:
 * Title: an optional title for the selected menu tree
 * Menu: the existing menu the user wishes to display.
 * Initial visibility level: The menu is only visible if the menu link for the
 current page is at this level or below it. Use level 1 to always display this menu.
 * Number of levels to display: This maximum number includes the initial level.
 * Make the initial visibility level follow the active menu item: If the active
 menu item is deeper than the initial visibility level set above, the initial
 visibility level will be relative to the active menu item. Otherwise, the
 initial visibility level of the tree will remain fixed.
 * Initial visibility level will be (Active menu item) or (Children of active menu
 item): When following the active menu item, select whether the initial
 visibility level should be set to the active menu item, or its children.

