<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  WebService.Internal
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

namespace Joomla\CMS\WebService\Internal;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Document\JsonapiDocument;
use Joomla\CMS\User\User;
use Joomla\Input\Input;

/**
 * Isolated API application context used while dispatching an internal web service request.
 *
 * The application reuses the outer application's configuration, container, event dispatcher, session and language.
 * Its identity is supplied explicitly by the authenticated caller. Request, document, response body and headers remain
 * local to the internal dispatch.
 *
 * @since  __DEPLOY_VERSION__
 */
final class InternalApiApplication extends CMSApplication
{
    /**
     * @since  __DEPLOY_VERSION__
     */
    public function __construct(CMSApplication $parent, Input $input, User $identity)
    {
        $this->name     = 'api';
        $this->clientId = 3;

        parent::__construct(
            $input,
            $parent->getConfig(),
            null,
            $parent->getContainer(),
        );

        $this->document = new JsonapiDocument();
        $this->loadIdentity($identity);
        $this->loadLanguage($parent->getLanguage());

        if (method_exists($this, 'setDispatcher') && method_exists($parent, 'getDispatcher')) {
            $this->setDispatcher($parent->getDispatcher());
        }

        if (method_exists($this, 'setSession') && method_exists($parent, 'getSession')) {
            $this->setSession($parent->getSession());
        }
    }

    /**
     * Prevents an internal controller from terminating the outer MCP request.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function close($code = 0)
    {
        throw new InternalApiApplicationClosed('The internal API application was closed.', (int) $code);
    }

    /**
     * Suppresses direct header output. Headers remain available through the application response state.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function sendHeaders()
    {
        return $this;
    }

    /**
     * The internal application is dispatched explicitly and is never executed as a top-level application.
     *
     * @since  __DEPLOY_VERSION__
     */
    protected function doExecute()
    {
    }
}
