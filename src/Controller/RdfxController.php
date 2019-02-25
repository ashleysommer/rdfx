<?php

/**
 * @file
 * Contains \Drupal\rdfx\Controller\RdfxController.
 */

namespace Drupal\rdfx\Controller;

use Drupal\Core\Entity\ContentEntityInterface;


/**
 * Controller routines for book routes.
 */
class RdfxController {

    /**
     * Returns an administrative overview of rdfx
     *
     * @return array
     *   A render array representing the administrative page content.
     */
    public function adminOverview() {
        $renderer = \Drupal::service('renderer');
        $render = array();
        $entityTypeBundleInfoService = \Drupal::service('entity_type.bundle.info');
        $entityFieldManager = \Drupal::service('entity_field.manager');
        $entities = \Drupal::entityTypeManager()->getDefinitions();
        //$fields = field_info_instances();

        // Create a tab for each entity.
        foreach ($entities as $entity_type_name => $entity_type) {
            if ($entity_type->isInternal()) {
                continue;
            }
            $klass = $entity_type->getClass();
            if (is_subclass_of($klass, ContentEntityInterface::class)) {
                $render[$entity_type_name] = array(
                    '#type' => 'details',
                    '#title' => $entity_type->getLabel(),
                    '#collapsible' => TRUE,
                    '#collapsed' => TRUE,
                );
                $bundle_info = $entityTypeBundleInfoService->getBundleInfo($entity_type->id());
                // The bundle's RDF mapping array may contain mappings for entity attributes
                // that are not fields. The bundle's field array may contain fields that are
                // not in the RDF mapping array. In order to ensure we get all the available
                // fields and all the mapped entity attributes, we compare the arrays.
                foreach ($bundle_info as $bundle_name => $bundle) {
                    $rows = array();
                    $real_fields = array();
                    $fake_fields = array();
                    $bundle_fields = $entityFieldManager->getFieldDefinitions($entity_type->id(), $bundle_name);
                    $bundle['edit_path'] = NULL;
                    $rdf_mapping = $bundle['rdf_mapping'];
                    if (isset($bundle['admin']['real path'])) {
                        $bundle['edit_path'] = $bundle['admin']['real path'] . '/rdf';
                    }

                    // Set RDF type.
                    if (isset($rdf_mapping['rdftype'])) {
                        $rdftype = implode(', ', $rdf_mapping['rdftype']);
                        unset($rdf_mapping['rdftype']);
                    }
                    foreach ($rdf_mapping as $field_name => $mapping_info) {
                        // Gather Field API fields.
                        if (isset($bundle_fields[$field_name])) {
                            $real_fields[$field_name]['rdf_mapping'] = $mapping_info;
                            $real_fields[$field_name]['label'] = $bundle_fields[$field_name]->getLabel();
                            $real_fields[$field_name]['edit_path'] = $bundle['edit_path'];
                            unset($bundle_fields[$field_name]);
                        }
                        // Gather non-field content variables.
                        else {
                            $fake_fields[$field_name]['rdf_mapping'] = $mapping_info;
                            $fake_fields[$field_name]['label'] = $field_name;
                            $fake_fields[$field_name]['edit_path'] = ($field_name == 'title') ? $bundle['edit_path'] : NULL;
                        }
                    }
                    $render[$entity_type_name][$bundle_name] = theme_rdfx_mapping_admin_overview( array('bundle' => $bundle, 'rdftype' => $rdftype, 'real_fields' =>$real_fields, 'fake_fields' => $fake_fields));
                }
            }
        }
        return $render;
    }
    public function adminNamespaces() {
        $output = '';

        // List conflicting namespaces.
        $conflicting_namespaces = rdfx_get_conflicting_namespaces();
        if ($conflicting_namespaces) {
            $table_conflicting_namespaces = array();
            $table_conflicting_namespaces['header'] = array('Prefix', 'Conflicting Namespaces');
            foreach ($conflicting_namespaces as $prefix => $uris) {
                $table_conflicting_namespaces['rows'][] = array($prefix, implode(", ", $uris));
            }
            $output .= '<div class="messages warning">' . t("Warning: The following namespaces have conflicts") . '</div>';
            $output .= theme('table', $table_conflicting_namespaces);
        }

        // List non-conflicting namespaces.
        $table_namespaces = array();
        $table_namespaces['header'] = array('Prefix', 'Namespace');
        foreach (rdf_get_namespaces() as $prefix => $namespace) {
            $table_namespaces['rows'][] = array($prefix, $namespace);
        }
        // Only show label if there were conflicting namespaces.
        if ($conflicting_namespaces) {
            $output .= '<div class="messages status">' . t("The following namespaces do not have conflicts") . '</div>';
        }
        $output .= theme('table', $table_namespaces);

        // Form to add namespaces.
        $form = drupal_get_form('rdfx_admin_namespaces_form');
        $output .= drupal_render($form);

        return $output;
    }

}
