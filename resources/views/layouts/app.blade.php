<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('page_title', 'Dashboard')</title> <!-- Dynamische titel -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/flowbite@1.4.0/dist/flowbite.min.css" />
   <!-- <link rel="stylesheet" href="https://unpkg.com/flowbite@1.4.0/dist/flowbite.min.css" />-->
    <link rel="stylesheet" href="flowbite.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <!--<script src="https://unpkg.com/flowbite@1.4.0/dist/flowbite.min.js"></script>-->
    <script src="https://unpkg.com/feather-icons"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/nl.js"></script>

    @yield('style')
    @yield('scripts')
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        @include('layouts.menu')

        <!-- Content Area -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow flex items-center justify-between p-4 h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-semibold ml-4">@yield('page_title', 'Welkom')</h1> <!-- Dynamische paginatitel -->
                </div>
                <div class="flex items-center space-x-4 relative">
                    <!-- Profielicoon -->
                    <div class="flex items-center cursor-pointer" id="profile-menu">
                        <svg class="w-8 h-8 text-gray-600" data-feather="user"></svg>
                        <span class="ml-2 text-gray-800">{{ Auth::user()->name }}</span> <!-- Naam van de gebruiker -->
                    </div>
                    <div class="absolute right-0 hidden bg-white border rounded shadow-lg w-40 mt-16 z-10" id="profile-options">
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Profiel</a>
                        <a href="#" class="block px-4 py-2 text-gray-800 hover:bg-gray-100">Uitloggen</a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6 flex justify-left">
                <div class="max-w-6xl w-full">
                    @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {!! nl2br(e(session('success'))) !!}
                    </div>
                @endif
                @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                    </div>
                @endif
                    <!-- Fouten -->
                    @if ($errors->any())
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        
                           <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>
        </div>
    </div>
    
    <script>
        feather.replace();

        // Toggle submenu
        document.querySelectorAll('.menu-item').forEach(item => {
            item.addEventListener('click', () => {
                const submenu = item.querySelector('.submenu');
                if (submenu) {
                    submenu.classList.toggle('hidden');
                }
            });
        });

        // Toggle profile options
        const profileMenu = document.getElementById('profile-menu');
        const profileOptions = document.getElementById('profile-options');

        profileMenu.addEventListener('click', (event) => {
            event.stopPropagation(); // Stop click from propagating to the window
            profileOptions.classList.toggle('hidden');
        });

        // Close profile options when clicking outside
        window.addEventListener('click', (event) => {
            if (!profileMenu.contains(event.target)) {
                profileOptions.classList.add('hidden');
            }
        });

        // data bij default op NL + namen
        document.addEventListener('DOMContentLoaded', function() {
            flatpickr("#date", {
                locale: "nl",
                dateFormat: "d-m-Y", // Bijvoorbeeld: 27-03-2025
            });
            flatpickr("#due_date", {
                locale: "nl",
                dateFormat: "d-m-Y",
            });
            flatpickr("#start_date", {
                locale: "nl",
                dateFormat: "d-m-Y",
            });
            flatpickr("#end_date", {
                locale: "nl",
                dateFormat: "d-m-Y",
            });
        });

    </script>
</body>
</html>
