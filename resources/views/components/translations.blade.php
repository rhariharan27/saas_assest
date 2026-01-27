{{-- Simple Translation Component for JavaScript --}}
<script>
    // Basic translations for JavaScript
    window.translations = {};
    
    // Translation function
    window.__ = function(key, replace = {}) {
        const translations = {
            'Live Location': '{{ __("Live Location") }}',
            'Card View': '{{ __("Card View") }}',
            'Dashboard': '{{ __("Dashboard") }}',
            'Timeline': '{{ __("Timeline") }}',
            'Task View': '{{ __("Task View") }}',
            'Monitoring': '{{ __("Monitoring") }}',
            'Loading...': '{{ __("Loading...") }}',
            'Success': '{{ __("Success") }}',
            'Error': '{{ __("Error") }}',
            'Save': '{{ __("Save") }}',
            'Cancel': '{{ __("Cancel") }}',
            'Edit': '{{ __("Edit") }}',
            'Delete': '{{ __("Delete") }}',
            'Add': '{{ __("Add") }}',
            'Update': '{{ __("Update") }}',
            
            // New translations
            'Welcome to': '{{ __("Welcome to") }}',
            'Number of Additional Users': '{{ __("Number of Additional Users") }}',
            'Additional Users': '{{ __("Additional Users") }}',
            'No timeline data available.': '{{ __("No timeline data available.") }}',
            'No visits data available.': '{{ __("No visits data available.") }}',
            'No breaks data available.': '{{ __("No breaks data available.") }}',
            'No orders data available.': '{{ __("No orders data available.") }}',
            'No data available.': '{{ __("No data available.") }}',
            'No hierarchy data available.': '{{ __("No hierarchy data available.") }}',
            'Included Users': '{{ __("Included Users") }}',
            'Total Users': '{{ __("Total Users") }}',
            'Per User Price': '{{ __("Per User Price") }}',
            'Duration': '{{ __("Duration") }}',
            'Amount to be paid': '{{ __("Amount to be paid") }}'
        };
        
        let translation = translations[key] || key;
        
        // Handle replacements
        Object.keys(replace).forEach(placeholder => {
            const regex = new RegExp(':' + placeholder, 'g');
            translation = translation.replace(regex, replace[placeholder]);
        });
        
        return translation;
    };
</script>