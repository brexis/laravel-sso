<?php

namespace Brexis\LaravelSSO;

/**
 * Encription class
 */
class Encription
{
    /**
     * Generate new checksum
     *
     * @param string $type
     * @param string $token
     * @param string $secret
     * @return string
     */
    public function generateChecksum($type, $token, $secret)
    {
        return hash('sha256', $type . $token . $secret);
    }

    /**
     * Verify if attach checksum matchs
     *
     * @param string $token
     * @param string $secret
     * @param string $checksum
     * @return bool
     */
    public function verifyAttachChecksum($token, $secret, $checksum)
    {
        $gen_checksum = $this->generateChecksum('attach', $token, $secret);

        return $checksum && $checksum === $gen_checksum;
    }

    /**
     * Generate a random token
     * 
     * @return string
     */
    public function randomToken()
    {
        return base_convert(md5(uniqid(rand(), true)), 16, 36);
    }
}
