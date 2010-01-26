<?php

if (!defined('ZP_API_VER')) {
  $zetaprints_api_file = Mage::getRoot().'/code/local/ZetaPrints/Zpapi/Model/zp_api.php';

  if (file_exists($zetaprints_api_file))
    require $zetaprints_api_file;
}

class ZetaPrints_WebToPrint_Model_Convert_Parser_Template extends  Mage_Dataflow_Model_Convert_Parser_Abstract {

  public function parse() {
    $url = Mage::getStoreConfig('zpapi/settings/w2p_url');
    $key = Mage::getStoreConfig('zpapi/settings/w2p_key');

    //Always print debug information. Issue #80
    $this->debug = true;

    $this->notice("ZetaPrints URL: {$url}");
    $this->notice("ZetaPrints API Key: {$key}");
    $this->warning('Please, make certain the domain name and the API key for your ZetaPrints account are correct');

    $refresh_templates = (bool)(int)Mage::getStoreConfig('zpapi/settings/w2p_refresh');
    if ($refresh_templates)
      $this->warning('Refresh all templates');

    $catalogs = zetaprints_get_list_of_catalogs($url, $key);

    if ($catalogs === null) {
      $this->error('Error in parsing catalogs detailes xml');
      return;
    } else if (is_string($catalogs)) {
      $this->error("Error in receiving catalogs: {$catalogs}");
      return;
    }

    if (!count($catalogs)) {
      $this->warning('No catalogs');
      return;
    }

    $total_number_of_templates = 0;
    $number_of_added_templates = 0;
    $number_of_uptodate_templates = 0;
    $number_of_updated_templates = 0;

    foreach ($catalogs as $catalog) {
      $templates = zetaprints_get_templates_from_catalog($url, $key, $catalog['guid']);

      if ($templates === null) {
        $this->error("Error in parsing templates detailes xml from catalog {$catalog['title']}");
        continue;
      } else if (is_string($templates)) {
        $this->error("Error in receiving list of templates for catalog {$catalog['title']}: {$templates}");
        continue;
      }

      if (!count($templates)) {
        $this->warning("No templates in catalog {$catalog['title']}");
        continue;
      }

      $total_number_of_templates += count($templates);

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

              $template['xml'] = zetaprints_get_template_details_as_xml($url, $key, $template['guid']);

              if (!$template['xml']) {
                $this->error("Error in receiving detailes for template {$template['guid']}. Leaving the template unmodified.");
                continue;
              }

              $template_model->addData($template)->save();
              $number_of_updated_templates++;
            }
            else {
              $number_of_uptodate_templates++;
              $this->debug("Template {$template['guid']} is up to date");
            }
        else {
          $template['xml'] = zetaprints_get_template_details_as_xml($url, $key, $template['guid']);

          if (!$template['xml']) {
            $this->error("Error in receiving detailes for template {$template['guid']}. Passing the template.");
            continue;
          }

          $template_model = Mage::getModel('webtoprint/template');
          $template_model->addData($template)->save();

          if ($template_model->getId())
            $number_of_added_templates++;
            $this->notice("Template {$template['guid']} is succesfully added.");
        }
      }
    }

    $this->notice("Total number of templates: {$total_number_of_templates}");
    $this->notice("Number of added templates: {$number_of_added_templates}");
    $this->notice("Number of up to date templates: {$number_of_uptodate_templates}");
    $this->notice("Number of updated templates: {$number_of_updated_templates}");
  }

  public function unparse() {}

  private function error ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::ERROR);
  }

  private function warning ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::WARNING);
  }

  private function notice ($message) {
    $this->addException($message, Mage_Dataflow_Model_Convert_Exception::NOTICE);
  }

  private function debug ($message) {
    if ($this->debug)
      $this->notice($message);
  }
}
