<?php

namespace Drupal\relaxed\Tests;

use Drupal\Component\Serialization\Json;

/**
 * Tests the /db/_ensure_full_commit resource.
 *
 * @group relaxed
 */
class EnsureFullCommitResourceTest extends ResourceTestBase {

  public function testPost() {
    $db = $this->workspace->id();
    $this->enableService('relaxed:ensure_full_commit', 'POST');

    // Create a user with the correct permissions.
    $permissions[] = 'restful post relaxed:ensure_full_commit';
    $account = $this->drupalCreateUser($permissions);
    $this->drupalLogin($account);

    $response = $this->httpRequest("$db/_ensure_full_commit", 'POST', NULL);
    $this->assertResponse('201', 'HTTP response code is correct.');
    $this->assertHeader('content-type', $this->defaultMimeType);
    $data = Json::decode($response);
    $expected = array(
      'instance_start_time' => (string) $this->workspace->getStartTime(),
      'ok' => TRUE,
    );
    $this->assertIdentical($expected, $data, ('Correct values in response.'));
  }
}
