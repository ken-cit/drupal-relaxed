#!/bin/sh

- curl -X PUT localhost:5984/test_db
- drush --yes pm-enable relaxed_test || true