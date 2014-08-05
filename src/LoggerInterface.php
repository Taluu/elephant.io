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
 * @author Byeoung Wook <quddnr145@gmail.com>
 */
interface LoggerInterface
{
    /** Information message print to the target server */
    public function info($errstr);
}

