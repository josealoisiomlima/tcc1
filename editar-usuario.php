<?php
session_start();
require_once("config.php");

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id = $_SESSION['usuario_id'];
$mensagem = "";

// Atualizar os dados
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $data_nasc = $_POST["data_nasc"];
    $telefone = $_POST["telefone"];

    $sql = "UPDATE usuarios SET nome = ?, email = ?, senha = ?, data_nasc = ?, telefone = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $nome, $email, $senha, $data_nasc, $telefone, $id);

    if ($stmt->execute()) {
        $mensagem = "✅ Dados atualizados com sucesso.";
    } else {
        $mensagem = "❌ Erro ao atualizar: " . $stmt->error;
    }
    $stmt->close();
}

// Buscar os dados atuais
$sql = "SELECT nome, email, senha, data_nasc, telefone FROM usuarios WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$resultado = $stmt->get_result();
$usuario = $resultado->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8" />
    <title>Editar Perfil</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: rgb(116, 117, 190);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background: #fff;
            width: 100%;
            max-width: 450px;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        h2 {
            margin-bottom: 20px;
            color: #333;
        }
        input {
            width: 100%;
            padding: 10px;
            margin-top: 8px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        button {
            width: 100%;
            padding: 12px;
            background-color: rgb(118, 46, 185);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: rgb(141, 54, 199);
        }
        .mensagem {
            margin-top: 20px;
            color: red;
            font-weight: bold;
        }
        .sair {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #6725aa;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .sair:hover {
            background-color: rgb(141, 54, 199);
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="agenda.php" class="sair">Minha Agenda</a>
        <h2>Editar Perfil</h2>
        <form method="post" action="">
            <input type="text" name="nome" placeholder="Nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required />
            <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($usuario['email']) ?>" required />
            <input type="text" name="senha" placeholder="Senha" value="<?= htmlspecialchars($usuario['senha']) ?>" required />
            <input type="date" name="data_nasc" placeholder="Data de Nascimento" value="<?= htmlspecialchars($usuario['data_nasc']) ?>" required />
            <input type="text" name="telefone" placeholder="Telefone" value="<?= htmlspecialchars($usuario['telefone']) ?>" required />
            <button type="submit">Salvar Alterações</button>
        </form>
        <?php if ($mensagem): ?>
            <p class="mensagem"><?= $mensagem ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
