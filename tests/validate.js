const fs = require('fs');
const yaml = require('js-yaml');
const validateScript = require('./validate-script/validate-script.js');
const file = process.argv[2];
fs.readFile(file, 'utf8', (err, data) => {
    if (err) {
        console.error('Error reading file:', err);
        return;
    }

    try {
        let doc = yaml.load(data);
        let error = validateScript(doc);
        if (error) {
            console.error('Error in file:', file, error);
        } else {
            console.log('File parsed successfully:', file);
        }
    } catch (e) {
        console.error('Error parsing YAML:', e);
    }
});