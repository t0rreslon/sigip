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
      <td>{{ \App\Support\Auditoria::OPERACIONES[$registro->operacion] ?? $registro->operacion }}</td>
      <td>{{ $registro->usuario }}</td>
      <td><x-detalle-cambio :registro="$registro" /></td>
    </tr>
    @empty
    <tr>
      <td colspan="4">No hay cambios registrados.</td>
    </tr>
    @endforelse
  </tbody>
</table>
