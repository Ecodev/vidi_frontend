<?php
namespace Fab\VidiFrontend\Service;

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

use Fab\Vidi\Persistence\QuerySettings;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Fab\Vidi\Domain\Repository\ContentRepositoryFactory;
use Fab\Vidi\Persistence\Matcher;
use Fab\Vidi\Persistence\Order;
use Fab\Vidi\Signal\AfterFindContentObjectsSignalArguments;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Service related to the Content.
 */
class ContentService
{

    /**
     * @var string
     */
    protected $dataType;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var \Fab\Vidi\Domain\Model\Content[]
     */
    protected $objects = [];

    /**
     * @var int
     */
    protected $numberOfObjects = 0;

    /**
     * Constructor
     *
     * @param string $dataType
     * @return ContentService
     */
    public function __construct($dataType = '')
    {
        $this->dataType = $dataType;
    }

    /**
     * Fetch the files given an object assuming
     *
     * @param Matcher $matcher
     * @param Order $order The order
     * @param int $limit
     * @param int $offset
     * @return $this
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
     */
    public function findBy(Matcher $matcher, Order $order = null, $limit = null, $offset = null)
    {

        // Query the repository.
        $contentRepository = ContentRepositoryFactory::getInstance($this->dataType);

        /** @var QuerySettings $querySettings */
        if (!empty($this->settings['enableFields'])) {

            $ignoreFields = GeneralUtility::trimExplode(',', $this->settings['enableFields'], true);
            $querySettings = GeneralUtility::makeInstance(QuerySettings::class);
            $querySettings->setEnableFieldsToBeIgnored($ignoreFields);
            $querySettings->setIgnoreEnableFields(true);
            $contentRepository->setDefaultQuerySettings($querySettings);
        }

        $objects = $contentRepository->findBy($matcher, $order, $limit, $offset);
        $signalResult = $this->emitAfterFindContentObjectsSignal($objects, $matcher, $limit, $offset);

        // Reset objects variable after possible signal / slot processing.
        $this->objects = $signalResult->getContentObjects();

        // Count number of content objects.
        if ($signalResult->getHasBeenProcessed()) {
            $this->numberOfObjects = $signalResult->getNumberOfObjects();
        } else {
            $this->numberOfObjects = ContentRepositoryFactory::getInstance($this->dataType)->countBy($matcher);
        }

        // reset default query settings as repository is a singleton.
        $contentRepository->resetDefaultQuerySettings();
        return $this;
    }

    /**
     * Signal that is called after the content objects have been found.
     *
     * @param array $contentObjects
     * @param \Fab\Vidi\Persistence\Matcher $matcher
     * @param int $limit
     * @param int $offset
     * @return AfterFindContentObjectsSignalArguments
     * @signal
     */
    protected function emitAfterFindContentObjectsSignal($contentObjects, Matcher $matcher, $limit = 0, $offset = 0)
    {

        /** @var AfterFindContentObjectsSignalArguments $signalArguments */
        $signalArguments = GeneralUtility::makeInstance(AfterFindContentObjectsSignalArguments::class);
        $signalArguments->setDataType($this->dataType)
            ->setContentObjects($contentObjects)
            ->setMatcher($matcher)
            ->setLimit($limit)
            ->setOffset($offset)
            ->setHasBeenProcessed(FALSE);

        $signalResult = $this->getSignalSlotDispatcher()->dispatch(ContentService::class, 'afterFindContentObjects', array($signalArguments));
        return $signalResult[0];
    }

    /**
     * Get the SignalSlot dispatcher.
     *
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {

        /** @var ObjectManager $objectManager */
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        return $objectManager->get(Dispatcher::class);
    }

    /**
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * @return \Fab\Vidi\Domain\Model\Content[]
     */
    public function getObjects()
    {
        return $this->objects;
    }

    /**
     * @return int
     */
    public function getNumberOfObjects()
    {
        return $this->numberOfObjects;
    }

    /**
     * @param string $dataType
     * @return $this
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
        return $this;
    }

    /**
     * @param array $settings
     * @return $this
     */
    public function setSettings($settings)
    {
        $this->settings = $settings;
        return $this;
    }

}
