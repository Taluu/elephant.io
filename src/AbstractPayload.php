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

namespace ElephantIO;

/**
 * Payload for sending data through the websocket
 *
 * Loosely based on the work of the following :
 *   - Ludovic Barreca (@ludovicbarreca)
 *   - Byeoung Wook (@kbu1564)
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
abstract class AbstractPayload
{
    const OPCODE_CONTINUE               = 0x0;
    const OPCODE_TEXT                   = 0x1;
    const OPCODE_BINARY                 = 0x2;
    const OPCODE_NON_CONTROL_RESERVED_1 = 0x3;
    const OPCODE_NON_CONTROL_RESERVED_2 = 0x4;
    const OPCODE_NON_CONTROL_RESERVED_3 = 0x5;
    const OPCODE_NON_CONTROL_RESERVED_4 = 0x6;
    const OPCODE_NON_CONTROL_RESERVED_5 = 0x7;
    const OPCODE_CLOSE                  = 0x8;
    const OPCODE_PING                   = 0x9;
    const OPCODE_PONG                   = 0xA;
    const OPCODE_CONTROL_RESERVED_1     = 0xB;
    const OPCODE_CONTROL_RESERVED_2     = 0xC;
    const OPCODE_CONTROL_RESERVED_3     = 0xD;
    const OPCODE_CONTROL_RESERVED_4     = 0xE;
    const OPCODE_CONTROL_RESERVED_5     = 0xF;

    protected $fin = 0x1;
    protected $rsv = [0x0, 0x0, 0x0];

    protected $mask    = 0x0;
    protected $maskKey = [0x0, 0x0, 0x0, 0x0];

    protected $opCode;
    protected $payload;

    public function __construct($payload, $opCode, $mask)
    {
        $this->opCode  = $opCode;
        $this->payload = $payload;

        $this->setMask($mask);
    }

    public function setMask($mask)
    {
        $this->mask = (int)$mask;

        // @todo what is the type of mask ? what should we expect ? weak typing here which may provoke some errors ?
        if (0x1 === $this->mask) {
            $this->maskKey = openssl_random_pseudo_bytes(4);
        }

        return $this;
    }

    protected function maskData($data, $key)
    {
        $masked = '';
        $length = strlen($data);
        if (null == trim($key)) {
            return;
        }

        // @todo add verification for $key type and size ?

        for ($i = 0; $i < $length; $i++) {
            $masked .= $data[$i] ^ $key[$i % 4];
        }

        return $masked;
    }
}

