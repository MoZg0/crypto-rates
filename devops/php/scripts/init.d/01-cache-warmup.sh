#!/bin/bash

set +eu

if [ "$APP_ENV" = "prod" ]; then
   php bin/console cache:warmup --env=prod --no-debug
fi
