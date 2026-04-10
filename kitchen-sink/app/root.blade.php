<!-- resources/application/root.blade.php -->
<!DOCTYPE html>
<html class="flex flex-col h-svh">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		@vite
	</head>
	<body class="flex flex-col bg-white dark:bg-neutral-950 h-full text-default antialiased">
    <div class="absolute inset-0 bg-repeat pointer-events-none" style="background-image: url(/noise.svg)"></div>
		@hybridly(class: 'flex h-full grow flex-1 overflow-hidden')
	</body>
</html>
