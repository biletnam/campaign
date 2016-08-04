<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Site\Settings;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the ERR composite relationship upgrade path.
 *
 * @group paragraphs
 */
class ParagraphsCompositeRelationshipTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'paragraphs',
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create paragraphs and article content types.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsRevisions() {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'test_text',
      'id' => 'test_text',
    ));
    $paragraph_type->save();

    $paragraph_type_nested = ParagraphsType::create(array(
      'label' => 'test_nested',
      'id' => 'test_nested',
    ));
    $paragraph_type_nested->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'nested_paragraph_field',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => array(
        'target_type' => 'paragraph'
      ),
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'test_nested',
    ));
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => array(
        'target_type' => 'paragraph'
      ),
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'article',
    ));
    $field->save();

    // Add a paragraph field to the user.
    $field_storage = FieldStorageConfig::create(array(
      'field_name' => 'user_paragraph_field',
      'entity_type' => 'user',
      'type' => 'entity_reference_revisions',
      'settings' => array(
        'target_type' => 'paragraph'
      ),
    ));
    $field_storage->save();
    $field = FieldConfig::create(array(
      'field_storage' => $field_storage,
      'bundle' => 'user',
    ));
    $field->save();

    // Create a paragraph.
    $paragraph1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph1->save();
    // Create another paragraph.
    $paragraph2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph2->save();
    // Create another paragraph.
    $paragraph3 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph3->save();
    // Create another paragraph.
    $paragraph_nested_children1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph_nested_children1->save();
    // Create another paragraph.
    $paragraph_nested_children2 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph_nested_children2->save();

    // Create another paragraph.
    $paragraph4_nested_parent = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_nested',
      'nested_paragraph_field' => [$paragraph_nested_children1, $paragraph_nested_children2],
    ]);
    $paragraph4_nested_parent->save();

    // Create another paragraph.
    $paragraph_user_1 = Paragraph::create([
      'title' => 'Paragraph',
      'type' => 'test_text',
    ]);
    $paragraph_user_1->save();

    // Create a node with two paragraphs.
    $node = Node::create([
      'title' => $this->randomMachineName(),
      'type' => 'article',
      'node_paragraph_field' => array($paragraph1, $paragraph2, $paragraph3, $paragraph4_nested_parent),
      ]);
    $node->save();

    // Create an user with a paragraph.
    $user = User::create([
      'name' => 'test',
      'user_paragraph_field' => $paragraph_user_1,
    ]);
    $user->save();
    $settings = Settings::getAll();
    $settings['paragraph_limit'] = 1;
    new Settings($settings);

    // Unset the parent field name, type and id of paragraph1.
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = Paragraph::load($paragraph1->id());
    $paragraph->set('parent_field_name', NULL);
    $paragraph->set('parent_type', NULL);
    $paragraph->set('parent_id', NULL);
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();

    // Unset the parent field name, type and id of paragraph2.
    $paragraph = Paragraph::load($paragraph2->id());
    $paragraph->set('parent_field_name', NULL);
    $paragraph->set('parent_type', NULL);
    $paragraph->set('parent_id', NULL);
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();

    // Unset the parent field name, type and id of $paragraph_nested_parent.
    $paragraph = Paragraph::load($paragraph4_nested_parent->id());
    $paragraph->set('parent_field_name', NULL);
    $paragraph->set('parent_type', NULL);
    $paragraph->set('parent_id', NULL);
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();

    // Unset the parent field name, type and id of $paragraph_nested_children1.
    $paragraph = Paragraph::load($paragraph_nested_children1->id());
    $paragraph->set('parent_field_name', NULL);
    $paragraph->set('parent_type', NULL);
    $paragraph->set('parent_id', NULL);
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();

    // Unset the parent field name, type and id of paragraph_user_1.
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph */
    $paragraph = Paragraph::load($paragraph_user_1->id());
    $paragraph->set('parent_field_name', NULL);
    $paragraph->set('parent_type', NULL);
    $paragraph->set('parent_id', NULL);
    $paragraph->setNewRevision(FALSE);
    $paragraph->save();

    // Create a revision for node.
    /** @var \Drupal\node\Entity\Node $node_revision1 */
    $node_revision1 = Node::load($node->id());
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph1_revision1 */
    $paragraph1_revision1 = Paragraph::load($paragraph1->id());
    $paragraph1_revision1->setNewRevision(TRUE);
    $paragraph1_revision1->save();
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph2_revision1 */
    $paragraph2_revision1 = Paragraph::load($paragraph2->id());
    $paragraph2_revision1->setNewRevision(TRUE);
    $paragraph2_revision1->save();
    $node_revision1->set('node_paragraph_field', [$paragraph1_revision1, $paragraph2_revision1]);
    $node_revision1->setNewRevision(TRUE);
    $node_revision1->save();

    // Unset the parent field name, type and id of paragraph2_revision1.
    $paragraph2_revision1 = Paragraph::load($paragraph2_revision1->id());
    $paragraph2_revision1->set('parent_field_name', NULL);
    $paragraph2_revision1->set('parent_type', NULL);
    $paragraph2_revision1->set('parent_id', NULL);
    $paragraph2_revision1->setNewRevision(FALSE);
    $paragraph2_revision1->save();

    // Create another revision for node.
    /** @var \Drupal\node\Entity\Node $node_revision2 */
    $node_revision2 = Node::load($node->id());
    /** @var \Drupal\paragraphs\Entity\Paragraph $paragraph1_revision2 */
    $paragraph1_revision2 = Paragraph::load($paragraph1->id());
    $paragraph1_revision2->setNewRevision(TRUE);
    $paragraph1_revision2->save();
    $node_revision2->set('node_paragraph_field', [$paragraph1_revision2]);
    $node_revision2->setNewRevision(TRUE);
    $node_revision2->save();

    // Run update function and check #finished.
    $sandbox = [];
    do {
      paragraphs_update_8003($sandbox);
    } while ($sandbox['#finished'] < 1);

    $node_paragraph1 = Paragraph::load($paragraph1->id())->toArray();
    // Check if the fields are properly set.
    self::assertEquals($node_paragraph1['parent_id'][0]['value'], $node->id());
    self::assertEquals($node_paragraph1['parent_type'][0]['value'], $node->getEntityTypeId());
    self::assertEquals($node_paragraph1['parent_field_name'][0]['value'], 'node_paragraph_field');

    $node_paragraph2 = Paragraph::load($paragraph2->id())->toArray();
    // Check if the fields are properly set.
    self::assertEquals($node_paragraph2['parent_id'][0]['value'], $node->id());
    self::assertEquals($node_paragraph2['parent_type'][0]['value'], $node->getEntityTypeId());
    self::assertEquals($node_paragraph2['parent_field_name'][0]['value'], 'node_paragraph_field');

    $user_paragraph = Paragraph::load($paragraph_user_1->id())->toArray();
    // Check if the fields are properly set.
    self::assertEquals($user_paragraph['parent_id'][0]['value'], $user->id());
    self::assertEquals($user_paragraph['parent_type'][0]['value'], $user->getEntityTypeId());
    self::assertEquals($user_paragraph['parent_field_name'][0]['value'], 'user_paragraph_field');

    $nested_paragraph_parent = Paragraph::load($paragraph4_nested_parent->id())->toArray();
    // Check if the fields are properly set.
    self::assertEquals($nested_paragraph_parent['parent_id'][0]['value'], $node->id());
    self::assertEquals($nested_paragraph_parent['parent_type'][0]['value'], $node->getEntityTypeId());
    self::assertEquals($nested_paragraph_parent['parent_field_name'][0]['value'], 'node_paragraph_field');

    $nested_paragraph_children = Paragraph::load($paragraph_nested_children1->id())->toArray();
    // Check if the fields are properly set.
    self::assertEquals($nested_paragraph_children['parent_id'][0]['value'], $paragraph4_nested_parent->id());
    self::assertEquals($nested_paragraph_children['parent_type'][0]['value'], $paragraph4_nested_parent->getEntityTypeId());
    self::assertEquals($nested_paragraph_children['parent_field_name'][0]['value'], 'nested_paragraph_field');

  }
}