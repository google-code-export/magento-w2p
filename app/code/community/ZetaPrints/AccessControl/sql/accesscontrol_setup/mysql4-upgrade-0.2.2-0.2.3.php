<?php

$this->startSetup();

$this->updateAttribute('catalog_product', 'groupscatalog_hide_group', 'is_filterable_in_search', '0');

$this->endSetup();