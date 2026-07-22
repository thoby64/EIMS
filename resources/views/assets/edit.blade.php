@extends('layouts.app')

@section('title', 'Edit '.$asset->asset_tag)
@section('heading', 'Edit asset')

@section('content')
<div class="mx-auto max-w-6xl pt-4">
    <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-800"><p class="font-extrabold">Editing is available only before first assignment.</p><p class="mt-1 text-xs">After this asset enters an assignment workflow, its identity and properties become permanently locked to protect accountability.</p></div>
    <form method="POST" action="{{ route('assets.update',$asset) }}" class="space-y-6">
        @csrf @method('PATCH')
        @if ($errors->any())<div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-700"><p class="font-extrabold">Please correct the following.</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <section class="eims-card p-6">
            <div class="flex items-center gap-4"><div class="grid size-12 place-items-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-700 text-xs font-black text-white">{{ $asset->category->code }}</div><div><p class="font-mono text-xs font-bold text-indigo-500">{{ $asset->asset_tag }}</p><h2 class="font-extrabold text-eims-ink">{{ $asset->category->group->name }} · {{ $asset->category->name }}</h2><p class="mt-1 text-xs text-slate-400">Classification and EIMS tag cannot be changed through ordinary editing.</p></div></div>
        </section>

        <section class="eims-card p-6">
            <h2 class="mb-6 text-lg font-extrabold text-eims-ink">Core property information</h2>
            <div class="grid gap-5 md:grid-cols-2">
                @foreach ([['name','Property name',$asset->name,true],['manufacturer','Manufacturer',$asset->manufacturer,false],['brand','Brand',$asset->brand,false],['model','Model',$asset->model,false],['serial_number','Manufacturer serial number',$asset->serial_number,false],['external_barcode','Existing barcode value',$externalBarcode,false]] as [$field,$label,$current,$required])
                    <div><label class="text-sm font-bold text-slate-600">{{ $label }} @if($required)<span class="text-red-500">*</span>@endif</label><input name="{{ $field }}" value="{{ old($field,$current) }}" @required($required) class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100"></div>
                @endforeach
                <div class="md:col-span-2"><label class="text-sm font-bold text-slate-600">Description</label><textarea name="description" rows="3" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('description',$asset->description) }}</textarea></div>
            </div>
        </section>

        @if($asset->category->attributes->isNotEmpty())
        <section class="eims-card p-6"><h2 class="mb-6 text-lg font-extrabold text-eims-ink">Category-specific information</h2><div class="grid gap-5 md:grid-cols-2">
            @foreach($asset->category->attributes as $attribute)
                @php($current=old('attributes.'.$attribute->id,$attributeValues[$attribute->id] ?? ''))
                <div><label class="text-sm font-bold text-slate-600">{{ $attribute->name }} @if($attribute->unit)({{ $attribute->unit }})@endif @if($attribute->pivot->is_required)*@endif</label>
                    @if($attribute->data_type==='select')<select name="attributes[{{ $attribute->id }}]" @required($attribute->pivot->is_required) class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">Select</option>@foreach($attribute->options ?? [] as $option)<option value="{{ $option }}" @selected((string)$current===(string)$option)>{{ ucfirst($option) }}</option>@endforeach</select>
                    @else<input type="{{ $attribute->data_type==='number'?'number':($attribute->data_type==='date'?'date':'text') }}" @if($attribute->data_type==='number') step="any" @endif name="attributes[{{ $attribute->id }}]" value="{{ $current }}" @required($attribute->pivot->is_required) class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">@endif
                </div>
            @endforeach
        </div></section>
        @endif

        <section class="eims-card p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="text-lg font-extrabold text-eims-ink">Additional properties</h2><p class="mt-1 text-xs text-slate-400">Flexible name and value details unique to this property.</p></div><button type="button" data-add-property class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-extrabold text-indigo-600">＋ Add another property</button></div>
            <div data-property-list class="mt-5 space-y-3"></div><p data-property-empty class="mt-5 rounded-xl bg-slate-50 px-4 py-5 text-center text-sm text-slate-400">No additional properties added.</p>
        </section>

        <section class="eims-card p-6"><h2 class="mb-6 text-lg font-extrabold text-eims-ink">Lifecycle information</h2><div class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
            <div><label class="text-sm font-bold text-slate-600">Condition *</label><select name="condition" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">@foreach(['excellent','good','fair','damaged','beyond_repair'] as $option)<option value="{{ $option }}" @selected(old('condition',$asset->condition)===$option)>{{ ucfirst(str_replace('_',' ',$option)) }}</option>@endforeach</select></div>
            <div><label class="text-sm font-bold text-slate-600">Ownership *</label><select name="ownership_type" required class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">@foreach(['purchased','donated','leased','borrowed','grant_funded'] as $option)<option value="{{ $option }}" @selected(old('ownership_type',$asset->ownership_type)===$option)>{{ ucfirst(str_replace('_',' ',$option)) }}</option>@endforeach</select></div>
            <div><label class="text-sm font-bold text-slate-600">Location</label><select name="location_id" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"><option value="">Not assigned</option>@foreach($locations as $location)<option value="{{ $location->id }}" @selected(old('location_id',$asset->location_id)==$location->id)>{{ $location->name }}</option>@endforeach</select></div>
            <div><label class="text-sm font-bold text-slate-600">Acquisition date</label><input type="date" name="acquired_on" value="{{ old('acquired_on',optional($asset->acquired_on)->format('Y-m-d')) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
            <div><label class="text-sm font-bold text-slate-600">Acquisition cost</label><input type="number" min="0" step="0.01" name="acquisition_cost" value="{{ old('acquisition_cost',$asset->acquisition_cost) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
            <div><label class="text-sm font-bold text-slate-600">Currency</label><input name="currency" maxlength="3" required value="{{ old('currency',$asset->currency) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm uppercase"></div>
            <div><label class="text-sm font-bold text-slate-600">Warranty expiry</label><input type="date" name="warranty_expires_on" value="{{ old('warranty_expires_on',optional($asset->warranty_expires_on)->format('Y-m-d')) }}" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm"></div>
            <div class="md:col-span-2"><label class="text-sm font-bold text-slate-600">Internal notes</label><textarea name="notes" rows="2" class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-3 text-sm">{{ old('notes',$asset->notes) }}</textarea></div>
        </div></section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a href="{{ route('assets.show',$asset) }}" class="rounded-xl border border-slate-200 px-6 py-3 text-center text-sm font-bold text-slate-500">Cancel</a><button class="rounded-xl bg-gradient-to-r from-indigo-600 to-purple-700 px-7 py-3 text-sm font-extrabold text-white shadow-lg shadow-indigo-500/20">Save asset changes</button></div>
    </form>
