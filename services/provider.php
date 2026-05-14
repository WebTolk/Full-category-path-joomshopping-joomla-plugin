<?php

/**
 * Service provider for the JoomShopping full category path router plugin.
 * @package       Jshoppingrouter - Full Category Path
 * @subpackage    plg_jshoppingrouter_full_category_path
 * @author        Sergey Tolkachyov
 * @copyright     Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @version       __DEPLOY_VERSION__
 * @license       GNU General Public License version 3 or later.
 * @link          https://web-tolk.ru
 */

\defined('_JEXEC') or die;

use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Plugin\Jshoppingrouter\FullCategoryPath\Extension\FullCategoryPath;

JLoader::registerNamespace('Joomla\\Plugin\\Jshoppingrouter\\FullCategoryPath', __DIR__ . '/../src');

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(
            PluginInterface::class,
            $container->lazy(FullCategoryPath::class, function () {
                $plugin = new FullCategoryPath(
                    (array) PluginHelper::getPlugin('jshoppingrouter', 'full_category_path')
                );

                $plugin->setApplication(Factory::getApplication());

                return $plugin;
            })
        );
    }
};
