<<<<<<< .mine
<?php

$profile_name = 'ZetaPrints Fixed Quantities Import';

$profile_model = Mage::getModel('dataflow/profile');

if ($profile_model->getResource()->isProfileExists($profile_name)) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile_name);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
}

$actionXml = '
<action type="dataflow/convert_adapter_io" method="load">
    <var name="type">file</var>
    <var name="path">var/import</var>
    <var name="filename"><![CDATA[fixed_qtys_data.csv]]></var>
    <var name="format"><![CDATA[csv]]></var>
</action>
<action type="dataflow/convert_parser_csv" method="parse">
    <var name="delimiter"><![CDATA[,]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
    <var name="store"><![CDATA[0]]></var>
    <var name="number_of_records">1</var>
    <var name="decimal_separator"><![CDATA[.]]></var>
    <var name="adapter">fixedprices/convert_adapter_fixedprices</var>
    <var name="method">saveRow</var>
</action>
';

$profile_model->setName($profile_name)
  ->setActionsXml($actionXml)
  ->setGuiData(false)
  ->setDataTransfer('interactive')
  ->save();
=======
<?php

$profile_name = 'ZetaPrints Fixed Quantities Import';

$profile_model = Mage::getModel('dataflow/profile');

if ($profile_model->getResource()->isProfileExists($profile_name)) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile_name);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
}

$actionXml = '
<action type="dataflow/convert_adapter_io" method="load">
    <var name="type">file</var>
    <var name="path">var/import</var>
    <var name="filename"><![CDATA[fixed_qtys_data.csv]]></var>
    <var name="format"><![CDATA[csv]]></var>
</action>
<action type="dataflow/convert_parser_csv" method="parse">
    <var name="delimiter"><![CDATA[,]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
    <var name="store"><![CDATA[0]]></var>
    <var name="number_of_records">1</var>
    <var name="decimal_separator"><![CDATA[.]]></var>
    <var name="adapter">fixedprices/convert_adapter_fixedprices</var>
    <var name="method">saveRow</var>
</action>
';

$profile_model->setName($profile_name)
  ->setActionsXml($actionXml)
  ->setGuiData(false)
  ->setDataTransfer('interactive')
  ->save();
>>>>>>> .r1756
