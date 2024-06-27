<div class="relative">
    <select name="{{ $name }}" {!! $attributes->merge(['class' => 'block w-full bg-white border border-gray-300 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary mt-1']) !!}>
        <option value="" {{ $selected ? '' : 'selected' }}>{{ $placeholder }}</option>
        @foreach ($options as $index => $value)
            <option value="{{ $index }}" {{ $selected == $index ? 'selected' : '' }}>{{ $value }}</option>
        @endforeach
    </select>
</div>
