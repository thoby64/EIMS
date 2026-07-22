@props(['name'])
<svg {{ $attributes->merge(['class' => 'size-5']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($name)
@case('home')<path d="M3 11.5 12 4l9 7.5"/><path d="M5.5 10v10h13V10M9 20v-6h6v6"/>@break
@case('asset')<path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="m4 7 8 4 8-4v10l-8 4-8-4V7Z"/><path d="M12 11v10"/>@break
@case('scan')<path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3"/><path d="M8 9h8v6H8zM10 9v6M14 9v6"/>@break
@case('assignment')<path d="M8 4h8M9 3v3M15 3v3"/><rect x="5" y="5" width="14" height="16" rx="2"/><path d="m9 13 2 2 4-5"/>@break
@case('transfer')<path d="M7 7h12m0 0-3-3m3 3-3 3M17 17H5m0 0 3 3m-3-3 3-3"/>@break
@case('handover')<path d="M4 12h5l2 2h4l5-5"/><path d="M4 12v5h13l3-3M8 8l3-3 3 3"/>@break
@case('request')<path d="M6 3h9l3 3v15H6z"/><path d="M14 3v4h4M9 12h6M12 9v6"/>@break
@case('maintenance')<path d="m14.5 6.5 3-3a4 4 0 0 1-5 5L5 16l3 3 7.5-7.5a4 4 0 0 1 5-5l-3 3-3-3Z"/>@break
@case('inspection')<path d="M9 5h6M10 3h4l1 3H9l1-3Z"/><path d="M7 5H5v16h14V5h-2M8 11h3M8 15h3M14 11l1 1 2-2M14 15l1 1 2-2"/>@break
@case('disposal')<path d="M4 7h16M9 7V4h6v3M6 7l1 14h10l1-14M10 11v6M14 11v6"/>@break
@case('report')<path d="M5 21V10h4v11M10 21V4h4v17M15 21v-7h4v7M3 21h18"/>@break
@case('audit')<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/>@break
@case('admin')<circle cx="9" cy="8" r="3"/><path d="M3.5 20v-2a5.5 5.5 0 0 1 11 0v2M18 9v6M15 12h6"/>@break
@case('bell')<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>@break
@case('check')<circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/>@break
@case('layers')<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 12 9 5 9-5M3 16l9 5 9-5"/>@break
@case('category')<path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z"/>@break
@case('location')<path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/>@break
@case('computer')<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8M12 17v4"/>@break
@case('chair')<path d="M7 12V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v7M5 10v6h14v-6M7 16v5M17 16v5"/>@break
@case('building')<path d="M4 21V7l8-4 8 4v14M8 9h2M14 9h2M8 13h2M14 13h2M10 21v-4h4v4"/>@break
@case('bolt')<path d="m13 2-8 12h7l-1 8 8-12h-7l1-8Z"/>@break
@case('water')<path d="M12 2S5 10 5 15a7 7 0 0 0 14 0c0-5-7-13-7-13Z"/><path d="M9 16a3 3 0 0 0 3 3"/>@break
@case('vehicle')<path d="m5 11 2-5h10l2 5M4 11h16v7H4zM7 18v2M17 18v2"/><circle cx="8" cy="14.5" r="1"/><circle cx="16" cy="14.5" r="1"/>@break
@case('flask')<path d="M9 3h6M10 3v6l-5 9a2 2 0 0 0 1.8 3h10.4a2 2 0 0 0 1.8-3l-5-9V3M8 15h8"/>@break
@case('education')<path d="M4 5.5A3.5 3.5 0 0 1 7.5 2H12v18H7.5A3.5 3.5 0 0 0 4 23V5.5ZM20 5.5A3.5 3.5 0 0 0 16.5 2H12v18h4.5A3.5 3.5 0 0 1 20 23V5.5Z"/>@break
@case('medical')<path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z"/>@break
@case('shield')<path d="M12 3 4 6v5c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-3Z"/><path d="m9 12 2 2 4-5"/>@break
@case('sports')<circle cx="12" cy="12" r="9"/><path d="m8 4 4 3 4-3M7 11l5-4 5 4-2 6H9l-2-6ZM5 17l4 0M15 17h4"/>@break
@case('tools')<path d="m14.5 6.5 3-3a4 4 0 0 1-5 5L5 16l3 3 7.5-7.5a4 4 0 0 1 5-5l-3 3-3-3Z"/>@break
@case('kitchen')<path d="M6 3v7M3 3v5a3 3 0 0 0 6 0V3M6 11v10M15 3v18M15 3c4 2 5 6 5 9h-5"/>@break
@case('library')<path d="M4 4h4v16H4zM10 4h4v16h-4zM16 5l4-1 2 15-4 1-2-15Z"/>@break
@case('leaf')<path d="M20 4C11 4 5 8 5 15a5 5 0 0 0 5 5c7 0 10-7 10-16Z"/><path d="M4 21c3-6 7-9 13-13"/>@break
@case('license')<path d="M6 3h9l3 3v15H6zM14 3v4h4M9 12h6M9 16h4"/><circle cx="17" cy="16" r="2"/><path d="m18.5 17.5 2 2"/>@break
@endswitch
</svg>
