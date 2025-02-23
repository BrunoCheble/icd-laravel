<div x-data="dropdownSelect({{ json_encode($options) }}, '{{ $selected }}')" class="relative" @click.outside="open = false; validateInput()">
    <input type="hidden" name="{{ $name }}" x-model="selected">

    <div class="relative">
        <input
            type="text"
            x-model="search"
            x-on:focus="open = true"
            x-on:keydown.escape="open = false"
            placeholder="{{ $placeholder }}"
            class="block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-text focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary"
        >

        <div x-show="open" x-transition
             class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg">
            <ul class="max-h-60 overflow-auto">
                <template x-for="(value, index) in filteredOptions" :key="index">
                    <li
                        x-text="value"
                        x-on:click="selectOption(index)"
                        class="cursor-pointer px-3 py-2 hover:bg-gray-100"
                    ></li>
                </template>
                <li x-show="filteredOptions.length === 0" class="px-3 py-2 text-gray-500">No results found</li>
            </ul>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dropdownSelect', (options, selected) => ({
        search: options[selected] || '', // Exibe o nome correto ao carregar a página
        selected: selected,
        open: false,
        highlighted: null,

        selectOption(index) {
            this.selected = index;
            this.search = options[index]; // Atualiza o input corretamente
            this.open = false;
            console.log('teste')
        },

        selectHighlighted() {
            if (this.highlighted !== null) {
                this.selectOption(this.highlighted);
            }
        },

        navigate(step) {
            let keys = Object.keys(this.filteredOptions);
            let currentIndex = keys.indexOf(this.highlighted);

            if (currentIndex === -1) {
                this.highlighted = keys[0];
            } else {
                let nextIndex = (currentIndex + step + keys.length) % keys.length;
                this.highlighted = keys[nextIndex];
            }
        },
        validateInput() {
            if (this.search.trim() === '') {
                this.selected = null; // Define o input hidden como null se estiver vazio
                return;
            }

            let validKey = Object.keys(options).find(key => options[key].toLowerCase() === this.search.toLowerCase());
            if (validKey) {
                this.selected = validKey; // Mantém a seleção correta
            } else {
                this.search = options[this.selected] || ''; // Restaura o valor original se não for válido
            }
        },

        get filteredOptions() {
            return Object.keys(options)
                .filter(key => options[key].toLowerCase().includes(this.search.toLowerCase()))
                .reduce((obj, key) => {
                    obj[key] = options[key];
                    return obj;
                }, {});
        }
    }));
});
</script>
