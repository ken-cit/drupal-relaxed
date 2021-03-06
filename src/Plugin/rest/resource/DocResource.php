<?php

namespace Drupal\relaxed\Plugin\rest\resource;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\relaxed\HttpMultipart\ResourceMultipartResponse;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @RestResource(
 *   id = "relaxed:doc",
 *   label = "Document",
 *   serialization_class = {
 *     "canonical" = "Drupal\Core\Entity\ContentEntityInterface",
 *   },
 *   uri_paths = {
 *     "canonical" = "/{db}/{docid}",
 *   }
 * )
 *
 * @todo We should probably make it not possible to save '_local' documents
 *   through this resource.
 */
class DocResource extends ResourceBase {

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function head($workspace, $existing) {
    if (is_string($workspace) || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : array($existing);

    foreach ($revisions as $revision) {
      if (!$revision->access('view')) {
        throw new AccessDeniedHttpException();
      }
    }

    // @todo Create a event handler and override the ETag that's set by core.
    // @see \Drupal\Core\EventSubscriber\FinishResponseSubscriber
    return new ResourceResponse(NULL, 200, array('X-Relaxed-ETag' => $revisions[0]->_revs_info->rev));
  }

  /**
   * @param string | \Drupal\Core\Config\Entity\ConfigEntityInterface $workspace
   * @param mixed $existing
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function get($workspace, $existing) {
    if (is_string($workspace) || is_string($existing)) {
      throw new NotFoundHttpException();
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface[] $revisions */
    $revisions = is_array($existing) ? $existing : array($existing);

    foreach ($revisions as $revision) {
      if (!$revision->access('view')) {
        throw new AccessDeniedHttpException();
      }
      foreach ($revision as $field_name => $field) {
        if (!$field->access('view')) {
          unset($revision->{$field_name});
        }
      }
    }

    $result = $revisions[0];
    $request = Request::createFromGlobals();
    if (is_array($existing)) {
      $parts = array();
      if (strpos($request->headers->get('Accept'), 'application/json') === FALSE
        && strpos($request->headers->get('Content-Type'), 'application/json') === FALSE) {
        foreach ($revisions as $revision) {
          $parts[] = new ResourceResponse($revision, 200);
        }

        // Multipart response.
        return new ResourceMultipartResponse($parts, 200, array('Content-Type' => 'multipart/mixed'));
      }
      else {
        $result = array();
        foreach ($revisions as $revision) {
          $result[] = array('ok' => $revision);
        }
      }
    }

    // Normal response.
    return new ResourceResponse($result, 200, array('X-Relaxed-ETag' => $revisions[0]->_revs_info->rev));
  }

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $existing_entity
   * @param \Drupal\Core\Entity\ContentEntityInterface $received_entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function put($workspace, $existing_entity, ContentEntityInterface $received_entity) {
    if (is_string($workspace)) {
      throw new NotFoundHttpException();
    }

    // Check entity and field level access.
    if (!$received_entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    foreach ($received_entity as $field_name => $field) {
      if (!$field->access('create')) {
        throw new AccessDeniedHttpException(t('Access denied on creating field @field.', array('@field' => $field_name)));
      }
    }

    // @todo Ensure that $received_entity is being saved with the UUID from $existing_entity

    // Validate the received data before saving.
    $this->validate($received_entity);

    if (!is_string($existing_entity) && $received_entity->_revs_info->rev != $existing_entity->_revs_info->rev) {
      throw new ConflictHttpException();
    }

    try {
      $received_entity->save();
      $rev = $received_entity->_revs_info->rev;
      $data = array('ok' => TRUE, 'id' => $received_entity->uuid(), 'rev' => $rev);
      return new ResourceResponse($data, 201, array('X-Relaxed-ETag' => $rev));
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, NULL, $e);
    }
  }

  /**
   * @param string | \Drupal\multiversion\Entity\WorkspaceInterface $workspace
   * @param string | \Drupal\Core\Entity\ContentEntityInterface $entity
   *
   * @return \Drupal\rest\ResourceResponse
   */
  public function delete($workspace, $entity) {
    if (is_string($workspace) || is_string($entity)) {
      throw new NotFoundHttpException();
    }

    if (!$entity->access('delete')) {
      throw new AccessDeniedHttpException();
    }

    $record = \Drupal::service('entity.index.uuid')->get($entity->uuid());
    $last_rev = $record['rev'];
    if ($last_rev != $entity->_revs_info->rev) {
      throw new ConflictHttpException();
    }

    try {
      $entity->delete();
    }
    catch (\Exception $e) {
      throw new HttpException(500, NULL, $e);
    }

    return new ResourceResponse(array('ok' => TRUE), 200);
  }
}
