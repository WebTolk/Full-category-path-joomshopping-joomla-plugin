<?php

/**
 * JoomShopping full category path router plugin.
 * @package       Jshoppingrouter - Full Category Path
 * @subpackage    plg_jshoppingrouter_full_category_path
 * @author        Sergey Tolkachyov
 * @copyright     Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @version       5.0.0
 * @license       GNU General Public License version 3 or later.
 * @link          https://web-tolk.ru
 */

declare(strict_types=1);

namespace Joomla\Plugin\Jshoppingrouter\FullCategoryPath\Extension;

\defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Component\Jshopping\Site\Helper\Helper as JoomShoppingHelper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Component\Jshopping\Site\Lib\ShopItemMenu;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\ParameterType;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;

final class FullCategoryPath extends CMSPlugin implements SubscriberInterface
{
    protected $autoloadLanguage = true;

    private int $categoryId = 0;

    public static function getSubscribedEvents(): array
    {
        return [
            'onBeforeBuildRoute' => 'onBeforeBuildRoute',
            'onBeforeParseRoute' => 'onBeforeParseRoute',
            'onAfterParseRoute'  => 'onAfterParseRoute',
        ];
    }

    public function onBeforeBuildRoute(EventInterface $event): void
    {
        $arguments = $event->getArguments();

        if (!isset($arguments[0], $arguments[1])) {
            return;
        }

        $query    = &$arguments[0];
        $segments = &$arguments[1];

        if (!$this->isSupportedBuildQuery($query)) {
            return;
        }

        $categoryId = (int) $query['category_id'];

        if ($query['controller'] === 'product') {
            $categoryId           = $this->resolveProductBuildCategoryId((int) $query['product_id'], $categoryId);
            $query['category_id'] = $categoryId;
        }

        $categoryItemIdList = ShopItemMenu::getInstance()->getListCategory();

        if (\array_key_exists($categoryId, $categoryItemIdList)) {
            return;
        }

        $segments = self::buildSegment($categoryId);
    }

    public function onBeforeParseRoute(EventInterface $event): void
    {
        $arguments = $event->getArguments();

        if (!isset($arguments[0], $arguments[1])) {
            return;
        }

        $vars     = &$arguments[0];
        $segments = &$arguments[1];

        $aliasCategory = JSFactory::getAliasCategory();
        $aliasProduct  = JSFactory::getAliasProduct();
        $aliases       = str_replace(':', '-', $segments);
        $productId     = array_search(end($aliases), $aliasProduct);
        $countSegments = \count($segments);

        if ($countSegments > 1 && $productId) {
            array_pop($aliases);

            $product = JSFactory::getTable('Product', 'jshop');
            $product->load($productId);

            $listCategory          = $product->getCategories(1);
            $categoryAlias         = array_pop($aliases);
            $categoryIds           = array_keys($aliasCategory, $categoryAlias);
            $needRedirect          = true;
            $categoriesForRedirect = [];

            foreach ($categoryIds as $categoryId) {
                if (\in_array($categoryId, $listCategory)) {
                    $buildSegments                      = self::buildSegment((int) $categoryId);
                    $categoriesForRedirect[$categoryId] = \count(array_intersect($aliases, $buildSegments));

                    if ($aliases == $buildSegments) {
                        $this->categoryId = (int) $categoryId;
                        $needRedirect     = false;
                        break;
                    }
                }
            }

            if (!$needRedirect && $this->shouldUseMainCategoryId()) {
                $mainCategoryId = (int) $product->getCategory();

                if ($mainCategoryId && $this->categoryId !== $mainCategoryId) {
                    $this->redirectToProduct($mainCategoryId, (int) $productId);

                    return;
                }
            }

            if ($needRedirect) {
                arsort($categoriesForRedirect);
                $categoryForRedirect = (int) key($categoriesForRedirect);

                if (!$categoryForRedirect) {
                    $categoryForRedirect = $this->getProductFallbackCategoryId($product, $listCategory);
                }

                $this->redirectToProduct($categoryForRedirect, (int) $productId);
            }

            $productSegment = array_pop($segments);
            $segments       = [end($segments), $productSegment];
        } elseif ($countSegments > 0) {
            $categoryAlias         = array_pop($aliases);
            $categoryIds           = array_keys($aliasCategory, $categoryAlias);
            $needRedirect          = true;
            $categoriesForRedirect = [];

            foreach ($categoryIds as $categoryId) {
                $buildSegments                      = self::buildSegment((int) $categoryId);
                $categoriesForRedirect[$categoryId] = \count(array_intersect($aliases, $buildSegments));

                if ($aliases == $buildSegments) {
                    $this->categoryId = (int) $categoryId;
                    $needRedirect     = false;
                    break;
                }
            }

            if (\count($categoryIds)) {
                if ($needRedirect) {
                    arsort($categoriesForRedirect);
                    $categoryForRedirect = (int) key($categoriesForRedirect);
                    $this->redirectToCategory($categoryForRedirect);
                }

                $segments = [end($segments)];
            }
        }
    }

