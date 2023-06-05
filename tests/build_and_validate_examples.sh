#!/bin/bash
# Install test dependencies
echo "ℹ️ Installing test dependencies..."
cd ${BASH_SOURCE%/*} && npm install

# Install examples dependencies
echo "ℹ️ Installing examples dependencies..."
cd ../examples && ../composer.phar install

# Loop through all .php files from the examples directory, excluding those in vendor and node_modules directories
for file in $(find . -name 'vendor' -prune -o -name 'node_modules' -prune -o -name '*.php' -print); do

    # Remove old yaml:
    rm -f "${file%.php}.yml"

    # Build the yaml:
    php "$file"

    # Validate the yaml:
    node "../tests/validate.js" "${file%.php}.yml"

done
