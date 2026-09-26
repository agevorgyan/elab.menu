const fs = require('fs');
const html = fs.readFileSync('/tmp/debug.html', 'utf8');
const regex = /<script.*?>([\s\S]*?)<\/script>/g;
let match;
while ((match = regex.exec(html)) !== null) {
    const code = match[1];
    try {
        new Function(code);
    } catch (e) {
        console.error("Error evaluating script:", e);
        const lines = code.split('\n');
        for (let i = 0; i < lines.length; i++) {
            console.log(i + 1, lines[i]);
        }
    }
}
