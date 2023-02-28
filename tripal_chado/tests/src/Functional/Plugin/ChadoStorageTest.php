<?php

namespace Drupal\Tests\tripal_chado\Functional;

use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoVarCharStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
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
   * Protected variables set during setUp and used in tests.
   */
  protected $content_entity_id;
  protected $content_type;
  protected $organism_id;
  protected $feature_id;
  protected $featureprop_id = [];

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

    // Specifically we are mocking our example based on a "Gene" entity type (bundle).
    // Stored in the feature Chado table:
    //    name: test_gene_name, uniquename: test_gene_uname, type: gene (SO:0000704)
    //    organism) genus: Oryza, species: sativa, common_name: rice,
    //      abbreviation: O.sativa, infraspecific_name: Japonica,
    //      type: species_group (TAXRANK:0000010), comment: 'This is rice'
    //    Feature Properties)
    //      - type: note (rdfs:comment), value: "Note 1", rank: 0
    //      - type: note (rdfs:comment), value: "Note 2", rank: 2
    //      - type: note (rdfs:comment), value: "Note 3", rank: 1
    // The Gene entity type has 3 fields: Gene Name, Notes, Organism.

    // Add the organism record.
    $infra_type_id = $this->getCvtermID('TAXRANK', '0000010');
    $query = $connection->insert('1:organism');
    $query->fields([
        'genus' => 'Oryza',
        'species' => 'sativa',
        'common_name' => 'rice',
        'abbreviation' => 'O.sativa',
        'infraspecific_name' => 'Japonica',
        'type_id' => $infra_type_id,
        'comment' => 'This is rice'
      ]);
    $this->organism_id = $query->execute();

    // Add the gene record.
    $gene_type_id = $this->getCvtermID('SO', '0000704');
    $query = $connection->insert('1:feature');
    $query->fields([
        'name' => 'test_gene_name',
        'uniquename' => 'test_gene_uname',
        'type_id' => $gene_type_id,
        'organism_id' => $this->organism_id,
      ]);
    $this->feature_id = $query->execute();

    // Add featureprop notes:
    $note_type_id = $this->getCvtermID('rdfs', 'comment');
    $this->featureprop_id[0] = $connection->insert('1:featureprop')
      ->fields([
        'feature_id' => $this->feature_id,
        'type_id' => $note_type_id,
        'value' => "Note 1",
        'rank' => 0,
      ])
      ->execute();
    $this->featureprop_id[2] = $connection->insert('1:featureprop')
      ->fields([
        'feature_id' => $this->feature_id,
        'type_id' => $note_type_id,
        'value' => "Note 2",
        'rank' => 2,
      ])
      ->execute();
    $this->featureprop_id[1] = $connection->insert('1:featureprop')
      ->fields([
        'feature_id' => $this->feature_id,
        'type_id' => $note_type_id,
        'value' => "Note 3",
        'rank' => 1,
      ])
      ->execute();

    // We need to make sure the CVs we're going to use are registered.
    // they should already be loaded in the test Chado instance.
    $vmanager = \Drupal::service('tripal.collection_plugin_manager.vocabulary');
    $idsmanager = \Drupal::service('tripal.collection_plugin_manager.idspace');

    $vocabulary = $vmanager->createCollection('SIO', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('SIO', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('schema', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('schema', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('sequence', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('SO', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('ncit', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('NCIT', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('OBCS', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('OBCS', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('obi', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('OBI', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('rdfs', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('rdfs', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('taxonomic_rank', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('TAXRANK', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

    $vocabulary = $vmanager->createCollection('local', 'chado_vocabulary');
    $idSpace = $idsmanager->createCollection('local', 'chado_id_space');
    $idSpace->setDefaultVocabulary($vocabulary->getName());

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
   * Tests the *Types() and loadValues() methods of the ChadoStorage class.
   *
   * @dataProvider provideFields
   *
   * Summary (for each dataset provided by provideFields):
   *  - Create TripalStoragePropertyType and TripalStoragePropertyValue objects
   *    for all fields + test they are as expected.
   *  - Use addTypes() with the above property types and check it was successful.
   *  - Use validateTypes() to confirm they are valid.
   *  - Use getTypes() to confirm we can retrieve the types we added.
   *  - Use loadValues() to confirm we can load the chado values the property
   *    types refer to.
   *  - Use removeTypes() and then getTypes() to check that we can remove types
   *    successfully.
   */
  public function testChadoStorageCRUDtypes($fields) {

    // Get plugin managers we need for our testing.
    $storage_manager = \Drupal::service('tripal.storage');
    $chado_storage = $storage_manager->createInstance('chado_storage');

    $values = [];
    $num_properties = 0;
    foreach($fields as $field) {

      // We reset these for each field.
      $propertyTypes = [];
      $propertyValues = [];

      // We also need FieldConfig classes for loading values.
      // We're going to create a TripalField and see if that works.
      $fieldconfig = new FieldConfigMock(['field_name' => $field['field_name'], 'entity_type' => $this->content_type]);
      $fieldconfig->setMock(['label' => $field['field_label'], 'settings' => $field['storage_settings']]);

      // Testing the Property Type + Value class creation
      foreach($field['propertyTypes'] as $key => $details) {

        // First we try to create the property type.
        // We need to take into account the type here...
        switch($details['type']) {
          case 'int':
            $new_prop_type = new ChadoIntStoragePropertyType(
              $this->content_type,
              $field['field_name'],
              $details['chado_column'],
              $details['term_accession'],
              $details['propertyType_options']
            );
            break;
          case 'varchar':
            $new_prop_type = new ChadoVarCharStoragePropertyType(
              $this->content_type,
              $field['field_name'],
              $details['chado_column'],
              $details['term_accession'],
              $details['size'],
              $details['propertyType_options']
            );
            break;
          default:
            $this->assertFalse(TRUE, "There is an unsupported type in the data provider: " . $details['type']);
        }

        // Then we try to create the property value.
        // These are the same regardless of type... unless it's the record_id!
        if ($key === 'record_id') {
          $new_prop_value = new StoragePropertyValue(
            $this->content_type,
            $field['field_name'],
            $details['chado_column'],
            $details['term_accession'],
            $this->content_entity_id,
            $this->feature_id
          );
        }
        else {
          $new_prop_value = new StoragePropertyValue(
            $this->content_type,
            $field['field_name'],
            $details['chado_column'],
            $details['term_accession'],
            $this->content_entity_id
          );
        }

        // Then we test that we were able to create them.
        $context = "Field: " . $field['field_name'] . "; Column: " . $details['chado_column'] . "; Key: " . $key;
        $this->assertIsObject($new_prop_type, "Unable to create the property TYPE ($context).");
        $this->assertIsObject($new_prop_value, "Unable to create the property VALUE ($context).");

        // Then we add it to the list for further testing later.
        $propertyTypes[$key] = $new_prop_type;
        $propertyValues[$key] = $new_prop_value;
        $num_properties++;

        // Sometimes the expected value needs to be set here. That's because
        // values determined during setUp are not available to the data provider.
        if ($details['expected_value'] == 'FEATURE ID') {
          $details['expected_value'] = $this->feature_id;
        }
        // We also want to add to the values array here which will be used for loading.
        $values[ $field['field_name'] ][0][$key] = [
          'value' => $new_prop_value,
          'type' => $new_prop_type,
          'definition' => $fieldconfig,
        ];
        $expected_values[] = [
          $field['field_name'],
          0,
          $key,
          $details['expected_value'],
        ];
      } // End of foreach property type.

      // Now we test the addTypes and getTypes methods.
      $chado_storage->addTypes($propertyTypes);
      $retrieved_types = $chado_storage->getTypes();
      $this->assertIsArray($retrieved_types, "Unable to retrieve the PropertyTypes after adding " . $field['field_name'] . ".");
      $this->assertCount($num_properties, $retrieved_types, "Did not revieve the expected number of PropertyTypes after adding " . $field['field_name'] . ".");

      // Next we actually load the values.
      $success = $chado_storage->loadValues($values);
      $this->assertTrue($success, "Loading values after adding " . $field['field_name'] . " was not success (i.e. did not return TRUE).");

      // Then we test that the values are now in the types that we passed in.
      foreach ($expected_values as $deets) {
        list($Dfield, $Ddelta, $Dproperty, $Dvalue) = $deets;
        $context = "Field: " . $Dfield . "; Ddelta: " . $Ddelta . "; Property: " . $Dproperty . "; Expected Value: " . $Dvalue;
        $Gvalue = $values[$Dfield][$Ddelta][$Dproperty]['value']->getValue();
        $this->assertEquals($Dvalue, $Gvalue, "Could not load the value we expected but loaded $Gvalue instead ($context)");
      }
    } // End of foreach field.
  }

  /**
   * Provides fields with property types for use in the above tests.
   *
   * @return array
   *   Returns an array of test datasets. Each dataset should have a
   *   single key of `fields` which is an array of fields to be tested.
   *   Each field will define the following:
   *    - field_name (string): the machine name of the field
   *    - field_label (string): the human-readable name of the field
   *    - chado_table (string): the name of the chado table
   *    - chado_column (string): the name of the column this field is primarily managing
   *    - storage_settings (array): an array of field storage settings
   *    - propertyTypes (array): an array of info about properties this field would have.
   *        Each member of this array should have the following:
   *           - type
   *           - chado_column
   *           - term_accession
   *           - propertyType_options
   *         Some will have additional keys needed by that property type.
   */
  public function provideFields() {

    // Each key in this array is a test dataset.
    $data = [];

    // The first test dataset is a single field with only one property.
    // This is the simplest case *smiles*
    // To test this we will generate the types as they would be for a
    // gene name field  with the data stored in feature.name.
    // Term: schema:name. Existing Value: test_gene_name
    $this_dataset = [];
    $this_dataset['fields']['schema__name'] = [
      'field_name'  => 'schema__name',
      'field_label' => 'Gene Name',
      'chado_table' => 'feature',
      'chado_column' =>'name',
      'storage_settings' => [
        'storage_plugin_id' => 'chado_storage',
        'storage_plugin_settings' => [
          'base_table' => 'feature',
        ],
      ],
    ];
    $this_dataset['fields']['schema__name']['propertyTypes'] = [
      'record_id' => [
        'type' => 'int',
        'chado_column' => 'feature_id',
        'term_accession' => 'SIO:000729',
        'propertyType_options' => [
          'action' => 'store_id',
          'drupal_store' => TRUE,
          'chado_table' => 'feature',
          'chado_column' => 'feature_id',
        ],
        'expected_value' => 'FEATURE ID',
      ],
      'name' => [
        'type' => 'varchar',
        'chado_column' => 'name',
        'term_accession' => 'schema:name',
        'size' => 255,
        'propertyType_options' => [
          'action' => 'store',
          'chado_table' => 'feature',
          'chado_column' => 'name',
        ],
        'expected_value' => 'test_gene_name',
      ],
    ];
    $data['single field with one property'] = $this_dataset;

    return $data;
  }

  /**
   * Tests the *Types() and loadValues() methods of the ChadoStorage class.
   *
   * OLD STYLE TEST.
   */
  public function testChadoStorage() {

    // Get plugin managers we need for our testing.
    $storage_manager = \Drupal::service('tripal.storage');
    $chado_storage = $storage_manager->createInstance('chado_storage');

    // $testEnviro_chado = $this->getTestSchema();
    // $testEnviro_chado_schemaname = $testEnviro_chado->getSchemaName();
    // $coreEnviro_chado = \Drupal::service('tripal_chado.database');
    // $coreEnviro_chado_schemaname = $coreEnviro_chado->getSchemaName();
    // print "\ntestChadoStorage: $testEnviro_chado_schemaname = $coreEnviro_chado_schemaname.\n";
    // $this->assertEquals($testEnviro_chado_schemaname, $coreEnviro_chado_schemaname, "Core Services are not using the test schema.");

    // For the ChadoStorage->addTypes() and ChadoStorage->loadValues()
    // We are going to progressively test these methods with more + more fields.
    // Hence, I'm starting the values variable here to be added to as we go.
    // The types will be stored in the $chado_storage service object.
    $values = [];

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // -- Single value + single property field
    // Stored in feature.name; Term: schema:name.
    // Value: test_gene_name
    $field_name  = 'schema__name';
    $field_label = 'Gene Name';
    $chado_table = 'feature';
    $chado_column = 'name';
    $storage_settings = [
      'storage_plugin_id' => 'chado_storage',
      'storage_plugin_settings' => [
        'base_table' => $chado_table,
      ],
    ];


    // Testing the Property Type + Value class creation
    // + prepping for future tests.
    //$this->addFieldPropertyCVterms();
    $propertyTypes = [
      'feature_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'feature_id', 'SIO:000729', [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => $chado_table,
        'chado_column' => $chado_column,
      ]),
      'name' => new ChadoVarCharStoragePropertyType($this->content_type, $field_name, 'name', 'schema:name', 255, [
        'action' => 'store',
        'chado_table' => $chado_table,
        'chado_column' => $chado_column,
      ]),
    ];
    $propertyValues = [
      'feature_id' => new StoragePropertyValue(
        $this->content_type,
        $field_name,
        'feature_id',
        'SIO:000729',
        $this->content_entity_id,
        $this->feature_id
      ),
      'name' => new StoragePropertyValue(
        $this->content_type,
        $field_name,
        'name',
        'schema:name',
        $this->content_entity_id,
      ),
    ];
    $this->assertIsObject($propertyTypes['feature_id'], "Unable to create feature_id ChadoIntStoragePropertyType: $field_name, record_id");
    $this->assertIsObject($propertyValues['feature_id'], "Unable to create feature_id StoragePropertyValue: $field_name, record_id, $this->content_entity_id");
    $this->assertIsObject($propertyTypes['name'], "Unable to create feature.name ChadoIntStoragePropertyType: $field_name, value");
    $this->assertIsObject($propertyValues['name'], "Unable to create feature.name StoragePropertyValue: $field_name, value, $this->content_entity_id");

    // Make sure the values start empty.
    $this->assertEquals($this->feature_id, $propertyValues['feature_id']->getValue(), "The $field_name feature_id property should already be set.");
    $this->assertTrue(empty($propertyValues['name']->getValue()), "The $field_name feature.name property should not have a value.");

    // Now test ChadoStorage->addTypes()
    // param array $types = Array of \Drupal\tripal\TripalStorage\StoragePropertyTypeBase objects.
    $chado_storage->addTypes($propertyTypes);
    $retrieved_types = $chado_storage->getTypes();
    $this->assertIsArray($retrieved_types, "Unable to retrieve the PropertyTypes after adding $field_name.");
    $this->assertCount(2, $retrieved_types, "Did not revieve the expected number of PropertyTypes after adding $field_name.");

    // We also need FieldConfig classes for loading values.
    // We're going to create a TripalField and see if that works.
    $fieldconfig = new FieldConfigMock(['field_name' => $field_name, 'entity_type' => $this->content_type]);
    $fieldconfig->setMock(['label' => $field_label, 'settings' => $storage_settings]);

    // Next we actually load the values.
    $values[$field_name] = [
      0 => [
        'name'=> [
          'value' => $propertyValues['name'],
          'type' => $propertyTypes['name'],
          'definition' => $fieldconfig,
        ],
        'feature_id' => [
          'value' => $propertyValues['feature_id'],
          'type' => $propertyTypes['feature_id'],
          'definition' => $fieldconfig,
        ],
      ],
    ];
    $success = $chado_storage->loadValues($values);
    $this->assertTrue($success, "Loading values after adding $field_name was not success (i.e. did not return TRUE).");

    // Then we test that the values are now in the types that we passed in.
    $this->assertEquals('test_gene_name', $values['schema__name'][0]['name']['value']->getValue(), 'The gene name value was not loaded properly.');

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // -- Multi-value single property field
    // Stored in featureprop.value; Term: rdfs:comment.
    // Values:
    //   - type: note (rdfs:comment), value: "Note 1", rank: 0
    //   - type: note (rdfs:comment), value: "Note 2", rank: 2
    //   - type: note (rdfs:comment), value: "Note 3", rank: 1
    $field_name = 'rdfs__comment';
    $field_term_string = 'rdfs:comment';
    $chado_table = 'featureprop';
    $chado_column = 'value';
    $chado_table = 'featureprop';
    $base_table = 'feature';
    $chado_column = 'organism_id';
    $storage_settings = [
      'storage_plugin_id' => 'chado_storage',
      'storage_plugin_settings' => [
        'base_table' => $chado_table,
      ],
    ];

    $propertyTypes = [
      'feature_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'feature_id', 'SIO:000729', [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => 'feature',
        'chado_column' => 'feature_id',
      ]),
      'featureprop_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'featureprop_id', 'SIO:000729', [
        'action' => 'store_pkey',
        'chado_table' => 'featureprop',
        'chado_column' => 'featureprop_id',
      ]),
      'fk_feature_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'fk_feature_id', 'SO:0000110', [
        'action' => 'store_link',
        'chado_table' => 'featureprop',
        'chado_column' => 'feature_id',
      ]),
      'type_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'type_id', 'schema:additionalType', [
        'action' => 'store',
        'chado_table' => 'featureprop',
        'chado_column' => 'type_id',
      ]),
      'value' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'value', 'NCIT:C25712', [
        'action' => 'store',
        'chado_table' => 'featureprop',
        'chado_column' => 'value',
      ]),
      'rank' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'rank', 'OBCS:0000117', [
        'action' => 'store',
        'chado_table' => 'featureprop',
        'chado_column' => 'rank',
      ]),
    ];
    foreach ($propertyTypes as $key => $propType) {
      $this->assertIsObject($propType, "Unable to create the *StoragePropertyType: $field_name, $key");
    }

    // Testing the Property Value class creation.
    $propertyValues = [
      'feature_id' => new StoragePropertyValue($this->content_type, $field_name, 'feature_id', 'SIO:000729', $this->content_entity_id, $this->feature_id),
      'featureprop_id' => new StoragePropertyValue($this->content_type, $field_name, 'featureprop_id', 'SIO:000729', $this->content_entity_id),
      'fk_feature_id' => new StoragePropertyValue($this->content_type, $field_name, 'fk_feature_id', 'SO:0000110', $this->content_entity_id),
      'type_id' => new StoragePropertyValue($this->content_type, $field_name, 'type_id', 'schema:additionalType', $this->content_entity_id),
      'value' => new StoragePropertyValue($this->content_type, $field_name, 'value', 'NCIT:C25712', $this->content_entity_id),
      'rank' => new StoragePropertyValue($this->content_type, $field_name, 'rank', 'OBCS:0000117', $this->content_entity_id),
    ];
    foreach ($propertyValues as $key => $propVal) {
      $this->assertIsObject($propVal, "Unable to create the StoragePropertyValue: $field_name, $key");
    }

    // Make sure the values start empty.
    $this->assertEquals($this->feature_id, $propertyValues['feature_id']->getValue(), "The $field_name feature_id property should be the feature_id.");
    $this->assertTrue(empty($propertyValues['featureprop_id']->getValue()), "The $field_name feature property pkey should not have a value.");
    $this->assertTrue(empty($propertyValues['fk_feature_id']->getValue()), "The $field_name feature property feature_id property should not have a value.");
    $this->assertTrue(empty($propertyValues['type_id']->getValue()), "The $field_name type_id property should not have a value.");
    $this->assertTrue(empty($propertyValues['value']->getValue()), "The $field_name value property should not have a value.");
    $this->assertTrue(empty($propertyValues['rank']->getValue()), "The $field_name rank property should not have a value.");

    // Now test ChadoStorage->addTypes()
    // param array $types = Array of \Drupal\tripal\TripalStorage\StoragePropertyTypeBase objects.
    $chado_storage->addTypes($propertyTypes);
    $retrieved_types = $chado_storage->getTypes();
    $this->assertIsArray($retrieved_types, "Unable to retrieve the PropertyTypes after adding $field_name.");
    $this->assertCount(8, $retrieved_types, "Did not revieve the expected number of PropertyTypes after adding $field_name.");

    // We also need FieldConfig classes for loading values.
    // We're going to create a TripalField and see if that works.
    $fieldconfig = new FieldConfigMock(['field_name' => $field_name, 'entity_type' => $this->content_type]);
    $fieldconfig->setMock(['label' => $field_label, 'settings' => $storage_settings]);

    // Next we actually load the values.
    $values[$field_name] = [ 0 => [], 1 => [], 2 => [] ];
    foreach ($propertyTypes as $key => $propType) {
      $values[$field_name][0][$key] = [
        'type' => $propType,
        'value' => clone $propertyValues[$key],
        'definition' => $fieldconfig
      ];
      $values[$field_name][1][$key] = [
        'type' => $propType,
        'value' => clone $propertyValues[$key],
        'definition' => $fieldconfig
      ];
      $values[$field_name][2][$key] = [
        'type' => $propType,
        'value' => clone $propertyValues[$key],
        'definition' => $fieldconfig
      ];
    }
    // We also need to set the featureprop_id for each.
    $values[$field_name][0]['featureprop_id']['value']->setValue($this->featureprop_id[0]);
    $values[$field_name][1]['featureprop_id']['value']->setValue($this->featureprop_id[1]);
    $values[$field_name][2]['featureprop_id']['value']->setValue($this->featureprop_id[2]);
    // Now we can try to load the rest of the property.
    $success = $chado_storage->loadValues($values);
    $this->assertTrue($success, "Loading values after adding $field_name was not success (i.e. did not return TRUE).");

    // Then we test that the values are now in the types that we passed in.
    // All fields should have been loaded, not just our organism one.
    $this->assertEquals('test_gene_name', $values['schema__name'][0]['name']['value']->getValue(), 'The gene name value was not loaded properly.');
    // Now test the feature properties were loaded as expected.
    // Values:
    //   - type: note (rdfs:comment), value: "Note 1", rank: 0
    //   - type: note (rdfs:comment), value: "Note 2", rank: 2
    //   - type: note (rdfs:comment), value: "Note 3", rank: 1
    $this->assertEquals(
      "Note 1", $values['rdfs__comment'][0]['value']['value']->getValue(),
      'The delta 0 featureprop.value was not loaded properly.'
    );
    $this->assertEquals(
      "Note 3", $values['rdfs__comment'][1]['value']['value']->getValue(),
      'The delta 1 featureprop.value was not loaded properly.'
    );
    $this->assertEquals(
      "Note 2", $values['rdfs__comment'][2]['value']['value']->getValue(),
      'The delta 2 featureprop.value was not loaded properly.'
    );
    $note_type_id = $this->getCvtermID('rdfs', 'comment');
    foreach([0,1,2] as $delta) {
      $this->assertEquals(
        $note_type_id, $values['rdfs__comment'][$delta]['type_id']['value']->getValue(),
        "The type_id of the delta $delta note was not loaded properly."
      );
      $this->assertEquals(
        $this->feature_id, $values['rdfs__comment'][$delta]['fk_feature_id']['value']->getValue(),
        "The featureprop.feature_id of the delta $delta note was not loaded properly."
      );
      $this->assertEquals(
        $delta, $values['rdfs__comment'][$delta]['rank']['value']->getValue(),
        "The featureprop.rank of the delta $delta note was not loaded properly."
      );
    }

    // ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    // -- Single value, multi-property field
    // Stored in feature.organism_id; Term: obi:organism.
    // Value: genus: Oryza, species: sativa, common_name: rice,
    //   abbreviation: O.sativa, infraspecific_name: Japonica,
    //   type: species_group (TAXRANK:0000010), comment: 'This is rice'
    $field_name = 'obi__organism';
    $field_label = 'Organism';
    $field_type = 'obi__organism';
    $field_term_string = 'obi:organism';
    $chado_table = 'feature';
    $chado_column = 'organism_id';
    $storage_settings = [
      'storage_plugin_id' => 'chado_storage',
      'storage_plugin_settings' => [
        'base_table' => $chado_table,
      ],
    ];

    // Testing the Property Type class creation.
    $base_table = $chado_table;
    $propertyTypes = [
      'feature_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'feature_id', 'SIO:000729', [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => $base_table,
        'chado_column' => 'feature_id'
      ]),
      'organism_id' => new ChadoIntStoragePropertyType($this->content_type, $field_name, 'organism_id', 'OBI:0100026', [
        'action' => 'store',
        'chado_table' => $base_table,
        'chado_column' => 'organism_id',
      ]),
      'label' => new ChadoVarCharStoragePropertyType($this->content_type, $field_name, 'label', 'rdfs:label', 255, [
        'action' => 'replace',
        'template' => "<i>[genus] [species]</i> [infraspecific_type] [infraspecific_name]",
      ]),
      'genus' => new ChadoVarCharStoragePropertyType($this->content_type, $field_name, 'genus', 'TAXRANK:0000005', 255, [
        'action' => 'join',
        'path' => $base_table . '.organism_id>organism.organism_id',
        'chado_column' => 'genus'
      ]),
      'species' => new ChadoVarCharStoragePropertyType($this->content_type, $field_name, 'species', 'TAXRANK:0000006', 255, [
        'action' => 'join',
        'path' => $base_table . '.organism_id>organism.organism_id',
        'chado_column' => 'species'
      ]),
      'infraspecific_name' => new ChadoVarCharStoragePropertyType($this->content_type, $field_name, 'infraspecific_name', 'TAXRANK:0000045', 255, [
        'action' => 'join',
        'path' => $base_table . '.organism_id>organism.organism_id',
        'chado_column' => 'infraspecific_name',
      ]),
      'infraspecific_type'=> new ChadoIntStoragePropertyType($this->content_type, $field_name, 'infraspecific_type', 'local:infraspecific_type', [
        'action' => 'join',
        'path' => $base_table . '.organism_id>organism.organism_id;organism.type_id>cvterm.cvterm_id',
        'chado_column' => 'name',
        'as' => 'infraspecific_type_name'
      ])
    ];
    foreach ($propertyTypes as $key => $propType) {
      $this->assertIsObject($propType, "Unable to create the *StoragePropertyType: $field_name, $key");
    }

    // Testing the Property Value class creation.
    $propertyValues = [
      'feature_id' => new StoragePropertyValue($this->content_type, $field_name, 'feature_id', 'SIO:000729', $this->content_entity_id, $this->feature_id),
      'organism_id' => new StoragePropertyValue($this->content_type, $field_name, 'organism_id', 'OBI:0100026', $this->content_entity_id),
      'label' => new StoragePropertyValue($this->content_type, $field_name, 'label', 'rdfs:label', $this->content_entity_id),
      'genus' => new StoragePropertyValue($this->content_type, $field_name, 'genus', 'TAXRANK:0000005', $this->content_entity_id),
      'species' => new StoragePropertyValue($this->content_type, $field_name, 'species', 'TAXRANK:0000006', $this->content_entity_id),
      'infraspecific_name' => new StoragePropertyValue($this->content_type, $field_name, 'infraspecific_name', 'TAXRANK:0000045', $this->content_entity_id),
      'infraspecific_type'=> new StoragePropertyValue($this->content_type, $field_name, 'infraspecific_type', 'local:infraspecific_type', $this->content_entity_id)
    ];
    foreach ($propertyValues as $key => $propVal) {
      $this->assertIsObject($propVal, "Unable to create the StoragePropertyValue: $field_name, $key");
    }

    // Make sure the values start empty.
    $this->assertEquals($this->feature_id, $propertyValues['feature_id']->getValue(), "The $field_name feature_id property should be the feature_id.");
    $this->assertTrue(empty($propertyValues['organism_id']->getValue()), "The $field_name value property should not have a value.");
    $this->assertTrue(empty($propertyValues['label']->getValue()), "The $field_name label property should not have a value.");
    $this->assertTrue(empty($propertyValues['genus']->getValue()), "The $field_name genus property should not have a value.");
    $this->assertTrue(empty($propertyValues['species']->getValue()), "The $field_name species property should not have a value.");
    $this->assertTrue(empty($propertyValues['infraspecific_name']->getValue()), "The $field_name infraspecific_name property should not have a value.");
    $this->assertTrue(empty($propertyValues['infraspecific_type']->getValue()), "The $field_name infraspecific_type property should not have a value.");

    // Now test ChadoStorage->addTypes()
    // param array $types = Array of \Drupal\tripal\TripalStorage\StoragePropertyTypeBase objects.
    $chado_storage->addTypes($propertyTypes);
    $retrieved_types = $chado_storage->getTypes();
    $this->assertIsArray($retrieved_types, "Unable to retrieve the PropertyTypes after adding $field_name.");
    $this->assertCount(15, $retrieved_types, "Did not revieve the expected number of PropertyTypes after adding $field_name.");

    // We also need FieldConfig classes for loading values.
    // We're going to create a TripalField and see if that works.
    $fieldconfig = new FieldConfigMock(['field_name' => $field_name, 'entity_type' => $this->content_type]);
    $fieldconfig->setMock(['label' => $field_label, 'settings' => $storage_settings]);

    // Next we actually load the values.
    $values[$field_name] = [ 0 => [] ];
    foreach ($propertyTypes as $key => $propType) {
      $values[$field_name][0][$key] = [
        'type' => $propType,
        'value' => $propertyValues[$key],
        'definition' => $fieldconfig
      ];
    }
    $success = $chado_storage->loadValues($values);
    $this->assertTrue($success, "Loading values after adding $field_name was not success (i.e. did not return TRUE).");

    // Then we test that the values are now in the types that we passed in.
    // All fields should have been loaded, not just our organism one.
    $this->assertEquals(
      'test_gene_name', $values['schema__name'][0]['name']['value']->getValue(),
      'The gene name value was not loaded properly.'
    );
    $this->assertEquals(
      "Note 1", $values['rdfs__comment'][0]['value']['value']->getValue(),
      'The delta 0 featureprop.value was not loaded properly.'
    );
    $this->assertEquals(
      "Note 3", $values['rdfs__comment'][1]['value']['value']->getValue(),
      'The delta 1 featureprop.value was not loaded properly.'
    );
    $this->assertEquals(
      "Note 2", $values['rdfs__comment'][2]['value']['value']->getValue(),
      'The delta 2 featureprop.value was not loaded properly.'
    );
    // Now test the organism values were loaded as expected.
    // Value: genus: Oryza, species: sativa, common_name: rice,
    //   abbreviation: O.sativa, infraspecific_name: Japonica,
    //   type: species_group (TAXRANK:0000010), comment: 'This is rice'
    $this->assertEquals($this->organism_id, $values['obi__organism'][0]['organism_id']['value']->getValue(), 'The organism value was not loaded properly.');
    $this->assertEquals('Oryza', $values['obi__organism'][0]['genus']['value']->getValue(), 'The organism genus was not loaded properly.');
    $this->assertEquals('sativa', $values['obi__organism'][0]['species']['value']->getValue(), 'The organism species was not loaded properly.');
    $this->assertEquals('Japonica', $values['obi__organism'][0]['infraspecific_name']['value']->getValue(), 'The organism infraspecific name was not loaded properly.');
    $this->assertEquals('species_group', $values['obi__organism'][0]['infraspecific_type']['value']->getValue(), 'The organism infraspecific type was not loaded properly.');
    $this->assertEquals("<i>Oryza sativa</i> species_group Japonica", $values['obi__organism'][0]['label']['value']->getValue(), 'The organism label was not loaded properly.');
  }
}
