(() => {
    document.querySelectorAll('[data-branding-field]').forEach((field) => {
        const input = field.querySelector('[data-branding-input]');
        const preview = field.querySelector('[data-branding-preview]');
        const remove = field.querySelector('[data-branding-remove]');
        const original = preview.src;
        let reader;

        input.addEventListener('change', () => {
            if (reader?.readyState === FileReader.LOADING) reader.abort();
            const file = input.files[0];
            if (!file) {
                preview.src = original;
                return;
            }
            remove.checked = false;
            reader = new FileReader();
            reader.addEventListener('load', () => { preview.src = reader.result; });
            reader.readAsDataURL(file);
        });

        remove.addEventListener('change', () => {
            if (remove.checked) {
                if (reader?.readyState === FileReader.LOADING) reader.abort();
                input.value = '';
                preview.src = original;
            }
        });
    });
})();
