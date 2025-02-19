<?php

namespace Drupal\tripal_chado\Plugin\Field\FieldType;

use Drupal\tripal_chado\TripalField\ChadoFieldItemBase;
use Drupal\tripal\TripalField\TripalFieldItemBase;
use Drupal\tripal_chado\TripalStorage\ChadoIntStoragePropertyType;
use Drupal\tripal_chado\TripalStorage\ChadoTextStoragePropertyType;
use Drupal\core\Field\FieldStorageDefinitionInterface;
use Drupal\tripal\TripalStorage\StoragePropertyValue;
use Drupal\Core\Form\FormStateInterface;
use Drupal\core\Field\FieldDefinitionInterface;


/**
 * Plugin implementation of Tripal linker property field type.
 *
 * @FieldType(
 *   id = "chado_linker_property_default",
 *   label = @Translation("Chado Property"),
 *   description = @Translation("Add a property or attribute to the content type."),
 *   default_widget = "chado_linker_property_widget_default",
 *   default_formatter = "chado_linker_property_formatter_default"
 * )
 */
class ChadoLinkerPropertyDefault extends ChadoFieldItemBase {

  public static $id = "chado_linker_property_default";

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    $settings = parent::defaultStorageSettings();
    $settings['storage_plugin_settings']['prop_table'] = '';
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function tripalTypes($field_definition) {

    // Create variables for easy access to settings.
    $entity_type_id = $field_definition->getTargetEntityTypeId();
    $settings = $field_definition->getSetting('storage_plugin_settings');
    $base_table = $settings['base_table'];
    $prop_table = $settings['prop_table'];

    // If we don't have a base table then we're not ready to specify the
    // properties for this field.
    if (!$base_table) {
      $record_id_term = 'SIO:000729';
      return [
        new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
          'action' => 'store_id',
          'drupal_store' => TRUE,
        ])
      ];
    }

    // Get the base table columns needed for this field.
    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    $base_schema_def = $schema->getTableDef($base_table, ['format' => 'Drupal']);
    $base_pkey_col = $base_schema_def['primary key'];
    $prop_schema_def = $schema->getTableDef($prop_table, ['format' => 'Drupal']);
    $prop_pkey_col = $prop_schema_def['primary key'];
    $prop_fk_col = array_keys($prop_schema_def['foreign keys'][$base_table]['columns'])[0];

    // Get the property terms by using the Chado table columns they map to.
    $storage = \Drupal::entityTypeManager()->getStorage('chado_term_mapping');
    $mapping = $storage->load('core_mapping');
    $record_id_term = 'SIO:000729';
    $link_term = $mapping->getColumnTermId($prop_table, $prop_fk_col);
    $value_term = $mapping->getColumnTermId($prop_table, 'value');
    $rank_term = $mapping->getColumnTermId($prop_table, 'rank');
    $type_id_term = $mapping->getColumnTermId($prop_table, 'type_id');

    // Create the property types.
    return [
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'record_id', $record_id_term, [
        'action' => 'store_id',
        'drupal_store' => TRUE,
        'chado_table' => $base_table,
        'chado_column' => $base_pkey_col
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'prop_id', $record_id_term, [
        'action' => 'store_pkey',
        'drupal_store' => TRUE,
        'chado_table' => $prop_table,
        'chado_column' => $prop_pkey_col,
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'linker_id',  $link_term, [
        'action' => 'store_link',
        'chado_table' => $prop_table,
        'chado_column' => $prop_fk_col,
      ]),
      new ChadoTextStoragePropertyType($entity_type_id, self::$id, 'value', $value_term, [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'value',
        'delete_if_empty' => TRUE,
        'empty_value' => ''
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'rank', $rank_term,  [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'rank'
      ]),
      new ChadoIntStoragePropertyType($entity_type_id, self::$id, 'type_id', $type_id_term, [
        'action' => 'store',
        'chado_table' => $prop_table,
        'chado_column' => 'type_id'
      ]),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {

    // We need to set the prop table for this field but we need to know
    // the base table to do that. So we'll add a new validation function so
    // we can get it and set the proper storage settings.
    $elements = parent::storageSettingsForm($form, $form_state, $has_data);
    $elements['storage_plugin_settings']['base_table']['#element_validate'] = [[static::class, 'storageSettingsFormValidate']];
    return $elements;
  }

  /**
   * Form element validation handler
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the (entire) configuration form.
   */
  public static function storageSettingsFormValidate(array $form, FormStateInterface $form_state) {
    $settings = $form_state->getValue('settings');
    if (!array_key_exists('storage_plugin_settings', $settings)) {
      return;
    }
    $base_table = $settings['storage_plugin_settings']['base_table'];
    $prop_table = $base_table . 'prop';

    $chado = \Drupal::service('tripal_chado.database');
    $schema = $chado->schema();
    if ($schema->tableExists($prop_table)) {
      $form_state->setValue(['settings', 'storage_plugin_settings', 'prop_table'], $prop_table);
    }
    else {
      $form_state->setErrorByName('storage_plugin_settings][base_table',
          'The selected base table does not have an associated property table.');
    }
  }
}
