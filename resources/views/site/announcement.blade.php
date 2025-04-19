<x-guest-layout>
    <div x-data="announcementForm()">
        @if (session('success'))
            <div class="alert alert-success">
                <div class="text-center">
                    <h2 class="text-2xl font-bold text-green-600 mb-2">{{ session('success')['title'] }}</h2>
                    <p class="text-gray-700 text-sm">{{ session('success')['message'] }}</p>

                    @if (session('success')['note'])
                        <div class="border-t border-gray-200 my-4"></div>
                        <p class="text-sm text-gray-600 italic">{{ session('success')['note'] }}</p>
                    @endif
                </div>
            </div>
            <div class="mt-4 flex justify-center">
                <a href="{{ route('site.announcement') }}"
                    class="bg-primary hover:bg-[#d8881c] text-white font-bold py-2 px-4 rounded">
                    REGISTAR NOVO ANÚNCIO
                </a>
            </div>
        @else
            <form method="POST" action="{{ route('announcement.register') }}" @submit.prevent="validateAndSubmit"
                x-init="init()" x-ref="form" role="form" enctype="multipart/form-data">
                @csrf


                <!-- Modal -->
                <div x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center px-4">
                    <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 space-y-4 relative z-50">
                        <!-- Cabeçalho -->
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Precisa de ajuda para escrever?</h2>
                            <button class="text-gray-500 hover:text-gray-700 text-xl"
                                @click="showModal = false">&times;</button>
                        </div>

                        <!-- Conteúdo -->
                        <div class="text-sm text-gray-700 space-y-3">
                            <p>Se tiver dificuldade para escrever um <strong>título</strong> ou uma
                                <strong>descrição</strong> clara para seu anúncio, você pode usar o <a href="https://chat.openai.com/" class="text-blue-500" target="_blank">ChatGPT</a> como
                                assistente.</p>

                            <p class="bg-gray-100 p-3 rounded border border-gray-300">
                                ✏️ <strong>Exemplo de pergunta para o ChatGPT:</strong><br>
                                <code>"Me ajuda a escrever um título e descrição para um anúncio de venda de peças de
                                    cerâmica artesanal."</code>
                            </p>

                            <p>Você pode explicar o que está oferecendo ou procurando, e o ChatGPT vai gerar uma
                                sugestão de texto para você.</p>
                        </div>

                        <!-- Botão Fechar -->
                        <div class="text-right">
                            <button class="bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-800 text-sm"
                                @click="showModal = false">
                                Fechar
                            </button>
                        </div>
                    </div>

                    <!-- Backdrop -->
                    <div x-show="showModal" class="fixed inset-0 bg-black bg-opacity-40 z-40"
                        @click="showModal = false"></div>
                </div>


                <div class="mb-4">
                    <x-input-label for="member_document" :value="__('Member Document')" />
                    <x-text-input id="member_document" class="block mt-1 w-full" type="text" name="member_document"
                        x-model="member_document" autocomplete="member_document" @change="checkDocumentNumber"
                        x-ref="member_document" />
                    <x-input-error class="mt-2" :messages="$errors->get('member_document')" />
                    <p class="error-message" x-text="errors.member_document"></p>
                </div>

                <div class="mb-4">
                    <x-input-label for="type" :value="__('Announcement Type')" />
                    <x-dropdown-select :options="$announcementOptions" placeholder="" @change="handleType" :selected="old('type')"
                        x-model="type" name="type" />
                    <x-input-error class="mt-2" :messages="$errors->get('type')" />
                    <p class="error-message" x-text="errors.type"></p>
                </div>

                <div class="mb-4">
                    <x-input-label for="title" :value="__('Title')" />
                    <x-text-input id="title" class="block mt-1 w-full" type="text"
                        @change="validateErrors('title')" x-bind:placeholder="placeholder.title" name="title"
                        x-model="title" autocomplete="title" />
                    <x-input-error class="mt-2" :messages="$errors->get('title')" />
                    <p class="error-message" x-text="errors.title"></p>
                </div>

                <div class="mb-4">
                    <x-input-label for="description" :value="__('Description')" />
                    <textarea id="description" name="description" @change="validateErrors('description')"
                        x-bind:placeholder="placeholder.description" x-model="description" rows="8"
                        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    <x-input-error class="mt-2" :messages="$errors->get('description')" />
                    <p class="error-message" x-text="errors.description"></p>
                    <span @click="showModal = true" class="text-primary text-sm cursor-pointer">Precisa de ajuda?</span>
                </div>

                <div class="mb-4">
                    <x-input-label for="link" :value="__('Link')" />
                    <x-text-input id="link" class="block mt-1 w-full" type="text" name="link" x-model="link"
                        autocomplete="link" />
                    <x-input-error class="mt-2" :messages="$errors->get('link')" />
                    <p class="error-message" x-text="errors.link"></p>
                </div>

                <div class="mb-4">
                    <x-input-label for="contact" :value="__('Telephone Number')" />
                    <x-text-input id="contact" class="block mt-1 w-full" type="text"
                        @change="validateErrors('contact')" name="contact" x-model="contact" autocomplete="contact" />
                    <x-input-error class="mt-2" :messages="$errors->get('contact')" />
                    <p class="error-message" x-text="errors.contact"></p>
                </div>

                <div class="mb-4">
                    <x-input-label for="image_path" :value="__('Upload File')" />
                    <input id="image_path" name="image_path" type="file" accept=".pdf,image/*"
                        class="block mt-1 w-full border-gray-300 rounded-md shadow-sm" x-model="image_path" />

                    <x-input-error class="mt-2" :messages="$errors->get('image_path')" />
                    <p class="error-message" x-text="errors.image_path"></p>
                </div>

                <div class="mt-8 flex justify-between items-center">
                    <x-primary-button type="submit" class="bg-primary hover:bg-[#d8881c]">
                        {{ __('Create Announcement') }}
                    </x-primary-button>
                    <a type="button" href="{{ route('site.member') }}" class="ml-4">
                        {{ __('To be a member!') }}
                    </a>
                </div>
            </form>
        @endif
    </div>

    <style>
        .error-message {
            margin-top: 0.25rem;
            color: #e53e3e;
            font-size: 0.75rem;
        }
        body {
            /* background com 50% do tamanho menor com opacidade na imagem */
            background: url("{{ asset('img/background.jpg') }}") repeat !important;
            background-position: center !important;
            background-attachment: fixed !important;
            background-size: 70% !important;
            background-color: #333 !important;
        }
    </style>
    <script src="https://unpkg.com/imask"></script>
    <script>
        function announcementForm() {
            return {
                showModal: false,
                member_document: '',
                title: '',
                description: '',
                link: '',
                type: '',
                contact: '',
                image_path: '',
                errors: {
                    member_document: '',
                    title: '',
                    link: '',
                    type: '',
                    contact: '',
                    image_path: '',
                    description: ''
                },
                placeholder: {
                    title: 'Título do anúncio',
                    description: 'Descrição do anúncio',
                    link: 'Link do anúncio',
                },
                handleType() {
                    this.validateErrors('type');

                    if (this.type === 'service') {
                        this.placeholder.title = 'Ex. Pintor Profissional';
                        this.placeholder.description =
                            'Ex. Pintor com experiência em pintura residencial e comercial, oferecendo serviços de alta qualidade com acabamento profissional. Trabalho com pintura interna e externa, aplicação de massa corrida, textura, grafiato e reparos em paredes. Atendimento rápido, limpo e com foco na satisfação do cliente.';
                    } else if (this.type === 'product') {
                        this.placeholder.title = 'Ex. Produtos de cerâmica';
                        this.placeholder.description =
                            'Oferecemos uma variedade de produtos em cerâmica para decoração, utilitários e acabamento. Itens feitos com qualidade e ótimo custo-benefício. Consulte disponibilidade e modelos';
                    } else if (this.type === 'resume') {
                        this.placeholder.title = 'Ex. Restauração';
                        this.placeholder.description =
                            'Ex. Sou uma pessoa motivada e entusiasta, em busca de uma oportunidade na área de restauração. Tenho interesse em aprender e crescer no setor, buscando uma posição onde possa desenvolver minhas habilidades em atendimento ao cliente e trabalho em equipe. Estou disponível para diferentes turnos e pronto para começar imediatamente. Sou pontual, comunicativo e tenho vontade de aprender novas tarefas.';
                        this.placeholder.link = 'https://www.linkedin.com/in/****/';
                    } else if (this.type === 'donation') {
                        this.placeholder.title = 'Ex. Doação de Roupas';
                        this.placeholder.description = 'Ex. Doação de roupas para crianças';
                    } else {
                        this.placeholder.title = '';
                        this.placeholder.description = '';
                        this.placeholder.link = '';
                    }
                },
                checkDocumentNumber() {
                    fetch(`{{ route('member.checkDocumentNumber', ':document_number') }}`.replace(':document_number', this
                            .member_document))
                        .then(response => response.json())
                        .then(data => {
                            if (data.exists) {
                                this.errors.member_document = '';
                            } else {
                                this.errors.member_document =
                                    'Você precisa ser membro para criar um anúncio. Caso já seja membro, verifique o documento informado ou contate-nos.';
                            }
                        })
                        .catch(error => {
                            this.errors.member_document = 'Erro ao validar o documento. Tente novamente mais tarde.';
                        });
                },
                validateErrors(attribute = 'all') {

                    if ((attribute === 'all' || attribute === 'member_document') && this.member_document === '') {
                        this.errors.member_document = 'Campo obrigatório';
                    }

                    if (attribute === 'all' || attribute === 'contact') {
                        this.errors.contact = this.contact ? '' : 'Campo obrigatório';
                    }

                    if (attribute === 'all' || attribute === 'type') {
                        this.errors.type = this.type ? '' : 'Campo obrigatório';
                    }

                    if (attribute === 'all' || attribute === 'title') {
                        this.errors.title = this.title ? '' : 'Campo obrigatório';
                    }

                    if (attribute === 'all' || attribute === 'description') {
                        this.errors.description = this.description ? '' : 'Campo obrigatório';
                    }
                },
                validateAndSubmit() {
                    this.validateErrors();

                    if (this.isValid()) {
                        this.$refs.form.submit();
                    }
                },
                isValid() {
                    return Object.values(this.errors).every(e => e === '');
                },
                init() {
                    IMask(this.$refs.member_document, {
                        mask: '000000000'
                    });
                    IMask(this.$refs.contact, {
                        mask: '000000000'
                    });
                },
            }
        }
    </script>
</x-guest-layout>
