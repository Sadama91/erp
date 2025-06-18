<div class="bg-white w-64 flex-shrink-0 border-r">
    <!-- Logo -->
    <div class="p-4 border-b">
        <img src="{{ asset('logo.jpeg') }}" alt="Logo" class="w-20 h-20">
    </div>
    <!-- Navigation -->
    <nav class="px-4 mt-4">
        <ul class="space-y-2">
            <!-- Dashboard -->
            <li>
                <a href="{{ route('dashboard') }}" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="home"></svg>
                    <span class="ml-3">Dashboard</span>
                </a>
            </li>
            <!-- Producten -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="package"></svg>
                    <span class="ml-3">Producten</span>
                </a>
                <ul class="submenu {{ request()->routeIs('products.*') || request()->routeIs('subgroups.*') || request()->routeIs('categories.*') || request()->routeIs('tags.*') || request()->routeIs('brands.*') || request()->routeIs('locations.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('products.index') }}" class="text-gray-600 hover:text-primary">Overzicht</a></li>
                    <li><a href="{{ route('subgroups.index') }}" class="text-gray-600 hover:text-primary">Subgroepen</a></li>
                    <li><a href="{{ route('categories.index') }}" class="text-gray-600 hover:text-primary">Categorieën</a></li>
                    <li><a href="{{ route('tags.index') }}" class="text-gray-600 hover:text-primary">Tags</a></li>
                    <li><a href="{{ route('brands.index') }}" class="text-gray-600 hover:text-primary">Merken</a></li>
                    <li><a href="{{ route('locations.index') }}" class="text-gray-600 hover:text-primary">Locaties</a></li>
                </ul>
            </li>
            <!-- Afbeeldingen -->
            <li>
                <a href="{{ route('image.index') }}" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="image"></svg>
                    <span class="ml-3">Afbeeldingen</span>
                </a>
            </li>
            <!-- Orders -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="shopping-cart"></svg>
                    <span class="ml-3">Orders</span>
                </a>
                <ul class="submenu {{ request()->routeIs('orders.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-primary">Overzicht</a></li>
                    <li><a href="{{ route('orders.create') }}" class="text-gray-600 hover:text-primary">Aanmaken</a></li>
                </ul>
            </li>
            <!-- Voorraad -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="archive"></svg>
                    <span class="ml-3">Voorraad</span>
                </a>
                <ul class="submenu {{ request()->routeIs('inventory.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-primary">Overzicht</a></li>
                    <!-- Eventueel extra voorraad-gerelateerde links -->
                </ul>
            </li>
            <!-- Inkoop -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="dollar-sign"></svg>
                    <span class="ml-3">Inkoop</span>
                </a>
                <ul class="submenu {{ request()->routeIs('purchases.*') || request()->routeIs('suppliers.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('purchases.index') }}" class="text-gray-600 hover:text-primary">Overzicht</a></li>
                    <li><a href="{{ route('suppliers.index') }}" class="text-gray-600 hover:text-primary">Leveranciers</a></li>
                </ul>
            </li>
            <!-- Financieel -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="bar-chart-2"></svg>
                    <span class="ml-3">Financieel</span>
                </a>
                <ul class="submenu {{ request()->is('financial/*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('financial.invoices.index') }}" class="text-gray-600 hover:text-primary">Facturen</a></li>
                    <li><a href="{{ route('financial.accounts.index') }}" class="text-gray-600 hover:text-primary">Bankrekeningen</a></li>
                    <li><a href="{{ route('financial.transactions.index') }}" class="text-gray-600 hover:text-primary">Transacties</a></li>
                    <li><a href="{{ route('financial.debt-overview') }}" class="text-gray-600 hover:text-primary">Schulden</a></li>
                    <li><a href="{{ route('financial.vat.index') }}" class="text-gray-600 hover:text-primary">BTW</a></li>
                    <li><a href="{{ route('financial.balance_sheet.index') }}" class="text-gray-600 hover:text-primary">Balans</a></li>
                </ul>
            </li>
            <!-- Rapporten -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="file-text"></svg>
                    <span class="ml-3">Rapporten</span>
                </a>
                <ul class="submenu {{ request()->routeIs('tax_preparations.*') || request()->routeIs('reports.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('reports.index') }}"" class="text-gray-600 hover:text-primary">Rapporten</a></li>
                    <li><a href="" class="text-gray-600 hover:text-primary">Belastingen</a></li>
                </ul>
            </li>
            <!-- Instellingen -->
            <li class="menu-item">
                <a href="#" class="flex items-center p-2 text-gray-800 hover:bg-gray-200 rounded-lg">
                    <svg class="w-6 h-6 text-gray-500" data-feather="settings"></svg>
                    <span class="ml-3">Instellingen</span>
                </a>
                <ul class="submenu {{ request()->routeIs('settings.*') || request()->routeIs('parameters.*') || request()->routeIs('import.*') ? 'block' : 'hidden' }} ml-4 space-y-1">
                    <li><a href="{{ route('settings.index')}}" class="text-gray-600 hover:text-primary">Algemeen</a></li>
                    <li><a href="{{ route('parameters.index') }}" class="text-gray-600 hover:text-primary">Parameters</a></li>
                    <li><a href="{{ route('import.index') }}" class="text-gray-600 hover:text-primary">Data import</a></li>
                </ul>
            </li>
        </ul>
    </nav>
</div>
