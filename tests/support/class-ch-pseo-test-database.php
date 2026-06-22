<?php
/**
 * Database test double.
 *
 * @package CH_PSEO
 */

/**
 * Records schema reconciliation calls without using WordPress.
 */
class CH_PSEO_Test_Database extends CH_PSEO_Database {

	/**
	 * Number of create_tables() calls.
	 *
	 * @var int
	 */
	public $create_tables_calls = 0;

	/**
	 * Number of remove_legacy_fields() calls.
	 *
	 * @var int
	 */
	public $remove_legacy_fields_calls = 0;

	/**
	 * Number of enforce_relational_constraints() calls.
	 *
	 * @var int
	 */
	public $enforce_relational_constraints_calls = 0;

	/**
	 * Number of make_url_base_optional() calls.
	 *
	 * @var int
	 */
	public $make_url_base_optional_calls = 0;

	/**
	 * Records a schema reconciliation call.
	 *
	 * @return void
	 */
	public function create_tables() {
		++$this->create_tables_calls;
	}

	/**
	 * Records a legacy-field cleanup call.
	 *
	 * @return void
	 */
	public function remove_legacy_fields() {
		++$this->remove_legacy_fields_calls;
	}

	/**
	 * Records a relational-constraint reconciliation call.
	 *
	 * @return void
	 */
	public function enforce_relational_constraints() {
		++$this->enforce_relational_constraints_calls;
	}

	/**
	 * Records URL-base schema reconciliation.
	 *
	 * @return void
	 */
	public function make_url_base_optional() {
		++$this->make_url_base_optional_calls;
	}
}
