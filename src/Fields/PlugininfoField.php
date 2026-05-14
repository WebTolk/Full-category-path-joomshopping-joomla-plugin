<?php

/**
 * Plugin information field for the JoomShopping full category path router plugin.
 * @package       Jshoppingrouter - Full Category Path
 * @subpackage    plg_jshoppingrouter_full_category_path
 * @author        Sergey Tolkachyov
 * @copyright     Copyright (c) 2022 - 2026 Sergey Tolkachyov. All rights reserved.
 * @version       __DEPLOY_VERSION__
 * @license       GNU General Public License version 3 or later.
 * @link          https://web-tolk.ru
 */

namespace Joomla\Plugin\Jshoppingrouter\FullCategoryPath\Fields;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\NoteField;
use Joomla\CMS\Language\Text;

class PlugininfoField extends NoteField
{
    protected $type = 'Plugininfo';

    protected function getInput(): string
    {
        $data    = $this->form->getData();
        $element = $data->get('element');
        $folder  = $data->get('folder');
        $wa      = Factory::getApplication()->getDocument()->getWebAssetManager();

        $wa->addInlineStyle("
			.plugin-info-img-svg:hover * {
				cursor:pointer;
			}
		");

        $manifest = simplexml_load_file(JPATH_SITE . '/plugins/' . $folder . '/' . $element . '/' . $element . '.xml');

        return '</div><div class="d-flex shadow p-4">
			<div class="flex-shrink-0">
				<a href="https://web-tolk.ru" target="_blank" rel="noopener noreferrer">
					<svg class="plugin-info-img-svg" width="200" height="50" xmlns="http://www.w3.org/2000/svg">
						<g>
							<title>Go to https://web-tolk.ru</title>
							<text font-weight="bold" xml:space="preserve" text-anchor="start"
							      font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_3" y="36.085949"
							      x="8.152073" stroke-opacity="null" stroke-width="0" stroke="#000"
							      fill="#0fa2e6">Web</text>
							<text font-weight="bold" xml:space="preserve" text-anchor="start"
							      font-family="Helvetica, Arial, sans-serif" font-size="32" id="svg_4" y="36.081862"
							      x="74.239105" stroke-opacity="null" stroke-width="0" stroke="#000"
							      fill="#384148">Tolk</text>
						</g>
					</svg>
				</a>
			</div>
			<div class="flex-grow-1 ms-3">
				<span class="badge bg-success text-white">v.' . $manifest->version . '</span>
				' . Text::_('PLG_' . strtoupper((string) $element) . '_DESC') . '
			</div>
		</div><div>';
    }

    protected function getLabel(): string
    {
        return ' ';
    }

    protected function getTitle(): string
    {
        return $this->getLabel();
    }
}
