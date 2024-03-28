<?php

declare( strict_types = 1 );

require __DIR__ . '/bootstrap.php';

use Sailing\ApiClient\ApiClient;
use Tester\Assert;

/**
 * @testCase
 */
final class PublicApiTest extends \Tester\TestCase
{

	const EXPECTED_CLUBS = 100;

	private function checkRequiredProperty ( \stdClass $element, array $properties ): void
	{
		$element_as_array = (array) $element;

		foreach ( $properties as $property => $propertyType ) {
			Assert::contains( $property, array_keys( $element_as_array ) );

			Assert::notNull( $element_as_array[ $property ] );

			Assert::type( $propertyType, $element_as_array[ $property ] );
		}
	}

	private function checkOptionalProperty ( \stdClass $element, array $properties ): void
	{
		$element_as_array = (array) $element;

		foreach ( $properties as $property => $propertyType ) {
			if ( property_exists( $element, $property ) ) {
				Assert::type( $propertyType, $element_as_array[ $property ] );
			}
		}
	}

	public function testActionGet (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$clubs = $apiClient->getClubs();

		Assert::type( 'array', $clubs );

		if ( count( $clubs ) < self::EXPECTED_CLUBS ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $clubs ), self::EXPECTED_CLUBS );
		}

		$clubIds = [];

		foreach ( $clubs as $club ) {
			Assert::type( 'object', $club );

			$this->checkRequiredProperty( $club, [ 'id' => 'int', 'name' => 'string' ] );

			Assert::match( '#^[0-9]{4}#', (string) $club->id, 'Club ID is not 4-digit length.' );

			$clubIds[] = $club->id;
		}

		Assert::contains( 1101, $clubIds, 'CYK (1101) has to exists in set.' );

		Assert::contains( 2103, $clubIds, 'YCLSB (2103) has to exists in set.' );

		Assert::contains( 9999, $clubIds, 'One-time use licenses has to exists in set.' );
	}

}

( new PublicApiTest() )->run();
