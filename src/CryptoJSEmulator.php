<?php

declare( strict_types = 1 );

namespace Sailing\ApiClient;

require_once __DIR__ . '/../vendor/autoload.php';

final class CryptoJSEmulator
{

	private const SALT = 'OfSaIApI';

	public static function decrypt ( string $payload, string $key ): string
	{
		$decoded = base64_decode( $payload, TRUE );

		if ( $decoded === FALSE ) {
			return '';
		}

		$salt = mb_substr( $decoded, 8, 8, '8bit' );

		$bytes = '';
		$last  = '';

		while ( strlen( $bytes ) < 48 ) {
			$last  = hash( 'md5', $last . $key . $salt, TRUE );
			$bytes .= $last;
		}

		$key        = mb_substr( $bytes, 0, 32, '8bit' );
		$iv         = mb_substr( $bytes, 32, 16, '8bit' );
		$ciphertext = mb_substr( $decoded, 16, NULL, '8bit' );

		$decrypted = openssl_decrypt( $ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );

		return $decrypted !== FALSE ? $decrypted : '';
	}

	public static function encrypt ( string $payload, string $key ): string
	{
		$salt = openssl_random_pseudo_bytes( 8 );

		$keyAndIV = self::aes_evpKDF( $key, $salt );
		$encrypt  = openssl_encrypt( $payload, 'aes-256-cbc', $keyAndIV[ "key" ], OPENSSL_RAW_DATA, $keyAndIV[ 'iv' ] );

		return base64_encode( self::SALT . $salt . $encrypt );
	}

	private static function aes_evpKDF ( string $password, string $salt, int $keySize = 8, int $ivSize = 4, int $iterations = 1, string $hashAlgorithm = 'md5' ): array
	{
		$targetKeySize        = $keySize + $ivSize;
		$derivedBytes         = '';
		$numberOfDerivedWords = 0;
		$block                = NULL;
		$hasher               = hash_init( $hashAlgorithm );

		while ( $numberOfDerivedWords < $targetKeySize ) {
			if ( $block != NULL ) {
				hash_update( $hasher, $block );
			}

			hash_update( $hasher, $password );
			hash_update( $hasher, $salt );
			$block  = hash_final( $hasher, TRUE );
			$hasher = hash_init( $hashAlgorithm );

			for ( $i = 1; $i < $iterations; $i ++ ) {
				hash_update( $hasher, $block );
				$block  = hash_final( $hasher, TRUE );
				$hasher = hash_init( $hashAlgorithm );
			}

			$derivedBytes .= substr( $block, 0, min( strlen( $block ), ( $targetKeySize - $numberOfDerivedWords ) * 4 ) );

			$numberOfDerivedWords += strlen( $block ) / 4;
		}

		return [
			'key' => substr( $derivedBytes, 0, $keySize * 4 ),
			'iv'  => substr( $derivedBytes, $keySize * 4, $ivSize * 4 ),
		];
	}

}