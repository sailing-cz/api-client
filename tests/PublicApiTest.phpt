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

	const EXPECTED_CLUBS       = 100;

	const EXPECTED_VENUES      = 20;

	const EXPECTED_CLASSES     = 30;

	const EXPECTED_COMPETITORS = 1000;

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

	public function testActionCatalogClubs (): void
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

	public function testActionCatalogVenues (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$venues = $apiClient->getVenues();

		Assert::type( 'array', $venues );

		if ( count( $venues ) < self::EXPECTED_VENUES ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $venues ), self::EXPECTED_VENUES );
		}

		$venueIds = [];

		foreach ( $venues as $venue ) {
			Assert::type( 'object', $venue );

			$this->checkRequiredProperty( $venue, [ 'id' => 'int', 'name' => 'object' ] );

			$this->checkRequiredProperty( $venue->name, [ 'cs' => 'string' ] );

			$venueIds[] = $venue->id;
		}

		Assert::contains( 21, $venueIds, 'Brněnská přehrada (21) has to exists in set.' );

		Assert::contains( 27, $venueIds, 'Lipno - Černá v Pošumaví (27) has to exists in set.' );
	}

	public function testActionCatalogClasses (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$classes = $apiClient->getClasses();

		Assert::type( 'array', $classes );

		if ( count( $classes ) < self::EXPECTED_CLASSES ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $classes ), self::EXPECTED_CLASSES );
		}

		$classIds = [];

		foreach ( $classes as $class ) {
			Assert::type( 'object', $class );

			$this->checkRequiredProperty( $class, [ 'id' => 'int', 'name' => 'string', 'shortcut' => 'string' ] );

			$classIds[] = $class->id;
		}

		Assert::contains( 52, $classIds, 'RS Aero (52) has to exists in set.' );

		Assert::contains( 11, $classIds, 'Optimist (11) has to exists in set.' );

		Assert::contains( 30, $classIds, 'Other classes (30) has to exists in set.' );
	}

	public function testActionCatalogRefereeTitles (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$titles = $apiClient->getRefereeTitles();

		Assert::type( 'array', $titles );

		if ( count( $titles ) < 1 ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $titles ), 1 );
		}
	}

	public function testActionCatalogCoachTitles (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$titles = $apiClient->getCoachTitles();

		Assert::type( 'array', $titles );

		if ( count( $titles ) < 1 ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $titles ), 1 );
		}
	}

	public function testActionCatalogRefereeRoles (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$roles = $apiClient->getRefereeRoles();

		Assert::type( 'array', $roles );

		if ( count( $roles ) < 1 ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $roles ), 1 );
		}
	}

	public function testActionCatalogLifeguardRoles (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$roles = $apiClient->getLifeguardRoles();

		Assert::type( 'array', $roles );

		if ( count( $roles ) < 1 ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $roles ), 1 );
		}
	}

	public function testActionCatalogCompetitors (): void
	{
		$apiClient = new ApiClient( 'nette-tester' );

		$competitors = $apiClient->getCompetitors();

		Assert::type( 'array', $competitors );

		if ( count( $competitors ) < self::EXPECTED_COMPETITORS ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $competitors ), self::EXPECTED_COMPETITORS );
		}
	}

}

( new PublicApiTest() )->run();