</div>
<script>
(() => {
    const initial = @json(old('custom_properties', $asset->customProperties->map(fn($property)=>['name'=>$property->name,'value'=>$property->value])->values()));
    const list=document.querySelector('[data-property-list]'), empty=document.querySelector('[data-property-empty]'); let index=0;
    const escape=value=>String(value??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));
    function add(property={}) { const i=index++; list.insertAdjacentHTML('beforeend',`<div data-property-row class="grid gap-3 rounded-xl border border-slate-100 bg-slate-50/60 p-3 sm:grid-cols-[1fr_1.5fr_auto] sm:items-end"><div><label class="text-xs font-bold text-slate-500">Property name</label><input name="custom_properties[${i}][name]" value="${escape(property.name||'')}" pattern="[A-Za-z0-9 ]+" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm"></div><div><label class="text-xs font-bold text-slate-500">Property value</label><input name="custom_properties[${i}][value]" value="${escape(property.value||'')}" pattern="[A-Za-z0-9 ]+" required class="mt-1 w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm"></div><button type="button" data-remove-property class="rounded-lg px-3 py-2.5 text-sm font-bold text-red-500">Remove</button></div>`); empty.classList.add('hidden'); }
    document.querySelector('[data-add-property]').addEventListener('click',()=>add()); list.addEventListener('click',event=>{if(event.target.matches('[data-remove-property]')){event.target.closest('[data-property-row]').remove();empty.classList.toggle('hidden',list.children.length>0);}}); Object.values(initial).forEach(add);
})();
</script>
@endsection
