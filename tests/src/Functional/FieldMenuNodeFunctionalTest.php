<?php

namespace Drupal\Tests\field_menu\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the field_menu module.
 *
 * @group field_menu
 */
class FieldMenuNodeFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field_menu_node_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The profile to install as a basis for testing.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * An administrative user to configure the test environment.
   *
   * @var \Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->moduleHandler = \Drupal::moduleHandler();

    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'create field_menu_sitemap content',
      'edit own field_menu_sitemap content',
    ]);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests that the proper options appear in the create node form for the menu
   * field.
   */
  public function testFieldMenuAddContent() {
    $this->drupalGet('/node/add/field_menu_sitemap');
    // Ensure menu filtering is working for field_menu if menu options are set
    // to negate.
    $expected_menu_options = [
      'footer' => 'Footer',
      'main' => 'Main navigation',
    ];
    $actual_menu_options = $this->getOptions('field_menu[0][menu]');
    $this->assertEquals($actual_menu_options, $expected_menu_options);

  }

}
