#!/bin/bash

# Loop through all .php files from the examples directory, excluding those in vendor and node_modules directories
for file in $(find ../examples -name 'vendor' -prune -o -name 'node_modules' -prune -o -name '*.php' -print); do
    # Remove old yaml:
    rm -f "${file%.php}.yml"
    # Build the yaml:
    php "$file"
    # Validate the yaml:
    node "./validate.js" "${file%.php}.yml"
done
