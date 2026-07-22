@extends('layouts.app')

@section('title', 'Register Asset')
@section('heading', 'Register property')

@section('content')
<div class="mx-auto max-w-6xl pt-4">
    <form method="POST" action="{{ route('assets.store') }}" class="space-y-6">
        @csrf
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"><p class="font-extrabold">Registration needs attention.</p><ul class="mt-2 list-disc space-y-1 pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="eims-card p-6">
            <div class="mb-6"><p class="text-xs font-bold uppercase tracking-wider text-indigo-500">Classification</p><h2 class="mt-1 text-lg font-extrabold text-eims-ink">What property are you registering?</h2></div>
            <label class="block text-sm font-bold text-slate-600" for="asset_category_id">Asset category <span class="text-red-500">*</span></label>
            <select id="asset_category_id" name="asset_category_id" required class="mt-2 w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">
                <option value="">Select a category</option>
                @foreach ($categories->groupBy('group.name') as $group => $items)
                    <optgroup label="{{ $group }}">@foreach ($items as $category)<option value="{{ $category->id }}" @selected(old('asset_category_id') == $category->id)>{{ $category->name }} · {{ ucfirst($category->tracking_mode) }}</option>@endforeach</optgroup>
                @endforeach
            </select>
            <p id="tracking-mode" class="mt-2 text-xs font-semibold text-slate-400">Selecting a category loads its applicable data fields.</p>
        </section>

        <section class="eims-card p-6">
            <div class="mb-6"><p class="text-xs font-bold uppercase tracking-wider text-purple-500">Identity</p><h2 class="mt-1 text-lg font-extrabold text-eims-ink">Core property information</h2></div>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ([['name','Property name','text',true],['manufacturer','Manufacturer','text',false],['brand','Brand','text',false],['model','Model','text',false],['serial_number','Manufacturer serial number','text',false],['external_barcode','Existing barcode value','text',false]] as [$field,$label,$type,$required])
                    <div><label for="{{ $field }}" class="text-sm font-bold text-slate-600">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label><input id="{{ $field }}" type="{{ $type }}" name="{{ $field }}" value="{{ old($field) }}" @required($required) class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"></div>
                @endforeach
                <div class="md:col-span-2"><label for="description" class="text-sm font-bold text-slate-600">Short description</label><textarea id="description" name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">{{ old('description') }}</textarea></div>
            </div>
        </section>

        <section id="category-fields-card" class="eims-card hidden p-6">
            <div class="mb-6"><p class="text-xs font-bold uppercase tracking-wider text-cyan-500">Category profile</p><h2 class="mt-1 text-lg font-extrabold text-eims-ink">Category-specific information</h2></div>
            <div id="category-fields" class="grid gap-5 md:grid-cols-2"></div>
        </section>

        <section class="eims-card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-xs font-bold uppercase tracking-wider text-amber-500">Flexible details</p><h2 class="mt-1 text-lg font-extrabold text-eims-ink">Additional properties</h2><p class="mt-1 text-xs text-slate-400">Add information unique to this property, such as plate number, fabric type or internal reference.</p></div>
                <button type="button" data-add-property class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-extrabold text-indigo-600">＋ Add another property</button>
            </div>
            <div data-property-list class="mt-5 space-y-3"></div>
            <p data-property-empty class="mt-5 rounded-xl bg-slate-50 px-4 py-5 text-center text-sm text-slate-400">No additional properties added.</p>
        </section>

        <section class="eims-card p-6">
            <div class="mb-6"><p class="text-xs font-bold uppercase tracking-wider text-emerald-500">Lifecycle</p><h2 class="mt-1 text-lg font-extrabold text-eims-ink">Condition, location and acquisition</h2></div>
            <div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                <div><label class="text-sm font-bold text-slate-600">Condition *</label><select name="condition" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="excellent">Excellent</option><option value="good" @selected(old('condition','good')==='good')>Good</option><option value="fair">Fair</option><option value="damaged">Damaged</option><option value="beyond_repair">Beyond repair</option></select></div>
                <div><label class="text-sm font-bold text-slate-600">Ownership *</label><select name="ownership_type" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="purchased">Purchased</option><option value="donated">Donated</option><option value="leased">Leased</option><option value="borrowed">Borrowed</option><option value="grant_funded">Grant funded</option></select></div>
                <div><label class="text-sm font-bold text-slate-600">Location</label><select name="location_id" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">Not assigned</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id')==$location->id)>{{ $location->name }}</option>@endforeach</select></div>
                <div><label class="text-sm font-bold text-slate-600">Acquisition date</label><input type="date" name="acquired_on" value="{{ old('acquired_on') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
                <div><label class="text-sm font-bold text-slate-600">Acquisition cost</label><input type="number" min="0" step="0.01" name="acquisition_cost" value="{{ old('acquisition_cost') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
                <div><label class="text-sm font-bold text-slate-600">Currency</label><input name="currency" value="{{ old('currency','TZS') }}" maxlength="3" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase"></div>
                <div><label class="text-sm font-bold text-slate-600">Warranty expiry</label><input type="date" name="warranty_expires_on" value="{{ old('warranty_expires_on') }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
                <div class="md:col-span-2"><label class="text-sm font-bold text-slate-600">Internal notes</label><textarea name="notes" rows="2" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('notes') }}</textarea></div>
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('assets.index') }}" class="rounded-xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-500">Cancel</a><button class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-7 py-3 text-sm font-extrabold text-white shadow-lg shadow-indigo-500/20">Register and generate EIMS tag</button></div>
    </form>
