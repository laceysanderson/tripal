<?php

namespace Drupal\tripal\TripalStorage;

use Drupal\tripal\TripalStorage\StoragePropertyBase;

/**
 * Base class for a Tripal storage property type.
 */
class StoragePropertyTypeBase extends StoragePropertyBase {

  /**
   * Constructs a new tripal storage property type base.
   *
   * @param string entityType
   *   The entity type associated with this storage property type base.
   *
   * @param string fieldType
   *   The field type associated with this storage property type base.
   *
   * @param string key
   *   The key associated with this storage property type base.
   *
   * @param string id
   *   The id of this storage property type base.
   */
  public function __construct($entityType,$fieldType,$key,$id) {
    parent::__construct($entityType,$fieldType,$key);
    $this->id = $id;
  }

  /**
   * Returns the id of this storage property type base.
   *
   * @return string
   *   The id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Sets the cardinality.
   *
   * @param bool $cardinality
   *   The cardinality.
   */
  public function setCardinality($cardinality) {
    $this->cardinality = $cardinality;
  }

  /**
   * Gets the cardinality.
   *
   * @return bool
   *   The cardinality.
   */
  public function getCardinality() {
    return $this->cardinality;
  }

  /**
   * Sets the searchability.
   *
   * @param bool $searchability
   *   The searchability.
   */
  public function setSearchability($searchability) {
    $this->searchability = $searchability;
  }

  /**
   * Gets the searchability.
   *
   * @return bool
   *   The searchability.
   */
  public function getSearchability() {
    return $this->searchability;
  }

  /**
   * Sets the operations.
   *
   * Valid operations are (eq,ne,contains,starts).
   *
   * @param bool $searchability
   *   The operations.
   */
  public function setOperations($operations) {
    $this->operations = $operations;
  }

  /**
   * Gets the operations.
   *
   * @return bool
   *   The operations.
   */
  public function getOperations() {
    return $this->operations;
  }

  /**
   * Sets the sortable property.
   *
   * @param bool $sortable
   *   The sortable property.
   */
  public function setSortable($sortable) {
    $this->sortable = $sortable;
  }

  /**
   * Gets the sortable property.
   *
   * @return bool
   *   The sortable property.
   */
  public function getSortable() {
    return $this->sortable;
  }

  /**
   * Sets the read only property.
   *
   * @param bool $readOnly
   *   The read only property.
   */
  public function setReadOnly($readOnly) {
    $this->readOnly_ = $readOnly;
  }

  /**
   * Gets the read only property.
   *
   * @return bool
   *   The read only property.
   */
  public function getReadOnly() {
    return $this->readOnly_;
  }

  /**
   * Sets the required property.
   *
   * @param bool $required
   *   The required property.
   */
  public function setRequired($required) {
    $this->required = $required;
  }

  /**
   * Gets the required property.
   *
   * @return bool
   *   The required property.
   */
  public function getRequired() {
    return $this->required;
  }

  /**
   * The id of this storage property type base.
   *
   * @var string
   */
  private $id;

}
