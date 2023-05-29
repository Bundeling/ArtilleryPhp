module.exports = {
    testVar, functionTitle
};

function testVar(context, events, done) {
    context.vars.test = context.vars.test + 1;
    return done();
}

function functionTitle(context, events, done) {
    let complexObject = {
        property1: 'hello',
        property2: 'world',
        // Add more properties as needed
    };

    // "userContext.vars" is a special object where you can set variables that can be referenced in the YAML script
    context.vars.complexObject = complexObject;
    return done();
}
