{{-- The public page where a member sends an announcement (it waits for approval before the site shows it). $types --}}
@php
    $icons = ['resume' => 'fa-file-lines', 'service' => 'fa-screwdriver-wrench', 'donation' => 'fa-hand-holding-heart', 'product' => 'fa-tag'];
    // What follows a sent announcement, by its type.
    $thanks = [
        'resume' => [__('Your résumé was received!'), __('The team will review it and it will be on the church\'s site once approved. God bless your search!'), null],
        'service' => [__('Your service was received!'), __('The team will review it and it will be on the church\'s site once approved.'), null],
        'product' => [__('Your product was received!'), __('The team will review it and it will be on the church\'s site once approved.'), __('Sales are agreed directly between you and whoever is interested.')],
        'donation' => [__('Your donation was received!'), __('Thank you for your generosity! It will be on the church\'s site once approved.'), null],
    ];
    $sent = session('sent');
@endphp
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Announce') }} · Igreja Cidade de David</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        body { margin: 0; min-height: 100vh; background: linear-gradient(180deg, #fef3e2 0, #f4f5f7 18rem); font-family: Figtree, ui-sans-serif, system-ui, sans-serif; color: #111827; }
        .an { width: min(38rem, 100%); margin: 0 auto; padding: 1.25rem 1rem 3rem; box-sizing: border-box; }
        .an-top { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.25rem; }
        .an-top img { width: 3.2rem; height: 3.2rem; padding: .3rem; border-radius: .95rem; background: #fff; box-shadow: 0 4px 14px rgba(0, 0, 0, .08); }
        .an-top a { display: inline-flex; align-items: center; gap: .4rem; color: #92400e; font-size: .88rem; font-weight: 700; text-decoration: none; }
        .an h1 { margin: 0; font-size: 1.6rem; font-weight: 800; letter-spacing: -.01em; }
        .an .lead { margin: .35rem 0 1.25rem; color: #4b5563; line-height: 1.5; }
        .an-card { padding: 1.25rem; border: 1px solid #e5e7eb; border-radius: 1.1rem; background: #fff; box-shadow: 0 1px 2px rgba(16, 24, 40, .04); }
        .an-field { margin-bottom: 1.1rem; }
        .an-field > label, .an-field > .label { display: block; margin-bottom: .4rem; font-size: .88rem; font-weight: 700; color: #374151; }
        .an-field label span, .an-field .label span { font-weight: 500; color: #9ca3af; }
        .an-field small { display: block; margin-top: .3rem; font-size: .8rem; color: #6b7280; }
        .an-input { width: 100%; box-sizing: border-box; min-height: 50px; padding: .6rem .85rem; border: 1px solid #d1d5db; border-radius: .8rem; background: #fff; font: inherit; font-size: 16px; color: #111827; }
        textarea.an-input { min-height: 9rem; line-height: 1.45; resize: vertical; }
        .an-input:focus { outline: none; border-color: #f8a925; box-shadow: 0 0 0 4px #fdeccd; }
        .an-types { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .5rem; }
        @media (max-width: 479px) { .an-types { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .an-types label { position: relative; }
        .an-types input { position: absolute; opacity: 0; pointer-events: none; }
        .an-types span { display: flex; flex-direction: column; align-items: center; gap: .4rem; padding: .85rem .5rem; border: 1px solid #e5e7eb; border-radius: .9rem; background: #fff; color: #4b5563; font-size: .85rem; font-weight: 700; text-align: center; cursor: pointer; }
        .an-types i { font-size: 1.2rem; color: #9ca3af; }
        .an-types input:checked + span { border-color: #f8a925; background: #fef3e2; color: #92400e; }
        .an-types input:checked + span i { color: #f8a925; }
        .an-types input:focus-visible + span { outline: 2px solid #f8a925; }
        .an-help { border: 0; background: none; padding: 0; margin-top: .35rem; color: #b45309; font: inherit; font-size: .82rem; font-weight: 700; cursor: pointer; }
        .an-tip { margin-top: .5rem; padding: .85rem 1rem; border-radius: .8rem; background: #f9fafb; border: 1px solid #e5e7eb; font-size: .85rem; line-height: 1.5; color: #374151; }
        .an-tip code { display: block; margin-top: .4rem; padding: .5rem .65rem; border-radius: .5rem; background: #fff; border: 1px solid #e5e7eb; font-size: .8rem; white-space: normal; }
        .an-error { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #dc2626; }
        .an-ok { margin: .35rem 0 0; font-size: .85rem; font-weight: 600; color: #15803d; }
        .an-btn { display: flex; align-items: center; justify-content: center; gap: .55rem; width: 100%; min-height: 54px; border: 0; border-radius: .9rem; background: #f8a925; color: #fff; font: inherit; font-size: 1.05rem; font-weight: 800; text-decoration: none; cursor: pointer; box-shadow: 0 8px 20px rgba(248, 169, 37, .3); }
        .an-btn:hover { background: #d8881c; }
        .an-btn:disabled { opacity: .7; cursor: default; }
        .an-btn.is-light { background: #fff; color: #92400e; border: 1px solid #fde68a; box-shadow: none; }
        .an-hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }
        .an-foot { margin-top: 1rem; text-align: center; font-size: .85rem; color: #6b7280; }
        .an-foot a { color: #b45309; font-weight: 700; }
        .an-done { padding: 2rem 1.25rem 1.5rem; text-align: center; }
        .an-done .icon { width: 4rem; height: 4rem; margin: 0 auto 1rem; display: grid; place-items: center; border-radius: 999px; background: #dcfce7; color: #16a34a; font-size: 1.7rem; }
        .an-done strong { display: block; font-size: 1.25rem; }
        .an-done p { margin: .45rem 0 0; color: #4b5563; line-height: 1.5; }
        .an-done .note { margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e5e7eb; font-size: .88rem; font-style: italic; color: #6b7280; }
        .an-done .actions { display: grid; gap: .5rem; margin-top: 1.25rem; }
    </style>
</head>
<body>
    <main class="an">
        <div class="an-top">
            <img src="{{ asset('icons/icon-192.png') }}" alt="Igreja Cidade de David">
            <a href="https://igrejaicd.pt/#announcements"><i class="fa-solid fa-arrow-left"></i> {{ __('Back to the site') }}</a>
        </div>

        @if ($sent && isset($thanks[$sent]))
            @php([$title, $message, $note] = $thanks[$sent])
            <div class="an-card an-done">
                <div class="icon"><i class="fa-solid fa-check"></i></div>
                <strong>{{ $title }}</strong>
                <p>{{ $message }}</p>
                @if ($note)<p class="note">{{ $note }}</p>@endif
                <div class="actions">
                    <a href="{{ route('site.announcement') }}" class="an-btn is-light"><i class="fa-solid fa-plus"></i> {{ __('Send another announcement') }}</a>
                    <a href="https://igrejaicd.pt/#announcements" class="an-btn">{{ __('Back to the site') }}</a>
                </div>
            </div>
        @else
            <h1>{{ __('Announce') }}</h1>
            <p class="lead">{{ __('Members of the church can announce a service, a product, a donation or their résumé. It shows on the site once approved by the team.') }}</p>

            <form method="POST" action="{{ route('announcement.register') }}" enctype="multipart/form-data" class="an-card"
                x-data="announcementForm(@js(['type' => old('type', ''), 'checkUrl' => route('member.checkDocumentNumber', '__NIF__')]))" @submit="sending = true">
                @csrf
                {{-- Left empty by people: a filled one is a bot --}}
                <div class="an-hp" aria-hidden="true"><label for="website">Website</label><input id="website" name="website" tabindex="-1" autocomplete="off"></div>

                <div class="an-field">
                    <label for="member_document">{{ __('Your NIF (member)') }}</label>
                    <input id="member_document" name="member_document" class="an-input" value="{{ old('member_document') }}" required inputmode="numeric" maxlength="9" autocomplete="off"
                        x-model="nif" @change="checkNif()">
                    <p class="an-ok" x-show="nifState === true" x-cloak><i class="fa-solid fa-check"></i> {{ __('Member found.') }}</p>
                    <p class="an-error" x-show="nifState === false" x-cloak>{{ __('You need to be a member to announce. If you are, check the NIF or talk to us.') }}</p>
                    @error('member_document')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field">
                    <span class="label">{{ __('Type') }}</span>
                    <div class="an-types" role="radiogroup" aria-label="{{ __('Type') }}">
                        @foreach ($types as $value => $label)
                            <label><input type="radio" name="type" value="{{ $value }}" x-model="type" required><span><i class="fa-solid {{ $icons[$value] ?? 'fa-bullhorn' }}"></i>{{ $label }}</span></label>
                        @endforeach
                    </div>
                    @error('type')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field">
                    <label for="title">{{ __('Title') }}</label>
                    <input id="title" name="title" class="an-input" value="{{ old('title') }}" required maxlength="255" :placeholder="hints[type]?.title || ''">
                    @error('title')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field" x-data="{ help: false }">
                    <label for="description">{{ __('Description') }}</label>
                    <textarea id="description" name="description" class="an-input" required maxlength="5000" :placeholder="hints[type]?.description || ''">{{ old('description') }}</textarea>
                    <button type="button" class="an-help" @click="help = !help"><i class="fa-regular fa-lightbulb"></i> Precisa de ajuda para escrever?</button>
                    <div class="an-tip" x-show="help" x-cloak>
                        Se tiver dificuldade para escrever um <strong>título</strong> ou uma <strong>descrição</strong> clara para seu anúncio, você pode usar o <a href="https://chat.openai.com/" target="_blank" rel="noopener">ChatGPT</a> como assistente.
                        <code>"Me ajuda a escrever um título e descrição para um anúncio de venda de peças de cerâmica artesanal."</code>
                    </div>
                    @error('description')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field">
                    <label for="contact">{{ __('Telephone Number') }}</label>
                    <input id="contact" name="contact" type="tel" class="an-input" value="{{ old('contact') }}" required inputmode="tel" placeholder="912 345 678">
                    <small>{{ __('Shown with the announcement, for whoever is interested.') }}</small>
                    @error('contact')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field">
                    <label for="link">{{ __('Link') }} <span>({{ __('optional') }})</span></label>
                    <input id="link" name="link" class="an-input" value="{{ old('link') }}" maxlength="255" :placeholder="hints[type]?.link || 'https://'">
                    @error('link')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <div class="an-field">
                    <label for="image_path">{{ __('Image or PDF') }} <span>({{ __('optional') }})</span></label>
                    <input id="image_path" name="image_path" type="file" accept=".pdf,image/jpeg,image/png" class="an-input">
                    @include('announcements::partials.image-cropper', ['input' => 'image_path'])
                    <small>{{ __('JPG, PNG or PDF, up to 5 MB.') }}</small>
                    @error('image_path')<p class="an-error">{{ $message }}</p>@enderror
                </div>

                <button type="submit" class="an-btn" :disabled="sending"><i class="fa-solid" :class="sending ? 'fa-spinner fa-spin' : 'fa-paper-plane'"></i> <span x-text="sending ? @js(__('Sending…')) : @js(__('Send for approval'))">{{ __('Send for approval') }}</span></button>
            </form>
            <p class="an-foot">{{ __('Not a member yet?') }} <a href="{{ route('site.member') }}">{{ __('To be a member!') }}</a></p>
        @endif
    </main>

    <script>
        // The form: the NIF checked once per value (not on every key), sending once, and examples by type.
        function announcementForm({ type, checkUrl }) {
            return {
                type, nif: '', nifState: null, checked: null, sending: false,
                hints: {
                    service: { title: 'Ex. Pintor Profissional', description: 'Ex. Pintor com experiência em pintura residencial e comercial, oferecendo serviços de alta qualidade com acabamento profissional. Trabalho com pintura interna e externa, aplicação de massa corrida, textura, grafiato e reparos em paredes. Atendimento rápido, limpo e com foco na satisfação do cliente.' },
                    product: { title: 'Ex. Produtos de cerâmica', description: 'Oferecemos uma variedade de produtos em cerâmica para decoração, utilitários e acabamento. Itens feitos com qualidade e ótimo custo-benefício. Consulte disponibilidade e modelos' },
                    resume: { title: 'Ex. Restauração', description: 'Ex. Sou uma pessoa motivada e entusiasta, em busca de uma oportunidade na área de restauração. Tenho interesse em aprender e crescer no setor, buscando uma posição onde possa desenvolver minhas habilidades em atendimento ao cliente e trabalho em equipe. Estou disponível para diferentes turnos e pronto para começar imediatamente. Sou pontual, comunicativo e tenho vontade de aprender novas tarefas.', link: 'https://www.linkedin.com/in/****/' },
                    donation: { title: 'Ex. Doação de Roupas', description: 'Ex. Doação de roupas para crianças' },
                },
                async checkNif() {
                    const nif = this.nif.replace(/\D/g, '');
                    // Only a whole NIF, and each one only once.
                    if (nif.length !== 9 || nif === this.checked) return;
                    this.checked = nif;
                    try {
                        const response = await fetch(checkUrl.replace('__NIF__', nif), { headers: { Accept: 'application/json' } });
                        this.nifState = response.ok ? (await response.json()).exists : null;
                    } catch (e) { this.nifState = null; }
                },
            };
        }
    </script>
</body>
</html>
