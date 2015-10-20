<?php
namespace Fab\VidiFrontend\Domain\Validator;

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

use Fab\VidiFrontend\Configuration\ContentElementConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validate "matches" to be used to filter the repository.
 */
class MatchesValidator extends AbstractValidator {

	/**
	 * Check if $matches is valid. If it is not valid, throw an exception.
	 *
	 * @param mixed $matches
	 * @return void
	 */
	public function isValid($matches) {
		$columnNames = ContentElementConfiguration::getInstance()->getColumnsNames();

		foreach ($matches as $fieldName => $value) {
			if (!in_array($fieldName, $columnNames)) {
				$message = sprintf('Field "%s" is not allowed. Actually, it is not configured in the TCA.', $fieldName);
				$this->addError($message, 1380019718);
			}
		}
	}
}
