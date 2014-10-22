#!/bin/sh

set -ev
# Create a new CouchDB database.
curl -X PUT localhost:5984/test_db
# Enable Simpletest.
drush en --yes simpletest
drush cr
curl -X POST -d '{"source":"http://localhost/relaxed/default","target":"localhost:5984/test_db"}' http://localhost:5984/_replicate