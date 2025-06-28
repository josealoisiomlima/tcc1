<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Painel do Administrador</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eaeaea;
            padding: 30px;
        }
        .painel {
            max-width: 600px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 0 10px #ccc;
        }
        h2 {
            margin-bottom: 20px;
        }
        a.botao {
            display: block;
            margin: 15px auto;
            padding: 12px 20px;
            background-color: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 16px;
            width: 80%;
        }
        a.botao:hover {
            background-color: #2980b9;
        }
        .sair {
            margin-top: 30px;
            background-color: #e74c3c;
        }
        .sair:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <div class="painel">
        <h2>Bem-vindo, <?= $_SESSION['admin'] ?>!</h2>
        <a class="botao" href="cad_profissional.php">Cadastrar Profissional</a>
        <a class="botao" href="agenda.php">Agenda Geral</a>
        <a class="botao" href="relatorio.php">Relatório de Faturamento</a>
        <a class="botao sair" href="logout.php">Sair</a>
    </div>
</body>
</html>
