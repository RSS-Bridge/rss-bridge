<!DOCTYPE html>

<html>
<head>
    <link rel="stylesheet" href="static/bootstrap.min.css">
    <link
        rel="stylesheet"
        href="https://use.fontawesome.com/releases/v5.6.3/css/all.css"
        integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/"
        crossorigin="anonymous">
    <link rel="stylesheet" href="static/connectivity.css">
    <script src="static/connectivity.js" type="text/javascript"></script>
</head>
<body>
<div id="main-content" class="container">
    <div class="progress">
        <div class="progress-bar" role="progressbar" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
    </div>
    <div id="status-message" class="sticky-top alert alert-primary alert-dismissible fade show" role="alert">
        <i id="status-icon" class="fas fa-sync"></i>
        <span>...</span>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close" onclick="stopConnectivityChecks()">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <input type="text" class="form-control" id="search" onkeyup="search()" placeholder="Search for bridge..">
</div>
</body>
</html>