</div>

<script type="application/json" id="category-schema">{!! json_encode($categorySchema, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
<script>
(() => {
    const schema = JSON.parse(document.getElementById('category-schema').textContent);
    const select = document.getElementById('asset_category_id');
    const container = document.getElementById('category-fields');
    const card = document.getElementById('category-fields-card');
    const mode = document.getElementById('tracking-mode');
    const oldValues = @json(old('attributes', []));
    const oldProperties = @json(old('custom_properties', []));
    const escape = value => String(value ?? '').replace(/[&<>'"]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[character]));
    function render() {
        const selected = schema[select.value];
        container.innerHTML = '';
        if (!selected) { card.classList.add('hidden'); mode.textContent = 'Selecting a category loads its applicable data fields.'; return; }
        mode.textContent = `Tracking mode: ${selected.tracking_mode.replace('_', ' ')}`;
        card.classList.toggle('hidden', selected.attributes.length === 0);
        selected.attributes.forEach(field => {
            const value = oldValues[field.id] ?? '';
            const required = field.required ? 'required' : '';
            const suffix = field.unit ? ` (${escape(field.unit)})` : '';
            let input;
            if (field.type === 'select') {
                input = `<select name="attributes[${field.id}]" ${required} class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">Select</option>${(field.options || []).map(option => `<option value="${escape(option)}" ${String(value) === String(option) ? 'selected' : ''}>${escape(option)}</option>`).join('')}</select>`;
            } else {
                const type = field.type === 'number' ? 'number' : field.type === 'date' ? 'date' : 'text';
                input = `<input type="${type}" ${type === 'number' ? 'step="any"' : ''} name="attributes[${field.id}]" value="${escape(value)}" ${required} class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100">`;
            }
            container.insertAdjacentHTML('beforeend', `<div><label class="text-sm font-bold text-slate-600">${escape(field.name)}${suffix}${field.required ? ' *' : ''}</label>${input}${field.help ? `<p class="mt-1 text-xs text-slate-400">${escape(field.help)}</p>` : ''}</div>`);
        });
    }
    select.addEventListener('change', render); render();

    const propertyList = document.querySelector('[data-property-list]');
    const propertyEmpty = document.querySelector('[data-property-empty]');
    let propertyIndex = 0;
    function addProperty(property = {}) {
        const index = propertyIndex++;
        propertyList.insertAdjacentHTML('beforeend', `<div data-property-row class="grid gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-[1fr_1.5fr_auto] sm:items-end"><div><label class="text-xs font-bold text-slate-500">Property name</label><input name="custom_properties[${index}][name]" value="${escape(property.name || '')}" maxlength="100" pattern="[A-Za-z0-9 ]+" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="e.g. Plate number"></div><div><label class="text-xs font-bold text-slate-500">Property value</label><input name="custom_properties[${index}][value]" value="${escape(property.value || '')}" maxlength="255" pattern="[A-Za-z0-9 ]+" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm" placeholder="e.g. T123ABC"></div><button type="button" data-remove-property class="rounded-lg px-3 py-2.5 text-sm font-bold text-red-500 hover:bg-red-50">Remove</button></div>`);
        propertyEmpty.classList.add('hidden');
    }
    document.querySelector('[data-add-property]').addEventListener('click', () => addProperty());
    propertyList.addEventListener('click', event => { if (event.target.matches('[data-remove-property]')) { event.target.closest('[data-property-row]').remove(); propertyEmpty.classList.toggle('hidden', propertyList.children.length > 0); } });
    Object.values(oldProperties).forEach(addProperty);
})();
</script>
@endsection
