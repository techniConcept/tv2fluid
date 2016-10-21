<?php
namespace TC\Tv2fluid\Command;

use \TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class Tv2fluidCommandController extends CommandController
{
    /**
     * @var \TC\Tv2fluid\Service\SharedHelper
     * @inject
     */
    protected $sharedHelper;

    /**
     * @var \TC\Tv2fluid\Service\UnreferencedElementHelper
     * @inject
     */
    protected $unreferencedElementHelper;

    /**
     * @var \TC\Tv2fluid\Service\ReferenceElementHelper
     * @inject
     */
    protected $referenceElementHelper;

    /**
     * @var \TC\Tv2fluid\Service\MigrateFceHelper
     * @inject
     */
    protected $migrateFceHelper;

    /**
     * @var \TC\Tv2fluid\Service\MigrateContentHelper
     * @inject
     */
    protected $migrateContentHelper;

    /**
     * @var \TC\Tv2fluid\Service\FixSortingHelper
     */
    protected $fixSortingHelper;

    private function getConfigFromJson($file)
    {
        $config = array();
        $configJson = file_get_contents($file);
        if ($configJson !== false) {
            $config = json_decode($configJson, true);
        }
        return $config;
    }

    /**
     * Sets all unreferenced Elements to deleted
     *
     * @param int $markasnegativecolpos
     */
    public function deleteUnreferencedElementsCommand($markasnegativecolpos = 0)
    {
        $this->sharedHelper->setUnlimitedTimeout();
        $numRecords = $this->unreferencedElementHelper->markDeletedUnreferencedElementsRecords((intval($markasnegativecolpos) === 1));
        $this->outputLine('records affected: ' . $numRecords);
    }

    /**
     * Migrates all reference elements to 'insert records' elements
     *
     * @param string $useparentuidfortranslations
     * @param string $usealllangifdefaultlangisreferenced
     */
    // TODO: method is not working on command line. Something wrong in Templavoilà API?
//    public function convertReferenceElementsCommand($useparentuidfortranslations = '', $usealllangifdefaultlangisreferenced = '')
//    {
//        $this->sharedHelper->setUnlimitedTimeout();
//        $this->referenceElementHelper->initFormData(array(
//            'useparentuidfortranslations' => $useparentuidfortranslations,
//            'usealllangifdefaultlangisreferenced' => $usealllangifdefaultlangisreferenced
//        ));
//        $numRecords = $this->referenceElementHelper->convertReferenceElements();
//        $this->outputLine('records affected: ' . $numRecords);
//    }

    /**
     * Migrates content from FCE to Grid Element
     *
     * @param string $file JSON config file
     */
    public function migrateFceCommand($file)
    {
        $this->sharedHelper->setUnlimitedTimeout();

        $migrationConfigurationFCE = $this->getConfigFromJson($file);

        foreach ($migrationConfigurationFCE as $formdata) {
            $fce = $formdata['fce'];
            $ge = $formdata['ge'];

            $fcesConverted = 0;
            $contentElementsUpdated = 0;

            if ($fce > 0 && !empty($ge)) {
                $contentElements = $this->migrateFceHelper->getContentElementsByFce($fce);
                foreach ($contentElements as $contentElement) {
                    $fcesConverted++;
                    $this->migrateFceHelper->migrateFceFlexformContentToGe($contentElement, $ge);

                    // Migrate content to GridElement columns (if available)
                    $contentElementsUpdated += $this->migrateFceHelper->migrateContentElementsForFce($contentElement, $formdata);
                }
                if ($formdata['markdeleted']) {
                    $this->migrateFceHelper->markFceDeleted($fce);
                }
            }
            $this->outputLine('FCE: ' . $ge);
            $this->outputLine('content elements updated: ' . $contentElementsUpdated);
            $this->outputLine('FCEs converted: ' . $fcesConverted);
            $this->outputLine('--- --- --- --- --- --- --- ---');
        }
    }

    /**
     * Does the content migration recursive for all pages
     *
     * @param string $file JSON config file
     */
    public function migrateContentCommand($file)
    {
        $this->sharedHelper->setUnlimitedTimeout();

        $migrationConfigurationPage = $this->getConfigFromJson($file);

        foreach ($migrationConfigurationPage as $formdata) {
            $uidTvTemplate = (int)$formdata['tvtemplate'];
            $uidBeLayout = $formdata['belayout'];

            $contentElementsUpdated = 0;
            $pageTemplatesUpdated = 0;

            if ($uidTvTemplate > 0 && !empty($uidBeLayout)) {
                $pageUids = $this->sharedHelper->getPageIds();

                foreach ($pageUids as $pageUid) {
                    if ($this->sharedHelper->getTvPageTemplateUid($pageUid) == $uidTvTemplate) {
                        $contentElementsUpdated += $this->migrateContentHelper->migrateContentForPage($formdata, $pageUid);
                        $this->migrateContentHelper->migrateTvFlexformForPage($formdata, $pageUid, $uidBeLayout);
                    }

                    // Update page template (must be called for every page, since to and next_to must be checked
                    $pageTemplatesUpdated += $this->migrateContentHelper->updatePageTemplate($pageUid, $uidTvTemplate, $uidBeLayout);
                }

                if ($formdata['markdeleted']) {
                    $this->migrateContentHelper->markTvTemplateDeleted($uidTvTemplate);
                }
            }
            $this->outputLine('Page template: ' . $uidBeLayout);
            $this->outputLine('content elements updated: ' . $contentElementsUpdated);
            $this->outputLine('Page templates converted: ' . $pageTemplatesUpdated);
            $this->outputLine('--- --- --- --- --- --- --- ---');

        }
    }

    /**
     * Fix content + translations sorting
     *
     * @param string $fixOptions singlePage or allPages
     * @param int $pageUid mandatory if singlePage
     */
    // TODO: method is not working on command line. Something wrong in Templavoilà API?
//    public function fixSortingCommand($fixOptions = 'allPages', $pageUid = 0)
//    {
//        $this->sharedHelper->setUnlimitedTimeout();
//
//        $numUpdated = 0;
//        if ($fixOptions == 'singlePage') {
//            $numUpdated = $this->fixSortingHelper->fixSortingForPage($pageUid);
//        } else if ($fixOptions == 'allPages') {
//            $pageUids = $this->sharedHelper->getPageIds();
//            foreach ($pageUids as $pageUid) {
//                $numUpdated += $this->fixSortingHelper->fixSortingForPage($pageUid);
//            }
//        }
//        $this->outputLine('records affected: ' . $numUpdated);
//    }
}