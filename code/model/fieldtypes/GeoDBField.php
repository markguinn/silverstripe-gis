<?php
/**
 * Base class for all geometry features.
 *
 * @package gis
 *
 * @see http://www.opengeospatial.org/specs/?page=specs
 *
 * @param string $name
 * @param string $srid
 */
abstract class GeoDBField extends DBField implements CompositeDBField {

	/**
	 * SRID - Spatial Reference Identifier
	 *
	 * @see http://en.wikipedia.org/wiki/SRID
	 *
	 * @var string
	 */
	protected $srid = '';

	/**
	 * Stores the field value as a "Well-Known-Text" string,
	 * as opposed to the usual $value storage of DBField classes.
	 *
	 * @var string
	 */
	protected $wkt;

	/**
	 * Well-known text identifier of the subclass, e.g. POINT
	 *
	 * @var string
	 */
	protected static $wkt_name;

	/**
	 * @var GisDatabaseAdapter
	 */
	protected $gisAdapter;


	function __construct($name = null, $srid = null) {
		$this->srid = $srid;

		parent::__construct($name);
	}

	public function isChanged() {
		return $this->isChanged;
	}

	public function hasGeoValue() {
		return ($this->wkt);
	}

	public function requireField() {}


	/**
	 * @return GisDatabaseAdapter
	 */
	public function getGisAdapter() {
		if (!isset($this->gisAdapter)) {
			// Is there a better way to do this? What we've got here is injectable on several fronts
			// but it would be nice to pass the db connection in somehow...
			$this->gisAdapter = GisDatabaseAdapter::singleton();
		}
		return $this->gisAdapter;
	}


	/**
	 * @param GisDatabaseAdapter $gisAdapter
	 * @return $this
	 */
	public function setGisAdapter($gisAdapter) {
		$this->gisAdapter = $gisAdapter;
		return $this;
	}


	/**
	 * @param SQLQuery|SS_Query $query
	 */
	function addToQuery(&$query) {
		parent::addToQuery($query);
		$this->getGisAdapter()->addFieldToQuery($this->name, $query);
	}


	/**
	 * @param array|DBField|string $value
	 * @param array $record
	 * @param bool $markChanged
	 */
	public function setValue($value, $record = null, $markChanged = true) {
		// If we have an enter database record, look inside that
		// only if the column exists (and we're not dealing with a newly created instance)
		if($record && isset($record[$this->name . '_AsText'])) {
			if($record[$this->name . '_AsText']) {
				$this->setAsWKT($record[$this->name . '_AsText']);
			} else {
				$this->value = $this->nullValue();
			}
		} elseif ($value instanceof GeoDBField) {
			$this->setAsWKT($value->WKT());
		} elseif(self::is_valid_wkt($value, true)) {
			$this->setAsWKT($value);
		} elseif(is_array($value) && $this->hasMethod('setAsArray')) {
			$this->setAsArray($value);
		} elseif(is_null($value)) {
			$this->wkt = null;
		} else {
			user_error("{$this->class}::setValue() - Bad value " . var_export($value, true), E_USER_ERROR);
		}

		$this->isChanged = true;
	}

	/**
	 * @param string $wktString
	 */
	public function setAsWKT($wktString) {
		$wktString = preg_replace("/GeomFromText\\('(.*)'\\)\$/i","\\1",$wktString);
		$this->wkt = $wktString;
		$this->isChanged = true;
	}

	/**
	 * @return string
	 */
	public function WKT() {
		return "GeomFromText('{$this->wkt}')";
	}

	/**
	 * As of SS3.2 returning text tries to escape the function itself
	 * This gets around the parameterized queries.
	 * @return array
	 */
	protected function formatWKTForManipulation() {
		return [
			'GeomFromText(?)' => [$this->wkt],
		];
	}

	/**
	 * @param array $manipulation
	 */
	function writeToManipulation(&$manipulation) {
		if ($this->hasGeoValue()) {
			$value = $this->formatWKTForManipulation();
		} else {
			$value = $this->nullValue();
		}
		$value = $this->getGisAdapter()->adjustWriteManipulation($value);
		$manipulation['fields'][$this->name] = $value;
	}

	/**
	 * @return string
	 */
	public function getSRID() {
		return $this->srid;
	}

	/**
	 * @param string $id
	 */
	public function setSRID($id) {
		$this->srid = $id;
	}

	/**
	 * Determines if the passed string is in valid "Well-known Text" format.
	 * For increased security and accuracy you should overload
	 * this method in the specific subclasses.
	 *
	 * @param string $wktString
	 * @param bool $stripGeomFromText [optional]
	 * @return bool
	 */
	public static function is_valid_wkt($wktString, $stripGeomFromText = false) {
		if (!is_string($wktString)) return false;
		if ($stripGeomFromText) $wktString = preg_replace("/GeomFromText\\('(.*)'\\)\$/i","\\1",$wktString);
		return preg_match('/^(POINT|LINESTRING|LINEARRING|POLYGON|MULTIPOINT|MULTILINESTRING|MULTIPOLYGON|GEOMETRYCOLLECTION)\(.*\)$/', $wktString);
	}
}

