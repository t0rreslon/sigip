@props(['p', 'etiqueta' => 'registros'])
<div class="paginacion">
  <small>Mostrando {{ $p->firstItem() ?? 0 }}–{{ $p->lastItem() ?? 0 }} de {{ $p->total() }} {{ $etiqueta }}</small>
  <div>
    @if ($p->onFirstPage())
    <button type="button" class="btn-pag" disabled>Anterior</button>
    @else
    <a href="{{ $p->previousPageUrl() }}" class="btn-pag">Anterior</a>
    @endif

    @foreach ($p->getUrlRange(max(1, $p->currentPage() - 2), min($p->lastPage(), $p->currentPage() + 2)) as $pagina => $url)
    <a href="{{ $url }}" class="btn-pag {{ $pagina == $p->currentPage() ? 'actual' : '' }}">{{ $pagina }}</a>
    @endforeach

    @if ($p->hasMorePages())
    <a href="{{ $p->nextPageUrl() }}" class="btn-pag">Siguiente</a>
    @else
    <button type="button" class="btn-pag" disabled>Siguiente</button>
    @endif
  </div>
</div>
