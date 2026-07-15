<?php

/**
 * @package         Joomla.API
 * @subpackage      com_mcp
 *
 * @copyright       (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */
declare(strict_types=1);

namespace Joomla\Component\MCP\Api\Resource;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

interface ResourceTemplateInterface
{
    public function getName(): string;

    public function getUriTemplate(): string;

    public function getDescription(): string;

    public function getTitle(): string;

    public function getMimeType(): string;
}
