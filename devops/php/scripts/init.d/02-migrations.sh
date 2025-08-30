#!/bin/bash

set +eu

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration --no-debug
