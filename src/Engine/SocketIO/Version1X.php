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

namespace ElephantIO\Engine\SocketIO;

use InvalidArgumentException;

use Psr\Log\LoggerInterface;

use GuzzleHttp\Stream\Stream;

use ElephantIO\EngineInterface,
    ElephantIO\Engine\AbstractSocketIO,

    ElephantIO\Payload\Encoder,
    ElephantIO\Exception\SocketException;

/**
 * Implements the dialog with Socket.IO version 1.x
 *
 * Based on the work of Mathieu Lallemand (@lalmat)
 *
 * @author Baptiste Clavié <baptiste@wisembly.com>
 */
class Version1X extends AbstractSocketIO
{
    const TRANSPORT_POLLING   = 'polling';
    const TRANSPORT_WEBSOCKET = 'websocket';

    /** @var Stream */
    protected $stream;

    /** {@inheritDoc} */
    public function connect()
    {
        $this->handshake();

        if ($this->stream instanceof Stream) {
            return;
        }

        try {
            $errors = [null, null];
            $host   = $this->url['host'];

            if (true === $this->url['secured']) {
                $host = 'ssl://' . $host;
            }

            $this->stream = new Stream(fsockopen($host, $this->url['port'], $errors[0], $errors[1]));
        } catch (InvalidArgumentException $e) {
            throw new SocketException($error[0], $error[1], $e);
        }
    }

    /** {@inheritDoc} */
    public function close()
    {
        if (!$this->stream instanceof Stream) {
            return;
        }

        $this->write(EngineInterface::CLOSE);

        $this->stream->close();
        $this->stream = null;
    }

    /** {@inheritDoc} */
    public function emit($event, array $args)
    {
        $this->write(EngineInterface::MESSAGE, static::EVENT . json_encode(['name' => $event, 'args' => $args]));
    }

    /** {@inheritDoc} */
    public function write($code, $message = null)
    {
        if (!is_int($code) || 0 > $code || 6 < $code) {
            throw new InvalidArgumentException('Wrong message type when trying to write on the socket');
        }

        if (!$this->stream instanceof Stream) {
            return;
        }

        $payload = new Encoder($code . $message, Encoder::OPCODE_TEXT, true);
        $this->stream->write((string) $payload);
    }

    /** {@inheritDoc} */
    public function getName()
    {
        return 'SocketIO Version 1.X';
    }

    /** {@inheritDoc} */
    protected function getDefaultOptions()
    {
        $defaults = parent::getDefaultOptions();

        $defaults['version']   = 2;
        $defaults['use_b64']   = false;
        $defaults['transport'] = static::TRANSPORT_POLLING;

        return $defaults;
    }

    /** Does the handshake with the Socket.io server and populates the `session` value object */
    protected function handshake()
    {
        if (null !== $this->session) {
            return;
        }

        $query = ['use_b64'   => $this->options['use_b64'],
                  'EIO'       => $this->options['version'],
                  'transport' => $this->options['transport']];

        if (isset($this->url['query'])) {
            $query = array_replace($query, $this->url['query']);
        }

        $url = sprintf('%s://%s:%d/%s/?%s', true === $this->url['secured'] ? 'ssl' : $this->url['scheme'], $this->url['host'], $this->url['port'], $this->url['path'], http_build_query($query));

        $result  = file_get_contents($url);
        $decoded = json_decode(substr($result, strpos($result, '{')), true);

        $this->session = new Session($decoded['sid'], $decoded['pingInterval'], $decoded['pingTimeout'], $decoded['upgrades']);
    }
}

