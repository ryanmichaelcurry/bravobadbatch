
<?php
require_once("vendor/apollophp/header.php");

$info = $apollo->select("cadet", ["passphrase"=>$_GET["pass"]])[0];
$feedbackArray = json_decode($info["feedback"]);

?>
<!DOCTYPE html>
<html lang="en">

<head>

  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?php $info["name"] ?></title>

  <!-- Bootstrap core CSS -->
  <link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">

  <!-- Custom fonts for this template -->
  <link href="https://fonts.googleapis.com/css?family=Catamaran:100,200,300,400,500,600,700,800,900" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Lato:100,100i,300,300i,400,400i,700,700i,900,900i" rel="stylesheet">

  <!-- Custom styles for this template -->
  <link href="/css/one-page-wonder.min.css" rel="stylesheet">

</head>

<body>

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark navbar-custom fixed-top">
    <div class="container">
      <a class="navbar-brand" href="/">Bravo Bad Batch</a>
      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto">
          <li class="nav-item">
            <a class="nav-link" data-toggle="modal" data-target="#recieve">Recieve Feedback</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <header class="masthead text-center text-white" style="background-image: url(/img/cover.jpg);">
    <div class="masthead-content">
      <div class="container">
        <h1 class="masthead-heading mb-0">Bravo's</h1>
        <h2 class="masthead-subheading mb-0"><?php echo $info["name"]; ?></h2>
      </div>
    </div>
  </header>

  <section id="feedback">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-md-8 col-sm-10 col-xs-12 mb-1 mt-4">
        
        <?php
            
          foreach ($feedbackArray as $key => $value) {
            $feedback = $apollo->select("feedback", ["id"=>$value])[0];
            echo "<div class='mb-3 card'><div class='card-header'>$feedback[timestamp]</div><div class='card-body'><blockquote class='blockquote mb-0'><p>$feedback[text]</p><footer class='blockquote-footer'><cite title='Source Title'>Anonymous</cite></footer></blockquote></div></div>";
          }
            
          ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="py-5 bg-black">
    <div class="container">
      <p class="m-0 text-center text-white small">Copyright &copy; Ryan Curry 2021</p>
    </div>
    <!-- /.container -->
  </footer>

  <!-- Modal -->
<div class="modal fade" id="recieve" tabindex="-1" role="dialog" aria-labelledby="recieve" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="exampleModalLongTitle">Passphrase</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <form method="POST" action="/login.php">
        <input type="input" class="form-control" id="passphrase" name="passphrase">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        <input type="submit" class="btn btn-primary" value="Login">
      </div>
</form>
    </div>
  </div>
</div>

  <!-- Bootstrap core JavaScript -->
  <script src="/vendor/jquery/jquery.min.js"></script>
  <script src="/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

</body>

</html>