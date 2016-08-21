<?php

########################################################################
# Extension Manager/Repository config file for ext: "tv2fluid"
#
# Auto generated by Extension Builder 2013-10-21
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
    'title' => 'TemplaVoila to Fluidpages/Fluidcontent',
    'description' => 'Backend module with tools that can be helpful when migration from Templavoila to Fluidpages and Fluidcontent',
    'category' => 'module',
    'author' => 'Sébastien Rüegg',
    'author_email' => 'sebastien.ruegg@techniconcept.ch',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'beta',
    'internal' => '',
    'uploadfolder' => '0',
    'createDirs' => '',
    'modify_tables' => '',
    'clearCacheOnLoad' => 0,
    'lockType' => '',
    'version' => '0.0.1',
    'constraints' => array(
        'depends' => array(
            'typo3' => '6.2.*',
            'templavoila' => '1.9.*',
            'flux' => '7.2.3-0.0.0',
            'fluidpages' => '3.3.1-0.0.0',
            'fluidcontent' => '4.3.3-0.0.0',
        ),
        'conflicts' => array(),
        'suggests' => array(),
    ),
);

?>