@props([
    'class' => '',
])
{{--
  Kullanım: <x-layout.dark-toggle />
  Alpine.js ile dark mode toggle — localStorage'a kaydeder
--}}
<button
    class="ds-dark-toggle {{ $class }}"
    x-data
    @click="
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('mentorde-theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    "
    title="Dark/Light Mod"
    type="button"
></button>

<script>
// Sayfa yüklendiğinde tema uygula
(function(){
    var t = localStorage.getItem('mentorde-theme');
    if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
})();
</script>
