<?php

$productEntityId = $this->getEntityTypeId('catalog_product');

$this
  ->updateAttribute($productEntityId,
                    'dynamic_imaging',
                    'frontend_label',
                    'Dynamic imaging (no PDF output)');

$categoryEntityId = $this->getEntityTypeId('catalog_category');
$attributeId = $this->getAttributeId($categoryEntityId, 'dynamic_imaging');

$this
  ->updateAttribute($categoryEntityId,
                    $attributeId,
                    'frontend_label',
                    'Dynamic imaging (no PDF output)');

$attributeSetId = $this->getDefaultAttributeSetId($categoryEntityId);
$groupId = $this->getAttributeGroupId($categoryEntityId,
                                      $attributeSetId,
                                      'General Information');

$this
  ->addAttributeToGroup($categoryEntityId,
                        $attributeSetId,
                        $groupId,
                        $attributeId,
                        100);
