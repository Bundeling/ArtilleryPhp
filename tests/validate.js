const fs = require('fs');
const yaml = require('js-yaml');
const validateScript = require('./node_modules/artillery/lib/util/validate-script.js');
const file = process.argv[2];

fs.readFile(file, 'utf8', (err, data) => {
    if (err) {
        console.error('❌ ArtilleryPhp build error (no yaml):', file);
        console.error(' ↳', err)
        return;
    }

    try {
        const doc = yaml.load(data);
        const error = validateScript(doc);
        if (error) {
            console.error('❌ Validation error:', file);
            console.error(' ↳', error)
        } else {
            console.log('✅ Validation success:', file);
        }
    } catch (e) {
        console.error('❌ YAML parsing error:', file);
        console.error(' ↳', file, e);
    }
});