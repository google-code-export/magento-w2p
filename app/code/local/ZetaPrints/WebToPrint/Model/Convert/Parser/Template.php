<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Api/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_Model_Convert_Parser_Template extends  Mage_Dataflow_Model_Convert_Parser_Abstract {

  public function parse() {
    $url = Mage::getStoreConfig('api/settings/w2p_url');
    $key = Mage::getStoreConfig('api/settings/w2p_key');

    $this->debug = (bool)Mage::getStoreConfig('api/settings/w2p_debug');

    $refresh_templates = (bool)(int)Mage::getStoreConfig('api/settings/w2p_refresh');
    if ($refresh_templates)
      $this->notice('Refresh all templates');

    $catalogs = zetaprints_get_list_of_catalogs($url, $key);

    foreach ($catalogs as $catalog) {
      $templates = zetaprints_get_templates_from_catalog($url, $key, $catalog['guid']);

      foreach ($templates as $template) {
        $template['public'] = $catalog['public'];
        $templates_collection = Mage::getModel('webtoprint/template')
                                  ->getCollection()
                                  ->get_by_guid($template['guid'])
                                  ->load();

        if ($templates_collection->getSize() == 1)
          foreach ($templates_collection as $template_model)
            if (strtotime($template['date']) > strtotime($template_model->getDate())
             || $refresh_templates
             || (int)$template_model->getPublic() != $template['public']) {
              $this->debug("Template {$template['guid']} is outdated");

              if ($refresh_templates)
                $template['xml'] = zetaprints_get_template_details_as_xml($url, $key, $template['guid']);

              $template_model->addData($template)->save();
            }
            else
              $this->debug("Template {$template['guid']} is up to date");
        else {
          $template['xml'] = zetaprints_get_template_details_as_xml($url, $key, $template['guid']);
          $template_model = Mage::getModel('webtoprint/template');
          $template_model->addData($template)->save();
        }
      }
    }
  }

  public function unparse() {}

  private function notice ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::NOTICE);
  }

  private function debug ($message) {
    if ($this->debug)
      $this->notice($message);
  }
}
