Tv2Fluid
========

Based on the extension **sf_tv2fluidge** (https://github.com/derhansen/sf_tv2fluidge) from Torben Hansen. Thanks to him for the initial work.


HOW TO
------

* Contrôler/Corriger la première page pour avoir un héritage sur les pages enfants de templavoila
* Lancer les cleanups avec fluidpages, fluidcontent, flux désactivés
* Créer des pages avec les différents templates fluid
* Ajouter la config TS pour la relation entre les pages créées et les templates. Exemple :
    ```
    module.tx_tv2fluid {
         settings {
             layoutsPageUids {
                 EbsSkin {
                     Home = 1673
                     Index = 1674
                     Index2Cols = 1675
                 }
             }
         }
     }
    ```
* Mettre les backend layouts à fluidpages
    ```
    UPDATE pages
    SET backend_layout='fluidpages__fluidpage',
        backend_layout_next_level='fluidpages__fluidpages'
    WHERE uid=<pageUid>;
    ```
* Migrer les FCEs
* Migrer les pages