    public function onAfterParseRoute(EventInterface $event): void
    {
        $arguments = $event->getArguments();

        if (!isset($arguments[0])) {
            return;
        }

        $vars = &$arguments[0];

        if (isset($vars['category_id']) && $this->categoryId) {
            $vars['category_id'] = $this->categoryId;
        }
    }

    private static function getCategoryList(): array
    {
        static $categoryList = null;

        if (!\is_array($categoryList)) {
            $allCategories = JSFactory::getTable('Category', 'jshop')->getAllCategories();
            $categoryList  = [];

            foreach ($allCategories as $row) {
                $categoryList[(int) $row->category_id] = (int) $row->category_parent_id;
            }
        }

        return $categoryList;
    }

    private static function buildSegment(int $categoryId): array
    {
        static $segments = [];

        if (!isset($segments[$categoryId])) {
            $categoryList  = self::getCategoryList();
            $aliasCategory = JSFactory::getAliasCategory();

            $segments[$categoryId] = [];
            $fullPath              = [];
            $currentCategoryId     = $categoryId;

            while (!empty($categoryList[$currentCategoryId])) {
                $currentCategoryId = (int) $categoryList[$currentCategoryId];
                $fullPath[]        = $currentCategoryId;
            }

            $fullPath = array_reverse($fullPath);

            foreach ($fullPath as $pathCategoryId) {
                if (isset($aliasCategory[$pathCategoryId])) {
                    $segments[$categoryId][] = $aliasCategory[$pathCategoryId];
                }
            }
        }

        return $segments[$categoryId];
    }

    private function isSupportedBuildQuery(array $query): bool
    {
        return isset($query['controller'])
            && ($query['controller'] === 'category' || $query['controller'] === 'product')
            && ($query['task'] ?? '') === 'view'
            && !empty($query['category_id'])
            && ($query['controller'] !== 'product' || !empty($query['product_id']));
    }

    private function resolveProductBuildCategoryId(int $productId, int $currentCategoryId): int
    {
        if (!$this->shouldUseMainCategoryId()) {
            return $currentCategoryId;
        }

        $product = JSFactory::getTable('Product', 'jshop');
        $product->load($productId);

        return (int) $product->getCategory() ?: $currentCategoryId;
    }

    private function shouldUseMainCategoryId(): bool
    {
        return !empty(JSFactory::getConfig()->product_use_main_category_id);
    }

    private function getProductFallbackCategoryId(object $product, array $listCategory): int
    {
        if ($this->shouldUseMainCategoryId()) {
            return (int) $product->getCategory();
        }

        return $this->getLegacyProductCategoryId((int) $product->product_id) ?: (int) reset($listCategory);
    }

    private function getLegacyProductCategoryId(int $productId): int
    {
        $db     = Factory::getContainer()->get(DatabaseInterface::class);
        $user   = $this->getApplicationSafe()->getIdentity();
        $levels = array_map('intval', $user->getAuthorisedViewLevels());
        $query  = $db->getQuery(true)
            ->select($db->quoteName('pr_cat.category_id'))
            ->from($db->quoteName('#__jshopping_products_to_categories', 'pr_cat'))
            ->leftJoin(
                $db->quoteName('#__jshopping_categories', 'cat')
                . ' ON ' . $db->quoteName('pr_cat.category_id') . ' = ' . $db->quoteName('cat.category_id')
            )
            ->where($db->quoteName('pr_cat.product_id') . ' = :product_id')
            ->where($db->quoteName('cat.category_publish') . ' = 1')
            ->whereIn($db->quoteName('cat.access'), $levels ?: [0])
            ->bind(':product_id', $productId, ParameterType::INTEGER);

        $db->setQuery($query);

        return (int) $db->loadResult();
    }

    private function redirectToProduct(int $categoryId, int $productId): void
    {
        $this->getApplicationSafe()->redirect(
            JoomShoppingHelper::SEFLink('index.php?option=com_jshopping&controller=product&task=view&category_id=' . $categoryId . '&product_id=' . $productId, 1),
            301
        );
    }

    private function redirectToCategory(int $categoryId): void
    {
        $this->getApplicationSafe()->redirect(
            JoomShoppingHelper::SEFLink('index.php?option=com_jshopping&controller=category&task=view&category_id=' . $categoryId, 1),
            301
        );
    }

    private function getApplicationSafe(): CMSApplicationInterface
    {
        return $this->getApplication() ?: Factory::getApplication();
    }
}
