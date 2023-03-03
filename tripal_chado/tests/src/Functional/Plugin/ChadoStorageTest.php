<?php

namespace Drupal\Tests\tripal_chado\Functional;

use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
use Drupal\tripal\TripalStorage\StoragePropertyTypeBase;
use Drupal\tripal\TripalStorage\StoragePropertyValue;
use Drupal\tripal\TripalVocabTerms\TripalTerm;
use Drupal\Tests\tripal_chado\Functional\MockClass\FieldConfigMock;

/**
 * Tests for the ChadoStorage Class.
 *
 * Testing of public functions in each test method.
 *  - testChadoStorage (OLD): addTypes, getTypes, loadValues
 *  - testChadoStorageCRUDtypes: addTypes, validateTypes(), getTypes,
 *      loadValues, removeTypes
 *  - testChadoStorageCRUDvalues: insertValues, loadValues, updateValues,
 *      validateValues, findValues, selectChadoRecord, validateSize
 *
 * Not Implemented in ChadoStorage: deleteValues, findValues
 *
 * @covers \Drupal\tripal_chado\Plugin\TripalStorage\ChadoStorage
 *
 * @group Tripal
 * @group Tripal Chado
 * @group Tripal Chado ChadoStorage
 */
class ChadoStorageTest extends ChadoTestBrowserBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['tripal', 'tripal_chado', 'field_ui'];

  /**
   * The unique identifier for a test piece of Tripal Content. This would be a
   * Tripal Content Page outside the test environment.
   *
   * @var int
   */
  protected $content_entity_id;

  /**
   * The unique identifier for a type of Tripal Content. This would be a
   * Tripal Content Type (e.g. gene) outside the test environment.
   *
   * @var string
   */
  protected $content_type;

  /**
   * {@inheritdoc}
   */
  protected function setUp() :void {

    parent::setUp();

    // Ensure we see all logging in tests.
    \Drupal::state()->set('is_a_test_environment', TRUE);

    // Create a new test schema for us to use.
    $connection = $this->createTestSchema(ChadoTestBrowserBase::PREPARE_TEST_CHADO);

    // All Chado storage testing requires an entity.
    $content_entity = $this->createTripalContent();
    $content_entity_id = $content_entity->id();
    $content_type = $content_entity->getType();
    $content_type_obj = \Drupal\tripal\Entity\TripalEntityType::load($content_type);

    $this->content_entity_id = $content_entity_id;
    $this->content_type = $content_type;

    // Prep the ID Spaces + vocabs.
    // We need to make sure the CVs we're going to use are registered.
    // they should already be loaded in the test Chado instance.
    $vmanager = \Drupal::service('tripal.collection_plugin_manager.vocabulary');
    $idsmanager = \Drupal::service('tripal.collection_plugin_manager.idspace');
    foreach (['SIO', 'schema'] as $name) {
      $vocabulary = $vmanager->createCollection($name, 'chado_vocabulary');
      $idSpace = $idsmanager->createCollection($name, 'chado_id_space');
      $idSpace->setDefaultVocabulary($vocabulary->getName());
    }
    // This term is missing from the current prepared test Chado so we
    // manually add it.
    $this->createTripalTerm([
      'vocab_name' => 'SIO',
      'id_space_name' => 'SIO',
      'term' => [
        'name' => 'record identifier',
        'accession' =>'000729',
      ]],
      'chado_id_space', 'chado_vocabulary'
    );
  }

  /**
   * All of these tests use this data provider to ensure we are testing multiple
   * cases effectively. These cases focus around property types, number of
   * properties per field and number of fields. They all map specifically to a
   * Chado table.
   *
   * @return array
   *   Each element in the returned array is a single test case which will be
   *   provided to test methods. For each test case there will be two keys:
   *    - fields (array): each element is an array describing a field...
   *        - name (string): the machine name of the field.
   *        - label (string): a human-readable name for the field.
   *        - base table (string): the name of a chado table, used in the
   *          storage settings.
   *        - valid (boolean): TRUE if all properties for this field are valid.
   *    - properties (array): each element is an array describing a property type...
   *        - field (string): the machine name of the field this property is part of
   *        - action (string): one of store_id, store_link, store_pkey, store,
   *          join, replace, function (see docs).
   *        - name (string): a unique-ish name for the property.
   *        - drupal_store (boolean): TRUE if it should be stored in the Drupal table.
   *        - chado_column (string): the name of the chado column this property acts on.
   *        - chado_table (string): the chado table the chado_column is part of.
   *        - valid (boolean): TRUE if validateTypes is expected to pass this property.
   *    - valid (boolean): TRUE if load/insertValues is expected to work. Specifically,
   *      if the table contraints are met and all properties/fields are valid.
   */
  public function provideTestCases() {
    $randomizer = $this->getRandomGenerator();
    $test_cases = [];

    // Expected to Work
    // ----------------------

    // Single field with very simple properties.
    // Simulates a field focused on the name of a database.
    // This was chosen since the db.name is the only non nullable field
    // in the chado.db table.
    $case = [ 'fields' => [], 'properties' => [], 'valid' => TRUE];

    $field_name = $this->randomMachineName(25);
    $case['fields'][] = [
      'name' => $field_name,
      'label' => $randomizer->word(rand(5,30)) . ' ' . $randomizer->word(rand(5,30)),
      'base_table' => 'db',
    ];

    // name (db.name)
    $case['properties'][] = [
      'field' => $field_name,
      'term' => 'schema:name',
      'type' => 'varchar',
      'action' => 'store',
      'name' => 'name',
      'drupal_store' => FALSE,
      'chado_column' => 'name',
      'chado_table' => 'db',
      'size' => 255,
      'valid' => TRUE,
    ];

    // primary key (db.db_id)
    $case['properties'][] = [
      'field' => $field_name,
      'term' => 'SIO:000729',
      'type' => 'int',
      'action' => 'store_id',
      'name' => 'primary_key',
      'drupal_store' => TRUE,
      'chado_column' => 'db_id',
      'chado_table' => 'db',
      'valid' => TRUE,
    ];

    // Yes, this test case supplies the required columns for the db table.
    $case['valid'] = TRUE;
    $test_cases[] = $case;

    // Expected to Fail
    // ----------------------

    return $test_cases;
  }

  /**
   * Tests CRUD related to StoragePropertyTypes used by ChadoStorage.
   *
   * Focus on addTypes(), getTypes(), removeTypes().
   * NOTE: we don't test validateTypes() here as that acts on StoragePropertyValues.
   *
   * @dataProvider provideTestCases
   */
  public function testStoragePropertyTypes(array $fields, array $properties, bool $valid) {
    $propertyTypes = [];
    $num_properties = 0;

    // Can we create the properties?
    foreach ($properties as $propdeets) {
      switch ($propdeets['type']) {
        case 'varchar':
          $propertyTypes[ $propdeets['name'] ] = new ChadoVarCharStoragePropertyType(
            $this->content_type,
            $propdeets['field'],
            $propdeets['name'],
            $propdeets['term'],
            $propdeets['size'],
            [
              'action' => $propdeets['action'],
              'drupal_store' => $propdeets['drupal_store'],
              'chado_column' => $propdeets['chado_column'],
              'chado_table' => $propdeets['chado_table'],
            ]
          );
          break;
        case 'int':
          $propertyTypes[ $propdeets['name'] ] = new ChadoIntStoragePropertyType(
            $this->content_type,
            $propdeets['field'],
            $propdeets['name'],
            $propdeets['term'],
            [
              'action' => $propdeets['action'],
              'drupal_store' => $propdeets['drupal_store'],
              'chado_column' => $propdeets['chado_column'],
              'chado_table' => $propdeets['chado_table'],
            ]
          );
          break;
      }

      $context = 'field: ' . $propdeets['field'] . '; property: ' . $propdeets['name'];
      $this->assertIsObject($propertyTypes[ $propdeets['name'] ],
        "Unable to create the Storage Property Type object ($context).");

      // Keep track of how many we have created for testing getTypes() later.
      $num_properties++;
    }

    // Get plugin managers we need for our testing.
    $storage_manager = \Drupal::service('tripal.storage');
    $chado_storage = $storage_manager->createInstance('chado_storage');

    // Can we add them?
    $return_value = $chado_storage->addTypes($propertyTypes);
    $this->assertNotFalse($return_value, "We were unable to add our properties using addTypes().");

    // Can we retrieve them?
    $retrieved_types = $chado_storage->getTypes();
    $this->assertCount($num_properties, $retrieved_types, "Did not retrieve the expected number of PropertyTypes when using getTypes().");
    foreach ($retrieved_types as $rtype) {
      $this->assertInstanceOf(StoragePropertyTypeBase::class, $rtype, "The retrieved property type does not inherit from our StoragePropertyTypeBase?");
      $rkey = $rtype->getKey();
      $this->assertArrayHasKey($rkey, $propertyTypes, "We did not add a type with the key '$rkey' but we did retrieve it?");
    }

    // Can we remove them?
    $removed_key = array_rand($propertyTypes);
    $remove_me = $propertyTypes[$removed_key];
    $chado_storage->removeTypes( [ $remove_me ] );
    // We can only check this by then retrieving them again.
    $retrieved_types = $chado_storage->getTypes();
    $this->assertCount(($num_properties -1), $retrieved_types, "Did not retrieve the expected number of PropertyTypes when using getTypes() after removing one.");
    foreach ($retrieved_types as $rtype) {
      $this->assertInstanceOf(StoragePropertyTypeBase::class, $rtype, "The retrieved property type does not inherit from our StoragePropertyTypeBase?");
      $rkey = $rtype->getKey();
      $this->assertNotEquals($rkey, $removed_key, "We were not able to remove the property with key $removed_key as it was still returned by getTypes().");
    }

    // Testing expected failures.
    // We should not be able to add types which are not the right class.
    $not_good_types = [
      "a string" => $this->getRandomGenerator()->word(rand(5,30)),
      "a basic array" => ['a', 'b', 'c'],
      "a stdclass" => new \StdClass(),
      "a Storage Property Value" => new StoragePropertyValue(
        $this->content_type,
        $this->randomMachineName(25),
        'name',
        'SIO:000729',
        $this->content_entity_id
      ),
    ];
    foreach($not_good_types as $message_part => $type) {
      $message = "We should not be able to add $message_part masquerading as a property type using addTypes().";
      ob_start();
      $return_value = $chado_storage->addTypes([ $type ]);
      ob_end_clean();
      $this->assertFalse($return_value, $message);
    }
    // We should not be able to add the same properties twice.
    ob_start();
    $return_value = $chado_storage->addTypes($propertyTypes);
    ob_end_clean();
    $this->assertFalse($return_value, "We should not be able to add the same types again using addTypes().");
  }

  /**
   * Tests loading values from chado using property types/values.
   *
   * Focus on loadValues() and validateValues().
   * Also public functions selectChadoRecord(), validateTypes(), validateSize().
   */

  /**
   * Tests inserting and updating values in chado using property types/values.
   *
   * Focus on insertValues() and updateValues().
   */

  /**
   * Focus on unimplemented methods.
   * Specifically, deleteValues(), findValues().
   *
   * Basically just check that
   *  - we get a warning that they are not implemented
   *  - we get a valid return value.
   */
  public function testUnimplementedChadoStorageMethods() {

    $storage_manager = \Drupal::service('tripal.storage');
    $chado_storage = $storage_manager->createInstance('chado_storage');

    // findValues().
    $caught_exception = FALSE;
    $exception_msg = '';
    try {
      $chado_storage->findValues('blah');
    }
    catch(\Exception $e) {
      $caught_exception = TRUE;
      $exception_msg = $e->getMessage();
    }
    $this->assertTrue($caught_exception, "Find values is not implemented yet so we should have been notified.");
    $this->assertStringContainsString('not yet implemented', $exception_msg, "The exception message should indicate that this method is 'not yet implemented'.");

    // deleteValues().
    $caught_exception = FALSE;
    $exception_msg = '';
    try {
      $chado_storage->deleteValues([]);
    }
    catch(\Exception $e) {
      $caught_exception = TRUE;
      $exception_msg = $e->getMessage();
    }
    $this->assertTrue($caught_exception, "Delete values is not implemented yet so we should have been notified.");
    $this->assertStringContainsString('not yet implemented', $exception_msg, "The exception message should indicate that this method is 'not yet implemented'.");
  }
}
