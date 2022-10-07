<html>
<head>
    <title>Batman listening things - Video Generator</title>
</head>
<body>
<form action="{{ route("generate") }}" method="POST" enctype="multipart/form-data">
    @csrf

    <input type="file" name="audio">

    <button type="submit">Genera</button>
</form>
</body>
</html>
