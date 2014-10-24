<?php

namespace Drupal\relaxed\Tests;

/**
 * Tests the /db resource.
 *
 * @group relaxed
 */
class DbResourceHeadTest extends ResourceTestBase {

  public function testHead() {
    // HEAD and GET is handled by the same resource.
    $this->enableService('relaxed:db', 'GET');

    // Create a user with the correct permissions.
    $permissions = $this->entityPermissions('workspace', 'view');
    $permissions[] = 'restful get relaxed:db';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    // Test the HEAD request with empty Accept header.
    $response = $this->httpRequest($this->workspace->id(), 'HEAD', NULL, '');
    $this->assertResponse('406', 'HTTP response code is correct.');
  }
}
