<?php
if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}
/**
 * @package   Jagger
 * @author    Middleware Team HEAnet
 * @copyright 2012, HEAnet Limited (http://www.heanet.ie)
 * @license   MIT http://www.opensource.org/licenses/mit-license.php
 */

class Metadatavalidator
{
    protected $rootSchemaFile;

    public function __construct() {
        $this->ci = &get_instance();
        $this->em = $this->ci->doctrine->em;
        $this->ci->load->helper('metadata_elements');
        $this->rootSchemaFile = $this->ci->config->item('rootSchemaFile');
        if (empty($this->rootSchemaFile)) {
            $this->rootSchemaFile = 'saml-schema-metadata-2.0.xsd';
        }
    }

    public function validateWithSchema($metadata = null) {
        if (empty($metadata)) {
            log_message('error', 'cannot validate empty metadata');

            return false;
        }

        $this->ci->load->helper('metadata_elements');
        if (function_exists("libxml_set_external_entity_loader")) {
            log_message('debug', 'libxml_set_external_entity_loader supported');
            $schemasFolder = dirname(APPPATH) . '/schemas/new/';
            $mapping = j_schemasMapping($schemasFolder);
            libxml_set_external_entity_loader(
                function ($public, $system, $context) use ($mapping) {
                    if (is_file($system)) {
                        return $system;
                    }
                    if (isset($mapping[$system])) {
                        return $mapping[$system];
                    }
                    $message = "Failed to load external entity";
                    throw new RuntimeException($message);
                }
            );
            $schemaLocation = $schemasFolder . $this->rootSchemaFile;
        } else {
            $schemaLocation = dirname(APPPATH) . '/schemas/old/' . $this->rootSchemaFile;
        }


        libxml_use_internal_errors(true);

        $doc = new \DOMDocument();
        $doc->strictErrorChecking = false;
        $doc->strictWarningChecking = false;
        $doc->loadXML($metadata);
        $doc->xinclude();
        libxml_use_internal_errors(true);
        $result = $doc->schemaValidate($schemaLocation);
        $errors = libxml_get_errors();
        if ($result === true) {
            log_message('debug', 'tested metadata is valid');
        } else {
            log_message('error', 'tested metadata is not valid:' . serialize($errors));
        }

        return $result;
    }

}
