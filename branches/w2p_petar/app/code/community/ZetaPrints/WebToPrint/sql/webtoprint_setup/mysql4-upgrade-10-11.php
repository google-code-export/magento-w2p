<?php

  $data = array(
    'severity' => Mage_AdminNotification_Model_Inbox::SEVERITY_NOTICE,
    'date_added' => gmdate('Y-m-d H:i:s', mktime(10, 0, 0, 6, 9, 2011)),
    'title' => 'New Zetaprints Web to Print setting added',
    'description' => 'A change is made to how product creation profile works. Now created products can be populated with some default values and enabled for you.
    Enabling this feature is controlled by new ZetaPrints Web-to-print setting - "Set Defaults For Created Products".',
    'url' => '#' . session_id(), // url is supposed to be unique, if url is found in notifications table, record is skipped. Ideally here would be release notes link.
  );

  Mage::getModel('adminnotification/inbox')->parse(array($data));
