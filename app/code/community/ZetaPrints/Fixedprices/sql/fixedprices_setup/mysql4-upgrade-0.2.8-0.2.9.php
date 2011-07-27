<<<<<<< .mine
<?php
$profile_name = 'ZetaPrints Fixed Quantities Export';

$profile_model = Mage::getModel('dataflow/profile');

if ($profile_model->getResource()->isProfileExists($profile_name)) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile_name);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
}

$actionXml = '
<action type="fixedprices/convert_adapter_fixedprices" method="load">
    <var name="store"><![CDATA[0]]></var>
</action>

<action type="fixedprices/convert_parser_product" method="unparse">
    <var name="store"><![CDATA[0]]></var>
</action>

<action type="dataflow/convert_mapper_column" method="map">
</action>

<action type="dataflow/convert_parser_csv" method="unparse">
    <var name="delimiter"><![CDATA[,]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
</action>

<action type="dataflow/convert_adapter_io" method="save">
    <var name="type">file</var>
    <var name="path">var/export</var>
    <var name="filename"><![CDATA[fixed_qtys_data.csv]]></var>
</action>
';

$profile_model->setName($profile_name)
  ->setActionsXml($actionXml)
  ->setGuiData(false)
  ->setDataTransfer('interactive')
  ->save();
=======
<?php
$profile_name = 'ZetaPrints Fixed Quantities Export';

$profile_model = Mage::getModel('dataflow/profile');

if ($profile_model->getResource()->isProfileExists($profile_name)) {
    $collection = $profile_model->getCollection();
    $collection->getSelect()->where('name = ?', $profile_name);

    if ($collection->count() == 1)
      $profile_model = $collection->getFirstItem();
}

$actionXml = '
<action type="fixedprices/convert_adapter_fixedprices" method="load">
    <var name="store"><![CDATA[0]]></var>
</action>

<action type="fixedprices/convert_parser_product" method="unparse">
    <var name="store"><![CDATA[0]]></var>
</action>

<action type="dataflow/convert_mapper_column" method="map">
</action>

<action type="dataflow/convert_parser_csv" method="unparse">
    <var name="delimiter"><![CDATA[,]]></var>
    <var name="enclose"><![CDATA["]]></var>
    <var name="fieldnames">true</var>
</action>

<action type="dataflow/convert_adapter_io" method="save">
    <var name="type">file</var>
    <var name="path">var/export</var>
    <var name="filename"><![CDATA[fixed_qtys_data.csv]]></var>
</action>
';

$profile_model->setName($profile_name)
  ->setActionsXml($actionXml)
  ->setGuiData(false)
  ->setDataTransfer('interactive')
  ->save();
>>>>>>> .r1756
