<?php

/**
 * @file
 * Contains \Drupal\relaxed\Plugin\rest\resource\AllDocsResource.
 */

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\relaxed\AllDocs\AllDocs;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RestResource(
 *   id = "relaxed:all_docs",
 *   label = "All Docs",
 *   serialization_class = {
 *     "canonical" = "Drupal\relaxed\AllDocs\AllDocs",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/_all_docs",
 *   }
 * )
 */
class AllDocsResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }
    // @todo: Inject the container without using deprecated method call.
    $all_docs = AllDocs::createInstance(
      \Drupal::getContainer(),
      $workspace
    );

    $request = Request::createFromGlobals();
    if ($request->query->get('include_docs') == 'true') {
      $all_docs->includeDocs(TRUE);
    }
    return new ResourceResponse($all_docs, 200);
  }
}
