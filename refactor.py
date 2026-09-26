import re

file_path = "resources/views/superadmin/settings/index.blade.php"
with open(file_path, 'r') as f:
    content = f.read()

# Define the boundaries of the tabs using regex or string searches
# 1. Landing: `<div x-show="activeTab === 'landing'" x-cloak>` to `<!-- ============================================== -->\n        <!-- TAB 2:`
# 2. Branding: `<div x-show="activeTab === 'branding'" x-cloak>` to `<!-- ============================================== -->\n        <!-- TAB 3:`
# 3. SEO: `<div x-show="activeTab === 'seo'" x-cloak>` to `<!-- ============================================== -->\n        <!-- TAB 4:`
# 4. Contacts: `<div x-show="activeTab === 'contacts'" x-cloak>` to `<!-- ============================================== -->\n        <!-- TAB 5:`
# 5. Telegram: `<div x-show="activeTab === 'telegram'" x-cloak>` to `<!-- Submit Button`

# 6. Form tag modification: `<form action="{{ route('superadmin.settings.update') }}" method="POST" enctype="multipart/form-data">`
# Add x-data="{ isSubmitting: false }" @submit="isSubmitting = true" ...

import os
os.makedirs("resources/views/components/superadmin/settings", exist_ok=True)

def extract_and_replace(content, start_marker, end_marker, component_name):
    start_idx = content.find(start_marker)
    end_idx = content.find(end_marker, start_idx)
    
    if start_idx == -1 or end_idx == -1:
        print(f"Could not find markers for {component_name}")
        return content
        
    chunk = content[start_idx:end_idx].strip()
    
    with open(f"resources/views/components/superadmin/settings/{component_name}.blade.php", 'w') as f:
        # pass the $settings variable to components. Actually blade components have access to them if we pass them, or since it's just a view inclusion, we could use `@include` but `<x-...` requires props.
        # It's better to just write the chunk.
        f.write("@props(['settings'])\n\n" + chunk)
        
    replacement = f"<x-superadmin.settings.{component_name} :settings=\"$settings\" />"
    return content[:start_idx] + replacement + "\n\n        " + content[end_idx:]

# Extract tabs
content = extract_and_replace(content, '<div x-show="activeTab === \'landing\'" x-cloak>', '<!-- ============================================== -->\n        <!-- TAB 2:', 'landing-tab')
content = extract_and_replace(content, '<div x-show="activeTab === \'branding\'" x-cloak>', '<!-- ============================================== -->\n        <!-- TAB 3:', 'branding-tab')
content = extract_and_replace(content, '<div x-show="activeTab === \'seo\'" x-cloak>', '<!-- ============================================== -->\n        <!-- TAB 4:', 'seo-tab')
content = extract_and_replace(content, '<div x-show="activeTab === \'contacts\'" x-cloak>', '<!-- ============================================== -->\n        <!-- TAB 5:', 'contacts-tab')
content = extract_and_replace(content, '<div x-show="activeTab === \'telegram\'" x-cloak>', '<!-- Submit Button (shown across all config tabs) -->', 'telegram-tab')

# Now add alpine validation to the form
old_form = '<form action="{{ route(\'superadmin.settings.update\') }}" method="POST" enctype="multipart/form-data">'
new_form = '<form action="{{ route(\'superadmin.settings.update\') }}" method="POST" enctype="multipart/form-data" x-data="{ isSubmitting: false, validate() { if(this.$el.checkValidity()) { this.isSubmitting = true; return true; } else { this.$el.reportValidity(); return false; } } }" @submit.prevent="if(validate()) $el.submit()">'

content = content.replace(old_form, new_form)

# Add alpine validation to the security forms as well
old_sec_form1 = '<form action="{{ route(\'superadmin.settings.security\') }}" method="POST">'
new_sec_form1 = '<form action="{{ route(\'superadmin.settings.security\') }}" method="POST" x-data="{ isSubmitting: false, validate() { if(this.$el.checkValidity()) { this.isSubmitting = true; return true; } else { this.$el.reportValidity(); return false; } } }" @submit.prevent="if(validate()) $el.submit()">'
content = content.replace(old_sec_form1, new_sec_form1)

with open(file_path, 'w') as f:
    f.write(content)

print("Refactoring complete.")
