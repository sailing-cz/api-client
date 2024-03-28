<?php

declare( strict_types = 1 );

namespace Sailing\ApiClient;

require_once '../vendor/autoload.php';

use GuzzleHttp\Client;
use Nette\Utils\Json;

final class ApiClient
{

	private const API_SAILING             = 'https://api.sailing.cz/';

	private const API_DEV_SAILING         = 'https://dev.api.sailing.cz/';

	private const API_SAILING_ENC_PWD_KEY = 'p9wVp1vv1zMn1y86aDhiL0a3EYWDUrI6';

	private Client  $client;

	private ?string $authToken = NULL;

	private string  $swIdent;

	private string  $apiUrl;

	private ?string $clubRegId;

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

		if ( $response->getStatusCode() !== 200 ) {
			return FALSE;
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->authToken = $payload->token;

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

		if ( $response->getStatusCode() !== 200 ) {
			return FALSE;
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->authToken = $payload->token;

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

		$this->authToken = NULL;
	}

	public function createSystemToken ( string $login, string $password, string $newSecret, string $systemTokenTitle ): ?string
	{
		if ( ! $this->loginUser( $login, $password ) ) {
			return NULL;
		}

		$secretEncrypted = CryptoJSEmulator::encrypt( $newSecret, self::API_SAILING_ENC_PWD_KEY );

		$response = $this->client->post( $this->apiUrl . '/auth/system/create', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
			'json'        => [ 'secret' => $secretEncrypted, 'title' => $systemTokenTitle ],
		] );

		if ( $response->getStatusCode() !== 200 ) {
			return NULL;
		}

		$payload = Json::decode( $response->getBody()->getContents() );

		$this->logout();

		return $payload->token;
	}

	public function getSystemTokensList (): ?array
	{
		if ( $this->authToken === NULL ) {
			return NULL;
		}

		$response = $this->client->get( $this->apiUrl . '/auth/system/list', [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		if ( $response->getStatusCode() !== 200 ) {
			return NULL;
		}

		return Json::decode( $response->getBody()->getContents() );
	}

	public function removeSystemToken ( string $token ): bool
	{
		if ( $this->authToken === NULL ) {
			return FALSE;
		}

		$response = $this->client->delete( $this->apiUrl . '/auth/system/' . $token, [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		return $response->getStatusCode() === 200;
	}

	public function getMembers ( string $clubRegId ): ?array
	{
		if ( $this->authToken === NULL ) {
			return NULL;
		}

		$response = $this->client->get( $this->apiUrl . '/evidence/members/' . $clubRegId, [
			'http_errors' => FALSE,
			'synchronous' => TRUE,
			'headers'     => [ 'Authorization' => 'Bearer ' . $this->authToken ],
		] );

		if ( $response->getStatusCode() !== 200 ) {
			return [];
		}

		return Json::decode( $response->getBody()->getContents() );
	}

	public function deactivateLicense ( string $regId, ?string $clubRegId = NULL ): bool
	{
		if ( $this->authToken === NULL ) {
			return FALSE;
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

		return $response->getStatusCode() === 200;
	}

	public function activateLicense ( string $regId, ?string $clubRegId = NULL ): bool
	{
		if ( $this->authToken === NULL ) {
			return FALSE;
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

		return $response->getStatusCode() === 200;
	}

}