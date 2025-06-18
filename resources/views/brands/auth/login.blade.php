<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inloggen bij Papier en Versier</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96 text-center">
        <img src="/images/logo.png" alt="Papier en Versier" class="mx-auto mb-4 h-16">
        <h2 class="text-2xl font-semibold mb-4">Inloggen bij Papier en Versier</h2>
        
        <form action="/login" method="POST" class="space-y-4">
            <input type="email" name="email" placeholder="E-mailadres" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <input type="password" name="password" placeholder="Wachtwoord" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">Inloggen</button>
        </form>
        
        <a href="/password/reset" class="block mt-4 text-blue-500 hover:underline">Wachtwoord vergeten?</a>
    </div>
</body>
</html>
 
