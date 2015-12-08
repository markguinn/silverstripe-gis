<?php

define("GOOGLEMAPS_HOST", "maps.google.com");

/**
 * Server-side connector to the Google Geocoding API.
 * Uses GOOGLEMAPS_API_KEY for the API key
 *
 * Usage:
 * <pre>
 * $g = new GoogleGeocoder();
 * $geoPoint = $g->addressToPoint("123 Victoria St, Te Aro, Wellington, NZ");
 * $otherGeoPoint = $g->addressPartsToPoint("123", "Adelaide Rd", "Newtown", "Wellington", "NZ");
 * </pre>
 */
class GoogleGeocoder {

	/**
	 * @var IGeocodingService
	 */
	protected $service;


	/**
	 * @return IGeocodingService
	 */
	public function getService() {
		if (!isset($this->service)) {
			$this->service = Injector::inst()->get('GeocodingService');
		}
		return $this->service;
	}

	/**
	 * @param IGeocodingService $service
	 * @return $this
	 */
	public function setService($service) {
		$this->service = $service;
		return $this;
	}

	/**
	 * Returns a Google Geopoint matching the given address parts
	 */
	public function addressPartsToPoint($streetNumber, $street, $suburb, $city, $country) {
		return $this->addressToPoint("$streetNumber, $street, $suburb, $city, $country");
	}

	/**
	 * Returns a Google Geopoint matching the given address.
	 */
	public function addressToPoint($address) {
		$result = $this->getService()->geocode($address);
		if ($result['Success']) {
			return GeoPoint::from_x_y($result['Longitude'], $result['Latitude']);
		} else {
			user_error("Could not geocode address: " . $result['Message'], E_USER_ERROR);
		}
	}
}

