<?php
/**
 * This file is part of the Elephant.io package
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Wisembly
 * @license   http://www.opensource.org/licenses/MIT-License MIT License
 */

namespace ElephantIO\Payload;

use ElephantIO\AbstractPayload;

/**
 * Decode the payload from a received frame
 *
 * Based on the work of the following :
 *   - Ludovic Barreca (@ludovicbarreca), project founder
 *   - Byeoung Wook (@kbu1564) in #49
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class Encoder extends AbstractPayload
{
    public function encode()
    {
        $extn   = 0x0;
        $length = strlen($this->payload);

        if ($length > 125) {
            $extn   = $length;
            $length = ($length <= 0xFFFF) ? 126 : 127;
        }

        $encodeData = ($this->fin  << 1) | $this->rsv[0];
        $encodeData = ($encodeData << 1) | $this->rsv[1];
        $encodeData = ($encodeData << 1) | $this->rsv[2];
        $encodeData = ($encodeData << 4) | $this->opCode;
        $encodeData = ($encodeData << 1) | $this->mask;
        $encodeData = ($encodeData << 7) | $length;

        $encodeData = pack('n', $encodeData);

        switch ($length) {
            case 126:
                $encodeData .= pack('n*', $extn);
                break;

            case 127:
                $encodeData .= pack('NN', ($extn >> 32), ($extn & 0xFFFFFFFF));
                break;
        }

        if (true === $this->mask) {
            $encodeData .= $this->maskKey;
            $rawMessage = $this->maskData($rawMessage, $this->maskKey);
        }

        return $encodeData . $rawMessage;
    }
}

