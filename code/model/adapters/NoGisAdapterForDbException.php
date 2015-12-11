<?php
/**
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 12.11.2015
 * @package gis
 * @subpackage adapters
 */
class NoGisAdapterForDbException extends Exception
{
	public $connector;

	/**
	 * @param SS_Database $connector
	 */
	public function __construct($connector) {
		parent::__construct("No GIS adapter exists for " . get_class($connector));
	}
}
