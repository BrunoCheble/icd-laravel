<div class="flex items-center align-center">
    <div class="w-1/3 px-4">
        <img class="w-full h-auto rounded-lg shadow-lg" :src="selectedMember.url_photo" alt="Member Photo" />
    </div>
    <div class="w-2/3 p-3">
        <dl class="divide-y divide-gray-100">
            <form method="POST" action="{{ route('financials.update', $financial->id) }}"  role="form" enctype="multipart/form-data">
                {{ method_field('PATCH') }}
                @csrf
                @include('financial.form')
            </form>
        </dl>
    </div>
</div>
