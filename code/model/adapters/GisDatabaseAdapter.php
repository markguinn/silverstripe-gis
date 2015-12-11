<?php
/**
 * Abstracts some database functionality so this can work interchangably with
 * Mysql Spatial and PostGIS (and others)
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 12.10.2015
 * @package gis
 * @subpackage adapters
 */
abstract class GisDatabaseAdapter
{
	/**
	 * @var SS_Database
	 */
	protected $connection;


	/**
	 * Create a new GIS database adapter using the given db connection.
	 * @param SS_Database $connection
	 */
	public function __construct(SS_Database $connection) {
		$this->connection = $connection;
	}


	/**
	 * Returns the appropriate adapter for the given database type
	 * @param SS_Database $connection [optional]
	 * @return GisDatabaseAdapter
	 * @throws NoGisAdapterForDbException
	 */
	public static function singleton(SS_Database $connection = null) {
		if (!$connection) $connection = DB::get_conn();
		switch ($connection->getDatabaseServer()) {
			case 'mysql':
				return Injector::inst()->get('MysqlSpatialDatabaseAdapter', true, [$connection]);
			case 'postgresql':
				return Injector::inst()->get('PostGisDatabaseAdapter', true, [$connection]);
			default:
				throw new NoGisAdapterForDbException($connection);
		}
	}


	/**
	 * @param string $tableName
	 * @param string $fieldName
	 * @param string $type
	 * @param int $srid [optional]
	 */
	public function requireField($tableName, $fieldName, $type, $srid = 0) {
		DB::require_field($tableName, $fieldName, $type);
	}


	/**
	 * @param string $fieldName
	 * @param SS_Query|SQLQuery $query
	 */
	public function addFieldToQuery($fieldName, &$query) {
		$query = $query->selectField("AsText({$fieldName})", "{$fieldName}_AsText");
	}


	/**
	 * @param string|array|null $value
	 * @return string|array|null
	 */
	public function adjustWriteManipulation($value) {
		return $value;
	}
}
