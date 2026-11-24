@props(['registros'])
{{-- Historial de cambios guardado en auditoria_tablas (HU-160, 193, 194, 197). --}}
<table class="tabla">
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Acción</th>
      <th>Realizado por</th>
      <th>Cambios</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($registros as $registro)
    <tr>
      <td>{{ \Illuminate\Support\Carbon::parse($registro->creado_en)->format('d/m/Y h:i a') }}</td>
      <td>{{ ['INSERT' => 'Registro', 'UPDATE' => 'Modificación', 'DELETE' => 'Eliminación', 'ESTADO' => 'Cambio de estado'][$registro->operacion] ?? $registro->operacion }}</td>
      <td>{{ $registro->usuario }}</td>
      <td>
        @if ($registro->operacion === 'INSERT')
        Registro inicial
        @else
        <ul class="lista-cambios">
          @foreach ($registro->nuevos as $campo => $valor)
          <li>
            <strong>{{ str_replace('_', ' ', $campo) }}:</strong>
            {{ is_bool($registro->anteriores[$campo] ?? null) ? (($registro->anteriores[$campo]) ? 'Sí' : 'No') : ($registro->anteriores[$campo] ?? '—') }}
            →
            {{ is_bool($valor) ? ($valor ? 'Sí' : 'No') : ($valor ?? '—') }}
          </li>
          @endforeach
        </ul>
        @endif
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="4">No hay cambios registrados.</td>
    </tr>
    @endforelse
  </tbody>
</table>
