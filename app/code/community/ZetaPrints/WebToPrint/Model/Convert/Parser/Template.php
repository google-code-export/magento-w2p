<?php

class ZetaPrints_WebToPrint_Model_Convert_Parser_Template
  extends  Mage_Dataflow_Model_Convert_Parser_Abstract
  implements ZetaPrints_Api {

  public function parse() {
    $url = Mage::getStoreConfig('webtoprint/settings/url');
    $key = Mage::getStoreConfig('webtoprint/settings/key');

    //Always print debug information. Issue #80
    $this->debug = true;

    if ($url)
      $this->notice("ZetaPrints URL: {$url}");
    else
      $this->error("ZetaPrints URL is empty");

    if ($key)
      $this->notice('ZetaPrints API Key: ' . substr($key, 0, 6). '&hellip;');
    else
      $this->error("ZetaPrints API Key is empty");

    $refresh_templates
         = (bool) Mage::getStoreConfig('webtoprint/settings/refresh-templates');

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
    $number_of_removed_templates = 0;

    $all_template_guids = array();

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
        $all_template_guids[$template['guid']] = $template['guid'];

        $template_array = zetaprints_get_template_detailes($url, $key, $template['guid']);

        if (!$template_array) {
          $this->error("Error in receiving or parsing detailes of template {$template['guid']}. Leaving the template unmodified.");
          continue;
        }

        $has_fields = false;

        foreach ($template_array['pages'] as $page)
          if (isset($page['images']) || isset($page['fields'])) {
            $has_fields = true;
            break;
          }

        $template['public'] = $catalog['public'];
        $templates_collection = Mage::getModel('webtoprint/template')
                                  ->getCollection()
                                  ->get_by_guid($template['guid'])
                                  ->load();

        if ($templates_collection->getSize() == 1)
          foreach ($templates_collection as $template_model) {
            if (!$has_fields) {
              $template_model->setExist(false)->save();
              $number_of_removed_templates++;

              $this->warning("Template {$template['guid']} has no variable fields. Template will be deleted.");
              continue;
            }

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
          }
        else {
          if (!$has_fields) {
            $this->warning("Template {$template['guid']} has no variable fields. Ignored.");
            continue;
          }

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

    $templates_collection = Mage::getModel('webtoprint/template')
                                  ->getCollection()
                                  ->load();

    foreach ($templates_collection as $template)
      if (!isset($all_template_guids[$template->getGuid()])) {
        $number_of_removed_templates += 1;

        $template->setExist(false)->save();
      }

    $this->notice("Total number of templates: {$total_number_of_templates}");
    $this->notice("Number of added templates: {$number_of_added_templates}");
    $this->notice("Number of up to date templates: {$number_of_uptodate_templates}");
    $this->notice("Number of updated templates: {$number_of_updated_templates}");
    $this->notice("Number of removed templates: {$number_of_removed_templates}");
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
