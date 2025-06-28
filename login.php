<?php
session_start();
require_once("config.php");

$mensagem = "";

// Login
if (isset($_POST['acao']) && $_POST['acao'] === "login") {
    $email = $_POST["email"];
    $senha = $_POST["senha"];

    $sql = "SELECT id, nome, nivel FROM usuarios WHERE email = ? AND senha = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $senha);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $nome, $nivel);
        $stmt->fetch();

        $_SESSION["usuario_id"] = $id;
        $_SESSION["usuario_nome"] = $nome;
        $_SESSION["usuario_nivel"] = $nivel;

        if ($nivel == 3) {
            header("Location: adm.php");
        } elseif ($nivel == 2) {
            header("Location: profissional.php");
        } else {
            header("Location: agenda.php");
        }
        exit();
    } else {
        $mensagem = "❌ E-mail ou senha incorretos!";
    }
    $stmt->close();
}

// Cadastro
if (isset($_POST['acao']) && $_POST['acao'] === "cadastrar") {
    $nome = $_POST["nome"];
    $email = $_POST["email"];
    $senha = $_POST["senha"];
    $data_nasc = $_POST["data_nasc"];
    $nivel = 1;

    $sql = "INSERT INTO usuarios (nome, email, senha, data_nasc, nivel) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $nome, $email, $senha, $data_nasc, $nivel);

    if ($stmt->execute()) {
        $mensagem = "✅ Usuário cadastrado com sucesso! Faça login.";
    } else {
        $mensagem = "❌ Erro ao cadastrar: " . $stmt->error;
    }
    $stmt->close();
}

// Recuperar senha
if (isset($_POST['acao']) && $_POST['acao'] === "recuperar") {
    $email = $_POST["email"];
    $nova_senha = $_POST["nova_senha"];

    $sql = "UPDATE usuarios SET senha = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $nova_senha, $email);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        $mensagem = "✅ Senha redefinida com sucesso!";
    } else {
        $mensagem = "❌ E-mail não encontrado ou erro ao atualizar.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Login - Salão de Beleza</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f8bbd0, #ffe0e9);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
            position: relative;
        }

        h2 {
            margin-bottom: 20px;
            color: #5d478b;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 10px;
            background-color: #f9f9f9;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #b39ddb;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        button:hover {
            background-color: #9575cd;
        }

        .link {
            margin-top: 15px;
            font-size: 14px;
        }

        .link a {
            color: #7e57c2;
            text-decoration: none;
            font-weight: bold;
        }

        .link a:hover {
            text-decoration: underline;
        }

        .mensagem {
            margin-top: 20px;
            color: #c62828;
            font-weight: bold;
        }

        .formulario {
            display: none;
        }

        #login {
            display: block;
        }

        .sair {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: #f48fb1;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .sair:hover {
            background-color: #ec407a;
        }
    </style>
</head>
<body>
    <div class="container">
        <a class="sair" href="index.php">Sair</a>

        <!-- Login -->
        <div class="formulario" id="login">
            <h2>Login</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="login">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <button type="submit">Entrar</button>
            </form>
            <div class="link">
                <a href="#" onclick="mostrar('cadastro')">Cadastre-se</a> |
                <a href="#" onclick="mostrar('recuperar')">Esqueci a senha</a>
            </div>
        </div>

        <!-- Cadastro -->
        <div class="formulario" id="cadastro">
            <h2>Cadastro</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="cadastrar">
                <input type="text" name="nome" placeholder="Nome" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="senha" placeholder="Senha" required>
                <input type="date" name="data_nasc" required>
                <button type="submit">Cadastrar</button>
            </form>
            <div class="link">
                <a href="#" onclick="mostrar('login')">Já tem conta? Login</a>
            </div>
        </div>

        <!-- Recuperar Senha -->
        <div class="formulario" id="recuperar">
            <h2>Recuperar Senha</h2>
            <form method="POST">
                <input type="hidden" name="acao" value="recuperar">
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="nova_senha" placeholder="Nova senha" required>
                <button type="submit">Redefinir</button>
            </form>
            <div class="link">
                <a href="#" onclick="mostrar('login')">Voltar ao login</a>
            </div>
        </div>

        <!-- Mensagem -->
        <?php if (!empty($mensagem)) echo "<div class='mensagem'>$mensagem</div>"; ?>
    </div>

    <script>
        function mostrar(id) {
            document.querySelectorAll('.formulario').forEach(f => f.style.display = 'none');
            document.getElementById(id).style.display = 'block';
        }
    </script>
</body>
</html>
