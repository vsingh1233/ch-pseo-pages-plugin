<?php
/**
 * Dynamic PSEO request context.
 *
 * @package CH_PSEO
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stores normalized service, location, SEO, and canonical data for a request.
 */
class CH_PSEO_Context {

	/**
	 * Resolved context values.
	 *
	 * @var array<string, mixed>
	 */
	private $data = array();

	/**
	 * Populates the current request context.
	 *
	 * @param array  $service   Service database row.
	 * @param array  $mapping   Service-location database row.
	 * @param array  $locations Country, state, and city rows.
	 * @param string $canonical Canonical URL.
	 * @return void
	 */
	public function set_context( $service, $mapping, $locations, $canonical ) {
		$country = isset( $locations['country'] ) && is_array( $locations['country'] ) ? $locations['country'] : array();
		$state   = isset( $locations['state'] ) && is_array( $locations['state'] ) ? $locations['state'] : array();
		$city    = isset( $locations['city'] ) && is_array( $locations['city'] ) ? $locations['city'] : array();

		$country_name = isset( $country['name'] ) ? $country['name'] : '';
		$state_name   = isset( $state['name'] ) ? $state['name'] : '';
		$city_name    = isset( $city['name'] ) ? $city['name'] : '';
		$location     = $city_name ? $city_name : ( $state_name ? $state_name : $country_name );
		$location_type = $city_name ? 'city' : ( $state_name ? 'state' : 'country' );

		if ( 'city' === $location_type ) {
			$location_parent = $state_name ? $state_name : $country_name;
		} elseif ( 'state' === $location_type ) {
			$location_parent = $country_name;
		} else {
			$location_parent = '';
		}

		$full_parts    = array_filter( array( $city_name, $state_name, $country_name ) );
		$location_full = implode( ', ', $full_parts );
		$robots        = ! empty( $mapping['robots'] ) ? $mapping['robots'] : $service['robots_default'];
		$schema_type   = ! empty( $mapping['custom_schema_type'] ) ? $mapping['custom_schema_type'] : $service['schema_type'];

		$this->data = array(
			'service_name'        => $service['service_name'],
			'service_slug'        => $service['service_slug'],
			'url_base'            => $service['url_base'],
			'country_name'        => $country_name,
			'state_name'          => $state_name,
			'city_name'           => $city_name,
			'location'            => $location,
			'location_full'       => $location_full,
			'location_parent'     => $location_parent,
			'location_type'       => $location_type,
			'canonical_url'       => ! empty( $mapping['canonical_override'] ) ? $mapping['canonical_override'] : $canonical,
			'robots'              => $robots,
			'meta_title'          => '',
			'meta_description'    => '',
			'schema_type'         => $schema_type,
			'service_location_id' => (int) $mapping['id'],
			'template_page_id'    => (int) $service['template_page_id'],
			'service'             => $service,
			'mapping'             => $mapping,
			'country'             => $country,
			'state'               => $state,
			'city'                => $city,
		);

		$title_template = ! empty( $mapping['custom_meta_title'] ) ? $mapping['custom_meta_title'] : $service['meta_title_template'];
		$description_template = ! empty( $mapping['custom_meta_description'] ) ? $mapping['custom_meta_description'] : $service['meta_description_template'];

		$this->data['meta_title']       = $this->replace_tokens( $title_template );
		$this->data['meta_description'] = $this->replace_tokens( $description_template );
	}

	/**
	 * Clears the current context.
	 *
	 * @return void
	 */
	public function reset() {
		$this->data = array();
	}

	/**
	 * Gets one context value.
	 *
	 * @param string $key     Context key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( $key, $default = '' ) {
		return array_key_exists( $key, $this->data ) ? $this->data[ $key ] : $default;
	}

	/**
	 * Gets the complete public context.
	 *
	 * @return array<string, mixed>
	 */
	public function get_all() {
		return $this->data;
	}

	/**
	 * Gets the resolved service row.
	 *
	 * @return array|null
	 */
	public function get_service() {
		return $this->get( 'service', null );
	}

	/**
	 * Gets the deepest resolved location name.
	 *
	 * @return string
	 */
	public function get_location() {
		return $this->get( 'location', '' );
	}

	/**
	 * Gets the service-location mapping row.
	 *
	 * @return array|null
	 */
	public function get_service_location() {
		return $this->get( 'mapping', null );
	}

	/**
	 * Replaces supported context tokens in a configured template.
	 *
	 * @param string $template Template text.
	 * @return string
	 */
	public function replace_tokens( $template ) {
		if ( ! is_string( $template ) || '' === $template ) {
			return '';
		}

		$tokens = array(
			'{service_name}'    => $this->get( 'service_name' ),
			'{service_slug}'    => $this->get( 'service_slug' ),
			'{url_base}'        => $this->get( 'url_base' ),
			'{country}'         => $this->get( 'country_name' ),
			'{country_name}'    => $this->get( 'country_name' ),
			'{state}'           => $this->get( 'state_name' ),
			'{state_name}'      => $this->get( 'state_name' ),
			'{city}'            => $this->get( 'city_name' ),
			'{city_name}'       => $this->get( 'city_name' ),
			'{location}'        => $this->get( 'location' ),
			'{location_full}'   => $this->get( 'location_full' ),
			'{location_parent}' => $this->get( 'location_parent' ),
			'{location_type}'   => $this->get( 'location_type' ),
		);

		return strtr( $template, $tokens );
	}

	/**
	 * Determines whether the current request is a resolved PSEO request.
	 *
	 * @return bool
	 */
	public function is_pseo_request() {
		return ! empty( $this->data );
	}
}

