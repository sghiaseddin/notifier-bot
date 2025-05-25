#!/bin/bash

# Resolve full path to this script's directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# PHP script path
PHP_SCRIPT="$SCRIPT_DIR/check-endpoint.php"

# Check existence before running
if [[ -f "$PHP_SCRIPT" ]]; then
    php "$PHP_SCRIPT"
else
    echo "Could not find $PHP_SCRIPT"
    exit 1
fi