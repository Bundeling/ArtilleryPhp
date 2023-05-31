const fs = require('fs');
const yaml = require('js-yaml');
const validateScript = require('./node_modules/artillery/lib/util/validate-script.js');
const file = process.argv[2];

fs.readFile(file, 'utf8', (err, data) => {
    if (err) {
        console.error('❌ Error reading file:', file);
        console.error(' ↳', err)
        return;
    }

    try {
        let doc = yaml.load(data);
        let error = validateScript(doc);
        if (error) {
            console.error('❌ Error in file:', file);
            console.error(' ↳', error)
        } else {
            console.log('✅ File validated:', file);
        }
    } catch (e) {
        console.error('❌ Error parsing YAML:', file);
        console.error(' ↳', file, e);
    }
});