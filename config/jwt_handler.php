<?php
class JWTHandler {
    private $secret_key = 'pandemic_resilience_system_2024';
    private $expiration_time = 3600; // 1 hour

    public function createToken($user_id) {
        $issuedAt = time();
        $expirationTime = $issuedAt + $this->expiration_time;

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $user_id
        ];

        $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = base64_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', "$header.$payload", $this->secret_key, true);
        $signature = base64_encode($signature);

        return "$header.$payload.$signature";
    }

    public function validateToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return false;

        $header = $parts[0];
        $payload = $parts[1];
        $signature = $parts[2];

        $valid_signature = hash_hmac('sha256', "$header.$payload", $this->secret_key, true);
        $valid_signature = base64_encode($valid_signature);

        if ($signature !== $valid_signature) return false;

        $payload_decoded = json_decode(base64_decode($payload), true);
        return $payload_decoded['exp'] > time();
    }
}
?> 
