<?php

namespace Drupal\Tests\tripal_chado;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Database\Database;
use Drupal\tripal_chado\api\ChadoSchema;

/**
 * Testing the tripal_chado/api/tripal_chado.db.api.php functions.
 *
 * @group Tripal
 * @group Tripal Chado
 * @group Tripal API
 */
class ChadoDbAPITest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   * @var array
   */
  protected static $modules = ['tripal', 'tripal_chado'];

  /**
   * Schema to do testing out of.
   * @var string
   */
  protected static $schemaName = 'testchado';

  /**
   * Tests chado.db associated functions.
   *
   * @group tripal-chado
   * @group chado-db
   */
  public function testDB() {

		$this->markTestIncomplete('This test is causing errors in the new environment.');

		// INSERT.
		// chado_insert_db().
		$dbval = [
			'name' => 'TD' . uniqid(),
			'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'url' => 'https://www.lipsum.com/feed',
		];
		$return = chado_insert_db($dbval, [], 'testchado');
		$this->assertNotFalse($return, 'chado_insert_db failed unexpectedly.');
		$this->assertIsObject($return, 'Should be an updated DB object.');
		$this->assertTrue(property_exists($return, 'db_id'),
			"The returned object should have the primary key included.");
		$this->assertEquals($dbval['name'], $return->name,
			"The returned object should be the one we asked for.");
		// test the update part of chado_insert_db().
		$dbval2 = $dbval;
		$dbval2['url'] .= '/updated';
		$returnagain = chado_insert_db($dbval2, [], 'testchado');
		$this->assertNotFalse($returnagain, 'chado_insert_db failed unexpectedly.');
		$this->assertIsObject($returnagain, 'Should be an updated DB object.');
		$this->assertTrue(property_exists($returnagain, 'db_id'),
			"The returned object should have the primary key included.");
		$this->assertEquals($dbval2['name'], $returnagain->name,
			"The returned object should be the one we asked for.");
		$this->assertEquals($dbval2['url'], $returnagain->url,
			"The URL should be updated.");
		$this->assertEquals($return->db_id, $returnagain->db_id,
			"Both should be the same database record!");

		// SELECT.
		// chado_get_db().
		$selectval = [
			'name' => $dbval['name'],
		];
		$return2 = chado_get_db($selectval, [], 'testchado');
		$this->assertNotFalse($return2, 'chado_select_db failed unexpectedly.');
		$this->assertIsObject($return2, 'Should be a DB object.');
		$this->assertEquals($dbval['name'], $return2->name,
			"The returned object should be the one we asked for.");
		// chado_get_db_select_options().
		$returned_options = chado_get_db_select_options('testchado');
		$this->assertNotFalse($returned_options, 'chado_get_db_select_options failed unexpectedly.');
		$this->assertIsArray($returned_options, 'Should be an array.');
		$this->assertNotEmpty($returned_options, "There should be at least one option.");;
		$this->assertArrayHasKey($return->db_id, $returned_options,
			"The DB we added should be one of the options.");

	}

	/**
   * Tests chado.dbxref associated functions.
   *
   * @group tripal-chado
   * @group chado-db
   */
  public function testDbxref() {

		$this->markTestIncomplete('This test is causing errors in the new environment.');

		// INSERT.
		// chado_insert_dbxref().
		$dbval = [
			'name' => 'dbxref-test'.uniqid(),
			'url' => 'https://www.lipsum.com/feed',
			'urlprefix' => 'https://www.lipsum.com/{accession}/feed',
		];
		$db = chado_insert_db($dbval, [], 'testchado');
		$dbxrefval = [
			'db_id' => $db->db_id,
			'accession' => 'dbxref-test'.uniqid(),
		];
		$return = chado_insert_dbxref($dbxrefval, [], 'testchado');
		$this->assertNotFalse($return, 'chado_insert_dbxref failed unexpectedly.');
		$this->assertIsObject($return, 'Should be an updated Dbxref object.');
		$this->assertTrue(property_exists($return, 'dbxref_id'),
			"The returned object should have the primary key included.");
		$this->assertEquals($dbxrefval['accession'], $return->accession,
			"The returned object should be the one we asked for.");
		// check it is returned if it already exists.
		$returnagain = chado_insert_dbxref($dbxrefval, [], 'testchado');
		$this->assertNotFalse($returnagain, 'chado_insert_dbxref failed unexpectedly.');
		$this->assertIsObject($returnagain, 'Should be an updated Dbxref object.');
		$this->assertTrue(property_exists($returnagain, 'dbxref_id'),
			"The returned object should have the primary key included.");
			$this->assertEquals($dbxrefval['accession'], $return->accession,
				"The returned object should be the one we asked for.");
		$this->assertEquals($return, $returnagain,
			"Both should be the same database record!");

		// chado_associate_dbxref().
		$org = ['genus' => 'Tripalus', 'species' => 'databasica'.uniqid()];
		$orgr = chado_insert_record('organism', $org, [], 'testchado');
		$dbxrefval['accession'] = 'dbxreforg-test'.uniqid();
		$return = chado_associate_dbxref(
			'organism',
			$orgr['organism_id'],
			$dbxrefval,
			[],
			'testchado'
		);
		$this->assertNotFalse($return, 'chado_associate_dbxref failed unexpectedly.');
		$this->assertIsObject($return, 'Should be the linking record.');

		// SELECT.
		// chado_get_dbxref().
		$return = chado_get_dbxref($dbxrefval, [], 'testchado');
		$this->assertNotFalse($return, 'chado_get_dbxref failed unexpectedly.');
		$this->assertIsObject($return, 'Should be an updated Dbxref object.');
		$this->assertTrue(property_exists($return, 'dbxref_id'),
			"The returned object should have the primary key included.");
		$this->assertEquals($dbxrefval['accession'], $return->accession,
			"The returned object should be the one we asked for.");

		// chado_get_dbxref_url().
		$expected_url = 'https://www.lipsum.com/' . $dbxrefval['accession'] . '/feed';
		$returned_url = chado_get_dbxref_url($return, [], 'testchado');
		$this->assertEquals($expected_url, $returned_url,
			"We did not get what we expected in terms of url.");
	}
}
