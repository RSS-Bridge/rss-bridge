<?php

final class UrlEncryptionService
{
    // The name of the special URL parameter indicating that this is an 'encrypted' request.
    public const PARAMETER_NAME = '_eut';

    // The cipher type to use for encryption and decryption.
    public const CIPHER = 'aes-128-cbc';

    private static ?self $instance = null;

    private string $rawTokenFromRequest;

    private string $key;

    private array $extractedContext = [];

    private function __construct(string $requestToken)
    {
        $rawToken = base64_decode($requestToken);

        if (!$requestToken || !$rawToken) {
            throw new \InvalidArgumentException('Invalid encryption token in request.');
        }

        $this->rawTokenFromRequest = $rawToken;
        $this->key = self::getKey();
    }

    public static function generateFromQueryString(string $q): string
    {
        if (!self::enabled()) {
            throw new \Exception('URL encryption is not enabled (an empty key cannot be used).');
        }

        // Always trim off leading '?' marks if they appear in the input.
        if (str_starts_with($q, '?')) {
            $q = substr($q, 1);
        }

        if (!$q) {
            throw new \Exception('The incoming query string to encrypt cannot be empty.');
        }

        if (!in_array(self::CIPHER, openssl_get_cipher_methods())) {
            throw new \Exception('The cipher "' . self::CIPHER . '" is not supported for this RSS-Bridge instance.');
        }

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);

        // Encrypt the compressed data.
        $cipherText = openssl_encrypt(
            $q,
            self::CIPHER,
            self::getKey(),
            0,
            $iv
        );

        if (!$cipherText) {
            throw new \Exception('Failed to generate an encrypted URL (invalid ciphertext).');
        }

        // The object to marshal later is in the concatenated format below.
        //   $raw[:1]  = one-byte length of the init vector (n)
        //   $raw[1:n] = raw init vector
        //   $raw[n:]  = decoded (raw) ciphertext
        //
        // This same structure is used when decoding and decrypting incoming tokens to
        //   rebuild the original query string.
        $raw  = chr($ivLength & 0xFF);
        $raw .= $iv;
        $raw .= base64_decode($cipherText);

        return base64_encode($raw);
    }

    public static function enabled(): bool
    {
        return !!self::getKey();
    }

    public static function getKey(): ?string
    {
        $key = trim(Configuration::getConfig('system', 'enc_url_key', ''));

        if (!$key) {
            // No key means the URL encryption feature is disabled.
            return null;
        }

        if ($key === 'b3c7@hsLqk)P(SJvjCBDUy]GMg6RamdHxEWV8K9nA4QN.p_5') {
            throw new \Exception('You cannot use the example URL encryption key... Don\'t be lazy.');
        }

        if (strlen($key) > 64 || strlen($key) < 16) {
            throw new \Exception('The URL encryption key must be between 16 and 64 characters long.');
        }

        if (preg_match('#\s#', $key)) {
            throw new \Exception('The URL encryption key cannot contain whitespace.');
        }

        return $key;
    }

    public static function fromRequest(Request &$request): ?self
    {
        if (!self::enabled()) {
            return null;
        }

        self::$instance = new self($request->get(self::PARAMETER_NAME));
        self::$instance->decrypt();

        return self::$instance;
    }

    public function toArray(): array
    {
        return $this->extractedContext;
    }

    private function decrypt(): void
    {
        if (!$this->key) {
            throw new \Exception('URL encryption is not enabled (an empty key cannot be used).');
        }

        if (!$this->rawTokenFromRequest) {
            throw new \Exception('The request does not contain a decrypt-able token.');
        }

        $t = $this->rawTokenFromRequest;
        $ivLength = ord($t[0]);

        if (!$t) {
            throw new \Exception('Invalid token base64 value.');
        } elseif (!$ivLength || $ivLength > 32) {
            throw new \Exception('Invalid initialization vector length.');
        } elseif ($ivLength >= strlen($t)) {
            throw new \Exception('No payload to decrypt.');
        }

        $iv = substr($t, 1, $ivLength);
        $cipherText = base64_encode(substr($t, $ivLength + 1));

        $originalQuery = openssl_decrypt(
            $cipherText,
            self::CIPHER,
            self::getKey(),
            0,
            $iv
        );

        if (!$originalQuery) {
            throw new \Exception('Failed to decrypt the given token.');
        }

        $result = [];
        parse_str($originalQuery, $result);

        if (!count($result)) {
            throw new \Exception('The encrypted token did not result in a parseable query string.');
        }

        // Finally, set the extracted context store and put the _eut back in.
        //   This gets bubbled up to the 'get' container of a Request instance later.
        $this->extractedContext = $result;
    }
}