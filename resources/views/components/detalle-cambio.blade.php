@props(['registro'])
{{-- Campos que cambiaron en un registro de auditoria_tablas: valor anterior → valor nuevo. --}}
@php
  $mostrar = fn ($v) => is_bool($v) ? ($v ? 'Sí' : 'No') : (is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : ($v ?? '—'));
@endphp
@if ($registro->operacion === 'INSERT')
<ul class="lista-cambios">
  @forelse ($registro->nuevos as $campo => $valor)
  <li><strong>{{ str_replace('_', ' ', $campo) }}:</strong> {{ $mostrar($valor) }}</li>
  @empty
  <li>Registro inicial</li>
  @endforelse
</ul>
@else
<ul class="lista-cambios">
  @foreach ($registro->nuevos as $campo => $valor)
  <li>
    <strong>{{ str_replace('_', ' ', $campo) }}:</strong>
    {{ $mostrar($registro->anteriores[$campo] ?? null) }} → {{ $mostrar($valor) }}
  </li>
  @endforeach
</ul>
@endif
