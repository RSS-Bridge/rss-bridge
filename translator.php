<!DOCTYPE html><html lang="en"><head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="description" content="RSS-Bridge" />
	<title>RSS-Bridge</title>
	<link href="static/style.css" rel="stylesheet">
	<link rel="icon" type="image/png" href="static/favicon.png">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js"></script>
    <script>
            $(document).ready(function(){
            $('#searchfield').change(function(){
                //Selected value
                var inputValue = $(this).val();

                //Ajax for calling php function
                $.post('index.php?action=translate&url=' + inputValue, function(data){
                    document.getElementById("resultfield").value = data;
                });
            });
    });
    </script>
</head>
<body>
<section class="searchbar">
	<h3>Search</h3>
	<input type="text" name="searchfield" id="searchfield" placeholder="" value="">
	<input type="text" name="result" id="resultfield" placeholder="" value="">
</section>
</body>
</html>
