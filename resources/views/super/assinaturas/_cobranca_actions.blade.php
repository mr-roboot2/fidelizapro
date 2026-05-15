@if ($c->status !== 'pago')
    <div class="flex items-center gap-1 shrink-0">
        @if ($c->status !== 'cancelado')
            <form action="{{ route('super.cobrancas.cancelar', $c) }}" method="POST"
                  onsubmit="return confirm('Cancelar essa cobrança? Tenta cancelar também no gateway.')">
                @csrf
                <button type="submit" title="Cancelar cobrança"
                        class="w-8 h-8 inline-flex items-center justify-center rounded hover:bg-amber-50 text-amber-600">
                    <i class="ri-close-circle-line"></i>
                </button>
            </form>
        @endif
        <form action="{{ route('super.cobrancas.excluir', $c) }}" method="POST"
              onsubmit="return confirm('EXCLUIR essa cobrança permanentemente? Essa ação não pode ser desfeita.')">
            @csrf @method('DELETE')
            <button type="submit" title="Excluir cobrança"
                    class="w-8 h-8 inline-flex items-center justify-center rounded hover:bg-rose-50 text-rose-600">
                <i class="ri-delete-bin-line"></i>
            </button>
        </form>
    </div>
@endif
