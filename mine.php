<!DOCTYPE html>
<html>
<head>
  <title>Sphinx Search</title>
  <meta name="description" content="" />
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon" />
  <script type="text/javascript" src="js/jquery-2.2.4.min.js"></script>
  <script type="text/javascript" src="js/jquery.livesearch.js"></script>
  <link rel="stylesheet" href="bus.css" />
  <script src="script.js"></script>
  <!--[if lt IE 8]>
  <![endif]-->
</head>
<body>
<h1>Sphinx Search</h1>
<form method="get" action="/" id="live-search-example">
    <div><input type="text" name="s" placeholder="Search"></div>
</form>

<script>
    LiveSearch.init(document.getElementById('live-search-example').querySelector('input[name=s]'), {url: '/?s='});
</script>
</body>
</html>
