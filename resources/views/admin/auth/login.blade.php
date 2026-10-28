<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
</head>
<body>
    <h1>Admin Login</h1>

    <form method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <input
            type="email"
            name="email"
            placeholder="Admin email"
            required
        >
        <input
            type="password"
            name="password"
            placeholder="Password"
            required
        >

        <button type="submit">Login</button>
    </form>

    @if ($errors->any())
        <p>{{ $errors->first() }}</p>
    @endif
</body>
</html>