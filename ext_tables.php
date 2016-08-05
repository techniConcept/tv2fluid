<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile($_EXTKEY, 'Configuration/TypoScript', 'TemplaVoila Toolbox');

if (TYPO3_MODE === 'BE') {

    /**
     * Registers a Backend Module
     */
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'TC.' . $_EXTKEY,
        'web', // Make module a submodule of 'web'
        'tv2fluid', // Submodule key
        '', // Position
        array(
            'Tv2fluid' => 'index,indexDeleteUnreferencedElements,deleteUnreferencedElements,indexConvertReferenceElements,convertReferenceElements,indexMigrateFce,migrateFce,indexMigrateContent,migrateContent,indexConvertMultilangContent,convertMultilangContent,indexFixSorting,fixSorting',
        ),
        array(
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml',
        )
    );

}
