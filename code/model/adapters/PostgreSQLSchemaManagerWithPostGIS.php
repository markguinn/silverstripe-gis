<?php

/**
 * This allows you to create GIST type KNN indexes for finding nearest neighbor quickly.
 * Probably allows other things too but that's the main one.
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 12.14.2015
 * @package gis
 * @subpackage adapters
 */
class PostgreSQLSchemaManagerWithPostGis extends PostgreSQLSchemaManager
{
	/**
	 * The base SS postgres module doesn't include support for gist indexes. This just adds that functionality.
	 * @param string $tableName
	 * @param string $indexName
	 * @param array|string $indexSpec
	 * @param bool $asDbValue
	 * @return string
	 */
	protected function getIndexSqlDefinition($tableName, $indexName, $indexSpec, $asDbValue=false) {
		// Consolidate/Cleanup spec into array format
		$indexSpec = $this->parseIndexSpec($indexName, $indexSpec);

		if (strtolower($indexSpec['type']) === 'gist' && !$asDbValue) {
			$tableCol = $this->buildPostgresIndexName($tableName, $indexName);
			$fillfactor = $where = '';

			if (isset($indexSpec['fillfactor'])) {
				$fillfactor = 'WITH (FILLFACTOR = ' . $indexSpec['fillfactor'] . ')';
			}
			if (isset($indexSpec['where'])) {
				$where = 'WHERE ' . $indexSpec['where'];
			}

			$spec = "create index \"$tableCol\" ON \"$tableName\" USING gist (" . $indexSpec['value'] . ") $fillfactor $where";

			return trim($spec) . ';';
		} else {
			return parent::getIndexSqlDefinition($tableName, $indexName, $indexSpec, $asDbValue);
		}
	}

}
