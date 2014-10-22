#!/bin/sh

set -ev
# Enable Simpletest.
cd $TRAVIS_BUILD_DIR/../drupal
drush en --yes simpletest
drush cr
# Create a new CouchDB database.
curl -X PUT localhost:5984/test_db
drush --yes pm-enable relaxed_test || true