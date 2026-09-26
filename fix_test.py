import re

file_path = "tests/Feature/IdorProtectionTest.php"
with open(file_path, 'r') as f:
    content = f.read()

content = content.replace("        }\n}\n", "}\n")

with open(file_path, 'w') as f:
    f.write(content)

print("Test fixed.")
