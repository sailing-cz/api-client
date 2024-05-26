<?php

declare( strict_types = 1 );

namespace Sailing\ApiClient;

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;

final class ApiClient
{

	private const API_SAILING             = 'https://api.sailing.cz/';

	private const API_DEV_SAILING         = 'https://dev.api.sailing.cz/';

	private const API_SAILING_ENC_PWD_KEY = 'p9wVp1vv1zMn1y86aDhiL0a3EYWDUrI6';

	private Client  $client;

	private ?string $authToken   = NULL;

	private string  $swIdent;

	private string  $apiUrl;

	private ?string $clubRegId;

	private mixed   $lastResults = NULL;

	public function __construct ( string $softwareIdentification, bool $devApi = FALSE, ?string $clubRegId = NULL )
	{
		$this->client = new Client();

		$this->swIdent = $softwareIdentification;

		$this->apiUrl = $devApi ? self::API_DEV_SAILING : self::API_SAILING;

		$this->clubRegId = $clubRegId;
	}

	private function loginUser ( string $login, string $password ): bool
	{
		$this->authToken = NULL;

		$passwordEncrypted = CryptoJSEmulator::encrypt( $password, self::API_SAILING_ENC_PWD_KEY );

		$response = $this->client->post( $this->apiUrl . '/auth/login', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'json'        => [ 'username' => $login, 'password' => $passwordEncrypted, 'software' => $this->swIdent ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 ) {
			return FALSE;
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->lastResults = $payload;
		$this->authToken   = $payload->token;

		return TRUE;
	}

	private function loginSystem ( string $systemToken, string $secret ): bool
	{
		$this->authToken = NULL;

		$secretEncrypted = CryptoJSEmulator::encrypt( $secret, self::API_SAILING_ENC_PWD_KEY );

		$response = $this->client->post( $this->apiUrl . '/auth/login', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'json'        => [ 'system' => $systemToken, 'secret' => $secretEncrypted, 'software' => $this->swIdent ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 ) {
			return FALSE;
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->lastResults = $payload;
		$this->authToken   = $payload->token;

		return TRUE;
	}

	private function logout (): void
	{
		if ( $this->authToken === NULL ) {
			return;
		}

		$this->client->post( $this->apiUrl . '/auth/logout', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;
		$this->authToken   = NULL;
	}

	private function communicationException ( ResponseInterface $response ): void
	{
		throw new ApiClientException( 'API error: ' . $response->getReasonPhrase(), $response->getStatusCode() );
	}

	public function createSystemToken ( string $login, string $password, string $newSecret, string $systemTokenTitle ): string
	{
		if ( ! $this->loginUser( $login, $password ) ) {
			throw new ApiClientException( 'Cannot log in for system token creation.', 401 );
		}

		$secretEncrypted = CryptoJSEmulator::encrypt( $newSecret, self::API_SAILING_ENC_PWD_KEY );

		$response = $this->client->post( $this->apiUrl . '/auth/system/create', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
			'json'        => [ 'secret' => $secretEncrypted, 'title' => $systemTokenTitle ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 ) {
			$this->communicationException( $response );
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->logout();

		$this->lastResults = $payload;

		return $payload->token;
	}

	public function getSystemTokensList (): array
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$response = $this->client->get( $this->apiUrl . '/auth/system/list', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 ) {
			$this->communicationException( $response );
		}

		$this->lastResults = Json::decode( $response->getBody()->getContents() );

		return $this->lastResults;
	}

	public function removeSystemToken ( string $token ): bool
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$response = $this->client->delete( $this->apiUrl . '/auth/system/' . $token, [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 && $response->getStatusCode() !== 404 ) {
			$this->communicationException( $response );
		}

		return $response->getStatusCode() === 200;
	}

	public function getMembers ( string $clubRegId ): array
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$response = $this->client->get( $this->apiUrl . '/evidence/members/' . $clubRegId, [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 ) {
			$this->communicationException( $response );
		}

		$this->lastResults = Json::decode( $response->getBody()->getContents() );

		return $this->lastResults;
	}

	public function deactivateLicense ( string $regId, ?string $clubRegId = NULL ): bool
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$clubRegId = $clubRegId ?? $this->clubRegId;

		if ( $clubRegId === NULL ) {
			return FALSE;
		}

		$response = $this->client->post( $this->apiUrl . '/evidence/members/' . $clubRegId . '/member/' . $regId . '/license/deactivate', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 && $response->getStatusCode() !== 404 ) {
			$this->communicationException( $response );
		}

		return $response->getStatusCode() === 200;
	}

	public function activateLicense ( string $regId, ?string $clubRegId = NULL ): bool
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$clubRegId = $clubRegId ?? $this->clubRegId;

		if ( $clubRegId === NULL ) {
			return FALSE;
		}

		$response = $this->client->post( $this->apiUrl . '/evidence/members/' . $clubRegId . '/member/' . $regId . '/license/activate', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 && $response->getStatusCode() !== 404 ) {
			$this->communicationException( $response );
		}

		return $response->getStatusCode() === 200;
	}

	public function setMemberDetails ( string $regId, array $details, ?string $clubRegId = NULL ): bool
	{
		if ( $this->authToken === NULL ) {
			throw new ApiClientException( 'You are not authorized.', 401 );
		}

		$clubRegId = $clubRegId ?? $this->clubRegId;

		if ( $clubRegId === NULL ) {
			return FALSE;
		}

		$response = $this->client->post( $this->apiUrl . '/evidence/members/' . $clubRegId . '/member/' . $regId . '/details', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
			'body'        => json_encode( $details ),
		] );

		$this->lastResults = NULL;

		if ( $response->getStatusCode() !== 200 && $response->getStatusCode() !== 404 ) {
			$this->communicationException( $response );
		}

		$this->lastResults = Json::decode( $response->getBody()->getContents() );

		return $response->getStatusCode() === 200;
	}

	public function getLastResults (): array
	{
		return $this->lastResults;
	}

	public function getClubs (): array
	{
		$response = $this->client->get( $this->apiUrl . '/catalogs/clubs', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
		] );

		if ( $response->getStatusCode() !== 200 ) {
			$this->communicationException( $response );
		}

		$this->lastResults = Json::decode( $response->getBody()->getContents() );

		return $this->lastResults;
	}

}