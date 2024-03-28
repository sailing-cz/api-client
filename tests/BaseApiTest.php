<?php

declare( strict_types = 1 );

require __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Nette\Utils\Json;
use Tester\Assert;

abstract class BaseApiTest extends Tester\TestCase
{

	protected Client $client;

	protected string $token = '';

	final public function setUp (): void
	{
		$this->client = new Client;
	}

	final public function checkRequiredProperty ( \stdClass $element, array $properties ): void
	{
		$element_as_array = (array) $element;

		foreach ( $properties as $property => $propertyType ) {
			Assert::contains( $property, array_keys( $element_as_array ) );

			Assert::notNull( $element_as_array[ $property ] );

			Assert::type( $propertyType, $element_as_array[ $property ] );
		}
	}

	final public function checkOptionalProperty ( \stdClass $element, array $properties ): void
	{
		$element_as_array = (array) $element;

		foreach ( $properties as $property => $propertyType ) {
			if ( property_exists( $element, $property ) ) {
				Assert::type( $propertyType, $element_as_array[ $property ] );
			}
		}
	}

	final public function getReponse ( string $url, int $expectedCount ): array
	{
		$extra = [];

		if ( $this->token !== '' ) {
			$extra = [ 'headers' => [ 'Authorization' => 'Bearer ' . $this->token ] ];
		}

		$response = $this->client->get( API_SERVER . $url, array_merge( [
				'http_errors' => FALSE,
				'synchronous' => TRUE,
			], $extra )
		);

		Assert::same( 200, $response->getStatusCode() );

		$json = $response->getBody()->getContents();

		/** @var array $set */
		$set = Json::decode( $json );

		Assert::type( 'array', $set );

		if ( count( $set ) < $expectedCount ) {
			Assert::fail( 'There is %1 elements in set, we expected at least %2.', count( $set ), $expectedCount );
		}

		return $set;
	}

	final public function checkUnauthorized ( string $url ): void
	{
		$response = $this->client->get( API_SERVER . $url, [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
		] );

		Assert::same( 401, $response->getStatusCode() );
	}

	final public function authorize ( $username, $passwordHashed ): void
	{
		$response = $this->client->post( API_SERVER . '/auth/login', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'json'        => [ 'username' => $username, 'password' => $passwordHashed, 'software' => 'Nette Tester Suite' ],
		] );

		Assert::same( 200, $response->getStatusCode() );

		$json = $response->getBody()->getContents();

		$set = Json::decode( $json );

		Assert::type( 'object', $set );

		Assert::type( 'string', $set->token );

		$this->token = $set->token;
	}

}