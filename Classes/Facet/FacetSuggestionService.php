<?php
namespace Fab\VidiFrontend\Facet;

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
use Fab\Vidi\Domain\Repository\ContentRepositoryFactory;
use Fab\Vidi\Persistence\Matcher;
use Fab\Vidi\Persistence\MatcherObjectFactory;
use Fab\Vidi\Tca\Tca;

/**
 * Class for configuring a custom Facet item.
 */
class FacetSuggestionService {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var array
	 */
	protected $dataType;

	/**
	 * Constructor
	 *
	 * @param array $settings
	 * @param string $dataType
	 */
	public function __construct(array $settings, $dataType = '') {
		$this->settings = $settings;
		$this->dataType = $dataType;
	}

	/**
	 * Retrieve possible suggestions for a field name
	 *
	 * @param string $fieldNameAndPath
	 * @return array
	 */
	public function getSuggestions($fieldNameAndPath) {

		$values = array();

		$dataType = $this->getFieldPathResolver()->getDataType($fieldNameAndPath, $this->dataType);
		$fieldName = $this->getFieldPathResolver()->stripFieldPath($fieldNameAndPath, $this->dataType);

		if (FrontendTca::grid($this->dataType)->facet($fieldNameAndPath)->hasSuggestions()) {
			$values = FrontendTca::grid($this->dataType)->facet($fieldNameAndPath)->getSuggestions();
		} else if (Tca::table($dataType)->hasField($fieldName)) {

			if (Tca::table($dataType)->field($fieldName)->hasRelation()) {

				// Fetch the adequate repository
				$foreignTable = Tca::table($dataType)->field($fieldName)->getForeignTable();
				$contentRepository = ContentRepositoryFactory::getInstance($foreignTable);
				$table = Tca::table($foreignTable);

				// Initialize the matcher object.
				$matcher = MatcherObjectFactory::getInstance()->getMatcher(array(), $foreignTable);

				$numberOfValues = $contentRepository->countBy($matcher);
				if ($numberOfValues <= $this->getLimit()) {

					$contents = $contentRepository->findBy($matcher);

					foreach ($contents as $content) {
						$values[] = array($content->getUid() => $content[$table->getLabelField()]);
					}
				}
			} elseif (!Tca::table($dataType)->field($fieldName)->isTextArea()) { // We don't want suggestion if field is text area.

				// Fetch the adequate repository
				/** @var \Fab\Vidi\Domain\Repository\ContentRepository $contentRepository */
				$contentRepository = ContentRepositoryFactory::getInstance($this->dataType);

				/** @var $matcher Matcher */
				$matcher = GeneralUtility::makeInstance('Fab\Vidi\Persistence\Matcher', array(), $dataType);

				// Count the number of objects.
				$numberOfValues = $contentRepository->countDistinctValues($fieldName, $matcher);

				// Only returns suggestion if there are not too many for the browser.
				if ($numberOfValues <= $this->getLimit()) {

					// Query the repository.
					$contents = $contentRepository->findDistinctValues($fieldName, $matcher);

					foreach ($contents as $content) {
						$value = $content[$fieldName];
						$label = $content[$fieldName];
						if (Tca::table($dataType)->field($fieldName)->isSelect()) {
							$label = Tca::table($dataType)->field($fieldName)->getLabelForItem($value);
						}

						$values[] = $label;
					}
				}
			}
		}
		return $values;
	}

	/**
	 * Return from settings the suggestion limit.
	 *
	 * @return int
	 */
	protected function getLimit() {
		$suggestionLimit = (int)$this->settings['suggestionLimit'];
		if ($suggestionLimit <= 0) {
			$suggestionLimit = 1000;
		}
		return $suggestionLimit;
	}

	/**
	 * @return \Fab\Vidi\Resolver\FieldPathResolver
	 */
	protected function getFieldPathResolver() {
		return GeneralUtility::makeInstance('Fab\Vidi\Resolver\FieldPathResolver');
	}
}
