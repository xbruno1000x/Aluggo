export function initTaxasAssoc(root: HTMLElement | Document = document) {
    const radios = Array.from(root.querySelectorAll('input[name="assoc_type"]')) as HTMLInputElement[];
    if (!radios.length) return;

    function updateAssoc() {
        const checked = radios.find(r => r.checked);
        const val = checked ? checked.value : 'none';
        root.querySelectorAll('.assoc-target').forEach(el => el.classList.add('d-none'));
        if (val === 'imovel') {
            root.querySelectorAll('.assoc-imovel').forEach(el => el.classList.remove('d-none'));
        } else if (val === 'propriedade') {
            root.querySelectorAll('.assoc-propriedade').forEach(el => el.classList.remove('d-none'));
        }
    }

    radios.forEach(r => r.addEventListener('change', updateAssoc));
    updateAssoc();
}

if (typeof window !== 'undefined') {
    document.addEventListener('DOMContentLoaded', () => initTaxasAssoc(document));
}
