<?php
namespace Fab\VidiFrontend\ViewHelpers\Grid;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Fab\VidiFrontend\Tca\FrontendTca;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * View helper which returns suggestion for the Visual Search bar.
 */
class SuggestionsViewHelper extends AbstractViewHelper
{

    /**
     * Returns the json serialization of the search fields.
     *
     * @return boolean
     */
    public function render()
    {
        $settings = $this->templateVariableContainer->get('settings');

        $suggestions = array();
        $facets = GeneralUtility::trimExplode(',', $settings['facets'], TRUE);
        foreach ($facets as $facetName) {
            $suggestions[$facetName] = $this->getFacetSuggestionService()->getSuggestions($facetName);
        }

        return json_encode($suggestions);
    }

    /**
     * @return \Fab\VidiFrontend\Facet\FacetSuggestionService
     */
    protected function getFacetSuggestionService()
    {
        $settings = array();
        $dataType = $this->templateVariableContainer->get('dataType');
        return GeneralUtility::makeInstance('Fab\VidiFrontend\Facet\FacetSuggestionService', $settings, $dataType);
    }

}
