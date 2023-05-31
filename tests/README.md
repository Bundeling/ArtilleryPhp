Using https://github.com/artilleryio/artillery/blob/main/packages/artillery/lib/util/validate-script.js for some light validation of the library.

`build_run_scripts.sh` will call for each php script in the examples folder:

1. php $file
2. node validate.js ${file%.php}.yml

Each php script is expected to call `->build()` with default parameters.

Install prequisites (js-yaml joi):

```
npm install
```

`validate.js` uses `js-yaml` to parse the yaml file and then `validate-script.js` uses `joi` to validate the script.