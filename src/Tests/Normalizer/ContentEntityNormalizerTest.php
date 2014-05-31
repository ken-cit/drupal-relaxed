<?php

namespace Drupal\couch_api\Tests\Normalizer;

use Drupal\Core\Language\Language;
use Drupal\Component\Utility\String;
use Drupal\serialization\Tests\NormalizerTestBase;

class ContentEntityNormalizerTest extends NormalizerTestBase {

  public static $modules = array('serialization', 'system', 'entity', 'field', 'entity_test', 'text', 'filter', 'user', 'key_value', 'multiversion', 'rest', 'uuid', 'couch_api');

  protected $entityClass = 'Drupal\entity_test\Entity\EntityTest';

  /**
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  public static function getInfo() {
    return array(
      'name'  => 'Content serialization',
      'description'  => 'Tests the content serialization format used for Couch API.',
      'group' => 'Couch API'
    );
  }

  protected function setUp() {
    parent::setUp();
    $this->installSchema('key_value', array('key_value_sorted'));

    \Drupal::service('multiversion.manager')
      ->attachRequiredFields('entity_test_mulrev', 'entity_test_mulrev');

    // @todo: Attach a file field once multiversion supports attachments.

    // Create a test entity to serialize.
    $this->values = array(
      'name' => $this->randomName(),
      'user_id' => 0,
      'field_test_text' => array(
        'value' => $this->randomName(),
        'format' => 'full_html',
      ),
    );
    $this->entity = entity_create('entity_test_mulrev', $this->values);
    $this->entity->save();

    $this->serializer = $this->container->get('serializer');
  }

  public function testNormalize() {
    $expected = array(
      'id' => array(
        array('value' => 1),
      ),
      'revision_id' => array(
        array('value' => 1),
      ),
      'uuid' => array(
        array('value' => $this->entity->uuid()),
      ),
      'langcode' => array(
        array('value' => Language::LANGCODE_NOT_SPECIFIED),
      ),
      'default_langcode' => array(
        array('value' => NULL),
      ),
      'name' => array(
        array('value' => $this->values['name']),
      ),
      'type' => array(
        array('value' => 'entity_test_mulrev'),
      ),
      'user_id' => array(
        array('target_id' => $this->values['user_id']),
      ),
      'field_test_text' => array(
        array(
          'value' => $this->values['field_test_text']['value'],
          'format' => $this->values['field_test_text']['format'],
        ),
      ),
      '_id' => $this->entity->uuid(),
      '_rev' => $this->entity->_revs_info->rev,
      '_entity_type' => $this->entity->getEntityTypeId(),
    );

    $normalized = $this->serializer->normalize($this->entity);

    foreach (array_keys($expected) as $fieldName) {
      $this->assertEqual($expected[$fieldName], $normalized[$fieldName], "Field $fieldName is normalized correctly.");
    }
    $this->assertEqual(array_diff_key($normalized, $expected), array(), 'No unexpected data is added to the normalized array.');

    // @todo Test context switches.
  }

  public function testSerialize() {
    $normalized = $this->serializer->normalize($this->entity);
    $expected = json_encode($normalized);
    // Paranoid test because JSON serialization is tested elsewhere.
    $actual = $this->serializer->serialize($this->entity, 'json');
    $this->assertIdentical($actual, $expected, 'Entity serializes correctly to JSON.');
  }

  public function testDenormalize() {
    $normalized = $this->serializer->normalize($this->entity);
    $denormalized = $this->serializer->denormalize($normalized, $this->entityClass, 'json');
    $this->assertTrue($denormalized instanceof $this->entityClass, String::format('Denormalized entity is an instance of @class', array('@class' => $this->entityClass)));
    $this->assertIdentical($denormalized->getEntityTypeId(), $this->entity->getEntityTypeId(), 'Expected entity type found.');
    $this->assertIdentical($denormalized->bundle(), $this->entity->bundle(), 'Expected entity bundle found.');
    $this->assertIdentical($denormalized->uuid(), $this->entity->uuid(), 'Expected entity UUID found.');

    // @todo Test context switches.
  }
}