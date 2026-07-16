<?php

/**
 * @package     Joomla.Administrator
 * @subpackage  com_mcp
 *
 * @copyright   (C) 2026 Open Source Matters, Inc. <https://www.joomla.org>
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

declare(strict_types=1);

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;

// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\MVCComponent;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\MCP\Api\Auth\JwtAccessTokenValidator;
use Joomla\Component\MCP\Api\Auth\NumericSubjectResolver;
use Joomla\Component\MCP\Api\Auth\RemoteJwksProvider;
use Joomla\Component\MCP\Api\Auth\ResourceServerConfiguration;
use Joomla\Component\MCP\Api\Core\McpRequestContextFactory;
use Joomla\Component\MCP\Api\Core\ProtectedResourceMetadataProvider;
use Joomla\Component\MCP\Api\Core\ScopeAuthoriser;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

/**
 * The MCP component service provider.
 *
 * @since  __DEPLOY_VERSION__
 */
return new class () implements ServiceProviderInterface {
    /**
     * Registers the MCP component and OAuth Resource Server services.
     *
     * @since  __DEPLOY_VERSION__
     */
    public function register(Container $container): void
    {
        $container->registerServiceProvider(new MVCFactory('\\Joomla\\Component\\MCP'));
        $container->registerServiceProvider(new ComponentDispatcherFactory('\\Joomla\\Component\\MCP'));

        $container->set(
            ComponentInterface::class,
            function (Container $container): ComponentInterface {
                $mvcFactory = $container->get(MVCFactoryInterface::class);
                $component  = new MVCComponent($container->get(ComponentDispatcherFactoryInterface::class));
                $component->setMVCFactory($mvcFactory);

                $application = Factory::$application;

                try {
                    $configuration = ResourceServerConfiguration::fromRegistry(
                        ComponentHelper::getParams('com_mcp'),
                    );
                    $jwksProvider = new RemoteJwksProvider(
                        $configuration->jwksUri,
                        $configuration->jwksCacheLifetime,
                    );
                    $validator = new JwtAccessTokenValidator(
                        $configuration->issuer,
                        $jwksProvider,
                        $configuration->allowedAlgorithms,
                        $configuration->allowedTypes,
                        $configuration->clockSkew,
                    );
                    $contextFactory   = new McpRequestContextFactory(new NumericSubjectResolver());
                    $scopeAuthoriser  = new ScopeAuthoriser($configuration->baseScope);
                    $metadataProvider = new ProtectedResourceMetadataProvider(
                        $configuration->resource,
                        [$configuration->issuer],
                        $configuration->metadataUri,
                        $configuration->documentationUri,
                    );

                    $application->set('mcp.resourceServerConfiguration', $configuration);
                    $application->set('mcp.accessTokenValidator', $validator);
                    $application->set('mcp.requestContextFactory', $contextFactory);
                    $application->set('mcp.scopeAuthoriser', $scopeAuthoriser);
                    $application->set('mcp.protectedResourceMetadataProvider', $metadataProvider);
                } catch (\InvalidArgumentException) {
                    // Configuration errors are reported as HTTP 503 by the API controller.
                    $application->set('mcp.resourceServerConfiguration', null);
                    $application->set('mcp.accessTokenValidator', null);
                    $application->set('mcp.requestContextFactory', null);
                    $application->set('mcp.scopeAuthoriser', null);
                    $application->set('mcp.protectedResourceMetadataProvider', null);
                }

                return $component;
            },
        );
    }
};
