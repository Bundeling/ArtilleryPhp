Using https://github.com/artilleryio/artillery/blob/main/packages/artillery/lib/util/validate-script.js for some light validation of the library.

`build_and_validate_examples.sh` will call for each php script in the examples folder:
1. remove any old generated yml file `rm -f "${file%.php}.yml"`
2. generate a new yml file with `php $file`
3. validate the resulting schema `node validate.js ${file%.php}.yml`

Each php script is expected to call `->build()` with default parameters.

Install requirements (js-yaml artillery):

```
npm install
```

`validate.js` uses `js-yaml` to parse the yaml file and then `validate-script.js` from `artillery` to validate the script.