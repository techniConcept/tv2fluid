<?php

namespace TC\Tv2fluid\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Torben Hansen <derhansen@gmail.com>, Skyfillers GmbH
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use FluidTYPO3\Fluidpages\Backend\BackendLayout;
use FluidTYPO3\Fluidpages\Backend\BackendLayoutDataProvider;
use FluidTYPO3\Fluidpages\Service\ConfigurationService;
use FluidTYPO3\Fluidpages\Service\PageService;
use FluidTYPO3\Flux\Utility\ExtensionNamingUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Helper class for handling TV content column migration to Fluid backend layouts
 */
class MigrateContentHelper implements SingletonInterface
{

    /**
     * @var SharedHelper
     */
    protected $sharedHelper;

    /**
     * @var array
     */
    protected $beLayoutsConfig = array();

    /**
     * DI for shared helper
     *
     * @param SharedHelper $sharedHelper
     * @return void
     */
    public function injectSharedHelper(SharedHelper $sharedHelper)
    {
        $this->sharedHelper = $sharedHelper;
    }

    /**
     * Returns an array of all TemplaVoila page templates stored as file
     *
     * @return array
     */
    public function getAllFileTvTemplates()
    {
        $extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['templavoila']);
        \tx_templavoila_staticds_tools::readStaticDsFilesIntoArray($extConf);
        $staticDsFiles = array();
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['templavoila']['staticDataStructures'] as $staticDataStructure) {
            if ($staticDataStructure['scope'] == \tx_templavoila_datastructure::SCOPE_PAGE) {
                $staticDsFiles[] = $staticDataStructure['path'];
            }
        }
        $quotedStaticDsFiles = $GLOBALS['TYPO3_DB']->fullQuoteArray($staticDsFiles, 'tx_templavoila_tmplobj');

        $fields = 'tx_templavoila_tmplobj.uid, tx_templavoila_tmplobj.title';
        $table = 'tx_templavoila_tmplobj';
        $where = 'tx_templavoila_tmplobj.datastructure IN(' . implode(',', $quotedStaticDsFiles) . ')
			AND tx_templavoila_tmplobj.deleted=0';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where, '', '', '');

        $templates = array();
        foreach ($res as $fce) {
            $templates[$fce['uid']] = $fce['title'];
        }

        return $templates;
    }

    /**
     * Returns an array of all TemplaVoila page templates stored in database
     *
     * @return array
     */
    public function getAllDbTvTemplates()
    {
        $fields = 'tx_templavoila_tmplobj.uid, tx_templavoila_tmplobj.title';
        $table = 'tx_templavoila_datastructure, tx_templavoila_tmplobj';
        $where = 'tx_templavoila_datastructure.scope=1 AND tx_templavoila_datastructure.uid = tx_templavoila_tmplobj.datastructure
			AND tx_templavoila_datastructure.deleted=0 AND tx_templavoila_tmplobj.deleted=0';

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($fields, $table, $where, '', '', '');

        $templates = array();
        foreach ($res as $fce) {
            $templates[$fce['uid']] = $fce['title'];
        }

        return $templates;
    }

    /**
     * Returns an array of all Grid Elements
     *
     * @return array
     */
    public function getAllBeLayouts()
    {

        /** @var BackendLayoutDataProvider $backendLayoutProvider */
        $backendLayoutProvider = GeneralUtility::makeInstance('FluidTYPO3\\Fluidpages\\Backend\\BackendLayoutDataProvider');

        /** @var PageService $pageService */
        $pageService = GeneralUtility::makeInstance('FluidTYPO3\\Fluidpages\\Service\\PageService');

        /** @var ConfigurationService $configurationService */
        $configurationService = GeneralUtility::makeInstance('FluidTYPO3\\Fluidpages\\Service\\ConfigurationService');

        $settings = $configurationService->getTypoScriptByPath('module.tx_tv2fluid.settings');
        $beLayouts = array();

        foreach ($pageService->getAvailablePageTemplateFiles() as $extension => $group) {
            $extensionName = ExtensionNamingUtility::getExtensionName($extension);

            foreach ($group as $layout) {
                $beLayouts[$extension . ':' . $layout] = $extension . ':' . $layout;
                $this->beLayoutsConfig[$extension . ':' . $layout] = $backendLayoutProvider->getBackendLayout($layout, $settings['layoutsPageUids'][$extensionName][$layout]);
            }
        }

        return $beLayouts;
    }

    /**
     * Returns an array with names of content columns for the given backend layout
     *
     * @param int $uidBeLayout
     * @return array
     */
    public function getBeLayoutContentCols($uidBeLayout)
    {
        /** @var \TYPO3\CMS\Backend\View\BackendLayout\BackendLayout $beLayoutRecord */
        $beLayoutRecord = $this->getBeLayout($uidBeLayout);
        return $this->sharedHelper->getContentColsFromTs($beLayoutRecord->getConfiguration());
    }

    /**
     * Returns the BE Layout record for the given BE Layout uid
     *
     * @param int $uid
     * @return array mixed
     */
    private function getBeLayout($uid)
    {
        return $this->beLayoutsConfig[$uid];
    }

    /**
     * Returns the uid of the DS for the given template
     *
     * @param int $uidTemplate
     * @return int
     */
    public function getTvDsUidForTemplate($uidTemplate)
    {
        $fields = 'datastructure';
        $table = 'tx_templavoila_tmplobj';
        $where = 'uid=' . (int)$uidTemplate;

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow($fields, $table, $where, '', '', '');
        return $res['datastructure'];
    }

    /**
     * Migrates templavoila flexform of page to db fields with the given pageUid to the selected column positions
     *
     * @param array $formdata
     * @param int $pageUid
     * @return int Number of Content elements updated
     */
    public function migrateTvFlexformForPage($formdata, $pageUid, $uidBeLayout)
    {
        $pageUid = (int)$pageUid;
        $localizationDiffSourceFields = array();
        $flexformConversionOption = $formdata['convertflexformoption'];
        $flexformFieldPrefix = $formdata['flexformfieldprefix'];
        $pageRecord = $this->sharedHelper->getPage($pageUid);
        $tvTemplateUid = (int)$this->sharedHelper->getTvPageTemplateUid($pageUid);
        $isTvDataLangDisabled = $this->sharedHelper->isTvDataLangDisabled($tvTemplateUid);
        $pageFlexformString = $pageRecord['tx_templavoila_flex'];

        if (!empty($pageFlexformString)) {
            $langIsoCodes = $this->sharedHelper->getLanguagesIsoCodes();
            $allAvailableLanguages = $this->sharedHelper->getAvailablePageTranslations($pageUid);
            if (empty($allAvailableLanguages)) {
                $allAvailableLanguages = array();
            }
            array_unshift($allAvailableLanguages, 0);

            foreach ($allAvailableLanguages as $langUid) {
                $flexformString = $pageFlexformString;
                $langUid = (int)$langUid;
                if (($flexformConversionOption !== 'exclude')) {
                    if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
                        if ($langUid > 0) {
                            $forceLanguage = ($flexformConversionOption === 'forceLanguage');
                            if (!$isTvDataLangDisabled) {
                                $flexformString = $this->sharedHelper->convertFlexformForTranslation($flexformString, $langIsoCodes[$langUid], $forceLanguage);
                            }
                        }
                    }
                }

                list($flexformString, $children) = $this->sharedHelper->cleanFlexform($flexformString, $tvTemplateUid);

                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid=' . intval($pageUid),
                    array(
                        'tx_fed_page_flexform' => $flexformString
                    )
                );
            }
        }
        $this->sharedHelper->fixPageLocalizationDiffSources($pageUid, $localizationDiffSourceFields);
    }

    /**
     * Migrates all content elements for the page with the given pageUid to the selected column positions
     *
     * @param array $formdata
     * @param int $pageUid
     * @return int Number of Content elements updated
     */
    public function migrateContentForPage($formdata, $pageUid)
    {
        $fieldMapping = $this->sharedHelper->getFieldMappingArray($formdata, 'tv_col_', 'be_col_');
        $tvContentArray = $this->sharedHelper->getTvContentArrayForPage($pageUid);

        $count = 0;
        $sorting = 0;
        foreach ($tvContentArray as $key => $contentUidString) {
            if (array_key_exists($key, $fieldMapping) && $contentUidString != '') {
                $contentUids = explode(',', $contentUidString);
                foreach ($contentUids as $contentUid) {
                    $contentElement = $this->sharedHelper->getContentElement($contentUid);
                    if ($contentElement['pid'] == $pageUid) {
                        $this->sharedHelper->updateContentElementColPos($contentUid, $fieldMapping[$key], $sorting);
                        $this->sharedHelper->fixContentElementLocalizationDiffSources($contentUid);
                    }
                    $sorting += 25;
                    $count++;
                }
            }
        }
        return $count;
    }

    /**
     * Marks the TemplaVoila Template with the given uid as deleted
     *
     * @param int $uidTvTemplate
     * @return void
     */
    public function markTvTemplateDeleted($uidTvTemplate)
    {
        $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_templavoila_tmplobj', 'uid=' . intval($uidTvTemplate),
            array('deleted' => 1)
        );
    }

    /**
     * Sets the backend layout uid for the page with the given uid if the value of the TV template matches
     * the uid of the given uidTvTemplate
     *
     * @param int $pageUid
     * @param int $UidTvTemplate
     * @param int $uidBeLayout
     * @return int Number of page templates updated
     */
    public function updatePageTemplate($pageUid, $UidTvTemplate, $uidBeLayout)
    {
        $pageRecord = $this->sharedHelper->getPage($pageUid);
        $updateFields = array();
        $count = 0;
        if ($pageRecord['tx_templavoila_to'] > 0 && $pageRecord['tx_templavoila_to'] == $UidTvTemplate) {
            $updateFields['tx_fed_page_controller_action'] = str_replace(':', '->', $uidBeLayout);
        }
        if ($pageRecord['tx_templavoila_next_to'] > 0 && $pageRecord['tx_templavoila_next_to'] == $UidTvTemplate) {
            $updateFields['tx_fed_page_controller_action_sub'] = str_replace(':', '->', $uidBeLayout);
        }
        if (count($updateFields) > 0) {
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('pages', 'uid=' . intval($pageUid), $updateFields);
            $count++;
        }
        return $count;
    }

}

?>