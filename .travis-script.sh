#!/bin/bash

set -e $DRUPAL_TI_DEBUG

# Run PHPUnit tests and submit code coverage statistics.
drupal_ti_ensure_drupal
drupal_ti_ensure_module_linked

cd $DRUPAL_TI_DRUPAL_DIR/core
$DRUPAL_TI_DRUPAL_DIR/vendor/bin/phpunit --group address
