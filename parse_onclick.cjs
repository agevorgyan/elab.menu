const fs = require('fs');
const html = fs.readFileSync('/tmp/debug.html', 'utf8');
const regex = /onclick="([^"]*)"/g;
let match;
while ((match = regex.exec(html)) !== null) {
    const code = match[1];
    try {
        new Function(code);
    } catch (e) {
        console.error("Error evaluating:", code);
        console.error(e);
    }
}
