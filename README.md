Tv2Fluid
========
Backend module with tools that can be helpful when migration from TemplaVoila to FluidPages and FluidContent.

Based on the extension **sf_tv2fluidge** (https://github.com/derhansen/sf_tv2fluidge) from Torben Hansen. Thanks to him for the initial work.

Provided "as is". Feel free to contribute.

Requirements
------------

* Having a full TemplaVoila running website
* Having FluidPages templates corresponding to TemplaVoila page templates
* Having FluidContent element corresponding to TemplaVoila FCEs
* Best practices are to have the same fields names on each side for easy FlexForm migration

How to use it
-------------

* BACKUP your database!
* Install the extension (and dependencies)
* Check on every root pages that TemplaVoila templates are set for current page and subpages
* Temporarly disable Flux, FluidPages and FluidContent (there is a conflict with TemplaVoila on cleanup)
* Run the cleanups tasks:
    * Delete unreferenced elements
    * Convert reference elements to 'insert records' elements
    * Delete unreferenced elements (yes, once again!)
* Enable again Flux, FluidPages and FluidContent
* Create temporary pages, one for each page template
    * Set the corresponding FluidPages layout
* Add TS config to your first root page for each relation between created pages and templates. Example:
    ```
    module.tx_tv2fluid {
         settings {
             layoutsPageUids {
                 <CamelCaseExtensionName-ex:MySkin> {
                     <TemplateFilenameWithoutExt-ex:Default> = <pid>
                     ...
                 }
             }
         }
     }
    ```
* Set backend layout on root pages to FluidPage
    ```
    UPDATE pages
    SET backend_layout='fluidpages__fluidpages',
        backend_layout_next_level='fluidpages__fluidpages'
    WHERE uid=<pageUid>;
    ```
* Migrate TV FCE content to Fluidcontent
* Migrate content from TemplaVoila to Fluidpages
* Disable TemplaVoila
* Check your website!
