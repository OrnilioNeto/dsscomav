<div class="flex gap-1 mb-6 border-b border-gray-200">
    <a href="{{ route('admin.lembretes.disparo') }}"
       class="px-4 py-2 -mb-px border-b-2 font-semibold text-sm transition
              {{ request()->routeIs('admin.lembretes.disparo') ? 'border-green-600 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        <i class="fas fa-paper-plane mr-1"></i>Disparo
    </a>
    <a href="{{ route('admin.lembretes.index') }}"
       class="px-4 py-2 -mb-px border-b-2 font-semibold text-sm transition
              {{ request()->routeIs('admin.lembretes.index') ? 'border-green-600 text-green-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
        <i class="fas fa-history mr-1"></i>Histórico
    </a>
</div>
