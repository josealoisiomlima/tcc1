<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cadastro - BellaFace</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      background: linear-gradient(135deg, #f8bbd0, #ffe0e9);
      display: flex;
      flex-direction: column;
      min-height: 100vh;
      font-family: 'Segoe UI', sans-serif;
    }

    .navbar {
      background-color: #ec407a !important;
    }

    .navbar-nav .nav-link {
      color: white !important;
      font-weight: 500;
      transition: background 0.3s ease;
    }

    .navbar-nav .nav-link:hover {
      background-color: #d81b60;
      border-radius: 8px;
    }

    .container {
      flex: 1;
      padding-top: 30px;
    }

    .img-fluid {
      border: 6px solid #f06292;
      box-shadow: 0 0 20px rgba(240, 98, 146, 0.5);
      transition: transform 0.3s ease-in-out;
    }

    .img-fluid:hover {
      transform: scale(1.05);
    }

    footer {
      background-color: #fce4ec;
      padding: 20px 0;
      text-align: center;
    }

    footer p {
      margin: 0;
      color: #444;
    }

    h1.text-center {
      font-weight: bold;
      color: #c2185b;
      margin-bottom: 40px;
    }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
  <div class="container">
    <a class="navbar-brand text-white" href="#"><i class="fa-solid fa-scissors"></i> BellaFace</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
            aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" href="sobrenos.html"><i class="fa-solid fa-users"></i> Sobre nós</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="servico.php"><i class="fa-solid fa-spa"></i> Serviços</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="login.php"><i class="fa-solid fa-right-to-bracket"></i> Login</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Conteúdo -->
<div class="container">
  <div class="row">
    <div class="col">
      <?php
        include("config.php");
        switch(@$_REQUEST["page"]) {
          case "novo":
            include("novo-usuario.php");
            break;
          case "listar":
            include("listar-usuario.php");
            break;
          case "salvar":
            include("salvar-usuario.php");
            break;
          case "editar":
            include("editar-usuario.php");
            break;
          default:
            echo "<h1 class='text-center'>Bem-vindos ao BellaFace!</h1>";
        }
      ?>
    </div>
  </div>
  <div class="text-center mt-4">
    <img src="imag1.jpg" alt="Imagem de boas-vindas" class="img-fluid rounded mx-auto d-block" style="max-width: 450px; height: 450px;">
  </div>
</div>

<!-- Footer -->
<footer>
  <div class="container">
    <p>&copy; 2025 Salão <strong>BellaFace</strong>. Todos os direitos reservados.</p>
  </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
