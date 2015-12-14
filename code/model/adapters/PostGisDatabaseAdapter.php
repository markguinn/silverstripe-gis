<?php

/**
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 12.11.2015
 * @package gis
 * @subpackage adapters
 */
class PostGisDatabaseAdapter extends GisDatabaseAdapter
{
	/**
	 * @param SS_Database $connection
	 */
	public function __construct(SS_Database $connection) {
		parent::__construct($connection);
		/** @var PostgreSQLDatabase $connection */
		$connection->setSchemaSearchPath( $connection->currentSchema(), 'postgis' );
	}


	/**
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string $type
	 * @param int $srid
	 */
	public function requireField($tableName, $fieldName, $type, $srid = 0) {
		// Check if the extension is installed
		if (!$this->isPostGisInstalled()) {
			$this->installPostGis();
		}

		$srid = (int)$srid;

		// Check if the field exists
//		if (!$this->fieldExists($tableName, $fieldName)) {
//			DB::prepared_query("SELECT AddGeometryColumn (?, ?, ?, ?, 2)", [$tableName, $fieldName, $srid, $type]);
//			DB::get_schema()->alterationMessage("Added geometry column $fieldName of type $type");
//		}

		DB::require_field($tableName, $fieldName, "postgis.geometry($type, $srid)");
	}


	/**
	 * @param string $fieldName
	 * @param SS_Query|SQLQuery $query
	 */
	public function addFieldToQuery($fieldName, &$query) {
		$query = $query->selectField("postgis.ST_AsText(\"{$fieldName}\")", "{$fieldName}_AsText");
	}


	/**
	 * @param string|array|null $value
	 * @return string|array|null
	 */
	public function adjustWriteManipulation($value) {
		$key = 'GeomFromText(?)';

		if (is_array($value) && isset($value[$key])) {
			$wkt = $value[$key];
			unset($value[$key]);
			$key = 'postgis.ST_' . $key;
			$value[$key] = $wkt;
		}

		return $value;
	}


	/**
	 * @return bool
	 */
	protected function isPostGisInstalled() {
		$r = DB::query("SELECT * FROM pg_extension WHERE extname = 'postgis'");
		return $r->numRecords() > 0;
	}


	/**
	 * Install the postGis extension on this database
	 */
	protected function installPostGis() {
		DB::query("CREATE SCHEMA postgis;");
		DB::query("CREATE EXTENSION IF NOT EXISTS postgis;");
		DB::query("ALTER EXTENSION postgis SET SCHEMA postgis");
	}


	/**
	 * @param string $tableName
	 * @param string $fieldName
	 * @return bool
	 */
	protected function fieldExists($tableName, $fieldName) {
		$r = DB::prepared_query("
			SELECT column_name, data_type, character_maximum_length
			FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = ?
				AND column_name = ?
		", [$tableName, $fieldName]);
		return $r->numRecords() > 0;
	}


}
