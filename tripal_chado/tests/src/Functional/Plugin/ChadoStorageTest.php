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
    foreach ($properties as $details) {
      list('class' => $className, 'args' => $args) = $this->helperPrepStoragePropertyTypeCreate($details);
      $propertyTypes[ $details['name'] ] = new $className(...$args);

      $context = 'field: ' . $details['field'] . '; property: ' . $details['name'];
      $this->assertIsObject($propertyTypes[ $details['name'] ],
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
   *
   * @dataProvider provideTestCases
   */
  public function testLoadValidateValues(array $fields, array $properties, array $load_testData, bool $valid) {

    // Get plugin managers we need for our testing.
    $storage_manager = \Drupal::service('tripal.storage');
    $chado_storage = $storage_manager->createInstance('chado_storage');

    // Insert data required for this test.
    $inserted = [];
    $this->testSchemaName;
    $connection = $this->getTestSchema();
    foreach ($load_testData as $record_details) {
      $result = $connection->insert('1:' . $record_details['table'])
        ->fields($record_details['values'])
        ->execute();

      $key = $record_details['table'] . '_id';
      $inserted[ $record_details['table'] ] = $record_details['values'];
      $inserted[ $record_details['table'] ][ $key ] = $result;
    }

    // For each field...
    $values = [];
    $num_properties = 0;
    $expected_values = [];
    foreach($fields as $field) {

      // We reset these for each field.
      $propertyTypes = [];
      $propertyValues = [];

      // We also need FieldConfig classes for loading values.
      // We're going to create a TripalField and see if that works.
      $fieldconfig = $this->helperGetFieldMock($field);

      // Testing the Property Type + Value class creation
      foreach($properties as $key => $details) {
        $context = "Field: " . $field['name'] . "; Column: " . $details['chado_column'] . "; Key: " . $key;

        // Create the property Type.
        list('class' => $className, 'args' => $args) = $this->helperPrepStoragePropertyTypeCreate($details);
        $new_prop_type = new $className(...$args);
        $this->assertIsObject($new_prop_type, "Unable to create the property TYPE ($context).");
        // Create the property Value.
        $new_prop_value = new StoragePropertyValue(
          $this->content_type,
          $details['field'],
          $details['chado_column'],
          $details['term'],
          $this->content_entity_id
        );
        $this->assertIsObject($new_prop_value, "Unable to create the property VALUE ($context).");

        // If the current property is a store_id then we need to set the id
        // based on the inserted values.
        if ($details['action'] === 'store_id') {
          $value4prop = $inserted[ $details['chado_table'] ][ $details['chado_column'] ];
          $new_prop_value->setValue($value4prop);
        }
        // Now set the expected values for this.
        $expected_values[ $details['chado_table'] ][ $details['chado_column'] ] = $inserted[ $details['chado_table'] ][ $details['chado_column'] ];

        // Then we add it to the list for further testing later.
        $propertyTypes[$key] = $new_prop_type;
        $propertyValues[$key] = $new_prop_value;

        // We also want to add to the values array here which will be used for loading.
        $values[ $field['name'] ][0][$key] = [
          'value' => $new_prop_value,
          'type' => $new_prop_type,
          'definition' => $fieldconfig,
        ];
      } // End of foreach property type.
    } // End of foreach field.

    // Now we add the types and then load the values.
    $chado_storage->addTypes($propertyTypes);
    $success = $chado_storage->loadValues($values);
    $this->assertTrue($success, "Loading values after adding " . $field['name'] . " was not success (i.e. did not return TRUE).");

    // Then we test that the values are now in the types that we passed in.
    /*
    foreach ($expected_values as $deets) {
      list($Dfield, $Ddelta, $Dproperty, $Dvalue) = $deets;
      $context = "Field: " . $Dfield . "; Ddelta: " . $Ddelta . "; Property: " . $Dproperty . "; Expected Value: " . $Dvalue;
      $Gvalue = $values[$Dfield][$Ddelta][$Dproperty]['value']->getValue();
      $this->assertEquals($Dvalue, $Gvalue, "Could not load the value we expected but loaded $Gvalue instead ($context)");
    }
    */
  }

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

  /**
   * Prepare property type details returned by the dataProvider for object creation.
   * This just makes the tests a bit more readable since this is repeated often.
   *
   * @param array $details
   *   A single element from the properties array provided by the data provider.
   * @return array
   *   An array describing how to create this property.
   *    - class: the class of the object to create.
   *    - args: an ordered array of parameters needed to create the object.
   */
  public function helperPrepStoragePropertyTypeCreate($details) {
    $return = ['class' => NULL, 'args' => []];

    switch ($details['type']) {
      case 'varchar':
        $return['class'] = '\Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType';
        $return['args'] = [
          $this->content_type,
          $details['field'],
          $details['name'],
          $details['term'],
          $details['size'],
          [
            'action' => $details['action'],
            'drupal_store' => $details['drupal_store'],
            'chado_column' => $details['chado_column'],
            'chado_table' => $details['chado_table'],
          ]
        ];
        break;
      case 'int':
        $return['class'] = '\Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType';
        $return['args'] = [
          $this->content_type,
          $details['field'],
          $details['name'],
          $details['term'],
          [
            'action' => $details['action'],
            'drupal_store' => $details['drupal_store'],
            'chado_column' => $details['chado_column'],
            'chado_table' => $details['chado_table'],
          ]
        ];
        break;
    }
    return $return;
  }

  /**
   * Returns a mocked version of a field config to be used when working with values.
   *
   * @param array $field
   *   A single element from the fields array provided by the data provider.
   * @return FieldConfigMock
   *   A mock field configuration for the described field.
   */
  public function helperGetFieldMock($field) {

    $fieldconfig = new FieldConfigMock(['field_name' => $field['name'], 'entity_type' => $this->content_type]);

    $storage_settings = [
      'storage_plugin_id' => 'chado_storage',
      'storage_plugin_settings' => [
        'base_table' => $field['base_table'],
      ],
    ];
    $fieldconfig->setMock(['label' => $field['label'], 'settings' => $storage_settings]);

    return $fieldconfig;
  }
}
