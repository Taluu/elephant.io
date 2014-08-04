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
 * Based on the work of Byeoung Wook (@kbu1564) in #49
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class Decoder extends AbstractPayload
{
    // @todo Break that up once everything is understood
    public function decode()
    {
        $length = $this->getLength();

        // if ($payload !== null) and ($payload packet error)?
        // invalid websocket packet data or not (text, binary opCode)
        if (3 > $length) {
            return;
        }

        // php 5.x : notice: Array to string conversion
        $payload = array_map('ord', str_split($this->payload));

        $this->fin = ($this->payload[0] >> 7);

        $this->rsv = [($this->payload[0] >> 6) & 0x1,
                      ($this->payload[0] >> 5) & 0x1,
                      ($this->payload[0] >> 4) & 0x1];

        $this->opCode = $this->payload[0] & 0xF;
        $this->setMask($this->payload[1] >> 7);

        $maskKey = [0x0, 0x0, 0x0, 0x0];

        $payloadOffset = 2;

        if ($length > 125) {
            $payloadOffset = (0xFFFF < $length && 0xFFFFFFFF >= $length) ? 6 : 4;
        }

        $payload = implode('', array_map('chr', $payload));

        if (true === $this->mask) {
            $maskKey = substr($payload, $payloadOffset, 4);
            $this->maskKey = array_map('ord', str_split($maskKey));

            $payloadOffset += 4;
        }

        $data = substr($payload, $payloadOffset, $length);

        if (true === $this->mask) {
            $data = $this->maskData($data, $maskKey);
        }

        return $data;
    }

    protected function getLength()
    {
        if (null === $this->payload) {
            return;
        }

        $length = ord($this->payload[1]) & 0x7F;

        if ($length == 126 || $length == 127) {
            $length = unpack('H*', substr($this->payload, 2, ($length == 126 ? 2 : 4)));
            $length = hexdec($length[1]);
        }

        return $length;
    }
}

