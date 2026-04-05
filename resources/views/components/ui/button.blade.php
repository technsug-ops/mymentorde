@props(['type' => 'alt', 'href' => null, 'loading' => false, 'disabled' => false])
@php $tag = $href ? 'a' : 'button'; @endphp
<{{ $tag }} class="btn {{ $type }}" @if($href) href="{{ $href }}" @endif @if($disabled||$loading) disabled @endif {{ $attributes }}>{{ $slot }}</{{ $tag }}>
